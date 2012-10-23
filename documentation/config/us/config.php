<?php
	//::Begin change next constants in different env::

/*
	define('MYSQL_HOST', 'localhost');
	define('MYSQL_USER', 'root');
	define('MYSQL_PASSWORD', 'W00phy!');
	define('MYSQL_DBASE', 'woophy1');	
*/

//$db_connection = 'test';
$db_connection = 'staging';
//$db_connection = 'production';

switch($db_connection){
	case 'test':
		$db_host 		= 'v3.db.woophy.com';
		$db_name 		= 'woophy_site2';
		$db_user		= 'helspawn99';
		$pb_pass		= 'katja99';
		$db_slave_host 	= 'ec2-23-23-203-188.compute-1.amazonaws.com'; // this is the qa master db server
		$db_slave_name 	= 'woophy_site2';
		$db_slave_user	= 'helspawn99';
		$pb_slave_pass	= 'katja99';
		$aws_bucket		= 'woophy.production';
		break;
	case 'beta':
	case 'staging':
		$db_host 		= 'v3.db.woophy.com';
		$db_name 		= 'woophy_staging';
		$db_user		= 'helspawn99';
		$pb_pass		= 'katja99';
		$db_slave_host 	= 'ec2-184-72-198-84.compute-1.amazonaws.com'; // this is the qa master db server
		$db_slave_name 	= 'woophy_staging';
		$db_slave_user	= 'helspawn99';
		$pb_slave_pass	= 'katja99';
		$aws_bucket		= 'woophyv3.prod';
		break;
	case 'production':
		$db_host 		= 'v3.db.woophy.com';
		$db_name 		= 'woophy_site2';
		$db_user		= 'helspawn99';
		$pb_pass		= 'katja99';
		$db_slave_host 	= 'ec2-23-23-203-188.compute-1.amazonaws.com'; // this is the qa master db server
		$db_slave_name 	= 'woophy_site2';
		$db_slave_user	= 'helspawn99';
		$pb_slave_pass	= 'katja99';
		$aws_bucket		= 'woophy.production';
		break;
	case 'production2':
		$db_host 		= 'ec2-23-20-86-166.compute-1.amazonaws.com'; // the test database
		$db_name 		= 'woophy_site2';
		$db_user		= 'helspawn99';
		$pb_pass		= 'katja99';
		$db_slave_host 	= 'ec2-23-23-203-188.compute-1.amazonaws.com'; // this is the qa master db server
		$db_slave_name 	= 'woophy_site2';
		$db_slave_user	= 'helspawn99';
		$pb_slave_pass	= 'katja99';
		$aws_bucket		= 'woophy.production';
		break;
	default:
		$db_host 		= 'ec2-23-23-203-188.compute-1.amazonaws.com'; // this is the qa master db server
		$db_name 		= 'woophy_site2';
		$db_user		= 'helspawn99';
		$pb_pass		= 'katja99';
		$db_slave_host 	= 'ec2-23-23-203-188.compute-1.amazonaws.com'; // this is the qa master db server
		$db_slave_name 	= 'woophy_site2';
		$db_slave_user	= 'helspawn99';
		$pb_slave_pass	= 'katja99';
		$aws_bucket		= 'woophy.production';
		break;
}
 
	define('MYSQL_HOST', 						$db_host);
	define('MYSQL_DBASE', 						$db_name);	
	define('AWS_BUCKET', 						$aws_bucket);
	define('MYSQL_USER', 						$db_user);
	define('MYSQL_PASSWORD', 					$pb_pass);

	define('MYSQL_SLAVE_HOST', 					$db_slave_host);
	define('MYSQL_SLAVE_DBASE', 				$db_slave_name);
	define('MYSQL_SLAVE_USER', 					$db_slave_user);
	define('MYSQL_SLAVE_PASSWORD', 				$pb_slave_pass);
		
	//
	// AWS S3 login info
	//
	define('AWS_S3_PUBLIC_KEY', 				'AKIAIQPUSDGPVV6FIMAQ');
	define('AWS_S3_PRIVATE_KEY', 				'4hgV6ku7mnulS/3VU/3Tw8OxVA2zRATZsePyo8hc');       
	define('ERROR_REPORTING_LEVEL',				E_ALL);//0 in production, E_ALL in development!!
	
	define('ROOT_URL', 							((@$_SERVER['HTTPS']!='')?'https':'http'). '://'. $_SERVER['HTTP_HOST'] . '/');
	define('ROOT_PATH',							'/');
	
