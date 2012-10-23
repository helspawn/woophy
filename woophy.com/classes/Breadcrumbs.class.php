<?php
class Breadcrumbs{
	private $seperator;
	private $trail;
	public function __construct() {
		$this->seperator = '&nbsp;&lt;&nbsp;';
		$this->trail = array();
	}
	public function add($label='', $path=''){//path is relative to last crumb!
		$this->trail[] = array('label'=>mb_strtolower($label, 'UTF-8'), 'path'=>$path);
	}
	public function output(){
		//print_r($this->trail);
		$p = '';
		$l = count($this->trail);
		$str = '';
		foreach($this->trail as $k=>$v){
			if(mb_strlen($v['path'])>0 && $k != $l-1){//do not show link on last crumb
				$p .= rtrim($v['path'],'/');	
				$str .= '<a href="'.$p.'">'.$v['label'].'</a>';
				$p .= '/';
			}else $str .= $v['label'];
			if($k<$l-1) $str .= $this->seperator;
		}
		return $str;
	}
	public function setSeperator($str=''){
		$this->seperator = $str;
	}
	public function getLength(){
		return count($this->trail);
	}
}
?>