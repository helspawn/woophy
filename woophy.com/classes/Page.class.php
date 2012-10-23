<?php
//outputs html header and footer
//TODO: put html into a template file somehow

class Page{

	private $header;
	private $footer;
	private $title;
	private $doctype;
	private $activetab;
	private $tablabels;
	private $taburls;
	private $loginEnabled;
	private $script;
	private $style;
	private $scripts;
	private $styles;
	private $meta_tags;
	private $section;
	private $viewmode;

	public function __construct(){
		$this->access = ClassFactory::create('Access');
		$this->loginEnabled = true;//default true
		$this->title = 'Woophy';//default title
		$this->viewmode = 0;//default viewmode: full HTML
		$this->page_image = WOOPHY_LOGO_URL; //default image
		$this->script = '';
		$this->style = '';
		$this->scripts = array();
		$this->styles = array();
		$this->meta_tags = array();
		$this->tablabels = array('home','photos','members', 'contest', 'news','forum');
		$this->tabpaths = array('','photo', 'member', 'contest', 'news','forum');
		if($this->access->isLoggedIn()){
			array_push($this->tablabels, 'my woophy');
			array_push($this->tabpaths,'account');
		}
		$this->setSection();
		
		//viewmode parameter reference: 0 = standalone display (not as popup); 1 = popup display (including prev/next paging); 2 = Only HTML to load new content in existing popup
		$viewmode = (isset($_GET['viewmode'])?(int)$_GET['viewmode']:0);
		$this->setViewmode($viewmode);

		$this->setActivetab();//resolve path
		$this->setDocType('','html');//HTML5 doctype
		$this->header = '<html xmlns="http://www.w3.org/1999/xhtml" xmlns:og="http://ogp.me/ns#" xmlns:fb="http://www.facebook.com/2008/fbml">'.PHP_EOL;
		$this->header .= '<head>'.PHP_EOL;

		$this->addMeta('content-type','text/html;charset=utf-8','http-equiv');
		$this->addMeta('content-language', 'en','http-equiv');

		$this->addMeta('og:url', ABSURL.REQUEST_PATH, 'property');
		$this->addMeta('og:type','website', 'property');
		$this->addMeta('og:site_name','Woophy', 'property');

		/*
		THESE WILL BE FILLED ONCE THE OFFICIAL WOOPHY FACEBOOK PAGE HAS BEEN CREATED
		$this->addMeta('fb:page_id', '', 'property');
		$this->addMeta('fb:admins', '', 'property');
		$this->addMeta('fb:app_id', '', 'property');
		*/
 		$this->addMeta('robots', 'all, index, follow');
		$this->addMeta('verify-v1', 'XAhuN7RhpCSZjSWA6aWLfg8dHcKXKTtv6HOO326+yhc=');

		/** Skip reloading in Stylesheets Javascripts when a page is called through AJAX */ 
		if($this->viewmode == 0){
			$this->addStyle('core.css?1.02');//default stylesheet
			//$this->addStyle('lights-out.css');
			$this->addStyle('ie.css','IE');//IE only
			$this->addStyle('ie7.css','IE 7');//IE 7 only

			$this->addScript('https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js', TRUE);
			$this->addScript('http://maps.google.com/maps/api/js?sensor=false', TRUE);

			$this->addScript('lib.packed.js?1.02');

			/*
			//library_packed consists of the following js files:
			$this->addScript('global.js');
			$this->addScript('jquery.colorbox.js');
			$this->addScript('map.js');*/
			
			
			//$this->addInlineScript('jQuery.noConflict();');
			$this->addInlineScript('Page.root_url=\'' . ROOT_PATH . '\'; jQuery(\'<link href="\'+Page.root_url+\'css/jsonly.css" rel="stylesheet" type="text/css" />\').appendTo(\'head\');'.PHP_EOL);
			$this->addInlineScript('var ados = ados || {};');
		}

	}
	public function enableLogin($bln = true){
		$this->loginEnabled = $bln;
	}
	public function outputHeaderSimple(){
		//header without navigation/login
		$this->header .= '<title>'.$this->title.'</title>';
		$this->putMeta();
		$this->putStyle();
		$this->putInlineStyle();
		$this->putScript();
		$this->putInlineScript();
		$this->header = $this->doctype.PHP_EOL.$this->header;
		$this->header .= '</head>'.PHP_EOL;
		$this->header .= '<body id="'.ucfirst($this->section).'Page" class="Simple">';
		return $this->header;
	}
	public function outputHeader($pageColumns=1){
		$class = '';
		if($pageColumns==2)$class = ' class="TwoColumn"';

		//head section
		$this->header .= '<title>'.$this->title.'</title>'.PHP_EOL;
		$this->addMeta('description', WOOPHY_TAGLINE);
		$this->addMeta('keywords', 'woophy, photos, world, travel, blog, map');
		$this->addMeta('og:title',$this->title, 'property');
		$this->addMeta('og:image',$this->page_image, 'property');

		$this->putMeta();
		$this->putStyle();
		$this->putInlineStyle();
		$this->putScript();
		$this->putInlineScript();
		$this->header .= '<link href="'.ROOT_PATH.'favicon.ico" rel="shortcut icon" type="image/x-icon">';
		$this->header = $this->doctype.PHP_EOL.$this->header;
		$this->header .= '</head>'.PHP_EOL;
		$this->header .= '<body id="'. ucfirst($this->section) .'Page"'.$class.'>';
		$this->header .= '<noscript><div>Your web browser does not have JavaScript enabled. Many features on Woophy.com will not work unless you enable Javascript.</div></noscript>';
		$this->header .= '<div id="HeaderContainer">';
		$this->header .= '<div id="Header">';
		$this->header .= '<div id="HeaderTop" class="clearfix">';
		$this->header .= '<a id="Logo" title="Woophy" href="'.ROOT_PATH.'"><img alt="Woophy" class="logo" src="'.ROOT_PATH.'images/sprite.png" /></a>';
		
		//Top Links
		$this->header .= '<ul id="TopLinks">';
		if($this->access->isLoggedIn()) $this->header .= '<li class="clearfix"><a class="UserLink floatleft" href="'.ROOT_PATH.'member/'.urlencode($this->access->getUserName()).'">Hi '.$this->access->getUserName().'</a><a class="TinyThumb floatleft sprite" href="'.ROOT_PATH.'member/'.urlencode($this->access->getUserName()).'"><img src="'.AVATARS_URL.$this->access->getUserId().'.jpg" onerror="this.style.display=\'none\'" /></a></li><li class="sprite"><a href="'.ROOT_PATH.'Logout">sign out</a></li>';
		else {
			if($this->loginEnabled)$this->header .= '<li><a class="LoginButton sprite" href="' . ROOT_PATH . 'login"><span>login</span></a></li>';
			//$this->header .= '<li><a href="'.ROOT_PATH.'Register">register</a></li>';

 		}
		$this->header .= '<li class="sprite"><a href="' . ROOT_PATH . 'about">about woophy</a></li>';
		$this->header .= '<li class="sprite"><a href="' . ROOT_PATH . 'donate">donate</a></li>';
		$this->header .= '<li class="sprite"><a href="' . ROOT_PATH . 'contact">contact us</a></li>';
		$this->header .= '<li class="sprite"><a href="' . ROOT_PATH . 'advertising">advertisers</a></li>';
		$this->header .= '<li class="ActionButton sprite" id="FacebookButtonTop"><a class="sprite replace" href="http://www.facebook.com/sharer.php?u='.urlencode(ABSURL.REQUEST_PATH).'&t='.urlencode($this->title) .'" target="_blank">Facebook</a></li>';
		$this->header .= '<li class="ActionButton sprite" id="TwitterButtonTop"><a class="sprite replace" href="http://twitter.com/#!/woophy" target="_blank">Twitter</a></li>';
		$this->header .= '</ul>'; // end Top Links
		$this->header .= '</div>'; //end Header Top
		
		$this->header .= '<div id="HeaderBottom" class="clearfix">';
		//Main Nav section
		$this->header .= '<div id="MainNav">';
		$this->header .= '<ul>';

		foreach($this->tablabels as $k=>$v){
			$class = ($this->activetab==$k) ? 'active' : 'inactive';
			$this->header .= '<li><a href="'.ROOT_PATH.$this->tabpaths[$k].'" class="'.$class.'">'.$v.'</a></li>';
		}
		$this->header .= '</ul>';
		$this->header .= '</div>'; // closing Main Nav

		$this->header .= '<div class="UploadButton GreenButton"><a href="'. ROOT_PATH .'account/upload" class="sprite"><span>Upload photos</span></a></div>';
		$this->header .= '</div>'; // closing Header Bottom
		$this->header .= '</div></div>';//closing header, header gradient, header container

		$this->header .= '<div id="MainContainer">'.PHP_EOL;

		return $this->header;
	}

