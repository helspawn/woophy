<?php

if(isset($_POST['submit_delete'])){
	if(isset($_POST['deletes'])){
		$deletes = $_POST['deletes'];
		$error = 'Editor(s) deleted!';
		foreach($deletes as $delete){
			if(DB::query('UPDATE users SET editor=0 WHERE user_id= '.(int)$delete)){
				DB::query('DELETE FROM editors_picks WHERE user_id= '.(int)$delete);
			}else{
				$error = 'Could not delete editor: '.DB::error();
			}
		}
	}else $error = 'Select an editor!';
}
?>
<fieldset>
<legend>Delete Editor</legend>
<div style="padding:10px;">
<form onsubmit="return confirm('Are you sure you want to delete this editor(s) and all of their picks?')" name="form_editor" method="post" action="">
<?php

if(isset($error))echo '<p class="Error">'.$error.'</p><hr/>';
echo '<h1>Editors</h1>';

$result = DB::query('SELECT user_id,user_name FROM users WHERE editor=1 ORDER BY user_name LIMIT 0, 500');
if($result){
	
	if(DB::numRows($result)>0){
		echo '<table><tr><td><b>Delete</b></td><td><b>Member</b></td></tr>';
		while($row = DB::fetchAssoc($result)){
			echo '<tr><td><input type="checkbox" name="deletes[]" value="'.$row['user_id'].'"/></td><td>'.$row['user_name'].'</td></tr>';
		}
		echo '<tr><td colspan="2"><input type="submit" name="submit_delete" value="Delete selected"/></td></tr></table>';
		echo '</table>';
	}else echo 'No editors founds!';
}else echo 'Error executing query';

?>
</form>
</div>
</fieldset>