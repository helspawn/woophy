<?php

require_once CLASS_PATH . '/s3.class.php';

class Utils{

	const re_url = '(https?):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[A-Z0-9+&@#\/%=~_|]';
	const re_filename_filter = '[\/0-9!@#\$\^&\*\-\(\)_;=+{}\'"\\\?<>,\.\|¿\s]+';

	public static function getPhotoUrl($user_id, $photo_id, $size, $cat='', $seo_suffix=''){/* size:s,m,l, cat:photo or blog*/
		$prefix = $cat == 'blog' ? BLOGS_URL : PHOTOS_URL;
		return self::getPhotoPathUrl($user_id, $photo_id, $size, $prefix, '/', $seo_suffix);
	}

	public static function getPhotoPath($user_id, $photo_id, $size, $cat=''){/* size:s,m,l*/
		$prefix = $cat == 'blog' ? BLOGS_RELATIVE_PATH : PHOTOS_RELATIVE_PATH;
		return self::getPhotoPathUrl($user_id, $photo_id, $size, $prefix, DIRECTORY_SEPARATOR, '');
	}

	public static function getWoophyPhotoRegEx(){//public because of use in forum, bb_codes.php
		return '('.str_replace('/','\/','http://'.$_SERVER['HTTP_HOST']).')?('.str_replace('/','\/',PHOTOS_URL).')((([0-9]+)\/)+)([a-zA-Z]+\/)([0-9]+)([^\.]*).jpg'; 	
	}

	private static function getPhotoPathUrl($user_id, $photo_id, $size, $prefix, $separator, $seo_suffix){
		$url = '';
		if(isset($user_id,$photo_id,$size)){
			$a = str_split($user_id, 3);
			$all_sizes_filepath = implode($separator, $a).$separator;
			$url = $prefix.$all_sizes_filepath.$size.$separator.$photo_id.$seo_suffix.'.jpg';
		}
		return $url;
	}

	public static function getWoophyPhotoUrl($src){
		preg_match('/'.self::getWoophyPhotoRegEx().'/i', $src, $matches);
		if($matches) return $matches[0];
		return false;
	}

	/* case-insensitive version of array_unique */
	public static function array_iunique($array) {
		return array_intersect_key($array,array_unique(array_map('strtolower',$array)));
	}

	public static function getSEOText($metadata=NULL, $alttext=FALSE){
		$delimiter = '%';
		$output = array();
		$max_amount_tags = 7;
		$terms = '';
		$terms_array = array();
		$tags_array = array();
		$desc_array = array();
	
		if(isset($metadata)){
			$city = $metadata['city'];
			$country = $metadata['country'];
			$category = $metadata['category'];
			$tags = @$metadata['tags'];
			$desc = @$metadata['description'] . '';
			$username = @$metadata['username'] .'';
			$tagslist = $delimiter;
	
			$username_array = self::rawToCleanArray($username);
			$city_array = self::rawToCleanArray($city);
			$country_array = self::rawToCleanArray($country);
			
			$category = preg_replace('/'.self::re_filename_filter.'/i', $delimiter, trim($category));
	
			$category_array = self::cleanKeyWords($category, array_merge($username_array, $city_array, $country_array), $delimiter, $max_amount_tags);		
	
			if(isset($tags) && count($tags)>0){
				foreach($tags as $tag){
					$tagslist .= trim(strtolower($tag)) . $delimiter;
				}
				$tags_array = self::cleanKeyWords($tagslist, array_merge($username_array, $city_array, $country_array), $delimiter, $max_amount_tags);
			}
			
			if($desc != ''){
				$desc = $delimiter . trim(strtolower($desc)) . $delimiter;
				$desc_array = self::cleanKeyWords($desc, array_merge($username_array, $city_array, $country_array), $delimiter, $max_amount_tags);		
			}
	
			if($alttext){
				$terms_array = self::array_iunique(array_merge($desc_array,$tags_array));
				$terms_array = array_slice($terms_array, 0, 20);
				
				if(count($terms_array)>0) $terms = ' ('.implode(' ', $terms_array) . ')';
				$output['raw'] = ($metadata['category']!=''?$metadata['category'] . ' in ':' ').mysql_real_escape_string($metadata['city']).', '.$metadata['country'] . $terms . ' - a photo by ' . $username;
				$output['linked'] = ($metadata['category']!=''?$metadata['category'] . ' in ':' ').mysql_real_escape_string($metadata['city']).', '.$metadata['country'] . self::linkifyAlt($terms) . ' - a photo by ' . $username;
				
			}else{
				$core_terms = array_merge($category_array, $city_array, $country_array);
				$terms_array = self::array_iunique(array_merge($tags_array, $desc_array));
				$terms_array = array_slice($terms_array, 0, $max_amount_tags);
				$terms_array = array_filter($terms_array, function($item) use(&$core_terms){return !in_array($item, $core_terms);});
	//			$terms_array = self::array_iunique(array_merge($category_array, $city_array, $country_array, $terms_array));
				
				if(count($terms_array)>0) $terms = '-'.strtolower(implode('-', $terms_array));
				
				$output['raw'] = '-'.strtolower(implode('-', $core_terms)) . $terms;
				$output['linked'] = '-'.strtolower(implode('-', $core_terms)) . self::linkifySEO($terms);
			}
		}
		return $output;	
	}
	
