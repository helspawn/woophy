<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}

	$blog = ClassFactory::create('Blog');
	$blog->buffer = false;

	$param = explode('/', trim(REQUEST_PATH, '/'));
	
	$baseurl = ROOT_PATH.Utils::stripQueryString($param[0]).'/newsletter/';//use "/newsletter/" for backward compatibility
	$cat_id = Blog::CATEGORY_ID_NEWSLETTER;

	$html = '';
	$js = '';
	$search_str = '';
	$search_offset = 0;
	$search_limit = 10;
	
	//check for post id and publication date:
	if(count($param) >= 2){
		$val = Utils::stripQueryString(end($param));
		$list = explode('-', $val);
		if(count($list)==3 && checkdate($list[1],$list[2],$list[0]))$publication_date = $val;
		else{
			$val = (int)$val;
			if($val>0){
				$post_id = $val;
				include INCLUDE_PATH.'blogpost.php';
			}
		}
	}
	if(isset($_GET['search'])){
		$val = trim($_GET['search']);
		if(strlen($val)>0 && $val != $search_str) {
			$search_str = $val;
			$search_offset = isset($_GET['offset'])?(int)$_GET['offset']:$search_offset;
			include_once INCLUDE_PATH.'blogsearchresults.php';
			$searchresults = outputBlogSearchResults($search_str, $cat_id, $search_offset, $search_limit, '', true);
		}
	}


	$page = ClassFactory::create('Page');
	$page->setTitle('News');
	$page->addScript('simpleblogarchive.js');
	$page->addRSS(ROOT_PATH.'feeds/newsletter', 'Woophy News');

	include CLASS_PATH.'Newsletter.class.php';
	$newsletter = new Newsletter();
	$newsletter->buffer = false;

	$html .= '<div id="MainContent" class="clearfix"><div id="MainColumn">'.PHP_EOL;

	$outputstr = '';

	if(isset($searchresults))$outputstr .= $searchresults;
	else if(isset($post_id)){
		//print post:
		$xml_post = $blog->getPostById($post_id, null, true, true);
		if($err=$xml_post->err)$outputstr .= '<div class="Error">'.$err['msg'].'</div>';
		else{
			if($xml_post->category_id==$cat_id){
				$page->setTitle($xml_post->title);

				$outputstr .= '<div class="MenuBar clearfix">';
				$outputstr .= '<div class="ActionButtons clearfix"><div class="SocialButtons">';
				$outputstr .= '<div class="ActionButton" id="FacebookButton"><a class="sprite replace" href="http://www.facebook.com/sharer.php?u='.urlencode(ABSURL.REQUEST_PATH).'&t='.urlencode($xml_post->title) .'" target="_blank">Facebook</a></div>';
				$outputstr .= '<div class="ActionButton" id="TwitterButton"><a class="sprite replace" href="http://twitter.com/home?status=' .urlencode($xml_post->title).'%20-%20'.urlencode(ABSURL.REQUEST_PATH).'" target="_blank">Twitter</a></div>';
				$outputstr .= '</div></div>';
				
				//diplay next previous link:
				$xml_prevnext = $blog->getPrevNextByCategoryId($cat_id, $post_id, $xml_post->publication_date);
				$prevnext = '';
				if($prev_id = $xml_prevnext->prev_id)if(mb_strlen($prev_id)>0)$prevnext .= '<li><a href="'.$baseurl.$prev_id.'">&laquo; Previous Post</a></li>';
				if($next_id = $xml_prevnext->next_id)if(mb_strlen($next_id)>0)$prevnext .= '<li><a href="'.$baseurl.$next_id.'">Next Post &raquo;</a></li>';
				if(mb_strlen($prevnext)>0)$outputstr .= '<div class="PrevNext" id="SubNav"><ul class="clearfix">'.$prevnext.'</ul></div>';

				$outputstr .= '</div>';//end MenuBar
				
				$page->addScript('swfobject.js');//blog videos
				$outputstr .= outputBlogPost($xml_post, 'js');
			}else $outputstr .= '<div class="Error">No post found</div>';
		}
	}else{
		if(isset($publication_date)){
			$posts_xml = $newsletter->getNewsletterByDate($publication_date);
		}else $posts_xml = $blog->getRecentPostsByCategoryId($cat_id);
		if($error = $posts_xml->err) $outputstr .= $error['msg'];
		else{
			$posts = $posts_xml->post;
			include INCLUDE_PATH.'blogpostlist.php';
			$outputstr .= '<div id="TitleBar"><h1>'.(isset($publication_date)?'Newsletter '.Utils::formatDate($publication_date):'Latest News').'</h1></div><div class="Section">';
			$outputstr .= outputBlogPostList($posts);
			$outputstr .= '</div>';
		}
	}
	
	$html .= $outputstr . '&nbsp;';
	$html .= '</div> <!-- end Section, MainColumn -->'.PHP_EOL;//</div>

	$html .= '<div id="RightColumn">'.PHP_EOL;
	//search
	$html .= '<form action="'.Utils::stripQueryString($_SERVER['REQUEST_URI']).'" id="Search" class="Section SearchBar NewsSearch clearfix" method="get"><div><input type="hidden" id="offset" name="offset" value="0" /><input type="hidden" id="limit" name="limit" value="'.$search_limit.'" /><input type="text" class="text" id="input" name="search" alt="Search" value="'.$search_str.'" /><input class="submit GreenButton" type="submit" id="submit_search" name="submit_search" value="go!"/></div></form>';
	
	//city of the day:

	if($xmlstr = $blog->getFromCache('cityoftheday')){//TODO make constant of key (also used in Status.class)
		$xml_city = new SimpleXMLElement($xmlstr);
		$img_w = 348;//2px border
		$img_h = (int)$xml_city->photo_height;
		if($img_h>0){
			$html .= '<div class="Section"><div class="Header clearfix"><h2>City of the Day</h2></div><a class="CityOfTheDay" href="'.ROOT_PATH.'photo/'.$xml_city->photo_id.'"><img alt="'.$xml_city->name.'" src="'.Utils::getPhotoUrl($xml_city->user_id,$xml_city->photo_id,'medium').'" /></a>';
			$html .= '<a href="'.ROOT_PATH.'city/'.urlencode($xml_city->name).'/'.urlencode($xml_city->country_code).'">'.$xml_city->name.'</a>, <a href="'.ROOT_PATH.'country/'.urlencode($xml_city->country_code).'">'.$xml_city->country_name.'</a></div>';
		}
	}

	$html .= '<div class="AdContainer" id="azk76744"></div>';
	//blogpostarchive	
	$service_url = ABSURL.'services?&method=woophy.blog.getArchiveByCategoryId&category_id='.$cat_id;
	$limit = 10;
	if(isset($_GET['total'], $_GET['offset']) && !isset($_GET['search'])){
		$total = (int)$_GET['total'];
		$offset = min($total, (int)$_GET['offset']);
		$offset -= fmod($offset, $limit);
	}else{
		$xml_offset = $blog->getPostOffsetByCategoryId(isset($post_id)?$post_id:NULL,$cat_id);
		$total = (int)$xml_offset->total;
		$offset = $xml_offset->offset;
	}
	$simplearchive = $cat_id == Blog::CATEGORY_ID_NEWSLETTER;
	$html .= '<div class="Section">';
	$html .= '<div class="Header clearfix"><h2>Newsletter Blogposts</h2>';
	$html .= '<div class="Nav"><a class="PagingLeft sprite replace" id="page_forward">&laquo;&nbsp;next</a><a class="PagingRight sprite replace" id="page_backward">back&nbsp;&raquo;</a></div></div>';
	$html .= '<div id="BlogPosts" class="archive"></div></div>';
	$html .= '<script type="text/javascript">//<![CDATA['.PHP_EOL;
	$html .= 'jQuery(document).ready(function(){var blogarchive = new SimpleBlogArchive({divObj:document.getElementById(\'BlogPosts\'),page_forward:document.getElementById(\'page_backward\'),page_backward:document.getElementById(\'page_forward\'),count_items:'.$total.',current_item_id:'.(isset($post_id)?$post_id:'undefined').',offset:'.$offset.',limit:'.$limit.',service_url:\''.$service_url.'\',blog_url:\''.$baseurl.'\'});});'.PHP_EOL;
	$html .= '//]]></script>';

	//newsletter archive
	$xml_dates = $newsletter->getNewsletterDates();
	$html .= '<div class="Section">';
	$html .= '<div class="Header clearfix"><h2>Newsletter Archive</h2></div>';
	if($err = $xml_dates->err) $html .= $err['msg'];
	else{
		$html .= '<div class="DropdownContainer"><select class="sprite" onchange="if(this.value.length)document.location.href=\''.$baseurl.'\'+this.value" name="select_newsletter">'.PHP_EOL;
		$nr = count($xml_dates);
		$l = strlen($nr);
		$html .= '<option value="">select</option>';
		foreach($xml_dates as $date){
			$d = $date['publication_date'];
			$html .= '<option';
			if(isset($publication_date) && $d == $publication_date) $html .= ' selected="true"';
			$html .= ' value="'.$d.'">#'.str_pad($nr--, $l, '0', STR_PAD_LEFT).' | '.date('F Y',strtotime($d)).'</option>';
		}
		$html .= '</select></div>'.PHP_EOL;
	}

	$html .= '<a class="sprite RSS" href="'.ABSURL.'feeds/newsletter';
		if(isset($publication_date)) $html .= '?&publication_date='.$publication_date;
	$html .= '">RSS 2.0</a></div>';
	$html .= '</div></div> <!-- end RightColumn, MainContent -->'.PHP_EOL;
	
	$page->addInlineScript($js);
	echo $page->outputHeader(2);
	echo $html;
	echo $page->outputFooter();
?>
