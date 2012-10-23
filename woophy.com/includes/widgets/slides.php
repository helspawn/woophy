<?php
if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden');exit();}
$status = ClassFactory::create('Status');

$cache_id = 'rightcolumn_slides';
$htmlstr = $status->getFromCache($cache_id);
//if($htmlstr == FALSE){

$photo = ClassFactory::create('Photo');
$user = ClassFactory::create('User');

$user->buffer = false;
$xml_cityoftheday = NULL;
$xmlstr = $status->getFromCache('cityoftheday');
if($xmlstr == FALSE){
	$status->updateCityOfTheDay();
	$xmlstr = $status->getFromCache('cityoftheday');
}
if($xmlstr) $xml_cityoftheday = new SimpleXMLElement($xmlstr);

function getUserIdFromUser($xmldoc){
	return $xmldoc->user->id;
}

function OutputFeaturedUserSlide($type, $title, $xmldoc){
	$member_url = ROOT_PATH . 'member/'. urlencode($xmldoc->user_name);
	$output = '';
	
	$output .= '<div id="'. ucfirst($type) .'Slide" class="Slide clearfix js_hidden">';
	$output .= '<h2 class="SlideTitle Header">' . $title . '</h2>';
	$output .= '<div class="SlideContentContainer">';
	$output .= '<div class="SlidePhoto"><a href="'.$member_url.'">';
	if(isset($xmldoc->photo_url))$output .= '<img src="'. $xmldoc->photo_url.'" title="'.$xmldoc->city_name.',	 '. $xmldoc->country_name .'" alt="'. $xmldoc->city_name .', '. $xmldoc->country_name .'" />';
	$output .= '</a></div><div class="SlideInfoContainer" class="clearfix">';
	$output .= '<div class="UserAvatar sprite"><a href="'.$member_url.'"><img src="' . AVATARS_URL . $xmldoc->user_id . '.jpg" alt="'. $xmldoc->user_name .'" title="'. $xmldoc->user_name .'" /></a></div>';
	$output .= '<div class="SlideInfo" class="clearfix">';
	$output .= '<div class="UserInfo clearfix">';
	if($type == 'birthday'):
		$output .= '<h3>Happy Birthday<br><a href="'.$member_url.'">'. $xmldoc->user_name .'</a>!</h3>';
	else:
		$output .= '<div class="UserName"><h3><a href="'.$member_url.'">'. $xmldoc->user_name .'</a></h3></div>';
		if($type == 'motm'):
			$awards_count = $xmldoc->user_award->count();
			$camera = (int)$xmldoc->camera;
			$is_ambassador = isset($xmldoc->ambassador);
			if($awards_count>0 || $camera>0 || $is_ambassador):
				$total_count = $awards_count + $camera + (int)$is_ambassador;
				$output .= '<div class="UserAwards">';
				$output .= '<span class="AwardIcons">';
				$awards_labels = explode(',', AWARDS);
				foreach($xmldoc->user_award as $awd){
					$cat_id = (int)$awd['category_id'];
					if($cat_id<=count($awards_labels)){
						$output .= '<span class="sprite award award-'.$cat_id.' replace" title="'.$awards_labels[$cat_id-1].', '.Utils::formatDate($awd['date']).'">'.$awards_labels[$cat_id-1].', '.Utils::formatDate($awd['date']).'</span>';
					}
				}
				if($camera>0){
					/*TODO: retreive this from dbase!!*/
					$camera_labels = array('Bronze camera, member has published more than 10 photos with above average rating','Silver camera, member has published more than 10 photos with high rating','Gold camera, member has published more than 10 photos with very high rating');
					$output .= '<span class="sprite award camera-'.$camera.' replace" title="'.$camera_labels[$camera-1].'">'.$camera_labels[$camera-1].'</span>';
				}
				if(isset($xmldoc->ambassador))$output .= '<a class="sprite award ambassador replace" href="'.ROOT_PATH.'member/?ambassadors#'.(string)$xmldoc->ambassador.'">Ambassador</a>';

				$output .= '</span>';
				$output .= '</div> <!-- end UserAwards -->';
			endif;
		endif;
	endif;
	$output .= '</div></div> <!-- end UserInfo, SlideInfo -->';
	$output .= '</div></div></div> <!-- end SlideInfoContainer, SlideContentContainer, '. ucfirst($type) .'Slide -->';
	
	return $output;
}

