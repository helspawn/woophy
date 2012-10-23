<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}
	include CLASS_PATH.'Page.class.php';
	include CLASS_PATH.'Account.class.php';
	
	$account = new Account();

	$xhr = FALSE;
	if(isset($_POST['xhr']) && $_POST['xhr']=='true') $xhr = TRUE;
	if($xhr) header('Content-type: application/json');
	
	if(isset($_GET['uid'],$_GET['pw']))	$xml_update = $account->updateForgotPasswd($_GET['uid'],$_GET['pw']);
	if(isset($_POST['email_forgot'])) $xml_random = $account->randomForgotPasswd($_POST['email_forgot']);

	if(isset($xml_update) || isset($xml_random)){
		if(isset($xml_update)) $rsp_error = $xml_update->err;
		if(isset($xml_random)) $rsp_error = $xml_random->err;
		if(isset($rsp_error['code'])){
			if($xhr) echo '{"error_code":"' .$rsp_error['code']. '", "error_message":"<p>'.$rsp_error['msg']. '</p>"}';
			else $result = '<p class="Error">'.$rsp_error['msg'].'</p>';
		}else{
			$rsp_error = array('code'=>-1,'msg'=>'');
			if(isset($xml_update)) $message = '<p>Your password has been changed and you are logged in.</p><p>If you want to change this new password to a password more easy for you to remember go to the <a href="'.ROOT_PATH.'account/profile">My Woophy section.</p>';
			if(isset($xml_random)) $message = '<p>We sent you an email. Click on the link in this email to activate your new password.</p>';
			if($xhr) echo '{"message":"'.$message.'"}';
			else $result = $message;
		}
		if($xhr) die();
	}else{
		$rsp_error = array('code'=>-1,'msg'=>'');
	}
	
	$page = new Page();
	$page->setTitle('Forgot Password');
	if(!isset($_GET['viewmode']) || (int)$_GET['viewmode']==0)	echo $page->outputHeader();
	//else echo $page->outputHeaderSimple();


	echo '<div id="MainContent">';
	echo '<div id="ForgotPasswordForm" class="FormArea clearfix">';	
	echo '<div class="MainHeader DottedBottom clearfix"><h1>Forgot password</h1></div>';
	if((isset($xml_update) || isset($xml_random)) && $rsp_error['code']==-1){
		echo $result;
	}else{

		if(isset($xml_random)){
			if(!($rsp_error = $xml_random->err)){
				echo '<p>We sent you an email. Click on the link in this email to activate your new password.</p>';
				echo '</div>';
				echo $page->outputFooter();
				exit;
			}
		}
	?>
	<form class="xhr" name="frmForgotPassword" id="frmForgotPassword" action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="post">
		<p>Fill in your e-mail address and we will send you an e-mail with a new randomly generated password.</p>
		<div class="FormRow clearfix">
			<label for="email_forgot">Email address</label>
			<input name="email_forgot" type="text" class="text sendable" id="email_forgot" size="40">
			
		</div>
		<div class="SubmitRow clearfix">
			<input class="submit GreenButton" name="submit_forgot_password" id="submit_forgot_password" type="submit" value="Submit"/>
			<span class="Error"><?php echo $rsp_error['msg'] ?></span>
		</div>
		<div class="BottomText"><a class="BackToLogin" href="<?php echo ROOT_PATH ?>login">Back to Login</a></div>
	</form>
	<?php
	}
	echo '</div></div>';
	if(!isset($_GET['viewmode']) || (int)$_GET['viewmode']==0) echo $page->outputFooter();
	//else echo $page->outputFooterSimple();
	exit;
?>