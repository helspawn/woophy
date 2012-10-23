<?php
/*
require_once '../includes/config.php';
require_once CLASS_PATH.'DB.class.php';
require_once CLASS_PATH.'Utils.class.php';
require_once CLASS_PATH.'ClassFactory.class.php';
*/

require_once CLASS_PATH.'Response.class.php';


class Map extends Response{
	
	const ZOOMLEVEL_KEY = 'Map::Zoomlevel';

	private $city;
	private $half_globe_width;
	private $pixel_per_degree;
	public function __construct() {
		$this->half_globe_width = GLOBE_WIDTH/2;
		$this->pixel_per_degree = GLOBE_WIDTH/360;
		parent::__construct();
	}
	
	//main function to get cities
	//you can apply one or more filters
	//@param key, val: key=city,keyword val=rotterdam,beach
	
	public function getCities($key, $val){
		$cities = array();
		$k = explode(',', $key);
		$v = explode(',', $val);
		if(count($k)!=count($v)){
			return $cities;
		}
		foreach($k as $i=>$n){
			$filters[urldecode($n)] = urldecode($v[$i]);
		}
		if(count($filters) == 0){
			return $cities;
		}

		$max_limit = 1000;
		$sql_select = array('u'=>'photos.city_id','x'=>'photos.longi','y'=>'photos.lati');
		$sql_joins = array();//this format: $sql_joins['table'] = 'condition';
		$sql_where = array();
		$sql_group = 'photos.city_id';
		$sql_order = NULL;
		$sql_limit = array(0,$max_limit);
		$sql_from = 'photos';

		$filterCities = FALSE; //if true: remove duplicate entries at the end and count number of occurences. Sometimes this approach is faster than a COUNT + GROUP BY query
		
		//remove city/cityid filters if tooltip is present (tooltip already queries on cityid)
		if(array_key_exists('tooltip',$filters)){
			unset($filters['city']);
			unset($filters['cityid']);
		}

		foreach($filters as $filter_key=>$filter_value){
			switch($filter_key){
				case 'tooltip':
					$sql_from = 'photos';
					$sql_select['pid'] = 'photos.photo_id';
					$sql_select['uid'] = 'photos.user_id';
					$sql_select['n'] = 'cities.FULL_NAME_ND';
					$sql_select['c'] = 'countries.country_name';
					$sql_joins['cities'] = 'photos.city_id = cities.UNI';
					$sql_joins['countries'] = 'cities.CC1=countries.country_code';

					if(count($filters)==1){
						$sql_select['q'] = 'cities.photo_count';
						$sql_group = NULL;
						$sql_where[] = 'photos.photo_id=(SELECT photo_id FROM photos WHERE city_id='.(int)$filter_value.' ORDER BY average_rate DESC LIMIT 1)';
					}else{
						$sql_where[] = 'photos.city_id='.(int)$filter_value;
						$sql_select['q'] = 'COUNT(photos.photo_id)';
						if($filterCities){
							$sql_group = 'photos.city_id';
							$filterCities = false;
						}
					}

					$sql_limit = array(0,1);
					break;
				case 'username':
				case 'userid':
					if($filter_key == "userid"){
						$uid = (int)$filter_value;
					}else{
						$result_uid = DB::query('SELECT user_id FROM users WHERE user_name = \''.DB::escape($filter_value).'\';');
						if($result_uid && DB::numRows($result_uid)==1){
							$uid = DB::result($result_uid, 0);
						}
					}
					if(isset($uid)){
						$sql_where[] = 'photos.user_id='.$uid;
						$sql_select['q'] = 'COUNT(photos.photo_id)';
						$sql_from = 'photos';
					}
					break;
				case 'city':
					//tricky: you cannot combine this filter with keywords!
					$sql_select['u']='cities.UNI';
					$sql_select['x']='cities.longi';
					$sql_select['y']='cities.lati';
					$sql_select['q']='cities.photo_count';
					$sql_where[] = 'cities.FULL_NAME_ND = \''.DB::escape($filter_value).'\'';
					$sql_where[] = 'cities.photo_count>0';
					$sql_order = 'photo_count DESC';
					$sql_from = 'cities';
					$sql_group = NULL;
					$sql_limit = array(0,min($sql_limit[1],5));//max 5 cities

					break;
				case 'cityid':
					$id = (int)$filter_value;
					if($id!=0 && strlen($id) == strlen($filter_value)){
						$sql_select['u']='photos.city_id';
						$sql_select['x']='photos.longi';
						$sql_select['y']='photos.lati';
						$sql_select['q']='COUNT(photos.photo_id)';
						$sql_where[] = 'photos.city_id = '.$id;
						$sql_from = 'photos';
					}else return $cities;
					break;
				case 'keywords':
					$tag_ids = Utils::getTagIds($filter_value);
					if(count($tag_ids)==0)return $cities;

					$sql_joins['photo_tag2photo'] = 'photos.photo_id=photo_tag2photo.photo_id';
					$sql_where[] = 'tag_id IN ('.implode(',',$tag_ids).')';
					$sql_from = 'photos';
					if(!isset($sql_select['q'])){
						$sql_group = NULL;
						$filterCities = true;
					}
					break;
				case 'photoid':
					$id = (int)$filter_value;
					if($id>0 && strlen($id) == strlen($filter_value)){
						$sql_where[] = 'photos.photo_id = '.$id;
						$sql_select['q'] = '1';
						$sql_from = 'photos';
					}else{
						return $cities;
					}
					break;
				case 'last24H':
					$sql_where[] = 'DATE_SUB( NOW() , INTERVAL 24 HOUR ) < photos.photo_date';
					$sql_select['q'] = 'COUNT(photos.photo_id)';
					$sql_from = 'photos';
					break;
				//case 'limit':
				//	$sql_limit = array(0, min($max_limit, (int)$filter_value));
				//	break;
				case 'travelblog':
					$sql_where[] = 'photos.travelblog_id='.(int)$filter_value;
					$sql_select['q'] = 'COUNT(photos.photo_id)';
					$sql_from = 'photos';
					$sql_order = 'photo_id ASC';
					break;
			}
		}

		if(count($sql_where)>0){
			$query = Utils::buildQuery($sql_select, array($sql_from), $sql_joins, $sql_where, $sql_group, $sql_order, $sql_limit);
			
//return $query;
			$result = DB::query($query);
			if($result){
				while($row = DB::fetchAssoc($result)){
					$row['x'] = (float)$row['x'];
					$row['y'] = (float)$row['y'];
					$row['u'] = (int)$row['u'];
					if($filterCities){
						if(isset($cities[$row['u']])){
							$cities[$row['u']]['q']++;
							continue;
						}
						if(!isset($row['q']))$row['q'] = 1;
						$cities[$row['u']] = $row;
						//$cities[1] = 'x';
						
					}else{
						$row['q'] = (int)$row['q'];
 					$cities[] = $row;						
					}
				}
				
				if($filterCities) $cities = array_merge($cities);//reset array keys
				
				function sortByQty($a, $b) {
					return $b['q'] - $a['q'];
				}
				usort($cities, 'sortByQty');

				if(array_key_exists('tooltip',$filters)){
					if(count($cities)==1)$cities[0]['url'] = Utils::getPhotoUrl($cities[0]['uid'], $cities[0]['pid'], 'thumb', 'photo');
				}
				
				//$cities['total'] = count($cities['result']);
			}
			
			//$cities['query'] = $query;
		}
		return $cities;
	}
	public function getCitiesCache($zoomlevel=1, $forceUpdate=false){//zoomlevel 1 or 2 (other levels are not cached), forceUpdate is only used for testing purposes
		$zoomlevel = (int)$zoomlevel;
		if($zoomlevel>0 && $zoomlevel<=2){
			if($this->hasCache()){//be sure memcache is running, if not skip updateZoomlevels because this function takes up too much resources to run every time 
				$key = self::ZOOMLEVEL_KEY.$zoomlevel;
				if($forceUpdate || !($cities = $this->getFromCache($key))){
					$this->updateZoomLevels();
					$cities = $this->getFromCache($key);
				}
			}
		}
		if(!isset($cities))$cities = getCitiesByArea(-180, 180, 90, -90, $zoomlevel);//fallback if memcache fails
		return $cities;
	}
	public function getCitiesByArea($x1,$y1,$x2,$y2,$zoomlevel){

		$zoomlevel = (int)$zoomlevel;
		$scale = pow(2, $zoomlevel+1);//zoomlevel is zero based
		
		if(round($x1, 6) == round($x2, 6) || $x1 == 180 && $x2 == -180){
			$x1 = -180;
			$x2 = 180;
		}
		$y1 = max(-MAX_LATITUDE,min(MAX_LATITUDE,$y1));
		$y2 = max(-MAX_LATITUDE,min(MAX_LATITUDE,$y2));

		if($x1<$x2) $qry ="LONGI BETWEEN $x1 AND $x2";
		else $qry = "LONGI NOT BETWEEN $x2 AND $x1"; 

		$query="SELECT photo_count AS q, UNI AS u, LONGI as x, LATI as y
		FROM cities
		WHERE photo_count > 0 AND zoomlevel > 0 AND zoomlevel <= $zoomlevel AND $qry AND LATI BETWEEN $y1 AND $y2 ORDER BY photo_count DESC";
		
		//return $query;

		
		$result = DB::query($query);
		//$time_query = $this->getElapsedTime();
		$cities = array();

		if($result){
			/*
			//we use zoomlevel field in dbase instead of the filter algorithme
			$hash = array();
			while($row = DB::fetchAssoc($result)){
				$x = ($this->lon2x($row['x']))*$scale;
				$y = ($this->lat2y($row['y']))*$scale;
				$idx_x = round($x/MAP_DENSITY);
				$idx_y = round($y/MAP_DENSITY);
				if(!isset($hash[$idx_x][$idx_y])){
					$hash[$idx_x][$idx_y] = true;
					$cities[] = array('u'=>(int)$row['u'],'x'=>(float)$row['x'],'y'=>(float)$row['y'],'q'=>(int)$row['q']);
				}
			}
			unset($hash);*/
			
			while($row = DB::fetchAssoc($result)){
				$cities[] = array('u'=>(int)$row['u'],'x'=>(float)$row['x'],'y'=>(float)$row['y'],'q'=>(int)$row['q']);
			}
		}
		return $cities;
	}

