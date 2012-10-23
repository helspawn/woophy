<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}
	

	include CLASS_PATH.'Page.class.php';
	include CLASS_PATH.'Template.class.php';
	include CLASS_PATH.'Photo.class.php';
	include INCLUDE_PATH.'thumbsgrid.php';
	include INCLUDE_PATH.'widgets/search.php';
	
	$page = new Page();
	$page->addScript('gallery.js');
	$page->addScript('photopage.js');

	$param = explode('/', rtrim(REQUEST_PATH, '/'));
	
	if(mb_strtolower(Utils::stripQueryString($param[0]))=='search'){//backward compatibility
		$param[0]='photo';
		$param[1]='search';
		$page->setActiveTab(2);
	}

	$photo_id = 0;
	$js = '';
	$main_column = '';
	$right_column = '';
	$search_str = 'Search';
	$search_options = array(
		array('name'=>'keyword', 	'label'=>'Keywords'),
		array('name'=>'city_name', 	'label'=>'City'),
		array('name'=>'user_name', 	'label'=>'Member'),
		array('name'=>'photo_id', 	'label'=>'Photo ID')
	);

	$uri = Utils::stripSpecialAction(Utils::stripQueryString($_SERVER['REQUEST_URI']));
	$baseurl = ROOT_PATH.Utils::stripQueryString($param[0]).'/';

	if(count($param) >= 2)$photo_id = (int)Utils::stripQueryString($param[1]);
	$page->addInlineScript('PhotoPage.photo_id='.$photo_id.';');

	$pages = array('overview','search','browse');
	$currentpage = 0;//default home
	if(count($param) >= 2){
		if($photo_id>0) $currentpage = -1;//no active tab on photo page
		else{
			$i = array_search(mb_strtolower(Utils::stripQuerystring($param[1])), $pages);
			if($i !== false)$currentpage = $i;
		}
	}
	
	$photo = new Photo();
	$photo->buffer = false;
	
	$subnav = '<div id="SubNav">';
	//if($photo_id==0){
		$subnav .= '<ul class="clearfix">';
		foreach($pages as $k=>$v){
			$class = $currentpage==$k ? 'active' : 'inactive';
			$subnav .= '<li><a href="'.$baseurl.$pages[$k].'" class="'.$class.'">'.$v.'</a></li>';
		}
		$subnav .= '</ul>';
	//}
	$subnav .= '</div>';

	// SEARCH BAR
	if($photo_id>0){//photo page
		$page->addInlineScript('init_global_pre.add(function(){PhotoPage.init('.$photo_id.')});');
		$page->setSection($page->getSection().'Detail');
		$access = ClassFactory::create('Access');
		$pid = isset($_SESSION['pid']) ? (int)$_SESSION['pid'] : 0;
		$increaseViews = true;
		if(isset($_POST['pid']) && $_POST['pid']==$pid){//prevent submitting data twice through redirect
			if(isset($_POST['post_id'], $_POST['comment_text'])){
				$increaseViews = false;
				$xml_addcomment = $photo->addComment($_POST['post_id'], $_POST['comment_text']);
			}
			if(isset($_POST['photo_id'], $_POST['tag_text'])){
				$increaseViews = false;
				$xml_tag = $photo->addTags($_POST['photo_id'], $_POST['tag_text']);
			}
			if(isset($_POST['photo_id'], $_POST['submit_removetag'])){
				$increaseViews = false;
				$xml_tag = $photo->removeTag($_POST['photo_id'], $_POST['submit_removetag']);
			}
			if(isset($xml_addcomment) || isset($xml_tag)){
				$pid = $pid + 1;
				$_SESSION['pid'] = $pid;
				session_write_close();
			}
		}
		$xml_photo = $photo->getMoreInfo($photo_id, $increaseViews);
		
		if($err = $xml_photo->err)include INCLUDE_PATH.'404.php';
		else{
			$photo_url = Utils::getPhotoUrl($xml_photo->user_id, $photo_id, 'large', '', $xml_photo->seo_suffix);
			$city = $xml_photo->city_name;
			$country = $xml_photo->country_name;

			$isLoggedIn = $access->isLoggedIn();
 			if($isLoggedIn){
				include_once CLASS_PATH.'User.class.php';
				$user = new User();
				$isEditor = $user->isEditor();
			}

			$ownsPhoto = $xml_photo->user_id == $access->getUserId();
			$title = 'Photo in '.$city.' '.$country.', by '.$xml_photo->user_name;
			$page->setTitle($title);
	
			$js .= 'var map=new MapSideBar({map_id:\'MapSidebar\',marker_image_dir:Page.root_url+\'images/map_markers/\', base_url:Page.root_url,';
			$js .= 'latitude:\''.$xml_photo->latitude.'\',';
			$js .= 'longitude:\''.$xml_photo->longitude.'\',';
			$js .= 'city_id:\''.$xml_photo->city_id.'\'});';	
			$js .= 'window.removeTag = function(){return confirm(\'Are you sure you want to delete this keyword?\')};';
			
			$page->setPageImage($photo_url);
			$page->addInlineStyle('#image a{width:'.$xml_photo->width.'px;height:'.$xml_photo->height.'px;}#image{background-image:url('.$photo_url.')}#image img{display:none}');
			
			$main_column .= '<div id="MainColumn">';

			$main_column .= '<div class="MenuBar clearfix">';
			$main_column .= $subnav;
			$main_column .= '<div class="ActionButtons clearfix">';
			$main_column .= '<div class="SocialButtons">';
			$photo_absurl = trim(ROOT_URL,'/').$photo_url;
			$main_column .= '<div class="ActionButton" id="FacebookButton"><a class="sprite replace" title="Share on Facebook" href="http://www.facebook.com/sharer.php?u='.urlencode(ABSURL.REQUEST_PATH).'&t='.urlencode($page->getTitle()) .'" target="_blank">Facebook</a></div>';
			$main_column .= '<div class="ActionButton" id="TwitterButton"><a class="sprite replace" title="Tweet this" href="http://twitter.com/home?status=' .urlencode($city.', '.$country.'. Photo by '.$xml_photo->user_name).'%20-%20'.urlencode(ABSURL.REQUEST_PATH).'" target="_blank">Twitter</a></div>';
			$main_column .= '<div class="ActionButton" id="TumblrButton"><a class="sprite replace" alt="Post to Tumblr" title="Post to Tumblr" href="http://www.tumblr.com/share/photo?source='.urlencode($photo_absurl).'&caption='.urlencode($city.', '.$country.'. Photo by '.$xml_photo->user_name).'&click_thru='.urlencode(ABSURL.REQUEST_PATH).'" target="_blank">Tumblr</a></div>';
			$main_column .= '<div class="ActionButton" id="PinterestButton"><a class="sprite replace" alt="'.$xml_photo->alt_text.'" title="Pin this Photo" rel="'.$photo_absurl.'" href="#">Pinterest</a></div>';
			$main_column .= '</div>';
			$main_column .= '<div class="ActionButton FavoritePhoto" id="AddFavorite"><a href="'.($isLoggedIn? ABSURL .'services?method=woophy.photo.addToFavorites&photo_id='. $photo_id:'#').'" class="sprite replace '.($isLoggedIn?'enabled':'disabled').'" title="Add to Favorite photos" alt="Add to Favorite photos">Add to favorite photos</a></div>';
			$main_column .= '<div class="ActionButton" id="Enlarge"><a class="sprite replace" alt="Enlarge" rel="nofollow" title="View full-sized photo" alt="View full-sized photo" href="'.ROOT_PATH.'download/'.$photo_id.'">View full-sized photo</a></div>';
			if($isLoggedIn){
				if($isEditor) {
					$picked = $photo->isEditorsPick($photo_id);
					$main_column .= '<div class="ActionButton'.($picked?' Active':'').'" id="EditorsPick"><a href="#" title="'.($picked?'Remove':'Make').' Editor\'s Pick" class="sprite replace">Editor\'s Pick</a></div>';
				}
				if($ownsPhoto) $main_column .= '<div class="ActionButton" id="EditPhoto"><a class="sprite replace" title="Edit Photo" alt="Edit Photo" href="'.ROOT_PATH.'account/photos?&photo_id='.$photo_id.'">Edit Photo</a></div>';
			}
			if(!$ownsPhoto) $main_column .= '<div class="ActionButton" id="ReportAbuse"><a class="sprite replace" title="Report Abuse" alt="Report Abuse" href="'.ROOT_PATH.'report?&url='.urlencode(ABSURL.'photo/'.$photo_id).'" target="_blank">Report Abuse</a></div>';
			$main_column .= '</div></div> <!-- end ActionButtons, MenuBar -->';

			$main_column .= '<div class="Section"><div class="MainHeader DottedBottom"><h1>'.$city.', '.$country. '</h1></div>';
			$main_column .= '<div class="IconBar">';

			
			//rating
			$xml_rating = $photo->getRating($photo_id);
			$rating = (int)$xml_rating->rating;//rating by logged in user
			$rate = round((float)$xml_photo->average_rate);
			$main_column .= '<div class="RatingContainer clearfix"><span class="label">Rating</span><div class="Rating"><ul id="StarRating" class="PositionRelative sprite clearfix">';
			$main_column .= '<li class="CurrentRating sprite replace PositionAbsolute" style="width:'.(100*$rate/5).'%;">Currently '.$rate.'/5</li>';

			if($isLoggedIn){
				$fb = $rating > 0 ? 'You rated this '.$rating : '';
				$main_column .= '<li><a href="#" title="1/5" class="OneStar sprite replace PositionAbsolute">1</a></li>';
				$main_column .= '<li><a href="#" title="2/5" class="TwoStars sprite replace PositionAbsolute">2</a></li>';
				$main_column .= '<li><a href="#" title="3/5" class="ThreeStars sprite replace PositionAbsolute">3</a></li>';
				$main_column .= '<li><a href="#" title="4/5" class="FourStars sprite replace PositionAbsolute">4</a></li>';
				$main_column .= '<li><a href="#" title="5/5" class="FiveStars sprite replace PositionAbsolute">5</a></li>';

			}else $fb = '<a style="display:inline;" href="'.ROOT_PATH.'Login?r=1" target="_top">Log in</a> to rate';
			
			$main_column .= '</ul></div><div id="FeedbackRating">'.$fb.'</div></div>';


			$main_column .= 'Photo by <a href="'.ROOT_PATH.'member/'.urlencode($xml_photo->user_name).'">'.htmlspecialchars($xml_photo->user_name).'</a>';
			
			$awards = $xml_photo->user_award;
			$ambassador = isset($xml_photo->ambassador)?$xml_photo->ambassador:false;
			$user_camera = (int)$xml_photo->user_camera;
			
			$toolbar_width = 290;
			$toolbar_width -= mb_strlen($xml_photo->user_name)*6;
			if($user_camera>0)$toolbar_width -= 24;
			$toolbar_width -= 24*(count($awards)+($ambassador?1:0));

			$main_column .= '<div class="Awards">';
			if($user_camera>0){
				$camera = array('Bronze camera, member has published more than 10 photos with above average rating','Silver camera, member has published more than 10 photos with high rating','Gold camera, member has published more than 10 photos with very high rating');/*KLUDGE: retreive this from dbase!!*/
				$main_column .= '<span class="sprite award camera-'.$user_camera.' replace" title="'.$camera[$user_camera-1].'">'.$camera[$user_camera-1].'</span>';
			}
			if(count($awards)>0){
				$awards_labels = explode(',', AWARDS);
				foreach($awards as $award) $main_column .= '<span class="sprite award award-'.$award['category_id'].' replace" title="'.$awards_labels[$award['category_id']-1].', '.Utils::formatDate($award['date']).'">'.$awards_labels[$award['category_id']-1].'</span>';
			}
			if($ambassador)$main_column .= '<a class="sprite award ambassador replace" href="'.ROOT_PATH.'member/?ambassadors#'.$ambassador.'" title="Ambassador" alt="Ambassador">Ambassador</a>';
			$main_column .= '</div>';
			
			$title = preg_replace('/\n|\r\n/', '', strip_tags((string)$xml_photo->description));
			$maxlength = 150;
			if(mb_strlen($title)>$maxlength)$title = mb_substr($title,0,$maxlength).'...';
			if(mb_strlen($title)>0)$title .= ' - ';
			$main_column .= '</div>';
			$main_column .= '<div id="Image"><a rel="nofollow" href="'.ROOT_PATH.'download/'.$photo_id.'" title="View full-sized photo"><img src="'.$photo_url.'"  alt="'.htmlspecialchars($xml_photo->alt_text).'"/></a></div>';
			
			if(mb_strlen($xml_photo->description)>0)$main_column .= '<div id="Description">'.$xml_photo->description.'</div>';

			//comments
			$main_column .= '</div>';
			$limit = 50;
			$offset = 0;
			if(isset($_GET['offset']))$offset = (int)$_GET['offset'];
			$offset = round($offset/$limit)*$limit;
			$xml_comments = $photo->getCommentsByPhotoId($photo_id, $offset, $limit);
			$comments = $xml_comments->comment;
			$total_comments = (int)$xml_comments['total_comments'];
			if($total_comments>0){
				
				$main_column .= '<div class="Section" id="Comments"><div class="MainHeader"><h2>Comments</h2>';

				$main_column .= ($offset+1).' - '.(min($offset+$limit,$total_comments)).' of '.$total_comments.' total</div>';
				
				$pagingnav = Utils::getPagingNav($offset, $total_comments, $limit, '#Comments');
				
				$main_column .= $pagingnav;

				$tpl1 = new Template('comment.tpl');
				$tpl2 = new Template('comment_poster.tpl');
				$new_time = strtotime('2008-02-10');//at this date, also the time is recorded with each comment
				$main_column .= '<ul>';
				foreach ($comments as $comment){
					$comment_date = (strtotime($comment->date) < $new_time) ? Utils::formatDate($comment->date) : Utils::formatDateTime($comment->date);
					
					if($un = $comment->user_name){
						$main_column .= $tpl1->parse(array(
							'comment_id'=>$comment->id,
							'comment_text'=>$comment->text,
							'comment_url'=>$uri,
							'comment_id'=>$comment->id,
							'user_url'=>ABSURL.'member/'.urlencode($un),
							'user_name'=>htmlspecialchars($un),
							'comment_date'=>$comment_date
						));
					}else if($pn = $comment->poster_name){
						$main_column .= $tpl2->parse(array(
							'comment_id'=>$comment->id,
							'comment_text'=>$comment->text,
							'comment_url'=>$uri,
							'comment_id'=>$comment->id,
							'poster_name'=>$pn,
							'comment_date'=>$comment_date
						));
					}
				}
				$main_column .= $pagingnav;
				$main_column .= '</ul>';

				$main_column .= '</div> <!-- end Comments -->'.PHP_EOL;
			}

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
				$main_column .= $tpl->parse(array('post_id'=>$photo_id,'form_action'=>$uri,'pid'=>$pid,'text'=>$text,'error'=>$error));
			}else{
				$tpl = new Template('commentform_noaccess.tpl');
				$main_column .= $tpl->parse(array('form_action'=>$uri));
			}

			$main_column .= '</div> <!-- end MainColumn -->'.PHP_EOL;
			
			$right_column = '<div id="RightColumn">';
			$right_column .= outputSearchBox($search_str, $baseurl.'search', $search_options).'<div id="MapSidebar" class="Section"></div>';
			$right_column .= '<div class="AdContainer" id="azk76744"></div>';
			
			$right_column .= '<div class="PhotoLocation Section"><div class="SectionHeader clearfix"><h2>Location</h2></div>';
			$right_column .= '<div class="Content">';
			$right_column .= '<div class="clearfix"><span class="label">City:</span><span>'.$city.'</span></div>';
			$right_column .= '<div class="clearfix"><span class="label">Country:</span><span>'.$country.'&nbsp;&nbsp;<a class="flag flag-'. strtolower($xml_photo->country_code) .' replace" href="'.ROOT_PATH.'country/'.$xml_photo->country_code.'">'. $xml_photo->country_code .'</a></span></div>';
			$right_column .= '<div class="clearfix"><span class="label">Latitude:</span><span>'.Utils::dec2dms((float)$xml_photo->latitude, 'lat').'</span></div>';
			$right_column .= '<div class="clearfix"><span class="label">Longitude:</span><span>'.Utils::dec2dms((float)$xml_photo->longitude, 'lon').'</span></div>';
			$right_column .= '</div></div> <!-- end Content Section -->';
			$right_column .= '<div class="AdContainer" id="azk76744"></div>';
			$right_column .= '<div class="PhotoDetails Section"><a name="details"></a><div class="SectionHeader clearfix"><h2>Details</h2></div>';
			$right_column .= '<div class="Content">';
			$right_column .= '<div class="clearfix"><span class="label">Photo id:</span><span>'.$photo_id.'</span></div>';
			$right_column .= '<div class="clearfix"><span class="label">Added on:</span><span>'.Utils::formatDateShort($xml_photo->date).'</span></div>';		
			$right_column .= '<div class="clearfix"><span class="label">Selected as fav:</span><span>'.$xml_photo->favorite_count.'</span></div>';
			$right_column .= '<div class="clearfix"><span class="label">Votes:</span><span>'.$xml_photo->num_voters.'</span></div>';
			$right_column .= '<div class="clearfix"><span class="label">Views:</span><span>'.$xml_photo->views.'</span></div>';
			$right_column .= '<div class="clearfix"><span class="label">Large views:</span><span>'.$xml_photo->downloads.'</span></div>';
			
			$tags = $xml_photo->tags->tag;
			$htmltags = '';
			if($ownsPhoto){
				$htmltags .= '<form action="'.$uri.'#details" name="frmtag" id="Tags" method="post">';
				$htmltags .= '<input type="hidden" name="pid" value="'.$pid.'" />';
				$htmltags .= '<input type="hidden" name="photo_id" value="'.$photo_id.'" />';
			}		
			if(count($tags)>0){
				foreach ($tags as $tag){
					$htmltags .= '<div class="Tag clearfix"><a href="'.ROOT_PATH.'photo/search?&keyword='.urlencode($tag).'#search_results">'.$tag.'</a>';
					if($ownsPhoto)$htmltags .= ' <input class="sprite_admin replace RemoveTag" onclick="return removeTag()" type="submit" name="submit_removetag" value="'.$tag['id'].'"/>';
					$htmltags .= '</div>';
				}
				if($ownsPhoto) $htmltags .= '<br/>';
			}
			if($ownsPhoto){
				$htmltags .= '<input type="text" class="text" id="TagText" name="tag_text" value=""/><input class="submit GreenButton" type="submit" name="submit_addtag" value="Add"/>';
				$htmltags .= '</form>';
			}
			if(mb_strlen($htmltags)>0){
				$right_column .= '<div class="clearfix"><span class="label">Keywords:</span><span>';
				$right_column .= $htmltags;
				$right_column .= '</span></div>';
			}
			$right_column .= '</div></div> <!-- end Content Section -->';
			$exif = new Photo();
			$xml_exif = $exif->getExif($photo_id);
			if(!$xml_exif->err){
				$props = $xml_exif->children();
				if(count($props)>0){
					$right_column .= '<div class="PhotoEXIF Section">';
					$right_column .= '<div class="SectionHeader clearfix"><h2>EXIF</h2></div>';
					$right_column .= '<div class="Content">';
					foreach ($props as $key=>$val) $right_column .= '<div class="clearfix"><span class="label">'.$key.':</span><span>'.$val.'</span></div>';
					$right_column .= '</div></div> <!-- end Content, Section -->';
				}
			}
			$right_column .= '</div> <!-- end RightColumn -->'.PHP_EOL;
		}
	}else{//photos home
		
		$photo_categories = array();//store category names for later use
		$right_column = '<div id="RightColumn">';
		
		if($currentpage==2){//browse
	
			$page->addStyle('dateselector.css');
			$page->addScript('dateselector.js');

			//calendar
			$right_column .= '<div class="Section"><div class="Header clearfix"><h2>Browse Calendar</h2></div>';
			$right_column .= '<div id="calendar"></div>';
			$js .= 'jQuery(document).ready(function(){var delimiter=\'-\';var sel=new DateSelector(document.getElementById(\'calendar\'));sel.dateFormatter=function(d){return d.getFullYear()+delimiter+(d.getMonth()+1)+delimiter+d.getDate();};sel.setRange(new Date(2005,2,4),new Date());sel.showWeeks(true);';
			if(isset($_GET['date'])){
				$d = explode('-', $_GET['date']);
				if(count($d)==3) $js .= 'sel.setSelectedDate(new Date('.(int)$d[0].', '.max((int)$d[1]-1,0).', '.(int)$d[2].'));';
			}else if(isset($_GET['week'])){
				$w = explode('-', $_GET['week']);
				if(count($w)>=2){
					$num = (int)$w[1];
					$y = (int)$w[0];
					if($num==53)$m=11;//fixes a bug with strtotime and week 53
					else $m=(date('m', strtotime("1-1-$y + $num weeks"))-1);
					$js .= 'sel.setSelectedMonth('.$y.', '.$m.');';
				}
			}else if(isset($_GET['month'])){
				$m = explode('-', $_GET['month']);
				if(count($m)>=2)$js .= 'sel.setSelectedMonth('.(int)$m[0].', '.((int)$m[1]-1).');';
			}
			$order_by = 'recent';
			$order_by_fav = false;
			//$url = $baseurl.$param[1];//search or browse
			if(isset($_GET['order_by'])){
				if(mb_strtolower($_GET['order_by'])=='rating')$order_by = 'rating';
				else if(mb_strtolower($_GET['order_by'])=='favorite'){
					$order_by_fav = true;
				}
			}else if(count($param)>=3 && $param[2]=='favorite')$order_by_fav = true;

			$js .= 'jQuery(sel).on(sel.DATE_EVENT, function(evt,p){if(p)document.location.href=\''.$baseurl.$pages[2].'?&date=\'+p+\'&order_by='.$order_by.'\'});';
			$js .= 'jQuery(sel).on(sel.WEEK_EVENT, function(evt,p){if(p)document.location.href=\''.$baseurl.$pages[2].'?&week=\'+p+\'&order_by='.$order_by.'\'});';
			$js .= 'jQuery(sel).on(sel.MONTH_EVENT, function(evt,p){if(p)document.location.href=\''.$baseurl.$pages[2].'?&month=\'+p+\'&order_by='.($order_by_fav?'favorite':$order_by).'\'});';
			
			$js .= '});';

			$right_column .= '</div> <!-- end Section -->';
			$right_column .= '<div class="AdContainer" id="azk76744"></div>';
			
			$right_column .= '<div class="Section search"><div class="Header clearfix"><h2>Browse Categories</h2></div>';
			$xml_cat = $photo->getLastPhotoPerCategory();
			if($error = $xml_cat->err) $right_column .= $error['msg'];
			else{
				$photos = $xml_cat->photo;
				$i = 0;$num = count($photos);
				foreach ($photos as $p){
					$i++;
					$right_column .= '<div class="Excerpt clearfix';
					if($i==$num)$right_column .= ' last';
					$right_column .= '"><a class="Thumb sprite" href="?&category_id='.$p->category_id.'"><img src="'.Utils::getPhotoUrl($p->user_id, $p->id, 'thumb').'" /></a><div class="ExcerptContent"><div><a href="?&category_id='.$p->category_id.'" class="Title">'.$p->category_name.'</a></div>';
					if(isset($p->user_name))$right_column .= '<div>posted by <a href="'.ROOT_PATH.'member/'.urlencode($p->user_name).'">'.htmlspecialchars($p->user_name).'</a></div><div class="Meta">'.Utils::dateDiff(strtotime($p->date)).' ago</div>';
					$right_column .= '</div></div>';
					$photo_categories[(string)$p->category_id] = $p->category_name;
				}
			}
			$right_column .= '</div> <!-- end Section -->';

		}else{//search/home
			$right_column .= '<div class="AdContainer" id="azk76744"></div>';
			$right_column .= '<div class="Section"><div class="Header clearfix"><h2>Most Recent Comments</h2></div>';
			$xml_comments = $photo->getRecentComments(0,10);
			if($err = $xml_comments->err)$right_column .= '<div class="Error">'.$err['msg'].'</div>';
			else{
				$comments = $xml_comments->comment;
				$i = 0;
				$n = count($comments);
				foreach($comments as $comment){
					$i++;
					$right_column .= '<div class="Excerpt ';
					if($i == $n)$right_column .= 'last ';
					$right_column .= 'clearfix">';
					$right_column .= '<a class="Thumb" href="'.ROOT_PATH.'photo/'.$comment->photo_id.'#Comments"><img src="'.Utils::getPhotoUrl($comment->user_id,$comment->photo_id,'thumb').'" /></a>';
					$right_column .= '<div class="ExcerptContent">';
					$right_column .= '<div class="Meta clearfix"><a href="'.ROOT_PATH.'member/'.urlencode($comment->poster_name).'">'.htmlspecialchars($comment->poster_name).'</a> <div class="Date">'.Utils::dateDiff(strtotime($comment->date)).' ago</div></div>';
					$right_column .= '<div class="Comment">'. $comment->text .'</div>';
					$right_column .= '</div></div>';
				}
			}
			$right_column .= '</div> <!-- end Section -->';
		}
		$right_column .= '</div> <!-- end RightColumn -->';


		$page->setTitle('Photos');
		$main_column .= '<div id="MainColumn">';
		$main_column .= '<div class="MenuBar clearfix">';
		$main_column .= $subnav;
		
		if($currentpage!=0){
			$main_column .= '<div class="ActionButtons clearfix">';
			$main_column .= '<div class="ActionButton" id="FacebookButton"><a class="sprite replace" title="Share on Facebook" href="http://www.facebook.com/sharer.php?u='.urlencode(ABSURL.REQUEST_PATH).'&t='.urlencode($page->getTitle()) .'" target="_blank">Facebook</a></div>';
			$main_column .= '<div class="ActionButton" id="TwitterButton"><a class="sprite replace" title="Tweet this" href="http://twitter.com/home?status=' .urlencode($page->getTitle()).'%20-%20'.urlencode(ABSURL.REQUEST_PATH).'" target="_blank">Twitter</a></div>';
			$main_column .= '<div class="ActionButton" id="TumblrButton"><a class="sprite replace" alt="Post to Tumblr" title="Post to Tumblr" href="http://www.tumblr.com/share/photo?source='.urlencode(WOOPHY_LOGO_URL).'&caption='.urlencode($page->getTitle()).'&click_thru='.urlencode(ABSURL.REQUEST_PATH).'" target="_blank">Tumblr</a></div>';
			$main_column .= '<div class="ActionButton" id="PinterestButton"><a class="sprite replace" alt="Add to Pinboard" title="Add to Pinboard" href="#">Pinterest</a></div>';
			$main_column .= '</div>';
		}
		$main_column .= '</div> <!-- end MenuBar -->';
		
		if($currentpage!=1)$main_column .= outputSearchBox($search_str, $baseurl.'search', $search_options);
		
		switch($currentpage){
			case 1://search
			case 2://browse
				
				include CLASS_PATH.'AdvancedSearch.class.php';
				
				if(isset($_GET['city']))$_GET['city_name']=$_GET['city'];

				$search_param = array('keyword','user_name','city_name','country_code','category_id','order_by','last24h','photo_id','sort_order','offset','total','user_type','date','week','month');
				$search_input = array();

				//backward compatibility:
				$search_param_old = array('kw','un','c','cc','ct','ob');
				foreach($search_param_old as $k=>$v){
					if(isset($_GET[$v]))$_GET[$search_param[$k]]= $_GET[$v];
				}
				$dosearch = FALSE;
				foreach($search_param as $k=>$v){
					if(isset($_GET[$v])){
						$val = trim($_GET[$v]);
						if($v=='last24h')$search_input[$v] = 1;//last24h doesn't require value, assign value because AdvancedSearch requires
						else $search_input[$v] = $val;
						if(mb_strlen($v)>0)$dosearch = TRUE;
					}else $search_input[$v] = '';//default empty
				}

				if(mb_strlen($search_input['offset'])==0)$search_input['offset'] = 0;//default
				if(mb_strlen($search_input['order_by'])==0)$search_input['order_by'] = 'recent';//default
				if($search_input['order_by']=='averageRate')$search_input['order_by'] = 'rating';//backward compatibility
				else if($search_input['order_by']=='date')$search_input['order_by'] = 'recent';//backward compatibility
				else if($search_input['order_by']=='views')$search_input['order_by'] = 'popular';//backward compatibility
				if($search_input['user_type']!=0)$search_input['user_type'] = 1;//default exact match
				$search_input['sort_order'] = mb_strtolower($search_input['sort_order'])=='asc'?'asc':'desc';//default DESC
	
				if($currentpage == 2 || $dosearch){
			
					$search_results_header = 'Search Results';
					$select_order = '';
					$browse_editors = false;
					if($currentpage == 2){
						if(count($param)>=3){
							$p = mb_strtolower(Utils::stripQueryString($param[2]));
							if($p=='toprated') $search_input['order_by'] = 'rating';//this is not (yet) used
							else if($p=='favorite') $search_input['order_by'] = 'favorite';
							else if($p=='editors') {
								if(strlen($search_input['category_id'])==0)$browse_editors = true;
							}
						}
						switch($search_input['order_by']){
							case 'rating':
								$search_results_header = 'Top Rated Photos';
							case 'favorite':
								$search_results_header = 'Most Favorited Photos';
								break;
							default:
								$search_results_header = 'Most Recent Photos';
						}				
					}
					
					if($browse_editors ){
						$search_results_header = 'Browse Editors\' Picks';
						$xml_search = $photo->getRecentEditorsPicks($search_input['offset'], 30, (isset($xml_search['total']) ? min((int)$xml_search['total'],1000) : NULL));
					}else{

						$search = new AdvancedSearch();
						$xml_search = call_user_func(array($search, 'dosearch'), $search_input);

						//TRICKY: order matters below, same as in AdvancedSearch
						if(strlen($search_input['category_id'])>0){
							if(isset($photo_categories[$search_input['category_id']]))$search_results_header = 'Browse '.$photo_categories[$search_input['category_id']];
						}else if(strlen($search_input['last24h'])>0)$search_results_header = 'Browse last 24h';
						else if(strlen($search_input['date'])>0 || 
								strlen($search_input['week'])>0 || 
								strlen($search_input['month'])>0){
							
							$orderby = 'recent';
							if(isset($_GET['order_by'])){
								$ob = mb_strtolower($_GET['order_by']);
								if($ob=='rating')$orderby = 'rating';
								if($ob=='favorite'){
									if(strlen($search_input['month'])>0)$orderby = 'favorite';
								}
							}
							
							$select_order = '<div class="PhotoOrderDropdown DropdownContainer"><select class="sprite" onchange="document.location.href=\''.Utils::stripQueryString($_SERVER['REQUEST_URI']).'?';

							if(strlen($search_input['date'])>0){
								$search_results_header = Utils::formatDate($search_input['date']);
								$select_order .= '&date='.$search_input['date'];
							}else if(strlen($search_input['week'])>0){
								$week = explode('-',$search_input['week']);
								if(count($week)>=2)$search_results_header = 'Browse '.$week[0].', week '.$week[1];
								$select_order .= '&week='.$search_input['week'];
							}else{
								$a = explode('-', $search_input['month']);
								if(count($a)>1)$search_results_header = date('F, Y', mktime(0, 0, 0, (int)$a[1], 1, (int)$a[0]));
								$select_order .= '&month='.$search_input['month'];
							}
							$select_order .= '&order_by=\'+this.value" name="order_by"><option '.($orderby=='date'?'selected="true" ':'').'value="date">Order by Date</option><option '.($orderby=='rating'?'selected="true" ':'').'value="rating">Order by Rating</option>';
							if(strlen($search_input['month'])>0)$select_order .= '<option '.($orderby=='favorite'?'selected="true" ':'').'value="favorite">Order by Favorite</option>';
							$select_order .= '</select></div>';
						}
					}
				}

				if($currentpage == 1){

					$main_column .= '<div class="Section">';
					$main_column .= '<form id="SearchForm" action="'.$uri.'#search_results'.'" name="frm_search" method="get" target="_self">';
					
					$main_column .= '<div class="MainHeader"><h1>Search</h1></div>';
					
					
					$main_column .= '<div class="FormArea DottedBottom DottedTop">';
					$main_column .= '<div class="FormRow clearfix"><label for="keyword">Search by keyword</label><input class="text" type="text" id="keyword" name="keyword" value="'.htmlspecialchars($search_input['keyword']).'" /></div>';
					$main_column .= '</div><!-- end FormArea -->';
					$main_column .= '<div class="FormArea DottedBottom">';
					$main_column .= '<div class="FormRow clearfix"><label for="user_name">Search by member</label><input id="user_name" class="text" type="text" name="user_name" value="'.htmlspecialchars($search_input['user_name']).'" />';
					$main_column .= '<div class="UserType DropdownContainer"><select class="sprite" name="user_type"><option value="1"';
					if($search_input['user_type']==1)$main_column .= ' selected="true"';
					$main_column .= '>Exact Match</option><option value="0"';
					if($search_input['user_type']==0)$main_column .= ' selected="true"';
					$main_column .= '>Starts with</option></select></div></div>';
					$main_column .= '<div class="FormRow clearfix"><label for="country">In country</label>';
					$main_column .= '<div class="CountryDropdown DropdownContainer"><select class="sprite" name="country_code" id="cc">';
					$main_column .= '<option value="">Select country</option>';

					include CLASS_PATH.'Location.class.php';
					$location = new Location();
					$xml = $location->getAllCountries();
					if($error = $xml->err)$main_column .= $error['msg'];
					else{
						$country_code = mb_strtoupper($search_input['country_code']);
						$sel = true;
						if(isset($_GET['country']))$cn = $_GET['country'];
						if(isset($_GET['country_name']))$cn = $_GET['country_name'];
						foreach ($xml->country as $c){		
							$main_column .= '<option value="'.$c['cc'].'"';
							if($sel && $country_code == $c['cc']){
								$main_column .= ' selected="true"';
								$sel = false;
							}
							if($sel && isset($cn))if($cn == $c){
								$main_column .= ' selected="true"';
								$sel = false;
							}
							$main_column .= '>'.$c.'</option>';
						}
					}
					$main_column .= '</select></div></div>';
					$main_column .= '<div class="FormRow clearfix"><label for="city_name">In city</label><input class="text" type="text" id="city_name" name="city_name" value="'.htmlspecialchars($search_input['city_name']).'" /></div>';
					$main_column .= '<div class="FormRow clearfix"><label for="photo_id">By photo #</label><input class="text" type="text" id="photo_id" name="photo_id" value="'.htmlspecialchars($search_input['photo_id']).'" /></div>';
					$main_column .= '</div><!-- end FormArea -->';
					$main_column .= '<div class="FormArea  DottedBottom">';
					$main_column .= '<div class="FormRow clearfix"><label>Order by</label>';

					$order_by_cat = array('recent', 'rating');
					$order_by = mb_strtolower($search_input['order_by']);
					foreach($order_by_cat as $cat){
						$main_column .= '<input type="radio" name="order_by" id="order_by_'.$cat.'" value="'.$cat.'"';
						if($order_by == $cat) $main_column .= ' checked="true"';
						$main_column .= '/>';
						$main_column .= '<label class="SmallLabel" for="order_by_'.$cat.'">'.ucfirst($cat).'</label>';
					}
					$main_column .= '</div>';
					$main_column .= '<div class="FormRow clearfix"><label>Sort order</label><div class="PhotoOrderDropdown DropdownContainer"><select class="sprite" name="sort_order"><option value="desc"';
					if(mb_strtolower($search_input['sort_order'])=='desc')$main_column .= ' selected="true"';
					$main_column .= '>Descending</option><option value="asc"';
					if(mb_strtolower($search_input['sort_order'])=='asc')$main_column .= ' selected="true"';
					$main_column .= '>Ascending</option></select></div></div>';
					
					$main_column .= '</div><!-- end FormArea -->';
					$main_column .= '<div class="FormArea">';
					$main_column .= '<div class="FormRow clearfix"><input type="submit" class="GreenButton submit" name="search" value="Search" /></div>';
					$main_column .= '</div><!-- end FormArea -->';
					$main_column .= '<input type="hidden" name="offset" value="0" />';
					$main_column .= '</form>';

					$main_column .= '</div>';
				}
				break;
			default://home
				//recent photos:
				$page->addRSS(ROOT_PATH.'feeds/photos', 'Woophy Recently added Photos');
				$main_column .= '<div class="OuterContentContainer clearfix">';
				$main_column .= '<div class="MainHeader DottedBottom"><h2>Most Recent Photos</h2><a href="'.$baseurl.$pages[2].'/recent">View last 1000</a></div>';
				$xml_recent = $photo->getRecent(6);
				if($err = $xml_recent->err) $main_column .= '<div class="Error">'.$err['msg'].'</div>';
				else $main_column .= '<div id="GalleryContainer" class="clearfix"><div id="RecentPhotosGallery" class="Gallery clearfix"><div class="Page clearfix">' . outputThumbsgrid($xml_recent->photo) . '</div></div></div>';
				$main_column .= '</div> <!-- end OuterContentContainer -->';
				//top rated:
				$main_column .= '<div class="OuterContentContainer clearfix">';
				$main_column .= '<div class="MainHeader DottedBottom"><h2>Top Rated Photos Today</h2>';
				$xml_toprated = $photo->getPhotosTopRatedToday();
				if($err = $xml_toprated->err){
					$main_column .= '</div><div class="Error">'.$err['msg'].'</div>';
				}else{
					$photos = $xml_toprated->photo;
					$main_column .= '<a href="'.$baseurl.$pages[2].'?&date='.date('Y-m-d').'&order_by=rating">View All Today</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="'.$baseurl.$pages[2].'?&week='.Utils::getYearWeek().'&order_by=rating">This Week</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="'.$baseurl.$pages[2].'?&month='.date('Y-m').'&order_by=rating">This Month</a></div>';
					if(count($photos)==0){
						$main_column .= '<div class="Notice">No photos have been uploaded yet!</div>';
					}else $main_column .= '<div id="GalleryContainer" class="clearfix"><div id="TopRatedPhotosGallery" class="Gallery clearfix"><div class="Page clearfix">' . outputThumbsgrid($photos) . '</div></div></div>';
				}
				$main_column .= '</div> <!-- end OuterContentContainer -->';

				//most favorited:
				$main_column .= '<div class="OuterContentContainer clearfix">';
				$main_column .= '<div class="MainHeader DottedBottom"><h2>Most Favorited This Month</h2><a href="'.$baseurl.$pages[2].'?&month='.date('Y-m').'&order_by=favorite">View All</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="'.$baseurl.$pages[2].'/favorite">Top 1000 All Time</a></div>';
				$xml_favorite = $photo->getPhotosMostFavorited();
				if($err = $xml_favorite->err) $main_column .= '<div class="Error">'.$err['msg'].'</div>';
				else{
					$photos = $xml_favorite->photo;
					if(count($photos)==0) $main_column .= '<div class="Notice">No photos have been favorited yet!</div>';
					else $main_column .= '<div id="GalleryContainer" class="clearfix"><div id="MostFavoritedPhotosGallery" class="Gallery clearfix"><div class="Page clearfix">' . outputThumbsgrid($photos) . '</div></div></div>';
				}
				$main_column .= '</div> <!-- end OuterContentContainer -->';
				
				
				//recent editors picks:
				$main_column .= '<div class="OuterContentContainer clearfix">';
				$main_column .= '<div class="MainHeader DottedBottom"><h2>Recent Editors\' Picks</h2><a href="'.$baseurl.$pages[2].'/editors">View last 1000</a></div>';
				$xml_editorspicks = $photo->getRecentEditorsPicksFromCache();
				if($err = $xml_editorspicks->err) $main_column .= '<div class="Error">'.$err['msg'].'</div>';
				else{
					$photos = $xml_editorspicks->photo;
					if(count($photos)==0) $main_column .= '<div class="Notice">No photos have been picked yet!</div>';
					else $main_column .= '<div id="GalleryContainer" class="clearfix"><div id="EditorsPicksGallery" class="Gallery clearfix"><div class="Page clearfix">' . outputThumbsgrid($photos) . '</div></div></div>';
				}
				$main_column .= '</div> <!-- end OuterContentContainer -->';
		
			}	
		
		//display search/browse results:
		if(isset($xml_search)){
			if($error = $xml_search->err)$main_column .= $error['msg'];
			else{
				
				$limit = 30;//30 photos per page
				$max = 1000;
				$offset = min($max-$limit, max(0,$search_input['offset']));//max 1000 photos
				unset($search_input['offset']);//offset gets handled by pagingNav
				$total = isset($xml_search['total']) ? min((int)$xml_search['total'],$max) : 0;
				$qs = '&total='.$total;
			
				if($total==0)$select_order = '';
				foreach($search_param as $v){
					if($v=='total')continue;
					if(isset($search_input[$v]) && strlen($search_input[$v])>0)$qs .= '&'.$v.'='.$search_input[$v];
				}
				
				$main_column .= '<div class="OuterContentContainer clearfix">';
				$main_column .= '<div class="MainHeader DottedBottom">';
				if($currentpage == 1){
					$qs .= '#search_results';
					$main_column .= '<a name="search_results"></a>';
				}
				
				$header_tag = ($currentpage==1)?'<h2>':'<h1>';
				$header_tag_close = ($currentpage==1)?'</h2>':'</h1>';
				$main_column .= $select_order.$header_tag.$search_results_header.$header_tag_close.($total>0?$offset+1:0).' - '.min($total, $offset+$limit).' of '.($total>=1000?'1000+':$total).' total';
				$main_column .= '</div>';
				$nav = Utils::getPagingNav($offset, $total, $limit, $qs);
				$main_column .= $nav;

				$photos = $xml_search->photo;
				$l = count($photos);
				if($l>0)$main_column .= '<div id="GalleryContainer" class="clearfix"><div id="SearchGallery" class="Gallery clearfix"><div class="Page clearfix">' . outputThumbsgrid($photos) . '</div></div></div>';
				else $main_column .= '<div class="Notice">Your search did not match any photos.</div>';
				$main_column .= $nav;
				$main_column .= '</div> <!-- end OuterContentContainer -->';
			}
		}
		
		$main_column .= '</div> <!-- end MainColumn -->'.PHP_EOL;

	}

	$page->addInlineScript($js);
	echo $page->outputHeader(2);
	echo '<div id="MainContent" class="clearfix">';
	echo $main_column;
	echo $right_column;
	echo '</div> <!-- end MainContent -->'.PHP_EOL;
	echo $page->outputFooter();
?>

