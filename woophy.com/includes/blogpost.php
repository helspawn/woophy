<?php
	//include_once CLASS_PATH.'Template.class.php';
	
	//handling form posts:
	$__pid__ = isset($_SESSION['pid']) ? (int)$_SESSION['pid'] : 0;
	if(isset($_POST['submit_comment'],$_POST['pid']) && $_POST['pid']==$__pid__){//prevent submitting data twice through redirect
		if(isset($_POST['post_id'], $_POST['comment_text'])){
			$__blog__ = ClassFactory::create('Blog');
			$xml_addcomment = $__blog__->addCommentByPostId($_POST['post_id'], $_POST['comment_text']);
			$__pid__ = $__pid__ + 1;
			$_SESSION['pid'] = $__pid__;
			session_write_close();
		}
	}

	
	function outputBlogPost($post, $js_name){
		global $__pid__;
		$outputstr = '';
		$js = '';
		$access = ClassFactory::create('Access');
		if($video = $post->video){
			$videos = explode(',',$video);//multiple videos are separated by ","
			$js .= 'jQuery(document).ready(function(){';
			$str = '';
			foreach($videos as $k=>$v){
				//look for video image, if no image is found we have an audio only video
				$v = trim($v);
				$h = 20;$img='';
				if(file_exists(VIDEO_PATH.$v.'.jpg')){
					$h = 260;
					$img = 'so.addVariable(\'image\',\''.$v.'.jpg\');';
				}
				$js .= 'var so = new SWFObject(\''.ROOT_PATH.'swf/video.swf\',\'video\',\'320\',\''.$h.'\',\'8\',\'#FFFFFF\',\'high\');'.$img.'so.addVariable(\'base_url\',\''.VIDEO_URL.'\');so.addVariable(\'file\',\''.$v.'.flv\');so.addParam(\'wmode\',\'opaque\');so.write(\'post_video_'.$k.'\');';
				$str .= '<div class="post_video" id="post_video_'.$k.'"></div>';
			}
			$js .= '});';
			$post->text = $str.$post->text;
		}
		$edit = '';
		if($uid = $access->getUserId()){
			if($uid==$post->user_id){
				$edit = ' | <a href="'.ROOT_PATH.'account/blog/edit?&action=edit&post_id='.$post->id.'">Edit</a>';
			}
		}
		$tpl = ClassFactory::create('Template','blogpost.tpl');
		$outputstr .= $tpl->parse(array(
		'post_title'=>$post->title,
		'post_publication_date'=>Utils::formatDate($post->publication_date),
		'user_url'=>ABSURL.'member/'.urlencode($post->user_name),
		'user_name'=>$post->user_name,
		'post_text'=>$post->text,
		'post_views'=>$post->views,
		'comment_count'=>$post->comment_count,
		'comment_str'=>(int)$post->comment_count==1?'comment':'comments',
		'edit'=>$edit
		));
		
		//print comments:
		$blog = ClassFactory::create('Blog');
		$comments = $blog->getCommentsByPostId($post->id);
		
		if(count($comments->comment)){
			$outputstr .= '<div id="Comments" class="Section">';
			$outputstr .= '<div class="MainHeader"><h2>Comments</h2></div><ul>';
			$tpl1 = new Template('comment.tpl');
			$tpl2 = new Template('comment_poster.tpl');
			$uri = Utils::stripSpecialAction($_SERVER['REQUEST_URI']);
			foreach ($comments->comment as $comment){
				if($un = $comment->user_name){
					$outputstr .= $tpl1->parse(array(
						'comment_id'=>$comment->id,
						'comment_text'=>$comment->text,
						'comment_url'=>$uri,
						'comment_id'=>$comment->id,
						'user_url'=>ABSURL.'member/'.urlencode($un),
						'user_name'=>$un,
						'comment_date'=>Utils::formatDateTime($comment->date)
					));
				}else if($pn = $comment->poster_name){
					$outputstr .= $tpl2->parse(array(
						'comment_id'=>$comment->id,
						'comment_text'=>$comment->text,
						'comment_url'=>$uri,
						'comment_id'=>$comment->id,
						'poster_name'=>$pn,
						'comment_date'=>Utils::formatDateTime($comment->date)
					));
				}
			}
			$outputstr .= '</ul>';
			$outputstr .= '</div>';//Comments
		}
		//print form:
		$url = Utils::stripSpecialAction($_SERVER['REQUEST_URI']);
		if($access->isLoggedIn()){
			$tpl = new Template('commentform.tpl');
			$error = '';
			$text = '';
			if(isset($xml_addcomment)){
				if($err = $xml_addcomment->err){
					$error = $err['msg'];
					$text = $_POST['comment_text'];
				}
			}
			$outputstr .= $tpl->parse(array('post_id'=>$post->id,'form_action'=>$url,'pid'=>$__pid__,'text'=>$text,'error'=>$error));
		}else{
			$tpl = new Template('commentform_noaccess.tpl');
			$outputstr .= $tpl->parse(array('form_action'=>$url));
		}
		
		$GLOBALS[$js_name].= $js;
		return $outputstr;
	}
?>