	public function getCitiesByTravelBlogId($travelblogId){
		//TRICKY: use limit because many records can cause slow query, avoid using GROUP BY because of filesort
		$result = DB::query('SELECT post_id AS p, city_id AS c, cities.longi AS x, cities.lati as y FROM blog_posts INNER JOIN cities ON blog_posts.city_id=cities.UNI WHERE travelblog_id = '.(int)$travelblogId.' AND post_status=\'published\' ORDER BY post_publication_date ASC LIMIT 0, 500');

		/*
		if($result && DB::numRows($result) >0){
			$xmlstr = '<cities>';
			$last_city_id = NULL;
			while($row = DB::fetchAssoc($result)){
				if($last_city_id == $row['city_id']) continue;
				$last_city_id = $row['city_id'];
				$xmlstr .= '<c p="'.$row['post_id'].'" x="'.$row['x'].'" y="'.$row['y'].'"/>';
				//$xmlstr .= '<c u="'.$last_city_id.'" x="'.$row['x'].'" y="'.$row['y'].'"/>';
			}
			$xmlstr .= '</cities>';
		}else $xmlstr = '<cities/>';
		return $this->getXMLObject($xmlstr);
		*/

		$cities = array();
		if($result && DB::numRows($result) >0){//filter duplicate cities:
			$last_city_id = NULL;
			while($row = DB::fetchAssoc($result)){
				if($last_city_id == $row['c']) continue;
				$last_city_id = $row['c'];
				$cities[] = $row;
			}
		}
		return $cities;
	}
	public function lon2x($lon){ 
		return $this->half_globe_width + $lon * $this->pixel_per_degree;
	}
	public function lat2y($lat){ 
		return $this->half_globe_width + ($this->half_globe_width/M_PI) * log(tan(M_PI_4+deg2rad($lat)/2));
	}
	/*
	public function x2lon($x){
		return ($x - $this->half_globe_width)/$this->pixel_per_degree;
	}
	public function y2lat($y){//TODO: fix this function
		return rad2deg(2.0 * atan(exp($y / GLOBE_WIDTH)) - M_PI_2);
	}*/
	
