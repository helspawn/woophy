<?php
require_once INCLUDE_PATH . 'config.php';
require_once INCLUDE_PATH . 'image.php';
require_once CLASS_PATH . 's3.class.php';

class Image{
	
	public $errorMessage;
	public $errorNo;
	
	private $file;
	private $img_src;
	private $img_dst;

	private $width;
	private $height;

	private $size;
	private $type;
	private $error;
	private $tmp_name;
	private $name;

	const TYPE_GIF = 'image/gif';//TODO: are these constants used somewhere else?
	const TYPE_PJPEG = 'image/pjpeg';
	const TYPE_JPEG = 'image/jpeg';
	const TYPE_PNG = 'image/png';
	const TYPE_XPNG = 'image/x-png';
	
	const ERRBASE = 800;
	
	public function __construct($file){		
		
		$this->file = $file;
		$this->size = $file['size'];
		$this->type = $file['type'];
		$this->error = $file['error'];
		$this->tmp_name = $file['tmp_name'];
		$this->name = $file['name'];
		$this->img_dest = array();
		$this->type = self::TYPE_JPEG;
		if($this->error == 0){
			$info = getimagesize($this->tmp_name);
//				$memory_needed = round(($info[0]*$info[1]*$info['bits']*$info['channels']/8+pow(2, 16))*3.1);
//				if(function_exists('memory_get_usage') && memory_get_usage()+$memory_needed>(int)ini_get('memory_limit')*pow(1024, 2))$this->throwError(6);
//				else{
					switch($this->type){
						case self::TYPE_GIF:
							$this->img_src = @imagecreatefromgif($this->tmp_name);
							break;
						case self::TYPE_PJPEG:
						case self::TYPE_JPEG:
							$this->img_src = @imagecreatefromjpeg($this->tmp_name);
							break;
						case self::TYPE_PNG:
						case self::TYPE_XPNG:
							$this->img_src = @imagecreatefrompng($this->tmp_name);
							break;
						default:
							$this->throwError(0);
					}
//				}		
			}else $this->throwError($this->error);
		}
		public function getTemporaryFilename(){
			return $this->tmp_name;
		}
		public function isImage(){
			return $this->img_src ? TRUE : FALSE;
		}
		public function destroy(){//Do NOT forget to call this
		if($this->img_src) @imagedestroy($this->img_src);
		foreach($this->img_dest as $dest) @imagedestroy($dest);
	}
	public function getWidth(){
		return $this->img_src ? imagesx($this->img_src) : NULL;
	}
	public function getHeight(){
		return $this->img_src ? imagesy($this->img_src) : NULL;
	}
	public function getDimensions($maxwidth=NULL, $maxheight=NULL){
		if($this->img_src){
			$w = $orgwidth = $this->getWidth();
			$h = $orgheight = $this->getHeight();
			if (isset($maxwidth) && $w > $maxwidth){
				$h *= $maxwidth/$w;
				$w = $maxwidth;
			}
			if(isset($maxheight) && $h > $maxheight){
				$w *= $maxheight/$h;
				$h = $maxheight;
			}
			return array('width'=>round($w),'height'=>round($h),'orgwidth'=>$orgwidth,'orgheight'=>$orgheight);
		}
		return NULL;
	}
	public function output($maxwidth=NULL, $maxheight=NULL, $filename=NULL, $quality=90){
		if($this->img_src){
			if(isset($filename)){
				$dir = dirname($filename);
				if(!is_dir($dir)) mkdir($dir,0777, true);
				$pos = strpos($filename, 'original');
				$fpos = strpos($filename, 'full');
				if($pos === false)
					resizeimg($this->tmp_name,$filename,$maxwidth,$maxheight);
				else
					move_uploaded_file($this->tmp_name,$filename);
				if($pos === false && $fpos === false) {
				        $s3 = new S3(AWS_S3_PUBLIC_KEY, AWS_S3_PRIVATE_KEY);
				        S3::$useSSL = false;
					if($s3->putObjectFile(ABSPATH . 'html/'.$filename, AWS_BUCKET, $filename, S3::ACL_PUBLIC_READ)) {
						file_put_contents("s3.log", "uploaded ".$filename."\n", FILE_APPEND);
						return true;
					 }else file_put_contents("s3.log", "upload failed ".$filename."\n", FILE_APPEND);			
				}					
			}else $this->throwError(5);
		}
		return false;
	}

	private function throwError($id){
		$this->errorNo = self::ERRBASE+$id;
		switch($id){
			case 0:$this->errorMessage='Please select a gif, jpg or png file.';break;
			case 1:$this->errorMessage='Image is more than 4MB.';break;
			case 2:$this->errorMessage='Image is more than 4MB.';break;
			case 3:$this->errorMessage='Image could not be uploaded.';break;
			case 4:$this->errorMessage='Please select a photo.';break;
			case 5:$this->errorMessage='No filename given.';break;
			case 6:$this->errorMessage='The image dimensions exceed the maximum limit. Try to reduce the pixel size.';break;
		}
	}
}
?>
