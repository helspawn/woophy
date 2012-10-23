<?php
require_once CLASS_PATH.'Response.class.php';
class AdvancedSearch extends Response{

	const ERRBASE = 500;
	
	/*
		$param: array in next format:
			array(
			'keyword'=>'',
			'user_name'=>'',
			'city_name'=>'',
			'country_code'=>'',
			'category_id'=>'',
			'date_after'=>'',
			'date_before'=>'',
			'order_by'=>'',
			'offset'=>'',
			'user_type=>''
			);
	 */
	
	public function dosearch($param){
		
		$xmlobject = $this->getXMLObject();
		
		$sql_where = array();
		$sql_joins = array();
		$sql_select = array();
		$order_by = 'photo_id';//default
		$sql_order = 'photo_id DESC';
		if(isset($param['offset']))$offset = min(980,max(0,(int)$param['offset']));//max 1000 rows
		else $offset = 0;
		$limit = 30;
		$sql_limit = array($offset, $limit);
		$sql_select['photo_id'] = 'photos.photo_id';
		$sql_from = array('photos');
		$sql_group = NULL;
		$photo_ids = NULL;

		$params = array('category_id','last24h','country_code','city_name','user_name','keyword','photo_id','date','week','month');
		foreach($params as $p)if(isset($param[$p]) && mb_strlen(trim($param[$p]))>0)$$p=$param[$p];//convert param to var
		
		//KEYWORD::
		if(isset($keyword)){
			//$keyword = DB::escapeMatchPattern($keyword);
			//if(mb_strlen($keyword)>0){
			//	$sql_where[] = 'MATCH (photos.keywords) AGAINST (\''.$keyword.'\' IN BOOLEAN MODE)';
			//	unset($param['order_by']);//do not order with fulltext!
			//}
			$photo_ids = array();
			$xmlobject['total'] = 0;
			$tag_ids = Utils::getTagIds($keyword);
			if(count($tag_ids)>0){
				$order_by = 'photo_id';
				if(isset($param['order_by']))if(mb_strtolower($param['order_by'])=='rating')$order_by = 'average_rate';
				$so = 'DESC';
				if(isset($param['sort_order']))if(mb_strtolower($param['sort_order'])=='asc')$so = 'ASC';	
				$result = DB::query('SELECT photo_id FROM photo_tag2photo WHERE tag_id IN ('.implode(',',$tag_ids).') ORDER BY '.$order_by.' '.$so.' LIMIT '.$offset.','.$limit);//Do not use DISTINCT because of temporary table in conjunction with ORDER BY

				if($result){
					while($row = DB::fetchAssoc($result))$photo_ids[] = $row['photo_id'];
					if(isset($param['total']) && mb_strlen($param['total'])>0)$xmlobject['total']=(int)$param['total'];
					else{
						$result_total = DB::query('SELECT COUNT(DISTINCT photo_id) FROM photo_tag2photo WHERE tag_id IN ('.implode(',',$tag_ids).')');
						if($result_total)$xmlobject['total'] = DB::result($result_total, 0);
					}
				}
			}		
		}else if(isset($category_id)){
			//CATEGORY::
			$category_id = (int)$category_id;
			if($category_id>0){
				$result = DB::query('SELECT photo_id FROM photo2category WHERE category_id = '.$category_id. ' ORDER BY photo_id DESC LIMIT '.$offset.','.$limit);
				$param['offset'] = 0;
				if($result){
					$xmlobject['total'] = '1001';//each category has more than 1000 photos, no need for SQL_CALC_FOUND_ROWS
					$photo_ids = array();
					while($row = DB::fetchAssoc($result))$photo_ids[] = $row['photo_id'];
					if(count($photo_ids)==0)return $xmlobject;
					$sql_order = 'photos.photo_id DESC';
				}
			}else{
				$xmlobject['total'] = 0;
				return $xmlobject;
			}
		}else if(isset($last24h)){
			//Last 24H::
			$xmlobject['total'] = 0;
			if(isset($param['total']) && mb_strlen($param['total'])>0)$xmlobject['total']=(int)$param['total'];
			else{
				$query = Utils::buildQuery(array('total'=>'COUNT(0)'), $sql_from, NULL, array('DATE_SUB( NOW() , INTERVAL 24 HOUR ) < photos.photo_date'), $sql_group, NULL, NULL);
				//echo $query;
				
				$result_total = DB::query($query);
				if($result_total)$xmlobject['total'] = DB::result($result_total, 0);
			}
			$tot = $xmlobject['total'];
			$off = max(0,min($sql_limit[0],$tot));
			$lmt = min($tot, $sql_limit[1]);
			$sql_limit = array($off, $lmt);
			$sql_order = 'photos.photo_id DESC';
		}else if(isset($date)){
			$d = date('Ymd', strtotime($date));
			//$sql_where[] = 'photos.photo_date >= \''.$d.'\'';
			//$sql_where[] = 'photos.photo_date < DATE_ADD(\''.$d.'\',INTERVAL 1 DAY)';
			//$sql_order = 'photo_date DESC';
			$sql_order = NULL;
			$sql_where[] = 'photos.photo_date_lookup = \''.$d.'\'';
			if(isset($param['order_by']))if(mb_strtolower($param['order_by'])=='rating')$sql_order = 'photos.average_rate DESC';
		}else if(isset($week)){
			$week = str_replace('-','',$week);
			$sql_order = NULL;
			$sql_where[] = 'photos.photo_week = \''.DB::escape($week).'\'';
			if(isset($param['order_by']))if(mb_strtolower($param['order_by'])=='rating')$sql_order = 'photos.average_rate DESC';
		}else if(isset($month)){
			$a = explode('-',$month);
			if(count($a)>1){
				$month = date('Ym', mktime(0, 0, 0, (int)$a[1], 1, (int)$a[0]));
				$sql_order = NULL;
				$sql_where[] = 'photos.photo_month = \''.DB::escape($month).'\'';
				if(isset($param['order_by'])){
					if(mb_strtolower($param['order_by'])=='rating')$sql_order = 'photos.average_rate DESC';
					else if(mb_strtolower($param['order_by'])=='favorite'){
						$sql_order = 'photos.favorite_count DESC';
					}
				}
			}
		}else if(isset($photo_id)){
			$photo_ids = array();
			$ids = preg_split('/[,;\s]/', $photo_id , 30);
			foreach($ids as $id){
				$id = trim($id);
				if((int)$id>0)$photo_ids[] = (int)$id;
			}
			if(count($photo_ids)==0){
				$xmlobject['total'] = 0;
				return $xmlobject;
			}
		}else if(isset($param['order_by']) && ($param['order_by']=='favorite')){
			//do not allow searching on others params
			$so = 'DESC';
			if(isset($param['sort_order']))if(mb_strtolower($param['sort_order'])=='asc')$so = 'ASC';
			$sql_order = 'photos.favorite_count '.$so;
			$xmlobject['total'] = '1001';
		}else{
			//COUNTRY::(put country before city!)
			if(isset($country_code)){
				if(mb_strlen($country_code)==2){
					$country_code = mb_strtoupper($country_code);
					$sql_where[] = 'photos.country_code=\''.DB::escape($country_code).'\'';
				}else unset($country_code);
			}
			//CITY::
			if(isset($city_name)){
				$city_name = DB::escape($city_name);
				if(mb_strlen($city_name)>0){
					if(isset($country_code)){
						$sql = 'AND CC1=\''.$country_code.'\' ';//if country is present, use it!
						$sql_where = array();//no need to look for country in end query, since we select on country here
					}else $sql = '';
					$result = DB::query('SELECT UNI FROM cities WHERE photo_count>0 AND FULL_NAME_ND = \''.$city_name.'\' '.$sql.'ORDER BY photo_count DESC LIMIT 0,5;');//max 5 cities
					
					if($result && DB::numRows($result)>0){
						while($row = DB::fetchAssoc($result))$city_ids[]=$row['UNI'];
						$sql_where[] = 'city_id IN ('.implode(',',$city_ids).')';//TRICKY: if more than 1 city is found, mysql uses filesort
	
					}else{
						//TODO: look for name variants!!
						
						$xmlobject['total'] = 0;
						return $xmlobject;
					}
				}
			}
			//USER_NAME::
			if(isset($user_name)){
				$user_type = isset($param['user_type'])? (int)$param['user_type'] : 1;//default 1
				if($user_type == 1){//exact match
					$result = DB::query('SELECT user_id FROM users WHERE user_name = \''.DB::escape($user_name).'\' LIMIT 0,1');
					if($result && DB::numRows($result)==1)$sql_where[] = 'photos.user_id='.DB::result($result, 0);
					else{
						$xmlobject['total'] = 0;
						return $xmlobject;
					}
				}else{//starts with
					$user_name = DB::escapeLikePattern($user_name);
					if(mb_strlen($user_name)>0){
						$result = DB::query('SELECT user_id FROM users WHERE user_name LIKE \''.$user_name.'\' LIMIT 0,10');//limit to max 10 users...
						if($result && DB::numRows($result)>=1){
							while($row = DB::fetchAssoc($result))$user_ids[]=$row['user_id'];
							$sql_where[] = 'photos.user_id IN ('.implode(',',$user_ids).')';
						}else{
							$xmlobject['total'] = 0;
							return $xmlobject;
						}
					}
				}
			}
/*
			//DATE AFTER::
			if(isset($param['date_after'])){
				$date_after = DB::escape($param['date_after']);
				if(mb_strlen($date_after)>0)$sql_where[] = 'photo_date>\''.$date_after.'\'';
			}
			//DATE BEFORE::
			if(isset($param['date_before'])){
				$date_before = DB::escape($param['date_before']);
				if(mb_strlen($date_before)>0)$sql_where[] = 'photo_date<\''.$date_before.'\'';
			}
*/
			//ORDER_BY::
			
			if(isset($param['order_by'])){
				switch(mb_strtolower($param['order_by'])){
					case 'popular':
						$order_by='photos.views';
						break;
					case 'rating':
						$order_by='photos.average_rate';
						break;
					case 'recent':
					default:
						$order_by='photos.photo_id';
				}
				$so = 'DESC';
				if(isset($param['sort_order']))if(mb_strtolower($param['sort_order'])=='asc')$so = 'ASC';
				$sql_order = $order_by.' '.$so;
			}
		}
		
		if(!isset($photo_ids)){/*no category or city*/
			if($query = Utils::buildQuery($sql_select, $sql_from, NULL, $sql_where, $sql_group, $sql_order, $sql_limit)){
				//first retreive ids
				$result = DB::query($query);	
//echo $query;
				if($result){
					//look for total number of rows:
					$num_rows = DB::numRows($result);
					if($num_rows>0){			
						if(!isset($xmlobject['total'])){
							if(isset($param['total']) && mb_strlen($param['total'])>0)$xmlobject['total']=(int)$param['total'];
							else{
								if($offset+$num_rows>=$limit){
									$query = Utils::buildQuery(array('total'=>'COUNT(0)'), $sql_from, NULL, $sql_where, $sql_group, NULL, NULL);
									//echo $query;
									$result_total = DB::query($query);
									if($result_total)$xmlobject['total'] = DB::result($result_total, 0);
								}else $xmlobject['total'] = $num_rows;
							}
						}
						$photo_ids = array();
						while ($row = DB::fetchAssoc($result)) array_push($photo_ids, $row['photo_id']);
					}else $xmlobject['total'] = 0;
				}else $this->throwError(3);
			}else $this->throwError(2);
		}
		if(isset($photo_ids) && count($photo_ids)>0){
			//now get the joins (this way is much faster then 1 query combined)

			$sql_where = array('photos.photo_id IN ('.implode(',',$photo_ids).')');
			
			$sql_select['user_id'] = 'photos.user_id';
			$sql_select['comment_count'] = 'photos.comment_count';
			$sql_select['favorite_count'] = 'photos.favorite_count';
			$sql_select['seo_suffix'] = 'photos.seo_suffix';
			$sql_select['alt_text'] = 'photos.alt_text';
			$sql_select['user_name'] = 'users.user_name';
			$sql_select['city_name'] = 'cities.FULL_NAME_ND';
			$sql_select['country_name'] = 'countries.country_name';
			
			$sql_joins['cities'] = 'photos.city_id=cities.UNI';
			$sql_joins['countries'] = 'cities.CC1=countries.country_code';
			$sql_joins['users'] = 'photos.user_id=users.user_id';
			
			if($query = Utils::buildQuery($sql_select, $sql_from, $sql_joins, $sql_where, NULL, $sql_order, NULL)){
//echo $query;
				$result = DB::query($query);	
				if($result){
					while($row = DB::fetchAssoc($result)){					
						$photo = $xmlobject->addChild('photo');
						$photo->addChild('id',$row['photo_id']);
						$photo->addChild('city_name', htmlspecialchars($row['city_name'], ENT_QUOTES, 'UTF-8'));
						$photo->addChild('country_name', $row['country_name']);
						$photo->addChild('user_id',$row['user_id']);
						$photo->addChild('user_name',htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'));
						$photo->addChild('comment_count',(int)$row['comment_count']);
						$photo->addChild('favorite_count',(int)$row['favorite_count']);
						$photo->addChild('comment_count',(int)$row['comment_count']);
						$photo->addChild('alt_text', htmlspecialchars($row['alt_text'], ENT_QUOTES, 'UTF-8'));
						$photo->addChild('photo_url',Utils::getPhotoUrl((int)$row['user_id'], $row['photo_id'], 'medium', '', $row['seo_suffix']));
					}
					if(isset($photo_id))$xmlobject['total'] = DB::numRows($result);//KLUDGE: we do not know number of found rows uptil here
				}else $this->throwError(3);
			}else $this->throwError(2);
		}
		return $xmlobject;
	}
	protected function throwError($code=1, $msg = ''){
		switch($code){
			case 1:$msg='Fill in all of the required fields';break;
			case 2:$msg='Error building query';break;
			case 3:$msg='Error executing query';break;
		}
		parent::throwError(self::ERRBASE+$code, $msg);
	}
}
?>