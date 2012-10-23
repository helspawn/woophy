<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004-2006 Paul Puzyrev, Sergei Larionov. www.minibb.net
Latest File Update: 2006-Dec-21
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

$list_topics='';
$pageNav='';
//$forumsList='';//CHANGED 12.06.07 by MG

if(!isset($_GET['showSep'])||$_GET['showSep']==2){
$st=1; $frm=$forum;
//include($pathToFiles.'bb_func_forums.php');//CHANGED 12.06.07 by MG: include in index.php
}

if (!isset($forumsArray[$forum])) {
$errorMSG=$l_forumnotexists; $correctErr=$backErrorLink;
$title=$title.$l_forumnotexists;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}

$forumName=$forumsArray[$forum][0]; $forumIcon=$forumsArray[$forum][1]; $forum_desc=$forumsArray[$forum][2];
$description=substr(strip_tags($forum_desc),0,1000);

if($user_sort=='') $user_sort=$sortingTopics; /* Sort messages default by last answer (0) desc OR 1 - by last new topics */

$warn='';
if(!isset($_GET['showSep'])||$_GET['showSep']==2){

$numRows=$forumsArray[$forum][3];

if($numRows==0){
$errorMSG=$l_noTopicsInForum; $correctErr='';
$title=$title.$l_noTopicsInForum;
$warn=ParseTpl(makeUp('main_warning'));
}

else{

//if at least one topic exists in this forum

if(isset($mod_rewrite) and $mod_rewrite and $sortBy==0) $urlp="{$main_url}/{$forum}_";
else $urlp="{$main_url}/{$indexphp}action=vtopic&amp;forum=$forum&amp;sortBy={$user_sort}&amp;page=";

$pageNav=pageNav($page,$numRows,$urlp,$viewmaxtopic,FALSE,($user_sort==0));
$makeLim=makeLim($page,$numRows,$viewmaxtopic);

if(isset($customTopicSort) and is_array($customTopicSort) and isset($customTopicSort[$forum])) 
$defaultSorting="<br /><a href=\"{$main_url}/{$indexphp}action=vtopic&amp;forum={$forum}&amp;sortBy=2\">{$l_sortBy}&nbsp;{$customTopicSort[$forum][1]}</a>";

if( (!isset($_GET['sortBy']) or $sortBy==2) and isset($customTopicSort) and is_array($customTopicSort) and isset($customTopicSort[$forum])) { $orderBy=$customTopicSort[$forum][0];
$sortedByT=$customTopicSort[$forum][1];
$defaultSorting='';
}
elseif ($user_sort==1) $orderBy='sticky DESC,topic_id DESC';
else $orderBy='sticky DESC,topic_last_post_id DESC';

$colls=array();

if($cols=db_simpleSelect(0,$Tt,'topic_id, topic_title, topic_poster, topic_poster_name, topic_time, topic_status, posts_count, sticky, topic_views, topic_last_post_id, topic_last_post_time, topic_last_poster','forum_id','=',$forum,$orderBy,$makeLim)) {
do {
if(!isset($textLd)) $lPosts[]=$cols[9];
else { if($user_sort==0) $lPosts[]=$cols[9]; else $lPosts[]=$cols[0]; }
$colls[]=array($cols[0], $cols[1], $cols[2], $cols[3], $cols[4], $cols[5], $cols[6], $cols[7], $cols[8], $cols[9], $cols[10], $cols[11]);
}
while($cols=db_simpleSelect(1));
}

if(isset($textLd)){

if(sizeof($lPosts)>0) {
if($user_sort==0) { $ordb='post_id'; $ordSql='DESC'; } else { $ordb='topic_id'; $ordSql='ASC'; }
$xtr=getClForums($lPosts,'where','',$ordb,'or','=');
}
else $xtr='';

if($xtr!=''){
if($row=db_simpleSelect(0, $Tp, 'poster_id, poster_name, post_time, topic_id, post_text', '', '', '', 'post_id '.$ordSql))
do
if(!isset($pVals[$row[3]])) $pVals[$row[3]]=array($row[0],$row[1],$row[2],$row[4]); else continue;
while($row=db_simpleSelect(1));
unset($xtr);
}
}

$i=1;
$tpl=makeUp('main_topics_cell');

foreach($colls as $cols){

if($i>0) $bg='tbCel1';else $bg='tbCel2';
$topic=$cols[0];

$topic_reverse='';
$topic_views=$cols[8];
if(isset($themeDesc) and in_array($topic,$themeDesc)) $topic_reverse="<img src=\"{$img_url}/forum_icons/topic_reverse.gif\" style=\"vertical-align:middle;\" alt=\"\" />&nbsp;";

if(!isset($preModerationType) or $preModerationType==0) $topicTitle=$cols[1]; elseif($preModerationType>0 and isset($premodTopics) and in_array($cols[0], $premodTopics)) $topicTitle=$l_topicQueued; else $topicTitle=$cols[1];

if(trim($topicTitle)=='') $topicTitle=$l_emptyTopic;
if(isset($_GET['h']) and $_GET['h']==$topic) $topicTitle='&raquo; '.$topicTitle;

$numReplies=$cols[6]; if($numReplies>=1) $numReplies-=1;
if ($cols[3]=='') $cols[3]=$l_anonymous; $topicAuthor=$cols[3];
$whenPosted=convert_date($cols[4]);

if(isset($pVals[$topic][0])) $lastPosterID=$pVals[$topic][0]; else $lastPosterID='N/A';

if($numReplies>0 and isset($cols[11]) and $cols[11]!='') $lastPoster=$cols[11];
elseif($numReplies>0 and isset($pVals[$topic][1])) $lastPoster=$pVals[$topic][1];
else $lastPoster='&mdash;';

if($numReplies>0 and isset($cols[10])) $lastPostDate=convert_date($cols[10]);
elseif($numReplies>0 and isset($pVals[$topic][2])) $lastPostDate=convert_date($pVals[$topic][2]);
else $lastPostDate='';

if(isset($textLd) and isset($pVals[$topic][3])){
$lptxt=($textLd==1?$pVals[$topic][3]:strip_tags($pVals[$topic][3]));
if(!isset($preModerationType) or $preModerationType==0) $lastPostText=$lptxt;
elseif($preModerationType>0 and isset($premodTopics) and in_array($cols[0], $premodTopics)) $lastPostText=($textLd==1?$l_postQueued:strip_tags($l_postQueued));
else $lastPostText=$lptxt;
}

if(isset($mod_rewrite) and $mod_rewrite) $urlp="{$main_url}/{$forum}_{$topic}_"; else $urlp="{$main_url}/{$indexphp}action=vthread&amp;forum=$forum&amp;topic=$topic&amp;page=";

$pageNavCell=pageNav(0,$numReplies+1,$urlp,$viewmaxreplys,TRUE);

if ($cols[7]==1 and $cols[5]==1) $tpcIcon='stlock';
elseif ($cols[7]==1) $tpcIcon='sticky';
elseif ($cols[5]==1) $tpcIcon='locked';
elseif ($numReplies<=0) $tpcIcon='empty';
elseif ($numReplies>=$viewmaxreplys) $tpcIcon='hot';
else $tpcIcon='default';

if(isset($mod_rewrite) and $mod_rewrite) $linkToTopic="{$main_url}/{$forum}_{$topic}_0.html"; else $linkToTopic="{$main_url}/{$indexphp}action=vthread&amp;forum={$forum}&amp;topic={$topic}";

if(function_exists('parseTopic')) parseTopic();
$list_topics.=ParseTpl($tpl);
$i=-$i;
}
}//request ok

$newTopicLink='<a href="'.$main_url.'/'.$indexphp.'action=vtopic&amp;forum='.$forum.'&amp;showSep=1">'.$l_new_topic.'</a>';
}//if not showsep