	/*	
	 *	updates database and caches results, this function is called once every 3 days. this needs optimization because it runs for 2 seconds
	 */
	public function updateZoomLevels(){

		$result = DB::query('SELECT longi as x, lati as y, UNI AS u, zoomlevel AS z FROM cities WHERE photo_count >0 ORDER BY photo_count DESC, UNI DESC');//TRICKY: order by uni needs a filesort!! But this is necessary because order by photo_count alone gives unreliable results 
		if($result){
			$new_zoom = array();//store uni and zoom if new zoom is different than current zoom
			$hash = array();
			
			while($row = DB::fetchAssoc($result)){
				$u = $row['u'];
				$z = $row['z'];
				$x = $row['x'];
				$y = $row['y'];
				$new_z = 0;
				for($zoomlevel=MIN_ZOOMLEVEL; $zoomlevel<=MAX_ZOOMLEVEL; $zoomlevel++){
					
					$scale = pow(2, $zoomlevel+1);//zoomlevel is zero based
					
					$_x = ($this->lon2x($x))*$scale;
					$_y = ($this->lat2y($y))*$scale;
					
					$idx_x = round($_x/MAP_DENSITY);
					$idx_y = round($_y/MAP_DENSITY);
					if(!isset($hash[$zoomlevel][$idx_x][$idx_y])){
						$hash[$zoomlevel][$idx_x][$idx_y] = $zoomlevel;
						if($new_z == 0) $new_z = $zoomlevel;
						//break;//do not break because city needs to be stored in all hash zoomlevels
					}
				}
				if($z != $new_z){
					$new_zoom[$u] = $new_z;
					//echo $u.': old:'.$z.' new:'.$new_z.'<br/>';
				}
			}
			//print_r($hash);
			unset($hash);//free memory
			foreach($new_zoom as $uni=>$zoom){
				DB::query('UPDATE cities SET zoomlevel = '.$zoom.' WHERE UNI = '.$uni);
			}
			//store first 2 zoom levels into cache
			for($zoomlevel=MIN_ZOOMLEVEL;$zoomlevel<=MIN_ZOOMLEVEL+1;$zoomlevel++){
				$result = DB::query('SELECT UNI AS u, LONGI as x, LATI as y, photo_count AS q FROM cities WHERE photo_count>0 AND zoomlevel>0 AND zoomlevel <= '.$zoomlevel.' ORDER BY photo_count DESC');//include photo_count in WHERE clause, so index can be used (and no need for an extra index for this query only)
				if($result){
					$cities = array();
					while($row = DB::fetchAssoc($result)){
						$cities[] = array('u'=>(int)$row['u'],'x'=>(float)$row['x'],'y'=>(float)$row['y'],'q'=>(int)$row['q']);
					}
					$this->saveToCache(self::ZOOMLEVEL_KEY.$zoomlevel, $cities, false, 259200);//cache for 3 days
					
					//echo self::ZOOMLEVEL_KEY.$zoomlevel.'<br/>'.count($cities).'<br/>';
				}
			}
			
			include_once CLASS_PATH.'Status.class.php';//update status here??
			$status = ClassFactory::create('Status');
			$status->updateNumberOfCities();
		}
	}

