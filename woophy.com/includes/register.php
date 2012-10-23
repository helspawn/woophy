<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}
	//$access = ClassFactory::create('Access');
	//if($access->isLoggedIn()) header('location:'.ROOT_PATH.'account');

	include_once CLASS_PATH.'Utils.class.php';
	include_once CLASS_PATH.'Page.class.php';
	include_once CLASS_PATH.'Account.class.php';
	include_once CLASS_PATH.'User.class.php';

	$page = new Page();
	$page->setTitle('Register');
	echo $page->outputHeader(2);
	
	$email = '';
	$user_name = '';
	$password = '';
	$password2 = '';
	if(@$_GET['origin']=='widget'){
		$user_name = $_GET['username'];
	}
	if(array_key_exists('submit_register', $_POST)){	
		$email = $_POST['email'];
		$user_name = $_POST['user_name'];
		$password = $_POST['password'];
		$password2 = $_POST['password2'];
		$account = new Account();
		$xml_account = $account->register($_POST['user_name'], $_POST['password'], $_POST['password2'], $_POST['email']);
	}
	echo '<div id="MainContent" class="clearfix"><div id="MainColumn">'.PHP_EOL;
	
	if(!isset($xml_account) || isset($xml_account, $xml_account->err)){
		//not (succesfully) registered
	?>
	<div id="TitleBar"><h1>Join now, it's free!</h1></div>
	<div class="Section">
	<p class="strong">Share your photos of the world and help creating a photographic image of our earth.</p><p>A free Woophy membership allows you to store, share and publish your photos of our planet. You can create your own profile page and put yourself on the map. But Woophy is more, when you register you can meet other members, discuss photos, rate and comment photos, take part in our competitions and forum.</p>
	<?php
		$error = array('code'=>-1);
		if(isset($xml_account)){
			if($error = $xml_account->err){
				echo '<p class="Error">'.$error['msg'].'</p>';
			}
		}
		$uri = Utils::stripSpecialAction($_SERVER['REQUEST_URI']);
	?>
	<form class="FormArea" id="RegisterForm" name="frmRegister" method="post" action="<?php echo $uri.'/Register' ?>">
		<div class="FormRow clearfix">
			<label for="user_name"<?php if($error['code']==102 || $error['code']==118)echo ' class="Error"'?>>Your name</label>
			<input size="30" id="user_name" maxlength="30" name="user_name" class="text" value="<?php echo htmlspecialchars($user_name)?>" type="text" /><span class="small midgreen">Your name as you would like it to appear on this website</span>
		</div>
		<div class="FormRow clearfix">
			<label for="email"<?php if($error['code']==109)echo ' class="Error"'?>>Your e-mail address</label>
			<input size="30" id="email" name="email" class="text" value="<?php echo htmlspecialchars($email)?>" type="text" /><span class="small midgreen">Your e-mail address will be invisible to other Woophy users</span>
		</div>
		<div class="FormRow clearfix">
			<label for="password"<?php if($error['code']==112)echo ' class="Error"'?>>Password</label>
			<input size="30" id="password" name="password" class="text" value="<?php echo $password?>" type="password" />
		</div>
		<div class="FormRow clearfix">
			<label for="password2"<?php if($error['code']==112)echo ' class="Error"'?>>Re-enter password</label>
			<input size="30" id="password2" name="password2" class="text" value="<?php echo $password2?>" type="password" />
		</div>
		<div class="FormRow clearfix">
			<label>Terms of Use</label><p>By clicking on the "Submit" button you agree to the Woophy <a href="<?php echo ROOT_PATH ?>termsofuse" target="_blank">Terms of Use</a> and you agree to receive notices from us electronically.</p>
		</div>
		<div class="FormRow clearfix">
			<input name="submit_register" class="submit GreenButton" id="submit_register" type="submit" value="Submit" />
		</div>
	</form>
	<div class="Warning clearfix"><p><span class="sprite"/>!</span>When you sign up, we send you an email that contains a link you must enter into your browser to activate your Woophy account. Your email address will never be shared with third parties or made public in any way.</p></div>
	</div>
	<p>Already a registered user? Go to the <a href="<?php echo $uri.'/Login' ?>">sign in page</a>.</p>
<?php
	}else{
		//successfully registered:
?>
	<div id="TitleBar"><h1>Registration almost complete</h1></div>
	<div class="Section">
	<p class="strong">Check your e-mail address <strong><?php echo $email ?></strong></p>
	<p><strong>Important!</strong> A confirmation e-mail has been sent to this address and you have to click the link in the message to activate your account.</p>

	<p>E-mail address not correct? Please contact us at <a href="mailto:<?php echo SUPPORT_EMAIL_ADDRESS?>"><?php echo SUPPORT_EMAIL_ADDRESS?>.</a></p>
	<p>Please note that some email providers may block the confirmation mail or put the email into the &quot;unwanted&quot; or &quot;bulk&quot; mail folder.</p>
	<br/>
	<div class="Warning"><span class="sprite"/>!</span><p></p>If you do not receive your email within 30 minutes please contact us at <a href="mailto:<?php echo SUPPORT_EMAIL_ADDRESS?>"><?php echo SUPPORT_EMAIL_ADDRESS?>.</a></p></div>
	</div>
<?php
	}
	echo '</div> <!-- endMainColumn -->';

	echo '<div id="RightColumn"><div class="Section"><div class="Header clearfix"><h2>Latest Registrations</h2></div>';

	$user = new User();
	$xml_users = $user->getRecent(0,5);
	if($err = $xml_users->err){
		$html = '<div class="Error">'.$err['msg'].'</div>';
	}else{
		$users = $xml_users->user;
		$html = '';
		$count = 0;
		$html .= '<div class="UsersList clearfix">';
		foreach($users as $u){
			$html .= '<div class="User clearfix DottedBottom">';
			$url = ROOT_PATH.'member/'.urlencode($u->name);
			$html .= '<a class="Thumb sprite" href="'.$url.'"><img src="'.AVATARS_URL.$u->id.'.jpg" /></a>';
			$html .= '<div class="Content"><a href="'.$url.'">'.$u->name.'</a><br/>';
			//$html .= '<div>';
			$num = (int)$u->photo_count;
			$html .=  '<strong>'.$num.'</strong> photo'.($num==1?'':'s').'<br />';
			$html .= 'registered: '.(Utils::dateDiff(strtotime($u->registration_date))).' ago';
			$html .= '</div></div>';//</div>';
		}
		$html .= '</div>';
	}
	echo $html;
	
	echo '</div></div>';
	echo '</div></div> <!-- end RightColumn, MainContent -->';
	
	echo $page->outputFooter();
	exit;
?>