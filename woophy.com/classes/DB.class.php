<?php
class DB{

	private static $connection = false;
	private static $read_connection = false;
	private static $read = false;
	private static $write = false;

	public static function connect($new_link = false){
		if(self::$connection = @mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, $new_link)){
			if(@mysql_select_db(MYSQL_DBASE, self::$connection))return true;
		}
		return false;
	}
	public static function connect_ro($new_link = false){
		if(self::$read_connection = @mysql_connect(MYSQL_SLAVE_HOST, MYSQL_USER, MYSQL_PASSWORD, $new_link)){
			if(@mysql_select_db(MYSQL_DBASE, self::$read_connection))return true;
		}
		return false;
	}
	public static function close(){
		if(self::$read_connection){
			if(@mysql_close(self::$read_connection)){
				self::$read_connection = false;
				self::$read = false;
			}
		}
		if(self::$connection){
			if(@mysql_close(self::$connection)){
				self::$connection = false;
				self::$write = false;
			}
		}
	}
	public static function escape($text){
		if(self::$write) {
			if(!self::$connection)self::connect();
			return mysql_real_escape_string(trim($text), self::$connection);
		}
		else {
			if(!self::$read_connection)self::connect_ro();
			return mysql_real_escape_string(trim($text), self::$read_connection);
		}
	}
	public static function escapeLikePattern($text){//filters user input for use in query with LIKE statement
			$text = str_replace('\\', '', $text);//apparently a backslash is ignored when using the LIKE operator
			$text = self::escape($text);
			if($text !==false){
				$text = str_replace('%', '\%', $text);
				$text = str_replace('_', '\_', $text);
				if(mb_strlen($text)>=3) $text .= '%';
			}
			return $text;
		}
	public static function escapeMatchPattern($text){//filters user input for use in query with MATCH statement
		$text = str_replace('*', '', trim($text));
		if(mb_strlen($text)>=3){
			if(!strstr($text,'"')){
				if(!strstr($text,"'")){
					//not exact phrase
					if(!strstr($text,' ')){
						//one word
						$text.='*';//truncation
					}
				}
			}
		}
		return self::escape($text);
	}
	public static function query($sql, $do_benchmark=FALSE){
		if($do_benchmark){
			$starttime = microtime(true);
			$debug = ClassFactory::create('Debug');
		}
		if(preg_match ("/^(\s*)select/i", $sql)) {
			if(!self::$read_connection) self::connect_ro();
			$result = mysql_query($sql, self::$read_connection);
			self::$read = true;
			self::$write = false;
		}
		else {
			self::$read = false;
			self::$write = true;
			if(!self::$connection) self::connect();

			$result = mysql_query($sql, self::$connection);
		}
		if($do_benchmark) $debug->benchmark_query($sql, $starttime);
		return $result; 
		
	}
	public static function numRows($result){
		return mysql_num_rows($result);
	}
	public static function numFields($result){
		return mysql_num_fields($result);
	}
	public static function fieldName($result, $field_offset=0){
		return mysql_field_name($result, $field_offset);
	}
	public static function freeResult($result){
		return mysql_free_result($result);
	}
	public static function result($result, $row=0 , $field=null){
		return mysql_result($result, $row, $field);
	}
	public static function insertId(){
		if(self::$write && self::$connection)return mysql_insert_id(self::$connection);
		return false;
	}
	public static function fetchAssoc($result){
		return mysql_fetch_assoc($result);
	}
	public static function fetchArray($result, $result_type = MYSQL_BOTH){
		return mysql_fetch_array($result, $result_type);
	}
	public static function fetchRow($result){
		return mysql_fetch_row($result);
	}
	public static function affectedRows(){
		if(self::$connection)return mysql_affected_rows(self::$connection);		
		return false;
	}
	public static function dataSeek($result, $row_number){
		return mysql_data_seek($result, $row_number);
	}
	public static function error(){
		if(self::$write)
		return mysql_error(self::$connection);
		else
		return mysql_error(self::$read_connection);
		
	}
}
?>