	private static function rawToCleanArray($input){
		$delimiter = '%';
		$delimiter = '%';
		
		$input = preg_replace('/'.self::re_filename_filter.'/i', $delimiter, trim($input));
		$input = preg_replace('/^'.$delimiter.'/i', '', $input);
		$input = preg_replace('/'.$delimiter.'$/i', '', $input);
		return explode($delimiter, $input);
	}
	
	public static function cleanKeyWords($input, $filter_words, $delimiter='%', $max_amount_tags=5){
		$fluff_words = self::getFluffWords();
		$delimiter = '%';
		
		$html_filter = '<(.*?)>';
		$url_filter = 'http\:\/\/[^%]+';
		$entity_filter = '&[^;]+;';
		$clean = $input;
		$clean_array = array();
	
		//remove HTML from the description
		$clean = preg_replace('/'.$html_filter.'/i', $delimiter, $clean);
	
		//remove URLs from the description
		$clean = preg_replace('/'.$url_filter.'/i', $delimiter, $clean);
	
		//remove HTML entities from the description
		$clean = preg_replace('/'.$entity_filter.'/i', $delimiter, $clean);
	
		//remove URL-unfriendly characters
		$clean = str_replace('&#039;', '', $clean);
		$clean = preg_replace('/'.self::re_filename_filter.'/i', $delimiter, $clean);
		//remove commonly-used, meanlingless/irrelevant words from the description
		foreach($fluff_words as $word){
			$clean = preg_replace('/'.$delimiter.trim($word).$delimiter.'/i', $delimiter, $clean);
		}
	
		//remove tags that refer to the username (if username has spaces, grab the first name)
		foreach($filter_words as $word) $clean = preg_replace('/'.$delimiter.trim($word).$delimiter.'/i', $delimiter, $clean);
	
		//remove consecutive delimiters
		$clean = preg_replace('/'.$delimiter.'+/i', $delimiter, $clean);
	
		//remove leading, trailing delimiters
		$clean = trim($clean, $delimiter);
		$clean = preg_replace('/^'.$delimiter.'/i', '', $clean);
		$clean = preg_replace('/'.$delimiter.'$/i', '', $clean);
		$clean = trim($clean);	
		if($clean!=''){
			//remove duplicate words
			$clean_array = explode($delimiter,$clean);
			$clean_array = array_unique($clean_array);
			//remove empty tags
			$clean_array = array_filter($clean_array);
		
			//finally, reduce the filtered set to 5
			$clean_array = array_slice($clean_array, 0, $max_amount_tags);
		}
		return $clean_array;
	}
	
	private static function getFluffWords(){
		$fluff_filename = ADMIN_PATH . 'fluff_words.txt';
		$fluff_words_fh = fopen($fluff_filename,'r');
		$fluff_words = fread($fluff_words_fh, filesize($fluff_filename));
		return explode("\n", $fluff_words);
	}

	private static function linkifySEO($input){
		$output = preg_replace('/([^\-]+)/i', '<a href="#">$1</a>', $input);	
		return $output;
	}
	
