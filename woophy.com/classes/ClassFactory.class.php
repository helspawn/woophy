<?php
/*
	Use ClassFactory for including and creating single instances of classes
	TODO: basic error handling
*/
class ClassFactory{
	static private $instances = array();
	public static function create(/*class_name, arg1, arg2*/){
		$args = func_get_args();
		$cls = array_shift($args);
		if(!($ins = self::get($cls))){
			include_once CLASS_PATH.$cls.'.class.php';
			$ref = new ReflectionClass($cls);
			$ins = $ref->newInstanceArgs($args);
			self::$instances[$cls] = $ins;
		}
		return $ins;
	}
	public static function get($cls){
		if(isset(self::$instances[$cls]))return self::$instances[$cls];
		return false;
	}
}
?>