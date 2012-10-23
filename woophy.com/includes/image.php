<?php
require_once INCLUDE_PATH . 'config.php';

function resizeimg($sourcefile, $output_file, $max_w, $max_h){

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

			if($source_w < $max_w) $max_w = $source_w;//don't upscale
			if($source_h < $max_h) $max_h = $source_h;

			if ($source_ratio > $target_ratio) { // if source image is wider than the thumbnail ratio, we scale down using the width
				$target_w = $max_w;
				$target_h = round($max_w/$source_ratio);
			}elseif($source_ratio < $target_ratio){ // if source image is taller than the thumbnail ratio, we scale down using the height
				$target_h = $max_h;
				$target_w = round($max_h*$source_ratio);
			}else{ // if source image has the exact same ratio as the thumbnail, scale both
				$target_h = $max_h;
				$target_w = $max_w;
			}
			
			$image->resizeImage($target_w,$target_h,Imagick::FILTER_LANCZOS,1);

//			$outputfile = preg_replace('/(.*)\/(.*)\.(jpg|jpeg|png)/i', '$2.jpg', $sourcefile);
			
			$image->writeImage($output_file);
//			output('Finished resizing ' .$sourcefile. ' to '.$output_folder);
			unset($image);
		}
	}else{
		log_error($sourcefile . "does not exist! No thumbnail could be created.");
	}
}
?>