	//main function to get photo ids
	//you can apply one or more filters
	//@param key, val: key=city,keyword val=rotterdam,beach
	//returns: array('results'=>array, 'total'=>int)
	public function getPhotos($key, $val, $offset, $limit, $output_mode='html'){
		$html_output = '';
		$header_type = '';
		$header_value = '';
		$photo = ClassFactory::create('Photo');
		$photos = array('result'=>array(), 'total'=>0, 'offset'=>$offset);

		$k = explode(',', $key);
		$v = explode(',', $val);
		if(count($k)!=count($v)){
			if($output_mode == 'html') return $html_output;
			else return $photos;
		}
		foreach($k as $i=>$n){
			$filters[$n] = $v[$i];
			if($n=='cityid') $n='city';
			if($n=='userid') $n='username';
			$header[$n] = $v[$i];
		}
		if(count($filters) == 0){
			if($output_mode == 'html') return $html_output;
			else return $photos;
		}
		$max_limit = 32;
		$limit = min(max(0, $limit), $max_limit);
		$max_offset = 1000-$limit;
		$offset = min($max_offset, max(0, $offset));
		

		$sql_select = array('id'=>'photos.photo_id','cid'=>'photos.city_id','uid'=>'photos.user_id','seo_suffix'=>'photos.seo_suffix','alt_text'=>'photos.alt_text','username'=>'users.user_name','city'=>'cities.FULL_NAME_ND','country'=>'countries.country_name','cc'=>'countries.country_code','comments'=>'photos.comment_count','favorites'=>'photos.favorite_count');
		$sql_where = array();
		$sql_order = 'photos.average_rate DESC';
		$sql_limit = array($offset, $limit);
		$sql_from = array('photos');
		$sql_joins = array();
		$sql_group = NULL;
		$sql_count = 'COUNT(0)';
		$sql_joins['users'] = 'photos.user_id=users.user_id';
		$sql_joins['cities'] = 'photos.city_id=cities.UNI';
		$sql_joins['countries'] = 'cities.CC1=countries.country_code';
		
		$query_total = TRUE;

		foreach($filters as $filter_key=>$filter_value){
			switch($filter_key){
				case 'username':
					//first get id, this approach is faster than extra join on users
					$result_uid = DB::query('SELECT user_id FROM users WHERE user_name = \''.DB::escape($filter_value).'\';');
					if($result_uid && DB::numRows($result_uid)==1){
						$sql_where[] = 'photos.user_id='.DB::result($result_uid, 0);
					}
					break;
				case 'userid':
					$sql_where[] = 'photos.user_id='.(int)$filter_value;
					break;
				case 'city':
					if(!isset($filters['cityid'])){//if city_id is present, no need to look for city_name

						$result = DB::query('SELECT UNI FROM cities WHERE photo_count>0 AND FULL_NAME_ND = \''.DB::escape($filter_value).'\' ORDER BY photo_count DESC LIMIT 0,5;');//max 5 cities

						if($result && DB::numRows($result)>0){
							while($row = DB::fetchAssoc($result))$city_ids[]=$row['UNI'];
							$sql_where[] = 'city_id IN ('.implode(',',$city_ids).')';//TRICKY: if more than 1 cities are found, mysql uses filesort!
						}else{
							//TODO: look for name variants!!
							if($output_mode == 'html') return $html_output . self::getHeaderMeta($filters, 2);
							else return $photos;
						}
					}
					break;
				case 'cityid':
					$sql_where[] = 'photos.city_id='.(int)$filter_value;
					$sql_joins['users'] = 'photos.user_id=users.user_id';;
					break;
				case 'keywords':
					if($header_type == 'keywords') $header_value = '"'. $filter_value . '"';
					$tag_ids = Utils::getTagIds($filter_value);
					if(count($tag_ids)==0){
						if($output_mode == 'html') return $html_output . self::getHeaderMeta($filters, 2);
						return $photos;
					}
					if(count($filters)>1){//limit filter is always used
						//$sql_select['id'] = 'DISTINCT photos.photo_id';
						$sql_count = 'COUNT(DISTINCT photos.photo_id)';//you can use DISTINCT because there is no ORDER BY
						$sql_joins['photo_tag2photo'] = 'photos.photo_id=photo_tag2photo.photo_id';
						$sql_where[] = 'tag_id IN ('.implode(',',$tag_ids).')';
						unset($tag_ids);
					}
					
					//$sql_where[] = 'MATCH (photos.keywords) AGAINST (\''.DB::escapeMatchPattern($filter_value).'\' IN BOOLEAN MODE)';
					//unset($sql_order);
					break;
				case 'photoid':
					$id = (int)$filter_value;
					if($id>0 && strlen($id) == strlen($filter_value)){
						$sql_where[] = 'photos.photo_id='.$id;
					}else{
						if($output_mode == 'html') return $html_output . self::getHeaderMeta($filters, 2);
						else return $photos;
					}
					break;
				case 'last24H':
					//used by photos added today
					$sql_where[] = 'DATE_SUB( NOW() , INTERVAL 24 HOUR ) < photos.photo_date';
					$sql_order = 'photos.photo_date DESC';
					break;
				case 'travelblog':
					$sql_where[] = 'photos.travelblog_id='.(int)$filter_value;
					$sql_order = 'photo_id DESC';
					break;
				//case 'orderby':
				//	$sql_order = DB::escape($filter_value);
				//	break;
			}
		}
		
		if(isset($tag_ids)){
			//with only one filter, get photoids first, this is faster than inner join
			$tag_ids_str = implode(',',$tag_ids);
			$result = DB::query('SELECT photo_id FROM photo_tag2photo WHERE tag_id IN ('.$tag_ids_str.') ORDER BY average_rate DESC LIMIT '.$sql_limit[0].','.$sql_limit[1]);
			$photo_ids = array();
			if($result){
				while($row = DB::fetchAssoc($result))$photo_ids[] = $row['photo_id'];
			}
			if(count($photo_ids)==0){
				if($output_mode == 'html') return $html_output . self::getHeaderMeta($filters, 2);
				else return $photos;
			}
			$sql_where[] = 'photos.photo_id IN ('.implode(',',$photo_ids).')';
			
			$photos['total'] = DB::result(DB::query('SELECT COUNT(0) FROM photo_tag2photo WHERE tag_id IN ('.$tag_ids_str.')'), 0);
			$query_total = FALSE;
		}
		
		if(count($sql_where)>0){
			if(isset($tag_ids)) $sql_limit = array(0, $sql_limit[1]); //offset already applied when returning the photo_ids from the tags
			$query = Utils::buildQuery($sql_select, $sql_from, $sql_joins, $sql_where, $sql_group, $sql_order, $sql_limit);

//$photos['query'] = $query;
//echo $query;
			$result = DB::query($query);
			if($result){
				$num_results = 0;
				$meta_html = '';
				if($output_mode=='html'){
					$tpl = ClassFactory::create('Template','gallery_image.tpl');
					while($row = DB::fetchAssoc($result)){
						if($num_results==0){
							if(array_key_exists('city', $header)) $header['city'] = htmlspecialchars($row['city'], ENT_QUOTES, 'UTF-8') .', '.$row['country'];
							if(array_key_exists('username', $header)) $header['username'] = htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8');
						}
						$icons = '';
						$num_results++;
						$html_output .= $tpl->parse(array(
							'root_url' 			=> ROOT_PATH,
							'photo_id' 			=> $row['id'],
							'lightbox'			=> ' lightbox',
							'map_link'			=> ' MapLink',
							'user_name'			=> htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8'),
							'city'				=> htmlspecialchars($row['city'], ENT_QUOTES, 'UTF-8'),
							'city_id'			=> $row['cid'],
							'country'			=> $row['country'],
							'photo_url' 		=> Utils::getPhotoUrl($row['uid'],$row['id'],'medium','',$row['seo_suffix']),
							'comment_count'		=> (int)$row['comments'],
							'favorite_count'	=> (int)$row['favorites'],
							'alt'				=> htmlspecialchars($row['alt_text'], ENT_QUOTES, 'UTF-8'),
						));	
					}
					$html_output .= self::getHeaderMeta($header, $num_results<$limit);
				}else{
					while($row = DB::fetchAssoc($result)){
						$photos['result'][] = $row;
					}
				}
			}else{
				if($output_mode=='html') $html_output .= self::getHeaderMeta($filters, 2);
			}
			if(count($photos['result'])>0){
				if($query_total){
					$query = Utils::buildQuery(array('q'=>$sql_count), $sql_from, $sql_joins, $sql_where, $sql_group);
					$photos['total'] = DB::result(DB::query($query), 0);
					//$photos['query'] = $query;
				}
			}
			$photos['offset'] = $sql_limit[0];
			if($output_mode == 'html') return $html_output;
			else return $photos;
		}else{
			if($output_mode == 'html') return $html_output .= self::getHeaderMeta($filters, 2);
			else return $photos;
		}
	}

