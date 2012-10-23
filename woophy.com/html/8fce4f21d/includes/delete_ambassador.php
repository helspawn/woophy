<?php

if(isset($_POST['submit_delete'])){
	if(isset($_POST['deletes'])){
		$deletes = $_POST['deletes'];
		$error = 'Ambassador(s) deleted!';
		foreach($deletes as $delete){
			$param = explode(',', $delete);
			if(count($param)==2){
				if(!DB::query('DELETE FROM ambassadors WHERE user_id= '.(int)$param[0].' AND language_code=\''.DB::escape($param[1]).'\''))$error = 'Could not delete ambassador: '.DB::error();
			}
		}
		include_once(CLASS_PATH.'ClassFactory.class.php');
		include_once(CLASS_PATH.'User.class.php');
		$user = new User();
		$user->deleteFromCache('User::getAmbassadors');
	}else $error = 'Select an ambassador!';
}
?>
<fieldset>
<legend>Delete Ambassador</legend>
<div style="padding:10px;">
<form name="form_ambassador" method="post" action="">
<?php

if(isset($error))echo '<p class="Error">'.$error.'</p><hr/>';
echo '<h1>Ambassadors</h1>';
		
$result = DB::query('SELECT user_name, language_name, ambassadors.user_id, ambassadors.language_code
FROM ambassadors
INNER JOIN users ON ambassadors.user_id = users.user_id
INNER JOIN languages ON ambassadors.language_code = languages.language_code
ORDER BY user_name LIMIT 0, 500');
if($result){
	
	if(DB::numRows($result)>0){
		echo '<table><tr><td><b>Delete</b></td><td><b>Member</b></td><td><b>Language</b></td></tr>';
		while($row = DB::fetchAssoc($result)){
			echo '<tr><td><input type="checkbox" name="deletes[]" value="'.$row['user_id'].','.$row['language_code'].'"/></td><td>'.$row['user_name'].'</td><td>'.$row['language_name'].'</td></tr>';
		}
		echo '<tr><td colspan="3"><input type="submit" name="submit_delete" value="Delete selected"/></td></tr></table>';
		echo '</table>';
	}else echo 'No ambassadors founds!';
}else echo 'Error executing query';

?>
</form>
</div>
</fieldset>