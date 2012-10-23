<?php

$user_name = isset($_POST['user_name']) ? $_POST['user_name'] : (isset($_GET['user_name'])?$_GET['user_name']:'');

if(strlen($user_name)>0 && !isset($_POST['user_id'])){
	$result = DB::query('SELECT user_id FROM users WHERE user_name = \''.DB::escape($user_name).'\'');
	if($result && DB::numRows($result)==1){
		$user_id = (int)DB::result($result, 0, 0);
	}else $error = 'No member with nickname \''.$_POST['user_name'].'\' found!';
}
if(isset($_POST['submit_add'],$_POST['categories'],$_POST['user_id'])){
	$error = 'Permissions added!';
	foreach($_POST['categories'] as $cat){
		if(!DB::query('INSERT INTO blog_user2category (user_id, category_id) VALUES ('.(int)$_POST['user_id'].','.(int)$cat.')')){
			$error = 'Could not add permission: '.DB::error();
			break;
		}
	}
}
?>
<fieldset>
<legend>Add member blog permission</legend>
<div style="padding:10px;">
<form name="form_blog2user" method="post" action="">
<?php
if(isset($user_id) && $user_id>0){
	//select categories			
	$result = DB::query('SELECT category_name, category_id FROM blog_categories');
	if($result){
		echo '<h1>Select blog categories</h1>';
		echo '<p>user name: '.$user_name.'</p>';
		if(DB::numRows($result)>0){
			echo '<table>';
			$i = 0;
			while($row = DB::fetchAssoc($result)){
				if($i++>0){//skip first cat
					$id = $row['category_id'];
					echo '<tr><td><input id="cat'.$id.'" type="checkbox" name="categories[]" value="'.$id.'" /></td>';
					echo '<td><label for="cat'.$id.'">'.$row['category_name'].'</label></td></tr>';
				}
			}	
			echo '<tr><td>&nbsp;</td><td><input type="hidden" name="user_name" value="'.$user_name.'"/><input type="hidden" name="user_id" value="'.$user_id.'"/>';
			echo '<input type="submit" name="submit_add" value="Add permissions"/></td></tr></table>';
		}else $error = 'There are no categories!';
	}else $error = 'Error executing query';

	if(isset($error))echo '<p class="Error">'.$error.'</p><hr/>';
}else{
	if(isset($error))echo '<p class="Error">'.$error.'</p><hr/>';
	//select user
?>
	<h1>Member nickname:</h1>
	<input type="text" name="user_name" value="<?php echo $user_name;?>"/>&nbsp;<input type="submit" name="submit_select" value="Continue"/>
<?php
}

?>
</form>
</div>
</fieldset>
