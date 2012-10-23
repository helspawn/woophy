<?php
require_once CLASS_PATH.'Response.class.php';
class Status extends Response{
	
	const ERRBASE = 1100;
	const MEMBERS_KEY = 'Status::getMembersOftheMonth';
	const STATUS_KEY = 'Status::getStatus';

	public function getStatus($fromCache=true){//return status in xml format
		if(!($xmlstr = $this->getFromCache(self::STATUS_KEY)) || !$fromCache){
			$xmlobj = $this->getXMLObject();
			$result = DB::query('SELECT * FROM status WHERE status_id=1;');
//			file_put_contents(LOGS_PATH . "debug.log", print_r($result,1)."\n",FILE_APPEND);
			if($result){
				$row = DB::fetchAssoc($result);
				foreach($row as $key=>$value){
					$xmlobj->addChild($key, htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
				}
				$this->saveToCache(__METHOD__, $this->send(), false, 60);//refresh every minute
			}else $this->throwError(1);
		}else $xmlobj = $this->getXMLObject($xmlstr);
		return $xmlobj;
	}
	
	public function getMembersOftheMonth($offset=0, $limit=100){
		if(!($xmlstr = $this->getFromCache(self::MEMBERS_KEY .'::'. $offset .'::'. $limit))){
			$xmlobj = $this->getXMLObject();
			$members = array();
			$query = 'SELECT users.user_name, awards.award_date, awards.user_id FROM awards 
				INNER JOIN users ON awards.user_id = users.user_id 
				WHERE category_id=1 AND awards.award_date <= NOW() 
				ORDER BY awards.award_id DESC LIMIT '.max(0,(int)$offset).', '.min(100,max(0,(int)$limit)).';';
			$result = DB::query($query);
			if($result && DB::numRows($result)>0){
				while ($row = DB::fetchAssoc($result)) {
					$user = $xmlobj->addChild('user');
					$user->addChild('date',$row['award_date']);
					$user->addChild('id',$row['user_id']);
					$user->addChild('name',htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
				}
				$this->saveToCache(self::MEMBERS_KEY, $this->send(), false, 0);
			}else $this->throwError(1);
		}else $xmlobj = $this->getXMLObject($xmlstr);
		return $xmlobj;
	}

	function updateNumberOfCities(){
		$XMLObject = $this->getXMLObject();
		if(!DB::query('UPDATE status SET num_of_cities = (SELECT COUNT(0) FROM cities WHERE photo_count>0) WHERE status_id=1'))$this->throwError(2);
		return $XMLObject;
	}
	function updateNumberOfPhotos(){
		$XMLObject = $this->getXMLObject();
		$res1 = DB::query('SELECT COUNT(0) FROM photos WHERE DATE_SUB( NOW() , INTERVAL 24 HOUR ) < photo_date');
		if($res1){
			$numOfPhotosToday = DB::result($res1, 0);
			$res2 = DB::query('SELECT COUNT(0) FROM photos');
			if($res2){
				$numOfPhotos = DB::result($res2, 0);
				$res4 = DB::query('SELECT photos.photo_id, photos.user_id, photos.photo_date, users.user_name, photos.city_id, cities.FULL_NAME_ND, countries.country_name
				FROM photos
				LEFT JOIN cities ON photos.city_id = cities.UNI
				LEFT JOIN users ON photos.user_id = users.user_id 
				INNER JOIN countries ON cities.CC1 = countries.country_code  
				WHERE photos.photo_id = (SELECT MAX(photo_id) FROM photos)
				LIMIT 0,1;');
				if($res4){
					$row = DB::fetchAssoc($res4);
					$city = DB::escape($row['FULL_NAME_ND']);
					$id=(int)$row['photo_id'];
					$uid=(int)$row['user_id'];
					$country=$row['country_name'];
					$date=$row['photo_date'];
					$uni=(int)$row['city_id'];
					$un=DB::escape($row['user_name']);
					
					if(!DB::query("UPDATE status SET num_of_photos_today = $numOfPhotosToday, 
					num_of_photos = $numOfPhotos,
					last_photo_id = $id,
					last_photo_country = '$country',
					last_photo_uni = $uni,
					last_photo_city = '$city',
					last_photo_userid = $uid,
					last_photo_username = '$un',
					last_photo_date = '$date' WHERE status_id=1;"))$this->throwError(3);

				}else $this->throwError(2);
			}else $this->throwError(2);
		}else $this->throwError(2);
		return $XMLObject;
	}
	public function updateNumberOfUsers(){
		$XMLObject = $this->getXMLObject();
		if(!DB::query('UPDATE status SET num_of_users = (SELECT COUNT(0) FROM users) WHERE status_id=1;'))$this->throwError(2);
		return $XMLObject;
	}
	public function updateNumberOfViews(){
		$XMLObject = $this->getXMLObject();
		if(!DB::query('UPDATE status SET num_of_views = (SELECT SUM(views) FROM photos) WHERE status_id=1;'))$this->throwError(2);
		return $XMLObject;
	}
	public function updateCityOfTheDay(){
		$XMLObject = $this->getXMLObject();
		$today = date("Y-m-d");
		$query = 'SELECT cityoftheday.UNI, cities.FULL_NAME_ND, countries.country_name, countries.country_code FROM cityoftheday 
		INNER JOIN cities ON cityoftheday.UNI = cities.UNI 
		INNER JOIN countries ON cities.CC1 = countries.country_code 
		WHERE cityoftheday.date = \''.$today.'\';';
		
		$result = DB::query($query);
		if($result){
			if(DB::numRows($result)==1){
				$row = DB::fetchAssoc($result);
				$uni = $row['UNI'];
				$city = $row['FULL_NAME_ND'];
				$country = $row['country_name'];
				$country_code = $row['country_code'];
			}else{
			
				//pick random city:
				$query = 'SELECT cities.UNI FROM cities LEFT JOIN cityoftheday ON cities.UNI = cityoftheday.UNI WHERE cities.photo_count > 20 AND cityoftheday.UNI IS NULL LIMIT 0,50;';

				$result = DB::query($query);
				if($result){
					$num = DB::numRows($result);
					if($num==0){
						//ran out of cities, reset cityoftheday and start all over again:
						DB::query('TRUNCATE TABLE cityoftheday;');
						$result = DB::query($query);//pick again
						$num= DB::numRows($result);
					}
					if($num>0){
						srand(time());
						$rand = (rand()%$num);
						$uni = DB::result($result,$rand);
						$query = "SELECT cities.FULL_NAME_ND, countries.country_name, countries.country_code FROM cities INNER JOIN countries ON cities.CC1 = countries.country_code WHERE cities.UNI = $uni;";
						$result = DB::query($query);
						$row = DB::fetchAssoc($result);
						$city = $row['FULL_NAME_ND'];
						$country = $row['country_name'];
						$country_code = $row['country_code'];
						DB::query("INSERT INTO cityoftheday SET date='$today', UNI=$uni;");
					}
				}
			}
			if(isset($uni,$country,$city,$country_code)){
				$result_pid = DB::query("SELECT photos.photo_id, photos.width, photos.height, photos.user_id FROM photos WHERE photos.city_id = $uni ORDER BY photos.average_rate DESC LIMIT 0,10;");
				$pid = 'NULL';
				$uid = 0;
				if(DB::numRows($result_pid)>=1){
					$row = DB::fetchAssoc($result_pid);
					$pid = (int)$row['photo_id'];
					$uid = (int)$row['user_id'];
					$pw = $row['width'];
					$ph = $row['height'];
					if($pw<$ph){//look for landscape format for news page
						while($row = DB::fetchAssoc($result_pid)){
							if($row['width']>$row['height']){
								$pid = (int)$row['photo_id'];
								$uid = (int)$row['user_id'];
								$pw = $row['width'];
								$ph = $row['height'];
								break;
							}
						}
					}
					if(DB::query("UPDATE status SET city_of_the_day_name = '$city',city_of_the_day_country='$country',city_of_the_day_uni=$uni, city_of_the_day_pid=$pid,city_of_the_day_userid=$uid,city_of_the_day_date='$today' WHERE status_id=1;")){
						$this->saveToCache('cityoftheday', '<city><name>'.htmlspecialchars($city).'</name><country_name>'.htmlspecialchars($country).'</country_name><country_code>'.htmlspecialchars($country_code).'</country_code><user_id>'.$uid.'</user_id><photo_id>'.$pid.'</photo_id><photo_width>'.$pw.'</photo_width><photo_height>'.$ph.'</photo_height></city>', false, 90000);//25 hours
					}else $this->throwError(2);
				}else $this->throwError(3);
			}
		}
		return $XMLObject;
	}

	public function updateMemberOfTheMonth(){
		$XMLObject = $this->getXMLObject();
		$res1 = DB::query('SELECT award_date, user_id FROM awards WHERE category_id = 1 AND award_date <= CURDATE() ORDER BY award_id DESC LIMIT 0,1');
		if($res1 &&  DB::numRows($res1) == 1){
			$date = DB::result($res1,0,0);
			$uid = (int)DB::result($res1,0,1);

			$query = "SELECT photos.photo_id, users.user_name, countries.country_name 
			FROM photos 
			INNER JOIN users ON photos.user_id = users.user_id
			LEFT JOIN countries ON users.country_code = countries.country_code 
			WHERE photos.user_id = $uid 
			ORDER BY photos.average_rate DESC 
			LIMIT 0, 1";
			
			$res2 = DB::query($query);
			if($res2){
				$row = DB::fetchAssoc($res2);
				$un = $row['user_name'];
				$country = $row['country_name'];
				$pid = (int)$row['photo_id'];
				if(DB::query('UPDATE status SET motm_date=\''.$date.'\',motm_name=\''.DB::escape($un).'\',motm_country=\''.$country.'\',motm_pid='.$pid.',motm_id='.$uid.' WHERE status_id=1')){
					if(DB::affectedRows()==1)$this->deleteFromCache(self::MEMBERS_KEY);
				}else $this->throwError(3);
			}else $this->throwError(3);
		}else $this->throwError(3);
		return $XMLObject;
	}
	public function updateLastComment($last_comment,$last_comment_pid,$last_comment_name,$last_comment_user_id){
		$xml_status = $this->getStatus();
		$XMLObject = $this->getXMLObject();
		if(!isset($last_comment,$last_comment_pid,$last_comment_name,$last_comment_user_id)){
			$result = DB::query('SELECT comment_text, photo_id, user_id, poster_name FROM photo_comments WHERE comment_id = (SELECT MAX(comment_id) FROM photo_comments)');
			if($result){
				$row = DB::fetchAssoc($result);
				$last_comment = $row['comment_text'];
				$last_comment_pid = $row['photo_id'];
				$last_comment_name = $row['poster_name'];
				$last_comment_user_id = $row['user_id'];
			}
		}
		if(isset($last_comment,$last_comment_pid,$last_comment_name,$last_comment_user_id)){
			//update last photo ids:
			$last_photo_ids = array();
			$photo_ids = $xml_status->last_comment_pids;
			$limit = 20;
			$delimiter = ',';
			if(count($photo_ids)==1 && strlen($photo_ids)>0){
				$last_photo_ids = explode($delimiter, $photo_ids);
				array_unshift($last_photo_ids, $last_comment_pid);
				$last_photo_ids = array_unique($last_photo_ids);
				array_splice($last_photo_ids, $limit);
			}else{
				$result = DB::query('SELECT DISTINCT photo_id FROM photo_comments ORDER BY comment_id DESC LIMIT 0,'.$limit);
				if($result)while($row = DB::fetchAssoc($result))$last_photo_ids[]=$row['photo_id'];
			}
			if(!DB::query('UPDATE status SET last_comment=\''.DB::escape($last_comment).'\',last_comment_pid='.(int)$last_comment_pid.',last_comment_name=\''.DB::escape($last_comment_name).'\',last_comment_userid='.(int)$last_comment_user_id.',last_comment_pids = \''.implode($delimiter, $last_photo_ids).'\' WHERE status_id=1'))$this->throwError(3);
		}else $this->throwError(3);
		return $XMLObject;
	}
	public function updateLastBlogPost($title=NULL, $name=NULL){
		$XMLObject = $this->getXMLObject();
		if(!isset($title, $name)){
			$result = DB::query('SELECT post_id FROM blog_posts WHERE post_status = \'published\' AND post_publication_date <= NOW() ORDER BY post_publication_date DESC LIMIT 0,1');
			if($result){
				$pid = DB::result($result, 0);
				if($result2 = DB::query('SELECT blog_posts.post_title, users.user_name FROM blog_posts INNER JOIN users ON blog_posts.user_id = users.user_id WHERE post_id = '.(int)$pid)){
					$row = DB::fetchAssoc($result2);
					$title = $row['post_title'];
					$name = $row['user_name'];
				}
			}
		}
		if(isset($title, $name)){
			if(!DB::query('UPDATE status SET last_blog_post=\''.DB::escape($title).'\', last_blog_user_name=\''.DB::escape($name).'\' WHERE status_id=1')) $this->throwError(3);
		}
		return $XMLObject;
	}
	/*
	public function updateLastTravelBlogPost($title, $name){
		$XMLObject = $this->getXMLObject();
		if(!DB::query('UPDATE status SET last_travelblog_post=\''.DB::escape($title).'\', last_travelblog_name=\''.DB::escape($name).'\' WHERE status_id=1')) $this->throwError(3);
		return $XMLObject;
	}*/
	protected function throwError($code=1, $msg=''){
		switch($code){
			case 1:$msg='No records found';break;
			case 2:$msg='Error executing query';break;
			case 3:$msg='Could not update status';break;
		}
		parent::throwError(self::ERRBASE+$code, $msg);
	}
}
