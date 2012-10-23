<?php
class Response{
	
	public $buffer;
	private $xmlobj;
	private $mt_start;
	//static private $dbconn = false;
	static private $memobj = NULL;
	static public $no_err = 'No error message set';
	static public $status_success = 'ok';
	static public $status_fail = 'fail';

	public function __construct(){
		$this->mt_start = $this->getMicrotime();
		$this->buffer = true;
		//if(!self::$dbconn) $dbconn = DB::connect();//no need to connect: DB takes care of this
	}
	public function __destruct(){
		DB::close();
	}
	public function clear(){
		//contruct empty response:
		$this->xmlobj = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8" ?><rsp stat="'.self::$status_success.'"></rsp>');
	}
	public function send(){
		return $this->xmlobj->asXML();
	}
	public function getElapsedTime(){
		return $this->getMicrotime() - $this->mt_start;
	}
	public function getFromCache($key=NULL){
		if(is_string($key) && mb_strlen($key)>0) {
			if($m = $this->getMemcache())return $m->get(md5($key));
		}
		return FALSE;
	}
	public function hasCache(){
		return $this->getMemcache() ? TRUE : FALSE;
	}
	public function deleteFromCache($key=NULL){
		if(is_string($key) && mb_strlen($key)>0) {
			if($m = $this->getMemcache())return $m->delete(md5($key));
		}
		return FALSE;
	}
	public function saveToCache($key=NULL, $data, $compress=FALSE, $expire=60/*sec*/){
		if(is_string($key) && mb_strlen($key)>0) {
			if($m = $this->getMemcache())return $m->set(md5($key), $data, $compress, $expire);
		}
		return FALSE;
	}
	public function getCachedDataFromURL($url=NULL,$expire=0){
		if(!($data = $this->getFromCache($url))){
			$data = file_get_contents($url);
			$this->saveToCache($url, $data,FALSE,$expire); 
		}
		return $data;
	}
	protected function getMicrotime(){
		$mt = explode (' ', microtime());
		return $mt[0] + $mt[1];
	}
	protected function getXMLObject($xmlstr=NULL){
		if(is_string($xmlstr)){
			try{
				$this->xmlobj = new SimpleXMLElement($xmlstr);
			}catch(Exception $e){}
		}else if(!$this->buffer) $this->clear();
		if(!isset($this->xmlobj)) $this->clear();
		return $this->xmlobj;
	}
	protected function throwError($code, $msg=''){
		if(isset($this->xmlobj)){
			$this->xmlobj['stat'] = self::$status_fail;
			$err = $this->xmlobj->addChild('err');
			$err->addAttribute('code', $code);
			$err->addAttribute('msg', mb_strlen($msg)==0 ? self::$no_err : $msg);
		}
	}
	protected function clearError(){
		if(isset($this->xmlobj)){
			if(isset($this->xmlobj->err)){
				$this->xmlobj['stat'] = self::$status_success;
				unset($this->xmlobj->err);
			}
		}
	}
	private function getMemcache(){
		if(self::$memobj === NULL){
			if(class_exists('Memcache')){
				self::$memobj = new Memcache();
				if(@self::$memobj->connect(MEMCACHE_HOST, MEMCACHE_PORT) == FALSE) self::$memobj = FALSE;
			}
		}
		return self::$memobj;
	}
}
?>