<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004-2006 Paul Puzyrev, Sergei Larionov. www.minibb.net
Latest File Update: 2006-Dec-04
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

if($user_id==0) $l_sub_post_msg=$l_sub_post_msg;

//added by MG:
$msg_logged_in = $user_id==0 ? ' You have to <a href="'.ABSURL.REQUEST_PATH.'/Login">sign in</a> to post messages' : '';
$str_disabled = $user_id==0 ? 'disabled="true" ' : '';
//==

$listPosts=''; $deleteTopic='';
$displayQuote='true';

/*** CHECK ***/
if($topicData and $topicData[4]==$forum){

if(!isset($preModerationType) or $preModerationType==0) $topicName=$topicData[0]; elseif($preModerationType>0 and isset($premodTopics) and in_array($topic, $premodTopics)) $topicName=$l_topicQueued; else $topicName=$topicData[0];

if ($topicName=='') $topicName=$l_emptyTopic;
$topicStatus=$topicData[1];
$topicSticky=$topicData[6];
$topicPoster=$topicData[2];
$topicPosterName=$topicData[3];
$topic_views=$topicData[7]+1;
$topicTime=$topicData[8];
}
else {
header('Status: 404 Not Found');
$metaRobots='NOINDEX,NOFOLLOW';
$errorMSG=$l_topicnotexists; $correctErr='';
$title=$title.$l_topicnotexists;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}

$st=0; $frm=$forum;
//include($pathToFiles.'bb_func_forums.php');//CHANGED 12.06.07 by MG: include in index.php

if(!isset($forumsArray[$forum])){
header('Status: 404 Not Found');
$errorMSG=$l_forumnotexists; $correctErr=$backErrorLink;
$title=$title.$l_forumnotexists;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}
unset($result);unset($countRes);

$forumName=$forumsArray[$forum][0]; $forumIcon=$forumsArray[$forum][1]; $forum_desc=$forumsArray[$forum][2];

/* form reply */
$l_messageABC=$l_sub_answer;
if ($topicStatus!=1) {
$emailCheckBox=emailCheckBox();

$allowForm=($user_id==1 or $isMod==1);
$c1=(in_array($forum,$clForums) and isset($clForumsUsers[$forum]) and !in_array($user_id,$clForumsUsers[$forum]) and !$allowForm);
$c4=(isset($roForums) and in_array($forum, $roForums) and !($user_id==1 or $isMod==1));

if ($c1 or $c4){
$mainPostForm='';$mainPostArea='';
$nTop=0;
$displayQuote='false';
}else{
$mainPostForm=ParseTpl(makeUp('main_post_form'));
$mainPostArea=makeUp('main_post_area');
$nTop=1;
}
}
else {
$mainPostArea=makeUp('main_post_closed');
$displayQuote='false';
}
$mainPostArea=ParseTpl($mainPostArea);

if($displayQuote=='true') $eachReply='<a href="#newreply">'.$l_reply.'</a>&nbsp;';
else $eachReply='';


/* actual */

$numRows=$topicData[5];

$topicDesc=0;
$topic_reverse='';
if(isset($themeDesc) and in_array($topic,$themeDesc)) {
$topicDesc=1;
$topic_reverse="<img src=\"{$img_url}/forum_icons/topic_reverse.gif\" align=\"middle\" border=\"0\" alt=\"\" />&nbsp;";
}

if($page==-1 and $topicDesc==0) $page=pageChk($page,$numRows,$viewmaxreplys);
elseif($page==-1 and $topicDesc==1) $page=0;

if(isset($mod_rewrite) and $mod_rewrite) $urlp="{$main_url}/{$forum}_{$topic}_"; else $urlp="{$main_url}/{$indexphp}action=vthread&amp;forum=$forum&amp;topic=$topic&amp;page=";

$pageNav=pageNav($page,$numRows,$urlp,$viewmaxreplys,FALSE);
$makeLim=makeLim($page,$numRows,$viewmaxreplys);

$anchor=1;
if($page==0) $anchor2=1; else $anchor2=($page)*$viewmaxreplys+1;
$i=1;
$ii=0;

if(isset($themeDesc) and in_array($topic,$themeDesc)) $srt='DESC'; else $srt='ASC';