	public function outputFooterSimple(){//without links
		$this->footer = '';
		//IE proof way to hide broken images:
		$this->footer .= '<script type="text/javascript">jQuery(\'img\').error(function(){jQuery(this).css(\'visibility\',\'hidden\').parent().removeClass(\'ImageContainer\');});</script></body></html>';
		return $this->footer;
	}
	public function outputFooter(){
		$footer = $this->outputFooterSimple();
		$this->footer = PHP_EOL.'</div> <!-- end MainContainer -->';
		$this->footer .= '<div id="FooterContainer">';
		$this->footer .= '<div id="Footer"><ul id="FooterText">';
		$this->footer .= '<li>&copy; 2004-'.date('Y').' woophy</li>';
		$this->footer .= '<li><a href="'.ROOT_PATH.'about">about</a></li>';
		$this->footer .= '<li><a href="'.ROOT_PATH.'contact">contact</a></li>';
		$this->footer .= '<li><a href="'.ROOT_PATH.'termsofuse">terms of use</a></li>';
		$this->footer .= '<li><a href="'.ROOT_PATH.'faq">faq</a></li>';
		$this->footer .= '<li><a href="'.ROOT_PATH.'advertising">advertising</a></li>';
		$this->footer .= '<li><a href="'.ROOT_PATH.'donate">donate</a></li>';
		$this->footer .= '<li><a href="'.ROOT_PATH.'press">press</a></li>';
		$this->footer .= '</ul></div></div> <!-- end Footer, FooterContainer -->';

		$this->footer .= $footer;
		return $this->footer;
	}
	public function setActiveTab($idx = NULL){
		if(array_key_exists($idx, $this->tabpaths)) $this->activetab = $idx;
		else if(mb_strlen(REQUEST_PATH)==0) $this->activetab = 0;//default active tab
		else{
			$idx = FALSE;
			//try current url
			$idx = $this->resolvePath(REQUEST_PATH);
			//try referer
			//if(!is_numeric($idx) && isset($_SERVER['HTTP_REFERER']))$idx = $this->resolvePath(str_replace(ABSURL,'',$_SERVER['HTTP_REFERER']));
			$this->activetab = is_numeric($idx) ? $idx : -1;
		}
	}
	public function addScript($docname=NULL, $external=FALSE, $type=NULL){
		if(isset($docname)){
			if($external) $scriptsrc = $docname;
			else $scriptsrc = ROOT_PATH.'js/'.$docname;
			$type = isset($type) ? $type : 'text/javascript';
			$script_inc = array('src'=>$scriptsrc,'type'=>$type);
			if(!in_array($script_inc, $this->scripts)){
				$this->scripts[] = $script_inc;
				return true;
			}
		}
		return false;
	}
	private function putScript(){
		foreach($this->scripts as $script){
			$this->header .= '<script type="'.$script['type'].'" src="'.$script['src'].'"></script>';
		}
	}
	public function addStyle($docname=NULL, $iefilter=''){
		if(isset($docname)){
			$style_inc = array('href'=>ROOT_PATH.'css/'.$docname,'iefilter'=>$iefilter);
			if(!in_array($style_inc, $this->styles)){
				$this->styles[] = $style_inc;
				return true;
			}
		}
		return false;
	}
	public function putStyle(){
		foreach($this->styles as $style){
//			print_r($style);
//			echo '<br>';
			if($style['iefilter'] != '') $this->header .= '<!--[if '. $style['iefilter'] .']>';
			$this->header .= '<link href="'. $style['href'].'" rel="stylesheet" type="text/css" />';
			if($style['iefilter'] != '') $this->header .= '<![endif]-->';
		}
	}