//::End change::

	define('MEMCACHE_HOST', 					'localhost');
	define('MEMCACHE_PORT', 					11211);
	define('ABSPATH', 							realpath(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR);
	define('ABSURL',							'http://' . str_replace('//','/',$_SERVER['HTTP_HOST'].ROOT_PATH));

	define('LOGS_PATH', 						ABSPATH.'logs'.DIRECTORY_SEPARATOR);
	define('IMAGES_PATH', 						ABSPATH.'html'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR);
	define('IMAGES_URL', 						ABSURL.'images/');
	define('BLOGS_URL', 						IMAGES_URL.'blogs/');
	define('BLOGS_PATH', 						IMAGES_PATH.'blogs'.DIRECTORY_SEPARATOR);
	define('TRAVELBLOGS_URL', 					IMAGES_URL.'travelblogs/');
	define('TRAVELBLOGS_PATH', 					IMAGES_PATH.'travelblogs'.DIRECTORY_SEPARATOR);

	define('WOOPHY_LOGO_URL', 					IMAGES_URL.'press_kit/woophy_logo_green.gif');
	define('WOOPHY_TAGLINE',					'Join Woophy, the online travel and photography community.');
	define('INCLUDE_PATH', 						ABSPATH.'includes'.DIRECTORY_SEPARATOR);
	define('CLASS_PATH', 						ABSPATH.'classes'.DIRECTORY_SEPARATOR);
	define('FORUM_URL', 						ABSURL.'forum/');
	define('FORUM_PATH', 						ABSPATH.'forum'.DIRECTORY_SEPARATOR);
	define('TEMPLATE_PATH', 					ABSPATH.'templates'.DIRECTORY_SEPARATOR);
	define('MAX_PATH', 							ABSPATH.'html'.DIRECTORY_SEPARATOR.'openx');//openx, TRICKY: no trailing slash
	define('VIDEO_PATH', 						ABSPATH.'html'.DIRECTORY_SEPARATOR.'flv'.DIRECTORY_SEPARATOR);
	define('VIDEO_URL', 						ROOT_PATH.'flv/');
	define('REQUEST_PATH', 						trim(str_replace(rtrim(ROOT_PATH,'/'), '', $_SERVER['REQUEST_URI']),'/'));
	define('ADMIN_PATH', 						ABSPATH.'html'.DIRECTORY_SEPARATOR . '8fce4f21d' . DIRECTORY_SEPARATOR);
		
	// photos is set to CDN
	define('PHOTOS_URL', 						ROOT_PATH.'photos/');

	// thumbs currently pulled directly from S3
	define('THUMBNAIL_URL', 					ROOT_PATH.'photos/');
	define('AVATARS_URL', 						'http://woophyv3.prod.s3.amazonaws.com/images/avatars/');
	
	// photos path same as url in thuis scheme
	define('PHOTOS_PATH', 						ROOT_PATH.'/photos/');
	define('PHOTOS_RELATIVE_PATH', 				'images/photos/');
	define('AVATARS_RELATIVE_PATH', 			'images/avatars/');
	define('AVATARS_PATH', 						'http://woophyv3.prod.s3.amazonaws.com/images/avatars/');

	define('MAX_AVATAR_WIDTH', 					80);
	define('MAX_AVATAR_HEIGHT', 				240);
	define('MAX_THUMB_WIDTH', 					100);
	define('MAX_THUMB_HEIGHT', 					100);
	define('MAX_PHOTO_WIDTH_PORTRAIT', 			640);
	define('MAX_PHOTO_HEIGHT_PORTRAIT',			480);
	define('MAX_PHOTO_WIDTH_LANDSCAPE', 		420);
	define('MAX_PHOTO_HEIGHT_LANDSCAPE',		480);
	define('MAX_PHOTO_ORG_WIDTH', 				1280);
	define('MAX_PHOTO_ORG_HEIGHT', 				1280);
	define('MAX_PANORAMA_WIDTH', 				2560);
	define('MAX_PANORAMA_HEIGHT', 				2560);

 	/* NEW PHOTO SIZES FOR VERSION 3 */
	define('MAX_PHOTO_WIDTH_THUMB',				68);
	define('MAX_PHOTO_HEIGHT_THUMB',			68);
	define('MAX_PHOTO_WIDTH_SMALL',				210);
	define('MAX_PHOTO_HEIGHT_SMALL',			157);
	define('MAX_PHOTO_WIDTH_MEDIUM',			288);
	define('MAX_PHOTO_HEIGHT_MEDIUM',			216);
	define('MAX_PHOTO_WIDTH_LARGE',				640);
	define('MAX_PHOTO_HEIGHT_LARGE',			480);
	define('MAX_PHOTO_HEIGHT_FULL',				1280);
	define('MAX_PHOTO_WIDTH_FULL',				1280);

	define('FOLDER_NAME_THUMB',					'thumb');
	define('FOLDER_NAME_SMALL',					'small');
	define('FOLDER_NAME_MEDIUM',				'medium');
	define('FOLDER_NAME_LARGE',					'large');
	define('FOLDER_NAME_FULL',					'full');
	define('FOLDER_NAME_ORIGINAL',				'l');

	define('MIN_NUM_VOTERS', 					3); //a photo has to got more than 3 votes to count for the camera
	define('MIN_NUM_PHOTOS_AWARD', 				10); //you got to have more than 10 photos to get a bronze, silver, gold camera
	define('MAP_DENSITY', 						20); //distance between dots on map, the higher the value the less dot are displayed
	define('GLOBE_WIDTH', 						256);//google constant
	define('MAX_LATITUDE', 						85.0511);//google constant
	define('MAX_ZOOMLEVEL', 					10);
	define('MIN_ZOOMLEVEL', 					1);//TRICKY, use value > 0

	define('RESERVED_USERNAMES', 				'admin,administrator,user,member,root,woophy,e-mail,email'); //lowercase!
	
	define('SPECIAL_ACTIONS', 					'login,register,forgotpasswd,logout,activate'); //lowercase!
	define('TAB', 								"\t");

	define('INFO_EMAIL_ADDRESS', 				'info@woophy.com');
	define('SUPPORT_EMAIL_ADDRESS', 			'support@woophy.com');
	define('NOREPLY_EMAIL_ADDRESS', 			'support@woophy.com');
	define('EMAIL_SENDER', 						'Woophy');

	define('OPENX_PHOTOPOPUP', 					10); //zone ids
	define('OPENX_HOME', 						11);
	define('OPENX_SKYSCRAPER', 					12);
	define('OPENX_CONTEXT', 					12);
	define('OPENX_TOP', 						12);
	define('OPENX_SIDEBAR', 					12);

	define('AWARDS', 							'Member of the Month,T-shirt,Contest Overall,Contest First Prize,Contest Second Prize,Contest Honourable Mention,Unofficial Contest,Woophy Staff,I support Woophy');

	define('CONTEST_JURY', 						'1,134,6,9,19397,45262'); //user ids

	// Bitwise values for feed widget content 
	define('NOTIFICATION_PHOTO_COMMENTS', 		1);
	define('NOTIFICATION_PHOTO_FAVORITES',		2);
	define('NOTIFICATION_FAVORITE_SUBMISSIONS', 4);
	define('NOTIFICATION_FANS', 				8);
	define('NOTIFICATION_ALL', 					15);

	// Bitwise values for displaying/hiding content & functionality based on user status &  privileges
	define('VISIBILITY_LOGGED_OUT', 			1);
	define('VISIBILITY_LOGGED_IN', 				2);
	define('VISIBILITY_ADMIN', 					4);

	define('DISABLE_ADS', 						true); //disable ad components on dev environment
	define('ENABLE_DEBUGGING', 					true); //enable debugging on dev environment

?>
