<?php
require_once '/var/www/woophy/includes/config.php';
include '/var/www/woophy/includes/image.php';
require_once '/var/www/woophy/classes/s3.class.php';
require_once '/var/www/woophy/classes/DB.class.php';
require_once '/var/www/woophy/classes/Image.class.php';
$accessKey = 'AKIAIQPUSDGPVV6FIMAQ';							// AWS Access key

$secretKey = '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';		                    // AWS Secret key

$bucketName = 'woophy.qa';					    // Bucket Name
                                                        // WARNING: Files in that Bucket that are not in the source 
														//          directory will be deleted from the bucket! Make 
								 						//          sure that this really is what you want!

$sourceDir = '/var/www/woophy/html/images/photos/';							// Source directory
$cacheDuration = 3600 * 24 * 30;						// Make Clients cache the files for... seconds (default 30 days)

$fileAcl = S3::ACL_PUBLIC_READ;                        
$s3 = new S3($accessKey, $secretKey);
S3::$useSSL = false;
$http_headers = array('Cache-Control' => 'max-age=' . $cacheDuration, 'Expires' => date('D, j M Y H:i:s \G\M\T', time() + $cacheDuration));
$meta_headers = array();
$query = 'select user_id, photo_id, photo_date from photo_que where full_processed = 0';
$result = DB::query($query);
while($row = DB::fetchAssoc($result)){
	print_r($row)."\n";
	$dir = $row["user_id"];
	$pid = $row['photo_id'];
	$date = $row['photo_date'];
	$file = $pid.'.jpg';
	$dir = str_replace("\r","",$dir);
	$dir = str_replace("\n","",$dir);
    	
	if(strlen($dir) > 3) {
	$arr = str_split($dir,3);
	$dir = $arr[0]."/".$arr[1];
	}
	$oimage =  $sourceDir.$dir.'/original/'.$file;
	$sdir = $sourceDir.$dir.'/thumb/';
	if(!is_dir($sdir)) mkdir($sdir,0777, true);
	$dimage = $sdir.$file;
	resizeimg($oimage,$dimage,MAX_PHOTO_WIDTH_THUMB,MAX_PHOTO_HEIGHT_THUMB);

	$sdir = $sourceDir.$dir.'/medium/';
	if(!is_dir($sdir)) mkdir($sdir,0777, true);
	$dimage = $sdir.$file;
	resizeimg($oimage,$dimage,MAX_PHOTO_WIDTH_MEDIUM, MAX_PHOTO_HEIGHT_MEDIUM);

	$sdir = $sourceDir.$dir.'/large/';
	if(!is_dir($sdir)) mkdir($sdir,0777, true);
	$dimage = $sdir.$file;
	resizeimg($oimage,$dimage,MAX_PHOTO_WIDTH_LARGE, MAX_PHOTO_HEIGHT_LARGE);

	$sdir = $sourceDir.$dir.'/full/';
	if(!is_dir($sdir)) mkdir($sdir,0777, true);
	$dimage = $sdir.$file;
	resizeimg($oimage,$dimage,MAX_PHOTO_WIDTH_FULL, MAX_PHOTO_HEIGHT_FULL);

//	$sdir = $sdir . ((substr($sdir, -1) != '/')?'/':'');
	$sdir = $sourceDir.$dir."/thumb/";
	$ofile = "images/photos/".$dir."/thumb/".$file;
	if ($s3->putObjectFile($sdir . $file, $bucketName, $ofile, $fileAcl, $meta_headers, $http_headers)) {
//		DB::query("update photo_que set full_processed=1 where photo_id=$pid");
		file_put_contents(LOGS_PATH."/processed.log", 'uploaded ' . htmlentities($ofile) . "\n", FILE_APPEND);
	} else {
		file_put_contents(LOGS_PATH."/processed.log", "error ".htmlentities($ofile)."\n", FILE_APPEND);
		$s3ok = false;
	}
	$sdir = $sourceDir.$dir."/medium/";
	$ofile = "images/photos/".$dir."/medium/".$file;
	if ($s3->putObjectFile($sdir . $file, $bucketName, $ofile, $fileAcl, $meta_headers, $http_headers)) {
//		DB::query("update photo_que set full_processed=1 where photo_id=$pid");
		file_put_contents(LOGS_PATH."/processed.log", 'uploaded ' . htmlentities($ofile) . "\n", FILE_APPEND);
	} else {
		file_put_contents(LOGS_PATH."/processed.log", "error ".htmlentities($ofile)."\n", FILE_APPEND);
		$s3ok = false;
	}

	$sdir = $sourceDir.$dir."/large/";
	$ofile = "images/photos/".$dir."/large/".$file;
	if ($s3->putObjectFile($sdir . $file, $bucketName, $ofile, $fileAcl, $meta_headers, $http_headers)) {
//		DB::query("update photo_que set full_processed=1 where photo_id=$pid");
		file_put_contents(LOGS_PATH."/processed.log", 'uploaded ' . htmlentities($ofile) . "\n", FILE_APPEND);
	} else {
		file_put_contents(LOGS_PATH."/processed.log", "error ".htmlentities($ofile)."\n", FILE_APPEND);
		$s3ok = false;
	}
	$sdir = $sourceDir.$dir."/full/";
	$ofile = "images/photos/".$dir."/full/".$file;
	if ($s3->putObjectFile($sdir . $file, $bucketName, $ofile, $fileAcl, $meta_headers, $http_headers)) {
		DB::query("update photo_que set full_processed=1 where photo_id=$pid");
		DB::query("update photos set photo_processed=1 where photo_id=$pid");
		file_put_contents(LOGS_PATH."/processed.log", 'uploaded ' . htmlentities($ofile) . "\n", FILE_APPEND);
	} else {
		file_put_contents(LOGS_PATH."/processed.log", "error ".htmlentities($ofile)."\n", FILE_APPEND);
		$s3ok = false;
	}

}
?>
