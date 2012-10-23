<?php
require_once CLASS_PATH.'Response.class.php';
require_once CLASS_PATH.'Image.class.php';
class User extends Response{

	private $access;
	private $city;
	const ERRBASE = 200;
	const ANTISPAM_TIME_DELAY = 120;//seconds

	public function __construct() {
		$this->access = ClassFactory::create('Access');
		parent::__construct();
	}
	public function isEditor(){
		if($uid = $this->access->getUserId()){//logged in
			//if(isset($_SESSION['usereditor']))return $_SESSION['usereditor'];
			//else{
				$result = DB::query('SELECT editor FROM users WHERE user_id= '.(int)$uid);
				if($result){
					$editor = (int)DB::result($result, 0)==0?false:true;
					$_SESSION['usereditor'] = $editor;
					@session_write_close();
					return $editor;
				}
			//}
		}
		return false;
	}
	public function getRecent($offset=0, $limit=30, $widget=FALSE){
		//$count = 0;
		$XMLObject = $this->getXMLObject();
		$query = 'SELECT users.user_id, users.user_name, users.photo_count, users.registration_date, cities.UNI as city_id, cities.FULL_NAME_ND, countries.country_name 
				FROM users 
				LEFT JOIN cities ON users.UNI = cities.UNI 
				LEFT JOIN countries ON cities.cc1 = countries.country_code
				WHERE users.photo_count >0 
				ORDER BY users.user_id DESC LIMIT '.min(200,max(0,(int)$offset)).' , '.min(30,max(0,(int)$limit));
		$result = DB::query($query);
		if($result){
			while($row = DB::fetchAssoc($result)){
				$user=NULL;
				if($widget){
					//if(Utils::s3_exists(AWS_BUCKET,'images/avatars/'.$row['user_id'].'.jpg')){
						$user = $XMLObject->addChild('item');
						$user->addAttribute('type', 'user');
						$user->addAttribute('timestamp', strtotime($row['registration_date']));
						//$count++;
					//}
				}else{
					$user = $XMLObject->addChild('user');
				}
				if($user != NULL){
					$user->addChild('id', $row['user_id']);
					$user->addChild('name', htmlspecialchars($row['user_name']));
					$user->addChild('photo_count', (int)$row['photo_count']);
					$user->addChild('registration_date', $row['registration_date']);
					$user->addChild('city_id', $row['city_id']);
					$user->addChild('city_name', $row['FULL_NAME_ND']);
					$user->addChild('country_name', $row['country_name']);
					//if($widget && $count>=5) break;
				}
			}
		}else $this->throwError(8);
		return $XMLObject;
	}
	public function getUsersByName($name, $offset=0, $limit=10, $type=0, $extended=false){/* type:0/1, starts with/exact match*/
		$xmlobject = $this->getXMLObject();
		$type = (int)$type;
		$offset = max(0,(int)$offset);
		$limit = min(100,max(0,(int)$limit));
		$name = DB::escapeLikePattern($name);
		if(!$extended){
			if(mb_strlen($name)>1){
				$name = trim($name,'%');
				$name = $name.'%';//always append wildcard
			}
		}
		if($type == 1)$name = trim($name,'%');
		$operand = $type==1?'=':'LIKE';
		if($extended)$query = 'SELECT user_id, user_name, last_upload_date, users.photo_count, users.registration_date, cities.FULL_NAME_ND, countries.country_name FROM users LEFT JOIN cities ON users.UNI = cities.UNI LEFT JOIN countries ON cities.cc1 = countries.country_code WHERE user_name '.$operand.' \''.$name.'\' AND users.photo_count>0 LIMIT '.$offset.','.$limit;
		else $query = 'SELECT user_name FROM users WHERE user_name LIKE \''.$name.'\' AND users.photo_count>0 LIMIT 0, 10';
		$result = DB::query($query);
		if($result){
			$num_rows = DB::numRows($result);
			$xmlobject['total_users'] = $num_rows;
			if($num_rows>0){
				while($row = DB::fetchAssoc($result)){
					$user = $xmlobject->addChild('user');
					$user->addAttribute('name', $row['user_name']);
					if($extended){
						$user->addAttribute('id', $row['user_id']);
						$user->addAttribute('last_upload_date', $row['last_upload_date']);
						if($city = $row['FULL_NAME_ND']) $user->addAttribute('city_name', $city);
						if($country = $row['country_name']) $user->addAttribute('country_name', $country);
						$user->addAttribute('photo_count', (int)$row['photo_count']);
						$user->addAttribute('registration_date',$row['registration_date']);
					}
				}
				if($extended){
					if($offset+$num_rows>=$limit){
						$result_count = DB::query('SELECT count(0) FROM users WHERE user_name '.$operand.' \''.$name.'\' AND users.photo_count>0');
						if($result_count) $xmlobject['total_users'] = DB::result($result_count, 0);
					}
				}
			}
		}else $this->throwError(8);
		return $xmlobject;
	}
	public function getUsersMostFavorited($offset=0, $limit=10, $show_last_month=FALSE){
		$max_limit = 1000;
		$limit = min((int)$limit, 30);
		$offset = min((int)$offset, $max_limit-$limit);
		$cache = FALSE;
		if($offset==0 && $limit==10){//default 10, store in cache:
			if($xmlstr = $this->getFromCache(__METHOD__.$show_last_month)){
				$xmlobject = $this->getXMLObject($xmlstr);
				$cache = TRUE;
			}
		}
		if(!$cache){
			$xmlobject = $this->getXMLObject();
			if($show_last_month){
				$last_month = date('Ymd', strtotime('-1 month'));
				$query = 'SELECT favorite_users.favorite_user_id as user_id, COUNT(favorite_id) as favorites FROM favorite_users WHERE favorite_date_lookup > \''.$last_month.'\' GROUP BY favorite_user_id ORDER BY favorites DESC LIMIT 0, '.$limit; //offset should never be >0, because of possible slow query
			}else $query = 'SELECT user_id FROM users ORDER BY favorite_count DESC LIMIT '.$offset.', '.$limit;
			$result = DB::query($query);
			if($result){
				$ids = array();
				while ($row = DB::fetchAssoc($result)) array_push($ids, $row['user_id']);
				if(count($ids)>0){
					$query2 = 'SELECT users.user_id, users.user_name, users.photo_count, users.favorite_count, users.registration_date, cities.FULL_NAME_ND, countries.country_name 
							FROM users 
							LEFT JOIN cities ON users.UNI = cities.UNI 
							LEFT JOIN countries ON cities.cc1 = countries.country_code
							WHERE user_id IN ('.implode(',',$ids).') ORDER BY favorite_count DESC, user_id ASC;';
					if($result = DB::query($query2)){
						while($row = DB::fetchAssoc($result)){
							$user = $xmlobject->addChild('user');
							$user->addChild('id',$row['user_id']);
							$user->addChild('name',htmlspecialchars($row['user_name']));
							$user->addChild('photo_count',$row['photo_count']);
							$user->addChild('registration_date',$row['registration_date']);
							$user->addChild('city_name', $row['FULL_NAME_ND']);
							$user->addChild('country_name', $row['country_name']);
						}
						if($offset==0 && $limit==10)$this->saveToCache(__METHOD__.$show_last_month, $this->send(), false, 43200);//cache for half a day
					}else $this->throwError(8);
				}else $this->throwError(2);
			}else $this->throwError(8);
		}
		return $xmlobject;
	}
	public function getBirthdayUserIDs($offset=0,$limit=5, $force_update=FALSE){
		$update = $force_update;
		$max_limit = 1000;
		$offset = min((int)$offset, $max_limit);
		$limit = min((int)$limit, 20);
		$today = date('Y-m-d');
		if(!$force_update){
			if($xmlstr = $this->getFromCache(__METHOD__)){
				$xmlobject = $this->getXMLObject($xmlstr);
				if($xmlobject->date != $today){
					$update = TRUE;
					$this->clear();
				}
			}else{$update = TRUE;}
			
		}
		if($update){
			$xmlobject = $this->getXMLObject();
			$xmlobject->addChild('date', $today);
			$query = 'SELECT user_id FROM users WHERE day_of_birth = DATE_FORMAT(\''.$today.'\', \'%m-%d\') AND photo_count >0 ORDER BY photo_count DESC LIMIT 0,'. $max_limit; 
			$result = DB::query($query);
			if($result){
				while($row = DB::fetchAssoc($result)){
					$xmlobject->addChild('user', $row['user_id']);
				}
				$this->saveToCache(__METHOD__, $this->send(), false, 0);//save all users to cache and apply offset, limit later on
			}else $this->throwError(8);
		}

		//apply offset, limit:
		$users = $xmlobject->user;
		$total = count($users);
		$i = $offset+$limit;
		$j = count($users);
		
		$xmlobject->addChild('total',$j);
		$xmlobject->addChild('date', $today);
		
		if($offset != -1 && $limit != -1){
			while($j-->$i)unset($users[$j]);
			while($offset-->0)unset($users[$offset]);
		}
		return $xmlobject;		
	}
	public function getBirthdayUsers($offset=0,$limit=5){
		$force_update = FALSE;//set to TRUE to reset cache
		$update = $force_update;
		$max_limit = 1000;
		$offset = min((int)$offset, $max_limit);
		$limit = min((int)$limit, 20);
		$today = date('Y-m-d');
		if(!$force_update){
			if($xmlstr = $this->getFromCache(__METHOD__)){
				$xmlobject = $this->getXMLObject($xmlstr);
				if($xmlobject->date != $today){
					$update = TRUE;
					$this->clear();
				}
			}else{$update = TRUE;}
			
		}
		if($update){
			$birthday_users = $this->getBirthdayUserIDs(-1,-1,TRUE);
			
			$user_ids = array();
			foreach($birthday_users->user as $user)array_push($user_ids, $user);
			
			$xmlobject = $this->getUsersList($user_ids);
			$this->saveToCache(__METHOD__, $this->send(), false, 0);//save all users to cache and apply offset, limit later on
		}
		
		//apply offset, limit:
		$users = $xmlobject->user;
		$total = count($users);
		$i = $offset+$limit;
		$j = count($users);
		$xmlobject->addChild('total',$j);
		$xmlobject->addChild('date', $today);
		while($j-->$i)unset($users[$j]);
		while($offset-->0)unset($users[$offset]);
		return $xmlobject;
	}
	public function getRandomBirthdayUserId(){
		$force_update = FALSE;//set to TRUE to reset cache
		$update = $force_update;
		$limit = 10;
		$refresh_interval = 600; //in seconds
		$now = time();
		if(!$force_update){
			if($xmlstr = $this->getFromCache(__METHOD__)){
				$xmlobject = $this->getXMLObject($xmlstr);
				if((int)$xmlobject['timestamp'] < $now-$refresh_interval){
					$update = TRUE;
					$this->clear();
				}
			}else{$update = TRUE;}
		}
		if($update){
			$birthday_users = $this->getBirthdayUserIDs(0,$limit);
			$this->clear();
			$xmlobject = $this->getXMLObject();
			$current = 0;
			$user_id=NULL;
			$count = $birthday_users->count();
			$random = rand(0, $count-1);
			foreach($birthday_users->user as $user){
				if($current==$random){
					$user_id = $user;
					break;
				}else{
					$current++;
				}
			}
			if($user_id != NULL){
				$xmlobject->addAttribute('timestamp', $now);
				$xmlobject->addChild('user_id', $user_id);
			}
			$this->saveToCache(__METHOD__, $this->send(), false, 0);
		}else{
			$user_id = $xmlobject->user_id;
		}
		return $user_id;		
	}
	public function getUsersList($user_ids){
		$this->clear();
		$xmlobject = $this->getXMLObject();
		$query = 'SELECT user_id, user_name, registration_date, photo_count, favorite_count FROM users WHERE user_id IN('.implode(',',$user_ids).') ORDER BY favorite_count/photo_count DESC'; 
		$result = DB::query($query);
		if($result){
			while($row = DB::fetchAssoc($result)){
				$user = $xmlobject->addChild('user');
				$user->addChild('id',$row['user_id']);
				$user->addChild('name',htmlspecialchars($row['user_name'],ENT_QUOTES,'UTF-8'));
				$user->addChild('photo_count',$row['photo_count']);
				$user->addChild('favorite_count',$row['favorite_count']);
				$user->addChild('registration_date',$row['registration_date']);
				$user->addChild('avatar_url', AVATARS_URL.$row['user_id'].'.jpg');//store for xhr
				$user->addChild('time_registered', Utils::dateDiff(strtotime($row['registration_date'])));//store for xhr
			}
		}else $this->throwError(8);
		return $xmlobject;	
	}
	public function getUsersByLocation($city_name=NULL,$country_code, $offset=0, $limit=50){
		$xmlobject = $this->getXMLObject();
		if(isset($country_code) && mb_strlen($country_code)>0){
			$offset = min(1000-$limit , max(0,(int)$offset));
			$limit = min(50, max(0,(int)$limit));
			$country_code = DB::escape($country_code);
			$city_filter = '';
			if($city_name!=NULL){
				require_once CLASS_PATH.'City.class.php';
				$city = new City();
				$city_UNI = $city->getCityUNIByName($city_name,$country_code);
				$city_filter = ' AND UNI='.(int)$city_UNI;
			} 
			$query = 'SELECT users.user_id FROM users WHERE users.country_code = \''.$country_code.'\''.$city_filter.' ORDER BY users.photo_count DESC LIMIT '.$offset.','.$limit;
			if($result = DB::query($query)){
				$user_ids = array();
				while ($row = DB::fetchAssoc($result)) array_push($user_ids, $row['user_id']);
				if(count($user_ids)>0){
					$query2 = 'SELECT users.user_id, users.user_name, users.photo_count, users.registration_date, cities.FULL_NAME_ND, countries.country_name FROM users 
								LEFT JOIN cities ON users.UNI = cities.UNI 
								LEFT JOIN countries ON cities.cc1 = countries.country_code
								WHERE user_id IN('.implode(',',$user_ids).') 
								ORDER BY users.photo_count DESC';
					if($result = DB::query($query2)){
						$num_rows = DB::numRows($result);
						$xmlobject['total_users'] = $num_rows;
	
						while($row = DB::fetchAssoc($result)){
							$user = $xmlobject->addChild('user');
							$user->addChild('id', $row['user_id']);
							$user->addChild('name', htmlspecialchars($row['user_name']));
							$user->addChild('photo_count', (int)$row['photo_count']);
							$user->addChild('registration_date', $row['registration_date']);
							if($city = $row['FULL_NAME_ND']) $user->addChild('city_name', $city);
						}
						if($offset+$num_rows>=$limit){
							if($result_count = DB::query('SELECT count(0) FROM users WHERE country_code = \''.$country_code.'\''.$city_filter))$xmlobject['total_users'] = DB::result($result_count, 0);
						}

					}else $this->throwError(8);
				}else $this->throwError(2);
			}else $this->throwError(8);
		}else $this->throwError(3);
		return $xmlobject;
	}
	public function getProfile(){
		$xml = $this->getXMLObject();
		if($uid = $this->access->getUserId()){
			$query = 'SELECT		
				users.user_name,
				users.email,
				users.country_code,
				users.photogear,
				users.date_of_birth,
				users.about,
				users.photo_count,
				users.blog_post_count,
				users.newsletter,
				users.anonymous,
				users.notify_comments,
				users.public_favorites,
				users.exif,
				cities.UFI,
				cities.FULL_NAME_ND  
				FROM users 
				LEFT JOIN cities ON users.UNI = cities.UNI 
				WHERE users.user_id = '.(int)$uid;
			$result = DB::query($query);
			if($result && DB::numRows($result) == 1){
				$row = DB::fetchAssoc($result);
				$xml->addChild('id',$uid);
				$xml->addChild('name',htmlspecialchars($row['user_name'],ENT_QUOTES));
				$xml->addChild('email',htmlspecialchars($row['email']));
				$xml->addChild('country_code',$row['country_code']);
				$xml->addChild('city_name',htmlspecialchars($row['FULL_NAME_ND'],ENT_QUOTES));
				$xml->addChild('city_id',$row['UFI']);//TRICKY: updateProfile requires UFI
				$xml->addChild('photogear',htmlspecialchars($row['photogear'],ENT_QUOTES));
				$xml->addChild('date_of_birth',$row['date_of_birth']);
				$xml->addChild('about',htmlspecialchars($row['about'],ENT_QUOTES));
				$xml->addChild('photo_count',$row['photo_count']);
				$xml->addChild('blog_post_count',$row['blog_post_count']);
				$xml->addChild('newsletter',$row['newsletter']);
				$xml->addChild('anonymous',$row['anonymous']);
				$xml->addChild('notify_comments',$row['notify_comments']);
				$xml->addChild('public_favorites',$row['public_favorites']);
				$xml->addChild('exif',$row['exif']);
			}else $this->throwError(2);
		}else $this->throwError(1);
		return $xml;
	}
	public function getProfileByName($name=NULL){
		$xmlobject = $this->getXMLObject();
		$query = 'SELECT users.user_id, user_name, about, email, anonymous, photogear, camera, awards, public_favorites, date_of_birth, registration_date, users.UNI, users.photo_count, users.favorite_count, users.blog_post_count, ambassadors.language_code, cities.FULL_NAME_ND, cities.LATI, cities.LONGI, countries.country_name, users.country_code FROM users LEFT JOIN ambassadors ON users.user_id = ambassadors.user_id LEFT JOIN cities ON users.UNI = cities.UNI LEFT JOIN countries ON users.country_code = countries.country_code WHERE user_name = \''.DB::escape($name).'\' LIMIT 0,1';
		$result = DB::query($query);
		if($result){
			if(DB::numRows($result) == 1){
				$row = DB::fetchAssoc($result);
				$xmlobject->addChild('id', $row['user_id']);
				$xmlobject->addChild('name', htmlspecialchars($row['user_name']));
				if($city = $row['FULL_NAME_ND']){
					$xmlobject->addChild('city_id', $row['UNI']);
					$xmlobject->addChild('city_name', htmlspecialchars($city));
					$xmlobject->addChild('longitude', $row['LONGI']);
					$xmlobject->addChild('latitude', $row['LATI']);
				}
				if($country = $row['country_name']) $xmlobject->addChild('country_name', htmlspecialchars($country));
				if($cc = $row['country_code']) $xmlobject->addChild('country_code', htmlspecialchars($cc));
				if($row['anonymous']==0)$xmlobject->addChild('email', $row['email']);
				$xmlobject->addChild('camera', $row['camera']);
				$xmlobject->addChild('photo_count', $row['photo_count']);
				$xmlobject->addChild('favorite_count', $row['favorite_count']);
				$xmlobject->addChild('blog_post_count', $row['blog_post_count']);
				$xmlobject->addChild('public_favorites', $row['public_favorites']);
				if(isset($row['photogear']))$xmlobject->addChild('photogear', htmlspecialchars($row['photogear']));
				if(isset($row['date_of_birth']))$xmlobject->addChild('date_of_birth', $row['date_of_birth']);
				$xmlobject->addChild('registration_date', $row['registration_date']);
				if(isset($row['about']))$xmlobject->addChild('about', htmlspecialchars($row['about']));
				if(isset($row['language_code']))$xmlobject->addChild('ambassador', htmlspecialchars($row['language_code']));
				if(isset($row['awards'])) $this->addAwardsXML($xmlobject, $row['awards']);
			}else $this->throwError(2);
		}else $this->throwError(8);
		return $xmlobject;
	}
	
