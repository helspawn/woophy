<?php
require_once CLASS_PATH.'Response.class.php';
class Location extends Response{

	const ERRBASE = 700;

	public function getAllCountries(){
		if(!($xmlstr = $this->getFromCache(__METHOD__))){
			$xmlobj = $this->getXMLObject();
			$result = DB::query('SELECT country_name, country_code FROM countries ORDER BY country_name ASC;');
			if($result){
				while ($row = DB::fetchAssoc($result)) {
					$country = $xmlobj->addChild('country', htmlspecialchars($row['country_name'], ENT_QUOTES, 'UTF-8'));
					$country->addAttribute('cc', $row['country_code']);
				}
				$this->saveToCache(__METHOD__, $this->send(), false, 0);
			}else $this->throwError(1);
		}else $xmlobj = $this->getXMLObject($xmlstr);
		return $xmlobj;
	}
	public function getAllCitiesByName($name){
		$xmlobj = $this->getXMLObject();
		$query = 'SELECT cities.CC1, MAX(cities.photo_count) as count, countries.country_name 
				FROM cities 
				INNER JOIN countries ON cities.CC1 = countries.country_code 
				WHERE cities.FULL_NAME_ND=\'' . DB::escape($name). '\' AND cities.photo_count >0 
				GROUP BY countries.country_name 
				ORDER BY count DESC;';
		$result = DB::query($query);
		if($result){
			while ($row = DB::fetchAssoc($result)) {
				$city = $xmlobj->addChild('city');
				$city->addChild('city_name', $name);
				$city->addChild('country_code', $row['CC1']);
				$city->addChild('country_name', $row['country_name']);
				$city->addChild('photo_count', $row['count']);
			}
			$this->saveToCache(__METHOD__, $this->send(), false, 0);
		}else $this->throwError(1);
		return $xmlobj;
	}
	public function getRandomCountryCode(){
		if(!($xmlstr = $this->getFromCache(__METHOD__))){
			$xmlobj = $this->getXMLObject();
			if($result = DB::query('SELECT DISTINCT country_code FROM photos')){
				$idx = mt_rand(1, DB::numRows($result));
				if(DB::dataSeek($result, $idx-1)){
					$row = DB::fetchAssoc($result);
					$xmlobj->addChild('country_code',$row['country_code']);
					$this->saveToCache(__METHOD__, $this->send(), false, 86400);//cache for 1 day
				}else $this->throwError(2);
			}else $this->throwError(1);
		}else $xmlobj = $this->getXMLObject($xmlstr);
		return $xmlobj;
	}
	public function getCountriesByPhotoCount(){
		if(!($xmlstr = $this->getFromCache(__METHOD__))){
			$xmlobj = $this->getXMLObject();
			//TODO: this is a slow query, uses filesort on 247 rows. Be sure to use memcache here
			$result = DB::query('SELECT countries.country_name, (
			SELECT COUNT( 0 )
			FROM users
			WHERE countries.country_code = users.country_code
			) AS user_count, count( 0 ) AS photo_count, photos.country_code, MAX( photos.photo_id ) AS photo_id
			FROM photos
			INNER JOIN countries ON photos.country_code = countries.country_code
			GROUP BY photos.country_code
			ORDER BY photo_count DESC
			LIMIT 0 , 10');
			if($result){
				$photo_ids = array();
				while ($row = DB::fetchAssoc($result)) $photo_ids[] = $row['photo_id'];
				if(count($photo_ids)>0){
					//fetch the user_id, (because of MAX we need two queries)
					$result_user = DB::query('SELECT user_id, photo_id FROM photos WHERE photo_id IN ('.implode(',',$photo_ids).')');
					if($result_user){
						$user_ids = array();
						while ($row = DB::fetchAssoc($result_user)) $user_ids[$row['photo_id']] = $row['user_id'];//build array for easy lookup
						DB::dataSeek($result,0);
						while ($row = DB::fetchAssoc($result)){
							if(isset($user_ids[$row['photo_id']])){
								$country = $xmlobj->addChild('country');
								$country->addChild('name', htmlspecialchars($row['country_name'], ENT_QUOTES, 'UTF-8'));
								$country->addChild('code', $row['country_code']);
								$country->addChild('photo_count', $row['photo_count']);
								$country->addChild('photo_id', $row['photo_id']);
								$country->addChild('user_id', $user_ids[$row['photo_id']]);
								$country->addChild('user_count', $row['user_count']);
							}
						}
						$this->saveToCache(__METHOD__, $this->send(), false, 604800);//cache for 7 days
					}else $this->throwError(1);
				}else $this->throwError(2);
			}else $this->throwError(1);
		}else $xmlobj = $this->getXMLObject($xmlstr);
		return $xmlobj;
	}
	public function getCountryInfoByCode($cc){
		return $this->getCountryInfo($cc);
	}
	public function getCountryInfoByName($name){
		return $this->getCountryInfo(NULL, $name);
	}
	private function getCountryInfo($code=NULL, $name=NULL){
		$xmlobj = $this->getXMLObject();
		if(isset($code))$query = 'SELECT * FROM countries WHERE country_code = \''.DB::escape($code).'\' LIMIT 1';
		else if(isset($name))$query = 'SELECT * FROM countries WHERE country_name LIKE \''.DB::escapeLikePattern($name).'\' LIMIT 1';
		if(isset($query)){
			$result = DB::query($query);
			if($result){
				if(DB::numRows($result)==1){
					$r = DB::fetchAssoc($result);
					$xmlobj->addChild('name', htmlspecialchars($r['country_name'], ENT_QUOTES, 'UTF-8'));
					$xmlobj->addChild('code', $r['country_code']);
					$a = array('government','capital','languages','area','population','currency','climate','religions');
					foreach($a as $e)$xmlobj->addChild($e, is_null($r[$e])?'':htmlspecialchars($r[$e], ENT_QUOTES, 'UTF-8'));
				}else $this->throwError(2);
			}else $this->throwError(1);
		}
		return $xmlobj;
	}
	protected function throwError($code=1, $msg = ''){
		switch($code){
			case 1:$msg='Error executing query.';break;
			case 2:$msg='No record found.';break;
		}
		parent::throwError(self::ERRBASE+$code, $msg);
	}
}
?>