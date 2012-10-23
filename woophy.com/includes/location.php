<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}

	require_once CLASS_PATH.'Page.class.php';
	require_once CLASS_PATH.'Location.class.php';
	 	
	$param = explode('/', rtrim(REQUEST_PATH, '/'));
	$location = new Location();
	$location->buffer = false;
	$type = $param[0];
	switch($type):
		case 'country':
			$label = 'Country';
		break;
		case 'city':
			$label = 'City';
		break;
	endswitch;
	if(count($param)>1){
		$param[1] = urldecode(Utils::stripQueryString($param[1]));
		$l = mb_strlen($param[1]);
		if($l>0){
			if($type == 'country'){
				$city = NULL;
				$country = $param[1];
			}elseif($type=='city'){
				$city = $param[1];
				if(count($param)>2) $country = urldecode(Utils::stripQueryString($param[2]));
				else $country = '';
			}
			if(mb_strlen($country)==2)$xml_country = $location->getCountryInfoByCode($country);
			else $xml_country = $location->getCountryInfoByName($country);
			if($err = $xml_country->err):
				if(mb_strlen($country)>0):
					$country_errmsg = 'Country <b>&quot;'.$country.'&quot;</b> does not exist.';
				else:
					if($city != NULL)$xml_cities = $location->getAllCitiesByName($city);
					else $country_errmsg = 'No country specified!';
				endif;
			else: $country_code = $xml_country->code;
			endif;
			if($type=='city')$location_label = $city.', '.$xml_country->name;
			elseif($type=='country') $location_label = $xml_country->name;
			
		}
	}
	$pages = array('home','photos', 'members');
	$currentpage = 0;//default home
	$i=false;
	if(count($param) >= 3){
		if($type=='country'):
			$i = array_search(mb_strtolower(Utils::stripQuerystring($param[2])), $pages);
		else:
			if(count($param) >= 4) $i = array_search(mb_strtolower(Utils::stripQuerystring($param[3])), $pages);
		endif;
		if($i !== false)$currentpage = $i;
	}
	
	$page = new Page();
	$page->setTitle('Country'.(isset($country_code)?' - '.$xml_country->name:''));
	$page->addScript('gallery.js');
	$page->addScript('photopage.js');
	$page->addScript('swfobject.js');
	$js = 'jQuery(document).ready(function(){var so = new SWFObject(\''.ROOT_PATH.'swf/map_sidebar.swf\',\'FlashMap\',\'278\',\'208\',\'9\',\'#FFFFFF\',\'high\');so.addParam(\'wmode\',\'opaque\');so.addVariable(\'base_url\',\''.ROOT_PATH.'\');'.(isset($country_code)?'so.addVariable(\'country_code\',\''.$country_code.'\');':'').'so.write(\'FlashMap\');';
	$js .= '});';

	$main_column = '<div id="MainContent" class="clearfix"><div id="MainColumn">';

	if(isset($country_code)){
		$main_column .= '<div class="MenuBar clearfix"><div id="SubNav"><ul class="clearfix">';
		if($type=='country')$url = ROOT_PATH.Utils::stripQueryString($param[0]).'/'.Utils::stripQueryString($param[1]).'/';
		elseif($type=='city')$url = ROOT_PATH.Utils::stripQueryString($param[0]).'/'.Utils::stripQueryString($param[1]).'/'.Utils::stripQueryString($param[2]).'/';
		foreach($pages as $k=>$v){
			$class = $currentpage==$k ? 'active' : 'inactive';
			$main_column .= '<li><a href="'.$url.$pages[$k].'" class="'.$class.'">'.$v.'</a></li>';
		}
		$main_column .= '</ul></div></div>';

		$right_column = '<div id="RightColumn"><div class="Section" id="FlashMap"></div>';
		$right_column .= '<div class="Section CountryStats"><div class="Header clearfix"><h2>'.$xml_country->name.'</h2><span class="flag flag-'.strtolower($country_code).' replace">' . $country_code . '</span></div>';
		$right_column .= '<div class="Content">';
		$right_column .= '<div class="clearfix"><span class="label">Capital:</span><span>'.$xml_country->capital.'</span></div>';
		$right_column .= '<div class="clearfix"><span class="label">Population:</span><span>'.number_format((int)$xml_country->population,0).'</span></div>';
		$right_column .= '<div class="clearfix"><span class="label">Area:</span><span>'.number_format((int)$xml_country->area,0).' sq km</span></div>';
		$right_column .= '<div class="clearfix"><span class="label">Climate:</span><span>'.$xml_country->climate.'</span></div>';
		$right_column .= '<div class="clearfix"><span class="label">Languages:</span><span>'.$xml_country->languages.'</span></div>';
		$right_column .= '<div class="clearfix"><span class="label">Currency:</span><span>'.$xml_country->currency.'</span></div>';
		$right_column .= '<div class="clearfix"><span class="label">Government:</span><span>'.$xml_country->government.'</span></div>';
		$right_column .= '<div class="clearfix"><span class="label">Religions:</span><span>'.$xml_country->religions.'</span></div>';
		$right_column .= '<div class="clearfix"><span class="source">Source: CIA World Factbook 2007</span></div>';
		$right_column .= '</div></div></div>';
		
	}else{
		$main_column .= '<div class="MenuBar clearfix"><div id="SubNav"></div></div>';

		$xml_popular = $location->getCountriesByPhotoCount();
		$right_column = '<div id="RightColumn"><div style="border:1px solid #053904;" id="FlashMap">map</div>';
		$right_column .= '<div class="Section"><div class="Header clearfix"><h2>Popular countries</h2></div>';
		$right_column .= '<div class="clearfix">';
		foreach($xml_popular->country as $c){
			$right_column .= '<div class="Excerpt clearfix"><a href="'.ROOT_PATH.'country/'.$c->code.'" class="Thumb sprite"><img src="'.Utils::getPhotoUrl($c->user_id, $c->photo_id, 'thumb').'" /></a>';
			$right_column .= '<div class="ExcerptContent"><div><a href="'.ROOT_PATH.'country/'.$c->code.'" class="strong">'.$c->name.'</a></div>';
			$right_column .= '<div class="DottedTop">Total '.number_format((int)$c->photo_count,0).' photos | '.number_format((int)$c->user_count,0).' members</div>';
			$right_column .= '</div></div>';
		}
		$right_column .= '</div></div></div>';
	
	}
	if(isset($country_errmsg))$main_column .= '<div class="Error" style="margin-bottom:20px;">'.$country_errmsg.'</div>';
	
	if(isset($country_code)){
		//country page
		include INCLUDE_PATH.'thumbsgrid.php';
		
		include_once INCLUDE_PATH . 'userlist.php';

		include_once CLASS_PATH.'Photo.class.php';
		include_once CLASS_PATH.'User.class.php';
		$location_url = ROOT_PATH.$param[0].'/'.$param[1].'/';
		if($type=='city')$location_url .= $param[2].'/';
		$photo = new Photo();
		$user = new User();
		switch($currentpage){
			case 1://photos page
				
				$orderby = isset($_GET['order_by']) && mb_strtolower($_GET['order_by'])=='recent' ? 'recent' : 'rating';
				
				$main_column .= '<div class="OuterContentContainer"><div class="MainHeader DottedBottom"><div class="PhotoOrderDropdown DropdownContainer"><select class="sprite" onchange="document.location.href=\''.Utils::stripQueryString($_SERVER['REQUEST_URI']).'?&order_by=\'+this.value" name="order_by"><option '.($orderby=='rating'?'selected="true" ':'').'value="rating">Order by Rating</option><option '.($orderby=='recent'?'selected="true" ':'').'value="recent">Order by Recent</option></select></div><h1>Photos from '.$location_label.'</h1>';
				$limit = 30;
				$max_limit = 1000;
				$offset = 0;
				if(isset($_GET['offset']))$offset = (int)$_GET['offset'];
				$offset = round($offset/$limit)*$limit;
				$offset = min($max_limit-$limit,$offset);
				$xml_photo = $photo->getPhotosByLocation($city, $country_code, $offset, $limit, $orderby);
				$total = min($max_limit, (int)$xml_photo['total_photos']);
				$main_column .= ($offset+1).'&nbsp;-&nbsp;'.(min($offset+$limit,$total)).'&nbsp;of&nbsp;'.$total.($total==$max_limit?'+':'').'&nbsp;total</div>';
				if($total>0){
					$pagingnav = Utils::getPagingNav($offset, $total, $limit, '&order_by='.$orderby);
					$main_column .= $pagingnav;
					$main_column .= '<div id="GalleryContainer" class="clearfix"><div id="CountryGallery" class="Gallery clearfix"><div class="Page clearfix">' . outputThumbsGrid($xml_photo->photo, array('show_country'=>FALSE, 'show_location'=>($type=='city'?FALSE:TRUE))). '</div></div></div>';
					$main_column .= $pagingnav;
				}else $main_column .= '<div class="Notice">This '.$type.' has no photos yet!</div>';

				$main_column .= '</div>';
				
				break;
			case 2://members page
				$main_column .= '<div class="OuterContentContainer"><div class="MainHeader DottedBottom"><h1>Members from '.$location_label.'</h1>';
				
				$limit = 30;
				$max_limit = 1000;
				$offset = 0;
				if(isset($_GET['offset']))$offset = (int)$_GET['offset'];
				$offset = round($offset/$limit)*$limit;
				$offset = min($max_limit-$limit,$offset);
				$xml_user = $user->getUsersByLocation($city, $country_code, $offset, $limit);
				$total = min($max_limit, (int)$xml_user['total_users']);
				$main_column .= ' '.($offset+1).'&nbsp;-&nbsp;'.(min($offset+$limit,$total)).'&nbsp;of&nbsp;'.$total.($total == $max_limit?'+':'').'&nbsp;total</div>';
				if($total>0){
					$pagingnav = Utils::getPagingNav($offset, $total, $limit);
					$main_column .= $pagingnav;
					$main_column .= '<div class="UsersListContainer"><div class="UsersList clearfix">' . getListUsers($xml_user->user, $type) . '</div></div>';
				}else $main_column .= '<div class="Notice">There are no members in '.$location_label.' yet!</div>';
				$main_column .= '</div>';
				break;
			default://country/city home page
		
				$limit = 30;
				$max_limit = 1000;
				$xml_photo = $photo->getPhotosByLocation($city, $country_code, 0, $limit);
				$main_column .= '<div class="OuterContentContainer"><div class="MainHeader DottedBottom"><h1>Photos from '.$location_label.'</h1>';
				if($err = $xml_photo->err)$main_column .= '</div><div class="Error">'.$err['msg'].'</div>';
				else{
					$total = (int)$xml_photo['total_photos'];
					
					if($total>$limit) $main_column .= '<a href="'.$location_url.$pages[1].'">View&nbsp;all&nbsp;photos&nbsp;('.($total>$max_limit?$max_limit.'+':$total).')</a>';
					$main_column .= '</div>';
					if($total>0){
						$main_column .= '<div id="GalleryContainer" class="clearfix"><div id="CountryGallery" class="Gallery clearfix"><div class="Page clearfix">' . outputThumbsGrid($xml_photo->photo, array('show_country'=>FALSE,'show_location'=>($type=='city'?FALSE:TRUE))) . '</div></div></div>';
					}else $main_column .= '<div class="Notice">This '.$type.' has no photos yet!</div>';
				}
				$main_column .= '</div>';//OuterContentContainer
				$main_column .= '<div class="OuterContentContainer"><div class="MainHeader DottedBottom"><h1>Members from '.$location_label.'</h1>';
				$xml_user = $user->getUsersByLocation($city, $country_code, 0, 10);
				if($err = $xml_user->err)$main_column .= '</div><div class="Notice">There are no members in '.($city==NULL?'':$city.', ').$xml_country->name.' yet!</div>';
				else{
					$total = (int)$xml_user['total_users'];
					if($total>$limit) $main_column .= '<a href="'.$location_url.$pages[2].'">View all members ('.($total>$max_limit?$max_limit.'+':$total).')</a>';
					$main_column .= '</div>';
					if($total>0)$main_column .= '<div class="UsersListContainer"><div class="UsersList clearfix">' . getListUsers($xml_user->user, $type) . '</div></div>';
					else $main_column .= '<div class="Notice">There are no members in '.($city==NULL?'':$city.', ').$xml_country->name.' yet!</div>';
				}
				$main_column .= '</div>';//Section
		}
	}else{
		if(@$xml_cities){
			//List all cities by name
			$main_column .= '<h1>Cities called ' . $city . '</h2>';
			$main_column .= '<ul>';
			foreach($xml_cities->city as $c):
				$main_column .= '<li><a href="'.ROOT_PATH.'city/'.$c->city_name.'/'.$c->country_name.'">' .$c->city_name.', '.$c->country_name.'</a> ('.$c->photo_count.' photos)</li>';
			endforeach;
			$main_column .= '</ul>';
		}else{
			//countries home page
			$main_column .= '<h1>Countries</h1>';
			$xml_country = $location->getAllCountries();
			$main_column .= '<table class="countries">';
			$count = 0;
			$countries = $xml_country->country;
			$l = count($countries);
			$num_cols = 3;
			$num_rows = ceil($l/$num_cols);
			for($i=0; $i<$num_rows; $i++){
				$main_column .= '<tr>';
				for($j=0; $j<$num_cols; $j++){
					$main_column .= '<td>';
					$idx = (int)($i + $j*$num_rows);
					if(isset($countries[$idx]))$main_column .= '<a href="'.ROOT_PATH.'country/'.$countries[$idx]['cc'].'">'.$countries[$idx].'</a>';
					else $main_column .= '&nbsp;'; 
					$main_column .= '</td>';
				}
				$main_column .= '</tr>';
			}
			$main_column .= '</table>';
		}
	}	
	
	$main_column .= '</div>';
	$page->addInlineScript($js);
	echo $page->outputHeader(2);
	echo $main_column;
	echo $right_column;
	echo '</div>';
	echo $page->outputFooter();
?>