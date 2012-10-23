<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}
	if(isset($_POST['submit_password'])){
		include_once CLASS_PATH.'Account.class.php';
		$account = new Account();
		$xml = $account->editPasswd($_POST['currPW'], $_POST['newPW1'], $_POST['newPW2']);

		if ($error = $xml->err) {
			$error_passwd = $error['msg'];
		}else $msg_passwd = 'Your password has been updated.';
	}
?>
<div class="Section">
	<div class="MainHeader DottedBottom clearfix"><h1>Change password</h1></div>
<?php
		if(isset($error_passwd)) echo '<p class="Error">'.$error_passwd.'</p>';
		if(isset($msg_passwd)) echo '<p class="info">'.$msg_passwd.'</p>';
?>
	<form class="FormArea" action="<?php echo $_SERVER['REQUEST_URI']; ?>" name="frmpasswd"	method="post">
		<div class="FormRow clearfix">
			<label for="currPW">Current Password</label>
			<input id="currPW" name="currPW" class="text" value="" type="password" />*<br/>
		</div>
		<div class="FormRow clearfix">
			<label for="newPW1">New Password</label>
			<input id="newPW1" name="newPW1" class="text" value="" type="password" />*<br/>
		</div>
		<div class="FormRow clearfix">
			<label for="newPW2">Retype New Password</label>
			<input id="newPW2" name="newPW2" class="text" value="" type="password" />*<br/>
		</div>
		<div class="SubmitRow clearfix">		
			<input name="submit_password" class="submit GreenButton" type="submit" value="submit" />
		</div>
	
	</form>
</div>