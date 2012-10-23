<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}
	$access = ClassFactory::create('Access');
	$access->logout();
	if (!headers_sent()){
//		$uri = rtrim(str_replace('//','/',Utils::stripSpecialAction($_SERVER['HTTP_REFERER']).'/'),'/');
//		if(mb_strlen($uri) == 0) $uri = $uri.'/';
		header('Location: '.$_SERVER['HTTP_REFERER']);//redirect for clean url
		exit;
	}
?>
