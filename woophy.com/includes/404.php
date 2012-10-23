<?php
if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}

	require_once CLASS_PATH.'Page.class.php';
	$page = new Page();

	$warning = '<div style="margin:70px 0">The requested file is not available</div>';

	if(!headers_sent()){
		$page->setTitle('Error');
		echo $page->outputHeader();
		echo '<div id="MainContent" class="clearfix"><div id="MainColumn">';
		echo '<div id="SubNav"></div>';
		echo $warning;
		echo '</div>';
		echo '<div id="RightColumn"></div></div>';
	}else echo $warning;

	echo $page->outputFooter();
	exit;
?>