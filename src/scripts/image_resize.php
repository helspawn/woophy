<?php
set_time_limit(0);

define('WRITE_TO_LOGFILE', true);
define('SCREEN_OUTPUT', true);
define('FOLDER_PATTERN','/*');
define('IMAGES_PATTERN','/*.{jpg,png}');
define('MAX_W_SMALL',90);
define('MAX_H_SMALL',69);
define('MAX_W_MEDIUM',210);
define('MAX_H_MEDIUM',157);
define('MAX_W_LARGE',600);
define('MAX_H_LARGE',600);
define('IMAGES_FOLDER_NAME_SMALL','s');
define('IMAGES_FOLDER_NAME_MEDIUM','m');
define('IMAGES_FOLDER_NAME_LARGE','l');

$logfile = false;
$start_source_folder = '/home/woophy/woophy-sites/woophy-images';
$start_output_folder = '/home/woophy/woophy-sites/woophy-images/_resized';

output('STARTING EXPORT');
scan_directory($start_source_folder, $start_output_folder);
output('ALL DONE!!!');

if($logfile) fclose($logfile);

function scan_directory($source_folder, $output_folder){
	output('Starting scanning folder ' . $source_folder.FOLDER_PATTERN);
	foreach(glob($source_folder.FOLDER_PATTERN) as $item):
		if(is_dir($item)):
			if(preg_match('/(.*)\/'.IMAGES_FOLDER_NAME_LARGE.'/',$item)):
				output('FOUND A LARGE FOLDER in ' . $item);
				mkdir($output_folder.DIRECTORY_SEPARATOR.IMAGES_FOLDER_NAME_SMALL);
				mkdir($output_folder.DIRECTORY_SEPARATOR.IMAGES_FOLDER_NAME_MEDIUM);
				mkdir($output_folder.DIRECTORY_SEPARATOR.IMAGES_FOLDER_NAME_LARGE);
				foreach(glob($item.IMAGES_PATTERN, GLOB_BRACE) as $image):
					resizeimg($image, $output_folder.DIRECTORY_SEPARATOR.IMAGES_FOLDER_NAME_SMALL, MAX_W_SMALL, MAX_H_SMALL);
					resizeimg($image, $output_folder.DIRECTORY_SEPARATOR.IMAGES_FOLDER_NAME_MEDIUM, MAX_W_MEDIUM, MAX_H_MEDIUM);
					resizeimg($image, $output_folder.DIRECTORY_SEPARATOR.IMAGES_FOLDER_NAME_LARGE, MAX_W_LARGE, MAX_H_LARGE);
				endforeach;
			elseif(preg_match('/^(.*)\/([0-9]+)$/',$item)):
				output('Scanning subfolder ' . $item);
				$subfolder = preg_replace('/(.*)\/([^\/]+)/','$2',$item);
				$subfolder_output = $output_folder.DIRECTORY_SEPARATOR.$subfolder;
				output('Creating directory ' . $subfolder_output);
				mkdir($subfolder_output);
				scan_directory($item, $subfolder_output);
			endif;
		endif;
	endforeach;
}

function resizeimg($sourcefile, $output_folder, $max_w, $max_h){

	output('Resizing ' .$sourcefile.' to '.$output_folder); 
	if(file_exists($sourcefile)){
	
		$image = new Imagick($sourcefile);		
		$format = strtoupper($image->getImageFormat());
		$err = array();
		
		if($format != 'JPEG'){
			if($format == 'PNG') $image->setImageFormat('JPEG');
			else{
				$err[count($err)] = 'Sorry, ' . $format . ' images are not supported. Supported image type are JPEG and PNG.';
			}
		}
		if(count($err)==0){
			$image->setCompression(Imagick::COMPRESSION_JPEG); 
			$image->setCompressionQuality(90);

			$source_dimensions = $image->getImageGeometry();
			
			$source_w = $source_dimensions['width'];
			$source_h = $source_dimensions['height'];

			$source_ratio = $source_w / $source_h;
			$target_ratio = $max_w / $max_h;

			if ($source_ratio > $target_ratio) { // if source image is wider than the thumbnail ratio, we scale down using the width
				$target_w = $max_w;
				$target_h = (int)$max_w/$source_ratio;
			}elseif($source_ratio < $target_ratio){ // if source image is taller than the thumbnail ratio, we scale down using the height
				$target_h = $max_h;
				$target_w = (int)$max_h*$source_ratio;
			}else{ // if source image has the exact same ratio as the thumbnail, scale both
				$target_h = $max_h;
				$target_w = $max_w;
			}
			
			$image->resizeImage($target_w,$target_h,Imagick::FILTER_LANCZOS,1);

			$outputfile = preg_replace('/(.*)\/(.*)\.(jpg|jpeg|png)/i', '$2.jpg', $sourcefile);
			
			$image->writeImage($output_folder.DIRECTORY_SEPARATOR.$outputfile);
			output('Finished resizing ' .$sourcefile. ' to '.$output_folder);
			unset($image);
		}
	}else{
		log_error($sourcefile . "does not exist! No thumbnail could be created.");
	}
}

function output($message, $newline=true){
	global $logfile;
	$prefix = date('d:m:Y H:i:s',time()) . ' - ';
	if(WRITE_TO_LOGFILE):
		if(!$logfile) $logfile = fopen('log.txt', 'a+');
		fwrite($logfile, $prefix . $message . "\n");
	endif;
	
	if(SCREEN_OUTPUT):
		echo $prefix . $message;
		if($newline) echo '<br>';
	endif;
}
