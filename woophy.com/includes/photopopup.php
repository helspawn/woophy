<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}

	include CLASS_PATH.'Page.class.php';

	$page = new Page();
	$page->setSection('PhotoPopup');
	
	if(isset($_GET['photo_id'])){

		include CLASS_PATH.'Photo.class.php';
		$photo_id = (int)$_GET['photo_id'];
		if($page->getViewmode()==0) $page->addScript('photopage.js');
		
		//$page->addInlineScript('init_global_pre.add(function(){PhotoPage.init('.$photo_id.')});');//can this line be removed?
		$page->addInlineScript('PhotoPage.photo_id='.$photo_id.';');
		
		$photo = new Photo();
		$photo->buffer = false;
				
		$pid = isset($_SESSION['pid']) ? (int)$_SESSION['pid'] : 0;
		$increaseViews = true;
		$xhr = FALSE;
		if(isset($_POST['xhr']) && $_POST['xhr']=='true') $xhr = TRUE;
		if($xhr || (isset($_POST['pid']) && $_POST['pid']==$pid)){
			$increaseViews = false;
			if(isset($_POST['post_id'], $_POST['comment_text'])){
				$xml_addcomment = $photo->addComment($_POST['post_id'], $_POST['comment_text']);
				$pid = $pid + 1;
				$_SESSION['pid'] = $pid;
				session_write_close();
				if($xhr){
					header('Content-type: application/json');
					$output = '';
					if(isset($xml_addcomment)){
						if($error = $xml_addcomment->err){
							$code = $error['code'].'';
							$msg = $error['msg'].'';
							$output = json_encode(array('error_code'=>$code, 'error_message'=>$msg)); 
						}else{
							$date = date('j-n-\'y', strtotime($xml_addcomment->comment_date));
							$name = $xml_addcomment->poster_name.'';
							$comment = strip_tags($xml_addcomment->comment.'');
							$output = json_encode(array('comment_date'=>$date, 'datetime'=> $xml_addcomment->comment_date, 'poster_name'=>$name, 'comment'=>$comment));
						}
					}
					echo $output;
					die();
				}
			}
		}

		$xml_photo = $photo->getMoreInfo($photo_id, $increaseViews);
		$photo_url = Utils::getPhotoUrl($xml_photo->user_id,$photo_id,'large','',$xml_photo->seo_suffix);
		
		$title = 'Photo in '.$xml_photo->city_name.' '.$xml_photo->country_name.', by '.$xml_photo->user_name;
		$page->setTitle($title);
		
		$access = ClassFactory::create('Access');
		$isLoggedIn = $access->isLoggedIn();

		if($err = $xml_photo->err) {
			$err_message = '<div style="padding:20px" class="Error">The photo you are looking for cannot be found.</div>';
			$rating = '';
		}else{			
			$xml_rating = $photo->getRating($photo_id);
			$rating = (int)$xml_rating->rating;
			$err_message = '';
		}
			
		if($isLoggedIn){
			include_once CLASS_PATH.'User.class.php';
			$user = new User();
			$isEditor = $user->isEditor();
		}

		$ownsPhoto = $xml_photo->user_id == $access->getUserId();
		$country = $xml_photo->country_name;
		$country_code = $xml_photo->country_code;
		$city = $xml_photo->city_name;
		$user_name = $xml_photo->user_name;
		$awards = $xml_photo->user_award;

		if(count($awards)>0){
			$a = array();
			foreach($awards as $award){
				$a[] = '\''.$award['category_id'].';'.Utils::formatDate($award['date']).'\'';
			}
		}

		//if($page->getViewmode() < 2) echo $page->outputHeaderSimple();
		if($page->getViewmode() == 0) echo $page->outputHeaderSimple();//no header/footer for popup
		if($page->getViewmode()==1){
			echo '<div id="PhotoPopupContainer" class="clearfix">';
		}
		
		if($page->getViewmode()<2) echo '<div id="PhotoPopup" class="DropShadow clearfix">';

		if($err_message==''){
			$html = '<div id="HeaderBar" class="DottedBottom clearfix">';
			$html .= '<h2><a href="'.ROOT_PATH.'city/'.urlencode($city).'/'.$country_code.'">'.$city.'</a>, <a href="'.ROOT_PATH.'country/'.urlencode($country_code).'">'.$country. '</a>&nbsp;&nbsp;&nbsp;';
			$html .= 'Photo by <a href="'.ROOT_PATH.'member/'.htmlspecialchars($xml_photo->user_name).'" target="_top">'. $xml_photo->user_name. '</a></h2>';
	
			$awards = $xml_photo->user_award;
			$ambassador = isset($xml_photo->ambassador)?$xml_photo->ambassador:false;
			$user_camera = (int)$xml_photo->user_camera;
			
			$html .= '	<div class="Awards">';
			if($user_camera>0){
				$camera = array('Bronze camera, member has published more than 10 photos with above average rating','Silver camera, member has published more than 10 photos with high rating','Gold camera, member has published more than 10 photos with very high rating');/*KLUDGE: retreive this from dbase!!*/
				$html .= '<span class="sprite award camera-'.$user_camera.' replace" title="'.$camera[$user_camera-1].'">'.$camera[$user_camera-1].'</span>';
			}
			if(count($awards)>0){
				$awards_labels = explode(',', AWARDS);
				foreach($awards as $award) $html .= '<span class="sprite award award-'.$award['category_id'].' replace" title="'.$awards_labels[$award['category_id']-1].', '.Utils::formatDate($award['date']).'">'.$awards_labels[$award['category_id']-1].'</span>';
			}
			if($ambassador) $html .= '<a class="sprite award ambassador replace" href="'.ROOT_PATH.'member/?ambassadors#'.$ambassador.'" title="Ambassador" alt="Ambassador">Ambassador</a>';
			$html .= '</div>';
			
			
			$html .= '</div>';
		
			$html .= '<div id="ContentContainer" class="clearfix">';
			$html .= '<div id="MainColumn">';
			$html .= '<div id="Image" class="clearfix">';
			$html .= '<a href="'.ROOT_PATH.'photo/'.$photo_id.$xml_photo->seo_suffix.'" target="_top" title="View photo details">';
			$html .= '<img class="Loading js_hidden" src="'. $photo_url .'" alt="'.htmlspecialchars($xml_photo->alt_text).'" />';
			$html .= '</a>';
			$html .= '</div>';
	
			$html .= '<div id="ActionsBar" class="clearfix">';
			$rate = round((float)$xml_photo->average_rate);
			$html .= '<div class="RatingContainer clearfix">';
			$html .= '<span class="label">Rating</span>';
			$html .= '<div class="Rating clearfix">';
			$html .= '<ul id="StarRating" class="PositionRelative sprite clearfix">';
			$html .= '<li class="CurrentRating sprite replace PositionAbsolute" style="width:'.(100*$rate/5).'%;">Currently '.$rate.'/5</li>';
	
			if($isLoggedIn){
				$str = $rating > 0 ? 'You rated this '.$rating : '';
				$html .= '<li><a href="#" title="1/5" class="OneStar sprite replace PositionAbsolute">1</a></li>';
				$html .= '<li><a href="#" title="2/5" class="TwoStars sprite replace PositionAbsolute">2</a></li>';
				$html .= '<li><a href="#" title="3/5" class="ThreeStars sprite replace PositionAbsolute">3</a></li>';
				$html .= '<li><a href="#" title="4/5" class="FourStars sprite replace PositionAbsolute">4</a></li>';
				$html .= '<li><a href="#" title="5/5" class="FiveStars sprite replace PositionAbsolute">5</a></li>';
	
			}else $str = '&nbsp;&nbsp;<a href="'.ROOT_PATH.'Login?r=1" target="_top">Log in</a> to rate';
	
			$html .= '</ul>';
			$html .= '</div> <!-- end Rating -->';
			$html .= '<div id="FeedbackRating">'.$str.'</div>';
			$html .= '</div><!-- end RatingContainer -->';
			$html .= '<div class="ActionButtons clearfix">';
			$html .= '<div class="SocialButtons">';
			$photo_absurl = trim(ROOT_URL,'/').$photo_url;
			$html .= '<div class="ActionButton" id="FacebookButton"><a class="sprite replace" alt="Share to Facebook" title="Share to Facebook" href="http://www.facebook.com/sharer.php?u='.urlencode(ABSURL.'photo/'.$photo_id).'&t='.urlencode($page->getTitle()) .'" target="_blank">Facebook</a></div>';
			$html .= '<div class="ActionButton" id="TwitterButton"><a class="sprite replace" alt="Tweet this" title="Tweet this" href="http://twitter.com/home?status=' .urlencode($city.', '.$country.'. Photo by '.$xml_photo->user_name).'%20-%20'.ABSURL.'photo/'.$photo_id.'" target="_blank">Twitter</a></div>';
			$html .= '<div class="ActionButton" id="TumblrButton"><a class="sprite replace" alt="Post to Tumblr" title="Post to Tumblr" href="http://www.tumblr.com/share/photo?source='.urlencode($photo_absurl).'&caption='.urlencode($city.', '.$country.'. Photo by '.$xml_photo->user_name).'&click_thru='.urlencode(ABSURL.'photo/'.$photo_id).'" target="_blank">Tumblr</a></div>';
			$html .= '<div class="ActionButton" id="PinterestButton"><a class="sprite replace" alt="'.$xml_photo->alt_text.'" title="Pin this Photo" rel="'.$photo_absurl.'" href="#">Pinterest</a></div>';
			$html .= '</div>';
			$html .= '<div class="ActionButton FavoritePhoto" id="AddFavorite"><a href="'.($isLoggedIn? ABSURL .'services?method=woophy.photo.addToFavorites&photo_id='. $photo_id:'#').'" class="sprite replace '.($isLoggedIn?'enabled':'disabled').'" title="Add to Favorite photos" alt="Add to Favorite photos">Add to favorite photos</a></div>';
			$html .= '<div class="ActionButton" id="Enlarge"><a class="sprite replace" rel="nofollow" alt="Enlarge" title="View full-sized photo" alt="View full-sized photo" href="'.ROOT_PATH.'download/'.$photo_id.'">View full-sized photo</a></div>';
			if($isLoggedIn){
				if($isEditor) {
					$picked = $photo->isEditorsPick($photo_id);
					$html .= '<div class="ActionButton'.($picked?' Active':'').'" id="EditorsPick"><a href="" title="'.($picked?'Remove':'Make').' Editor\'s Pick" class="sprite replace">Make Editor\'s Pick</a></div>';
				}
				if($ownsPhoto) $html .= '<div class="ActionButton" id="EditPhoto"><a class="sprite replace" title="Edit Photo" alt="Edit Photo" href="'.ROOT_PATH.'account/photos?&photo_id='.$photo_id.'">Edit Photo</a></div>';
			}
			if(!$ownsPhoto) $html .= '<div class="ActionButton" id="ReportAbuse"><a class="sprite replace" title="Report Abuse" alt="Report Abuse" href="'.ROOT_PATH.'report?&url='.urlencode(ABSURL.'photo/'.$photo_id).'">Report Abuse</a></div>';
			$html .= '</div>';
			$html .= '</div> <!-- end ActionsBar -->';
			$html .= '<div id="PhotoDetails" class="clearfix">';
	
			$html .= '<div id="Info">';
			$html .= '<div><span>id:</span>'.$photo_id.'</div>';
			$html .= '<div><span>added:</span>'.Utils::formatDateShort($xml_photo->date).'</div>';
			$html .= '<div><span>votes:</span>'.$xml_photo->num_voters.'</div>';
			$html .= '<div><span>views:</span>'.$xml_photo->views.'</div>';
			$html .= '</div>';
			$html .= '<div id="Description">'.$xml_photo->description.'</div>';
			$html .= '</div> <!-- end PhotoDetails -->';
			$html .= '</div> <!-- end MainColumn -->';
	
			$html .= '<div id="RightColumn">';
			$html .= '<div id="AdHeader">Photos from <strong>'.$city.', '.$country.'</strong> are brought to you by:</div>';			
			$html .= '<div class="AdContainer"><div id="azk92219"></div></div>';// Adzerk
			
			$comments = $photo->getCommentsByPhotoId($photo_id, 0, 5);
			$html .= '<div id="Comments">';
			$html .= '<div class="Header clearfix">';
			$html .= '<h3>Latest comments</h3>';
			if((int)$xml_photo->comment_count>0)$html .='<a href="'.ROOT_PATH.'photo/'.$photo_id.'" target="_top">read all '.$xml_photo->comment_count.'</a>';
			$html .= '</div>';
			$html .= '<div id="CommentsHolder">';
			$maxlength = 68;
			$i = 0;
			foreach ($comments->comment as $comment){
				$html .= '<div class="Comment';
				if(fmod($i++,2)==0) $html .= ' green_bg';
				$html .= '">';
				$html .= '<div class="CommentContent"><div class="Meta clearfix"><div class="Date">'. Utils::formatDateShort($comment->date).'</div>';
				$html .= ' by ';
				if($un = $comment->user_name)$html .= '<a href="'.ABSURL.'member/'.urlencode($un).'" target="_top">'.$un.'</a>';
				else if($un = $comment->poster_name)$html .= $un;
				$html .= '</div>';
				$l = strlen($comment->text);
				$html .= '<p>'.substr(strip_tags($comment->text), 0, $maxlength);//strip in case some long url is posted
				if($l>$maxlength)$html .= '...';
				$html .= '</p></div></div>';
			}
			if($i==0)$html .= '<p class="NoComments">No comments</p>';
			$html .= '</div>';//holder
			$html .= '</div>';//comments
	
			$html .= '<div class="CommentPost">';
			if($isLoggedIn){
				$text = '';
				$error = '';
				if(isset($xml_addcomment)){
					if($err = $xml_addcomment->err){
						$text = $_POST['comment_text'];
						$error = '<span class="Error">'.$err['msg'].'</span>';
					}
				}
				$html .= '<form method="post" action="'.Utils::stripSpecialAction($_SERVER['REQUEST_URI']).'" id="frmpostcomment" target="_blank">';
				$html .= '<textarea class="focus sendable" name="comment_text" id="comment_text" maxlength="750" rows="3" cols="30">'.$text.'</textarea>';
				$html .= '<input type="submit" class="GreenButton submit" id="SubmitComment" name="submit_comment" value="Post a comment" />';
				$html .= '<input type="hidden" class="sendable" name="post_id" value="'.$photo_id.'" />';
				$html .= '<input type="hidden" class="sendable" name="pid" value="'.$pid.'" />';
				$html .= $error;
				$html .= '</form>';
			}else{
				$html .= '<span id="LoginNotice">You have to be <a href="'.ROOT_PATH.'login?r=1&photopopup='.$photo_id.'" target="_top">signed in</a> to comment.</span>';
			}
			$html .= '</div>';//post
			$html .= '</div> <!-- end RightColumn -->';
			$html .= '</div> <!-- end ContentContainer -->';
		}else{
			$html = '<div id="HeaderBar" class="DottedBottom clearfix"><h2>Error</h2></div>'. $err_message;		
		}
		
		if($page->getViewmode()<2) $html .= '</div> <!-- end PhotoPopup -->';
		
		if($page->getViewmode()==1) $html .= '</div> <!-- end PhotoPopupContainer -->';

		//adzerk invocation code:
		$html .= '<script type="text/javascript">Ads.keywords = \''.str_replace(' ',',',addslashes($xml_photo->city_name.' '.$xml_photo->country_name)).'\';</script>';

		echo $html;

		//if($page->getViewmode()<2) echo $page->outputFooterSimple();
		if($page->getViewmode()==0) echo $page->outputFooterSimple();
	
	}else{
		$page->setTitle('No Photo');
		echo $page->outputHeaderSimple();
		echo $page->outputFooterSimple();
	}
?>