/* User info in posts */
if(isset($GLOBALS['userInfoInPosts']) and is_array($GLOBALS['userInfoInPosts'])){
$userInfo=array();
if($cols=db_simpleSelect(0,$Tp,'poster_id','topic_id','=',$topic,'post_id '.$srt,$makeLim)){
do{
if(!in_array($cols[0],$userInfo)) $userInfo[]=$cols[0];
}
while($cols=db_simpleSelect(1));
}
$xtr=getClForums($userInfo,'where','',$dbUserId,'or','=');
unset($userInfo);
if($cols=db_simpleSelect(0,$Tu,$dbUserId.','.implode(',',$userInfoInPosts))){
for($i=0;$i<sizeof($userInfoInPosts);$i++) ${'userInfo_'.$userInfoInPosts[$i]}=array();
do for($i=0;$i<sizeof($userInfoInPosts);$i++) {
if(function_exists('parseUserInfo_'.$userInfoInPosts[$i])) $cols[$i+1]=call_user_func('parseUserInfo_'.$userInfoInPosts[$i],$cols[$i+1]);
${'userInfo_'.$userInfoInPosts[$i]}[$cols[0]]=$cols[$i+1];
}
while($cols=db_simpleSelect(1));
}
unset($xtr);
}
/* --User info in posts */

//echo $topicData[5];