$l_messageABC=$l_message;

$emailCheckBox=emailCheckBox();

$mainPostForm=ParseTpl(makeUp('main_post_form'));

if($page>0 or (isset($numRows) and $numRows>$viewmaxtopic) ) $tpage=' - ('.($page+1).')'; else $tpage='';
$title=$forumName.' - '.str_replace(' - ','',$title).$tpage;

if(!isset($_GET['showSep'])) $main=makeUp('main_topics');
else $main='';

$nTop=1;
$allowForm=($user_id==1 or $isMod==1);
$c1=(in_array($forum,$clForums) and isset($clForumsUsers[$forum]) and !in_array($user_id,$clForumsUsers[$forum]) and !$allowForm);
$c3=(isset($poForums) and in_array($forum, $poForums) and !$allowForm);
$c4=(isset($roForums) and in_array($forum, $roForums) and !$allowForm);

if ($c1 or $c3 or $c4) {
$main=preg_replace("/(<form.*<\/form>)/Uis", '', $main);
$nTop=0;
$newTopicLink='';
}

if($user_id==0) $l_sub_post_tpc=$l_sub_post_tpc;

//added by MG:
$msg_logged_in = $user_id==0 ? ' You have to <a href="'.ABSURL.REQUEST_PATH.'/Login">sign in</a> to post topics' : '';
//==

echo load_header(); echo $warn; echo ParseTpl($main);
?>