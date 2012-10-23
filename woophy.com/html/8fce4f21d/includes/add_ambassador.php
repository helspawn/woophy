<?php

$user_name = isset($_POST['user_name']) ? $_POST['user_name'] : (isset($_GET['user_name'])?$_GET['user_name']:'');

if(strlen($user_name)>0 && !isset($_POST['user_id'])){
	$result = DB::query('SELECT user_id FROM users WHERE user_name = \''.DB::escape($user_name).'\'');
	if($result && DB::numRows($result)==1){
		$user_id = (int)DB::result($result, 0, 0);
	}else $error = 'No member with nickname \''.$_POST['user_name'].'\' found!';
}
if(isset($_POST['submit_add'],$_POST['language_code'],$_POST['user_id'],$_POST['user_name'])){
	$user_id = (int)$_POST['user_id'];
	if(strlen($_POST['language_code'])>0){
		$error = 'Ambassador \''.$_POST['user_name'].'\' added to \''.$_POST['language_code'].'\'!';
		if(DB::query('INSERT INTO ambassadors (user_id, language_code) VALUES ('.$user_id.',\''.DB::escape($_POST['language_code']).'\')')){
			include_once(CLASS_PATH.'ClassFactory.class.php');
			include_once(CLASS_PATH.'User.class.php');
			$user = new User();
			$user->deleteFromCache('User::getAmbassadors');
		}else{
			$error = 'Could not add ambassador: '.DB::error();
		}
	}else $error = 'Select a language!';
}
?>
<fieldset>
<legend>Add Ambassador</legend>
<div style="padding:10px;">
<form name="form_ambassador" method="post" action="">
<?php
if(isset($user_id) && $user_id>0){
	if(isset($error))echo '<p class="Error">'.$error.'</p><hr/>';
			
	$result = DB::query('SELECT language_name AS n, language_code AS c FROM languages ORDER BY language_name;');
	if($result){
		echo '<h1>Select language</h1>';
		echo '<p>Note: You can add multiple languages to the same ambassador.</p>';
		if(DB::numRows($result)>0){
			echo '<table><tr><td>Member:</td><td>'.$user_name.'</td></tr>';
			echo '<tr><td>Language:</td><td><select name="language_code" style="width:231px;"><option value="">-</option>';
			while($row = DB::fetchAssoc($result)){
				echo '<option value="'.$row['c'].'">'.$row['n'].'</option>';
			}
			echo '</select></td></tr>';
			echo '<tr><td>&nbsp;</td><td><input type="hidden" name="user_name" value="'.$user_name.'"/><input type="hidden" name="user_id" value="'.$user_id.'"/>';
			echo '<input type="submit" name="submit_add" value="Add ambassador to language"/></td></tr></table>';
			echo '</table>';
		}else $error = 'No languages founds!';
	}else $error = 'Error executing query';

	
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
