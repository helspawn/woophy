<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004-2006 Paul Puzyrev, Sergei Larionov. www.minibb.net
Latest File Update: 2006-Dec-06
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

$canDelete=TRUE;
if(isset($topicData[2])) {
$poster_id=$topicData[2];
$time_diff=strtotime('now')-strtotime($topicData[8]);
if($useredit!=0 and $time_diff>$useredit) $canDelete=FALSE;
elseif($topicData[1]==1 and $user_id!=1 and $isMod==0) $canDelete=FALSE;
}
else {
$poster_id=-1;
$canDelete=FALSE;
}

if ($logged_admin==1 or $isMod==1 or (isset($userDeleteMsgs) and $userDeleteMsgs==2 and $user_id!=0 and $user_id==$poster_id and $canDelete)) {

if($user_sort==0) $return=0; else{

if($res=db_simpleSelect(0,$Tt,'topic_id','topic_id','>',$topic,'topic_id asc','','forum_id','=',$forum)) $h=$res[0]; else $h=0;

$numRows=$countRes;

/* define sticky topics */
if($stRow=db_simpleSelect(0,$Tt,'count(*)','sticky','=','1')) $stRow=$stRow[0]; else $stRow=0;
$numRows+=$stRow;

$rP=$numRows/$viewmaxtopic;
$rPInt=floor($numRows/$viewmaxtopic);
$return=$rPInt;
if($rP==$rPInt) $return-=1;
}

$pUsers=array();
if($row=db_simpleSelect(0,$Tp,'poster_id','topic_id','=',$topic,'post_id ASC')){
do if(!in_array($row[0], $pUsers) and $row[0]!=0) $pUsers[]=$row[0];
while($row=db_simpleSelect(1));
}

if(file_exists($pathToFiles.'bb_plugins2.php')) require_once($pathToFiles.'bb_plugins2.php');

db_delete($Ts,'topic_id','=',$topic);
$topicsDel=db_delete($Tt,'topic_id','=',$topic,'forum_id','=',$forum);
$postsDel=db_delete($Tp,'topic_id','=',$topic,'forum_id','=',$forum);
$postsDel--;
db_calcAmount($Tp,'forum_id',$forum,$Tf,'posts_count');
db_calcAmount($Tt,'forum_id',$forum,$Tf,'topics_count');

$i=0;
foreach($pUsers as $val){
if($i==0) db_calcAmount($Tt,'topic_poster',$val,$Tu,$dbUserSheme['num_topics'][1],$dbUserId);
db_calcAmount($Tp,'poster_id',$val,$Tu,$dbUserSheme['num_posts'][1],$dbUserId);
$i++;
}

if (defined('DELETE_PREMOD')) return;

if($user_sort==1){
if(isset($metaLocation)) { $meta_relocate="{$main_url}/{$indexphp}action=vtopic&forum={$forum}&page={$return}&h={$h}"; echo ParseTpl(makeUp($metaLocation)); exit; } else { header("Location: {$main_url}/{$indexphp}action=vtopic&forum={$forum}&page={$return}&h={$h}"); exit; }
}
else{
if(isset($mod_rewrite) and $mod_rewrite) $urlp="{$forum}_0.html"; else $urlp="{$indexphp}action=vtopic&forum={$forum}&page=0";
if(isset($metaLocation)) { $meta_relocate="{$main_url}/{$urlp}"; echo ParseTpl(makeUp($metaLocation)); exit; } else { header("Location: {$main_url}/{$urlp}"); exit; }
}

}
else {
$errorMSG=$l_forbidden; $correctErr='';
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}

?>