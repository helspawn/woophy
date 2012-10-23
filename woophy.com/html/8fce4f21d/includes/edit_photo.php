<?php

if(isset($_POST['submit_edit'],$_POST['pid'])){
	$query = 'UPDATE photos SET city_id='.(int)$_POST['city_id'].',
		keywords = \''.DB::escape($_POST['keywords']).'\'
		WHERE photo_id = '.(int)$_POST['pid'];
}
if(isset($_POST['submit_update_comment'],$_POST['cid'])){
	$query = 'UPDATE photo_comments SET comment_text=\''.DB::escape($_POST['comment']).'\' WHERE comment_id = '.(int)$_POST['cid'];
}
if(isset($_POST['submit_delete_comment'],$_POST['pid'],$_POST['cid'])){
	$query = 'DELETE FROM photo_comments WHERE comment_id = '.(int)$_POST['cid'];
	DB::query('UPDATE photos SET comment_count = (SELECT count(0) FROM photo_comment WHERE photo_id ='.(int)$_POST['pid'].');');
}
if(isset($query)){
	$result = DB::query($query);
	$error = $result ? 'photo has been updated!' : 'ERROR: '.DB::error();
}
?>
<fieldset>
<legend>Edit photo</legend>
<div style="padding:10px;">
<?php
if(!isset($_POST['pid']) || !is_numeric($_POST['pid'])){
	//select photo:
	?>
		
		<form name="form_select" method="post" action="">
		<h1>Photo id:</h1>
		<input type="text" name="pid" value=""/>&nbsp;&nbsp;<input name="Submit_select" type="submit" id="Submit_select" value="Continue"></form>
<?php
}else{
	$pid = (int)$_POST['pid'];

if(isset($error)){
	echo '<p class="Error">'.$error.'</p><hr/>';
}
?>

<form name="form_edit" method="post" action="">
<table>
<?php
	$query = "SELECT * FROM photos WHERE photo_id = $pid;";
	$result = DB::query($query);
			
	if(DB::numRows($result)==1){
		$row = DB::fetchAssoc($result);
		echo '<tr><td rowspan="12"><img hspace="10" src="'.Utils::getPhotoUrl($row['user_id'],$row['photo_id'],'thumb').'" border="0"></td><td>photo id</td><td>'.$row['photo_id'].'</td></tr>';
		echo '<tr><td>user id</td><td>'.$row['user_id'].'</td></tr>';
		echo '<tr><td>city id</td><td><input size="12" type="text" name="city_id" value="'.$row['city_id'].'"></td></tr>';
		echo '<tr><td>description</td><td><textarea cols="30" rows="3" name="keywords">'.$row['keywords'].'</textarea></td></tr>';
		echo '<tr><td colspan="2"><br/><input type="hidden" name="pid" value="'.$pid.'"/><input type="submit" name="submit_edit" value="Update photo"/></td><td></td></tr>';
	
	}else echo '<tr><td class="Error">ERROR: no photo found!</td></tr>';
?>
</td></tr>
</table></form>
<hr/>
<b>comments</b>
<?php

$query = "SELECT * FROM photo_comments WHERE photo_id = $pid;";
$result = DB::query($query);
if(DB::numRows($result)==0){
	print "<p><i>no comments</i></p>";
}else{
	while($row = DB::fetchAssoc($result)){
		echo '<form name="edit_comment" method="post" action=""><table><tr><td><textarea name="comment" cols="30" rows="3">'.$row['comment_text'].'</textarea><input type="hidden" name="cid" value="'.$row['comment_id'].'"><input type="hidden" name="pid" value="'.$pid.'"></td><td><input type="submit" name="submit_update_comment" value="Update"></td><td><input type="submit" name="submit_delete_comment" value="Delete"></td></tr></table></form>';
	}
}
?>
</div>
</fieldset>

<?php
}
?>