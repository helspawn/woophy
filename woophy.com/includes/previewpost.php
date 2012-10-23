<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}
		
	include CLASS_PATH.'Page.class.php';
	include_once CLASS_PATH.'Template.class.php';
	
	$page = new Page();
	$page->setTitle('Preview post');
	echo $page->outputHeaderSimple();
	
	echo '<div id="MainColumn" class="clearfix">';

	$access = ClassFactory::create('Access');
	if(!($username = $access->getUserName())) $username = '';
	$tpl = new Template('blogpost.tpl');
	$cc = isset($_POST['comment_count']) ? (int)$_POST['comment_count'] : 0;
	echo $tpl->parse(array(
	'post_title'=>isset($_POST['post_title']) ? Utils::filterText($_POST['post_title'],false,true) : '',
	'post_publication_date'=>isset($_POST['publication_date']) ? Utils::formatDate($_POST['publication_date']) : '',
	'user_url'=>ABSURL.'member/'.urlencode($username),
	'user_name'=>$username,
	'post_text'=>isset($_POST['post_text']) ? (Utils::filterText($_POST['post_text'],false,true,true)) : '',
	'post_views'=>isset($_POST['post_views']) ? $_POST['post_views'] : 0,
	'comment_count'=>$cc,
	'comment_str'=>$cc==1?'comment':'comments',
	'edit'=>''
	));
	
	echo '</div>';

	echo $page->outputFooterSimple();
?>