if($cols=db_simpleSelect(0,$Tp,'poster_id, poster_name, post_time, post_text, poster_ip, post_status, post_id','topic_id','=',$topic,'post_id '.$srt,$makeLim)){

if($page==0 and isset($enableViews) and $enableViews) updateArray(array('topic_views'),$Tt,'topic_id',$topic);

$tpl=makeUp('main_posts_cell');

$groupDelete=0;

do{
if($i>0) $bg='tbCel1'; else $bg='tbCel2';

$poster_id=$cols[0];
$postDate=convert_date($cols[2]);

if(!($user_id==1 or $isMod==1 or $user_id==0)) $availEditMes=($topicStatus==0 and (time()-strtotime($cols[2])<$useredit OR $useredit==0 ) );
else $availEditMes=TRUE;

if($availEditMes) $allowedEdit="<a href=\"{$main_url}/{$indexphp}action=editmsg&amp;topic=$topic&amp;forum=$forum&amp;post={$cols[6]}&amp;page=$page&amp;anchor={$cols[6]}\">$l_edit</a>";
else $allowedEdit='';

if ($logged_admin==1 or $isMod==1) $viewIP=' '.$l_sepr.' IP: '.'<a href="'.$indexphp.'action=viewipuser&amp;postip='.$cols[4].'">'.$cols[4].'</a>';
else $viewIP='';

if ($logged_admin==1 or $isMod==1 or (isset($userDeleteMsgs) and $userDeleteMsgs>0 and $user_id!=0 and $user_id==$cols[0] and $availEditMes) ){
if($topicData[5]==1) $deleteM='';
//and (($ii==0 and $page==0 and $topicDesc==0) or ($topicDesc==1 and $numRows==$viewmaxreplys*$page+$i+1))) 
else {
$deleteM=<<<out
<a href="JavaScript:confirmDelete({$cols[6]},0)" onmouseover="window.status='{$l_deletePost}'; return true;" onmouseout="window.status=''; return true;">$l_deletePost</a>
out;
if (($logged_admin==1 or $isMod==1) and isset($enableGroupMsgDelete)) { $groupDelete++; $deleteBox="<br /><input type=\"checkbox\" name=\"deleteAll[]\" value=\"{$cols[6]}\" />"; } else $deleteBox='';
}

$allowed=$allowedEdit." ".$deleteM;
}
else {
$cols[4]='';
if ($topicData[1]==0 and $user_id==$cols[0] and $user_id !=0 and $cols[5]!=2 and $cols[5]!=3) {
$allowed=$allowedEdit;
}
else {
$allowed='';
}
}

# post_status: 0-clear (available for edit), 1-edited by author, 2-edited by admin (available only for admin), 3 - edited by mod
if ($cols[5]==0) {
$editedBy='';
}
else {
$editedBy=" $l_sepr $l_editedBy";
if($cols[5]==2) $we="<a href=\"{$main_url}/{$indexphp}action=userinfo&amp;user=1\">{$l_admin}</a>";
elseif($cols[5]==1) $we=$cols[1];
elseif($cols[5]==3) $we="<a href=\"{$main_url}/{$indexphp}action=stats#mods\">{$l_moderator}</a>";
else $we='N/A';
$editedBy.=$we;
}

if ($cols[0]!=0) {
$cc=$cols[0];
if (isset($userRanks[$cc])) $ins=$userRanks[$cc];
elseif (isset($mods[$forum]) and is_array($mods[$forum]) and in_array($cc,$mods[$forum])) $ins=$l_moderator;
else { $ins=($cc==1?$l_admin:$l_member); }
if(!defined('NOFOLLOW')) $nof=' rel="nofollow"'; else $nof='';
$viewReg="<a href=\"{$main_url}/{$indexphp}action=userinfo&amp;user={$cc}\"{$nof}>$ins</a>";
}
else $viewReg='';

$posterName=$cols[1];
file_put_contents("bb.out", "here\n");
include_once CLASS_PATH.'Utils.class.php';
//added 07.06.07 by MG
//$posterAvatar = AVATARS_URL.(Utils::s3_exists(AWS_BUCKET,AVATARS_RELATIVE_PATH.$cols[0].'.jpg') ? $cols[0] : 'default').'.jpg';
$posterAvatar = AVATARS_URL.$cols[0].'.jpg';
file_put_contents("bb.out", "here2\n");
$posterUrl = ROOT_PATH.'member/'.urlencode($cols[1]);
//end added

if(substr_count($cols[1], '&#039;')==0) $posterNameJs=$cols[1];
else {
if(get_magic_quotes_gpc()==1) $apos=chr(92).chr(92).chr(92); else $apos=chr(92);
$posterNameJs=str_replace('&#039;', $apos.'&#039;', $cols[1]);
}

if(!isset($preModerationType) or $preModerationType==0) $posterText=$cols[3]; elseif($preModerationType>0 and isset($premodPosts) and in_array($cols[6], $premodPosts)) $posterText=$l_postQueued; else $posterText=$cols[3];

if(function_exists('parseMessage')) parseMessage();
$listPosts.=ParseTpl($tpl);

$i=-$i;
if($ii==0) {
$ii++;
$description=substr(strip_tags(str_replace(array("\r\n","\r","\n",'"'),' ',$cols[3])),0,1000);
}

$anchor++; $anchor2++;
}
while($cols=db_simpleSelect(1));
unset($result);unset($countRes);

//

if ($logged_admin==1 or $isMod==1 or (isset($userDeleteMsgs) and $userDeleteMsgs==2 and $user_id!=0 and $user_id==$topicPoster and ($useredit==0 or time()-strtotime($topicTime)<$useredit) and $topicStatus==0 ) ) {
$deleteTopic="$l_sepr <a href=\"JavaScript:confirmDelete({$topic},1)\" onmouseover=\"window.status='{$l_deleteTopic}'; return true;\" onmouseout=\"window.status=''; return true;\">$l_deleteTopic</a>";
}

if ($logged_admin==1 or $isMod==1) {

if(isset($enableGroupMsgDelete) and $groupDelete>0) $deleteAllMsgs="$l_sepr <a href=\"JavaScript:confirmDelete(0,2);\">$l_deleteAllMsgs</a>";

$moveTopic="$l_sepr <a href=\"{$main_url}/{$indexphp}action=movetopic&amp;forum=$forum&amp;topic=$topic&amp;page=$page\">$l_moveTopic</a>";

if ($topicStatus==0) { $chstat=1; $cT=$l_closeTopic; }
else { $chstat=0; $cT=$l_unlockTopic; }
$closeTopic="<a href=\"{$main_url}/{$indexphp}action=locktopic&amp;forum=$forum&amp;topic=$topic&amp;chstat=$chstat\">$cT</a>";

if ($topicSticky==0) { $chstat=1; $cT=$l_makeSticky; }
else { $chstat=0; $cT=$l_makeUnsticky; }
$stickyTopic="$l_sepr <a href=\"{$main_url}/{$indexphp}action=unsticky&amp;forum=$forum&amp;topic=$topic&amp;chstat=$chstat\">$cT</a>";

$extra=1;
if ($logged_admin==1 and $cnt=db_simpleSelect(0,$Ts,'count(*)','topic_id','=',$topic) and $cnt[0]>0) $subsTopic="$l_sepr <a href=\"{$main_url}/{$bb_admin}action=viewsubs&amp;topic=$topic\">$l_subscriptions</a>"; else $subsTopic='';
}

elseif (($user_id==$topicPoster and $user_id!=0 and $user_id!=1) and $topicSticky!=1) {
if ($topicStatus==0 and $userUnlock!=2) $closeTopic="<a href=\"{$main_url}/{$indexphp}action=locktopic&amp;forum=$forum&amp;topic=$topic&amp;chstat=1\">$l_closeTopic</a>";
elseif($topicStatus==1 and $userUnlock==1 and $userUnlock!=2) $closeTopic="<a href=\"{$main_url}/{$indexphp}action=locktopic&amp;forum=$forum&amp;topic=$topic&amp;chstat=0\">$l_unlockTopic</a>";
else $closeTopic='';
}

if($page>0 or $numRows>$viewmaxreplys) $tpage=' - ('.($page+1).')'; else $tpage='';
$title=strip_tags($topicName).' - '.str_replace(' - ','',$title).$tpage;

}//if posts

if(isset($mod_rewrite) and $mod_rewrite) $linkToForums="{$main_url}/{$forum}_0.html"; else $linkToForums="{$main_url}/{$indexphp}action=vtopic&amp;forum={$forum}";

echo load_header(); echo ParseTpl(makeUp('main_posts'));
?>
