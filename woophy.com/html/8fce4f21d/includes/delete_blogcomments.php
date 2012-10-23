<?php

//TODO: combine this file with delete_comments.php

if(isset($_POST["submit_select"])){
	$user_name = DB::escape($_POST["user_name"]);
	$query = 'SELECT user_id FROM users WHERE user_name = "'.$user_name.'"';
	$result = DB::query($query);
	if($result && DB::numRows($result) == 1){
		$_POST["uid"] = DB::result($result, 0);
	}else{
		$error = 'Member with nickname "'.$user_name.'" does not exist.';
	}
}
if(isset($_POST['submit_delete_comment']) || isset($_POST['submit_delete_all'])){
	$uid = (int)$_POST['uid'];
	if(isset($_POST['submit_delete_all'])){
		$result_pids = DB::query('SELECT post_id FROM blog_comments WHERE user_id = '.$uid.';');
		$qry = 'DELETE FROM blog_comments WHERE user_id = '.$uid.';';
	}else{
		if(isset($_POST['ids']) && is_array($_POST['ids']) && count($_POST['ids'])>0){
			$result_pids = DB::query('SELECT post_id FROM blog_comments WHERE comment_id IN ('.implode(',', $_POST['ids']).');');
			if(count($_POST['ids']) == $_POST['num']){
				//delete all:
				$qry = 'DELETE FROM blog_comments WHERE user_id = '.$uid.';';
			}else{
				$qry = 'DELETE FROM blog_comments WHERE comment_id IN ('.implode(',', $_POST['ids']).');';
			}
		}
	}
	if(isset($qry)){
		if(DB::query($qry)){
			$error = 'Comments deleted!';
		}else{
			$error = 'Error deleting comments!';
		}
	}
	if($result_pids){
		while($row = DB::fetchAssoc($result_pids)){
			$pid = (int)$row['post_id'];
			DB::query('UPDATE blog_posts SET comment_count = (SELECT COUNT(*) FROM blog_comments WHERE post_id = '. $pid .') WHERE post_id= \''. $pid .'\';');
		}
	}
}

?>
<fieldset>
<legend>Delete blog comments</legend>
<div style="padding:10px;">
<?php
if(isset($error))echo '<p class="Error">'.$error.'</p><hr/>';
if(!isset($_POST["uid"]) || !is_numeric($_POST["uid"])){
	//select member:
?>
		<form name="form_select" method="post" action="">
		<h1 style="margin-top:0px;">Member nickname:</h1>
		<input type="text" name="user_name" value=""/>&nbsp;&nbsp;<input name="submit_select" type="submit" id="submit_select" value="Continue">
		<p><hr/></p>
		<h1 style="margin-top:0px;">Member id:</h1>
		<input type="text" name="uid" value=""/>&nbsp;&nbsp;<input name="submit_uid" type="submit" id="submit_uid" value="Continue"></form>
<?php
	
}else{
	$limit = 50;
	$uid = (int)$_POST["uid"];
	$query = "SELECT SQL_CALC_FOUND_ROWS * FROM blog_comments WHERE user_id = $uid ORDER BY comment_id DESC LIMIT 0,$limit;";
	$result = DB::query($query);
	$result_total = DB::query('SELECT FOUND_ROWS();');
	$total = DB::result($result_total, 0);
	if($result){
		$num = DB::numRows($result);
		if($num == 0){
			echo "<p><i>no comments</i></p>";
		}else{
			echo '<form onsubmit="return confirm(\'Are you sure you want to delete these comments?\');" name="form_delete" method="post" action="">';
			echo '<p>Showing '.$num.'/'.$total;
			echo '&nbsp;&nbsp;<input type="submit" name="submit_delete_all" value="Delete all comments by this member"><hr/></p>';
			echo '<table><tr><td><b>delete</b></td><td><b>post id</b></td><td><b>comment</b></td><td><b>date</b></td></tr>';
			while($row = DB::fetchAssoc($result)){
				
				echo '<tr>';
				echo '<td><input class="noborder" type="checkbox" name="ids[]" value="'.$row['comment_id'].'"></td>';
				echo '<td>'.$row['post_id'].'</td>';
				echo '<td width="200">'.htmlspecialchars($row['comment_text']).'</td>';
				echo '<td>'.$row['comment_date'].'</td>';
				echo '</tr>';

			}
			echo '</table>';
			echo '<p><hr/></p>';
			echo '<input type="hidden" value="'.$total.'" name="num"/>';
			echo '<input type="hidden" value="'.$uid.'" name="uid"/>';
			echo '<input type="button" onclick="checkUncheckAll(\'form_delete\')" value="Check/Uncheck all">';
			echo '&nbsp;&nbsp;<input type="submit" name="submit_delete_comment" value="Delete checked">';
			echo '</form>';
		}
	}
}
?>
</div>
</fieldset>