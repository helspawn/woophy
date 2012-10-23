<?php
require_once CLASS_PATH.'Mail.class.php';
require_once CLASS_PATH.'Blog.class.php';
class TravelBlog extends Blog{
	const ERRBASE = 1700;
	const CATEGORY_ID = Blog::CATEGORY_ID_TRAVELBLOG;
	public function getBlogById($blog_id){
		$XMLObject = $this->getXMLObject();
		if((int)$blog_id>0){
			if($result = DB::query('SELECT travelblogs.*, users.user_name FROM travelblogs INNER JOIN users ON travelblogs.user_id=users.user_id WHERE travelblog_id = '.$blog_id)){
				if(DB::numRows($result)){
					$row = DB::fetchAssoc($result);
					$XMLObject->addChild('id', $row['travelblog_id']);
					$XMLObject->addChild('user_id', $row['user_id']);
					$XMLObject->addChild('user_name', htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
					$XMLObject->addChild('description', htmlspecialchars($row['travelblog_description'], ENT_QUOTES, 'UTF-8'));
					$XMLObject->addChild('title', htmlspecialchars($row['travelblog_title'], ENT_QUOTES, 'UTF-8'));
					$XMLObject->addChild('date', $row['travelblog_date']);
				
				}else $this->throwError(3);
			}else $this->throwError(2);
		}else $this->throwError(1);
		return $XMLObject;
	}
	public function getRecentPosts($limit=10){//Returns most recent post of different blogs
		if($xmlstr = $this->getFromCache(__METHOD__)){
			$XMLObject = $this->getXMLObject($xmlstr);
		}else{
			$XMLObject = $this->getXMLObject();
			if($result = DB::query('SELECT MAX(post_id) AS post_id, travelblog_id FROM blog_posts WHERE travelblog_id >0 AND post_status = \'published\' AND post_publication_date <= NOW() GROUP BY travelblog_id LIMIT 0, '.min(10,max(0,(int)$limit)))){
				$ids = array();
				while ($row = DB::fetchAssoc($result)) array_push($ids, $row['post_id']);
				if(count($ids)>0){
					if($result = DB::query('SELECT post_id, blog_posts.user_id, post_publication_date, users.user_name, travelblogs.post_count FROM blog_posts INNER JOIN users ON blog_posts.user_id = users.user_id INNER JOIN travelblogs ON blog_posts.travelblog_id = travelblogs.travelblog_id WHERE blog_posts.post_id IN ('.implode(',',$ids).') ORDER BY post_id DESC;')){
						while($row = DB::fetchAssoc($result)){
							$blog = $XMLObject->addChild('post');
							$blog->addChild('id', $row['post_id']);
							$blog->addChild('user_id', $row['user_id']);
							$blog->addChild('user_name', htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
							$blog->addChild('post_count', $row['post_count']);
							$blog->addChild('date', $row['post_publication_date']);
						}
						$this->saveToCache(__METHOD__, $this->send(), false, 0);//TRICKY: cache gets updated through Blog class
					}else $this->throwError(2);
				}else $this->throwError(5);
			}else $this->throwError(2);
		}
		return $XMLObject;
	}
	//TODO: this function is very similar to Blog.getArchiveByCategoryId
	public function getArchiveByBlogId($bid=NULL, $offset=0, $count=10){
		$XMLObject = $this->getXMLObject();
		$bid = (int)$bid;
		if($bid>0){
			$result = DB::query('SELECT post_id, post_title, post_publication_date FROM blog_posts WHERE travelblog_id = '.$bid.' AND blog_posts.post_status = \'published\' AND post_publication_date <= NOW() ORDER BY post_publication_date DESC, post_id DESC LIMIT '.max(0,(int)$offset).','.min(100,max(0,(int)$count)).';');
			if($result && DB::numRows($result) > 0){
				while($row = DB::fetchAssoc($result)){
					$post = $XMLObject->addChild('post');
					$post->addChild('id', $row['post_id']);
					//$post->addChild('title', htmlspecialchars($row['post_title'], ENT_QUOTES, 'UTF-8'));
					$post->addChild('title', $row['post_title']);//do not escape for ajax
					$post->addChild('date', Utils::formatDateShort($row['post_publication_date']));
					$post->addChild('post_age',  Utils::dateDiff(strtotime($row['post_publication_date'])));
				}
			}else $this->throwError(2);
			
		}else $this->throwError(1);
		return $XMLObject;
	}
	public function getPostOffsetByBlogId($pid=NULL, $bid=NULL, $count=10){//returns offset and total num of posts
		$XMLObject = $this->getXMLObject();
		$offset = 0;
		$total = 0;
		if(isset($bid)){
			$result = DB::query('SELECT post_id FROM blog_posts WHERE travelblog_id = '.(int)$bid.' AND blog_posts.post_status = \'published\' ORDER BY post_publication_date DESC, post_id DESC;');
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
	public function getPostsByBlogId($blog_id, $limit=1){
		$XMLObject = $this->getXMLObject();
		if((int)$blog_id>0){
			$result = DB::query('SELECT post_id, post_title, post_text, post_video, post_publication_date, post_views, comment_count, users.user_name FROM blog_posts 
			INNER JOIN users ON blog_posts.user_id = users.user_id 
			WHERE travelblog_id = '.(int)$blog_id.' AND blog_posts.post_status = \'published\' AND post_publication_date <= NOW() 
			ORDER BY post_publication_date DESC, post_id DESC LIMIT 0,'.min(100,max(0,(int)$limit)));
			if($result && DB::numRows($result) > 0){
				while($row = DB::fetchAssoc($result)){
					$post = $XMLObject->addChild('post');
					$post->addChild('id', $row['post_id']);
					$post->addChild('title', htmlspecialchars($row['post_title'], ENT_QUOTES, 'UTF-8'));
					$post->addChild('user_name', htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
					$post->addChild('publication_date', $row['post_publication_date']);
					$post->addChild('views', $row['post_views']);
					$post->addChild('comment_count', $row['comment_count']);
					$post->addChild('text', htmlspecialchars($row['post_text'], ENT_QUOTES, 'UTF-8'));
					if(!is_null($row['post_video']))$post->addChild('video', htmlspecialchars($row['post_video'], ENT_QUOTES, 'UTF-8'));
				}
			}else $this->throwError(2);
		}else $this->throwError(1);
		return $XMLObject;
	}

	public function getBlogByUserId($user_id){//get last blog by user id, (one can have more travelblogs, but the last blog should be the only active blog!)
		$XMLObject = $this->getXMLObject();
		if((int)$user_id>0){
			if($result = DB::query('SELECT travelblogs.travelblog_id, travelblogs.travelblog_title FROM travelblogs WHERE user_id = '.(int)$user_id.' ORDER BY travelblog_id DESC LIMIT 0,1')){
				while($row = DB::fetchAssoc($result)){
					$XMLObject->addChild('title',htmlspecialchars($row['travelblog_title'], ENT_QUOTES, 'UTF-8'));
					$XMLObject->addChild('id', $row['travelblog_id']);
				}
			}else $this->throwError(2);
		}else $this->throwError(4);
		return $XMLObject;
	}
	protected function throwError($code=1, $msg = ''){
		switch($code){
			case 1:$msg='Missing blog id.';break;
			case 2:$msg='Error executing query.';break;
			case 3:$msg='No blog found.';break;
			case 4:$msg='Missing user id.';break;
			case 5:$msg='No posts found.';break;
		}
		parent::throwError(self::ERRBASE+$code, $msg);
	}
}
?>