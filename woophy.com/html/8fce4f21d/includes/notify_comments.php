<?php
if(isset($_POST["user_id"]) && strlen(trim($_POST["user_id"]))>0 ){
	$bln = isset($_POST["submit_notify"]) ? 1 : 0;
	DB::query("UPDATE users SET notify_comments = $bln WHERE user_id = '".(int)$_POST["user_id"]."';");
	$error_id =  "Updated records: ". DB::affectedRows();
}
if(isset($_POST["email"]) && strlen(trim($_POST["email"]))>0 ){
	$bln = isset($_POST["submit_notify"]) ? 1 : 0;
	DB::query("UPDATE users SET notify_comments = $bln WHERE email = '".DB::escape($_POST["email"])."';");
	$error_email = "Updated records: ". DB::affectedRows();
}
?>
<fieldset>
<legend>(Un)notify comments by member email</legend>
<div style="padding:10px;">
<?php
if(isset($error_email)){
	echo '<p class="Error">'.$error_email.'</p><hr/>';
}
?>
	<form name="form_notify" method="post" action="">
	<h1>Member email:</h1>
	<input type="text" name="email" value=""/>
	<input type="submit" name="submit_notify" value="Notify" />
	<input type="submit" name="submit_unnotify" value="Unnotify"/>
	</form>
</div>
</fieldset>

<fieldset>
<legend>(Un)notify comments by member id</legend>
<div style="padding:10px;">
<?php
if(isset($error_id)){
	echo '<p class="Error">'.$error_id.'</p><hr/>';
}
?>
	<form name="form_lock_account" method="post" action="">
	<h1 style="margin-top:0px;">Member id:</h1>
	<input type="text" name="user_id" value=""/>
	<input type="submit" name="submit_notify" value="Notify" />
	<input type="submit" name="submit_unnotify" value="Unnotify"/>
	</form>
</div>
</fieldset>