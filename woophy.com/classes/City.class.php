<?php
require_once CLASS_PATH.'Response.class.php';
require_once CLASS_PATH.'Status.class.php';

class City extends Response{
	
	const ERRBASE = 600;
	
	public $errorMessage;
	public $errorNo;
	
	public function getCities($cityname='', $cc=NULL, $limit=NULL){
		$xmlobject = $this->getXMLObject();
		if(isset($cc,$cityname) && mb_strlen($cityname)>0){
			$cityname = DB::escapeLikePattern(ltrim($cityname,'?'));
			$cityname = strtr($cityname, '?', '_');
			$operator = mb_strlen($cityname) > 1 ? 'LIKE' : '=';
			if(isset($limit))$limit = min(100, max(0,(int)$limit));
			else $limit = 10;
			$result = DB::query('SELECT FULL_NAME_ND, UFI, LATI, LONGI, regions.region FROM cities LEFT JOIN regions ON CONCAT(cities.CC1,cities.ADM1) = regions.code WHERE cities.CC1 = \''.DB::escape($cc).'\' AND cities.FULL_NAME_ND '.$operator.' \''.$cityname.'\' ORDER BY cities.FULL_NAME_ND, cities.PC DESC, cities.photo_count DESC, cities.NT LIMIT 0, '.$limit.';');

			if($result){
				while ($row = DB::fetchAssoc($result)) {
					$city = $xmlobject->addChild('city', $row['FULL_NAME_ND']);
					$city->addAttribute('UFI', $row['UFI']);
					$city->addAttribute('region', htmlspecialchars($row['region'], ENT_QUOTES, 'UTF-8'));
					$city->addAttribute('lat', Utils::dec2dms((float)$row['LATI'], 'lat'));
					$city->addAttribute('long', Utils::dec2dms((float)$row['LONGI'], 2));
				}	
			}else $this->throwError(3);
		}else $this->throwError(2);
		return $xmlobject;
	}
	public function getCitiesByCountryCode($cc){//returns the 5 most popular cities
		if($xmlstr = $this->getFromCache(__METHOD__)){//if result is cached. get it!
			$xmlobj = new SimpleXMLElement($xmlstr);
			if($c = $xmlobj->city[0])if(mb_strtoupper($cc) == mb_strtoupper($c->country_code))return $xmlobj;
		}
		$xmlobject = $this->getXMLObject();
		if(isset($cc) && mb_strlen($cc) == 2){
			$result = DB::query('SELECT UNI FROM cities WHERE CC1 = \''.DB::escape($cc).'\' AND photo_count >0 ORDER BY photo_count DESC LIMIT 0, 5');
			if($result && DB::numRows($result)>0){
				//compiler doesn't use index when using subselect in one query (according to 'EXPLAIN'), so run queries separately
				$city_ids = array();
				while($row = DB::fetchAssoc($result))$city_ids[]=$row['UNI'];
				$result = DB::query('SELECT MAX(photo_id) AS photo_id FROM photos WHERE city_id IN('.implode(',',$city_ids).') GROUP BY city_id');
				if($result && DB::numRows($result)>0){
					$photo_ids = array();
					while($row = DB::fetchAssoc($result))$photo_ids[]=$row['photo_id'];
					$result = DB::query('SELECT cities.FULL_NAME_ND, cities.photo_count, countries.country_name, photos.country_code, photo_id, user_id, photos.alt_text, photos.seo_suffix FROM photos INNER JOIN cities ON photos.city_id = cities.UNI INNER JOIN countries ON countries.country_code = cities.CC1 WHERE photo_id IN('.implode(',',$photo_ids).') ORDER BY photo_count DESC');
					if($result){
						while($row = DB::fetchAssoc($result)){
							$c = $xmlobject->addChild('city');
							$c->addChild('name', htmlspecialchars($row['FULL_NAME_ND'], ENT_QUOTES, 'UTF-8'));
							$c->addChild('photo_count', $row['photo_count']);
							$c->addChild('country_name', htmlspecialchars($row['country_name'], ENT_QUOTES, 'UTF-8'));
							$c->addChild('seo_suffix', htmlspecialchars($row['seo_suffix'], ENT_QUOTES, 'UTF-8'));
							$c->addChild('alt_text', htmlspecialchars($row['alt_text'], ENT_QUOTES, 'UTF-8'));
							$c->addChild('country_code', $row['country_code']);
							$c->addChild('photo_id', $row['photo_id']);
							$c->addChild('user_id', $row['user_id']);
						}
						$this->saveToCache(__METHOD__, $this->send(), false, 172800);//cache for 2 days
					}
				}else $this->throwError(1);
			}else $this->throwError(1);
		}else $this->throwError(2);
		return $xmlobject;
	}
	public function updatePhotoCount($uni){
		$xmlobject = $this->getXMLObject();
		$result = DB::query('SELECT COUNT(*) FROM photos WHERE city_id='.(int)$uni);
		if($result){
			$num = (int)DB::result($result, 0);			
			$qry = '';
			if($num == 0) $qry = ', zoomlevel=0';//reset zoomlevel in case cities has no photos anymore
			if(!DB::query('UPDATE cities SET photo_count = '.$num.$qry.' WHERE UNI='.(int)$uni))$this->throwError(1);
		}else $this->throwError(1);
		return $xmlobject;
	}
	public function getCityByUFI($ufi){
		$xmlobject = $this->getXMLObject();
		$result = DB::query('SELECT cities.UNI, cities.CC1, cities.FULL_NAME_ND, countries.country_name, cities.LONGI, cities.LATI FROM cities INNER JOIN countries ON cities.CC1 = countries.country_code WHERE UFI = \''.(int)$ufi.'\' ORDER BY cities.NT LIMIT 1;');
		if($result && DB::numRows($result) == 1){
			$row = DB::fetchAssoc($result);
			$xmlobject->addChild('UNI', $row['UNI']);
			$xmlobject->addChild('city_name', $row['FULL_NAME_ND']);
			$xmlobject->addChild('country_code', $row['CC1']);
			$xmlobject->addChild('country_name', $row['country_name']);
			$xmlobject->addChild('longi', $row['LONGI']);
			$xmlobject->addChild('lati', $row['LATI']);
		}else $this->throwError(1);
		return $xmlobject;
	}
	public function getCountryByUNI($uni){
		$xmlobject = $this->getXMLObject();
		$result = DB::query('SELECT cities.CC1 as country_code, countries.country_name FROM cities INNER JOIN countries ON cities.CC1 = countries.country_code WHERE UNI = \''.(int)$uni.'\' ORDER BY cities.NT LIMIT 1;');
		if($result && DB::numRows($result) == 1){
			$row = DB::fetchAssoc($result);
			$xmlobject->addChild('country_code', $row['country_code']);
			$xmlobject->addChild('country_name', $row['country_name']);
		}else $this->throwError(1);
		return $xmlobject;
	}
	public function getCityUNIByName($city_name, $country_code=NULL){
		$xmlobject = $this->getXMLObject();
		$cc_filter = '';
		if($country_code!=NULL)$cc_filter = ' AND CC1 = \''.$country_code.'\'';
		$query = 'SELECT UNI FROM cities WHERE FULL_NAME_ND = \''.DB::escape($city_name).'\''.$cc_filter.' ORDER BY photo_count DESC, NT DESC LIMIT 1;';
		$result = DB::query($query);
		
		if($result && DB::numRows($result) == 1){
			$row = DB::fetchAssoc($result);
			$xmlobject->addChild('UNI', $row['UNI']);
		}else $this->throwError(1);
		
		return $xmlobject->UNI;
	}
	protected function throwError($code=1, $msg = ''){
		switch($code){
			case 1:$msg='Error executing query';break;
			case 2:$msg='Missing argument';break;
		}
		parent::throwError(self::ERRBASE+$code, $msg);
	}
}
?>