	public function getFeaturedUser($user_id){
		$user_id = (int)$user_id;
		$photo = ClassFactory::create('Photo');
		$user_xml = $this->getXMLObject('<user></user>');
		if($result = DB::query('SELECT users.user_id, users.user_name, users.awards, ambassadors.language_code FROM users LEFT JOIN ambassadors ON users.user_id = ambassadors.user_id WHERE users.user_id='. $user_id)){
			
			if(DB::numRows($result) == 1){
				$row = DB::fetchAssoc($result);
				$user_xml->addChild('user_id',(int)$user_id);
				$user_xml->addChild('user_name',htmlspecialchars($row['user_name'],ENT_QUOTES,'UTF-8'));
				if(isset($row['language_code']))$user_xml->addChild('ambassador', htmlspecialchars($row['language_code']));
				

				if($result2 = DB::query('SELECT photos.photo_id, photos.alt_text, photos.seo_suffix, cities.FULL_NAME_ND AS city_name, countries.country_name FROM photos INNER JOIN cities ON photos.city_id=cities.UNI INNER JOIN countries ON cities.CC1=countries.country_code WHERE photo_id = (SELECT photos.photo_id FROM photos WHERE photos.user_id ='.$user_id.' ORDER BY photos.average_rate DESC LIMIT 0 , 1)')){
				
					if(DB::numRows($result2) == 1){
						$row2 = DB::fetchAssoc($result2);
						$pid = (int)$row2['photo_id'];
						$user_xml->addChild('featured_photo_id',$pid);
						$user_xml->addChild('thumb_url',Utils::getPhotoUrl($user_id,$pid,'thumb',''));
						$user_xml->addChild('photo_url',Utils::getPhotoUrl($user_id,$pid,'medium','',$row2['seo_suffix']));
						$user_xml->addChild('alt_text',htmlspecialchars($row2['alt_text'],ENT_QUOTES,'UTF-8'));
						$user_xml->addChild('city_name', htmlspecialchars($row2['city_name'], ENT_QUOTES, 'UTF-8'));
						$user_xml->addChild('country_name', $row2['country_name']);
					}
				}

				$this->addAwardsXML($user_xml, $row['awards']);
			}else $this->throwError(8);
		}else $this->throwError(8);	
		return $user_xml;
	}