	public function addInlineScript($text=NULL){
		if(isset($text) && mb_strlen($text)>0){
			$this->script .= $text;
			return true;
		}
		return false;
	}
	public function addInlineStyle($text=NULL){
		if(isset($text) && mb_strlen($text)>0){
			$this->style .= $text;
			return true;
		}
		return false;
	}
	public function addRSS($href=NULL, $title=''){
		if(isset($rel, $href, $type)){
			$this->header .= '<link href="'. $href.'" rel="alternate" type="application/rss+xml" title="'.$title.'"/>';
			return true;
		}
		return false;
	}
	public function addMeta($name=NULL, $value=NULL, $type='name'){
		//type: name or http-equiv
		if(isset($name, $value)){
			// only one value of any specific meta tag allowed. overwrite any existing value
			if(key_exists($name, $this->meta_tags))unset($this->meta_tags[$name]);
			$this->meta_tags[$name] = array('value'=>$value, 'type'=>$type);
			return true;
		}
		return false;
	}
	private function putMeta(){
		foreach($this->meta_tags as $name=>$tag){
			$this->header .= '<meta '.$tag['type'].'="'.$name.'" content="'.$tag['value'].'" />';		
		}
	}
	public function getTitle(){
		return $this->title;
	}
	public function setTitle($title=NULL){
		if(isset($title)) $this->title .= ' - '.$title;
	}
	public function setPageImage($image=NULL){
		if(isset($image)) $this->page_image = $image;
	}
	public function setSection($section=NULL){
		if($section == NULL){
			$a = explode('/', REQUEST_PATH);
			$this->section = mb_strtolower(Utils::stripQueryString(reset($a)));
			if($this->section == '')$this->section = 'home';
		}else{
			$this->section = $section;
		}

	}
	public function getSection(){
		return $this->section;
	}
	
