<?php
//$page_starttime = microtime(true);
date_default_timezone_set('Europe/Amsterdam');
require_once '../includes/config.php';
error_reporting(ERROR_REPORTING_LEVEL);
define('VALID_INCLUDE', true);

//require_once INCLUDE_PATH.'offline.php';
include_once CLASS_PATH.'DB.class.php';
include_once CLASS_PATH.'Utils.class.php';
include_once CLASS_PATH.'ClassFactory.class.php';

// TODO: REMOVE ON PRODUCTION!
//$debug = ClassFactory::create('Debug');

//Resolve path (mod_rewrite):
$a = explode('/', Utils::stripQueryString(REQUEST_PATH));

//TODO: make constants of the different sections (for use in pageHeader)
$s = mb_strtolower(Utils::stripQueryString(end($a)));

if(in_array($s,explode(',',SPECIAL_ACTIONS))) include INCLUDE_PATH.$s.'.php';
switch(mb_strtolower(Utils::stripQueryString(reset($a)))){
	case 'services':
		include INCLUDE_PATH.'services.php';
		break;
	case 'photopopup':
		include INCLUDE_PATH.'photopopup.php';
		break;
	case '':
	case 'map':
	case 'ecard':
	case 'index.php':
	case 'index.html':
		include INCLUDE_PATH.'map.php';
		break;
	case 'search'://backward compatibility
	case 'photo':
		include INCLUDE_PATH.'photo.php';
		break;
	case 'forum':
		include FORUM_PATH.'index.php';
		break;
	case 'download':
		include INCLUDE_PATH.'download.php';
		break;
	case 'account':
		include INCLUDE_PATH.'account.php';
		break;
	case 'member':
		include INCLUDE_PATH.'member.php';
		break;
	case 'upload':
		include INCLUDE_PATH.'upload.php';
		break;
	case 'news':	
		include INCLUDE_PATH.'news.php';
		break;
	case 'feeds':
		include INCLUDE_PATH.'feeds.php';
		break;
	case 'viewpost':
		include INCLUDE_PATH.'viewpost.php';
		break;
	case 'previewpost':
		include INCLUDE_PATH.'previewpost.php';
		break;
	case 'donate':
	case 'sponsor':
	case 'about':
	case 'contact':
	case 'termsofuse':
	case 'faq':
	case 'press':
	case 'advertising':
		include INCLUDE_PATH.'docs.php';
		break;
	case 'report':
		include INCLUDE_PATH.'report.php';
		break;
	case 'country':
	case 'city':
		include INCLUDE_PATH.'location.php';
		break;
	case 'contest':
		include INCLUDE_PATH.'contest.php';
		break;
	case 'forum_admin':
		include FORUM_PATH.'bb_admin.php';
		break;
	default:
		include INCLUDE_PATH.'404.php';
}

//TODO: REMOVE FOR PRODUCTION
//echo $debug->show_benchmarks();
//echo '<div>TOTAL PAGE LOAD TIME: '. number_format(microtime(true) - $page_starttime, 3) . ' secs</div>';
