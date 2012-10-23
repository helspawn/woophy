<?php
require_once CLASS_PATH.'Response.class.php';
class Contest extends Response{
	
	const ERRBASE = 1300;

	public function getCategories(){
		$XMLObject = $this->getXMLObject();
		$result = DB::query('SELECT category_id, category_name FROM contest_categories ORDER BY category_id ASC LIMIT 0,10;');//limit??
		if($result){
			while($row = DB::fetchAssoc($result)){
				$cat = $XMLObject->addChild('category', $row['category_name']);
				$cat->addAttribute('id', $row['category_id']);
			}
		}else $this->throwError(1);
		return $XMLObject;
	}
	public function getCategoryById($id){
		$XMLObject = $this->getXMLObject();
		$result = DB::query('SELECT category_id, category_name FROM contest_categories WHERE category_id='.(int)$id.' LIMIT 0,1');//limit??
		if($result){
			while($row = DB::fetchAssoc($result)) {
				$XMLObject->addChild('name', htmlspecialchars($row['category_name'], ENT_QUOTES, 'UTF-8'));
				$XMLObject->addChild('id', $row['category_id']);
			}
		}else $this->throwError(1);
		return $XMLObject;
	}
	public function addEntry($photo_id, $category_id, $poster_id, $poster_name, $remark){
		$XMLObject = $this->getXMLObject();
		if(isset($photo_id, $category_id, $poster_id)){
			$photo_id = trim($photo_id);
			if((int)$photo_id>0 && (int)$category_id>0 && (int)$poster_id>0){
				$result = DB::query('SELECT category_id FROM contest_entries WHERE photo_id = '.(int)$photo_id.' AND category_id = '.(int)$category_id.' LIMIT 0,1;');
				if($result){
					if(DB::numRows($result)>0) $this->throwError(4);
					else{
						$result = DB::query('SELECT photo_id FROM photos WHERE photo_id ='.(int)$photo_id);
						if($result && DB::numRows($result)>0){	
							if(!DB::query('INSERT INTO contest_entries(photo_id, category_id, poster_id, poster_name, entry_remark) VALUES ('.(int)$photo_id.','.(int)$category_id.','.(int)$poster_id.',\''.DB::escape($poster_name).'\','.(isset($remark)?'\''.Utils::filterText($remark,true,false,true).'\'':'NULL').')')) $this->throwError(1);
						}else$this->throwError(3);
					}
				}else $this->throwError(1);
			}else $this->throwError(2);
		}else $this->throwError(2);
		return $XMLObject;
	}
	public function getTop100($month){
		$XMLObject = $this->getXMLObject();
		$result = DB::query('SELECT COUNT( contest_entries.photo_id ) AS num, contest_votes.entry_id, contest_entries.photo_id, contest_entries.category_id, photos.user_id FROM contest_votes INNER JOIN contest_entries ON contest_votes.entry_id = contest_entries.entry_id INNER JOIN photos ON contest_entries.photo_id = photos.photo_id WHERE value = "YES"'.(isset($month)?
		' AND DATE_FORMAT(contest_entries.entry_date,\'%m-%Y\') = \''.DB::escape($month).'\'':'').' GROUP BY contest_entries.photo_id ORDER BY num DESC LIMIT 0, 100');
		if($result){
			while($row = DB::fetchAssoc($result)){
				$entry = $XMLObject->addChild('entry');
				$entry->addChild('id', $row['entry_id']);
				$entry->addChild('photo_id', $row['photo_id']);
				$entry->addChild('user_id', $row['user_id']);
				$entry->addChild('category_id', $row['category_id']);
				$entry->addChild('vote_count', $row['num']);
			}
		}else $this->throwError(1);
		return $XMLObject;
	}
	public function getUnVotedEntriesByUserId($user_id, $limit=1, $month=NULL){
		$XMLObject = $this->getXMLObject();
		//TRICKY: possible slow query with many (10,000+) entries
		$result = DB::query('SELECT photos.user_id, e.entry_remark, e.entry_id, e.photo_id, e.category_id, e.entry_date FROM contest_entries e INNER JOIN photos ON e.photo_id = photos.photo_id WHERE'.(isset($month)?' DATE_FORMAT(e.entry_date,\'%m-%Y\') = \''.DB::escape($month).'\' AND':'').' NOT EXISTS(SELECT * FROM contest_votes WHERE user_id ='.(int)$user_id.' AND e.entry_id = contest_votes.entry_id) LIMIT 0,'.(int)$limit);
		
		if($result){
			while($row = DB::fetchAssoc($result)){
				$entry = $XMLObject->addChild('entry');
				$entry->addChild('id', $row['entry_id']);
				$entry->addChild('photo_id', $row['photo_id']);
				$entry->addChild('user_id', $row['user_id']);
				$entry->addChild('category_id', $row['category_id']);
				$entry->addChild('date', $row['entry_date']);
				$entry->addChild('remark', htmlspecialchars($row['entry_remark'], ENT_QUOTES, 'UTF-8'));
			}
		}else $this->throwError(1);
		return $XMLObject;
	}
	public function getAllVotedEntriesByUserId($user_id, $vote='YES',$month=NULL){
		$XMLObject = $this->getXMLObject();
		
		if(isset($month))$result = DB::query('SELECT v.entry_id FROM contest_votes v INNER JOIN contest_entries e ON v.entry_id = e.entry_id WHERE v.user_id = '.(int)$user_id.' AND v.value = \''.DB::escape($vote).'\' AND DATE_FORMAT(e.entry_date,\'%m-%Y\') = \''.DB::escape($month).'\' ORDER BY v.entry_id ASC');
		else $result = DB::query('SELECT v.entry_id FROM contest_votes v WHERE v.user_id = '.(int)$user_id.' AND v.value = \''.DB::escape($vote).'\' ORDER BY v.entry_id ASC');

		if($result){
			while($row = DB::fetchAssoc($result)){
				$XMLObject->addChild('id', $row['entry_id']);
			}
		}else $this->throwError(1);
		return $XMLObject;
	}
	public function getNextVotedEntryByUserId($user_id, $last_entry_id=0, $vote='YES', $month=NULL){
		$XMLObject = $this->getXMLObject();

		$result = DB::query('SELECT photos.user_id,  v.entry_id, e.photo_id, e.entry_remark, e.category_id, e.entry_date 
		FROM contest_votes v 
		INNER JOIN contest_entries e ON  v.entry_id = e.entry_id 
		INNER JOIN photos ON e.photo_id = photos.photo_id 
		WHERE v.user_id = '.(int)$user_id.' AND v.entry_id > '.(int)$last_entry_id.' AND v.value = \''.DB::escape($vote).'\''.(isset($month)?'AND DATE_FORMAT(e.entry_date,\'%m-%Y\') = \''.DB::escape($month).'\'':'').' ORDER BY v.entry_id ASC LIMIT 0,1');

		if($result){
			while($row = DB::fetchAssoc($result)){
				$entry = $XMLObject->addChild('entry');
				$entry->addChild('id', $row['entry_id']);
				$entry->addChild('photo_id', $row['photo_id']);
				$entry->addChild('user_id', $row['user_id']);
				$entry->addChild('category_id', $row['category_id']);
				$entry->addChild('date', $row['entry_date']);
				$entry->addChild('remark', htmlspecialchars($row['entry_remark'], ENT_QUOTES, 'UTF-8'));
			}
		}else $this->throwError(1);
		return $XMLObject;
	}
	public function getNumberOfVotesByUserId($user_id, $month){
		$XMLObject = $this->getXMLObject();
		if(isset($month))$result = DB::query('SELECT COUNT(0) FROM contest_votes v INNER JOIN contest_entries e ON v.entry_id = e.entry_id WHERE user_id = '.(int)$user_id.' AND DATE_FORMAT(e.entry_date,\'%m-%Y\') = \''.DB::escape($month).'\'');
		else $result = DB::query('SELECT COUNT(0) FROM contest_votes WHERE user_id = '.(int)$user_id);
		if($result && DB::numRows($result)==1)$XMLObject->addChild('count', DB::result($result, 0));
		else $this->throwError(1);
		return $XMLObject;
	}
	public function getNumberOfEntries($month){
		$XMLObject = $this->getXMLObject();
		if(isset($month)) $result = DB::query('SELECT COUNT(0) FROM contest_entries WHERE DATE_FORMAT(contest_entries.entry_date,\'%m-%Y\') = \''.DB::escape($month).'\'');
		else $result = DB::query('SELECT COUNT(0) FROM contest_entries');
		if($result && DB::numRows($result)==1)$XMLObject->addChild('count', DB::result($result, 0));
		else $this->throwError(1);
		return $XMLObject;
	}
	public function addVote($user_id, $entry_id, $value){
		$XMLObject = $this->getXMLObject();
		if((int)$entry_id>0 && (int)$user_id>0 && mb_strlen($value)>0){
			//insert
			$result = DB::query('INSERT INTO contest_votes(user_id, entry_id, value) VALUES ('.(int)$user_id.','.(int)$entry_id.',\''.DB::escape($value).'\')');
			if(!$result){
				//update
				$result = DB::query('UPDATE contest_votes SET value = \''.DB::escape($value).'\' WHERE user_id = '.(int)$user_id.' AND entry_id = '.(int)$entry_id);
				if(!$result)$this->throwError(1);
			}
		}else $this->throwError(5); 
		return $XMLObject;
	}
	public function getEntriesByCategoryId($category_id, $offset=0, $limit=5){
		$XMLObject = $this->getXMLObject();
		if(isset($category_id)){
			$result = DB::query('SELECT SQL_CALC_FOUND_ROWS users.user_name, users.user_id, countries.country_name, cities.FULL_NAME_ND, contest_entries.photo_id, contest_entries.poster_name, category_id, entry_remark, contest_entries.entry_date 
				FROM contest_entries 
				INNER JOIN photos ON contest_entries.photo_id = photos.photo_id 
				INNER JOIN cities ON photos.city_id = cities.UNI 
				INNER JOIN countries ON cities.cc1 = countries.country_code 
				INNER JOIN users ON photos.user_id = users.user_id
				WHERE category_id = '.(int)$category_id.' 
				ORDER BY entry_id DESC 
				LIMIT '.max(0,min((int)$offset, 2000)).','.min(100,max(0,(int)$limit)));
			if($result){
				while($row = DB::fetchAssoc($result)){
					$entry = $XMLObject->addChild('entry');
					$entry->addChild('photo_id', $row['photo_id']);
					$entry->addChild('user_name', htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
					$entry->addChild('user_id', $row['user_id']);
					$entry->addChild('submitted_by', htmlspecialchars($row['poster_name'], ENT_QUOTES, 'UTF-8'));
					$entry->addChild('country_name', htmlspecialchars($row['country_name'], ENT_QUOTES, 'UTF-8'));
					$entry->addChild('city_name', htmlspecialchars($row['FULL_NAME_ND'], ENT_QUOTES, 'UTF-8'));
					$entry->addChild('category_id', $row['category_id']);
					$entry->addChild('date', $row['entry_date']);
					$entry->addChild('remark', htmlspecialchars($row['entry_remark'], ENT_QUOTES, 'UTF-8'));
				}
				$result_count = DB::query('SELECT FOUND_ROWS()');
				if($result_count) $XMLObject['total_entries'] = DB::result($result_count, 0);
			}else $this->throwError(1);
		}else $this->throwError(5);
		return $XMLObject;
	}
	protected function throwError($code=1, $msg = ''){
		switch($code){
			case 1:$msg='Error executing query.';break;
			case 2:$msg='Fill in all the required fields.';break;
			case 3:$msg='Photo not found.';break;
			case 4:$msg='This photo has already been submitted for this contest.';break;
			case 5:$msg='Missing parameter.';break; 
		}
		parent::throwError(self::ERRBASE+$code, $msg);
	}
}
?>