	static function getHeaderMeta($header, $display_end_marker){
		$output = '<div id="GalleryMeta" class="nodisplay">';
		foreach($header as $hk=>$hv){
			$output .= '<div class="HeaderMeta" id="'. $hk .'">'. $hv .'</div>';
		}
		if($display_end_marker==1) $output .= '<div id="__ENDOFGALLERY__"></div>';
		elseif($display_end_marker==2) $output .= '<div id="__EMPTYGALLERY__"></div>';
		$output .= '</div>';
		return $output;
	}
}


//$m = new Map;
//print print_r($m->getCities("tooltip",-2984658)).'<br/>';
//print_r($m->getCitiesByArea(-1.23046875, 45.506346901083425, 10.01953125, 52.84259457223952, 6));
//$m->updateZoomLevels();


//print_r( $m->getCitiesCache(1));
//print $m->getElapsedTime();
//print $m->getCities(array("tooltip"=>-2969693));
//print $m->getCitiesToday();
//print $m->getCityById(-2985310);

//print_r($m->getCities(array("tooltip"=>"-2111976","keywords"=>"statue")));
//print $m->getCities(array("travelblog"=>1)).'<br/>';
//print_r($m->getCitiesByTravelBlogId(1)).'<br/>';
//print_r($m->getInfo(-2984658));



?>