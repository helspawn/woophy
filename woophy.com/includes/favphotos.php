<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}
	
	include_once CLASS_PATH.'Photo.class.php';
	include_once CLASS_PATH.'Template.class.php';

	$photo = new Photo();
	$photo->buffer = false;
	if(isset($_POST['submit_delete'])){
		if(isset($_POST['photo_ids'])){
			call_user_func_array(array($photo, 'removeFromFavorites'), $_POST['photo_ids']);
		}
	}
	$html = '';
	$js = '';
	$limit = 25;
	$offset = 0;
	if(isset($_GET['offset']))$offset = (int)$_GET['offset'];
	$offset = round($offset/$limit)*$limit;
	
	$gallery_template = new Template('gallery_image.tpl');
	
	$xml_photos = $photo->getFavorites($offset, $limit);
	$html .= '<div class="OuterContentContainer clearfix">';
	$html .= '<div class="MainHeader DottedBottom clearfix"><h1>My favorite photos</h1>';
	$total = $xml_photos['total_photos'];
	if($total>0){
		$html .= ($offset+1).'&nbsp;-&nbsp;'.(min($offset+$limit,$total)).'&nbsp;of&nbsp;'.$total.'&nbsp;total</div>';	
		
		$pagingnav = Utils::getPagingNav($offset, $total, $limit);
		$html .= $pagingnav;
		$html .= '<div id="GalleryContainer" class="clearfix">';
		$html .= '<form id="EditFavoritePhotos" action="'.$_SERVER['REQUEST_URI'].'" name="frmfavpict" method="post" onsubmit="return confirm(\'Are you sure you want to remove this photo(s) from your favorites?\')">';
		
		include_once INCLUDE_PATH.'thumbsgrid.php';
		$html .= '<div id="FavPicsGallery" class="Gallery clearfix"><div class="Page clearfix">' . outputThumbsGrid($xml_photos->photo, array('use_lightbox'=>FALSE,'show_checkbox'=>TRUE)) . '</div></div>';
		$html .= $pagingnav;
		$html .= '<div class="SubmitRow clearfix"><div id="SelectAll" class="link" onclick="this.innerHTML=checkUncheckAll(document.forms[\'frmfavpict\'][\'photo_ids[]\'])?\'Deselect All\':\'Select All\';return false;">Select All</div>';
		$html .= '<input class="submit GreenButton" type="submit" name="submit_delete" value="remove selected" /></div>';
		$html .= '</form></div>';

	}else $html .= '</div><div class="Notice">You don\'t have any favorite photos yet.</div>';
	$html .= '</div>';
	
	$page = ClassFactory::create('Page');
	$js .= 'var checkflag=false;function checkUncheckAll(field){checkflag=!checkflag;if(!field.length)field=[field];var i = field.length;while(i--)field[i].checked=checkflag;return checkflag;}';
	$page->addInlineScript($js);
	echo $html;
?>