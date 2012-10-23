<?php
function outputThumbsGrid($photos, $options=NULL, $data_format='xml'){
	include_once CLASS_PATH.'Template.class.php';
	
	$tpl = new Template('gallery_image.tpl');
	$default_options = array(
							'use_lightbox' => 	TRUE, 
							'show_user' => 		TRUE, 
							'show_counts' => 	TRUE, 
							'show_location' => 	TRUE, 
							'show_country' => 	TRUE, 
							'show_checkbox' => 	FALSE,
							'link_to_map'	=> 	FALSE
							);
	
	// load defaults
	if($options==NULL):
		$options = $default_options;
	else:
		foreach($default_options as $key=>$opt):
			if(!isset($options[$key])) $options[$key] = $opt;
		endforeach;
	endif;
	
	if(!$options['show_user']) $tpl = new Template('gallery_image_no_user.tpl');
	if(!$options['show_counts']) $tpl = new Template('gallery_image_no_counts.tpl');
	if(!$options['show_location']) $tpl = new Template('gallery_image_no_location.tpl');
	if(!$options['show_country']) $tpl = new Template('gallery_image_no_country.tpl');
	if($options['show_checkbox']) $tpl = new Template('gallery_image_checkbox.tpl');
	

	$str = '';
	if($options['use_lightbox']):
		$lightbox = ' lightbox';
	else:
		$lightbox = '';
	endif;
	
	if($options['link_to_map']):
		$map_link = ' MapLink';
	else:
		$map_link = '';
	endif;

	foreach($photos as $photo):
		$str .= $tpl->parse(array(
			'root_url' 			=> ROOT_PATH,
			'photo_id' 			=> $photo->id,
			'suffix'			=> $photo->seo_suffix,
			'alt'				=> $photo->alt_text,
			'lightbox'			=> $lightbox,
			'map_link'			=> $map_link,
			'user_name'			=> $photo->user_name,
			'city'				=> ($options['show_location'])?$photo->city_name:'',
			'city_id'			=> $photo->city_id,
			'country'			=> $photo->country_name,
			'photo_url' 		=> $photo->photo_url,
			'comment_count'		=> ($options['show_counts'])?(int)$photo->comment_count:'',
			'favorite_count'	=> ($options['show_counts'])?(int)$photo->favorite_count:''
		));	
	endforeach;

	return $str;
}
?>