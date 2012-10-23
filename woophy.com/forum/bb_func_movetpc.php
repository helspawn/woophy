<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
Latest File Update: 2006-May-02
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

if ($step!=1 and $step!=0) $step=0;
//0 - 1st step, 1-edit concrete

if ($topic!=0 and ($logged_admin==1 or $isMod==1) and $rt=db_simpleSelect(0,$Tt,'topic_title','topic_id','=',$topic) and $rf=db_simpleSelect(0,$Tf,'count(*)') and $rf[0]>0 and $row=db_simpleSelect(0,$Tf,'forum_id, forum_name','','','','forum_order')) {

if($step==1) {

if(isset($_POST['forumWhere'])) $forum_id=$_POST['forumWhere']+0; else $forum_id=0;

if ($forum_id!=0 and $ff=db_simpleSelect(0,$Tf,'forum_id','forum_id','=',$forum_id)) {
$u1=updateArray(array('forum_id'),$Tt,'topic_id',$topic);
$u2=updateArray(array('forum_id'),$Tp,'topic_id',$topic);

db_calcAmount($Tp,'forum_id',$forum_id,$Tf,'posts_count');
db_calcAmount($Tp,'forum_id',$forum,$Tf,'posts_count');
db_calcAmount($Tt,'forum_id',$forum_id,$Tf,'topics_count');
db_calcAmount($Tt,'forum_id',$forum,$Tf,'topics_count');

if ($u1>0 and $u2>0) {

/* If moving to closed forum, remove all forbidden subscribers */
if(in_array($forum_id,$clForums)){
if($row=db_simpleSelect(0,$Ts,'user_id','topic_id','=',$topic)){
$delstr='(';
do{
if(!isset($clForumsUsers[$forum_id]) OR (isset($clForumsUsers[$forum_id]) and !in_array($row[0],$clForumsUsers[$forum_id]))) db_delete($Ts,'user_id','=',$row[0],'topic_id','=',$topic);
}
while($row=db_simpleSelect(1));
}
}

$title=$l_topicMoved;
$errorMSG=$l_topicMoved;
if(isset($mod_rewrite) and $mod_rewrite) $urlp="{$forum_id}_{$topic}_0.html"; else $urlp="{$indexphp}action=vthread&amp;forum={$forum_id}&amp;topic={$topic}";
$correctErr="<a href=\"{$main_url}/{$urlp}\">$l_goTopic</a>";
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}
else {
$title=$l_itseemserror;
$errorMSG=$l_itseemserror;
$correctErr="<a href=\"{$main_url}/{$indexphp}action=vthread&amp;topic=$topic&amp;forum=$forum&amp;page=$page\">$l_back</a>";
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}

}
else {
$title=$l_forbidden;
$errorMSG=$l_forbidden;
$correctErr=$backErrorLink;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}

}

else{
$frm=$forum;
$st=1;
//include($pathToFiles.'bb_func_forums.php');//CHANGED 12.06.07 by MG: include in index.php
$topicTitle=$rt[0];
echo load_header(); echo ParseTpl(makeUp('tools_move_topic'));
}

}
else {
$title=$l_forbidden;
$errorMSG=$l_forbidden;
$correctErr=$backErrorLink;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}
?>