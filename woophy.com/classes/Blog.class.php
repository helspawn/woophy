<?php
require_once CLASS_PATH.'Response.class.php';
require_once CLASS_PATH.'Image.class.php';
require_once CLASS_PATH.'Status.class.php';
require_once CLASS_PATH.'City.class.php';
class Blog extends Response{

	const CATEGORY_ID_USER = 1;//TODO: retreive category id from dbase
	const CATEGORY_ID_NEWSLETTER = 2;
	const CATEGORY_ID_SPECIAL = 3;
	const CATEGORY_ID_TRAVELBLOG = 4;
	const MIN_PHOTO_COUNT = 2;//min number of uploaded photos before you are allowed to blog (anti spam)
	const ERRBASE = 400;
	private $access;
	private $status;
	public function __construct() {
		$this->access = ClassFactory::create('Access');
		$this->status = new Status();
		$this->city = new City();
		parent::__construct();
	}
	public function getCacheKey($category_id){
		if(isset($category_id)){
			switch($category_id){
				case self::CATEGORY_ID_USER:
					$key = 'Blog::getUserPosts';break;
				case self::CATEGORY_ID_NEWSLETTER:
					$key = 'Blog::getNewsletterPosts';break;
				case self::CATEGORY_ID_SPECIAL:
					$key = 'Blog::getSpecialPosts';break;
				case self::CATEGORY_ID_TRAVELBLOG:
					$key = 'Blog::getTravelblogPosts';break;
				default:
					$key = FALSE;
			}
		}else $key = 'Blog::getRecentPosts';
		return $key;
	}
	public function getRecentPostsByCategoryId($cid=NULL, $limit=10, $widget=FALSE){
		$XMLObject = $this->getXMLObject('');
		if($key = $this->getCacheKey($cid).($widget?'::W':'')){
			
			if($xmlstr = $this->getFromCache($key)){
				$XMLObject = $this->getXMLObject($xmlstr);
			}else{
				$cid = (int)$cid;
				$limit = max(0, min(10, (int)$limit));
				$query = 'SELECT post_id FROM blog_posts WHERE ';
				if($cid>0)$query .= 'category_id = '.$cid.' AND ';
				$query .= 'blog_posts.post_status = \'published\' AND post_publication_date <= NOW() ORDER BY post_publication_date DESC, post_id DESC LIMIT 0,'.$limit.';';
				$result = DB::query($query);
				if($result){
					$post_ids = array();
					while ($row = DB::fetchAssoc($result)) array_push($post_ids, $row['post_id']);
					if(count($post_ids)>0){
						$this->getRecentPosts($XMLObject, $post_ids, $widget);
						$this->saveToCache($key, $this->send(), false, 3600); //cache 1 hour, so views get updated
					}
				}else $this->throwError(9);
			}
		}else $this->throwError(1);
		return $XMLObject;
	}
	public function getRecentPostsByUserId($uid=NULL, $limit=10){
		$XMLObject = $this->getXMLObject();
		if(isset($uid)){
			$limit = max(0,min(50,(int)$limit));
			$result = DB::query('SELECT post_id FROM blog_posts WHERE user_id ='.(int)$uid.' AND post_status = \'published\' AND post_publication_date <= NOW() ORDER BY post_publication_date DESC, post_id DESC LIMIT 0,'.$limit);
			if($result){
				$post_ids = array();
				while ($row = DB::fetchAssoc($result)) array_push($post_ids, $row['post_id']);
				$this->getRecentPosts($XMLObject, $post_ids);
			}else $this->throwError(9);
		}else $this->throwError(10);
		return $XMLObject;
	}
	public function getPostsByUserId($uid=NULL, $offset=0, $limit=10){/* only use this in account section, because there no check on publication_date */
		$XMLObject = $this->getXMLObject();
		if(isset($uid)){
			$limit = max(0,min(50,(int)$limit));
			$result = DB::query('SELECT post_id, post_title, post_status, post_publication_date, blog_posts.category_id, category_name, comment_count FROM blog_posts 
			INNER JOIN blog_categories ON blog_posts.category_id = blog_categories.category_id
			WHERE user_id = '.(int)$uid.' 
			ORDER BY post_publication_date DESC, post_id DESC LIMIT '.max(0,(int)$offset).','.$limit.';');
			if($result && DB::numRows($result) > 0){
				while($row = DB::fetchAssoc($result)){
					$post = $XMLObject->addChild('post');
					$post->addChild('id', $row['post_id']);
					$post->addChild('category_id', $row['category_id']);
					$post->addChild('category_name', htmlspecialchars($row['category_name'], ENT_QUOTES, 'UTF-8'));
					$post->addChild('status', $row['post_status']);
					$post->addChild('title', htmlspecialchars($row['post_title'], ENT_QUOTES, 'UTF-8'));
					$post->addChild('publication_date', $row['post_publication_date']);
					$post->addChild('comment_count', $row['comment_count']);
				}
				$result_count = DB::query('SELECT count(0) FROM blog_posts WHERE user_id = '.(int)$uid.';');
				if($result_count) $XMLObject['total_posts'] = DB::result($result_count, 0);
			}else $this->throwError(2);
		}else $this->throwError(10);
		return $XMLObject;
	}
	public function isBlogEnabled($uid){
		$result = DB::query('SELECT count(0) FROM blog_user2category WHERE user_id = '.(int)$uid);
		return (int)DB::result($result, 0)>0;
	}
	public function getCategoriesByUserId($uid){
		$XMLObject = $this->getXMLObject();
		if(isset($uid)){
			$result = DB::query('SELECT blog_categories.category_name,blog_user2category.category_id FROM blog_user2category INNER JOIN blog_categories ON blog_user2category.category_id = blog_categories.category_id WHERE blog_user2category.user_id = '.(int)$uid.';');
			if($result){
				while($row = DB::fetchAssoc($result)){
					$cat = $XMLObject->addChild('category');
					$cat->addChild('id', $row['category_id']);
					$cat->addChild('name', htmlspecialchars($row['category_name'], ENT_QUOTES, 'UTF-8'));
				}
			}else $this->throwError(2);
		}else $this->throwError(8);
		return $XMLObject;
	}
	public function getPrevNextByUserId($uid=NULL, $pid=NULL, $pubdate=NULL){//returns previous and next post_id of userblogpost
		return $this->getPrevNextByColumn('user_id', $uid, $pid, $pubdate);
	}
	public function getPrevNextByCategoryId($cid=NULL, $pid=NULL, $pubdate=NULL){//returns previous and next post_id of userblogpost
		return $this->getPrevNextByColumn('category_id', $cid, $pid, $pubdate);
	}
	public function getLastPostByUserId($uid=NULL){
		$XMLObject = $this->getXMLObject();
		if(isset($uid)){
			$result = DB::query('SELECT post_id FROM blog_posts WHERE user_id ='.(int)$uid.' AND post_status = \'published\' AND post_publication_date <= NOW() ORDER BY post_publication_date DESC, post_id DESC LIMIT 0, 1');
			if($result){
				if(DB::numRows($result) == 1) return $this->getPostById(DB::result($result, 0), $uid, true, true);
				else $this->throwError(4);
			}else $this->throwError(9);
		}else $this->throwError(10);
		return $XMLObject;
	}
	public function getPostById($pid=NULL,$uid=NULL,$increaseViews=true,$published=FALSE){//uid is optional
		$XMLObject = $this->getXMLObject();
		if(isset($pid)){
			$query = 'SELECT blog_posts.user_id, category_id, travelblog_id, post_id, post_title, post_text, post_views, comment_count, post_video, post_status, post_publication_date, user_name, blog_posts.city_id, cities.CC1, cities.FULL_NAME_ND FROM blog_posts INNER JOIN users ON blog_posts.user_id = users.user_id LEFT JOIN cities ON blog_posts.city_id=cities.UNI WHERE post_id = '.(int)$pid;
			if(isset($published) && $published==TRUE) $query .= ' AND post_status=\'published\'';
			if(isset($uid)) $query .= ' AND blog_posts.user_id = '.(int)$uid;//extra check on uid
			$query .= ' LIMIT 0, 1;';
			$result = DB::query($query);
			if($result){
				if(DB::numRows($result) == 1){
					$row = DB::fetchAssoc($result);
					$XMLObject->addChild('id', $row['post_id']);
					$XMLObject->addChild('title', htmlspecialchars($row['post_title'], ENT_QUOTES, 'UTF-8'));
					$XMLObject->addChild('user_name', htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
					$XMLObject->addChild('user_id', $row['user_id']);
					$XMLObject->addChild('text', htmlspecialchars($row['post_text'], ENT_QUOTES, 'UTF-8'));
					if(!is_null($row['post_video']))$XMLObject->addChild('video', htmlspecialchars($row['post_video'], ENT_QUOTES, 'UTF-8'));
					$XMLObject->addChild('status', $row['post_status']);
					$XMLObject->addChild('publication_date', $row['post_publication_date']);
					$XMLObject->addChild('category_id', $row['category_id']);
					$XMLObject->addChild('city_id', is_null($row['city_id'])?'':$row['city_id']);
					$XMLObject->addChild('city_name', is_null($row['FULL_NAME_ND'])?'':htmlspecialchars($row['FULL_NAME_ND'], ENT_QUOTES, 'UTF-8'));
					$XMLObject->addChild('country_code', is_null($row['CC1'])?'':$row['CC1']);
					$XMLObject->addChild('views', $row['post_views']);
					$XMLObject->addChild('comment_count', $row['comment_count']);
					$XMLObject->addChild('travelblog_id', is_null($row['travelblog_id'])?'':$row['travelblog_id']);
					if($increaseViews)DB::query('UPDATE blog_posts SET post_views = post_views + 1 WHERE post_id = '.(int)$pid);
				}else $this->throwError(4);
			}else $this->throwError(9);
		}else $this->throwError(3);
		return $XMLObject;
	}
	public function getCommentsByPostId($pid=NULL){
		$XMLObject = $this->getXMLObject();
		if(isset($pid)){
			$result = DB::query('SELECT comment_id, comment_text, comment_date, user_id, user_name FROM blog_comments WHERE post_id = '.(int)$pid.' ORDER BY comment_id DESC LIMIT 0,100;');//TODO:: paging
			if($result){
				while($row = DB::fetchAssoc($result)){
					$comment = $XMLObject->addChild('comment');
					$comment->addChild('id', $row['comment_id']);
					$comment->addChild('user_id', $row['user_id']);
					$comment->addChild('text', htmlspecialchars($row['comment_text'], ENT_QUOTES, 'UTF-8'));
					$comment->addChild('date', $row['comment_date']);
					$comment->addChild('user_name', htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
				}
			}else $this->throwError(2);
		}else $this->throwError(3);
		return $XMLObject;
	}
	public function addCommentByPostId($pid=NULL, $text=NULL){
		$XMLObject = $this->getXMLObject();
		if($this->access->isLoggedIn()){
			if(isset($pid)){
				if(isset($text)){
					$t = Utils::filterText($text,true,false,true);
					if(mb_strlen($t)>0){
						$poster_name = $this->access->getUserName();
						if(DB::query('INSERT INTO blog_comments (comment_text, post_id, user_id, user_name) VALUES (\''. $t .'\', \''. (int)$pid .'\', \''. (int)$this->access->getUserId() .'\', \''. DB::escape($poster_name) .'\')')){
							DB::query('UPDATE blog_posts SET comment_count = (SELECT COUNT(*) FROM blog_comments WHERE post_id = '. (int)$pid .') WHERE post_id = \''. (int)$pid .'\';');

							//send notify mail:
							$result = DB::query('SELECT email, notify_comments FROM users INNER JOIN blog_posts ON users.user_id = blog_posts.user_id WHERE blog_posts.post_id = '.(int)$pid.' LIMIT 0,1');
							if($result){
								if(DB::numRows($result)==1){
									$row = DB::fetchAssoc($result);
									if($row['notify_comments']==1){
										$body = $text;
										$body .= "\r\n\r\n".ABSURL.REQUEST_PATH;
										$body .= "\r\n\r\nDo not reply to this email, but please post your comment through the web interface available at the above link.";

										include_once CLASS_PATH.'Mail.class.php';
										$mail = new Mail();
										$mail->From(EMAIL_SENDER, NOREPLY_EMAIL_ADDRESS);
										$mail->To($row['email']);
										$mail->Subject($poster_name.' has left a comment on one of your blogposts');
										$mail->Body($body);
										$mail->Send();
									}
								}
							}
						}else $this->throwError(7);
					}else $this->throwError(6);
				}else $this->throwError(6);
			}else $this->throwError(5);
		}else $this->throwError(13);
		return $XMLObject;
	}

	public function getArchiveByCategoryId($cid=NULL, $offset=0, $limit=10){
		$XMLObject = $this->getXMLObject();
		$cid = (int)$cid;
		if($cid>0){
			$result = DB::query('SELECT blog_posts.user_id, users.user_name, post_id, post_title, post_publication_date, NOW() as server_time, post_lastbuild_date FROM blog_posts INNER JOIN users ON blog_posts.user_id = users.user_id WHERE category_id = '.$cid.' AND blog_posts.post_status = \'published\' AND post_publication_date <= NOW() ORDER BY post_publication_date DESC, post_id DESC LIMIT '.max(0,(int)$offset).','.min(100,max(0,(int)$limit)).';');
			if($result && DB::numRows($result) > 0){
				while($row = DB::fetchAssoc($result)){
					$time_diff = (int)(strtotime($row['server_time']) - time());
					$post = $XMLObject->addChild('post');
					$post->addChild('id', (int)$row['post_id']);
					$post->addChild('title', $row['post_title']);
					$post->addChild('user_name', htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
					$post->addChild('avatar_url', AVATARS_URL.$row['user_id'].'.jpg');//store for xhr
					$post->addChild('time_posted', Utils::formatDateShort($row['post_lastbuild_date']));//store for xhr
					$post->addChild('post_age', Utils::dateDiff(strtotime($row['post_lastbuild_date'])-$time_diff));//store for xhr
					$post->addChild('date', Utils::formatDateShort($row['post_publication_date']));
				}
			}else $this->throwError(2);
			
		}else $this->throwError(1);
		return $XMLObject;
	}

	public function getArchiveByUserId($uid=NULL, $offset=0, $limit=10){
		$XMLObject = $this->getXMLObject();
		$uid = (int)$uid;
		if($uid>0){
			$result = DB::query('SELECT post_id, post_title, post_publication_date FROM blog_posts WHERE user_id = '.$uid.' AND blog_posts.post_status = \'published\' AND post_publication_date <= NOW() ORDER BY post_publication_date DESC, post_id DESC LIMIT '.max(0,(int)$offset).','.min(100,max(0,(int)$limit)).';');
			if($result && DB::numRows($result) > 0){
				while($row = DB::fetchAssoc($result)){
					$post = $XMLObject->addChild('post');
					$post->addChild('id', (int)$row['post_id']);
					$post->addChild('title', $row['post_title']);//do not escape for ajax
					$post->addChild('date', Utils::formatDateShort($row['post_publication_date']));
					$post->addChild('post_age', Utils::dateDiff(strtotime($row['post_publication_date'])));
				}
			}else $this->throwError(2);
		}else $this->throwError(1);
		return $XMLObject;
	}


	public function getPostOffsetByuserId($pid=NULL, $uid=NULL, $count=10){//returns offset and total num of posts
		$XMLObject = $this->getXMLObject();
		$offset = 0;
		$total = 0;
		if(isset($uid)){
			$result = DB::query('SELECT post_id FROM blog_posts WHERE user_id = '.(int)$uid.' AND blog_posts.post_status = \'published\' ORDER BY post_publication_date DESC, post_id DESC LIMIT 0,500;');//TRICKY: use limit to avoid possible slow query
			if($result){
				$a = $this->getPostOffset($result, $pid, $count);
				$total = $a['total'];
				$offset = $a['offset'];
			}else $this->throwError(4);
		}else $this->throwError(1);
		$XMLObject->addChild('offset', $offset);
		$XMLObject->addChild('total', $total);
		return $XMLObject;
	}
	
	public function getPostOffsetByCategoryId($pid=NULL, $cid=NULL, $count=10){//returns offset and total num of posts
		$XMLObject = $this->getXMLObject();
		$offset = 0;
		$total = 0;
		if(isset($cid)){
			$result = DB::query('SELECT post_id FROM blog_posts WHERE category_id = '.(int)$cid.' AND blog_posts.post_status = \'published\' ORDER BY post_publication_date DESC, post_id DESC LIMIT 0,500;');//TRICKY: use limit to avoid possible slow query
			if($result){
				$a = $this->getPostOffset($result, $pid, $count);
				$total = $a['total'];
				$offset = $a['offset'];
			}else $this->throwError(4);
		}else $this->throwError(1);
		$XMLObject->addChild('offset', $offset);
		$XMLObject->addChild('total', $total);
		return $XMLObject;
	}
	public function search($val='', $offset=0, $limit=50, $category_id){
		$XMLObject = $this->getXMLObject();
		
		$val = DB::escapeMatchPattern($val);
		$offset = max(0,(int)$offset);
		$limit = min(50,max(0,(int)$limit));

		//avoid SQL_CALC_FOUND_ROWS: http://bugs.mysql.com/bug.php?id=18454

		$query = 'SELECT 
					blog_posts.post_publication_date, 
					blog_posts.post_id, 
					blog_posts.post_title,
					blog_posts.category_id,
					users.user_name
					FROM blog_posts
					INNER JOIN users ON blog_posts.user_id = users.user_id
					WHERE MATCH (blog_posts.post_title, blog_posts.post_text)
					AGAINST (\''.$val.'\') ';
		if((int)$category_id>0)$query .= 'AND blog_posts.category_id = '.(int)$category_id.' ';
		$query .= 'AND blog_posts.post_status = \'published\' LIMIT '.$offset.', '.$limit.';';
		$result = DB::query($query);
	
		if($result){
			$num_rows = DB::numRows($result);
			$XMLObject['total_posts'] = $num_rows;
			if($num_rows>0){
				while($row = DB::fetchAssoc($result)){
					$post = $XMLObject->addChild('post');
					$post->addChild('id', $row['post_id']);
					$post->addChild('category_id', $row['category_id']);
					$post->addChild('title', htmlspecialchars($row['post_title'], ENT_QUOTES, 'UTF-8'));
					$post->addChild('user_name', htmlspecialchars($row['user_name'], ENT_QUOTES));
					$post->addChild('publication_date', $row['post_publication_date']);
				}
				if($offset+$num_rows>=$limit){
					//total number:
					$query = 'SELECT COUNT(0) FROM blog_posts WHERE MATCH (blog_posts.post_title, blog_posts.post_text) AGAINST (\''.$val.'\') ';
					if((int)$category_id>0)$query .= 'AND blog_posts.category_id = '.(int)$category_id.' ';
					$query .= 'AND blog_posts.post_status = \'published\'';
					$result_count = DB::query($query);
					if($result_count) $XMLObject['total_posts'] = DB::result($result_count, 0);
				}
			}
		}else $this->throwError(9);
		return $XMLObject;
	}
	public function searchByCategoryId($category_id=1, $val='', $offset=0, $limit=50){
		return $this->search($val, $offset, $limit, $category_id);
	}
	public function addPost($post_title, $post_text, $post_status, $post_publication_date, $category_id=1, $travelblog_id, $UFI){
		$XMLObject = $this->getXMLObject();
		if($user_id = $this->access->getUserId()){

			if($category_id == self::CATEGORY_ID_USER){
				//is member allowed to post? check on photo count
				$result = DB::query('SELECT photo_count FROM users WHERE user_id='.(int)$user_id);
				if($result){
					if(DB::numRows($result)==1){
						if((int)DB::result($result, 0)<self::MIN_PHOTO_COUNT){
							$this->throwError(14);
							return $XMLObject;
						}
					}
				}
			}
			$pd = $post_publication_date;
			$pd = is_null($pd) || mb_strlen($pd) == 0 ? 'NULL' : '\''.DB::escape($pd).'\'';
			$tid = (int)$travelblog_id;
			if($tid>0){//only set city id on travelblogs
				if(isset($UFI) && mb_strlen($UFI)>0){
					$xml_city = $this->city->getCityByUFI($UFI);
					$UNI = $xml_city->UNI;
				}
			}else $tid = 'NULL';
			$query = 'INSERT INTO blog_posts (user_id, post_title, post_text, post_status, post_publication_date, category_id,travelblog_id,city_id) VALUES (\''.(int)$user_id.'\',\''.Utils::filterText($post_title).'\',\''.Utils::filterText($post_text,false,true,true).'\',\''.DB::escape($post_status).'\', '.$pd.',\''.(int)$category_id.'\','.$tid.','.(isset($UNI)?(int)$UNI:'NULL').');';
			if(!DB::query($query))$this->throwError(9);
			else{
				$XMLObject->addChild('post_id', DB::insertId());
				if(strtotime($post_publication_date)<=strtotime(date('Y-m-d')) && $post_status == 'published') $this->updateCaches($post_title, $post_status, $post_publication_date, $category_id, $travelblog_id);
			}
		}else $this->throwError(10);
		return $XMLObject;
	}
	public function updatePost($post_id, $post_title, $post_text, $post_status, $post_publication_date, $category_id=1, $travelblog_id, $UFI, $old_city_id){
		$XMLObject = $this->getXMLObject();
		if($user_id = $this->access->getUserId()){
			$pd = $post_publication_date;
			$pd = is_null($pd) || mb_strlen($pd) == 0 ? 'NULL' : '\''.DB::escape($pd).'\'';
			$tid = (int)$travelblog_id;
			if($tid>0){//only set city id on travelblogs
				if(isset($UFI) && mb_strlen($UFI)>0){
					$xml_city = $this->city->getCityByUFI($UFI);
					$UNI = $xml_city->UNI;
				}elseif(isset($old_city_id) && mb_strlen($old_city_id)>0)$UNI=$old_city_id;
			}	
			$result = DB::query('SELECT post_status, post_publication_date FROM blog_posts WHERE post_id ='.(int)$post_id);
			$old_status = '';
			$old_publication_date = '';
			if($result){
				$row = DB::fetchAssoc($result);
				$old_status = $row['post_status'];
				$old_publication_date = $row['post_publication_date'];
			}
			$query = 'UPDATE blog_posts SET post_title = \''.Utils::filterText($post_title).'\',
			post_text = \''.Utils::filterText($post_text,false,true,true).'\',
			post_status = \''.DB::escape($post_status).'\',
			post_publication_date = '.$pd.',
			category_id = '.(int)$category_id.',
			travelblog_id = '.($tid>0?$tid:'NULL').',
			city_id = '.(isset($UNI)?(int)$UNI:'NULL').' 
			WHERE post_id = '.(int)$post_id.' AND user_id = '.(int)$user_id.';';
			if(!DB::query($query))$this->throwError(9);
			else if($old_status != $post_status || $old_publication_date != $post_publication_date)$this->updateCaches($post_title, $post_status, $post_publication_date, $category_id, $travelblog_id);
		}else $this->throwError(10);
		return $XMLObject;
	}
	public function deletePost($post_id){
		$XMLObject = $this->getXMLObject();
		if($user_id = $this->access->getUserId()){
			$post_id = (int)$post_id;
			if($post_id>0){
				//check on both user en post id
				DB::query('DELETE FROM blog_posts WHERE post_id = '.$post_id.' AND user_id = '.$user_id);
				if(DB::affectedRows()>0){
					DB::query('DELETE FROM blog_comments WHERE post_id = '.$post_id);
					DB::query('DELETE FROM blog_newsletters WHERE post_id = '.$post_id);
					
					$this->updateCaches();

				}else $this->throwError(12);
			}else $this->throwError(11);
		}else $this->throwError(10);
		return $XMLObject;
	}
	//TODO: combine this function with Photo.addPhoto!!
	public function addPhoto($file){
		$XMLObject = $this->getXMLObject();
		if($uid = $this->access->getUserId()){
			$img = new Image($file);
			if($img->isImage()){
				$w = $img->getWidth();
				$h = $img->getHeight();
				$d = $img->getDimensions($w, $h);
				$query = 'INSERT INTO blog_photos (user_id, width, height) VALUES ('.(int)$uid.', '.$d['width'].', '.$d['height'].')';
				if(DB::query($query)){
					$bln_success = FALSE;
					$pid = DB::insertId();
					//create image:
					if($img->output(MAX_PHOTO_WIDTH_THUMB, MAX_PHOTO_WIDTH_THUMB, Utils::getPhotoPath($uid,$pid,'thumb','blog'))){//Thumb
						if($img->output(MAX_PHOTO_WIDTH_MEDIUM, MAX_PHOTO_HEIGHT_MEDIUM, Utils::getPhotoPath($uid,$pid,'medium','blog'))){//Medium
							if($img->output(MAX_PHOTO_WIDTH_LARGE, MAX_PHOTO_HEIGHT_LARGE, Utils::getPhotoPath($uid,$pid,'large','blog'))){//Large	
								$bln_success = TRUE;
							}
						}
					}

					if(!$bln_success){
						$this->removePhoto($pid);
						parent::throwError($img->errorNo, $img->errorMessage);
					}
				}else $this->throwError(7);
			}else parent::throwError($img->errorNo, $img->errorMessage);
			$img->destroy();
		}else $this->throwError(10);
		return $XMLObject;
	}
	public function removePhoto($pid){
		$xmlobj = $this->getXMLObject();
		$pid = (int)$pid;
		if($uid = $this->access->getUserId()){//you can only remove your own photos!
			DB::query('DELETE FROM blog_photos WHERE photo_id='.$pid.' AND user_id='.(int)$uid.';');
			@unlink(Utils::getPhotoPath($uid,$pid,'thumb','blog'));
				Utils::s3_delete(Utils::getPhotoPath($uid,$pid,'thumb','blog'));
			@unlink(Utils::getPhotoPath($uid,$pid,'medium','blog'));
				Utils::s3_delete(Utils::getPhotoPath($uid,$pid,'medium','blog'));
			@unlink(Utils::getPhotoPath($uid,$pid,'large','blog'));
				Utils::s3_delete(Utils::getPhotoPath($uid,$pid,'large','blog'));
		}else $this->throwError(10);
		return $xmlobj;
	}
	public function getPhotosByUserId($uid=0, $offset=0, $limit=8){
		$XMLObject = $this->getXMLObject();
		$result = DB::query('SELECT photo_id FROM blog_photos WHERE user_id = \''.(int)$uid.'\' ORDER BY photo_id DESC LIMIT '.max(0,(int)$offset).','.max(0,(int)$limit).';');
		if($result){
			if(DB::numRows($result)>0){
				while($row = DB::fetchAssoc($result)){
					$pid = $row['photo_id'];
					$photo = $XMLObject->addChild('photo');	
					$photo->addChild('id', $pid);
					$photo->addChild('url', Utils::getPhotoUrl($uid, $pid, 'medium','blog'));
					$photo->addChild('thumb_url', Utils::getPhotoUrl($uid, $pid, 'thumb','blog'));
				}
				$result_count = DB::query('SELECT COUNT(0) FROM blog_photos WHERE user_id = \''.(int)$uid.'\'');
				if($result_count) $XMLObject['total_photos'] = DB::result($result_count, 0);
			}else $XMLObject['total_photos'] = 0;
		}else $this->throwError(9);
		return $XMLObject;
	}
	private function updateCaches($post_title=NULL, $post_status=NULL, $post_publication_date=NULL, $category_id=NULL, $travelblog_id=NULL){
		if(isset($post_title,$post_publication_date) && strtotime($post_publication_date)<=strtotime(date('Y-m-d')) && $post_status == 'published'){		
			if($user_name = $this->access->getUserName())$this->status->updateLastBlogPost($post_title, $user_name);
		}else $this->status->updateLastBlogPost();
		$travelblog_id = (int)$travelblog_id;
		if($travelblog_id>0)$this->deleteFromCache('TravelBlog::getRecentPosts');//KLUDGE: use constant for this key
		if($uid = $this->access->getUserId()){
			$query = 'UPDATE users set blog_post_count = (SELECT count(0) FROM blog_posts WHERE user_id ='.(int)$uid.' AND post_status = \'published\' AND post_publication_date <= NOW()) WHERE user_id='.(int)$uid;
			DB::query($query);
			if($travelblog_id>0){
				DB::query('UPDATE travelblogs set post_count = (SELECT count(0) FROM blog_posts WHERE travelblog_id ='.$travelblog_id.' AND post_status = \'published\' AND post_publication_date <= NOW()) WHERE travelblog_id='.$travelblog_id);
			}
		}
		$key = $this->getCacheKey($category_id);
		if($key)$this->deleteFromCache($key);
	}
	private function getRecentPosts($XMLObject, $post_ids, $widget=FALSE){//helper function
		if(count($post_ids)>0){	
			$result = DB::query('SELECT post_id, blog_posts.user_id, post_title, post_text, post_publication_date, NOW() as server_time, post_views, category_id, comment_count, users.user_name FROM blog_posts INNER JOIN users ON blog_posts.user_id = users.user_id WHERE post_id IN ('.implode(',',$post_ids).') ORDER BY post_publication_date DESC, post_id DESC');
			while($row = DB::fetchAssoc($result)){
				if($widget){
					$post = $XMLObject->addChild('item');
					$post->addAttribute('type', 'blogpost');
                   	$post->addAttribute('timestamp', strtotime($row['post_publication_date']));
				}else{
					$post = $XMLObject->addChild('post');
				}
				$post->addChild('id', $row['post_id']);
				$post->addChild('user_id', $row['user_id']);
				$post->addChild('title', htmlspecialchars($row['post_title'], ENT_QUOTES,'UTF-8'));//already escaped?
				$post->addChild('user_name', htmlspecialchars($row['user_name'], ENT_QUOTES,'UTF-8'));
				$post->addChild('publication_date', $row['post_publication_date']);
				$post->addChild('views', (int)$row['post_views']);
				$post->addChild('category_id', (int)$row['category_id']);
				$post->addChild('comment_count', (int)$row['comment_count']);
				$post->addChild('text', htmlspecialchars($row['post_text'], ENT_QUOTES,'UTF-8'));
			}
		}
	}
	private function getPrevNextByColumn($column='user_id', $value, $pid, $pubdate){//helper function
		$XMLObject = $this->getXMLObject();
		if(isset($pid, $pubdate, $value)){
			$result = DB::query('SELECT (SELECT post_id FROM blog_posts WHERE '.$column.' ='.(int)$value.' AND blog_posts.post_status = \'published\' AND post_publication_date >= \''.DB::escape($pubdate).'\' AND post_id >'.(int)$pid.' ORDER BY post_publication_date ASC , post_id ASC LIMIT 0 ,1) AS next_id, (SELECT post_id FROM blog_posts WHERE '.$column.' ='.(int)$value.' AND blog_posts.post_status = \'published\' AND post_publication_date <= \''.DB::escape($pubdate).'\' AND post_id <'.(int)$pid.' ORDER BY post_publication_date DESC , post_id DESC LIMIT 0 ,1) AS prev_id');
			if($result){
				$row = DB::fetchAssoc($result);
				if(isset($row['prev_id']))$XMLObject->addChild('prev_id', $row['prev_id']);
				if(isset($row['next_id']))$XMLObject->addChild('next_id', $row['next_id']);
			}else $this->throwError(9);
		}else $this->throwError(11);
		return $XMLObject;
	}
	protected function getPostOffset($result, $pid, $count){
		$offset = 0;
		$total = DB::numRows($result);
		$pid = (int)$pid;
		if($pid>0){
			$num = 0;
			while($row = DB::fetchAssoc($result)){
				if($row['post_id'] == $pid)break 1;
				if(fmod(++$num, $count)==0)$offset+=$count;
			}
		}
		return array('offset'=>$offset, 'total'=>$total);
	}
	protected function throwError($code=1, $msg=NULL){
		if(!isset($msg)){
			$msg = '';
			switch($code){
				case 1:$msg='Missing category id';break;
				case 2:$msg='No records found';break;
				case 3:$msg='No post id';break;
				case 4:$msg='No post found';break;
				case 5:$msg='Missing post id';break;
				case 6:$msg='Fill in all the required fields';break;
				case 7:$msg='Could not add comment';break;
				case 8:$msg='Missing contributor id';break;
				case 9:$msg='Error executing query';break;
				case 10:$msg='Missing user id';break;
				case 11:$msg='Missing parameter';break;
				case 12:$msg='Post could be deleted';break;
				case 13:$msg='You have to be signed in to post comments.';break;
				case 14:$msg='Only active members can post.';break;
			}
		}
		parent::throwError(self::ERRBASE+$code, $msg);
	}
}
?>
