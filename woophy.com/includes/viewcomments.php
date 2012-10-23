<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}

	include_once CLASS_PATH.'User.class.php';
	$user = new User();

	$max_comments = 1000;//only show last 1000 comments (some members have 10,000+ comments)
	$limit = 10;
	$offset = 0;
	if(isset($_GET['offset']))$offset = (int)$_GET['offset'];
	$offset = min($max_comments - $limit, $offset);
	
	$xml_comments = $user->getComments($offset, $limit);

	echo '<div class="Section"><div class="MainHeader DottedBottom"><h1>My latest comments</h1>';
	if($err = $xml_comments->err)echo '</div><p class="Error">'.$err['msg'].'</p>';
	else{
		$comments = $xml_comments->comment;
		if(count($comments)>0){
			$total = $xml_comments['total_comments'];
			echo ($offset+1).' - '.(min($offset+$limit,$total)).' of '.$total.' total</div>';

			$pagingnav = Utils::getPagingNav($offset, min($max_comments,$total), $limit);
			echo $pagingnav;
			$i = 0;
			foreach($comments as $comment){
				echo '<div class="Comment Excerpt clearfix';
				if($i>0) echo ' DottedTop';
				echo '"><a class="floatleft Thumb sprite" href="'.ROOT_PATH.'photo/'.$comment->photo_id.'#'.$comment->id.'"><img src="'.Utils::getPhotoUrl($comment->user_id, $comment->photo_id, 'thumb').'" /></a>';
				echo '<div class="ExcerptContent">';
				echo '<div class="Meta">';
				echo 'posted '.Utils::dateDiff(strtotime($comment->date)).' ago | ';
				$count = $comment->comment_count;
				echo 'total <span class="strong">'.$count.'</span> comment';
				if((int)$count!=1)echo 's';
				echo '</div>';
				echo '<div>';
				echo $comment->text;
				echo '</div>';
				echo '</div>';
				echo '</div>';
				$i++;
			}
			echo $pagingnav;

		}else echo '<p>You haven\'t posted any comments yet.</p>';
	}
	echo '</div>';
?>