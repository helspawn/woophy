<?php

$limit = 150;

if(isset($_POST["submit_lock"])){
	$query = 'UPDATE users SET active = 1 WHERE user_id IN ('.implode(",", $_POST['userIds']).');';
	if(DB::query($query)) $error = 'Account(s) unlocked';
	else $error = 'Error unlocking account(s)!';
}

?>
<form 
	id="activeForm" 
	name="activeForm" 
	method="post" 
	action="">
<fieldset>
<legend>Locked accounts</legend>
<div style="padding:10px;">
<?php
if(isset($error)){
	echo '<p class="Error">'.$error.'</p><hr/>';
}

$query = "SELECT user_id,user_name,email,registration_date FROM users WHERE active = 0 ORDER BY user_id DESC LIMIT 0,$limit";
$result = DB::query($query) or die(DB::error());
$result_total = DB::query('SELECT COUNT(0) FROM USERS WHERE active = 0;');
$total = DB::result($result_total, 0);
$num = DB::numRows($result);
if($num==0){
	echo '<i>No locked accounts.</i>';
}else{
	if($num == $limit){
		echo '<p><i>Showing '.$num.' of '.$total.'</i></p><hr/>';
	}
	echo '<table>';
	echo '<tr><td><b>id</b></td><td><b>username</b></td><td><b>email</b></td><td><b>registration date</b></td><td><b>unlock</b></td></tr>';
	while ($row = DB::fetchAssoc($result)) {
		echo '<tr><td>'.$row['user_id'].'</td><td>'.$row['user_name'].'</td><td>'.$row['email'].'</td><td>'.$row['registration_date'].'</td><td><input type="checkbox" class="noborder" name="userIds[]" value="'.$row['user_id'].'"/></td></tr>';
	}
	echo '<tr><td colspan="2"><br/><input id="submit_lock" type="submit" name="submit_lock" value="Submit" /></td></tr>';
	echo '</table>';
}
?>
</div>
</fieldset>
</form> 
