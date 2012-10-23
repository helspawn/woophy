<?php
require_once CLASS_PATH.'Response.class.php';
class Newsletter extends Response{
	
	const ERRBASE = 800;
	
	public function getNewsletterByDate($date){
		$XMLObject = $this->getXMLObject();
		if(isset($date)){		
			$result = DB::query('SELECT blog_posts.post_id, blog_posts.category_id, post_views, post_title, comment_count, post_text, post_publication_date, user_name FROM blog_newsletters 
			INNER JOIN blog_posts ON blog_newsletters.post_id = blog_posts.post_id  
			INNER JOIN users ON blog_posts.user_id = users.user_id 
			WHERE newsletter_publication_date = \''.date('Y-m-d', strtotime($date)).'\' 
			ORDER BY blog_newsletters.post_order ASC, newsletter_id ASC LIMIT 0,100;');//limit?
			if($result && DB::numRows($result) > 0){
				while($row = DB::fetchAssoc($result)){
					$post = $XMLObject->addChild('post');
					$post->addChild('id', $row['post_id']);
					$post->addChild('category_id', $row['category_id']);
					$post->addChild('title', htmlspecialchars($row['post_title'], ENT_QUOTES, 'UTF-8'));
					$post->addChild('user_name', htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
					$post->addChild('publication_date', $row['post_publication_date']);
					$post->addChild('text', htmlspecialchars($row['post_text'], ENT_QUOTES, 'UTF-8'));
					$post->addChild('views', $row['post_views']);
					$post->addChild('comment_count', $row['comment_count']);
				}
			}else $this->throwError(2);
		}else $this->throwError(1);
		return $XMLObject;
	}
	public function getLastNewsletter(){
		$result = DB::query('SELECT newsletter_publication_date FROM blog_newsletters WHERE newsletter_publication_date < NOW() ORDER BY newsletter_publication_date DESC LIMIT 0,1;');
		if($result && DB::numRows($result) == 1) return $this->getNewsletterByDate(DB::result($result,0));
		else{
			$XMLObject = $this->getXMLObject();
			$this->throwError(2);
			return $XMLObject;
		}
	}
	public function getNewsletterDates(){
		$XMLObject = $this->getXMLObject();
		$result = DB::query('SELECT DISTINCT newsletter_publication_date FROM blog_newsletters ORDER BY newsletter_publication_date DESC;');
		if($result && DB::numRows($result)>0){
			while($row = DB::fetchAssoc($result)){
				$newsletter = $XMLObject->addChild('newsletter');
				$newsletter['publication_date'] = $row['newsletter_publication_date'];
			}
		}else $this->throwError(2);
		return $XMLObject;
	}
	protected function throwError($code=1, $msg = ''){
		switch($code){
			case 1:$msg='Missing date parameter';break;
			case 2:$msg='No records found';break;
		}
		parent::throwError(self::ERRBASE+$code, $msg);
	}
}