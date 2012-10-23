<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}

	$feeds = array(
		'newsletter'=>array(	'link'=>ABSURL.'blogs/newsletter/',
								'title'=>'Woophy News',
								'description'=>'Woophy latest news, updates and announcements',
								'querystring'=>'publication_date'),
		'blog'=>array(			'link'=>ABSURL.'blogs/blog/',
								'title'=>'Woophy Member Blog',
								'description'=>'Woophy Recently added Member Blogposts',
								'querystring'=>'user_id'),
		'photos'=>array(		'link'=>'',
								'title'=>'Woophy Photos',
								'description'=>'Woophy Recently added Photos')
	);
	$feed_path = 'feeds';	
	$param = explode('/', Utils::stripQueryString(REQUEST_PATH));
	$k = array_search($feed_path, $param);
	if($k!==false){
		if(array_key_exists($k+1, $param))$req_feed = $param[$k+1];
	}
	if(isset($req_feed)){
		if(array_key_exists($req_feed, $feeds)){
			//rss:
			header('Content-type:text/xml; charset=UTF-8');
			
			include CLASS_PATH.'Feed.class.php';
			$feed = new Feed();
			$feed->link = $feeds[$req_feed]['link'];
			$feed->title = $feeds[$req_feed]['title'];
			$feed->description = $feeds[$req_feed]['description'];

			switch($req_feed){
				case 'newsletter':
					include CLASS_PATH.'Newsletter.class.php';
					$newsletter = new Newsletter();
					if(isset($_GET['publication_date'])) $xml_newsletter = $newsletter->getNewsletterByDate($_GET['publication_date']);
					else $xml_newsletter = $newsletter->getLastNewsletter();
					
					if($err = $xml_newsletter->err) {
						$feed->addItem(array('title'=>'error '.$err['code'],'description'=>$err['msg']));
					}else{
						$posts = $xml_newsletter->post;
						foreach ($posts as $post) {
							$param = array();
							$param['title'] = $post->title;
							$param['link'] = $feed->link;//TODO: insert link to post;
							$param['description'] = '';//TODO: excerpt here?
							$param['pubDate'] = $post->publication_date;
							$param['author'] = $post->user_name;
							$param['comments'] = $feed->link;//TODO: insert link to comments
							$param['content'] = $post->text;
							$feed->addItem($param);
						}
					}
					break;
				case 'blog':
					include CLASS_PATH.'Blog.class.php';
					$blog = new Blog();
					
					if(isset($_GET['user_id'])) $xml_posts = $blog->getRecentPostsByUserId($_GET['user_id'], 10);
					else $xml_posts = $blog->getRecentPostsByCategoryId(Blog::CATEGORY_ID_USER, 10);
					
					if($err = $xml_posts->err) {
						$feed->addItem(array('title'=>'error '.$err['code'],'description'=>$err['msg']));
					}else{
						$posts = $xml_posts->post;
						foreach ($posts as $post){
							$param = array();
							$param['title'] = $post->title;
							$param['link'] = $feed->link;//TODO: insert link to post;
							$param['description'] = '';//TODO: excerpt here?
							$param['pubDate'] = $post->publication_date;
							$param['author'] = $post->user_name;
							$param['comments'] = $feed->link;//TODO: insert link to comments
							$param['content'] = $post->text;
							$feed->addItem($param);
						}
					}
					break;
				case 'photos':
					include CLASS_PATH.'Photo.class.php';
					$photo = new Photo();
					$xml_photos = $photo->getRecent();
					if($err = $xml_photos->err) {
						$feed->addItem(array('title'=>'error '.$err['code'],'description'=>$err['msg']));
					}else{
						$photos = $xml_photos->photo;
						foreach ($photos as $p) {
							$url = ABSURL.'photo/'.$p->id;
							$param = array();
							$param['title'] = 'Woophy Photo #'.$p->id;
							$param['link'] = $url;
							$param['description'] = $p->city_name.', '.$p->country_name;
							$param['pubDate'] = $p->date;
							$param['author'] = $p->user_name;
							$param['comments'] = $url.'#comments';
							$param['content'] = '<a href="'.$url.'"><img border="0" src="http://'.$_SERVER['HTTP_HOST'].Utils::getPhotoUrl($p->user_id,$p->id,'medium',$p->seo_suffix).'" /></a>';
							$feed->addItem($param);
						}
					}
					break;
			}
			echo $feed->output();
		}else include INCLUDE_PATH.'404.php';
	}else{
		include CLASS_PATH.'Page.class.php';
		$page = new Page();
		$page->setTitle('Feeds');
		$page->addInlineStyle('td.first{width:100px}table{margin-bottom:20px}');
		echo $page->outputHeader();
		echo '<div class="MenuBar"><div id="SubNav"></div></div>';
		echo '<div id="MainColumn"><div class="Section">';
		echo '<div class="MainHeader DottedBottom"><h1>Available Feeds</h1></div>';
		echo '<p>Add the links below to your favorite RSS feed reader.</p>';
		foreach($feeds as $k=>$v){
			$url = ABSURL.$feed_path.'/'.$k;
			echo '<table><tr><td colspan="2"><a href="'.$url.'">'.$v['title'].'</a></td></tr>';
			echo '<tr><td class="strong first">description</td><td>'.$v['description'].'</td></tr>';
			echo '<tr><td class="strong">url</td><td>'.$url.'</td></tr>';
			if(isset($v['querystring']))echo '<tr><td class="strong">querystring</td><td>'.$v['querystring'].'</td></tr>';
			echo '</table>';
		}
		echo '<table><tr><td colspan="2"><a href="'.ROOT_PATH.'forum/rss">Woophy Forum</a></td></tr>';//TRICKY: link to forum rss needs modrewrite htaccess
		echo '<tr><td class="strong first">description</td><td>Woophy Forum Latest Additions</td></tr>';
		echo '<tr><td class="strong">url</td><td>'.ABSURL.'forum/rss</td></tr></table>';
		echo '</div></div>';
		echo '<div id="RightColumn"></div>';
		echo $page->outputFooter();
	}
	
?>