	private static function linkifyAlt($input){
		$output = '';
		preg_match_all('/\(([^\)]+)\)/i', $input, $matches);
		if(count($matches[1])>0) $output = ' (' . preg_replace('/([^\s]+)/i', '<a href="#">$1</a>', $matches[1][0]) . ')';
		return $output;
	}

	public static function calculatePagingOffset($offset, $limit, $total){//if offset exceeds total, look for offset of last page (offset:zero based)
		if($limit>0){
			$total = max(0, $total);
			while($offset >= $total){
				$offset = $offset - $limit;
				if($offset <= 0){
					$offset = 0;
					break;
				}
			}
		}
		return $offset;
	}
	public static function getYearWeek($year='', $month='', $day=''){//TRICKY: a database connection is required!
		if(empty($year))$year = date('Y');
		if(empty($month))$month = date('m');
		if(empty($day))$day = date('d');
		$result = DB::query('SELECT YEARWEEK(\''.$year.'-'.$month.'-'.$day.'\', 2)');
		$yearweek = DB::result($result, 0);
		if(isset($yearweek))$yearweek = substr($yearweek, 0, 4).'-'.substr($yearweek, 4);
		return $yearweek;
	}
	public static function dec2dms($l=0.00, $latorlon){
		$abs = abs($l);
		$d = floor($abs);
		$p = ($abs-$d)*60;
		$m = floor($p);
		$s = round((($p-$m)*60));
		if($s==60){
			$s = 0;
			$m++;
		}
		if($m==60){
			$m = 0;
			$d++;
		}
		$suffix = '';
		if(isset($latorlon)){
			if(strtolower($latorlon)=='lat')$suffix = $l<0?' S':' N';
			else $suffix = $l<0?' W':' E';
		}
		return $d.'°'.$m.'\' '.$s.'\'\''.$suffix;//utf8 encode if encoding of this file is ANSI
	}
	public static function getIP(){
		$ip1=getenv('REMOTE_ADDR');$ip2=getenv('HTTP_X_FORWARDED_FOR');
		if($ip2 && $ip2!='' && preg_match("/^[0-9.]+$/", $ip) && ip2long($ip2)!=-1) $finalIP=$ip2;
		else if($ip1)$finalIP=$ip1;
		else $finalIP='';
		$finalIP=mb_substr($finalIP,0,15);
		return $finalIP;
	}
	public static function calculateAge($birthday){/*YYYY-MM-DD*/
		list($y,$m,$d) = explode('-',$birthday);
		$y_diff = date('Y') - $y;
		$m_diff = date('m') - $m;
		$d_diff = date('d') - $d;
		if($m_diff < 0) $y_diff--;
		else if($m_diff==0 && $d_diff < 0) $y_diff--;
		return $y_diff;
	}
	/*
		from:	timestamp 
		to:		timestamp, defaults to current time
	*/
	public static function dateDiff($from=NULL, $to=NULL){
		$str = '';
		if(isset($from)){
			if(is_null($to))$to = time();
			$sec = $to - $from;
			$secs = array(31536000, 2628000, 604800, 86400, 3600, 60, 1);
			$suffix = array('year', 'month', 'week', 'day', 'hour', 'min', 'sec');
			$idx = count($suffix)-1;
			$time = $sec;
			foreach($secs as $k=>$v){
				$t = floor($sec/$v);
				if($t>0){
					$time = $t;
					$idx = $k;
					break;
				}
			}
			$time = floor($time);
			$str = $time.' '.$suffix[$idx];
			if($idx<count($suffix)-2 && $time!=1) $str.='s';
		}
		return $str;
	}
	public static function isValidUserName($un){
		$un = mb_strtolower(trim($un));
		if(mb_strlen($un)==0) return false;
		if(mb_strlen(strip_tags($un)) != mb_strlen($un))return false;//no html
		$a = array_merge(explode(',',RESERVED_USERNAMES), explode(',',SPECIAL_ACTIONS));
		foreach($a as $v)if(mb_strtolower($v)==$un)return false;
		return true;
	}
	public static function getPagingNav($offset=0, $total=0, $limit=0, $param=''){
		$str = '';
		$total = max(0,(int)$total);
		$offset = max(0,(int)$offset);
		$limit = max(0,(int)$limit);
		if($total > $limit){
			if($limit > 0){
				$numpages = ceil($total/$limit);
				$str = '<ul class="Paging clearfix">';
				$num = 0;
				$numoutput = '';
				$backoutput = '';
				$nextoutput = '';
				$maxnumpages = 15;//only show 1 line of numbers, TRICKY pick an odd number
				$activepage = floor($offset/$limit) + 1;//start counting with 1
				$url = '?&offset=';
				
				$startcount = max(1,$activepage - floor($maxnumpages/2));
				if($startcount==1)$endcount=min($numpages, $maxnumpages);
				else $endcount = min($numpages, $activepage + floor($maxnumpages/2));
				if($endcount == $numpages)$startcount = max(1, 1 + $endcount - $maxnumpages);

				if($activepage > 1) $backoutput = '<li class="Previous"><a class="sprite replace" href="'.$url.((($activepage-1)-1)*$limit).$param.'">Previous</a></li>';
				if($activepage < $endcount) $nextoutput = '<li class="Next"><a class="sprite replace" href="'.$url.((($activepage-1)+1)*$limit).$param.'">Next</a></li>';
				
				if($startcount > 1) $numoutput .= '<li> ... </li>';
				
				$count = $startcount;
				while($count <= $endcount){
					$off = ($count-1)*$limit;
					$numoutput .= '<li class="num"><a';
					if($count == $activepage) $numoutput .= ' class="Active"';
					$numoutput .= ' href="'.$url.$off.$param.'">'.$count.'</a></li>';
					$count++;
				}
				if($endcount < $numpages) $numoutput .= '<li> ... </li>';
				
				$str .= $backoutput;
				$str .= $numoutput;
				$str .= $nextoutput;
				$str .= '</ul>';
			}
		}
		return $str;
	}
	
