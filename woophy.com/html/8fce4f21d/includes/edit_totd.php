<?php

if(isset($_POST['tip_id'])){
	$tip_id = (int)$_POST['tip_id'];
}

if(isset($_POST['submit_update'])){
	$text = DB::escape($_POST['text']);
	if(DB::query('UPDATE tipoftheday SET text = \''.$text.'\' WHERE ID = '.$tip_id.';')){
		$error = 'Tip has been updated!';
		unset($tip_id);
	}else{
		$error = 'Error: '.DB::error();
	}
}
if(isset($_POST['submit_delete'])){		
	if(DB::query("DELETE FROM tipoftheday WHERE ID = $tip_id;")){
		$error = "Tip has been deleted!";
		unset($tip_id);
	}else{
		$error = "Error: ".DB::error();
	}
}
if(isset($_POST['submit_add'])){		
	$text = DB::escape($_POST['text']);
	if(DB::query('INSERT INTO tipoftheday (text) VALUES (\''.$text.'\');')){
		$error = 'Tip has been added!';
	}else{
		$error = 'Could not add tip!';
	}
}
?>
<fieldset>
<legend>Tip of the day</legend>
<div style="padding:10px;">
<?php
if(isset($error)){
	echo '<p class="Error">'.$error.'</p><hr/>';
}
if(!isset($tip_id)){
?>
		<form name="form_select" method="post" action="">
		<h1>Edit tip:</h1>
		<select name="tip_id" id="tip_id">
<?php				
		$query = 'SELECT text,ID FROM tipoftheday ORDER BY ID ASC;';
		$result = DB::query($query);
		if(!$result){
			echo '<option>Query failed</option>';
		}else{
			if(DB::numRows($result)>0){
				while ($row = DB::fetchAssoc($result)) {
					$id = $row['ID'];
					echo '<option value="'.$id.'">'.$row['text'].'</option>';
				}
			}else{
				echo '<option>No tips yet</option>';
			}
		}
?>
	 </select><br/><br/>
	 <input name="Submit_select" type="submit" id="Submit_select" value="Select tip" />
	 </form>
<?php
}else{

	$text = '';

	$result = DB::query('SELECT text FROM tipoftheday WHERE ID = '.$tip_id);
	if($result){
		if(DB::numRows($result)>0){
			$text = DB::result($result,0);
		}else{
			echo '<p class="Error">No tip with ID '.$tip_id.' exists!</p><hr/>';
		}

?>
<h1>Edit tip:</h1>
<form name="form_edit_tip" method="post" action="">
<input size="65" type="text" value="<?php echo $text?>" name="text" /><br/><br/>
<input type="submit" name="submit_update" value="Update"/>
<input type="submit" name="submit_delete" value="Delete" onclick="return confirm('Are you sure you want to delete this tip?');" />
<input type="hidden" name="tip_id" value="<?php echo $tip_id?>"/>
</form>
<?php
	}
}
?>
	<hr/>
	<form name="form_add" method="post" action="">
	<h1 style="margin-top:0px;">Add tip:</h1>
	<input maxlength="100" size="65" name="text" type="text" value=""><br/><br/>
	<input name="submit_add" type="submit" id="submit_add" value="Add tip" />
	</form>
</div>
</fieldset>