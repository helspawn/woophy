<?php
	/*
	this file requires the following classes:
	include_once CLASS_PATH.'Blog.class.php';
	*/
	function outputBlogPostList($posts){
		$outputstr = '';
		$num = count($posts);
		if($num>0){
			$access = ClassFactory::create('Access');
			$suffix = '[...]';
			$outputstr = '<div id="BlogList">';
			$i=0;
			foreach ($posts as $post){
				$thumb = '';
				$post_url = $post->category_id == Blog::CATEGORY_ID_NEWSLETTER ? ROOT_PATH.'news/newsletter/'.$post->id : ROOT_PATH.'member/'.urlencode($post->user_name).'/blog/'.$post->id;
				
				//insert thumb
				//KLUDGE: messy code
				if($src = Utils::getImageSource($post->text)){
					if($url = Utils::getWoophyPhotoUrl($src)){
						$src = str_replace('/large/', '/medium/', $src);
					}
					$thumb = '<a href="'.$post_url.'" class="Thumb sprite"><img src="'.$src.'" /></a>';
				}
				
				$outputstr .= '<div class="BlogPost ';
				if($i < $num-1) $outputstr .= 'DottedBottom';
				$outputstr .= '"><div class="Header"><h2><a href="'. $post_url .'">'.$post->title.'</a></h2></div>';
				$outputstr .= '<div class="Content clearfix">'. $thumb . Utils::getExcerpt($post->text, $suffix). '<br/><a href="'.$post_url.'">Read more...</a></div>';
				$outputstr .= '<div class="PostText">';
				$outputstr .= '<div class="Category cat' . $post->category_id . ' sprite"></div>';
				$outputstr .= 'Posted on ' . Utils::formatDate($post->publication_date).' by <a href="'.ROOT_PATH.'member/'.urlencode($post->user_name).'">'.$post->user_name.'</a>';
				if($uid = $access->getUserId())if($uid==$post->user_id)$outputstr .=' | <a href="'.ROOT_PATH.'account/blog/edit?&action=edit&post_id='.$post->id.'">Edit</a>';
				$outputstr .=' | '.$post->comment_count.' <a href="'.$post_url.'#bottom">'.($post->comment_count == 1 ? 'comment' : 'comments').'</a> | '.$post->views.' views</div>';
				$outputstr .= '</div>';
				$i++;
			}
			$outputstr .= '</div>';
		}
		return $outputstr;
	}
?>