	public static function getTagIds($tag, $limit = 10){//TRICKY: a database connection is required!
		$tag = trim($tag);
		/*$query = 'SELECT tag_id FROM photo_tags WHERE tag_text ';
		if(mb_strpos($tag, '"') === 0){
			//exact match:
			$query .= '= \''.DB::escape(trim($tag, '"')).'\'';
		}else{
			$query .= 'LIKE \''.DB::escapeLikePattern($tag).'\'';
		}
		$query .= ' LIMIT 0,'.$limit;
		$tag_ids = array();
		$result = DB::query($query);
		if($result){
			if(DB::numRows($result)>0){
				while($row = DB::fetchAssoc($result))$tag_ids[] = (int)$row['tag_id'];
			}
		}
		return $tag_ids;*/
		//look for exact match because using more tags causes slow query in AdvancedSearch
		$tag_ids = array();
		$query = 'SELECT tag_id FROM photo_tags WHERE tag_text = \''.DB::escape($tag).'\' LIMIT 0,1';
		if($result = DB::query($query)){
			if(DB::numRows($result)>0) $tag_ids[] = DB::result($result, 0);
		}
		return $tag_ids;
	}
	/*
		example:
		$sql_select['q'] = 'COUNT(photos.photo_id)';
		$sql_from[] = 'cities';
		$sql_joins['table'] = 'condition';
		$sql_where[] = 'cities.UNI=123';
		$sql_group = 'cities.UNI';
		$sql_order = 'photo.photo_id DESC';
		$sql_limit = array(0,2000);
		$sql_index_hint = 'FORCE INDEX(idx1)';
	*/
	public static function buildQuery($sql_select, $sql_from, $sql_joins=NULL, $sql_where, $sql_group=NULL, $sql_order=NULL, $sql_limit=NULL, $sql_index_hint=NULL){
		$query = '';
		if(isset($sql_select,$sql_from,$sql_where)){
			if(count($sql_select)>0){
				if(count($sql_from)>0){
					//if(count($sql_where)>0){
						$select = array();																//select
						foreach ($sql_select as $name=>$col) $select[] = $col.' AS '.$name;
						$query = 'SELECT '.implode(', ', $select);	
						$query .= ' FROM '.implode(', ', $sql_from);									//from
						if(isset($sql_index_hint)) $query .= ' '.trim($sql_index_hint);					//index hint
						if(isset($sql_joins)){															//joins
							foreach ($sql_joins as $table=>$condition) $query .= ' INNER JOIN '.$table.' ON '.$condition;
						}
						if(isset($sql_where) && count($sql_where)>0){
							$query .= ' WHERE '.array_shift($sql_where);									//where
							foreach ($sql_where as $where) $query .= ' AND '.$where;
						}
						if(isset($sql_group) && mb_strlen($sql_group)>0) $query .= ' GROUP BY '.$sql_group;	//group
						if(isset($sql_order) && mb_strlen($sql_order)>0) $query .= ' ORDER BY '.$sql_order;	//order
						if(isset($sql_limit) && count($sql_limit)==2) $query .= ' LIMIT '.implode(',', $sql_limit);//limit
					//}
				}
			}
		}
		if(mb_strlen($query)>0)return $query;
		else return FALSE;
	}
	public static function htmlnumericentities($str){
		return preg_replace('/[^!-%\x27-;=?-~ ]/e', '"&#".ord("$0").chr(59)', $str);
	}
	public static function getExcerpt($text, $suffix='[...]', $excerpt_length = 45) {
		$text = strip_tags($text);
		while(mb_substr_count($text, '  ')>0) $text=str_replace('  ', ' ', $text);//remove double spaces
		$words = explode(' ', $text, $excerpt_length + 1);
		if(count($words) > $excerpt_length){
			array_pop($words);
			array_push($words, $suffix);
			$text = implode(' ', $words);
		}
		return $text;
	}
	public static function getImageSource($text){//return first source of image in text
		if(preg_match("/<img src=\"([^<> \n\r\[\]]+?)\" alt=\"(.+?)\" (title=\"(.+?)\" )?\/>/i", $text, $matches)){
			if(isset($matches[1]))return $matches[1];
		}
		return FALSE;
	}
	public static function br2nl($text){
		return preg_replace('/<br\\s*?\/??>/i', '', $text);
	}
	public static function formatDateShort($d){
		return date('j-n-\'y', strtotime($d));
	}
	public static function formatDate($d){
		//return date('d M Y', strtotime($d));
		return date('F jS, Y', strtotime($d));
	}
	public static function formatDateTime($d){
		return date('F jS, Y g:i a', strtotime($d));
	}
	//begin:: miniBB functions:
	public static function filterText($text, $urls=false, $tags=false, $eofs=false){
		$text=trim(htmlspecialchars($text,ENT_QUOTES,'UTF-8'));
		$text=str_replace('\&#039;', '&#039;', $text);
		$text=str_replace('\&quot;', '&quot;', $text);
		$text=str_replace(chr(92).chr(92).chr(92).chr(92), '&#92;&#92;', $text);
		$text=str_replace(chr(92).chr(92), '&#92;', $text);
		$text=str_replace('&amp;#', '&#', $text);
		$text=str_replace('$', '&#036;', $text);
		if($urls && !$tags) $text=self::makeUrl($text);
		if($tags) $text=self::encodeTags($text);
		if($eofs){
			
			while (mb_substr_count($text, "\r\n\r\n\r\n\r\n")>4) $text=str_replace("\r\n\r\n\r\n\r\n","\r\n",$text);
			while (mb_substr_count($text, "\n\n\n\n")>4) $text=str_replace("\n\n\n\n","\n",$text);

			$text = nl2br($text);
		}
		while(mb_substr($text,-1)==chr(92)) $text=mb_substr($text,0,mb_strlen($text)-1);
		return $text;
	}
	public static function makeUrl($text){
		$text=str_replace("\n", " \n ", $text);

		$words=explode(' ',$text);

		for($i=0;$i<sizeof($words);$i++){

		$word=$words[$i];
		//Trim below is necessary if the tag is placed at the begin of string
		$c=0;

		if(mb_strtolower(mb_substr($words[$i],0,7))=='http://') {$c=1;$word='<a href=\"'.trim($words[$i]).'\" target=\"_new\" rel=\"nofollow\">'.trim($word).'</a>';}
		elseif(mb_strtolower(mb_substr($words[$i],0,8))=='https://') {$c=1;$word='<a href=\"'.trim($words[$i]).'\" target=\"_new\" rel=\"nofollow\">'.trim($word).'</a>';}
		elseif(mb_strtolower(mb_substr($words[$i],0,6))=='ftp://') {$c=1;$word='<a href=\"'.trim($words[$i]).'\" target=\"_new\" rel=\"nofollow\">'.trim($word).'</a>';}
		elseif(mb_strtolower(mb_substr($words[$i],0,4))=='ftp.') {$c=1;$word='<a href=\"ftp://'.trim($words[$i]).'\" target=\"_new\" rel=\"nofollow\">'.trim($word).'</a>';}
		elseif(mb_strtolower(mb_substr($words[$i],0,4))=='www.') {$c=1;$word='<a href="http://'.trim($words[$i]).'\" target=\"_new\" rel=\"nofollow\">'.trim($word).'</a>';}
		elseif(mb_strtolower(mb_substr($words[$i],0,7))=='mailto:') {$c=1;$word='<a href=\"'.trim($words[$i]).'\" rel=\"nofollow\">'.trim($word).'</a>';}
		if ($c==1) $words[$i]=$word;
		}
		$ret=str_replace (" \n ", "\n", implode(' ',$words));
		return $ret;
	}
	
