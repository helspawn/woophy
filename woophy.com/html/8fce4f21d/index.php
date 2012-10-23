<?php
	session_start();
	
	require_once '../../includes/config.php';
	require_once CLASS_PATH.'DB.class.php';
	require_once CLASS_PATH.'Utils.class.php';

	
	$nav = array(
		'Member'=>array(
			'latest members'=>'latest_members',
			'look up member'=>'member',
			'add award'=>'add_award',
			'delete award'=>'delete_award',
			'delete rating'=>'delete_rating',
			'delete photo comments'=>'delete_comments',
			'delete blog comments'=>'delete_blogcomments',
			'delete account'=>'delete_account',
			'(un)lock account'=>'lock_account',
			'(un)notify comments'=>'notify_comments',
			'reset member camera'=>'reset_usercamera'
			//'view locked accounts'=>'locked_accounts'
			),
		'Photo'=>array(
			'edit photo'=>'edit_photo',
			'move photo to trash'=>'trash_photo',
			'delete rating'=>'delete_rating_photo'),
		'Newsletter'=>array(
			'compose newsletter'=>'compose_news',
			'preview newsletter'=>'setup_news',
			'unsubscribe member'=>'unsubscribe',
			'export email addresses'=>'export_newsletter_emails'
		),
		'Editor'=>array(
			'add editor'=>'add_editor',
			'delete editor'=>'delete_editor'
		),
		'Ambassador'=>array(
			'add ambassador'=>'add_ambassador',
			'delete ambassador'=>'delete_ambassador'
		),
		'Blog'=>array(
			'add blog permission'=>'add_blog2user',
			'delete blog permission'=>'delete_blog2user',
			'delete blog comments'=>'delete_blogcomments_blog',
			'add travelblog'=>'add_travelblog',
			'delete blogpost'=>'delete_blogpost'
		),
		//use openX to deliver ads
		/*'Advertisement'=>array(
			'add advertisement'=>'add_ad',
			'delete advertisement'=>'delete_ad',
			'reset ad cache'=>'reset_ad_cache'
		),*/
		'Misc'=>array(
			'statistics'=>'stats',
			'view deleted accounts'=>'deleted_accounts',
			'clean up database'=>'clean_up',
			'tip of the day'=>'edit_totd',
			'reset city photo count'=>'reset_city_photo_count',
			'reset user photo count'=>'reset_user_photo_count',
			'reset city cache'=>'reset_city_cache',
			'add city'=>'add_city',
			'search engine optimization'=>'seo',
			'memcache statistics'=>'memcache_stats',
			'compress javascript'=>'compress_js')
	);
	//Resolve path (mod_rewrite):
	$a = explode('/', REQUEST_PATH);
	if(count($a)>1)$include = mb_strtolower(Utils::stripQueryString(end($a)));
	else $include = 'latest_members';

	DB::connect();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<title>Woophy - Admin</title>
<link href="<?php echo ROOT_PATH?>css/core.css" rel="stylesheet" type="text/css" />
<link href="<?php echo ROOT_PATH?>css/admin.css" rel="stylesheet" type="text/css" />
<link href="<?php echo ROOT_PATH?>css/datefield.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH?>js/dateselector.js"></script>
<script type="text/javascript">//<![CDATA[

	jQuery.noConflict();

	var symbol_expand = "+&nbsp;";
	var symbol_collapse = "–&nbsp;";
	function expand(evt){
		var el = evt.currentTarget;
		var p = el.parentNode;
		var l = p.getElementsByTagName('ul');
		if(l.length == 1){
			if(l[0].style.display == 'none'){//expand
				el.innerHTML = el.innerHTML.replace(symbol_expand, symbol_collapse);
				l[0].style.display = 'block';
			}else{//collapse
				el.innerHTML = el.innerHTML.replace(symbol_collapse, symbol_expand);
				l[0].style.display = 'none';
			}
		}
		return false;
	}
	
	function checkUncheckAll(frm){
		var e = document[frm].elements;
		var n = e.length;
		for(i=0; i<n; i++){
			if(e[i].type == 'checkbox'){
				e[i].checked = !e[i].checked;
			}
		}
    }
	jQuery(document).ready(function(){
		var leg = document.getElementsByTagName('legend');
		var i=leg.length;
		var symbol;
		while(i--){
			var l = leg[i].parentNode.getElementsByTagName('ul');
			if(l.length == 1){
				if(l[0].style.display != 'block'){
					l[0].style.display = 'none';
					symbol = symbol_expand;
				}else symbol = symbol_collapse;
			}else continue;
			leg[i].onclick = expand;
			leg[i].innerHTML = symbol + leg[i].innerHTML;
		}
	});
//]]></script>
</head>
<body>
<div class="header dotted"><img src="<?php echo ROOT_PATH?>images/woophy_logo_dark.gif" width="214" height="80" alt="Woophy Logo" /></div>
<div id="navigation">
<?php
	foreach($nav as $legend=>$item){
		if(count($item)==0)continue;
		$expand = false;
		$list = '';
		echo '<fieldset><legend>'.$legend.'</legend>';
		foreach($item as $title=>$inc){
			if($inc==$include) $expand = true;
			$list .= '<li><a '.($inc==$include?'class="active" ':'').'href="'.$inc.'">'.$title.'</a></li>';
		}
		if($expand) echo '<ul style="display:block">';
		else echo '<ul>';
		echo $list.'</ul></fieldset>';
	}
?>
</div>
<div id="content"><?php
	if(isset($include)){
		if(file_exists('includes/'.$include.'.php'))include 'includes/'.$include.'.php';
	}
?></div>

</body>
</html>
<?php	
	DB::close();
?>
