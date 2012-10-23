<?php

$user_name = '';

$user_name = isset($_POST['user_name']) ? $_POST['user_name'] : (isset($_GET['user_name'])?$_GET['user_name']:'');

if(strlen($user_name)>0 && !isset($_POST['user_id'])){
	$result = DB::query('SELECT user_id FROM users WHERE user_name = \''.DB::escape($user_name).'\'');
	if($result && DB::numRows($result)==1){
		$user_id = (int)DB::result($result, 0, 0);
	}else $error = 'No member with nickname \''.$_POST['user_name'].'\' found!';
}

if(isset($_POST['submit_delete'])){
	$user_id = (int)$_POST['user_id'];
	if(isset($_POST['deletes'])){
		foreach($_POST['deletes'] as $award_id){
			DB::query('DELETE FROM awards WHERE user_id='.$user_id.' AND award_id='.(int)$award_id);
		}

		//UPDATE user's cache:
		//TODO: duplicate code! (add_award.php)
		$result = DB::query('SELECT award_date, category_id FROM awards WHERE user_id = '.$user_id);
		$awards = array();
		while($row = DB::fetchAssoc($result)){
			$awards[] = array($row['category_id']=>$row['award_date']);
		}
		DB::query('UPDATE users SET awards = \''.serialize($awards).'\' WHERE user_id = '.$user_id);
		$error = "Award(s) from '$user_name' deleted";
	}else $error = "Select an award to delete!";
}
?>

<form 
	id="awardForm" 
	name="awardForm" 
	method="post" 
	action="" >
<fieldset>
<legend>Delete Award</legend>
<div style="padding:10px;">
<?php
	if(isset($user_id) && $user_id>0){
		echo '<h1>Awards from '.$user_name.'</h1>';

		$result = DB::query('SELECT * FROM awards WHERE user_id='.(int)$user_id);

		if(DB::numRows($result)>0){
			
			$result_cats = DB::query('SELECT * FROM award_categories ORDER by category_id ASC;');
			$award_cats = array();
			while($row = DB::fetchAssoc($result_cats)){
				$award_cats[$row['category_id']]=$row['category_name'];
			}
			
			echo '<table>';
			while($row = DB::fetchAssoc($result)){
				echo '<tr><td><input type="checkbox" name="deletes[]" value="'.$row['award_id'].'"></td><td>'.$award_cats[$row['category_id']].'</td><td>'.$row['award_date'].'</td></tr>';
			}
			echo '</table>';
			echo '<input type="hidden" name="user_name" value="'.htmlspecialchars($user_name).'"/>';
			echo '<input type="hidden" name="user_id" value="'.$user_id.'"/>';
			echo '<br/><input type="submit" value="Delete selected" name="submit_delete"/>';
		}else{
			$error = $user_name.' has no awards!';
		}

		if(isset($error) && strlen($error)>0){
			echo '<p class="Error">'.$error.'</p>';
		}
		
	}else{
		//select user:
		if(isset($error))echo '<p class="Error">'.$error.'</p><hr/>';
?>
	<h1>Member nickname:</h1>
	<input type="text" name="user_name" value="<?php echo $user_name;?>"/>&nbsp;<input type="submit" name="submit_select" value="Continue"/>

<?php
	}
?>
</div>
</fieldset>
</form>
