<?php
/*
addon_preview2.php: preview addon script for miniBB.
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004-2007 Paul Puzyrev. www.minibb.net
Latest File Update: 2007-Feb-02

20070806 : rewritten by MG, always show template instead of exit
*/
if(isset($_POST['prevForm']) and $_POST['prevForm']+0==1){

	if(!isset($_POST['topicTitle'])) $_POST['topicTitle']='';
	if(!isset($_POST['postText'])) $_POST['postText']='';
	if(!function_exists('textFilter')) require($pathToFiles.'bb_func_txt.php');
	$logged_admin=($user_id==1?1:0);
	$disbbcode=(isset($_POST['disbbcode']) and $_POST['disbbcode']=='on'?1:0);
	$topicTitle2=stripslashes(textFilter($_POST['topicTitle'],$topic_max_length,$post_word_maxlength,0,1,0,0));
	$postText2=stripslashes(textFilter($_POST['postText'],$post_text_maxlength,$post_word_maxlength,1,$disbbcode,1,$logged_admin));

	if(strlen(trim($topicTitle2))<$post_text_minlength)$topicTitle2 = '';
	if(strlen(trim($postText2))<$post_text_minlength)$postText2 = '';

	$tpl=makeUp('addon_preview2');
	if($topicTitle2=='') $tpl=preg_replace("#<!--topic_title-->(.+?)<!--/topic_title-->#s",'',$tpl);
	
	echo ParseTpl($tpl);
	exit;
}


//old code:
/*
if(isset($_POST['prevForm']) and $_POST['prevForm']+0==1 and strlen(trim($_POST['postText']))>=$post_text_minlength){
if(!function_exists('textFilter')) require($pathToFiles.'bb_func_txt.php');

if(isset($_POST['topicTitle']) and strlen(trim($_POST['topicTitle']))<$post_text_minlength and strlen($_POST['postText'])>=$post_text_minlength) {
exit;
}

if(!isset($_POST['topicTitle'])) $_POST['topicTitle']='';

$logged_admin=($user_id==1?1:0);
$disbbcode=(isset($_POST['disbbcode']) and $_POST['disbbcode']=='on'?1:0);
$topicTitle2=stripslashes(textFilter($_POST['topicTitle'],$topic_max_length,$post_word_maxlength,0,1,0,0));
$postText2=stripslashes(textFilter($_POST['postText'],$post_text_maxlength,$post_word_maxlength,1,$disbbcode,1,$logged_admin));

$tpl=makeUp('addon_preview2');
if($topicTitle2=='') $tpl=preg_replace("#<!--topic_title-->(.+?)<!--/topic_title-->#s",'',$tpl);
echo ParseTpl($tpl);
exit;
}
elseif(isset($_POST['prevForm']) and $_POST['prevForm']+0==1 and strlen(trim($_POST['postText']))<$post_text_minlength){
echo '';
exit;
}
*/
?>