	public static function encodeTags($text) {
		$pattern=array(); 
		$replacement=array();
		
		// woophy image:
		$pattern[]="/\[img=".self::getWoophyPhotoRegEx()."\](.*?)\[\/img\]/i";
		$replacement[]='<a href="'.ABSURL.'photo/\\7" target="_blank"><img src="\\2\\3\\6\\7\\8.jpg" alt="\\9" title="\\9" /></a>';

		//user url image
		$pattern[]="/\[img=(http:\/\/([^<> \n\r\[\]]+?)\.?(gif|jpg|jpeg|png)?)\](.*?)\[\/img\]/i";
		$replacement[]='<img src="\\1" alt="\\4" title="\\4" />';

		$pattern[]="/\[url[=]?\](.+?)\[\/url\]/i";
		$replacement[]="<a href=\"\\1\" target=\"_blank\" rel=\"nofollow\">\\1</a>";

		$pattern[]="/\[url=(((f|ht)tp(s?):\/\/|mailto:)[^<> \n]+?)\](.+?)\[\/url\]/i";
		$replacement[]="<a href=\"\\1\" target=\"_blank\" rel=\"nofollow\">\\5</a>";

		$pattern[]="/\[[bB]\](.+?)\[\/[bB]\]/s";
		$replacement[]='<b>\\1</b>';

		$pattern[]="/\[[iI]\](.+?)\[\/[iI]\]/s";
		$replacement[]='<i>\\1</i>';

		$pattern[]="/\[[uU]\](.+?)\[\/[uU]\]/s";
		$replacement[]='<u>\\1</u>';

		$pattern[]="/\[hr[\/]?\]/s";
		$replacement[]='<hr/>';

		$pattern[]="/\[[hH]\](.+?)\[\/[hH]\]/s";
		$replacement[]='<h2>\\1</h2>';

		$pattern[]="/(http:\/\/)([a-z]+)(\.youtube\.com\/)watch\?v=/i";
		$replacement[]="\\1\\2\\3v/";
		
		$pattern[]="/\[youtube=(http:\/\/[^<> \n]+?)\]/i";
		$replacement[]="<object type=\"application/x-shockwave-flash\" width=\"320\" height=\"268\" data=\"\\1\"><param name=\"movie\" value=\"\\1\" /><param name=\"wmode\" value=\"opaque\" /></object>";

		return preg_replace($pattern, $replacement, $text);
	}
	public static function decodeTags($text) {

		$pattern=array();
		$replacement=array();
		
		//new woophy image:
		$pattern[]="/<a href=\"([^<> \n\r\[\]]+?)\" target=\"_blank\"><img src=\"([^<> \n\r\[\]]+?)\" alt=\"(.*?)\" title=\"(.*?)\" ?\/><\/a>/i";
		$replacement[]="[img=\\2]\\3[/img]";

		//new user url image
		$pattern[]="/<img src=\"([^<> \n\r\[\]]+?)\" alt=\"(.*?)\" title=\"(.*?)\" ?\/>/i";
		$replacement[]="[img=\\1]\\3[/img]";

		$pattern[]="/<!-- nourl -->([^<> \n\r\[\]]+?)<!-- \/nourl -->/i";
		$replacement[]="[nourl]\\1[/nourl]";

		$pattern[]="/<a href=\"(.+?)\"( target=\"(_new|_blank)\")?( rel=\"nofollow\")?>(.+?)<\/a>/i";
		$replacement[]="[url=\\1]\\5[/url]";

		$pattern[]="/<b>(.+?)<\/b>/is";
		$replacement[]="[b]\\1[/b]";

		$pattern[]="/<i>(.+?)<\/i>/is";
		$replacement[]="[i]\\1[/i]";

		$pattern[]="/<[uU]>(.+?)<\/[uU]>/s";
		$replacement[]="[u]\\1[/u]";
		
		$pattern[]="/<hr[\/]>/s";
		$replacement[]="[hr]";

		$pattern[]="/<[hH]2>(.+?)<\/[hH]2>/s";
		$replacement[]="[h]\\1[/h]";

		$pattern[]="/<object type=\"application\/x-shockwave-flash\" width=\"320\" height=\"268\" data=\"(.+?)\"><param name=\"movie\" value=\"(.+?)\" \/><param name=\"wmode\" value=\"opaque\" \/><\/object>/i";
		$replacement[]="[youtube=\\1]";
		
		$text = preg_replace($pattern, $replacement, $text);

		return self::br2nl($text);
	}
	//end:: miniBB functions
	public static function stripSpecialAction($path){
		$a = explode(',', SPECIAL_ACTIONS);
		$p = mb_split('/',rtrim($path,'/'));
		foreach($p as $k=>$v){
			$s = self::stripQueryString($v);
			foreach($a as $e){//case-insensitive in_array
				if(mb_strtolower($s) == mb_strtolower($e)){
					unset($p[$k]);
					break 1;
				}
			}
		}
		return implode("/", $p);
	}
	public static function stripQueryString($path){
		$p = mb_split('[?&#]',$path);
		$out = $p[0];
		if(substr($out, strlen($out)-1) == '/') $out = substr($out,0,strlen($out)-1);
		return $out;
	}

