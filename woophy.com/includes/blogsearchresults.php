<?php
	/*
	this file requires the following classes:
	include_once CLASS_PATH.'Blog.class.php';
	include_once CLASS_PATH.'Utils.class.php';
	include_once CLASS_PATH.'ClassFactory.class.php';
	*/
	function outputBlogSearchResults($search_str, $category_id, $offset, $limit, $querystring='', $useTitleBar=false){
		
		$param = explode('/', trim(REQUEST_PATH, '/'));
		$param[0] = strtolower(Utils::stripQueryString($param[0]));
		$baseurl = ROOT_PATH.$param[0].'/';
		$fmod = fmod($offset, $limit);
		if($fmod!=0)$offset -= $fmod;
		$blog = ClassFactory::create('Blog');
		$search_xml = $blog->search($search_str, $offset, $limit, $category_id);
		$outputstr = '';
		$posts = $search_xml->post;
		if($useTitleBar)$outputstr.='<div id="TitleBar">';
		else $outputstr.='<div class="MainHeader DottedBottom">';
		$outputstr .='<h1>Search Results</h1>';
		if(count($posts)>0){
			$total = $search_xml['total_posts'];
			$outputstr .='<div id="Results">'. ($offset+1).' - '.(min($offset+$limit,$total)).' of '.$total.' total for <span class="strong">'.$search_str.'</span></div></div>';		
			
			if($useTitleBar)$outputstr .= '<div class="Section">';

			$pagingnav = Utils::getPagingNav($offset, $total, $limit, '&search='.urlencode($search_str).$querystring);
			$outputstr .= $pagingnav;

			$outputstr .= '<ol class="search_results" start="'.($offset+1).'">';
			foreach ($posts as $post){
				if($param[0]=='member')$href = $baseurl.urlencode($post->user_name).'/blog/';
				else $href = $baseurl;
				//if($post->category_id == Blog::CATEGORY_ID_NEWSLETTER)$href = $baseurl;
				//else $href = $baseurl.urlencode($post->user_name).'/blog/';
				$outputstr .= '<li><h2><a class="result" href="'.$href.$post->id.'">'.$post->title.'</a></h2>Posted on '.Utils::formatDate($post->publication_date).' by <a href="'.ROOT_PATH.'member/'.urlencode($post->user_name).'">'.$post->user_name.'</a></li>';

			}
			$outputstr .= '</ol>';

			if($useTitleBar)$outputstr .= '</div>';
		
		}else{
			$outputstr .= '</div>';
			if($useTitleBar)$outputstr .= '<div class="Section"><br/>';
			$outputstr .= 'Your search - <span class="strong">'.$search_str.'</span> - did not match any documents.';
			if($useTitleBar)$outputstr .= '</div>';
		} 
		
		return $outputstr;
		
	}

?>