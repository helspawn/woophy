<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}

	include_once CLASS_PATH.'User.class.php';
	$user = new User();
	
	include_once INCLUDE_PATH.'userlist.php';

	$limit = 10;
	$offset = 0;
	if(isset($_GET['offset']))$offset = (int)$_GET['offset'];

	$xml_users = $user->getLatestFans(NULL, $offset, $limit);

	$users = $xml_users->user;
	echo '<div class="OuterContentContainer"><div class="MainHeader DottedBottom clearfix"><h1>My fans</h1>';
	if(count($users)>0){	
		$total = $xml_users['total_users'];
		echo ($offset+1).'&nbsp;-&nbsp;'.(min($offset+$limit,$total)).'&nbsp;of&nbsp;'.$total.'&nbsp;total</div>';	

		$pagingnav = Utils::getPagingNav($offset, $total, $limit);
		echo $pagingnav;

		echo '<div class="UsersListContainer clearfix">';
		echo '<div class="UsersList clearfix">';
		echo getListUsers($users, 'recent');
		echo '</div></div>';

		echo $pagingnav;

	}else echo '</div><div class="Notice">You don\'t have any fans yet.</div>';
	echo '</div>';
	
?>