<?php
require_once CLASS_PATH.'Response.class.php';
class PhotoFolder extends Response{
	
	const ERRBASE = 1200;
	public static $search_categories = array('keyword','city','country','photo_id');
	private $access;
	public function __construct() {
		$this->access = new Access();
		parent::__construct();
	}
	public function moveToFolder($pid=NULL, $fid=NULL){//$pid could be single photo id or array of ids, folder_id could be NULL
 		$xml = $this->getXMLObject();
		if(isset($pid)){
			if($uid=$this->access->getUserId()){
				if(isset($fid)){
					//belongs set to user?
					$fid = (int)$fid;
					$result = DB::query('SELECT folder_id FROM photo_folders WHERE user_id = '.(int)$uid.' AND folder_id = '.$fid.' LIMIT 0,1');
					if(!($result && DB::numRows($result)==1)){
						$this->throwError(4);
						return $xml;
					} 
				}else $fid = 'NULL';

				if(!is_array($pid))$pid = array($pid);
				$result = DB::query('UPDATE photos SET folder_id = '.$fid.' WHERE photo_id IN ('.implode(',', $pid).') AND user_id = '.(int)$uid);
				if(!$result) $this->throwError(3);
			}else $this->throwError(2);
		}else $this->throwError(1);
		return $xml;
	}
	public function resetLastImage($fid){
		$XMLObject = $this->getXMLObject();
		if($uid=$this->access->getUserId()){
			if(isset($fid)){
				if(!DB::query('UPDATE photo_folders SET last_photo_id = (SELECT MAX(photo_id) FROM photos WHERE user_id='.(int)$uid.' AND folder_id='.(int)$fid.') WHERE folder_id = '.(int)$fid))$this->throwError(3);
			}else $this->throwError(1);
		}else $this->throwError(2);
		return $XMLObject;
	}
	public function getPhotosByFolderId($fid=NULL, $offset, $photos_limit, $orderby, $search, $search_cat){
		$XMLObject = $this->getXMLObject();
		if($uid=$this->access->getUserId()){
			$limit = min(max(0,$photos_limit), 20);

			$sql_select['photo_id'] = 'photo_id';
			$sql_from = array('photos');
			$sql_joins = NULL;
			$sql_where[] = 'user_id='.(int)$uid;
			$sql_where[] = 'photo_processed = 1 AND folder_id '.(isset($fid)?'='.(int)$fid:'IS NULL');
			$sql_group = NULL;
			$sql_order = 'photo_id '.(mb_strtolower($orderby)=='asc' ? 'ASC' : 'DESC');
			$sql_limit = array(max(0,(int)$offset), $limit);
			
			if(isset($search)){
				$search_key = array_search($search_cat, self::$search_categories);
				if($search_key !== false){
					switch($search_key){
						case 0://keyword
							$tag_ids = Utils::getTagIds($search);
							if(count($tag_ids)>0){
								//TRICKY: possible slow query with large collection of photos:
								$sql_select['photo_id'] = 'DISTINCT photos.photo_id';
								$sql_joins['photo_tag2photo'] = 'photos.photo_id=photo_tag2photo.photo_id';
								$sql_where[] = 'tag_id IN ('.implode(',',$tag_ids).')';	
							}else return $XMLObject;

							//$sql_where[] = 'MATCH (photos.keywords) AGAINST (\''.DB::escapeMatchPattern($search).'\' IN BOOLEAN MODE)';
							//$sql_order = NULL;//not order with fulltext
							break;
						case 1://city
							$city = DB::escapeLikePattern($search);
							$sql_joins['cities'] = 'photos.city_id=cities.UNI';
							$sql_where[] = 'cities.FULL_NAME_ND LIKE \''.$city.'\'';
							break;
						case 2://country
							$result = DB::query('SELECT country_code FROM countries WHERE country_name =\''.DB::escape($search).'\'');
							if($result && DB::numRows($result)==1){
								$sql_where[] = 'photos.country_code = \''.DB::result($result, 0, 0).'\'';
							}else return $XMLObject;
							break;
						case 3://photo id
							$sql_where[] = 'photo_id='.(int)$search;
							break;
					}
				}
			}
			
			if($query = Utils::buildQuery($sql_select, $sql_from, $sql_joins, $sql_where, $sql_group, $sql_order, $sql_limit)){
				//echo $query;
				$result = DB::query($query);
				if($result){
					$ids = array();
					while($row = DB::fetchAssoc($result)){
						$ids[] = $row['photo_id'];
					}
					$photos = $XMLObject->addChild('photos',implode(',',$ids));
					$sql_select = array('total'=>'COUNT(0)');//avoid SQL_CALC_FOUND_ROWS
					$sql_limit = array(0, 1);
					$sql_order = NULL;
					if($query = Utils::buildQuery($sql_select, $sql_from, $sql_joins, $sql_where, $sql_group, $sql_order, $sql_limit)){
						$result = DB::query($query);
						if($result && DB::numRows($result)>0) $photos->addAttribute('total', DB::result($result, 0));
					}else $this->throwError(5);
				}else $this->throwError(3);
			}else $this->throwError(5);
		}else $this->throwError(2);
		return $XMLObject;
	}
	public function getFolderById($fid=NULL){
		$XMLObject = $this->getXMLObject();
		if(isset($fid)){
			if($uid=$this->access->getUserId()){
				$result = DB::query('SELECT folder_id, folder_name FROM photo_folders WHERE user_id = '.(int)$uid.' AND folder_id = '.(int)$fid.' LIMIT 0,1');
				if($result){
					if(DB::numRows($result)==1){
						$row = DB::fetchAssoc($result);
						$folder = $XMLObject->addChild('folder');
						$folder->addAttribute('id', $row['folder_id']);
						$folder->addAttribute('name', $row['folder_name']);
					}else $this->throwError(4);
				}else $this->throwError(3);
			}else $this->throwError(2);		
		}else $this->throwError(1);
		return $XMLObject;
	}
	public function getFolders(){
		$XMLObject = $this->getXMLObject();
		if($uid=$this->access->getUserId()){
			$query = 'SELECT folder_id, folder_name, last_photo_id FROM photo_folders WHERE user_id = '.(int)$uid.' ORDER BY folder_name LIMIT 0,100';
			$result = DB::query($query); //limit??
			if($result){
				while($row = DB::fetchAssoc($result)){
					$folder = $XMLObject->addChild('folder');
					$folder->addAttribute('id',$row['folder_id']);
					$folder->addAttribute('name',$row['folder_name']);
					if(!is_null($row['last_photo_id']))$folder->addAttribute('last_photo_id', $row['last_photo_id']);
				}
			}else $this->throwError(3);
		}else $this->throwError(2);
		return $XMLObject;
	}
	
