<?php
require_once CLASS_PATH.'Response.class.php';
require_once CLASS_PATH.'Image.class.php';
require_once CLASS_PATH.'City.class.php';
require_once CLASS_PATH.'User.class.php';
require_once CLASS_PATH.'Status.class.php';
class Photo extends Response{
	
	private $info;
	private $comments;
	private $access;
	private $city;
	private $status;

	const ERRBASE = 300;
	const ANTISPAM_TIME_DELAY = 20;//seconds
	const MAX_NUM_TAGS = 50;

	public function __construct() {
		$this->access = ClassFactory::create('Access');
		$this->status = new Status();
		$this->city = new City();
		$this->user = new User();
		parent::__construct();
	}

	public function isEditorsPick($pid){
		$pid = (int)$pid;
		if($pid>0){
			$uid = $this->access->getUserId();
			if(isset($uid)){
				$result = DB::query('SELECT 0 FROM editors_picks WHERE photo_id = '.(int)$pid.' AND user_id = '.(int)$uid.' LIMIT 0,1');
				if($result && DB::numRows($result)==1)return true;
			}
		}
		return false;
	}
	public function addEditorsPick($pid){
		$XMLObject = $this->getXMLObject();
		$pid = (int)$pid;
		if($pid>0){
			$uid = $this->access->getUserId();
			if(isset($uid)){
				$result = DB::query('INSERT IGNORE INTO editors_picks (user_id, photo_id, city_id) VALUES ('.(int)$uid.','.$pid.', (SELECT city_id FROM photos WHERE photo_id='.$pid.'))');
			}else $this->throwError(8);
		}else $this->throwError(1);
		return $XMLObject;
	}
	public function removeEditorsPick($pid){
		$XMLObject = $this->getXMLObject();
		$pid = (int)$pid;
		if($pid>0){
			$uid = $this->access->getUserId();
			if(isset($uid)){
				$result = DB::query('DELETE FROM editors_picks WHERE user_id='.(int)$uid.' AND photo_id='.$pid);
			}else $this->throwError(8);
		}else $this->throwError(1);
		return $XMLObject;
	}
	public function getRecentEditorsPicksFromCache($limit=6, $widget=FALSE){
		$cid = __METHOD__.'::'.$limit.($widget?'::W':'');
		if($xmlstr = $this->getFromCache($cid)) $XMLObject = $this->getXMLObject($xmlstr);
		else{
			$XMLObject = $this->getRecentEditorsPicks(0,$limit,0,$widget);
			if(!$XMLObject->err){
				$this->saveToCache($cid, $this->send(), false, 10800);//cache for 3 hours
			}
		}
		return $XMLObject;
	}
	public function getRecentEditorsPicks($offset=0, $limit=10, $total=0, $widget=FALSE){
		
		$limit = min(20, max(0,(int)$limit));
		$offset = min(1000-$limit, max(0,(int)$offset));
		$this->buffer = false;
		$XMLObject = $this->getXMLObject();
		$query = 'SELECT photo_id FROM editors_picks ORDER BY pick_date DESC LIMIT '.$offset.','.$limit;
		$result = DB::query($query);
		if($result){
			$ids = array();
			while($row = DB::fetchAssoc($result)) array_push($ids, $row['photo_id']);
			if(count($ids)>0){
				$query2 = 'SELECT photos.photo_id, photos.average_rate, photos.comment_count, photos.favorite_count, photos.user_id, photos.photo_date, photos.alt_text, photos.seo_suffix, editors_picks.pick_date, users.user_name, cities.FULL_NAME_ND AS city_name, countries.country_name 
							FROM photos 
							INNER JOIN editors_picks ON editors_picks.photo_id=photos.photo_id 
							INNER JOIN users ON photos.user_id=users.user_id 
							INNER JOIN cities ON photos.city_id=cities.UNI 
							INNER JOIN countries ON cities.CC1=countries.country_code 
							WHERE photos.photo_id IN ('.implode(',',array_unique($ids)).')';	
				if($result = DB::query($query2)){
					
					if(DB::numRows($result)>0){
						$photos = array();
						while($row = DB::fetchAssoc($result))$photos[$row['photo_id']] = $row;
						//loop ids to preserve order:
						foreach($ids as $id){
							$row = $photos[$id];
							if($widget){
								$photo = $XMLObject->addChild('item');
								$photo->addAttribute('type', 'editorspick');
								$photo->addAttribute('timestamp', strtotime($row['photo_date']));
							}else{
								$photo = $XMLObject->addChild('photo');
							}
							$photo->addChild('id',$row['photo_id']);
							$photo->addChild('average_rate',$row['average_rate']);
							$photo->addChild('comment_count',$row['comment_count']);
							$photo->addChild('favorite_count',$row['favorite_count']);
							$photo->addChild('user_id',$row['user_id']);
							$photo->addChild('user_name',$row['user_name']);
							$photo->addChild('city_name',$row['city_name']);
							$photo->addChild('country_name',$row['country_name']);
							$photo->addChild('date',$row['photo_date']);
							$photo->addChild('pick_date',$row['pick_date']);
							$photo->addChild('alt_text',$row['alt_text']);
							$photo->addChild('photo_url',Utils::getPhotoUrl((int)$row['user_id'], $row['photo_id'], 'medium', '', $row['seo_suffix']));
							
						}
					}//do not report an error
				}else $this->throwError(7);
			}//do not report an error
			
			if(!isset($total)){
				if($result = DB::query('SELECT COUNT(0) FROM editors_picks')){
					if(DB::numRows($result)==1)$XMLObject['total'] = DB::result($result, 0);
				}
			}
		}else $this->throwError(7);
		return $XMLObject;
	}
	public function getEditorsPicks($city_name, $country_code=NULL, $limit=10){
		$XMLObject = $this->getXMLObject();
		if(isset($city_name) && mb_strlen(trim($city_name))>0){
			$sql = '';
			if(isset($country_code) && mb_strlen(trim($country_code))>0) $sql = ' AND CC1=\''.DB::escape($country_code).'\'';
			$result = DB::query('SELECT UNI FROM cities WHERE FULL_NAME_ND = \''.DB::escape($city_name).'\''.$sql.' AND photo_count>0 LIMIT 0,1');
			if($result){
				if(DB::numRows($result)==1){
					$uni = DB::result($result, 0);
					$limit = max(0, min(100, (int)$limit));
					//$result = DB::query('SELECT DISTINCT editors_picks.photo_id FROM editors_picks WHERE city_id = '.$uni.' LIMIT 0, '.$limit);
					//temporary using photos with rating instead of editors picks:
					$result = DB::query('SELECT photo_id FROM photos WHERE city_id = '.$uni.' ORDER BY average_rate DESC LIMIT 0, '.$limit);

					if($result){
						$ids = array();
						while($row = DB::fetchAssoc($result))$ids[] = $row['photo_id'];
						if(count($ids)>0){
							$result = DB::query('SELECT countries.country_name, photos.photo_id, photos.alt_text, photos.seo_suffix, cities.FULL_NAME_ND AS city_name, users.user_id, users.user_name 
							FROM photos 
							INNER JOIN cities ON photos.city_id = cities.UNI 
							INNER JOIN countries ON cities.CC1 = countries.country_code 
							LEFT JOIN users ON photos.user_id = users.user_id 
							WHERE photos.photo_id IN('.implode(',',$ids).')');
							if($result){
								while($row = DB::fetchAssoc($result)){
									$photo = $XMLObject->addChild('photo');
									$pid = (int)$row['photo_id'];
									$photo->addChild('id', $pid);
									$photo->addChild('url',Utils::getPhotoUrl((int)$row['user_id'], $pid, 'thumb', '', $row['seo_suffix']));
									$photo->addChild('alt_text', htmlspecialchars($row['alt_text'], ENT_QUOTES, 'UTF-8'));
									$photo->addChild('user_name',htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
									$photo->addChild('city_name', htmlspecialchars($row['city_name'], ENT_QUOTES, 'UTF-8'));
									$photo->addChild('country_name', $row['country_name']);
								}
							}else $this->throwError(7);
						}else $this->throwError(2);
					}else $this->throwError(7);
				}else $this->throwError(2);
			}else $this->throwError(7);
		}else $this->throwError(16);
		return $XMLObject;
	}
	
	public function getRating($pid=NULL){//rating by logged in user
		$XMLObject = $this->getXMLObject();
		$uid = $this->access->getUserId();
		if(isset($uid)){
			$result = DB::query('SELECT rate FROM rating WHERE photo_id = '.(int)$pid.' AND user_id = '.(int)$uid.' LIMIT 0,1');
			if($result){
				if(DB::numRows($result)==1) $XMLObject->addChild('rating', DB::result($result, 0));
				else $this->throwError(2);
			}else $this->throwError(7);
		}else $this->throwError(8);
		return $XMLObject;
	}
	
	public function addRating($pid, $rate=NULL){

		$XMLObject = $this->getXMLObject();
		$rate = (int)$rate;
		$pid = (int)$pid;
		if($rate >0){
			if($pid >0){
				$uid = $this->access->getUserId();
				$uid = (int)$uid;
				if($uid>0){

					//only users with more than 2 photos are allowed to rate:
					$result = DB::query('SELECT photo_count FROM users WHERE user_id='.(int)$uid);
					if($result){
						if(DB::numRows($result)==1){//check if user still exists. In case of spamming: user has been deleted but is still able to send mail because of session cookie
							if((int)DB::result($result, 0)>2){//user has uploaded more than 2 photo

								$result = DB::query('SELECT rate FROM rating WHERE photo_id = '.$pid.' AND user_id= '.$uid.' LIMIT 0,1');//TODO: combine this with getRating!
								if($result){
									
									$ip = DB::escape(Utils::getIP());
									if(DB::numRows($result)==1){
										//update
										$old_rate = DB::result($result, 0);
										if($rate != $old_rate){
											DB::query('UPDATE rating SET rate = '.$rate.', ip = \''.$ip.'\', date=NOW() WHERE photo_id = '.$pid.' AND user_id = '.$uid);
											//update old num:
											$result = DB::query('UPDATE photos SET rate'.$old_rate.' = GREATEST(rate'.$old_rate.'-1,0) WHERE photos.photo_id='.$pid.' LIMIT 1');

										}else return $XMLObject;//nothing changed
									}else{
										//insert
										DB::query('INSERT INTO rating (photo_id, user_id, rate, ip, date) VALUES ('.$pid.', '.$uid.', '.$rate.', \''.$ip.'\', NOW())');
									}

									//DB::query('UPDATE photos SET rate'.$rate.' = rate'.$rate.'+1 WHERE photos.photo_id = '.$pid.' LIMIT 1');
									
									DB::query('UPDATE photos SET rate'.$rate.' = (SELECT COUNT(0) FROM rating WHERE rating.photo_id='.$pid.' AND rate='.$rate.') WHERE photos.photo_id = '.$pid.' LIMIT 1');
									
									//calculate average_rate
									$result = DB::query('SELECT camera, rate1, rate2, rate3, rate4, rate5 FROM photos WHERE photo_id = '.$pid);
									if($result){
										
										if(DB::numRows($result)==1){
											
											$row = DB::fetchAssoc($result);
											
											$r1 = $row['rate1'];
											$r2 = $row['rate2'];
											$r3 = $row['rate3'];
											$r4 = $row['rate4'];
											$r5 = $row['rate5'];
											$num_voters = $r1 + $r2 + $r3 + $r4 + $r5;
											
											if($num_voters > 0)$average_rate = ($r1*1 + $r2*2 + $r3*3 + $r4*4 + $r5*5) / $num_voters;
											else $average_rate = 0;
											
											$min_voters = 1.9;
											$mid_rate = 3;//(1+2+3+4+5)/5;
											
											$weighted_rate = ($num_voters / ($num_voters+$min_voters)) * $average_rate + ($min_voters / ($num_voters+$min_voters)) * $mid_rate;
											
											if($weighted_rate > 4.25) $camera = 3;
											elseif($weighted_rate > 4) $camera = 2;
											elseif($weighted_rate > 3.75) $camera = 1;
											else $camera = 0;
							
											DB::query('UPDATE photos SET average_rate = '.$weighted_rate.', camera = '.$camera.' WHERE photo_id = '.$pid);
											DB::query('UPDATE photo_tag2photo SET average_rate = '.$weighted_rate.' WHERE photo_id = '.$pid);
																			
											if($num_voters > MIN_NUM_VOTERS){
												if($row['camera'] != $camera){
													$this->resetUserCamera($uid);
												}
											}
										}
									}
								}else $this->throwError(7);
							}else $this->throwError(17);
						}else $this->throwError(17);	
					}else $this->throwError(7);
				}else $this->throwError(8);
			}else $this->throwError(1);
		}else $this->throwError(4);
		return $XMLObject;
	}

	public function getTags($photo_id){
		$this->buffer = false;
		$xml = new SimpleXMLElement('<tags></tags>');
		$items = array();
		
		$query = 'SELECT photo_tag2photo.tag_id, tag_text FROM photo_tag2photo INNER JOIN photo_tags ON photo_tag2photo.tag_id = photo_tags.tag_id WHERE photo_id = '.$photo_id.' LIMIT 0,'.(self::MAX_NUM_TAGS-1);
		if($result = DB::query($query)){
			if(DB::numRows($result)>0){
				while($row = DB::fetchAssoc($result))$items[$row['tag_id']] = $row['tag_text'];
				asort($items);
			}
		}

		foreach($items as $id=>$tag){
			$t = $xml->addChild('tag', htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'));
			$t->addAttribute('id', $id);
		}

		return $xml;
	}

	public function addTags($pid=NULL, $input=NULL){//$input: string with space/comma separated tags
		$pid = (int)$pid;
		if($pid>0){
			if(isset($input)){
				$input = trim($input);
				if(mb_strlen($input)>0){
					if($this->access->isLoggedIn()){
						//get array of tags:
						//http://www.snippetcenter.org/en/comma-or-space-separated-list-extractor-with-quote-support-s1804.aspx
						$tags = array();
						$values = explode('"', $input);
						if(substr($input, -1) == '"') array_pop($values);
						$count = count($values);
						if($count > 1){
							for($i = 0; $i < $count; $i++){
							  	if($i % 2 != 0){
							  		$tags[] = $values[$i];
							  	}else{
							  		$value = $values[$i];
							  		if(trim($value) == '' || trim($value) == ',') continue;
							  		$tags = array_merge($tags, preg_split('/[,;\s]+/', $value, -1, PREG_SPLIT_NO_EMPTY));
							  	}
							}
						}else{
						  	$tags = preg_split('/[,;\s]+/', $values[0], -1, PREG_SPLIT_NO_EMPTY);
						}
						if(count($tags)>0){
							$tags = array_slice($tags, 0, self::MAX_NUM_TAGS);
							foreach($tags as $tag){
								$this->addTag($pid, $tag);
							}
						}else $this->throwError(5);
					}else $this->throwError(8);
				}else $this->throwError(5);
			}else $this->throwError(5);
		}else $this->throwError(5);
	}
	public function addTag($pid=NULL, $tag=NULL){
		$pid = (int)$pid;
		if($pid>0){
			if(isset($tag)){
				$tag = trim($tag);
				if(mb_strlen($tag)>1){
					if($this->access->isLoggedIn()){
						//get average_rate and check if user owns photo:
						if($result = DB::query('SELECT average_rate FROM photos WHERE photo_id = '.$pid.' AND user_id ='.(int)$this->access->getUserId())){
							if(DB::numRows($result) ==1){
								$average_rate = DB::result($result, 0);
								if(isset($average_rate))$average_rate = (float)$average_rate;
								else $average_rate = 'NULL';
								if($result = DB::query('SELECT COUNT(0) FROM photo_tag2photo WHERE photo_id = '.$pid)){
									if((int)DB::result($result, 0) < self::MAX_NUM_TAGS){
										$text = DB::escape(mb_strtolower($tag));
										$tag_id = 0;
										$result = DB::query('SELECT tag_id FROM photo_tags WHERE tag_text = \''.$text.'\' LIMIT 0,1');
										if($result){
											if(DB::numRows($result) ==0){
												if(DB::query('INSERT INTO photo_tags (tag_text) VALUES (\''.$text.'\')')){
													$tag_id = DB::insertId();
												}
											}else $tag_id = DB::result($result, 0);
										}
										$tag_id = (int)$tag_id;
										if($tag_id >0){
											if(!DB::query('INSERT INTO photo_tag2photo (tag_id, photo_id, average_rate) VALUES ('.$tag_id.','.$pid .','.$average_rate.')'))$this->throwError(13);
										}else $this->throwError(13);
									}else $this->throwError(14);
								}else $this->throwError(7);
							}else $this->throwError(13);
						}else $this->throwError(7);	
					}else $this->throwError(8);
				}else $this->throwError(5);
			}else $this->throwError(5);
		}else $this->throwError(5);
	}
	public function removeTag($pid=NULL, $tid=NULL){
		$pid = (int)$pid;
		$tid = (int)$tid;
		if($pid>0 && $tid>0){
			if($this->access->isLoggedIn()){
				if($result = DB::query('SELECT COUNT(0) FROM photos WHERE photo_id = '.$pid.' AND user_id = '.(int)$this->access->getUserId())){
					if((int)DB::result($result, 0) == 1){
						if(!DB::query('DELETE FROM photo_tag2photo WHERE photo_id = '.$pid.' AND tag_id = '.$tid))$this->throwError(15);
					}else $this->throwError(15);
				}else $this->throwError(7);
			}else $this->throwError(8);
		}else $this->throwError(5);
	}
	public function resetUserCamera($uid){
		$camera_total = 0;
		//recalculate camera:
		$result = DB::query('SELECT camera, COUNT(*) AS num FROM photos WHERE user_id = '.(int)$uid.' GROUP BY camera');
		//TODO: is php approach faster then mysql: ORDER BY num DESC LIMIT 1???
		while($row = DB::fetchAssoc($result)){
			if($row['num']>MIN_NUM_PHOTOS_AWARD){
				if($camera_total<$row['camera'])$camera_total = $row['camera'];
			}
		}
		DB::query('UPDATE users SET camera = '.$camera_total .' WHERE user_id = '.(int)$uid);
	}
	public function addComment($pid=NULL, $text=NULL){
		$XMLObject = $this->getXMLObject();
		$pid = (int)$pid;
		if($pid>0){
			if($this->access->isLoggedIn()){
				if(isset($text)){
					$t = Utils::filterText($text,true,false,true);
					if(mb_strlen($t)>0){
						
						//anti spam:
						$poster_id = (int)$this->access->getUserId();
						$query = 'SELECT comment_date FROM photo_comments WHERE poster_id = '.$poster_id.' ORDER BY comment_id DESC LIMIT 0,1';
						$result = DB::query($query);
						if($result){
							$bln_send = true;
							if(DB::numRows($result)==1){
								if(time() - strtotime(DB::result($result, 0))<self::ANTISPAM_TIME_DELAY){
									$bln_send = false;
								}
							}
							if($bln_send){
								//add comment
								$t = substr($t, 0, 750);//maxlength 750
								$poster_name = DB::escape($this->access->getUserName());
								$result = DB::query('SELECT email, users.user_id, notify_comments FROM users INNER JOIN photos ON users.user_id = photos.user_id WHERE photos.photo_id = '.$pid.' LIMIT 0,1');
								if($result && DB::numRows($result)==1){
									$row = DB::fetchAssoc($result);
									$uid = (int)$row['user_id'];
									if(DB::query('INSERT INTO photo_comments (photo_id, user_id, poster_name, poster_id, comment_text) VALUES ('.$pid .','.$uid.',\''. $poster_name .'\', '. $poster_id .', \''. $t .'\')')){
										$XMLObject->addChild('comment_date', date('Y-m-d H:i:s', time()));
										$XMLObject->addChild('poster_name', htmlspecialchars($poster_name, ENT_QUOTES, 'UTF-8'));
										$XMLObject->addChild('comment', htmlspecialchars($t, ENT_QUOTES, 'UTF-8'));
										//update caches:
										DB::query('UPDATE photos SET comment_count = (SELECT COUNT(*) FROM photo_comments WHERE photo_id = '. (int)$pid .') WHERE photo_id= \''. (int)$pid .'\';');
										$this->status->updateLastComment($text, $pid, $poster_name, $uid);
										//notify:
										if($row['notify_comments']==1){
											
											$body = $text;
											//$body .= preg_replace('/&#(\d+);/me',"chr(\\1)", $t);//decode numeric unicode character for plain text, e.g. &#228; becomes �
											$body .= "\r\n\r\n".ABSURL.'photo/'.$pid;
											$body .= "\r\n\r\nDo not reply to this email, but please post your comment through the web interface available at the above link.";

											include_once CLASS_PATH.'Mail.class.php';
											$mail = new Mail();
											$mail->From(EMAIL_SENDER, NOREPLY_EMAIL_ADDRESS);
											$mail->To($row['email']);
											$mail->Subject($poster_name.' has left a comment on one of your photos');
											$mail->Body($body);
											$mail->Send();
										}
									}else $this->throwError(6);
								}else $this->throwError(6);
							}else $this->throwError(12);
						}else $this->throwError(6);
					}else $this->throwError(18);
				}else $this->throwError(18);
			}else $this->throwError(8);
		}else $this->throwError(1);
		return $XMLObject;
	}
	public function increaseDownloads($pid){
		DB::query('UPDATE photos SET downloads = downloads+1 WHERE photos.photo_id = '.(int)$pid);
	}
	public function getUrl($photo_id, $size='medium'){//wrapper used by ajax services
		$xml = $this->getXMLObject();
		if($size == 'm') $size = 'medium';
		else if($size == 's') $size = 'thumb';
		else if($size == 'l') $size = 'large';
		$photo_id = (int)$photo_id;
		$query = 'SELECT photos.user_id , photos.seo_suffix FROM photos WHERE photos.photo_id = '.(int)$photo_id.' LIMIT 0,1;';
		if($result = DB::query($query)){
			if(DB::numRows($result) == 1){
				$row = DB::fetchAssoc($result);
				$url = Utils::getPhotoUrl($row['user_id'], $photo_id, $size, '', $row['seo_suffix']);
				$p = $xml->addChild('photo');
				$p->addChild('id', $photo_id);
				$p->addChild('url', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
			}else $this->throwError(2);
		}else $this->throwError(7);
		return $xml;
	}
	public function getInfo($pid=NULL, $increaseViews=true){
		$xml = $this->getXMLObject();
		if(isset($pid)){
			$pid = (int)$pid;
			$query = 'SELECT countries.country_name, photos.city_id, photos.user_id, photos.photo_id, photos.width, photos.height, photos.alt_text, photos.seo_suffix, cities.FULL_NAME_ND, users.user_name, users.awards, users.camera, ambassadors.language_code  
					FROM photos 
					INNER JOIN cities ON photos.city_id = cities.UNI 
					INNER JOIN countries ON cities.CC1 = countries.country_code 
					INNER JOIN users ON photos.user_id = users.user_id 
					LEFT JOIN ambassadors ON ambassadors.user_id = users.user_id 
					WHERE photos.photo_id = '.$pid.' LIMIT 0,1;';

			$result = DB::query($query);
			if($result){
				if(DB::numRows($result) == 1){
					
					$row = DB::fetchAssoc($result);
					
					$width = $row['width'];
					$height = $row['height'];
					if($width==0){
						$size = $this->setPhotoSize($row['user_id'], $pid);
						$width = $size['width'];
						$height = $size['height'];
					}
					
					$xml->addChild('id', $row['photo_id']);
					$xml->addChild('url', Utils::getPhotoUrl($row['user_id'],$row['photo_id'],'medium', $row['seo_suffix']));//needed by ajax service: Ecard.js
					$xml->addChild('width', $width);
					$xml->addChild('height', $height);
					$xml->addChild('country_name', htmlspecialchars($row['country_name'], ENT_QUOTES, 'UTF-8'));
					$xml->addChild('city_name', htmlspecialchars($row['FULL_NAME_ND'], ENT_QUOTES, 'UTF-8'));
					$xml->addChild('alt_text', htmlspecialchars($row['alt_text'], ENT_QUOTES, 'UTF-8'));
					$xml->addChild('city_id', $row['city_id']);
					$xml->addChild('user_id', $row['user_id']);	
					$xml->addChild('user_name',  htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
					$xml->addChild('user_camera', $row['camera']);
					if(isset($row['language_code']))$xml->addChild('ambassador', $row['language_code']);
					if(isset($row['awards']))$this->user->addAwardsXML($xml, $row['awards']);
					if($increaseViews)$this->increaseViews($pid);


				}else $this->throwError(2);
			}else $this->throwError(7);
		}else $this->throwError(1);
		return $xml;
	}
	public function getMoreInfo($pid=NULL, $increaseViews=true){
		$xml = $this->getXMLObject();
		if(isset($pid)){
			$pid = (int)$pid;
			$query = 'SELECT countries.country_name, photos.city_id, photos.folder_id, photos.photo_date, photos.photo_id, photos.width, photos.height, photos.comment_count, photos.favorite_count, photos.keywords, photos.views, photos.downloads,
					(photos.rate1+photos.rate2+photos.rate3+photos.rate4+photos.rate5) AS numOfVoters,  
					photos.average_rate, photos.user_id, photos.alt_text, photos.seo_suffix, cities.FULL_NAME_ND, cities.LATI, cities.LONGI, cities.CC1, users.user_name, users.awards, users.camera, ambassadors.language_code  
					FROM photos 
					INNER JOIN cities ON photos.city_id = cities.UNI 
					INNER JOIN countries ON cities.CC1 = countries.country_code 
					INNER JOIN users ON photos.user_id = users.user_id 
					LEFT JOIN ambassadors ON ambassadors.user_id = users.user_id 
					WHERE photos.photo_id = '.$pid.' LIMIT 0,1;';

			$result = DB::query($query);
			if($result){
				if(DB::numRows($result) == 1){
					$row = DB::fetchAssoc($result);

					//$keywords = preg_replace("/&amp;(#[0-9]+;)/","&$1",$row['keywords']);//numeric unicode characters
					
					$width = $row['width'];
					$height = $row['height'];
					if($width==0){
						$size = $this->setPhotoSize($row['user_id'], $pid);
						$width = $size['width'];
						$height = $size['height'];
					}
					$views = $row['views'];
					if($increaseViews){
						$this->increaseViews($pid);
						$views++;
					}

					$xml->addChild('id', $row['photo_id']);
					$xml->addChild('width', $width);
					$xml->addChild('height', $height);
					$xml->addChild('country_name', htmlspecialchars($row['country_name'], ENT_QUOTES, 'UTF-8'));
					$xml->addChild('country_code', htmlspecialchars($row['CC1'], ENT_QUOTES, 'UTF-8'));
					$xml->addChild('date', $row['photo_date']);
					$xml->addChild('views', $views);
					$xml->addChild('downloads', $row['downloads']);
					$xml->addChild('user_id', $row['user_id']);
					if(isset($row['folder_id']))$xml->addChild('folder_id', $row['folder_id']);
					$xml->addChild('user_name', htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
					$xml->addChild('user_camera', $row['camera']);
					$xml->addChild('city_id', $row['city_id']);
					$xml->addChild('city_name', htmlspecialchars($row['FULL_NAME_ND'], ENT_QUOTES, 'UTF-8'));
					$xml->addChild('average_rate', isset($row['average_rate']) ? round($row['average_rate'],2) : 0);
					$xml->addChild('num_voters', $row['numOfVoters']);
				
					$xml->addChild('latitude', $row['LATI']);
					$xml->addChild('longitude', $row['LONGI']);
					$xml->addChild('comment_count', $row['comment_count']);
					$xml->addChild('favorite_count', $row['favorite_count']);
					$xml->addChild('seo_suffix', htmlspecialchars($row['seo_suffix'], ENT_QUOTES, 'UTF-8'));
					$xml->addChild('alt_text', htmlspecialchars($row['alt_text'], ENT_QUOTES, 'UTF-8'));
					$xml->addChild('description', htmlspecialchars($row['keywords'], ENT_QUOTES, 'UTF-8'));
					if(isset($row['language_code']))$xml->addChild('ambassador', $row['language_code']);
					$xml->addChild('tags');
					$tags = $this->getTags($row['photo_id']);
					Utils::simpleXmlAppend($xml->tags, $tags->children());
					//print_r($tags->children());
					//foreach($tags as $tag){$xml->tags->addChild('tag', $tag['name']);

					if(isset($row['awards']) && mb_strlen($row['awards'])>0){
						$awards = unserialize($row['awards']);
						foreach($awards as $award){
							$a = $xml->addChild('user_award');
							$k = array_keys($award);
							$v = array_values($award);
							$a->addAttribute('category_id',$k[0]);
							$a->addAttribute('date', $v[0]);
						}
					}
					
				}else $this->throwError(2);
			}else $this->throwError(7);
		}else $this->throwError(1);
		return $xml;
	}

	private function setPhotoSize($uid, $pid){//width and height are automatically stored since 2007.12.09. To update old records, use this function when needed
		$width = null;
		$height = null;
		$pid = (int)$pid;
		$path = Utils::getPhotoPath($uid, $pid, 'medium');
		if(file_exists($path)){
			list($width, $height) = getimagesize($path);
			if(isset($width, $height) && $width>0 && $height>0)DB::query('UPDATE photos SET width='.$width.' , height='.$height.' WHERE photo_id = '.$pid);
		}
		return array('width'=>$width, 'height'=>$height);
	}
	private function increaseViews($pid){
		DB::query('UPDATE photos SET views = views+1 WHERE photos.photo_id = '.(int)$pid);
	}
	public function getCommentsByPhotoId($pid=NULL, $offset=0, $limit=100){
		$xmlobject = $this->getXMLObject();
		$pid = (int)$pid;
		$limit = min(max(0,(int)$limit),100);
		$offset = max(0,(int)$offset);
		if($pid>0){
			$query = 'SELECT photo_comments.comment_id, users.user_name, photo_comments.comment_date, photo_comments.poster_name, photo_comments.comment_text, photo_comments.poster_id 
					FROM photo_comments
					LEFT JOIN users ON photo_comments.poster_id = users.user_id 
					WHERE photo_comments.photo_id ='.$pid.' 
					ORDER BY photo_comments.comment_id DESC LIMIT '.$offset.','.min((int)$limit,100).';';
			$result = DB::query($query);
			if($result){
				$num_rows = DB::numRows($result);
				if($num_rows>0){
					while($row = DB::fetchAssoc($result)){
						//$comment = preg_replace('/&amp;(#[0-9]+;)/','&$1',$comment);//numeric unicode characters
						
						$comment = $xmlobject->addChild('comment');
						$comment->addChild('id', $row['comment_id']);
						$comment->addChild('date', $row['comment_date']);
						if(is_null($row['user_name']))$comment->addChild('poster_name', htmlspecialchars($row['poster_name'], ENT_QUOTES, 'UTF-8'));
						else $comment->addChild('user_name', htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
						$comment->addChild('user_id', $row['poster_id']);
						$comment->addChild('text', htmlspecialchars(trim($row['comment_text']), ENT_QUOTES, 'UTF-8'));
					}
					if($offset+$num_rows>=$limit){
						if($result = DB::query('SELECT COUNT(0) FROM photo_comments WHERE photo_id ='.$pid))$xmlobject['total_comments'] = DB::result($result, 0, 0);
					}else $xmlobject['total_comments'] = $num_rows;
				}else $xmlobject['total_comments'] = 0;	
			}else $this->throwError(7);
		}else $this->throwError(1);
		return $xmlobject;
	}
	public function getRecentComments($offset=0, $limit=8){
		$XMLObject = $this->getXMLObject();
		$result = DB::query('SELECT comment_id FROM photo_comments ORDER BY comment_id DESC LIMIT '.max(0,(int)$offset).', '.min(20,max(0,(int)$limit)));
		if($result){
			$ids = array();
			while($row = DB::fetchAssoc($result))$ids[] = $row['comment_id'];
			$result = DB::query('SELECT comment_text, comment_date, photo_id, user_id, poster_name FROM photo_comments WHERE comment_id IN('. (implode(',',$ids)) .') ORDER BY comment_id DESC');
			if($result){
				while($row = DB::fetchAssoc($result)){
					$comment = $XMLObject->addChild('comment');
					$comment->addChild('text',htmlspecialchars($row['comment_text'], ENT_QUOTES, 'UTF-8'));
					$comment->addChild('photo_id', $row['photo_id']);
					$comment->addChild('date', $row['comment_date']);
					$comment->addChild('user_id', $row['user_id']);
					$comment->addChild('poster_name', htmlspecialchars($row['poster_name'], ENT_QUOTES, 'UTF-8'));
				}
			}else $this->throwError(7);
		}else $this->throwError(7);
		return $XMLObject;
	}
	public function getComments($offset=0, $limit=8, $total=0, $parentNode=NULL, $show_labels=FALSE, $show_own_comments=TRUE){/* get last comments from photos of logged in user */
		if($parentNode!=NULL){
			$XMLObject = $parentNode;
			$nodeName = 'item';
		}else{
			$XMLObject = $this->getXMLObject();
			$nodeName = 'comment';
		}
		$uid = $this->access->getUserId();
		if(isset($uid)){
			$user_filter = '';
			if(!$show_own_comments)$user_filter = ' AND poster_id != ' . $uid;
			$limit = min(8, max(0,(int)$limit));
            $offset = min(160-$limit, max(0,(int)$offset));
            $query = 'SELECT comment_id FROM photo_comments WHERE user_id ='.$uid. $user_filter . ' ORDER BY comment_id DESC LIMIT '.$offset.', '.$limit;
            $result = DB::query($query);
            if($result){
                $ids = array();
                while($row = DB::fetchAssoc($result))$ids[] = $row['comment_id'];
                if(count($ids)>0){
                    $result = DB::query('SELECT comment_text, comment_date, photo_id, poster_name FROM photo_comments WHERE comment_id IN('. (implode(',',$ids)) .') ORDER BY comment_id DESC');
                    if($result){
                        while($row = DB::fetchAssoc($result)){
                            $comment = $XMLObject->addChild($nodeName);
	                        if($parentNode!=NULL){//for notification widget
	                        	$comment->addAttribute('type', 'comment'); 
	                        	$comment->addAttribute('timestamp', strtotime($row['comment_date'])); 
								if($show_labels) $comment->addChild('label', 'Comment on your photo');
							}
                            $comment->addChild('text', strip_tags($row['comment_text']));
                            $comment->addChild('photo_id', $row['photo_id']);
                            $comment->addChild('user_id', $uid);
                            $comment->addChild('thumb_url',Utils::getPhotoUrl($uid,$row['photo_id'],'thumb',''));/*used by ajax services: PhotoComments.js*/
                            //$comment->addChild('date', $row['comment_date']);
                            $comment->addChild('user_name', htmlspecialchars($row['poster_name'], ENT_QUOTES, 'UTF-8'));
                            $comment->addChild('time_posted', Utils::dateDiff(strtotime($row['comment_date'])));//store for xhr  
                        }
                        if($total==0){
                            $result_count = DB::query('SELECT count(0) FROM photo_comments WHERE user_id='.$uid);
                            if($result_count)$XMLObject->addChild('total', DB::result($result_count, 0));
                        }
                    }else $this->throwError(7);
                }
            }else $this->throwError(7);
		}else $this->throwError(8);
		return $XMLObject;
	}
	public function getExif($pid=NULL){
		$xml = $this->getXMLObject();
		$result = DB::query('SELECT photos.exif FROM photos INNER JOIN users ON photos.user_id = users.user_id WHERE photos.photo_id = '.(int)$pid.' AND users.exif=1 LIMIT 0,1');
		if($result &&  DB::numRows($result) == 1){
			$row = DB::result($result,0);
			if(isset($row)){
				$lines = explode("\n", $row);
				$data = array();
				foreach($lines as $line){
					$section = explode('.', $line);
					if(count($section) >= 2){
						$section0 = array_shift($section);
						$line = implode('.', $section);
						$section = explode('=', $line);
						if(count($section) >= 2){
							$section1 = array_shift($section);
							$sectionvalue = implode('=', $section);
							if(!isset($data[$section0]))$data[$section0] = array();
							$data[$section0][$section1] = $sectionvalue;
						}
					}
				}
				if(count($data)>0){
					include CLASS_PATH.'ExifData.class.php';
					$exif = new ExifData($data);
					$props = 
					//http://www.woophy.com/forum/2_3041_0.html
					//array('Model','FocalLength','ISOSpeedRatings','ApertureValue','ExposureTime','ExposureProgram','ExposureBiasValue','WhiteBalance','MeteringMode','ShutterSpeedValue','Dimension','Date','Flash','Orientation','Software');
					array('Model','FocalLength','ISOSpeedRatings','ApertureValue','ExposureTime','ExposureProgram','ExposureBiasValue','WhiteBalance','MeteringMode','ShutterSpeedValue','Dimension','Flash','Orientation','Software');
					foreach($props as $prop){
						$val = $exif->get($prop);
						if(isset($val))$xml->addChild($prop,$val);
					}
				}
			}
		}else $this->throwError(2);
		return $xml;
	}
	//TODO: use AdvancedSearch here
	public function getPhotosByLocation($city_name=NULL, $country_code, $offset=0, $limit=30, $orderby='rating'){
		$xmlobject = $this->getXMLObject();
		$limit = min(30, max(0,(int)$limit));
		$offset = min(1000-$limit, max(0,(int)$offset));
		$orderby = $orderby=='rating' ?  'average_rate' : 'photo_id';
		$country_code = DB::escape(mb_strtoupper($country_code));
		$city_filter = '';
		if($city_name!=NULL){
			$city_UNI = $this->city->getCityUNIByName($city_name,$country_code);
			$city_filter = ' AND city_id='.(int)$city_UNI;
		} 
		$query = 'SELECT photos.photo_id FROM photos WHERE photo_processed = 1 AND photos.country_code=\''.$country_code.'\''.$city_filter.' ORDER BY '.$orderby.' DESC LIMIT '.$offset.','.$limit;
		if($result = DB::query($query)){
			$photo_ids = array();
			while ($row = DB::fetchAssoc($result)) array_push($photo_ids, $row['photo_id']);
			$num_rows = count($photo_ids);
			$xmlobject['total_photos'] = $num_rows;
			if(count($photo_ids)>0){
				$query2 = 'SELECT photos.photo_id, photos.user_id, photos.comment_count, photos.average_rate, photos.alt_text, photos.seo_suffix, users.user_name, cities.FULL_NAME_ND as city_name, countries.country_name FROM photos 
						INNER JOIN users ON photos.user_id = users.user_id 
						INNER JOIN cities ON photos.city_id = cities.UNI 
						INNER JOIN countries ON cities.CC1 = countries.country_code 
						WHERE photos.photo_id IN('.implode(',',$photo_ids).') ORDER BY '.$orderby.' DESC';
				if($result = DB::query($query2)){
					while($row = DB::fetchAssoc($result)){
						$photo = $xmlobject->addChild('photo');

						$photo->addChild('id',$row['photo_id']);
						$photo->addChild('user_id',$row['user_id']);
						$photo->addChild('photo_url',Utils::getPhotoUrl($row['user_id'],$row['photo_id'],'medium','',$row['seo_suffix']));
						$photo->addChild('user_name',htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
						$photo->addChild('comment_count',$row['comment_count']);
						$photo->addChild('average_rate',isset($row['average_rate']) ? round($row['average_rate'],2) : 0);
						$photo->addChild('alt_text',htmlspecialchars($row['alt_text'], ENT_QUOTES, 'UTF-8'));
						$photo->addChild('city_name',htmlspecialchars($row['city_name'], ENT_QUOTES, 'UTF-8'));
						$photo->addChild('country_name',$row['country_name']);					}
					if($offset+$num_rows>=$limit){
						if($result = DB::query('SELECT COUNT(0) FROM photos WHERE country_code =\''.$country_code.'\''.$city_filter))$xmlobject['total_photos'] = DB::result($result, 0, 0);
					}
				}else $this->throwError(7);
			}
		}else $this->throwError(7);
		return $xmlobject;
	}

	//TODO: use AdvancedSearch here
	public function getPhotosByUserId($user_id, $offset=0, $limit=30, $orderby='recent'){
		$xmlobject = $this->getXMLObject();
		$orderby = $orderby=='recent' ? 'photo_id' : 'average_rate';
		$limit = min(30, max(0,(int)$limit));
		$offset = min(2000-$limit, max(0,(int)$offset));//allow 2000 photos to browse??
		if($result = DB::query('SELECT photos.photo_id FROM photos WHERE photo_processed = 1 AND photos.user_id='.(int)$user_id.' ORDER BY '.$orderby.' DESC LIMIT '.$offset.','.$limit)){
			$photo_ids = array();
			while ($row = DB::fetchAssoc($result)) array_push($photo_ids, $row['photo_id']);
			$num_rows = count($photo_ids);
			$xmlobject['total_photos'] = $num_rows;
			if(count($photo_ids)>0){
				$query = 'SELECT photos.photo_id, photos.comment_count, photos.favorite_count, photos.average_rate, photos.alt_text, photos.seo_suffix, users.user_name, cities.FULL_NAME_ND AS city_name, countries.country_name AS country_name FROM photos INNER JOIN cities ON photos.city_id=cities.UNI INNER JOIN users ON photos.user_id=users.user_id INNER JOIN countries ON cities.CC1=countries.country_code WHERE photos.photo_id IN('.implode(',',$photo_ids).') ORDER BY photos.'.$orderby.' DESC';
				if($result = DB::query($query)){
					while($row = DB::fetchAssoc($result)){
						$photo = $xmlobject->addChild('photo');
						$photo->addChild('id',$row['photo_id']);
						$photo->addChild('user_id',(int)$user_id);
						$photo->addChild('thumb_url',Utils::getPhotoUrl($user_id,$row['photo_id'],'thumb','',$row['seo_suffix']));/*used by ajax services: Photo.js*/
						$photo->addChild('photo_url',Utils::getPhotoUrl($user_id,$row['photo_id'],'medium','',$row['seo_suffix']));
						$photo->addChild('alt_text', htmlspecialchars($row['alt_text'], ENT_QUOTES, 'UTF-8'));
						$photo->addChild('city_name', htmlspecialchars($row['city_name'], ENT_QUOTES, 'UTF-8'));
						$photo->addChild('country_name', $row['country_name']);
						$photo->addChild('comment_count',$row['comment_count']);
						$photo->addChild('favorite_count',$row['favorite_count']);
						$photo->addChild('average_rate',isset($row['average_rate']) ? round($row['average_rate'],2) : 0);
					}
					if($offset+$num_rows>=$limit){
						if($result = DB::query('SELECT COUNT(0) FROM photos WHERE user_id ='.(int)$user_id))$xmlobject['total_photos'] = DB::result($result, 0, 0);
					}
				}else $this->throwError(7);
			}
		}else $this->throwError(8);	
		return $xmlobject;
	}
	public function getPhotosMostFavorited(){//returns 6 most favorited photos from last month from cache
		if($xmlstr = $this->getFromCache(__METHOD__))$xmlobject = $this->getXMLObject($xmlstr);
		else{
			$limit = 6;
			$xmlobject = $this->getXMLObject();
			$query = 'SELECT photo_id FROM photos WHERE photo_month = \''.date('Ym').'\' ORDER BY favorite_count DESC LIMIT 0,'.$limit;
			$result = DB::query($query);
			if($result){
				$ids = array();
				while($row = DB::fetchAssoc($result)) array_push($ids, $row['photo_id']);
				if(count($ids)>0){
					$inner_query = 'SELECT photos.photo_id, photos.user_id, photos.comment_count, photos.favorite_count, photos.average_rate, photos.alt_text, photos.seo_suffix, users.user_name, countries.country_name, cities.FULL_NAME_ND as city_name
								FROM photos 
								INNER JOIN users ON photos.user_id = users.user_id 
								INNER JOIN cities ON photos.city_id = cities.UNI 
								INNER JOIN countries ON cities.CC1 = countries.country_code 
								WHERE photo_id IN ('.implode(',',$ids).') AND photos.favorite_count>0 AND photos.photo_processed = 1 ORDER BY photos.favorite_count DESC, photos.photo_id ASC;';
					if($result = DB::query($inner_query)){
						if(DB::numRows($result)>0){
							while($row = DB::fetchAssoc($result)){
								$photo = $xmlobject->addChild('photo');
								$photo->addChild('id',$row['photo_id']);
								$photo->addChild('photo_url',Utils::getPhotoUrl($row['user_id'],$row['photo_id'],'medium','',$row['seo_suffix']));
								$photo->addChild('alt_text', htmlspecialchars($row['alt_text'], ENT_QUOTES, 'UTF-8'));
								$photo->addChild('user_id',$row['user_id']);
								$photo->addChild('user_name',htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
								$photo->addChild('city_name', htmlspecialchars($row['city_name'], ENT_QUOTES, 'UTF-8'));
								$photo->addChild('country_name', $row['country_name']);
								$photo->addChild('comment_count',(int)$row['comment_count']);
								$photo->addChild('favorite_count',(int)$row['favorite_count']);
								$photo->addChild('average_rate',$row['average_rate']);
							}
							$this->saveToCache(__METHOD__, $this->send(), false, 10800);//cache for 3 hours
						}//do not report an error
					}else $this->throwError(7);
				}//do not report an error
			}else $this->throwError(7);
		}
		return $xmlobject;
	}
	public function getPhotosTopRatedToday(){//returns 6 top rated photos today from cache, TODO: combine with getPhotosMostFavorited
		if($xmlstr = $this->getFromCache(__METHOD__)) $xmlobject = $this->getXMLObject($xmlstr);
		else{
			$limit = 6;
			$xmlobject = $this->getXMLObject();
			$query = 'SELECT photo_id FROM photos WHERE photo_processed = 1 AND photo_date_lookup = DATE_FORMAT(CURDATE( ),\'%Y%m%d\') ORDER BY average_rate DESC LIMIT 0,'.$limit;
			$result = DB::query($query);
			if($result){
				$ids = array();
				while($row = DB::fetchAssoc($result)) array_push($ids, $row['photo_id']);
				if(count($ids)>0){
					$query2 = 'SELECT photos.photo_id, photos.user_id, photos.comment_count, photos.favorite_count, photos.average_rate, photos.alt_text, photos.seo_suffix, users.user_name, countries.country_name, cities.FULL_NAME_ND as city_name
							FROM photos 
							INNER JOIN users ON photos.user_id = users.user_id 
							INNER JOIN cities ON photos.city_id = cities.UNI 
							INNER JOIN countries ON cities.CC1 = countries.country_code 
							WHERE photo_id IN ('.implode(',',$ids).') ORDER BY average_rate DESC, photo_id ASC;';
					if($result = DB::query($query2)){
						while($row = DB::fetchAssoc($result)){
							$photo = $xmlobject->addChild('photo');
							$photo->addChild('id', $row['photo_id']);
							$photo->addChild('photo_url', Utils::getPhotoUrl($row['user_id'],$row['photo_id'],'medium','',$row['seo_suffix']));
							$photo->addChild('alt_text',htmlspecialchars($row['alt_text'], ENT_QUOTES, 'UTF-8'));
							$photo->addChild('user_id',$row['user_id']);
							$photo->addChild('user_name',htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
							$photo->addChild('city_name', htmlspecialchars($row['city_name'], ENT_QUOTES, 'UTF-8'));
							$photo->addChild('country_name', $row['country_name']);
							$photo->addChild('comment_count',$row['comment_count']);
							$photo->addChild('favorite_count',(int)$row['favorite_count']);
							$photo->addChild('average_rate',$row['average_rate']);
						}
						$this->saveToCache(__METHOD__, $this->send(), false, 10800);//cache for 3 hours
					}else $this->throwError(7);
				}//do not report an error
			}else $this->throwError(7);
		}
		return $xmlobject;
	}
	
	public function getRecent($limit=10, $offset=0, $offset_id=NULL, $uid=NULL, $output_mode='xml'){//optional user_id
		//$mt_start = $this->getMicrotime();
		$html_output = '';

		$limit = min(10, max(0,(int)$limit));
		$offset = min(90-$limit, max(0,(int)$offset));
		
		$uid = (int)$uid;
		$offset_id = (int)$offset_id;
		
		//first retreive photoids:
		$query = 'SELECT photo_id FROM photos WHERE photo_processed = 1';
		if($uid>0) $query .= ' AND user_id = '.$uid;
		else if($offset_id>0) {
			$query .= ' AND photo_id < '.$offset_id;
			$offset = 0;
		}
		$query .= ' ORDER BY photo_id DESC LIMIT '. $offset .', '. $limit .';';

		$result = DB::query($query);
		if($result):
			//now get the joins (this way is much faster then 1 query combined)
			$ids = array();
		
			while ($row = DB::fetchAssoc($result)) array_push($ids, $row['photo_id']);
			$num_rows = count($ids);
			if($num_rows>0):
				$query = 'SELECT cities.FULL_NAME_ND as city_name, cities.UNI as city_id, countries.country_name, photos.photo_date, photos.photo_id, photos.user_id, photos.comment_count, photos.favorite_count, photos.alt_text, photos.seo_suffix, users.user_name FROM photos
					INNER JOIN users ON photos.user_id = users.user_id 
					INNER JOIN cities ON photos.city_id = cities.UNI 
					INNER JOIN countries ON cities.CC1 = countries.country_code WHERE photos.photo_id IN ('.implode(',',$ids).') ORDER BY photos.photo_id DESC;';
				$result = DB::query($query);
				if($result):
					$XMLObject = $this->getXMLObject();

					while($row = DB::fetchAssoc($result)):
						$photo = $XMLObject->addChild('photo');
						$photo->addChild('id',$row['photo_id']);
						$photo->addChild('thumb_url',Utils::getPhotoUrl($row['user_id'],$row['photo_id'],'thumb',''));/*used by ajax services: Photo.js*/
						//$photo->addChild('photo_url',Utils::getPhotoUrl($row['user_id'],$row['photo_id'],'medium','',$row['seo_suffix']));
						$photo->addChild('photo_url',Utils::getPhotoUrl($row['user_id'],$row['photo_id'],'medium'));//no seo for recent photo galleries
						//$photo->addChild('alt_text',htmlspecialchars($row['alt_text'], ENT_QUOTES, 'UTF-8'));
						$photo->addChild('alt_text','');//no seo
						$photo->addChild('user_id',$row['user_id']);
						$photo->addChild('user_name',htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
						$photo->addChild('city_name',htmlspecialchars($row['city_name'], ENT_QUOTES, 'UTF-8'));
						$photo->addChild('city_id',$row['city_id']);
						$photo->addChild('country_name',$row['country_name']);
						$photo->addChild('date', $row['photo_date']);
						$photo->addChild('comment_count', (int)$row['comment_count']);
						$photo->addChild('favorite_count', (int)$row['favorite_count']);
					endwhile;
					if($offset+$num_rows>=$limit):
						if(isset($uid)):
							if($result = DB::query('SELECT COUNT(0) FROM photos WHERE user_id = \''.(int)$uid.'\'')): $xmlobject['total_photos'] = DB::result($result, 0, 0); endif;
						endif;
					endif;

					if($output_mode=='html'):
						require_once(INCLUDE_PATH.'thumbsgrid.php');
						$html_output = outputThumbsGrid($XMLObject->photo, array('link_to_map'=>TRUE));
 					endif;
				else: $this->throwError(7); endif;
			else: $this->throwError(2); endif;
		else: $this->throwError(7); endif;
		//$XMLObject['time'] = $this->getMicrotime() - $mt_start;
		if($output_mode=='xml'):
			return $XMLObject;
		elseif($output_mode=='html'):
			return $html_output;
		endif;
	}

	public function addToFavorites($pid){
		$XMLObject = $this->getXMLObject();
		$pid = (int)$pid;
		if($pid>0){
			$uid = (int)$this->access->getUserId();
			if($uid>0){
				DB::query('INSERT IGNORE INTO favorite_photos (user_id, photo_id) VALUES ('.$uid.','.$pid.')');
				if(DB::affectedRows()==1)DB::query('UPDATE photos SET favorite_count = (SELECT COUNT(0) FROM favorite_photos WHERE photo_id = '.$pid.') WHERE photo_id ='.$pid);
				else  $this->throwError(11);
			}else $this->throwError(8);
		}else $this->throwError(1);
		return $XMLObject;
	}
	public function removeFromFavorites(){/*photo_id1, photo_id2, photo_id3*/
		$XMLObject = $this->getXMLObject();
		$args = func_get_args();
		$count = count($args);
		if($count>0){
			if($this->access->isSecureLoggedIn()){
				$uid = (int)$this->access->getUserId();
				$args = array_slice($args, 0, 25);//max 25 favs
				foreach($args as $k=>$v) $args[$k] = (int)$v;//convert to int
				DB::query('DELETE FROM favorite_photos WHERE user_id = '.$uid.' AND photo_id IN ('.implode(',', $args).')');
				if(DB::affectedRows()>0){
					foreach($args as $arg){DB::query('UPDATE photos SET favorite_count = (SELECT COUNT(0) FROM favorite_photos WHERE photo_id = '.$arg.') WHERE photo_id ='.$arg);
					}
				}
			}else $this->throwError(8);
		}
		return $XMLObject;
	}
	public function getFavorites($offset=0, $limit=50, $orderby=''){
		$XMLObject = $this->getXMLObject();
		$uid = (int)$this->access->getUserId();
		if($uid>0){
			$query = 'SELECT favorite_photos.photo_id, photos.average_rate, photos.comment_count, photos.alt_text, photos.seo_suffix, photos.user_id, users.user_name, cities.FULL_NAME_ND as city_name, countries.country_name 
					FROM favorite_photos 
					INNER JOIN photos ON favorite_photos.photo_id = photos.photo_id 
					INNER JOIN users ON photos.user_id = users.user_id 
					INNER JOIN cities ON photos.city_id = cities.UNI 
					INNER JOIN countries ON cities.CC1 = countries.country_code 
					WHERE favorite_photos.user_id ='.$uid.' ORDER BY favorite_photos.favorite_id DESC LIMIT '.max(0,(int)$offset).','.min(100,max(0,(int)$limit));
			$result = DB::query($query);

			if($result){
				$num_rows = DB::numRows($result);
				$XMLObject['total_photos'] = $num_rows;
				if($num_rows>0){
					while($row = DB::fetchAssoc($result)){
						$photo = $XMLObject->addChild('photo');
						$photo->addChild('id', $row['photo_id']);
						$photo->addChild('photo_url',Utils::getPhotoUrl($row['user_id'],$row['photo_id'],'medium','', $row['seo_suffix']));
						$photo->addChild('alt_text',htmlspecialchars($row['alt_text'], ENT_QUOTES, 'UTF-8'));
						$photo->addChild('user_id', $row['user_id']);
						$photo->addChild('user_name',htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
						$photo->addChild('comment_count', $row['comment_count']);
						$photo->addChild('average_rate', isset($row['average_rate']) ? round($row['average_rate'],2) : 0);
						$photo->addChild('city_name',htmlspecialchars($row['city_name'], ENT_QUOTES, 'UTF-8'));
						$photo->addChild('country_name',$row['country_name']);
					}
					
					if($offset+$num_rows>=$limit){
						$result_count = DB::query('SELECT count(0) FROM favorite_photos WHERE user_id = '.$uid);
						if($result_count) $XMLObject['total_photos'] = DB::result($result_count, 0);
					}
				}
			}else $this->throwError(7);
		}else $this->throwError(8);
		return $XMLObject;
	}
	public function getFavoritesByUserId($user_id, $offset=0, $limit=50, $orderby=''){ // This retrieves the favorite photos saved by a given user
		//TRICKY: favorites can be private, be sure to check this before calling this method!
		$XMLObject = $this->getXMLObject();
		if(isset($user_id)){
			$limit = min(50, max(0,(int)$limit));
			$offset = min(1000-$limit , max(0,(int)$offset));
			$query = 'SELECT favorite_photos.photo_id FROM favorite_photos WHERE favorite_photos.user_id ='.(int)$user_id.' ORDER BY favorite_photos.favorite_id DESC LIMIT '.$offset.','.$limit; 
			if($result = DB::query($query)){
				
				$photo_ids = array();
				while ($row = DB::fetchAssoc($result)) array_push($photo_ids, $row['photo_id']);
				if(count($photo_ids)>0){
					$query2 = 'SELECT photos.photo_id, photos.user_id, photos.comment_count, photos.favorite_count, photos.alt_text, photos.seo_suffix, users.user_name, cities.FULL_NAME_ND as city_name, countries.country_name 
							FROM photos 
							INNER JOIN users ON photos.user_id = users.user_id 
							INNER JOIN cities ON photos.city_id = cities.UNI 
							INNER JOIN countries ON cities.CC1 = countries.country_code 
							WHERE photos.photo_id IN('.implode(',',$photo_ids).')';
					$result = DB::query($query2);

					if($result){
						$num_rows = DB::numRows($result);
						$XMLObject['total_photos'] = $num_rows;
						if($num_rows>0){
							while($row = DB::fetchAssoc($result)){
								$photo = $XMLObject->addChild('photo');
								$photo->addChild('id', $row['photo_id']);
								$photo->addChild('photo_url',Utils::getPhotoUrl($row['user_id'],$row['photo_id'],'medium','',$row['seo_suffix']));
								$photo->addChild('alt_text', htmlspecialchars($row['alt_text'], ENT_QUOTES, 'UTF-8'));
								$photo->addChild('user_name', htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
								$photo->addChild('user_id', $row['user_id']);
								$photo->addChild('city_name', htmlspecialchars($row['city_name'], ENT_QUOTES, 'UTF-8'));
								$photo->addChild('country_name', $row['country_name']);
								$photo->addChild('comment_count', (int)$row['comment_count']);
								$photo->addChild('favorite_count', (int)$row['favorite_count']);
							}
							
							if($offset+$num_rows>=$limit){
								$result_count = DB::query('SELECT count(0) FROM favorite_photos WHERE user_id = '.(int)$user_id);
								if($result_count) $XMLObject['total_photos'] = DB::result($result_count, 0);
							}
						}
					}else $this->throwError(7);
				}else $this->throwError(2);
			}else $this->throwError(7);
		}else $this->throwError(8);
		return $XMLObject;
	}
	public function getFavoritesForUserId($offset=0, $limit=50, $parentNode=NULL, $show_labels=FALSE){ // This retrieves the favorites that the logged in user has recieved for his their photos
		if($parentNode!=NULL){
			$XMLObject = $parentNode;
			$nodeName = 'item';
		}else{
			$XMLObject = $this->getXMLObject();
			$nodeName = 'photo';
		}
		$uid = $this->access->getUserId();
		if($uid>0){
			$query = 'SELECT favorite_photos.user_id, users.user_name, favorite_photos.photo_id, favorite_photos.favorite_date, cities.UNI, cities.FULL_NAME_ND, countries.country_name FROM favorite_photos
					INNER JOIN photos on photos.photo_id = favorite_photos.photo_id
					INNER JOIN users on users.user_id = favorite_photos.user_id
					INNER JOIN cities ON photos.city_id = cities.UNI 
					INNER JOIN countries ON cities.CC1 = countries.country_code 
					WHERE photos.user_id = ' . $uid . ' ORDER BY favorite_photos.favorite_date DESC LIMIT '.max(0,(int)$offset).','.min(100,max(0,(int)$limit));
			
			$result = DB::query($query);
			if($result){
				$num_rows = DB::numRows($result);
				$XMLObject['total'] = $num_rows;
				if($num_rows>0){
					while($row = DB::fetchAssoc($result)){
                        $photo = $XMLObject->addChild($nodeName);
                        if($parentNode!=NULL){//for notification widget
							$photo->addAttribute('type', 'photo_favorite'); 
                        	$photo->addAttribute('timestamp', strtotime($row['favorite_date']));
							if($show_labels) $photo->addChild('label', 'Favorite on your photo');
                        }
						$photo->addChild('id', $row['photo_id']);
						$photo->addChild('user_id', $uid);
                        $photo->addChild('city_id', $row['UNI']);
                        $photo->addChild('city_name', htmlspecialchars($row['FULL_NAME_ND'], ENT_QUOTES, 'UTF-8'));
                        $photo->addChild('country_name', $row['country_name']);
						
                        $photo->addChild('thumb_url',Utils::getPhotoUrl($uid,$row['photo_id'],'thumb',''));
                        $photo->addChild('user_name', htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
                        $photo->addChild('date', $row['favorite_date']);  						
					}
					if($offset+$num_rows>=$limit){
						$result_count = DB::query('SELECT count(0) FROM favorite_photos WHERE user_id = '.$uid);
						if($result_count) $XMLObject['total_photos'] = DB::result($result_count, 0);
					}
				}
			}else $this->throwError(7);
		}else $this->throwError(8);
		return $XMLObject;
	}
	public function getLatestPhotosByFavorites($limit=50, $parentNode=NULL, $show_labels=FALSE){ //this retrieves the newest photos by the logged in user's favorite photographers
		if($parentNode!=NULL){
			$XMLObject = $parentNode;
			$nodeName = 'item';
		}else{
			$XMLObject = $this->getXMLObject();
			$nodeName = 'photo';
		}
		$uid = $this->access->getUserId();
		if($uid>0){
			
			$favorite_photogs = $this->user->getFavorites();

			$user_ids = array();
			foreach($favorite_photogs->user as $photog){
				array_push($user_ids, $photog->id);
			}
			if(count($user_ids)>0){
				$result = DB::query('SELECT MAX( photos.photo_id ) AS photo_id FROM photos WHERE photos.user_id IN ('.implode(',',$user_ids).') GROUP BY photos.user_id');
				if($result){
					$photo_ids = array();
					while($row = DB::fetchAssoc($result)){
						array_push($photo_ids, $row['photo_id']);
					}
					$num = count($photo_ids);
					$XMLObject['total'] = $num;
					if($num>0){
						rsort($photo_ids);
						$photo_ids = array_slice($photo_ids, 0, $limit);

						$query = 'SELECT photos.user_id, photos.photo_id, photos.comment_count, photos.favorite_count, photos.photo_date, users.user_name, cities.UNI, cities.FULL_NAME_ND, countries.country_name 
							FROM photos 
							INNER JOIN users ON photos.user_id = users.user_id 
							INNER JOIN cities ON photos.city_id = cities.UNI 
							INNER JOIN countries ON cities.CC1 = countries.country_code 
							WHERE photos.photo_id IN('.implode(',',$photo_ids).') ORDER BY photos.photo_id';

						$result = DB::query($query);
						if($result){

								while($row = DB::fetchAssoc($result)){
			                        $photo = $XMLObject->addChild($nodeName);
			                        if($parentNode!=NULL){ //for notification widget
			                        	$photo->addAttribute('type', 'photo');
			                        	$photo->addAttribute('timestamp', strtotime($row['photo_date']));
			                        	if($show_labels) $photo->addChild('label', 'Photo by favorite');
									}
			                        $photo->addChild('id', $row['photo_id']);
			                        $photo->addChild('city_name', htmlspecialchars($row['FULL_NAME_ND'], ENT_QUOTES, 'UTF-8'));
			                        $photo->addChild('city_id', $row['UNI']);
			                        $photo->addChild('country_name', $row['country_name']);
									
			                        $photo->addChild('thumb_url',Utils::getPhotoUrl($uid,$row['photo_id'],'thumb',''));
			                        $photo->addChild('user_id', $row['user_id']);
			                        $photo->addChild('user_name', htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
			                        $photo->addChild('date', $row['photo_date']);
			                        $photo->addChild('comment_count', $row['comment_count']);  						
			                        $photo->addChild('favorite_count', $row['favorite_count']);  						  
								}
						}else $this->throwError(7);
					}else $this->throwError(2);
				}else $this->throwError(7);
			}else $this->throwError(2);
		}else $this->throwError(8);
		return $XMLObject;
	}
	//TODO: combine this function with Blog.addPhoto!!
	public function addPhoto(
		$file,
		$UFI,
		$cats,
		$description=NULL,
		$folder_id=NULL,
		$tags=NULL
		){
		$XMLObject = $this->getXMLObject();
		$img = new Image($file);
		if($img->isImage()){
			$uid = (int)$this->access->getUserId();
			if($uid>0){
				if(isset($UFI)){
					$xml_city  = $this->city->getCityByUFI($UFI);
					if($UNI = $xml_city->UNI){
						$country_code = $xml_city->country_code;
						$longi = $xml_city->longi;
						$lati = $xml_city->lati;

						$w = $img->getWidth();
						$h = $img->getHeight();
						$scale = true;
						if($w <= 1280 || $h <= 1280)
							$scale = false;

						//add record
						$date = date('Y-m-d H:i:s');
						$description = Utils::filterText($description, false, false, true);
						$folder_id = (int)$folder_id;
						if($folder_id==0)$folder_id = 'NULL';

						$d = $img->getDimensions($w, $h);

						$str_exif = 'NULL';
						if(function_exists('exif_read_data')){
							$exif = @exif_read_data ($img->getTemporaryFilename(),0,true);
							if($exif){
								$str_skip = array('MAKERNOTE','COMPONENTSCONFIGURATION','USERCOMMENT','FILESOURCE','SCENETYPE','DEVICESETTINGDESCRIPTION');//skip binary bulk
								$str_exif = '';
								foreach($exif as $key=>$section) {
									foreach($section as $name=>$val) {
										if(mb_strtoupper($key)=='EXIF'){
											if(in_array(mb_strtoupper($name),$str_skip))continue;
										}
										$str_exif .= "$key.$name=$val\n";
									}
								}
								$str_exif = "'".DB::escape($str_exif)."'";
							}
						}
						$time = strtotime($date);
						$date_short = date('Ymd', $time);
						//$week = date('YW', $time);
						$week = 'YEARWEEK(\''.$date.'\' , 2)';
						$month = date('Ym', $time);
						$metadata = array('city'=>$xml_city->city_name, 'country'=>$xml_city->country_name, 'tags'=>explode(', ', $tags), 'category'=>$this->getCategoryName($cats[0]), 'description'=>$description, 'username'=>$this->access->getUserName());
						$seo_suffix = Utils::getSEOText($metadata, false);
						$alt_text = Utils::getSEOText($metadata, true);
						
						$query = 'INSERT INTO photos (city_id, user_id, longi, lati, country_code, width, height, keywords, exif, folder_id, photo_date, photo_date_lookup, photo_week, photo_month, alt_text, seo_suffix, photo_processed) VALUES ('.$UNI.', '.$uid.', '.$longi.', '.$lati.', \''.$country_code.'\', '.$d['width'].', '.$d['height'].', \''.$description.'\', '.$str_exif.', '.$folder_id.', \''.$date.'\', \''.$date_short.'\', '.$week.', \''.$month.'\', \''.$alt_text['raw'].'\', \''.$seo_suffix['raw'].'\', 0)';
						if(DB::query($query)){
							$pid = DB::insertId();
							//create images
							$bln_success = FALSE;
							$tmp_name = $file['tmp_name'];
							$filename = Utils::getPhotoPath($uid,$pid,'original');
							$dir = dirname($filename);
							if(!is_dir($dir)) mkdir($dir,0777, true);
							
							//if($img->output(MAX_PHOTO_WIDTH_THUMB, MAX_PHOTO_WIDTH_THUMB, Utils::getPhotoPath($uid,$pid,'thumb'))) {//Thumb
							//	if($img->output(MAX_PHOTO_WIDTH_MEDIUM, MAX_PHOTO_HEIGHT_MEDIUM, Utils::getPhotoPath($uid,$pid,'medium'))){//Medium
								//	if($img->output(MAX_PHOTO_WIDTH_LARGE, MAX_PHOTO_HEIGHT_LARGE, Utils::getPhotoPath($uid,$pid,'large'))){//Large
									//	if($img->output(MAX_PHOTO_WIDTH_FULL, MAX_PHOTO_HEIGHT_FULL, Utils::getPhotoPath($uid,$pid,'full'))){//Full
											if(copy($tmp_name,$filename)){//org
												DB::query('INSERT INTO photo_que (user_id, photo_id) VALUES ( '.$uid.', '.$pid.')');
												$bln_success = TRUE;
												$this->addCategories($pid, $cats);										
												$result = DB::query('SELECT count(0) FROM photos WHERE user_id='.$uid);
												if($result)$count = (int)DB::result($result, 0);
												else $count = 0;
												DB::query('UPDATE users SET last_upload_date = \''.$date.'\', photo_count = '.$count.' WHERE user_id='.$uid);
												//$XMLObject->addChild('url', Utils::getPhotoUrl($uid, $pid, 'thumb'));//return thumb url
												//$XMLObject->addChild('photo_id', $pid);
												$this->status->updateNumberOfPhotos();
												$this->city->updatePhotoCount($UNI);
												
												//added 22.06.2012: notify on first photo:
												if($count == 1){
													$result = DB::query('SELECT countries.country_name, cities.FULL_NAME_ND, users.user_name FROM photos 
													INNER JOIN cities ON photos.city_id = cities.UNI 
													INNER JOIN countries ON cities.CC1 = countries.country_code 
													INNER JOIN users ON photos.user_id = users.user_id 
													WHERE photos.photo_id = '.$pid.' LIMIT 0,1;');
													if($result && DB::numRows($result)==1){
														$row = DB::fetchAssoc($result);
														include_once CLASS_PATH.'Mail.class.php';
														$mail = new Mail();
														$mail->From(EMAIL_SENDER, NOREPLY_EMAIL_ADDRESS);
														//$mail->To(INFO_EMAIL_ADDRESS);
														$mail->To('joris@bbvh.nl');
														$mail->Cc('angel@woophy.com');
														$mail->Subject('New member has posted first photo');
														$body = '<html><img src="'.Utils::getPhotoUrl($uid, $pid, 's','photo',true).'"><br>';
														$body .= '<a href="http://www.woophy.com/member/'.urlencode($row['user_name']).'">User name: '.$row['user_name'].'</a><br>';
														$body .= 'User id: '.$uid.'<br>';
														$body .= 'Photo id: '.$pid.'<br>';
														$body .= 'Photo location: '.$row['FULL_NAME_ND'].', '.$row['country_name'].'</html>';
														$mail->Body($body, false);
														$mail->Send();
													}
												}
											}else $this->throwError(10);												
										//}else $this->throwError(10);
									//}else $this->throwError(10);
							//	}else $this->throwError(10);
							//}else $this->throwError(10);
							
							if($bln_success){
								$this->addTags($pid, $tags);
								$this->clearError();//in case addTags did throw an error
							}else{
								$this->removePhoto($pid, false, true, false);//uploading failed, clean up:
							}
						}else $this->throwError(7);
					}else $this->throwError(7);
				}else $this->throwError(7);
			}else $this->throwError(8);
		}else {
			parent::throwError($img->errorNo, $img->errorMessage);
		}
		$img->destroy();
		return $XMLObject;
	}
	public function editPhoto($photo_id, $UFI=NULL, $cats=NULL, $description='', $tags = '', $folder_id=NULL, $old_UNI, $city_name){
		$xmlobj = $this->getXMLObject();
		if(isset($photo_id)){
			if($uid = $this->access->getUserId()){//you can only edit your own photos!
				$updates = array();
				if(isset($UFI)){
					$xml_city  = $this->city->getCityByUFI($UFI);
					if($UNI = $xml_city->UNI){
						if($old_UNI != $UNI){
							$updates[] = 'city_id='.$UNI;
							if($country_code = $xml_city->country_code)$updates[] = 'country_code=\''.$country_code.'\'';
							if($lati = $xml_city->lati)$updates[] = 'lati='.$lati;
							if($longi = $xml_city->longi)$updates[] = 'longi='.$longi;
							$country_name = $xml_city->country_name;
						}
					}
				}else{
					$country_name = $this->city->getCountryByUNI($old_UNI);
				}
				$this->addCategories($photo_id, $cats);
				$updates[] = 'keywords=\''.Utils::filterText($description, false, false, true).'\'';
				$folder_id = (int)$folder_id;
				if($folder_id==0)$folder_id = 'NULL';
				$updates[] = 'folder_id='.$folder_id;
				$metadata = array('city'=>$city_name, 'country'=>$country_name, 'tags'=>explode(', ', $tags), 'category'=>$this->getCategoryName($cats[0]), 'description'=>$description, 'username'=>$this->access->getUserName());
				$seo_suffix = Utils::getSEOText($metadata, false);
				$alt_text = Utils::getSEOText($metadata, true);
				$updates[] = 'seo_suffix=\''.DB::escape($seo_suffix['raw']).'\'';
				$updates[] = 'alt_text=\''.DB::escape($alt_text['raw']).'\'';

				if(DB::query('UPDATE photos SET '.implode(',',$updates).' WHERE photo_id='.(int)$photo_id.' AND user_id='.$uid)){
					if(DB::affectedRows()==0)$this->throwError(2);
					else{
						if(isset($UNI)){
							$this->city->updatePhotoCount($UNI);
							$this->city->updatePhotoCount($old_UNI);
						}
					}
					$this->addTags($photo_id, $tags);
				}else $this->throwError(7);
			}else $this->throwError(8);
		}else $this->throwError(1);
		return $xmlobj;
	}
	private function addCategories($pid, $cats){
		if(isset($pid)){
			$pid = (int)$pid;
			if($pid>0){
				DB::query('DELETE FROM photo2category WHERE photo_id = '.$pid.';');
				if(is_array($cats)){
					foreach ($cats as $v){
						DB::query('INSERT INTO photo2category SET photo_id = '.$pid.', category_id = '.(int)$v.';');
					}
				}
			}
		}
	}
	public function getCategoriesByPhotoId($pid){
		$xmlobj = $this->getXMLObject();
		$result = DB::query('SELECT category_id FROM photo2category WHERE photo_id='.(int)$pid);
		if($result){
			while ($row = DB::fetchAssoc($result)) {
				$xmlobj->addChild('category_id', $row['category_id']);
			}
		}
		return $xmlobj;
	}
	public function getCategoryName($cat_id){
		$result = DB::query('SELECT category_name FROM photo_categories WHERE category_id='.(int)$cat_id. ' LIMIT 0,1');
		if($result && DB::numRows($result) == 1){
			$row = DB::fetchAssoc($result);
			return $row['category_name'];
		}
		return '';
	}

	public function getLastPhotoPerCategory(){//to prevent slow inner joins, use 3 light queries
		//if(!($xmlstr = $this->getFromCache(__METHOD__))){
			$xmlobj = $this->getXMLObject();
			$result1 = DB::query('SELECT category_id, category_name FROM photo_categories ORDER BY category_name');
			if($result1){
				$num_rows = DB::numRows($result1);
				if($num_rows>0){
					$result2 = DB::query('SELECT category_id, MAX(photo_id) AS photo_id FROM photo2category GROUP BY category_id');
					if($result2){
						if(DB::numRows($result2) == $num_rows){
							$cats = array();
							while ($row = DB::fetchAssoc($result1)) {
								$cats[$row['category_id']]=array('category_name'=>$row['category_name']);
							}			
							$photo_ids = array();
							while ($row = DB::fetchAssoc($result2)) {
								$pid = $row['photo_id'];
								$photo_ids[] = $pid;
								$cats[$row['category_id']]['photo_id']=$pid;
							}
							$result3 = DB::query('SELECT p.photo_date, p.user_id, u.user_name, p.photo_id FROM photos p INNER JOIN users u ON p.user_id = u.user_id WHERE p.photo_id IN ('. (implode(',',$photo_ids)) .')');//look up user_id for photoUrl
							if($result3){
								$photos = array();
								while ($row = DB::fetchAssoc($result3)) {
									$photos[$row['photo_id']] = array('user_id'=>$row['user_id'],'date'=>$row['photo_date'], 'user_name'=>$row['user_name']);
								}
								foreach($cats as $cat_id=>$cat){
									if(isset($photos[$cat['photo_id']])){
										$p = $photos[$cat['photo_id']];
										$photo = $xmlobj->addChild('photo');
										$photo->addChild('id', $cat['photo_id']);
										$photo->addChild('category_name',htmlspecialchars($cat['category_name'], ENT_QUOTES, 'UTF-8'));
										$photo->addChild('category_id',$cat_id);
										$photo->addChild('user_id', $p['user_id']);
										$photo->addChild('date', $p['date']);
										$photo->addChild('user_name', htmlspecialchars($p['user_name'], ENT_QUOTES, 'UTF-8'));
									}
								}
								$this->saveToCache(__METHOD__, $this->send(), false, 3600);//refresh every hour
							}else $this->throwError(7);
						}else $this->throwError(9);
					}else $this->throwError(7);
				}else $this->throwError(7);
			}else $this->throwError(7);
		//}else $xmlobj = $this->getXMLObject($xmlstr);
		return $xmlobj;
	}
	public function getCategories(){/*return all possible categories*/
		if(!($xmlstr = $this->getFromCache(__METHOD__))){
			$xmlobj = $this->getXMLObject();
			$result = DB::query('SELECT category_id, category_name, category_description FROM photo_categories ORDER BY category_name ASC;');
			if($result){
				while ($row = DB::fetchAssoc($result)) {
					$cat = $xmlobj->addChild('category');
					$cat->addChild('id', $row['category_id']);
					$cat->addChild('name', htmlspecialchars($row['category_name'], ENT_QUOTES, 'UTF-8'));
					$cat->addChild('desc', htmlspecialchars($row['category_description'], ENT_QUOTES, 'UTF-8'));
				}
				$this->saveToCache(__METHOD__, $this->send(), false, 0);
			}else $this->throwError(7);
		}else $xmlobj = $this->getXMLObject($xmlstr);
		return $xmlobj;
	}
	public function removePhoto($pid, $dependent=true, $bypassSecureLoggedIn=false, $reset_camera=true, $update_user_photo_count=true, $update_city_photo_count=true){/*dependent: if true delete all dependent rows*/
		$xmlobj = $this->getXMLObject();
		if(!$bypassSecureLoggedIn){
			if(!$this->access->isSecureLoggedIn()){
				$this->throwError(8);
				return $xmlobj;
			}
		}
		$pid = (int)$pid;
		$uid = (int)$this->access->getUserId();//you can only remove your own photos!
		if($uid>0){
			if($update_city_photo_count){
				$result = DB::query('SELECT city_id FROM photos WHERE photo_id = '.$pid.' LIMIT 0,1');
				if($result && DB::numRows($result)>0)$city_id = DB::result($result, 0);
			}
			DB::query('DELETE FROM photos WHERE photo_id='.$pid.' AND user_id='.$uid.';');
			if(DB::affectedRows()>0){
				if($dependent){
					DB::query('DELETE FROM rating WHERE photo_id='.$pid);
					DB::query('DELETE FROM favorite_photos WHERE photo_id='.$pid);
					DB::query('DELETE FROM photo_comments WHERE photo_id='.$pid);
					DB::query('DELETE FROM photo2category WHERE photo_id='.$pid);
					DB::query('DELETE FROM photo_tag2photo WHERE photo_id='.$pid);
				}
				if($reset_camera)$this->resetUserCamera($uid);
				if($update_user_photo_count)$this->user->updatePhotoCount($uid);
				if(isset($city_id))$this->city->updatePhotoCount($city_id);
				@unlink(Utils::getPhotoPath($uid,$pid,'thumb'));
				Utils::s3_delete(Utils::getPhotoPath($uid,$pid,'thumb'));
				@unlink(Utils::getPhotoPath($uid,$pid,'medium'));
				Utils::s3_delete(Utils::getPhotoPath($uid,$pid,'medium'));
				@unlink(Utils::getPhotoPath($uid,$pid,'large'));
				Utils::s3_delete(Utils::getPhotoPath($uid,$pid,'large'));
				@unlink(Utils::getPhotoPath($uid,$pid,'full'));
				Utils::s3_delete(Utils::getPhotoPath($uid,$pid,'full'));
				@unlink(Utils::getPhotoPath($uid,$pid,'original'));
				Utils::s3_delete(Utils::getPhotoPath($uid,$pid,'original'));

			}
		}else $this->throwError(8);
		return $xmlobj;
	}
	public function removePhotos($pids){/*array of photo ids*/
		$xmlobj = $this->getXMLObject();
		if(is_array($pids)){
			if(!$this->access->isSecureLoggedIn()){
				$this->throwError(8);
				return $xmlobj;
			}
			foreach($pids as $pid){
				$this->removePhoto($pid, true, true, false, false, true);
			}
			if($uid = $this->access->getUserId()){
				$this->resetUserCamera($uid);
				$this->user->updatePhotoCount($uid);
			}
		}
		return $xmlobj;
	}
	protected function throwError($code=1, $msg = ''){
		switch($code){
			case 1:$msg='Missing photo id';break;
			case 2:$msg='No record found';break;
			case 4:$msg='Missing rating value';break;
			case 5:$msg='Fill in all the required fields';break;
			case 6:$msg='Could not add comment';break;
			case 7:$msg='Error executing query';break;
			case 8:$msg='You have to be signed in.';break;
			case 9:$msg='Missing photos per category.';break;
			case 10:$msg='Upload failed.';break;
			case 11:$msg='This photo is already a favorite!';break;
			case 12:$msg='Oops! Too fast';break;
			case 13:$msg='Could not add tag';break;
			case 14:$msg='Maximum number of tags reached';break;
			case 15:$msg='Could not remove tag';break;
			case 16:$msg='Missing parameter';break;
			case 17:$msg='You are not allowed to rate (yet).';break;
			case 18:$msg='You haven\'t entered a comment.';break;
		}
		parent::throwError(self::ERRBASE+$code, $msg);
	}
}
?>
