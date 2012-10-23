<?php
if(isset($_POST['user_name']) && strlen($_POST['user_name'])>0 ){
	
	$result = DB::query('SELECT user_id FROM users WHERE user_name = \''.DB::escape($_POST['user_name']).'\'');
	if($result && DB::numRows($result)==1){
		$user_id = (int)DB::result($result, 0, 0);
	}else $error = 'No member with nickname \''.$_POST['user_name'].'\' found!';
}
if(isset($_POST['submit_delete'],$_POST['categories'],$_POST['user_id'])){
	if(DB::query('DELETE FROM blog_user2category WHERE user_id = '.(int)$_POST['user_id'].' AND category_id IN('.implode(',',$_POST['categories']).')'))$error = 'Permissions deleted!';
	else $error = 'Could not delete permissions: '.DB::error();
	
}
?>
<fieldset>
<legend>Delete member blog permission</legend>
<div style="padding:10px;">
<form name="form_blog2user" method="post" action="">
<?php
if(isset($user_id) && $user_id>0){
	//select categories			
	$result = DB::query('SELECT blog_categories.category_name, blog_user2category.category_id FROM blog_user2category INNER JOIN blog_categories ON blog_user2category.category_id = blog_categories.category_id WHERE user_id = '.$user_id);
	if($result){
		echo '<h1>Select blog categories</h1>';
		if(DB::numRows($result)>0){
			echo '<table>';
			while($row = DB::fetchAssoc($result)){
				$id = $row['category_id'];
				echo '<tr><td><input id="cat'.$id.'" type="checkbox" name="categories[]" value="'.$id.'" /></td>';
				echo '<td><label for="cat'.$id.'">'.$row['category_name'].'</label></td></tr>';
			}	
			echo '<tr><td>&nbsp;</td><td><input type="hidden" name="user_id" value="'.$user_id.'"/>';
			echo '<input type="submit" name="submit_delete" value="Delete"/></td></tr></table>';
		}else $error = 'This member has no permissions!';
	}else $error = 'Error executing query';

	if(isset($error))echo '<p class="Error">'.$error.'</p><hr/>';
}else{
	if(isset($error))echo '<p class="Error">'.$error.'</p><hr/>';
	//select user
?>
	<h1 style="margin-top:0px;">Member nickname:</h1>
	<input type="text" name="user_name" value=""/>&nbsp;<input type="submit" name="submit_select" value="Continue"/>
<?php
}
?>
</form>
</div>
</fieldset>