	//TODO: combine add, update and delete functions
	
	public function add($name=NULL){
		$XMLObject = $this->getXMLObject();
		if(isset($name)){
			$name = trim($name);
			if(mb_strlen($name)>0){
				if($uid=$this->access->getUserId()){
					$result = DB::query('INSERT INTO photo_folders (user_id, folder_name) VALUES ('.(int)$uid.', \''.Utils::filterText($name).'\')');
					if($result){
						$XMLObject->addChild('folder_id', DB::insertId());
						$XMLObject->addChild('folder_name', $name);
					}else $this->throwError(3);
				}else $this->throwError(2);
			}else $this->throwError(1);
		}else $this->throwError(1);
		return $XMLObject;
	}
	public function edit($fid, $name=NULL){
		$XMLObject = $this->getXMLObject();
		if(isset($fid, $name)){
			$name = trim($name);
			if(mb_strlen($name)>0){
				if($uid=$this->access->getUserId()){
					$result = DB::query('UPDATE photo_folders SET folder_name=\''.Utils::filterText($name).'\' WHERE folder_id = '.(int)$fid.' AND user_id = '.(int)$uid);
					if(!$result) $this->throwError(3);
				}else $this->throwError(2);
			}else $this->throwError(1);
		}else $this->throwError(1);
		return $XMLObject;
	}
	public function remove($fid=NULL){//only removes the set not the photos!
		$XMLObject = $this->getXMLObject();
		$fid = (int)$fid;
		if($fid>0){
			if($uid=$this->access->getUserId()){
				$result = DB::query('DELETE FROM photo_folders WHERE user_id = '.(int)$uid.' AND folder_id = '.$fid);
				if(!$result) $this->throwError(3);
				else DB::query('UPDATE photos SET folder_id=NULL WHERE user_id = '.(int)$uid.' AND folder_id = '.$fid);
			}else $this->throwError(2);
		}else $this->throwError(1);
		return $XMLObject;
	}
	protected function throwError($code=1, $msg = ''){
		switch($code){
			case 1:$msg='Fill in all the required fields.';break;
			case 2:$msg='You have to be signed in.';break;
			case 3:$msg='Error executing query.';break;
			case 4:$msg='No folder found!';break;
			case 5:$msg='Error building query';break;
		}
		parent::throwError(self::ERRBASE+$code, $msg);
	}
}
