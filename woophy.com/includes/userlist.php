<?php
function getListUsers($users, $type='recent'){
	$str = '';
	foreach($users as $u){
		$name = isset($u->name)?$u->name:$u['name'];//childnode or attribute
		$id = isset($u->id)?$u->id:$u['id'];
		$num = (int)(isset($u->photo_count)?$u->photo_count:$u['photo_count']);
		$city = isset($u->city_name)?$u->city_name:$u['city_name'];
		$country = isset($u->country_name)?$u->country_name:$u['country_name'];
		$registration_date = isset($u->registration_date)?$u->registration_date:$u['registration_date'];

		$url = ROOT_PATH.'member/'.urlencode($name);
		$str .= '<div class="User clearfix DottedTop">';
		$str .= '<a class="Thumb sprite" href="'.$url.'"><img class="Thumb" src="'.AVATARS_URL.$id.'.jpg" /></a>';
		$str .= '<div class="Content"><div><a href="'.$url.'">'.$name.'</a></div>';
		$str .= '<div>';
		$str .=  '<span class="highlight">'.$num.'</span> photo'.($num==1?'':'s');
		$str .= ', registered: '.Utils::dateDiff(strtotime($registration_date)).' ago<br />';
		if($city!='' || $country !=''):
			$str .= '<span class="Location">';
			if($city!='')$str .= '<a href="'.ROOT_PATH.'city/'.urlencode($city).'/'.urlencode($country).'">' . htmlspecialchars($city).'</a>';
			if($city!='' && $country !='' && $type!='country') $str .= ', ';
			if($type!='country') $str .= '<a href="'.ROOT_PATH.'country/'.urlencode($country).'">'.htmlspecialchars($country).'</a>';
			$str .= '</span>';
		endif;
		
		$str .= '</div>';
		$str .= '</div></div> <!-- end Content, User -->';
	}
	return $str;
}

function getListFavUsers($users){
	$str = '<div class="UsersListContainer">';
	$str .= '<div class="UsersList clearfix">';
	foreach ($users as $u){
		$str .= '<div class="User clearfix DottedTop"><a class="Thumb sprite" href="'.ROOT_PATH.'member/'.urlencode($u->name).'"><img src="'.AVATARS_URL.$u->id.'.jpg" /></a>';
		$n = (int)$u->photo_count;
		$str .= '<div class="Content"><div><a class="Header" href="'.ROOT_PATH.'member/'.urlencode($u->name).'">'.htmlspecialchars($u->name).'</a> ('.$n.' photo'.($n==1?'':'s').')</div>';
		$str .= '<div>';		

		if(isset($u->city_name))$str .= '<a href="'.ROOT_PATH.'city/'.urlencode($u->city_name).'/'.urlencode($u->country_name).'">' . htmlspecialchars($u->city_name) .'</a>, ';
		if(isset($u->country_name)) {
			$str .= '<a href="'.ROOT_PATH.'country/'.urlencode($u->country_name).'">'.htmlspecialchars($u->country_name).'</a>';
			if(isset($u->last_upload_date))$str .= '<br/>';
		}
		if(isset($u->last_upload_date))$str .= 'Last photo added <b>'.Utils::formatDateShort($u->last_upload_date).'</b>';
		$str .= '</div></div></div>';
	}
	$str .= '</div></div>';
	return $str;
}