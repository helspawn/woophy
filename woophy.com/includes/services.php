<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}
	//EXAMPLE:	http://www.woophy.com/services?&method=woophy.blog.getPhotosByUserId&user_id=1
	
	$output_mode = isset($_GET['output_mode'])?strtolower($_GET['output_mode']):'rest';
	switch($output_mode){
		case 'html':
			header('Content-Type: text/html; charset=utf-8');
			break;
		case 'json':
			header('Content-Type: application/json');
			break;
		default:
			header('Content-Type: text/xml; charset=utf-8');
	}


	$API = array(
		'blog'=>array('class'=>'Blog','methods'=>array(
			'getArchiveByCategoryId'=>array('category_id','offset','limit'),
			'getArchiveByUserId'=>array('user_id','offset','limit'),
			'getPhotosByUserId'=>array('user_id','offset','limit')
		)),
		'travelblog'=>array('class'=>'TravelBlog','methods'=>array(
			'getArchiveByBlogId'=>array('travelblog_id','offset','limit')
		)),
		'city'=>array('class'=>'City','methods'=>array(
			'getCities'=>array('city_name','country_code','limit')
		)),
		'photo'=>array('class'=>'Photo','methods'=>array(
			'getRecent'=>array('limit','offset','offset_id','user_id','output_mode'),
			'getInfo'=>array('photo_id'),
			'addToFavorites'=>array('photo_id'),
			'addEditorsPick'=>array('photo_id'),
			'removeEditorsPick'=>array('photo_id'),
			'getEditorsPicks'=>array('city_name','country_code','limit'),
			'addRating'=>array('photo_id','value'),
			'getUrl'=>array('photo_id','size'),
			'getComments'=>array('offset', 'limit', 'total')
		)),
		'user'=>array('class'=>'User','methods'=>array(
			'addToFavorites'=>array('user_id'),
			'getUsersByName'=>array('user_name'),
			'sendMessage'=>array('user_id', 'from_email', 'subject', 'message', 'xhr'),
			'getBirthdayUsers'=>array('offset', 'limit'),
		)),
		'uploadprogress'=>array('class'=>'UploadProgress','methods'=>array(
			'getStatus'=>array('key')
		)),
		//added:24.04.2012
		'map'=>array('class'=>'Map','methods'=>array(
			'getCities'=>array('key','val','output_mode'),
			'getCitiesByArea'=>array('x1','y1','x2','y2','zoomlevel','output_mode'),
			'getCitiesCache'=>array('zoomlevel','output_mode'),
			'getCitiesByTravelBlogId'=>array('travelblog_id','output_mode'),
			'getPhotos'=>array('key','val', 'offset', 'limit','output_mode')
		))
	);
	
	if(isset($_GET['method'])){
		$method = explode('.', $_GET['method']);
		if(count($method) == 3){//always 3 sections: woophy.blog.getPhotosByUserId
			if($method[0] == 'woophy'){//valid request
				if(array_key_exists($method[1], $API)){//group
					if(array_key_exists($method[2], $API[$method[1]]['methods'])){//method			
						if($class = $API[$method[1]]['class']){
							include CLASS_PATH.$class.'.class.php';
							$inst = new $class();
							if(method_exists($inst, $method[2])){
								$param = array();
								foreach($API[$method[1]]['methods'][$method[2]] as $p){
									if(isset($_GET[$p])) $param[] = trim($_GET[$p]);
									else break 1;
								}
								$ret = call_user_func_array(array($inst, $method[2]), $param);
								switch($output_mode){
									case 'html':
										echo $ret;
										break;
									case 'json':
										echo json_encode($ret);
										break;
									default:
										echo $inst->send();
								}
								exit;
							}
						}
					}
				}
			}
		}
	}
	if($output_mode == 'rest')echo '<?xml version="1.0" encoding="utf-8" ?><rsp stat="fail"></rsp>';
?>