	public function setViewmode($viewmode=0){
		$this->viewmode = $viewmode;
	}

	public function getViewmode(){
		return $this->viewmode;
	}
	
	public function setDocType($name='-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/transitional.dtd', $type='HTML', $publicId=NULL, $uri=NULL){
		if(isset($name,$type)){
			$this->doctype = '<!DOCTYPE '.$type;
			if(isset($publicId) && $publicId != NULL) $this->doctype .= ' '.$publicId.' "'.$name.'"';
			if(isset($uri)) $this->doctype .= ' "'.$uri.'"';
			$this->doctype .= '>';
		}else $this->doctype = '';//omit doctype
		return true;
	}
	private function putInlineScript(){
		//add google analytics after every other script 
		if($this->viewmode==0) $this->script .= 'var _gaq = _gaq || [];_gaq.push([\'_setAccount\', \'UA-31068333-1\']);_gaq.push([\'_trackPageview\']);(function() {var ga = document.createElement(\'script\'); ga.type = \'text/javascript\'; ga.async = true; ga.src = (\'https:\' == document.location.protocol ? \'https://ssl\' : \'http://www\') + \'.google-analytics.com/ga.js\'; var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(ga, s);})();';
		
		if(mb_strlen($this->script)>0)$this->header .= '<script type="text/javascript">//<![CDATA['.PHP_EOL.$this->script.PHP_EOL.'//]]></script>';
	}
	private function putInlineStyle(){
		if(mb_strlen($this->style)>0)$this->header .= '<style type="text/css">/*<![CDATA[*/'.PHP_EOL.$this->style.PHP_EOL.'/*]]>*/</style>';
	}
	private function resolvePath($path){
		$sections = explode('/',rtrim(Utils::stripQueryString($path),'/'));
		return array_search(mb_strtolower($sections[0]),$this->tabpaths);
	}
}
?>
