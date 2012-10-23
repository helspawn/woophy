<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}

	include_once CLASS_PATH.'User.class.php';
	$user = new User();
	
	if(isset($_POST['action'])){
		if(mb_strtolower($_POST['action'])=='delete') $user->removeFromFavorites($_POST['user_id']);
	}
	$limit = 10;
	$offset = 0;
	if(isset($_GET['offset']))$offset = (int)$_GET['offset'];

	$xml_users = $user->getFavorites($offset, $limit);

	$users = $xml_users->user;
	echo '<div class="OuterContentContainer">';
	echo '<div class="MainHeader DottedBottom clearfix"><h1>My favorite photographers</h1>';
	if(count($users)>0){	
		$total = $xml_users['total_users'];
		echo ($offset+1).'&nbsp;-&nbsp;'.(min($offset+$limit,$total)).'&nbsp;of&nbsp;'.$total.'&nbsp;total</div>';	

		$pagingnav = Utils::getPagingNav($offset, $total, $limit);
		echo $pagingnav;

		$page = ClassFactory::create('Page');
		$js = 'function onDelFavUsr(uid){document.forms[\'frmfavmembers\'][\'user_id\'].value=uid;return confirm(\'Are you sure you want to remove this photographer from your favorites?\');}';
		$page->addInlineScript($js);
		
		echo '<form action="'.Utils::StripQueryString($_SERVER['REQUEST_URI']).'" method="post" id="EditFavoritePhotogs" name="frmfavmembers">';
		echo '<input type="hidden" name="action" value="delete" />';
		echo '<input type="hidden" name="user_id" value="" />';
		echo '<div class="UsersListContainer clearfix">';
		echo '<div class="UsersList clearfix">';
		foreach ($users as $u){
			echo '<div class="User clearfix DottedTop"><a class="Thumb sprite" href="'.ROOT_PATH.'member/'.urlencode($u['name']).'"><img src="'.AVATARS_URL.$u->id.'.jpg" /></a><div class="Content">';
			$n = (int)$u->photo_count;
			echo '<div><a href="'.ROOT_PATH.'member/'.urlencode($u->name).'">'.$u->name.'</a> ('.$n.' photo'.($n==1?'':'s').')</div>';
			echo '<div>';
			$delimiter = '';
			if(isset($u->city_name)){
				echo $u->city_name.', '.$u->country_name;
				if(isset($u['last_upload_date']))echo '<br />';
			}
			if(isset($u['last_upload_date']))echo 'Last photo added <b>'.Utils::formatDateShort($u->last_upload_date).'</b>';
			echo '</div></div>';
			echo '<input type="submit" class="submit RedButton" value="remove" onclick="return onDelFavUsr('.$u->id.')" />';
			echo '</div>';
		}
		echo '</div></div>';
		echo '</form>';

		echo $pagingnav;

	}else echo '</div><div class="Notice">You don\'t have any favorite photographers yet.</div>';	
	echo '</div>';
?>