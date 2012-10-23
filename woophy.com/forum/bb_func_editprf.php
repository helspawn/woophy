<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004-2007 Paul Puzyrev, Sergei Larionov. www.minibb.net
Latest File Update: 2007-Jan-6
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

if ($user_id!=0) {

if (!isset($warning)) $warning='';
$l_fillRegisterForm='';
$editable='disabled="disabled"';
$userTitle=$l_editPrefs;
$l_passOnceAgain.=' (<span class="txtSm">'.$l_onlyIfChangePwd.')</span>';
$actionName='editprefs';

if ($userData=db_simpleSelect(0,$Tu,'*',$dbUserId,'=',$user_id)) {

$profileLink="<br /><a href=\"{$main_url}/{$indexphp}action=userinfo&amp;user={$user_id}\">{$l_about} &ldquo;{$userData[$dbUserSheme['username'][0]]}&rdquo;</a>";

include($pathToFiles.'bb_func_inslng.php');
if(isset($_POST['user_viewemail'])) $user_viewemail=$_POST['user_viewemail']; else $user_viewemail=$userData[$dbUserSheme['user_viewemail'][0]];
$showemailDown=makeValuedDropDown(array(0=>$l_no,1=>$l_yes),'user_viewemail');
if(isset($_POST['user_sorttopics'])) $user_sorttopics=$_POST['user_sorttopics']; else $user_sorttopics=$userData[$dbUserSheme['user_sorttopics'][0]];
$sorttopicsDown=makeValuedDropDown(array(0=>$l_newAnswers,1=>$l_newTopics),'user_sorttopics');
if(!isset($_POST['language'])) $language=$userData[$dbUserSheme['language'][0]]; else $language=$_POST['language'];
$languageDown=makeValuedDropDown($glang,'language');

if ($step==1) {
require($pathToFiles.'bb_func_usrdat.php');
${$dbUserSheme['username'][1]}=$userData[$dbUserSheme['username'][0]];
${$dbUserSheme['username'][2]}=$userData[$dbUserSheme['username'][0]];

$act='upd';
require($pathToFiles.'bb_func_checkusr.php');

if ($rowp=db_simpleSelect(0,$Tu,$dbUserId,$caseComp."({$dbUserSheme['user_email'][1]})",'=',strtolower(${$dbUserSheme['user_email'][1]}),'','',$dbUserId,'!=',$user_id) or (strtolower(${$dbUserSheme['user_email'][1]})==strtolower($admin_email) and $user_id!=1)) $correct=4;


$prevVals=array();

foreach($dbUserSheme as $key=>$val) {
if(strstr($key,'user_custom')) $prevVals[$key]=$userData[$dbUserSheme[$key][0]];
}

if ($correct=='ok') {
//Update db
$addFieldsGen=array('user_icq','user_website','user_occ','user_from','user_interest');

$upda=array($dbUserSheme['user_email'][1], $dbUserSheme['user_viewemail'][1], $dbUserSheme['user_sorttopics'][1], $dbUserSheme['language'][1]);

foreach($addFieldsGen as $k) if(isset($dbUserSheme[$k])) $upda[]=$dbUserSheme[$k][1];
foreach($dbUserSheme as $k=>$v) if(strstr($k,'user_custom') and isset($_POST[$v[2]]) and ($_POST[$v[2]]!='' OR (isset($prevVals[$k]) and $prevVals[$k]!='' and $_POST[$v[2]]=='' ) ) ) $upda[]=$v[1];

if($passwd!=''){
${$dbUserSheme['user_password'][1]}=writeUserPwd(${$dbUserSheme['user_password'][1]});
$upda[]=$dbUserSheme['user_password'][1];
}

/* sending confirm link on user's email if it's changed */
if($genEmailDisable!=1 and isset($closeRegister) and $closeRegister==1 and strtolower($userData[$dbUserSheme['user_email'][0]])!=strtolower(${$dbUserSheme['user_email'][2]})){
//echo $userData[$dbUserSheme['user_email'][0]].' '.${$dbUserSheme['user_email'][2]};
$upda[]=$dbUserNk; $upda[]=$dbUserAct; ${$dbUserAct}=-1;
$$dbUserNk=substr(ereg_replace("[^0-9A-Za-z]", "A", writeUserPwd(uniqid(rand()))),0,10);
$confirmCode='email'.$$dbUserNk;
$loginName=${$dbUserSheme['username'][2]};

$lng=${$dbUserSheme['language'][2]};
if(!file_exists($pathToFiles.'templates/email_user_confirm_'.$lng.'.txt')) $lng=$langOrig;

$emailMsg=ParseTpl(makeUp('email_user_confirm_'.$lng));
$sub=explode('SUBJECT>>', $emailMsg); $sub=explode('<<', $sub[1]); $emailMsg=trim($sub[1]); $sub=$sub[0];
sendMail(${$dbUserSheme['user_email'][2]}, $sub, $emailMsg, $admin_email, $admin_email);

$warning.=$l_emailChangeCode.'<br />';

}
/* --sending ... */

$upd=updateArray($upda,$Tu,$dbUserId,$user_id);
if ($upd>0) {
$title.=$l_prefsUpdated;
$warning.=$l_prefsUpdated;
if (${$dbUserSheme['user_password'][2]}!='') $warning.=', '.$l_prefsPassUpdated;
}
else {
$title.=$l_editPrefs;
$warning.=$l_prefsNotUpdated;
}

}
else {
if (!isset($l_userErrors[$correct])) $l_userErrors[$correct]=$l_undefined;
$warning.=$l_errorUserData.": <span class=warning>{$l_userErrors[$correct]}</span>";
$title.=$l_errorUserData;
}

$tpl=makeUp('user_dataform');
if($user_id==1) $tpl=preg_replace("#<!--PASSWORD-->(.*?)<!--/PASSWORD-->#is",'',$tpl);
echo load_header(); echo ParseTpl($tpl); return;
}

else {
//step=0
foreach($dbUserSheme as $k=>$v){
$fk=$v[2];
if(isset($userData[$v[0]])) $$fk=$userData[$v[0]]; else $$fk='';
}
${$dbUserSheme['user_password'][2]}='';
$passwd2='';

$title.=$l_editPrefs;
$tpl=makeUp('user_dataform');
if($user_id==1) $tpl=preg_replace("#<!--PASSWORD-->(.*?)<!--/PASSWORD-->#is",'',$tpl);
echo load_header(); echo ParseTpl($tpl); return;
}

}
else {
$title.=$l_mysql_error; $errorMSG=$l_mysql_error; $correctErr='';
$tpl=makeUp('main_warning'); 
}

}
else {
$title.=$l_forbidden; $errorMSG=$l_forbidden; $correctErr='';
$tpl=makeUp('main_warning');
}

echo load_header(); echo ParseTpl($tpl); return;
?>