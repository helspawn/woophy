<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}
	$param = explode('/', REQUEST_PATH);
	$photo_id = 0;
	if(count($param)>1) $photo_id = (int)$param[1];
	elseif(isset($_GET['photo_id'])) $photo_id = (int)$_GET['photo_id'];
	if($photo_id>0){
		include CLASS_PATH.'Photo.class.php';
		$photo = new Photo();
		$xml_photo = $photo->getMoreInfo($photo_id, false);
		if($error = $xml_photo->err)include INCLUDE_PATH.'404.php';//echo $error['msg'];
		else{
			include CLASS_PATH.'Page.class.php';
			$local = false;
			if(isset($_SERVER['HTTP_REFERER'])){
				if(stripos($_SERVER['HTTP_REFERER'], 'http://' . $_SERVER['SERVER_NAME'])===0){
					$local = true;
				}
			}
			if($local) $photo->increaseDownloads($photo_id);
			
			$page = new Page();
			$page->setDocType('-//W3C//DTD HTML 4.01 Transitional//EN', 'HTML', 'PUBLIC');//loose mode
			$page->addMeta('robots', 'noindex');
			$page->setTitle(ucwords($param[0]));
			if($local){
				//$page->addInlineScript('jQuery(document).ready(function(){var f=function(evt){window.stopEvent(evt);window.close();}.bindAsEventListener(window);var add=EventHandler.addEventListener;add($(\'photo_enlarged\'), \'click\', f);add($(\'back\'), \'click\', f);});');
			}
			echo $page->outputHeaderSimple();
			echo '<div class="DottedBottom lightgreen_bg clearfix">';
			echo '	<a href="'.ROOT_PATH.'"><img src="'.ROOT_PATH.'images/woophy_logo_dark.gif" width="214" height="80" alt="Woophy" /></a>';
			echo '	<div class="copy"><b>Copyright Information and Restrictions</b><br/>Please remember that the copyright of this photo remains with the photographer. It is only allowed to use this photo for non commercial personal use. Any publication of the photo like on a website, in a brochure or newsletter is forbidden without the written permission of the photographer. Please read our <a href="'.ROOT_PATH.'termsofuse">terms of use</a>.</div>';
			echo '</div>';
			echo '<img class="full" onerror="jQuery(this).hide();jQuery(\'#ImageError\').show()" src="'.Utils::getPhotoUrl($xml_photo->user_id, $xml_photo->id,'full','',$xml_photo->seo_suffix).'" alt="'.$xml_photo->alt_text.'"/>';
			echo '<div id="ImageError" class="Notice">This image has just been uploaded and is currently being resized. Please check back in a few minutes.</div>';
			echo '<div class="DottedTop"><a id="back" href="'.ROOT_PATH.'photo/'.$xml_photo->id.'">&#171; Back to Photo Page</a> | '.htmlspecialchars($xml_photo->city_name).', '.$xml_photo->country_name.'. Photo by <a href="'.ROOT_PATH.'member/'.urlencode($xml_photo->user_name).'">'.htmlspecialchars($xml_photo->user_name).'</a></div>';
			echo $page->outputFooterSimple();
		}
	}else include INCLUDE_PATH.'404.php';
?>