	public function addAwardsXML($xml, $awards_serialized){
		if($awards_serialized != ''):
			$awards = unserialize($awards_serialized);
			foreach($awards as $award):
				$a = $xml->addChild('user_award');
				$k = array_keys($award);
				$v = array_values($award);
				$a->addAttribute('category_id',$k[0]);
				$a->addAttribute('date', $v[0]);
			endforeach;
		endif;
	}
	
	public function updatePhotoCount($uid){
		$xmlobject = $this->getXMLObject();
		$uid = (int)$uid;
		if(!DB::query('UPDATE users SET photo_count = (SELECT count(0) FROM photos WHERE user_id='.$uid.') WHERE user_id='.$uid))$this->throwError(8);
		return $xmlobject;
	}
	public function updateProfile(
		$user_name='',
		$email='',
		$newsletter=1,
		$anonymous=1,
		$notify_comments=1,
		$public_favorites=0,
		$exif=1,
		$avatar_file,
		$country_code=NULL,
		$UFI=NULL,
		$photogear=NULL,
		$date_of_birth=NULL,
		$about=NULL){

		$xmlobject = $this->getXMLObject();
		$city = null;
		$updates = array();
		
		if($uid = $this->access->getUserId()){
			//user_name::
			if($user_name != $this->access->getUserName()){
				if(Utils::isValidUserName($user_name)){
					$un = DB::escape($user_name);
					$result = DB::query('SELECT user_name FROM users WHERE user_name = \''.$un.'\' AND user_id <> '.(int)$uid);
					if($result && DB::numRows($result) > 0) $this->throwError(4);
					else $updates['user_name'] = $un;
				}else $this->throwError(15);
			}
			//email::
			if(mb_strlen(trim($email))>0){
				$em = DB::escape($email);
				$result = DB::query('SELECT email FROM users WHERE email = \''.$em.'\' AND user_id <> '.(int)$uid);
				if($result && DB::numRows($result) > 0) $this->throwError(5);
				else $updates['email'] = $em;
			}else $this->throwError(3);

			//avatar::
			$img = new Image($avatar_file);
			$img->output(MAX_AVATAR_WIDTH, MAX_AVATAR_HEIGHT, AVATARS_RELATIVE_PATH.$uid.'.jpg');
			$img->destroy();
			$updates['UNI'] = NULL;
			if(mb_strlen($UFI)>0){
				$city = ClassFactory::create('City');
				$xml_city = $city->getCityByUFI($UFI);
				if($UNI = $xml_city->UNI)$updates['UNI'] = $UNI;
			}

			$updates['newsletter'] = (int)$newsletter;
			$updates['anonymous'] = (int)$anonymous;
			$updates['notify_comments'] = (int)$notify_comments;
			$updates['public_favorites'] = (int)$public_favorites;
			$updates['exif'] = (int)$exif;
			$updates['country_code'] = mb_strlen($country_code)==0?NULL:DB::escape($country_code);		
			$updates['photogear'] = mb_strlen($photogear)==0?NULL:Utils::filterText($photogear);
			$updates['date_of_birth'] = mb_strlen($date_of_birth)==0?NULL:DB::escape($date_of_birth);
			$updates['about'] = mb_strlen($about)==0?NULL:Utils::filterText($about);
			
			$query = 'UPDATE users SET ';
			$flag = false;
			foreach($updates as $k=>$v){
				if($flag)$query .= ',';
				$query .= $k.'='.(is_null($v)?'NULL':'\''.$v.'\'');
				$flag = true;
			}
			
			if(mb_strlen($date_of_birth)>0)$query .= ',day_of_birth=DATE_FORMAT(\''.DB::escape($date_of_birth).'\' , \'%m-%d\')';
			else $query .= ',day_of_birth=NULL';
			$query .= ' WHERE user_id='.$uid;
			if(!DB::query($query)) $this->throwError(6);
			else{
				if(isset($updates['user_name']) || isset($updates['email'])){
					$un = isset($updates['user_name'])?$user_name:$this->access->getUserName();
					$em = isset($updates['email'])?$email:$this->access->getUserEmail();
					$this->access->setSessionVars($un, $uid, $email, false);
				}
				if(isset($updates['user_name'])){
					//TRICKY: this can be slow queries if the user has 50.000+ comments
					DB::query('UPDATE LOW_PRIORITY photo_comments SET poster_name = \''.$updates['user_name'].'\' WHERE poster_id='.(int)$uid);
					DB::query('UPDATE LOW_PRIORITY blog_comments SET user_name = \''.$updates['user_name'].'\' WHERE user_id='.(int)$uid);
				}
			}
		}else $this->throwError(1);
		return $xmlobject;
	}
	public function addToFavorites($fid){
		$xmlobject = $this->getXMLObject();
		$fid = (int)$fid;
		if($fid>0){
			if($uid = $this->access->getUserId()){
				$result = DB::query('INSERT IGNORE INTO favorite_users (user_id, favorite_user_id, favorite_date_lookup) VALUES ('.(int)$uid.','.$fid.',\''.date('Ymd').'\')');
				if($result){
					if(DB::affectedRows()==1)DB::query('UPDATE users SET favorite_count = (SELECT COUNT(0) FROM favorite_users WHERE favorite_user_id = '.$fid.') WHERE user_id = '.$fid);
					else $this->throwError(12);
				}
			}else $this->throwError(1);
		}else $this->throwError(10);
		return $xmlobject;
	}
	public function removeFromFavorites($fid){
		$xmlobject = $this->getXMLObject();
		$fid = (int)$fid;
		if($fid>0){
			if($this->access->isSecureLoggedIn()){
				if($uid = $this->access->getUserId()){
					$result = DB::query('DELETE FROM favorite_users WHERE user_id='.(int)$uid.' AND favorite_user_id='.$fid);
					if($result){
						if(DB::affectedRows()==1)DB::query('UPDATE users SET favorite_count = (SELECT COUNT(0) FROM favorite_users WHERE favorite_user_id = '.$fid.') WHERE user_id = '.$fid);
						else $this->throwError(8);
					}
				}else $this->throwError(1);
			}else $this->throwError(1);
		}else $this->throwError(10);
		return $xmlobject;
	}
	public function getFavoritesByUserId($user_id, $offset=0, $limit=50, $orderby=''){
	//TRICKY: favorites can be private, be sure to check this before calling this method!
		$this->clear();
		$xmlobject = $this->getXMLObject();
		$uid = (int)$user_id;
		if($uid>0){
			$offset = min(1000-$limit , max(0,(int)$offset));
			$limit = min(50, max(0,(int)$limit));
			$query = 'SELECT users.user_id, users.user_name, users.photo_count, users.last_upload_date, cities.FULL_NAME_ND, countries.country_name FROM favorite_users 
			INNER JOIN users ON favorite_user_id = users.user_id 
			LEFT JOIN cities ON users.UNI = cities.UNI 
			LEFT JOIN countries ON cities.cc1 = countries.country_code
			WHERE favorite_users.user_id = '.$uid.' ORDER BY favorite_id DESC
			LIMIT '.$offset.','.$limit;
			$result = DB::query($query);
			if($result){
				$num_rows = DB::numRows($result);
				$xmlobject['total_users'] = $num_rows;
				if($num_rows>0){
					while($row = DB::fetchAssoc($result)){
						$user = $xmlobject->addChild('user');
						$user->addChild('id', $row['user_id']);
						$user->addChild('name', $row['user_name']);
						if($city = $row['FULL_NAME_ND']) $user->addChild('city_name', $city);
						if($country = $row['country_name']) $user->addChild('country_name', $country);
						$user->addChild('photo_count', (int)$row['photo_count']);
						if($date = $row['last_upload_date']) $user->addChild('last_upload_date', $date);
					}
					if($offset+$num_rows>=$limit){
						$result_count = DB::query('SELECT count(0) FROM favorite_users WHERE favorite_users.user_id = '.$uid);
						if($result_count) $xmlobject['total_users'] = DB::result($result_count, 0);
					}
				}
			}else $this->throwError(8);
		}else $this->throwError(1);
		return $xmlobject;
	}
	public function getFavorites($offset=0, $limit=10){/*get favorite members of logged in user*/
		$XMLObject = $this->getXMLObject();
		$uid = $this->access->getUserId();
		if(isset($uid))return $this->getFavoritesByUserId($uid, $offset, $limit);
		else $this->throwError(1);
		return $xmlobject;
	}
	public function getLatestFans($user_id=NULL, $offset=0, $limit=5, $parentNode=NULL, $show_labels=FALSE){
		$XMLObject = $this->getXMLObject();
		if($parentNode!=NULL){
			$XMLObject = $parentNode;
			$nodeName = 'item';
		}else{
			$XMLObject = $this->getXMLObject();
			$nodeName = 'user';
		}
		if($user_id == NULL) $user_id = $this->access->getUserId();
		if($user_id){
			$offset = max(0,(int)$offset);
			$limit = min(20,max(0,(int)$limit));
			$query = 'SELECT user_id FROM favorite_users WHERE favorite_user_id ='.(int)$user_id.' LIMIT '.(int)$offset.', '.$limit;
			//$query = 'SELECT user_id FROM favorite_users WHERE favorite_user_id ='.(int)$user_id.' AND favorite_users.user_id <> favorite_users.favorite_user_id LIMIT '.(int)$offset.', '.$limit;//possible slow query with a lot of fans
			$result = DB::query($query);
			if($result){
				$user_ids = array();
				while ($row = DB::fetchAssoc($result)) array_push($user_ids, $row['user_id']);
				if(count($user_ids)>0){
					$query2 = 'SELECT users.user_id, users.user_name, users.photo_count, users.last_upload_date, users.registration_date, favorite_users.favorite_date, cities.UNI, cities.FULL_NAME_ND, countries.country_name
					FROM users 
					INNER JOIN favorite_users ON users.user_id = favorite_users.user_id AND favorite_users.favorite_user_id = '.(int)$user_id.' 
					LEFT JOIN cities ON users.UNI = cities.UNI 
					LEFT JOIN countries ON cities.cc1 = countries.country_code
					WHERE users.user_id IN ('. implode(',', $user_ids).') ORDER BY favorite_users.favorite_date DESC';
					$result = DB::query($query2);
					if($result){
						$num_rows = DB::numRows($result);
						$XMLObject['total_users'] = $num_rows;
						if($num_rows>0){
							while($row = DB::fetchAssoc($result)){
								$user = $XMLObject->addChild($nodeName);
		                        if($parentNode!=NULL){//for notification widget
		                        	$user->addAttribute('type', 'fan'); 
		                        	$user->addAttribute('timestamp', strtotime($row['favorite_date']));
		                        	if($show_labels) $user->addChild('label', 'New fan');
								} 
								$user->addChild('id', $row['user_id']);
								$user->addChild('name',htmlspecialchars($row['user_name'], ENT_QUOTES));
								$user->addChild('photo_count', (int)$row['photo_count']);
								$user->addChild('date', $row['favorite_date']);
								$user->addChild('registration_date', $row['registration_date']);
								$user->addChild('city_id', $row['UNI']);
								$user->addChild('city_name', $row['FULL_NAME_ND']);
								$user->addChild('country_name', $row['country_name']);
								if($date = $row['last_upload_date']) $user->addChild('last_upload_date', $date);//if not null
							}
							if($offset+$num_rows>=$limit){
								$result_count = DB::query('SELECT count(0) FROM favorite_users WHERE favorite_user_id = '.(int)$user_id);
								if($result_count) $XMLObject['total_users'] = DB::result($result_count, 0);
							}
						}else{
							$item = $XMLObject->addChild('item');
							$item->addAttribute('type', 'error');
							$item->addAttribute('timestamp', '-1');
							$item->addChild('message', 'Sorry, you have no fans yet!');
		                    if($show_labels) $item->addChild('label', 'New fan');
						}
					}else $this->throwError(8);
				}
			}else $this->throwError(8);
		}
		return $XMLObject;
	}
	public function getComments($offset=0, $limit=20){/* get last comments of logged in user */
		$xmlobject = $this->getXMLObject();
		if($uid = $this->access->getUserId()){
			$offset = max(0,(int)$offset);
			$limit = min(20,max(0,(int)$limit));
			$query = 'SELECT comment_id FROM photo_comments WHERE poster_id ='.(int)$uid.' ORDER BY comment_id DESC LIMIT '.(int)$offset.', '.$limit;
			$result = DB::query($query);
			if($result){
				$ids = array();
				while($row = DB::fetchAssoc($result))$ids[] = $row['comment_id'];
				if(count($ids)>0){
					$result = DB::query('SELECT photo_comments.comment_id, photo_comments.comment_text, photo_comments.photo_id, photo_comments.comment_date, photo_comments.poster_name, photo_comments.user_id, photos.comment_count FROM photo_comments INNER JOIN photos ON photo_comments.photo_id = photos.photo_id WHERE comment_id IN('. (implode(',',$ids)) .') ORDER BY comment_id DESC');
					if($result){
						$num_rows = DB::numRows($result);
						$xmlobject['total_comments'] = $num_rows;
						if($num_rows>0){		
							while($row = DB::fetchAssoc($result)){
								$comment = $xmlobject->addChild('comment');
								$comment->addChild('id', $row['comment_id']);
								$comment->addChild('text',htmlspecialchars($row['comment_text'], ENT_QUOTES));
								$comment->addChild('comment_count', $row['comment_count']);
								$comment->addChild('photo_id', $row['photo_id']);
								$comment->addChild('user_id', $row['user_id']);
								$comment->addChild('date', $row['comment_date']);
							}
							if($offset+$num_rows>=$limit){
								$result_count = DB::query('SELECT count(0) FROM photo_comments WHERE poster_id = '.$uid);
								if($result_count) $xmlobject['total_comments'] = DB::result($result_count, 0);
							}
						}
					}else $this->throwError(8);
				}
			}else $this->throwError(8);
		}else $this->throwError(1);
		return $xmlobject;
	}
	public function getAmbassadors(){
		if(!($xmlstr = $this->getFromCache(__METHOD__))){
			$xmlobject = $this->getXMLObject();
			$result = DB::query('SELECT c.country_name, cities.FULL_NAME_ND AS city_name, a.user_id, u.user_name, u.registration_date, l.language_code, l.language_name, l.language_name_native, u.country_code FROM ambassadors a INNER JOIN users u ON a.user_id = u.user_id INNER JOIN languages l ON a.language_code = l.language_code LEFT JOIN cities ON u.UNI = cities.UNI LEFT JOIN countries c ON u.country_code = c.country_code ORDER BY l.language_name LIMIT 0 , 500');	
			if($result){
				while($row = DB::fetchAssoc($result)){
					$user = $xmlobject->addChild('user');
					$user->addChild('id', $row['user_id']);
					$user->addChild('name', htmlspecialchars($row['user_name']));
					$user->addChild('language_code', htmlspecialchars($row['language_code']));
					$user->addChild('language_name', htmlspecialchars($row['language_name']));
					$user->addChild('language_name_native', htmlspecialchars($row['language_name_native']));
					if($country = $row['country_name']){
						$user->addChild('country_name', htmlspecialchars($country));
						$user->addChild('country_code', $row['country_code']);
					}
					if($city = $row['city_name']) $user->addChild('city_name', htmlspecialchars($city));
					$user->addChild('registration_date', $row['registration_date']);
				}
				$this->saveToCache(__METHOD__, $this->send(), false, 0);
			}else $this->throwError(8);
		}else $xmlobject = $this->getXMLObject($xmlstr);
		return $xmlobject;
	}
	public function sendAmbassadorMessage($user_id=0, $from_email='', $message=''){
		$message = 'You received this message because you are registered as a Woophy Ambassador:'.PHP_EOL.PHP_EOL.$message;
		return $this->sendMessage($user_id, $from_email, 'question about Woophy', $message);
	}
	public function sendMessage($user_id=0, $from_email='', $subject='', $message='', $xhr=FALSE){/*send email to other member, member has to be logged in*/
		$xmlobject = $this->getXMLObject();
		$err_code = 0;
		$ip = DB::escape(Utils::getIP());
		$result = DB::query('SELECT message_date FROM messages WHERE sender_ip = \''.$ip.'\' ORDER BY message_id DESC LIMIT 0,1');
		if($result){
			$bln_send = true;
			if(DB::numRows($result)==1){
				if(time()-strtotime(DB::result($result, 0))<self::ANTISPAM_TIME_DELAY){
					$bln_send = false;
				}
			}
			if($bln_send){
				$result_to = DB::query('SELECT email, user_name FROM users WHERE user_id = '.(int)$user_id);
				if($result_to){
					if(DB::numRows($result_to)==1){
						$to_user = DB::fetchAssoc($result_to);
						$to_email = $to_user['email'];
						$to_user_name = $to_user['user_name'];
						if($uid = $this->access->getUserId()){
							if($from_name = $this->access->getUserName()){
								$result_from = DB::query('SELECT photo_count FROM users WHERE user_id='.(int)$uid);
								if($result_from){
									if(DB::numRows($result_from)==1){//check if user still exists. In case of spamming: user has been deleted but is still able to send mail because of session cookie
										if((int)DB::result($result_from, 0)>=0){//user has uploaded more than 2 photo
											$subject = strip_tags(trim($subject));
											if(mb_strlen($subject)>0){
												$body = strip_tags(trim($message));
												if(mb_strlen($body)>0){
													include_once CLASS_PATH.'Mail.class.php';
													$mail = new Mail();
													$mail->From(EMAIL_SENDER, SUPPORT_EMAIL_ADDRESS);
													$mail->To($to_email);
													$mail->Subject($from_name.' has sent you a message: '.$subject);
													$mail->Body($body);
													if(!$mail->Send())$this->throwError(11);
													else DB::query('INSERT INTO messages SET user_id='.(int)$user_id.',sender_id='.(int)$uid.',sender_name=\''.DB::escape($from_name).'\',sender_ip=\''.$ip.'\', message_subject=\''.DB::escape($subject).'\',message_text=\''.DB::escape($body).'\'');//log
												}else $err_code = 3;
											}else $err_code = 3;
										}else $err_code = 14;
									}else $err_code =  11;
								}else $err_code = 8;
							}else $err_code = 1;
						}else $err_code = 1;
					}else $err_code = 2;
				}else $err_code = 8;
			}else $err_code = 13;
		}
		if($err_code > 0){
			if($xhr){
				return array('error_code'=>$err_code, 'error_message'=>$this->getErrorMessage($err_code));
			}else{
				header('location:'.ROOT_URL.'member/'.urldecode($to_user_name).'/send_message/?err='.$this->getErrorMessage($err_code));
			}
		}
		if(!$xhr)return $xmlobject;
		else{
			return array('message'=>'Your message has been sent.');
		}
	}

