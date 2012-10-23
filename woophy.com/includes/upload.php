<?php
	/*handles upload form posts*/

	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}
		
	if(isset($_POST['city_id'],$_POST['description'],$_FILES['photo_file'])){//default photo upload
		include CLASS_PATH.'Photo.class.php';
		$file = $_FILES['photo_file'];
		$photo = new Photo();
		$ufi = $_POST['city_id'];
		$description = $_POST['description'];
		$cats = isset($_POST['categories']) ? $_POST['categories'] : array();
		$tags = isset($_POST['tags']) ? $_POST['tags'] : '';
		if(isset($_POST['folder_id'])) $folder_id = (int)$_POST['folder_id'];
		else $folder_id = NULL;
		//if(isset($_POST['travelblog_id']))$travelblog_id = (int)$_POST['travelblog_id'];
		//else $travelblog_id = NULL;
		//$xml_photo = $photo->addPhoto($file,$ufi,$cats,$description,$folder_id,$travelblog_id);

		//uncomment to test progress bar
		$xml_photo = $photo->addPhoto($file,$ufi,$cats,$description,$folder_id,$tags);
		
		if($folder_id != NULL){
			include_once CLASS_PATH.'PhotoFolder.class.php';
			$photofolder = new PhotoFolder();
			$photofolder->buffer = false;
			$photofolder->resetLastImage($folder_id);
		}		
	}else if(isset($_FILES['image_value'])){//blog photos
		include CLASS_PATH.'Blog.class.php';
		$file = $_FILES['image_value'];
		$blog = new Blog();
		$xml_photo = $blog->addPhoto($file);
	}
	if(isset($xml_photo)){
		$url = '';
		$photo_id = '';
		$err = 'Upload successful!! Your photo is being processed and will appear on Woophy shortly. You can now upload another photo.';
		$success = 'true';
		if($error = $xml_photo->err){
			$err = $error['msg'];
			$success = 'false';
		}else{
			$url = $xml_photo->url;
			$photo_id = $xml_photo->photo_id;
		}
		echo '<script type="text/javascript">//<![CDATA['.PHP_EOL;
		echo 'if(window.parent)var p = window.parent.Page.uploadprogress;if(p)p.onUploadComplete('.$success.',\''.$err.'\',\''.$url.'\',\''.$photo_id.'\');'.PHP_EOL;
		echo '//]]></script>';
	}
?>
