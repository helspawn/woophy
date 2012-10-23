<?php
if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}

if(isset($_GET['uid'],$_GET['pw'])){
	include_once CLASS_PATH.'Account.class.php';
	$account = new Account();
	$xml_activate = $account->activate($_GET['uid'], $_GET['pw']);
	if($err = $xml_activate->err) $errmsg = $err['msg'];
}else $errmsg = 'We could not activate your account. Missing parameters.';

if(isset($errmsg)){
	//something's wrong: display error page:
	include_once CLASS_PATH.'Page.class.php';
	$page = new Page();
	$page->setTitle('Activate Account');
	echo $page->outputHeader();
	echo '<div class="FormArea" id="ActivateAccount">';
	echo '<div class="MainHeader DottedBottom clearfix"><h1>Activate account</h1></div>';
	echo '<p>'.$errmsg.'</p>';
	echo '</div>';
	echo $page->outputFooter();
	exit;
}else{
	header('Location: '.ABSURL.'account/activated');
	exit;
}
?>

	<div class="FormArea clearfix" id="ForgotPasswordForm"><div class="MainHeader DottedBottom clearfix"><h1>Forgot password</h1></div>	<form method="post" action="/woophy3.0/branches/20120224-Global_Redesign/woophy.com/html/forgotpasswd" id="frmForgotPassword" name="frmForgotPassword" class="xhr"><input type="hidden" value="json" name="output_mode" class="sendable"><input type="hidden" value="true" name="xhr" class="sendable">
		<p>Fill in your e-mail address and we will send you an e-mail with a new randomly generated password.</p>
		<div class="FormRow clearfix">
			<label for="email_forgot">Email address</label>
			<input type="text" size="40" id="email_forgot" class="text sendable" name="email_forgot">
			
		</div>
		<div class="SubmitRow clearfix">
			<input type="submit" value="Submit" id="submit_forgot_password" name="submit_forgot_password" class="submit GreenButton">
			<span class="Error"></span>
		</div>
		<div class="BottomText"><a href="/woophy3.0/branches/20120224-Global_Redesign/woophy.com/html/login" class="BackToLogin">Back to Login</a></div>
	</form>
	</div>