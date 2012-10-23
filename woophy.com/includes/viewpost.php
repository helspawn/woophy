<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}
		
	include CLASS_PATH.'Page.class.php';
	include_once CLASS_PATH.'Blog.class.php';
	include_once CLASS_PATH.'Template.class.php';
	
	$page = new Page();
	$page->setTitle('Post');
	echo $page->outputHeaderSimple();

	echo '<div id="PageContent" class="clearfix">';
	if(isset($_GET['post_id'])){
		$access = ClassFactory::create('Access');
		if($uid = $access->getUserId()){
			$blog = new Blog();
			$post_xml = $blog->getPostById($_GET['post_id'], $uid, false);
			if($err = $post_xml->err) echo $err['msg'];
			else{
				$tpl = new Template('blogpost.tpl');
				echo $tpl->parse(array(
				'post_title'=>$post_xml->title,
				'post_publication_date'=>Utils::formatDate($post_xml->publication_date),
				'user_url'=>ABSURL.'member/'.urlencode($post_xml->user_name),
				'user_name'=>$post_xml->user_name,
				'post_text'=>$post_xml->text,
				'post_views'=>$post_xml->views,
				'comment_count'=>$post_xml->comment_count,
				'comment_str'=>(int)$post_xml->comment_count==1?'comment':'comments',
				'edit'=>''
				));
			}
		}else echo 'You have to be logged in to view posts!';
	}else echo 'Missing post id';

	echo '</div>';

	echo $page->outputFooterSimple();
?>