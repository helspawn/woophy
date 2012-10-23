<?php
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // disable IE caching
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
require_once CLASS_PATH.'Response.class.php';
class UploadProgress extends Response{
	const ERRBASE = 1600;
	public function getStatus($key){
		$XMLObject = $this->getXMLObject();
		if(function_exists('uploadprogress_get_info')){
			$status = uploadprogress_get_info($key);
			if($status){
				$xml = $XMLObject->addChild('status');
				foreach($status as $key=>$val){
					$xml->addAttribute($key, $val);
				}
			}else $this->throwError(2, $key);
		}else $this->throwError(1);
		return $XMLObject;
	}
	protected function throwError($code=1, $key=''){
		$msg = '';
		switch($code){
			case 1:$msg='uploadprogress extension is missing.';break;
			case 2:$msg='No status found for key ' .$key;break;
		}
		parent::throwError(self::ERRBASE+$code, $msg);
	}
}
?>