	public static function stripLinks($text, $filter=0){
		if($filter >= 0){
			if($filter==0) $limit = $filter;
			elseif($filter == 0) $limit = -1;
			$out = preg_replace(self::re_url, '', $text, $limit);
		}else{
			$out = $text;
			while($filter < 0){
				$out = preg_replace('/(.*)('.self::re_url.')', '', $out, 1);
			}
		}
		return $out;
	}
	public static function parseLinks($text){
		$out = preg_replace('/('. self::re_url .')/i', '<a target="_blank" href="$1">$1</a>', $text);
		return $out;
	}
	public static function getSigned32($int64) {
		$tmpint = (int)$int64;
		if($tmpint > 0x7fffffff)
		   $tmpint -= 0x100000000;
		 return $tmpint;
	}
	public static function s3_exists($bucket,$name) {
			$s3 = new S3(AWS_S3_PUBLIC_KEY, AWS_S3_PRIVATE_KEY); 
			$s3->useSSL = false;
			return($s3->getObjectInfo($bucket, $name) !== false);
	}
	public static function simpleXmlAppend(SimpleXMLElement $to, SimpleXMLElement $from) {
		if($from->count() > 0):
	    	$toDom = dom_import_simplexml($to);
			foreach($from as $el){
		    	$fromDom = dom_import_simplexml($el);
	    		$toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
			}
		endif;
	}
	public static function externalResize($user_id, $photo_id) {
         exec("php ../scripts/resize.php -u ".$user_id." -p ".$photo_id." 2>&1 > /tmp/resize.log");
   }
	public static function image_exists($url) {
		if (!$fp = @fopen($url, 'r')) return false;
		else
			fclose($fp);
		return true;
	}
	public static function s3_rename($oldname,$newname) {
		$s3 = new S3(AWS_S3_PUBLIC_KEY, AWS_S3_PRIVATE_KEY); 
		$s3->useSSL = false;
		if($s3->copyObject(AWS_BUCKET,$oldname,AWS_BUCKET,$newname))
			$s3->deleteObject(AWS_BUCKET,$oldname);
		else
			return false;
	}
	public static function s3_delete($uri) {
		$s3 = new S3(AWS_S3_PUBLIC_KEY, AWS_S3_PRIVATE_KEY); 
		$s3->useSSL = false;
		return($s3->deleteObject(AWS_BUCKET,$uri));
	}
}
?>
