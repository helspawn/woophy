<?php

if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}

include_once INCLUDE_PATH.'thumbsgrid.php';
$total = 0;//make global, because member.php line 285
function outputGalleryHTML($properties){
	global $total;
	$default_properties = array('limit'=>6, 'gallery_type'=>'recent', 'user_xml'=>NULL, 'is_homepage'=>FALSE, 'show_full_gallery'=>FALSE, 'link_to_more'=>FALSE, 'dashboard'=>FALSE, 'orderby'=>NULL);
	foreach($default_properties as $key=>$prop) if(!isset($properties[$key])) $properties[$key] = $prop;
	
	$access = ClassFactory::create('Access');
	require_once(CLASS_PATH . 'Photo.class.php');
	$photo = new Photo();

	$has_photos = TRUE;
	$show_user = TRUE;
	$no_photos_message = 'There are no photos yet!';
	$output = '';
	$more_link = '';
	$max_limit = 2000;//2000 limit??
	if(isset($properties['orderby']))$orderby = $properties['orderby'];
	else $orderby = isset($_GET['order_by']) && mb_strtolower($_GET['order_by'])=='recent' ? 'recent' : 'rating';

	$offset = 0;
	if(isset($_GET['offset']))$offset = max(0,(int)$_GET['offset']);
	
	$offset = round($offset/$properties['limit'])*$properties['limit'];
	$offset = min($max_limit-$properties['limit'],$offset);

	if($properties['user_xml']!=NULL){
		$user_name = $properties['user_xml']->name;
		$user_id = (int)$properties['user_xml']->id;
		$user_url = ROOT_PATH.'member/'.urlencode($user_name).'/';
		$account_url = ROOT_PATH.'account/';
		
		/* Some default values, to be overridden if necessary in the switch statement */
		$my = $properties['dashboard'] && $user_id==(int)$access->getUserId();
		if($my) $name_from = 'My';
		else{
			$name_from = htmlspecialchars($user_name).'&#39;';
			if(mb_strtolower(mb_substr($user_name, -1))!='s')$name_from .= 's';
		}
		$no_photos_message = $my ? 'You have no photos yet!' : 'This member has no photos yet!';
		if($properties['dashboard']) $more_link_url = $account_url.'photos';
		else  $more_link_url = $user_url.'photos?&order_by='.$orderby;
		$total = $properties['user_xml']->photo_count;
		$total = min($max_limit, $total);
		
		switch($properties['gallery_type']){
			case 'favorites': // (member overview, member favorites page)
				$gallery_header = $name_from . ' favorite photos';
				if((int)$properties['user_xml']->public_favorites==1 || $my){
					$gallery_id = 'FavoritesGallery'; 
					$photos_xml = $photo->getFavoritesByUserId($user_id, $offset, $properties['limit']);
					if($properties['dashboard']) $more_link_url = $account_url.'favorites/favphotos';
					else $more_link_url = $user_url.'favoritephotos';
					$total = (int)$photos_xml['total_photos'];
					$no_photos_message = $my ? 'You have no favorite photos yet!' : 'This member has no favorite photos yet!';
				}else{
					$no_photos_message = 'The favorite photos of '.$user_name.' are not public.';
					$total=0;
				}
				break;
			default: // recent (member overview, member photos page)
				$show_user = FALSE;
				if($properties['show_full_gallery'])$gallery_header = $name_from .' photos';
				else $gallery_header = $name_from . ($orderby == 'recent'?' latest':' top rated') .' photos';
				$gallery_id = ($properties['is_homepage']? 'MainGallery':($orderby == 'recent'?'RecentGallery':'MemberGallery')); 
				$photos_xml = $photo->getPhotosByUserId($user_id, $offset, $properties['limit'], $orderby);
				break;
		}
	}else{
		$user_id = NULL;
		$user_name = NULL;
		
		switch($properties['gallery_type']){
			default: // recent (homepage, photos homepage)
				$gallery_header = 'Latest Photos';
				$gallery_id = ($properties['is_homepage']? 'MainGallery':'MemberGallery'.time()); 
				$photos_xml = $photo->getRecent($properties['limit']);
				$total = $properties['limit'];
				break;
		}
	}

	if(!$properties['is_homepage']){
		$has_photos = $total>0;
		$more_link = ' <a href="'.$more_link_url.'">View all photos ('.($total>$max_limit?$max_limit.'+':$total).')</a>';
		$output .= '<div class="MainHeader DottedBottom clearfix">';
	}					

	if($properties['dashboard'] && $properties['gallery_type'] == 'recent') $output .= '<div class="UploadButton OrangeButton"><a href="'. ROOT_PATH .'account/upload" class="sprite"><span>Upload new photo</span></a></div>';

	if($properties['show_full_gallery']){
		if($properties['gallery_type']=='recent') $output .= '<div class="PhotoOrderDropdown DropdownContainer"><select class="sprite" onchange="document.location.href=\''.Utils::stripQueryString($_SERVER['REQUEST_URI']).'?&order_by=\'+this.value" name="order_by"><option '.($orderby=='rating'?'selected="true" ':'').'value="rating">Order by Rating</option><option '.($orderby=='recent'?'selected="true" ':'').'value="recent">Order by Recent</option></select></div>';
		$output .= '<h1>'. $gallery_header .'</h1>';
		$output .= ($offset+1).'&nbsp;-&nbsp;'.(min($offset+$properties['limit'],$total)).'&nbsp;of&nbsp;'.$total.($total==$max_limit?'+':'').'&nbsp;total';
		if($total>0) $pagingnav = Utils::getPagingNav($offset, $total, $properties['limit'], '&order_by='.$orderby);
	}else{
		$output .= '<h2>'. $gallery_header .'</h2>';
		$pagingnav = '';
	}
	
	if($properties['link_to_more']) $output .= $more_link;
	if(!$properties['is_homepage']) $output .= '</div>';

	if($has_photos){
		$output .= $pagingnav;
		$output .= '<div id="GalleryContainer" class="clearfix"><div id="'.$gallery_id.'" class="Gallery'.($properties['is_homepage']?' infinite':'').'">';
		$output .= '<div'.($properties['is_homepage']?' id="Page-1"':'').' class="Page clearfix">';
		if($err = $photos_xml->err) $output .= '<div class="Error">'.$err['msg'].'</div>';
		else $output .= outputThumbsGrid($photos_xml->photo, array('link_to_map'=>$properties['is_homepage'], 'show_user'=>$show_user));
		$output .= '</div></div></div> <!-- end Page, Gallery, GalleryContainer -->';
		$output .= $pagingnav;
	}else{
		$output .= '<div class="Notice">' . $no_photos_message .'</div>';
	}
	return $output;
	
}
	