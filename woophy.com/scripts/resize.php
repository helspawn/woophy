<?php
include_once "../includes/config.php";
include_once "../includes/image.php";
include_once "../classes/s3.class.php";

	$s3 = new S3(AWS_S3_PUBLIC_KEY, AWS_S3_PRIVATE_KEY); 
	$s3->useSSL = false;
	$args = getopt("u:p:");
	
	if($args){
		$user_id = $args['u'];
		$photo_id = $args['p'];
		$a = str_split($user_id, 3);
		$all_sizes_filepath = implode('/', $a).'/';

		$obj = $s3->getObject(AWS_BUCKET,PHOTOS_RELATIVE_PATH.$all_sizes_filepath."l/".$photo_id.'.jpg', "/tmp/".$user_id."_".$photo_id.".jpg");
		if(file_exists("/tmp/".$user_id."_".$photo_id.".jpg")) {
						    $filename = "/tmp/".$photo_id."_large.jpg";
							resizeimg("/tmp/".$user_id."_".$photo_id.".jpg",$filename,MAX_PHOTO_WIDTH_LARGE,MAX_PHOTO_HEIGHT_LARGE);
							$s3->putObjectFile($filename, AWS_BUCKET, PHOTOS_RELATIVE_PATH.$all_sizes_filepath."large/".$photo_id.".jpg", S3::ACL_PUBLIC_READ);
						    $filename = "/tmp/".$photo_id."_thumb.jpg";
							resizeimg("/tmp/".$user_id."_".$photo_id.".jpg",$filename,MAX_PHOTO_WIDTH_THUMB,MAX_PHOTO_HEIGHT_THUMB);
							$s3->putObjectFile($filename, AWS_BUCKET, PHOTOS_RELATIVE_PATH.$all_sizes_filepath."thumb/".$photo_id.".jpg", S3::ACL_PUBLIC_READ);
						    $filename = "/tmp/".$photo_id."_medium.jpg";
							resizeimg("/tmp/".$user_id."_".$photo_id.".jpg",$filename,MAX_PHOTO_WIDTH_MEDIUM,MAX_PHOTO_HEIGHT_MEDIUM);
							$s3->putObjectFile($filename, AWS_BUCKET,PHOTOS_RELATIVE_PATH.$all_sizes_filepath."medium/".$photo_id.".jpg", S3::ACL_PUBLIC_READ);
							$s3->putObjectFile("/tmp/".$user_id."_".$photo_id.".jpg", AWS_BUCKET, PHOTOS_RELATIVE_PATH.$all_sizes_filepath."full/".$photo_id.".jpg", S3::ACL_PUBLIC_READ);
		}
	}


?>
