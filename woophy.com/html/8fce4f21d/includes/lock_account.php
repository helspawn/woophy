<?php

if(isset($_POST["user_name"]) && strlen($_POST["user_name"])>0 ){
	$bln = isset($_POST["submit_lock"]) ? 0 : 1;
	DB::query("UPDATE users SET active = $bln WHERE user_name = '".DB::escape($_POST["user_name"])."';");
	$error_user =  "Updated records: ". DB::affectedRows();
}
if(isset($_POST["email"]) && strlen($_POST["email"])>0 ){
	$bln = isset($_POST["submit_lock"]) ? 0 : 1;
	DB::query("UPDATE users SET active = $bln WHERE email = '".DB::escape($_POST["email"])."';");
	$error_email = "Updated records: ". DB::affectedRows();
}
?>
<fieldset>
<legend>Lock account by member nickname</legend>
<div style="padding:10px;">
<?php
if(isset($error_user)){
	echo '<p class="Error">'.$error_user.'</p><hr/>';
}
?>
	<form name="form_lock_account" method="post" action="">
	<h1>Member nickname:</h1>
	<input type="text" name="user_name" value=""/>
	<input type="submit" name="submit_lock" value="Lock" />
	<input type="submit" name="submit_unlock" value="Unlock"/>

	</form>
</div>
</fieldset>

<fieldset>
<legend>Lock account by member email</legend>
<div style="padding:10px;">
<?php
if(isset($error_email)){
	echo '<p class="Error">'.$error_email.'</p><hr/>';
}
?>
	<form name="form_lock_account" method="post" action="">
	<h1>Member email:</h1>
	<input type="text" name="email" value=""/>
	<input type="submit" name="submit_lock" value="Lock" />
	<input type="submit" name="submit_unlock" value="Unlock"/>
	</form>
</div>
</fieldset>