	public function getNotifications($notification_type=NOTIFICATION_ALL, $max_items_per_type=5, $show_labels=FALSE){
		$photo = ClassFactory::create('Photo');
		$XMLObject = simplexml_load_string('<notifications/>');
		if($notification_type & NOTIFICATION_PHOTO_COMMENTS){
			$photo->getComments(0,$max_items_per_type,-1, $XMLObject, $show_labels, FALSE);
		}
		if($notification_type & NOTIFICATION_PHOTO_FAVORITES){
			$photo->getFavoritesForUserId(0,$max_items_per_type, $XMLObject, $show_labels);
		}
		if($notification_type & NOTIFICATION_FAVORITE_SUBMISSIONS){
			$photo->getLatestPhotosByFavorites($max_items_per_type, $XMLObject, $show_labels);			
		}	
		if($notification_type & NOTIFICATION_FANS){
			$this->getLatestFans(NULL,0,$max_items_per_type,$XMLObject, $show_labels);
		}

		$items = $XMLObject->xpath('/notifications/item');
		usort($items, 'User::sortNotifications');

		return $items;
	}

	static function sortNotifications($t1, $t2) {
   		return strcmp($t2['timestamp'], $t1['timestamp']);
	}
	protected function throwError($code=1, $msg=''){
		$msg = $this->getErrorMessage($code);
		parent::throwError(self::ERRBASE+$code, $msg);
	}
	protected function getErrorMessage($code=1){
		$msg = '';
		switch($code){
			case 1:$msg='You have to be signed in.';break;
			case 2:$msg='No record found.';break;
			case 3:$msg='Fill in all the required fields.';break;
			case 4:$msg='This nickname already exists. Try another name.';break;
			case 5:$msg='This e-mail address already exists. Try again.';break;
			case 6:$msg='Could not update your account. Try again.';break;
			case 7:$msg='Unknown city reference.';break;
			case 8:$msg='Error executing query.';break;
			case 9:$msg='Could not upload photo.';break;
			case 10:$msg='Missing user id.';break;
			case 11:$msg='Could not send e-mail.';break;
			case 12:$msg='This member is already a favorite.';break;
			case 13:$msg='You can not make more than one post every 2 min!';break;
			case 14:$msg='You are not allowed to post messages.';break;
			case 15:$msg='This nickname is not allowed.';break;
		}
		return $msg;		
	}
}
?>