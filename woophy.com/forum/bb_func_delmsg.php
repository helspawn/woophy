<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
Latest File Update: 2006-Dec-08
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

if(isset($enableGroupMsgDelete) and isset($_POST['deleteAll']) and is_array($_POST['deleteAll']) and sizeof($_POST['deleteAll'])>0) {
$deleteAll=$_POST['deleteAll'];
}
else {
if(isset($_POST['post'])) $post=$_POST['post']+0; elseif(isset($_GET['post'])) $post=$_GET['post']+0; else $post=0;
$deleteAll=array($post);
}

if($topicData[1]==1 and $user_id!=1 and $isMod==0) $canDelete=FALSE;
elseif(isset($post)){
$canDelete=TRUE;
if($rw=db_simpleSelect(0,$Tp,'poster_id,post_time','post_id','=',$post)) {
$poster_id=$rw[0];
$time_diff=strtotime('now')-strtotime($rw[1]);
if($useredit!=0 and $time_diff>$useredit) $canDelete=FALSE;
}
else {
$poster_id=-1;
$canDelete=FALSE;
}
}//isset post
else $canDelete=FALSE;

if($logged_admin==1 or $isMod==1 or ($canDelete and isset($userDeleteMsgs) and $userDeleteMsgs>0 and $user_id!=0 and $user_id==$poster_id) ) {

foreach($deleteAll as $post){
//if($first!=$post) {
if(!isset($poster_id)) { $rww=db_simpleSelect(0,$Tp,'poster_id','post_id','=',$post); $poster_id=$rww[0]; }
db_delete($Tp,'post_id','=',$post);
if($poster_id!=0) db_calcAmount($Tp,'poster_id',$poster_id,$Tu,$dbUserSheme['num_posts'][1],$dbUserId);
//}
if(file_exists($pathToFiles.'bb_plugins2.php')) require($pathToFiles.'bb_plugins2.php');
}// deletion cycle

if($pp=db_simpleSelect(0,$Tp,'post_id, post_time, poster_name','topic_id','=',$topic,'post_id DESC',1)){
$topic_last_post_id=$pp[0];
$topic_last_post_time=$pp[1];
$topic_last_poster=$pp[2];
updateArray(array('topic_last_post_id', 'topic_last_post_time', 'topic_last_poster'),$Tt,'topic_id',$topic);
db_calcAmount($Tp,'forum_id',$forum,$Tf,'posts_count');
db_calcAmount($Tp,'topic_id',$topic,$Tt,'posts_count');

if (defined('DELETE_PREMOD')) return;

if(isset($metaLocation)) { $meta_relocate="{$main_url}/{$indexphp}action=vthread&forum={$forum}&topic={$topic}&page={$page}"; echo ParseTpl(makeUp($metaLocation)); exit; } else { 
if(isset($mod_rewrite) and $mod_rewrite) $urlp="{$forum}_{$topic}_{$page}.html"; else $urlp="{$indexphp}action=vthread&forum={$forum}&topic={$topic}&page={$page}";
header("Location: {$main_url}/{$urlp}"); exit;
}

}
else {
$errorMSG=$l_itseemserror; $correctErr=$backErrorLink;
}

}
else {
$errorMSG=$l_forbidden; $correctErr=$backErrorLink;
}

$title.=$errorMSG;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
?>