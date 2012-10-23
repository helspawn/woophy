<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}	

	$photo = new Photo();
	
	include_once INCLUDE_PATH.'gallery.php';
	include_once INCLUDE_PATH.'userlist.php';

	$user = ClassFactory::create('User');
	$user->buffer = false;

	$page->addScript('gallery.js');
	$page->addScript('photopage.js');

	$page->setSection($page->getSection().'Detail');
	$name_from = htmlspecialchars($user_name).'&#39;';
	if(mb_strtolower(mb_substr($xml_user->name, -1))!='s')$name_from .= 's';
	$user_id = (int)$xml_user->id;
	$blog_url = ROOT_PATH.'member/'.urlencode($user_name).'/blog/';

	$html = '<div class="OuterContentContainer clearfix">';
	$html .= outputGalleryHTML(array('user_xml'=>$xml_user, 'link_to_more'=>TRUE, 'dashboard'=>TRUE));
	$html .= '</div> <!-- end OuterContentContainer -->';

	$blog_post_count = (int)$xml_user->blog_post_count;
	$html .= '<div class="Section">';
	$html .= '<div class="MainHeader DottedBottom clearfix">';
	$html .= '<div class="NewBlogButton OrangeButton"><a href="'. ROOT_PATH .'account/blog" class="sprite"><span>Write new blog</span></a></div>';
	$html .= '<h2>My latest blogpost</h2>';
	if($blog_post_count>1) $html .= '<a href="'.$account_url.'blog/edit?&action=edit">View all ('.$blog_post_count.')</a>';
	$html .= '</div>';
	if($blog_post_count>0){
		$blog = ClassFactory::create('Blog');
		$blog->buffer = false;
		$posts_xml = $blog->getRecentPostsByUserId($user_id, 1);
		if($error = $posts_xml->err) $html .= '<div class="Error">'.$error['msg'].'</div>';
		else{
			$posts = $posts_xml->post;
			if(count($posts)>0){
				include INCLUDE_PATH.'blogpostlist.php';
				$html .= outputBlogPostList($posts);
			}
		}
	}else $html .= '<div class="Notice">You are not blogging.</div>';
	$html .= '</div> <!-- end Section -->';
	$limit = 6;

	$max_limit = 1000;
	$html .= '<div class="OuterContentContainer clearfix">';
	$html .= outputGalleryHTML(array('user_xml'=>$xml_user, 'gallery_type'=>'favorites', 'link_to_more'=>TRUE, 'dashboard'=>TRUE));
	$html .= '</div> <!-- end OuterContentContainer -->';
	
	$limit = 3;
	$html .= '<div class="OuterContentContainer Skinny floatleft clearfix">';
	$html .= '<div class="MainHeader DottedBottom"><h2>My favorite photographers</h2>';
	
	$xml_favs = $user->getFavoritesByUserId($user_id, 0, $limit);
	$total = (int)$xml_favs['total_users'];
	$html .= ' <a href="'.$account_url.'favorites/favmembers">View&nbsp;all&nbsp;('.($total>$max_limit?$max_limit.'+':$total).')</a>';
	$html .= '</div>';
	if($total>0)$html .= getListFavUsers($xml_favs->user);
	else $html .= '<div class="Notice">You have no favorite photographers yet!</div>';

	$html .= '</div> <!-- end OuterContentContainer -->';
		
	$html .= '<div class="OuterContentContainer Skinny floatright clearfix">';
	$html .= '<div class="MainHeader DottedBottom"><h2>My fans</h2>';
	$xml_fans = $user->getLatestFans($user_id, 0, $limit);
	$total = (int)$xml_fans['total_users'];
	$html .= ' <a href="'.$account_url.'fans"><br/>View&nbsp;all&nbsp;('.($total>$max_limit?$max_limit.'+':$total).')</a>';
	$html .= '</div>';
	if($total>0)$html .= getListFavUsers($xml_fans->user);
	else $html .= '<div class="Notice">You have no fans yet!</div>';

	$html .= '</div> <!-- end OuterContentContainer -->';

	echo $html;			