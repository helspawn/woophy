<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}

	include_once CLASS_PATH.'Template.class.php';

	$param = explode('/', rtrim(REQUEST_PATH, '/'));	
	$absurl = ROOT_PATH.$param[0].'/';

	include_once INCLUDE_PATH.'thumbsgrid.php';
	function outputThumbsGridContest($photos){//helper function, used in contest result files
		$xml = new SimpleXMLElement('<photos></photos>');
		foreach($photos as $p){
			$photo = $xml->addChild('photo');
			$photo->addChild('id', $p['pid']);
			$photo->addChild('user_id', $p['uid']);
			$photo->addChild('user_name', $p['un']);
			$photo->addChild('city_name', $p['city']);
			$photo->addChild('country_name', $p['country']);
		}
		return outputThumbsGrid($xml->photo);
	}


	$js = '';
	
	$user_ids = explode(',', CONTEST_JURY);
	$access = ClassFactory::create('Access');
	$user_id = $access->getUserId();
	if(in_array($user_id, $user_ids))$pages = array('home','submit','submissions','jury','results');
	else $pages = array('home','submit','submissions');
	$baseurl = ROOT_PATH.Utils::stripQueryString($param[0]).'/';
	
	$currentpage = 0;//default home
	if(count($param) >= 2){
		$i = array_search(mb_strtolower(Utils::stripQuerystring($param[1])), $pages);
		if($i !== false)$currentpage = $i;
	}
	$navbar = '<div class="MenuBar clearfix"><div id="SubNav"><ul class="clearfix">';
	foreach($pages as $k=>$v){
		$class = $currentpage==$k ? 'active' : 'inactive';
		$navbar .= '<li><a href="'.$baseurl.$pages[$k].'" class="'.$class.'">'.$v.'</a></li>';
	}
	$navbar .= '</ul></div></div>';
	
	$results_id = 0;
	$contest_id = 2011;//default current contest
	if(count($param) > 1){
		$param1 = mb_strtolower(Utils::stripQueryString($param[1]));
		if($param1 == 'submit' || $param1 == 'jury' || $param1 == 'results')include_once INCLUDE_PATH.'login.php';
		elseif(is_numeric($param1))$contest_id = $param1;
		else{
			$a = explode('_',$param[1]);
			if(count($a)==2){
				if(is_numeric($a[0]) && is_numeric($a[1])){
					$contest_id	= $a[0];
					$results_id = $a[1];
				}
			}
		}
	}


	$outputstr = '';
	if(isset($param1) && $param1 == 'submit'){
		$file = 'contest_submit';
	}elseif(isset($param1) && $param1 == 'submissions'){
		$file = 'contest_submissions';
	}elseif(isset($param1) && $param1 == 'jury'){
		$file = 'contest_jury';
	}elseif(isset($param1) && $param1 == 'results'){
		$file = 'contest_results';
	}else{
		$file = 'contest'.$contest_id;
		if($results_id > 0)$file.='_'.$results_id;
	}

	$page = ClassFactory::create('Page');
	$page->setTitle('Contest');

	$maincolumn = '<div id="MainColumn">'.PHP_EOL;
	$maincolumn .= $navbar.PHP_EOL;

	$file = INCLUDE_PATH.'contest'.DIRECTORY_SEPARATOR.$file.'.php';
	if(file_exists($file)){
		ob_start();
		include $file;
		$outputstr .= ob_get_clean();
	}

	
	ob_start();
	$maincolumn .= ob_get_clean();
	
	$maincolumn .= $outputstr;
	$maincolumn .= '</div> <!-- end MainColumn -->'.PHP_EOL;


	$rightcolumn = '<div id="RightColumn">'.PHP_EOL;
	
	
	$rightcolumn .= '<div class="Section"><div class="Header clearfix"><h2>Contest 2011 - 2012</h2></div><a class="Thumbs" href="'.$absurl.'2011"><img src="'. ROOT_PATH.'images/contest/category_4.jpg" width="280" /></a>';
	$rightcolumn .= '<div class="clearfix"><h3 class="floatleft">Picture Your World</h3><div class="PostDate floatright">'.Utils::formatDateShort('1-12-2011').'</div></div>';
	$rightcolumn .= '<ul class="ContestArchive"><li';
	if($contest_id == 2011 && $results_id == 0) $rightcolumn .= ' class="active"';
	$rightcolumn .= '><a href="'.$absurl.'2011">';
	if($contest_id == 2011 && $results_id == 0) $rightcolumn .= '&gt;&nbsp;';
	$rightcolumn .= 'Introduction</a></li>';

	$rightcolumn .= ($contest_id == 2012 && $results_id == 6) ? '<li class="active">' : '<li>';
	$rightcolumn .= '<a href="'.$absurl.'2012_6">';
	if($contest_id == 2012 && $results_id == 6) $rightcolumn .= '&gt;&nbsp;';
	$rightcolumn .= 'June 2012 - Work</a></li>';

	$rightcolumn .= ($contest_id == 2012 && $results_id == 5) ? '<li class="active">' : '<li>';
	$rightcolumn .= '<a href="'.$absurl.'2012_5">';
	if($contest_id == 2012 && $results_id == 5) $rightcolumn .= '&gt;&nbsp;';
	$rightcolumn .= 'May 2012 - People</a></li>';
	
	$rightcolumn .= ($contest_id == 2012 && $results_id == 4) ? '<li class="active">' : '<li>';
	$rightcolumn .= '<a href="'.$absurl.'2012_4">';
	if($contest_id == 2012 && $results_id == 4) $rightcolumn .= '&gt;&nbsp;';
	$rightcolumn .= 'April 2012 - Landscapes</a></li>';

	$rightcolumn .= ($contest_id == 2012 && $results_id == 3) ? '<li class="active">' : '<li>';
	$rightcolumn .= '<a href="'.$absurl.'2012_3">';
	if($contest_id == 2012 && $results_id == 3) $rightcolumn .= '&gt;&nbsp;';
	$rightcolumn .= 'March 2012 - Animals</a></li>';

	
	$rightcolumn .= ($contest_id == 2012 && $results_id == 2) ? '<li class="active">' : '<li>';
	$rightcolumn .= '<a href="'.$absurl.'2012_2">';
	if($contest_id == 2012 && $results_id == 2) $rightcolumn .= '&gt;&nbsp;';
	$rightcolumn .= 'February 2012 - Cityscape</a></li>';


	$rightcolumn .= ($contest_id == 2012 && $results_id == 1) ? '<li class="active">' : '<li>';
	$rightcolumn .= '<a href="'.$absurl.'2012_1">';
	if($contest_id == 2012 && $results_id == 1) $rightcolumn .= '&gt;&nbsp;';
	$rightcolumn .= 'January 2012 - Culture</a></li>';

	$rightcolumn .= ($contest_id == 2011 && $results_id == 12) ? '<li class="active">' : '<li>';
	$rightcolumn .= '<a href="'.$absurl.'2011_12">';
	if($contest_id == 2011 && $results_id == 12) $rightcolumn .= '&gt;&nbsp;';
	$rightcolumn .= 'December 2011 - Nature</a></li>';



	$rightcolumn .= '</ul>';
	$rightcolumn .= '<input type="button" class="GreenButton" onclick="document.location.href=\''.$absurl.'submit\'" value="Submit a photo"/></div>';
	$rightcolumn .= '<div class="AdContainer" id="azk76744"></div>';
	$rightcolumn .= '<div class="Section"><div class="Header clearfix"><h2>Contest Archive</h2></div>
	
	<a class="Thumbs" href="'.$absurl.'2007"><img src="'. ROOT_PATH.'images/contest/category_3.jpg" width="280" /></a>';
	$rightcolumn .= '<div class="clearfix"><h3 class="floatleft">News in Pictures</h3><div class="PostDate floatright">'.Utils::formatDateShort('1-1-2007').'</div></div>';
	$rightcolumn .= '<ul class="ContestArchive">';

	$rightcolumn .= ($contest_id == 2007 && $results_id == 1) ? '<li class="active">' : '<li>';
	$rightcolumn .= '<a href="'.$absurl.'2007_1">';
	if($contest_id == 2007 && $results_id == 1) $rightcolumn .= '&gt;&nbsp;';
	$rightcolumn .= 'Results</a></li>';
	$rightcolumn .= '</ul>';


	$rightcolumn .= '</div>';
	$rightcolumn .= '</div> <!-- end RightColumn -->'.PHP_EOL;

	$page->addInlineScript($js);
	echo $page->outputHeader(2);
	echo '<div id="MainContent" class="clearfix">';
	echo $maincolumn;
	echo $rightcolumn;
	echo '</div> <!-- end MainContent -->'.PHP_EOL;
	echo $page->outputFooter();
?>
