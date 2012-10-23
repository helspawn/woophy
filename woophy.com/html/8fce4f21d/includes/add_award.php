<?php
$date = '';
$user_name = '';
if(isset($_POST["submit_name"])){
	$user_name = $_POST["user_name"];
	if(strlen($user_name)>0){
		$date = $_POST["date"];
		if(strlen($date)>0){
			$cat = $_POST["award"];
			if(strlen($cat)>0){
				$query = 'SELECT user_id FROM users WHERE user_name = \''.DB::escape($user_name).'\';';
				$result = DB::query($query) or die(DB::error());
				if($result && DB::numRows($result) == 1){
					$id = (int)DB::result($result, 0);
					$query = 'SELECT * FROM awards WHERE user_id = '.$id.' AND category_id = \''.DB::escape($cat).'\' AND award_date=\''.DB::escape($date).'\'';
					$result = DB::query($query) or die(DB::error());
					if(DB::numRows($result) == 0 ){
						$query = 'INSERT INTO awards SET user_id='.$id.', category_id=\''.DB::escape($cat).'\', award_date=\''.DB::escape($date).'\';';
						$result = DB::query($query);
						if($result){
							//UPDATE user's cache:
							$result2 = DB::query('SELECT award_date, category_id FROM awards WHERE user_id = '.$id);
							$awards = array();
							while($row = DB::fetchAssoc($result2)){
								$awards[] = array($row['category_id']=>$row['award_date']);
							}
							DB::query('UPDATE users SET awards = \''.serialize($awards).'\' WHERE user_id = '.$id);

							if((int)$cat==1){
								//update memberofthemonth cache
								include_once CLASS_PATH.'ClassFactory.class.php';
								include_once CLASS_PATH.'Status.class.php';
								$status = new Status();
								$status->updateMemberOftheMonth();
							}

							$error = "Award for '$user_name' added";
						}else $error = "Could not add award: ".DB::error();
					}else $error = "This award for '$user_name' on '$date' already exists!";					
				}else $error = "No member with name '$user_name' exists!";
			}else $error = "Enter the award category";
		}else $error = "Enter the date";
	}else $error = "Enter name of member";
}
?>
<form 
	id="motmForm" 
	name="motmForm" 
	method="post" 
	action="" >
<fieldset>
<legend>Add Award</legend>
<div style="padding:10px;">
<?php
	if(isset($error) && strlen($error)>0){
		echo '<p class="Error">'.$error.'</p><hr/>';
	}
?>
<table>
<tr><td>Date</td><td><input type="text" class="datefield" name="date" readonly="true" value="<?php echo $date?>" /></td></tr>
<tr><td>Award:</td><td><select name="award" id="award"><option value="">-</option><?php

$result = DB::query('SELECT * FROM award_categories ORDER by category_id ASC;');
while($row = DB::fetchAssoc($result)){
	echo '<option value="'.$row['category_id'].'">'.$row['category_name'].'</option>';
}

?></select></td></tr>
<tr><td>Name:</td><td><input type="text" value="<?php echo $user_name?>" name="user_name"/></td></tr>
<tr><td>&nbsp;</td><td><input type="submit" value="Submit" name="submit_name"/></td></tr>
</table>
</div>
</fieldset>
</form>

<fieldset>
<legend>Reset Member of the Month</legend>
<div style="padding:10px;">
<p>If the Member of the Month award is added before the actual month has started you have to reload the cache.<br/>(The cache doesn't auto-reload to save resources.)</p>
<?php
if(isset($_POST['submit_reset'])){

	include_once CLASS_PATH.'ClassFactory.class.php';
	include_once CLASS_PATH.'Status.class.php';
	
	$status = new Status();
	$xml = $status->updateMemberOfTheMonth();
	echo '<p class="Error">';
	if($err = $xml->err)echo 'Error: '.$err;
	else echo 'Member of the Month has been reset!';
	echo '</p>';

}else{//display form:
?>
<form name="form_reset" method="post" action="">
<input type="submit" name="submit_reset" value="Reset" />
</form>
<?php
}
?>
</div>
</fieldset>


<script type="text/javascript">//<![CDATA[
	var delimiter = '-';
	var f1 = document.forms[0]['date'];
	var sel1 = new DateField(f1);
	sel1.dateFormatter = function(d) {return d.getFullYear() + delimiter + (d.getMonth()+1) + delimiter + d.getDate();}
//]]></script>
