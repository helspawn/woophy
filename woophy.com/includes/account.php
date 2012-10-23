<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}

	$access = ClassFactory::create('Access');
	if(!$access->isLoggedIn()) header('Location:'.ROOT_PATH.'login/?referer='.$_SERVER['REQUEST_URI']);
	
	include CLASS_PATH.'Page.class.php';
	include CLASS_PATH.'Photo.class.php';
	$blog = ClassFactory::create('Blog');
	$page = ClassFactory::create('Page');
	$user = ClassFactory::create('User');
	$user_id = (int)$access->getUserId();
	$xml_user = $user->getProfile();
	$user_name = $xml_user->name;

	$nav = array(
		array('label'=>'dashboard','path'=>'','inc'=>'dashboard.php'),
		array('path'=>'activated','inc'=>'actaccount.php','pages'=>array()),//no tab only for inclusion
		array('path'=>'upload','inc'=>'addphoto.php','pages'=>array()),//no tab only for inclusion
		array('label'=>'my photos','path'=>'photos','inc'=>'editphoto.php'),
		array('label'=>'my favorites','path'=>'favorites','pages'=>array(
			array('label'=>'my favorite photos','path'=>'favphotos','inc'=>'favphotos.php'),
			array('label'=>'my favorite photographers','path'=>'favmembers','inc'=>'favmembers.php')
		)),
		array('label'=>'my fans','path'=>'fans','inc'=>'fans.php'),
		array('label'=>'my comments','path'=>'comments','inc'=>'viewcomments.php'),
		array('label'=>'my blog','path'=>'blog','pages'=>array(
			array('label'=>'Add new post','path'=>'add?&action=add','inc'=>'editpost.php'),
			array('label'=>'Edit post','path'=>'edit?&action=edit','inc'=>'editpost.php')
		)),
		array('label'=>'my account','path'=>'profile','pages'=>array(
			array('label'=>'edit my profile','path'=>'edit','inc'=>'editprofile.php'),
			array('label'=>'change password','path'=>'passwd','inc'=>'editpasswd.php'),
			array('label'=>'delete account','path'=>'delete','inc'=>'delaccount.php')
		))
	);

	$param = explode('/', rtrim(REQUEST_PATH, '/'));
	$account_url = ROOT_PATH.$param[0].(substr($param[0], strlen($param[0])-1) == '/'?'':'/');

	foreach($param as $k=>$v)$param[$k] = Utils::stripQueryString($v);
	
	//resolve main nav:
	$currentnavId = NULL;
	foreach($nav as $k=>$v){		
		if(!isset($currentnavId) && isset($v['label'])) $currentnavId = $k;
		if(isset($param[1]) && $v['path'] == $param[1]) {
			$currentnavId = $k;
			break 1;
		}
	}
	//resolve sub nav
	if(isset($nav[$currentnavId]['pages'])){
		$pages = $nav[$currentnavId]['pages'];
		if(count($pages)>0){
			$currentsubnavId = NULL;
			foreach($pages as $k=>$v){
				if(!isset($currentsubnavId) && isset($v['label'])) $currentsubnavId = $k;
				if(isset($param[2]) && Utils::stripQueryString($v['path']) == $param[2]) {
					$currentsubnavId = $k;
					break 1;
				}
			}
		}
	}
	//inc and label based on nav:
	$label = 'My Woophy';
	if(isset($currentsubnavId)){
		if($el = $nav[$currentnavId]['pages'][$currentsubnavId]){
			$inc = $el['inc'];
			if(isset($el['label']))$label =$el['label'];
		}
	}else{
		if($el =$nav[$currentnavId]){
			$inc = $el['inc'];
			if(isset($el['label']))$label =$el['label'];
		}
	}
	//output:
	//header
	
	$page->setTitle($label);

	$html_account = '';//TRICKY: be sure this variables name is not used in any of the includes!
	
	//navigation
	$html_account .= '<div id="MainContent" class="clearfix"><div id="MainColumn">';
	
	$html_account .= '<div class="MenuBar clearfix">';
	$html_account .= '<div id="SubNav" class="clearfix"><ul class="clearfix">';

	foreach($nav as $k=>$v){
		if(isset($v['label'])){
			$class = 'inactive';
			if($v['path'] == $nav[$currentnavId]['path']) $class = 'active';
			$html_account .= '<li><a href="'.ROOT_PATH.'account/'.$v['path'].'" class="'.$class.'">'.$v['label'].'</a></li>';
		}
	}
	$html_account .= '</ul></div> <!--end SubNav -->'.PHP_EOL;
	
		//sub nav (if any):
	if(isset($pages) && count($pages)>0){
		$html_account .= '<div id="SubNav2" class="clearfix"><ul class="clearfix">'.PHP_EOL;
		foreach($pages as $k=>$v){
			if(isset($v['label'])){
				$class = 'inactive';
				if($v == $pages[$currentsubnavId]) $class = 'active';
				$html_account .= '<li><a href="'.ROOT_PATH.'account/'.$nav[$currentnavId]['path'].'/'.$v['path'].'" class="'.$class.'">'.$v['label'].'</a></li>';
			}
		}
		
		if($currentnavId==5){//blog
			$html_account .= '<li><a class="midgreen" href="'.ROOT_PATH.'member/'.urlencode($access->getUserName()).'/blog/">View blog</a></li>';
		}
		
		$html_account .= '</ul>';

		
		$html_account .= '</div>';
	}
	
	$html_account .= '</div> <!-- end MenuBar -->';
	
	if(isset($inc)){
		if($inc=='favphotos.php'){$page->addScript('gallery.js');	$page->addScript('photopage.js');}
		ob_start();
		include INCLUDE_PATH.$inc;
		$html_account .= ob_get_clean();
	}

	$html_account .= '</div> <!-- end MainColumn -->'.PHP_EOL;
	
	$html_account .= '<div id="RightColumn">'.PHP_EOL;

	$page->addScript('photocomments.js');
	$page->addInlineScript('jQuery(document).ready(function(){var pc = new PhotoComments({divObj:document.getElementById(\'PhotoComments\'),service_url:\''.ABSURL.'services?method=woophy.photo.getComments\',offset:0,limit:8,page_forward:document.getElementById(\'pc_page_forward\'),page_backward:document.getElementById(\'pc_page_backward\')});});');
	$html_account .= '<div class="Section"><div class="Header clearfix"><h2>Most Recent Comments</h2>';
	$html_account .= '<div class="Nav"><a class="PagingLeft sprite replace" id="pc_page_backward">&laquo;&nbsp;back</a><a class="PagingRight sprite replace" id="pc_page_forward">next&nbsp;&raquo;</a></div></div><div id="PhotoComments"></div>';

	$html_account .= '</div> <!-- end Section -->';
	$html_account .= '</div></div> <!-- end RightColumn, MainContent -->'.PHP_EOL;

	echo $page->outputHeader(2);
	echo $html_account;
	echo $page->outputFooter();
	
?>