function OutputCitySlide($type, $title, $xml_city){
	if(isset($xml_city->name))$city_url = ROOT_PATH .'city/'.urlencode($xml_city->name).'/'.$xml_city->country_code;

	$output = '';
	$output .= '<div id="'. ucfirst($type) .'Slide" class="Slide clearfix js_hidden">';
	$output .= '<h2 class="SlideTitle Header">' . $title . '</h2>';
	$output .= '<div class="SlideContentContainer">';
	$output .= '<div class="SlidePhoto">';
	
	if(isset($city_url))$output .= '<a href="'.$city_url.'"><img src="' . Utils::getPhotoUrl($xml_city->user_id,$xml_city->photo_id,'large') . '" title="'. $xml_city->name .', '. $xml_city->country_name .'" alt="'. $xml_city->name .', '. $xml_city->country_name .'" /></a>';
	else $output .= '<a name=""></a>';
	
	$output .= '</div>';
	$output .= '<div class="SlideInfoContainer" class="clearfix">';
	$output .= '<div class="SlideInfo" class="clearfix">';
	$output .= '<div class="CityInfo clearfix">';
	
	if(isset($city_url))$output .= '<h3><a href="'.$city_url.'">' . $xml_city->name . '</a>, <a href="'.ROOT_PATH .'country/'.$xml_city->country_code.'">'.$xml_city->country_name.'</a></h3>';
	//$output .= '<span><a href="'.ROOT_PATH.'city/'.urlencode($xml_city->name).'/'.$xml_city->country_code .'">See more photos from ' . $xml_city->name . '</a></span>';
	
	$output .= '</div></div> <!-- end CityInfo, SlideInfo -->';
	$output .= '</div></div></div> <!-- end SlideInfoContainer, SlideContentContainer, '. ucfirst($type) .'Slide -->';
	
	return $output;
}

function OutputPhotoSlide($type, $title, $xml_photo){
	$output = '';
	$output .= '<div id="'. ucfirst($type) .'Slide" class="Slide clearfix js_hidden">';
	$output .= '<h2 class="SlideTitle Header">' . $title . '</h2>';
	$output .= '<div class="SlideContentContainer">';
	$output .= '<div class="SlidePhoto"><a href="'. ROOT_PATH .'photo/' . $xml_photo->photo_id .'"><img src="' . Utils::getPhotoUrl($xml_photo->user_id,$xml_photo->id,'large') . '" title="'. $xml_photo->city_name .', '. $xml_photo->country_name .'" alt="'. $xml_photo->city_name .', '. $xml_photo->country_name .'" /></a></div>';
	$output .= '<div class="SlideInfoContainer" class="clearfix">';
	$output .= '<div class="SlideInfo" class="clearfix">';
	$output .= '<div class="CityInfo clearfix">';
	$output .= '<h3>By <a href="'. ROOT_PATH . 'member/'. urlencode($xml_photo->user_name) .'">' . $xml_photo->user_name . '</a></h3>';
	$output .= '<span><a href="'.ROOT_PATH.'city/'.$xml_photo->city_name.'/'.$xml_photo->country_code .'">'.$xml_photo->city_name.'</a>, <a href="'.ROOT_PATH.'country/'.$xml_photo->country_code .'">'.$xml_photo->country_name.'</a></span>';
	$output .= '</div></div> <!-- end CityInfo, SlideInfo -->';
	$output .= '</div></div></div> <!-- end SlideInfoContainer, SlideContentContainer, '. ucfirst($type) .'Slide -->';
	
	return $output;
}


$slides = array(
	array(
		'type' 				=> 'birthday',
		'title' 			=> 'Member Birthdays',
		'content_xml'	 	=> $user->getFeaturedUser($user->getRandomBirthdayUserId()),
//		'content_xml'	 	=> $photo->getUsersPhotos($user->getBirthdayUserIDs(),5),
		'template_function' => 'OutputFeaturedUserSlide'
	),
	array(
		'type' 				=> 'motm',
		'title' 			=> 'Member of the Month',
		'content_xml'	 	=> $user->getFeaturedUser(getUserIdFromUser($status->getMembersOftheMonth(0,1))),
//		'content_xml'	 	=> $photo->getUsersPhotos($status->getMotmIDs(1)),
		'template_function' => 'OutputFeaturedUserSlide'
	),
	array(
		'type' 				=> 'cotd',
		'title' 			=> 'City of the Day',
		'content_xml'	 	=> $xml_cityoftheday,
		'template_function' => 'OutputCitySlide'
	)
);

$htmlstr='<div id="Slides" class="FeedWidget">';
$htmlstr.='<div class="SlidesContainer">';
foreach($slides as $slide):
	$htmlstr.= $slide['template_function']($slide['type'], $slide['title'], $slide['content_xml']);
endforeach;
$htmlstr.= '</div><!-- end SlidesContainer -->';

$htmlstr.= '<div class="SlideNav unselectable">';
$htmlstr.= '<span class="PagingArrow"><a href="#" class="slide_prev">prev</a></span>';
$htmlstr.= '<ul class="pagination">';
for($x=0;$x>count($slides);$x++):
	$htmlstr.= '<li><a href="#'.$x.'">'.($x+1).'</a></li>';
endfor;
$htmlstr.= '</ul> <!-- end pagination -->';
$htmlstr.= '<span class="PagingArrow"><a href="#" class="slide_next">next</a></span>';
$htmlstr.= '</div><!-- end SlideNav -->';
$htmlstr.= '</div><!-- end Slides -->';

$status->saveToCache($cache_id, $htmlstr, false, 3600);//store in cache for 1 hour
//}
echo $htmlstr;
