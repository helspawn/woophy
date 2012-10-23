<?php

$user_name = '';
if(isset($_POST['submit_add'],$_POST['user_name'])){
	$user_name = $_POST['user_name'];
	$result = DB::query('SELECT user_id FROM users WHERE user_name = \''.DB::escape($user_name).'\'');
	if($result && DB::numRows($result)==1){
		$user_id = (int)DB::result($result, 0, 0);
		if(DB::query('UPDATE users SET editor=1 WHERE user_id='.$user_id)){
			$error = 'Editor \''.$_POST['user_name'].'\' added!';
		}else{
			$error = 'Could not add editor: '.DB::error();
		}
	}else $error = 'No member with nickname \''.$_POST['user_name'].'\' found!';
}
?>
<fieldset>
<legend>Add Editor</legend>
<div style="padding:10px;">
<form name="form_editor" method="post" action="">
<?php
	if(isset($error))echo '<p class="Error">'.$error.'</p><hr/>';
	//select user
?>
	<h1>Member nickname:</h1>
	<input type="text" name="user_name" value="<?php echo $user_name;?>"/>&nbsp;<input type="submit" name="submit_add" value="Add"/>

</form>
</div>
</fieldset>