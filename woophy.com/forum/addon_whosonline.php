<?php

/*
addon_whosonline.php : instant who's online box for miniBB 2.
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2005-2007 Paul Puzyrev. www.minibb.net
Latest File Update: 2007-Feb-04
*/

/* Plugin options */

$woDir=$pathToFiles.'shared_files'; //general path to your directory, for storing temporary plugin file and data. On Linux/FreeBSD/Unix systems, it must have 0777 privileges. Many miniBB plugins will use this directory, so if you already have one, it is better to keep one directory for all shared files. No slash at the end!

$enableAnonymous=1; //enable anonymous visits (by IP) - set to 0 (no) or 1 (yes) 

$expireTime=300; // in seconds - amount of activity time, after that time user activity is broken. 5 min = 300 sec.

$l_whosOnline='Online now: '; //language def.
$l_guestsOnline='Guests'; //language def.
$l_loggedOnline='Members'; //language def.
$l_onlineRecord='Most users ever online:'; //language def.

/* End of options - don't edit below */

if (!defined('INCLUDED776')) die ('Fatal error.');

function elementss($array_name,$r_array,$cont=''){
$cont.='array(';
foreach($r_array as $key=>$val){
$cont.="'$key'=>";
if(is_array($val)) $cont.=elementss($key,$val).',';
else {
	//added 2008.03.07 by MG
	$val = str_replace('\'', '\\\'', $val);
	$cont.="'{$val}',";
	}
}
return substr($cont,0,strlen($cont)-1).')';
}

function printOutArray($array_name){
if(is_array($GLOBALS[$array_name])) $r_array=$GLOBALS[$array_name]; else $r_array=array();
if(sizeof($r_array)==0) return "\${$array_name}=array()";
else return "\${$array_name}=".elementss($array_name,$r_array);
}

function writeFile($w_anonymous_visits,$w_logged_users,$w_record){
$cont='<?php'."\n".printOutArray('w_anonymous_visits').";\n".printOutArray('w_logged_users').";\n".printOutArray('w_record').";\n?>";
$tmpfname=tempnam($GLOBALS['woDir'],'');
$fl=fopen($tmpfname,'w');
//flock($fl,LOCK_EX);
fwrite($fl,$cont);
//flock($fl,LOCK_UN);
fclose($fl);
if (@rename ($tmpfname, $GLOBALS['woDir'].'/addon_whosonline_data.php')==FALSE){
unlink($GLOBALS['woDir'].'/addon_whosonline_data.php');
rename($tmpfname, $GLOBALS['woDir'].'/addon_whosonline_data.php');
}
umask(0);
chmod($GLOBALS['woDir'].'/addon_whosonline_data.php', 0777);
}

$registeredList='';

$w_anonymous_visits=array(); $w_logged_users=array(); $w_record=array();
include($woDir.'/addon_whosonline_data.php');

if($user_id==1 and isset($_GET['resetwonline'])) {
$w_record[1]=0; $w_record[2]=0;
}

/* Associate any user with unique session number */
if(!isset($_COOKIE[$cookiename.'_anol'])) {
$tsess=rand(1,999).time();
setcookie($cookiename.'_anol', $tsess, 0, $cookiepath, $cookiedomain, $cookiesecure);
if($user_id==0) $tsess=FALSE;
}
else $tsess=trim($_COOKIE[$cookiename.'_anol'])+0;

/* Handling registered users. */
if($user_id!=0) {
//$w_logged_users[$user_id]=array($user_usr,time(),$tsess);
$w_logged_users[$user_id]=array('',time(),$tsess);//changed 31-10-08 by MG: do not store usernames, some names cause bug: "Most users ever online" is not stored correctly
if(isset($w_anonymous_visits[$tsess])) unset($w_anonymous_visits[$tsess]);
}

if($enableAnonymous==1){

if($tsess and $user_id==0) $w_anonymous_visits[$tsess]=time();

/* Deleting unnecessary anonymous users */
foreach($w_anonymous_visits as $key=>$val) if((time()-$val)>$expireTime) unset($w_anonymous_visits[$key]);

/* Counting anonymous users */
$guestsCount=sizeof($w_anonymous_visits);
if(!$tsess) $guestsCount+=1;
}
else $guestsCount='N/A';

/* Delete unnecessary registered users */
foreach($w_logged_users as $key=>$val) {
//(isset($w_anonymous_visits[$val[2]]) or
if( ((time()-$val[1])>$expireTime) OR ($user_id==0 and $val[2]==$tsess) ) unset($w_logged_users[$key]);
}

if((sizeof($w_logged_users)+sizeof($w_anonymous_visits))>($w_record[1]+$w_record[2])) $w_record=array(0=>date('Y-m-d H:i:s'),1=>sizeof($w_anonymous_visits), 2=>sizeof($w_logged_users));

writeFile($w_anonymous_visits,$w_logged_users,$w_record);

/* Counting registered users */
$registeredCount=sizeof($w_logged_users);

//ADDED BY MG:uncomment to display list of members online
/*
if($registeredCount>0){
$registeredList.='[';
foreach($w_logged_users as $key=>$val){
$registeredList.=" <a href=\"{$main_url}/{$indexphp}action=userinfo&amp;user={$key}\">{$val[0]}</a>, ";
}
$registeredList=substr($registeredList,0,strlen($registeredList)-2).' ]';
}
*/

$recDate=convert_date($w_record[0]);
$recTotal=$w_record[1]+$w_record[2];

$whosOnline="<table class=\"tbTransparent blocktitle whosonline\">
<tr><td class=\"tbTransparent\"><span class=\"txtSm\">{$l_onlineRecord} {$recTotal} [{$recDate}]<br/>{$l_guestsOnline} {$w_record[1]}, {$l_loggedOnline} {$w_record[2]}</span></td><td style=\"text-align:right;\" class=\"tbTransparent\" style=\"text-align:right\"><img src=\"".ROOT_PATH."images/forum_icons/whosonline.gif\" />&nbsp;<span class=\"txtSm\">{$l_whosOnline} {$l_guestsOnline} {$guestsCount}, {$l_loggedOnline} {$registeredCount}</span></td></tr>
</table>";


?>