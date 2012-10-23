<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}

	
	$access = ClassFactory::create('Access');
	
	$xhr = FALSE;
	$email = '';
	$password = '';
	$redirect = '';


	if(array_key_exists('submit_login', $_POST)){
		if(isset($_POST['xhr']) && $_POST['xhr']=='true') $xhr = TRUE;
		$password = trim($_POST['password']);
		$email = trim($_POST['email']);
		$remember = array_key_exists('remember', $_POST) ? true : false;
		$redirect = isset($_POST['redirect'])?$_POST['redirect']:'';
		$xml_login = $access->login(md5($password), $email, $remember);

		$submit_login = true;//submit flag
		if(isset($xml_login)){
			$error = $xml_login->err;
			if(!$xhr){
				if(!$error) $error = array('code'=>-1,'msg'=>'');
			}else{
				if($error){
					$code = $error['code'].'';
					$msg = $error['msg'].'';
					$output = json_encode(array('error_code'=>$code, 'error_message'=>$msg));
				}else $output = json_encode(array('status'=>'logged_in','redirect'=>$redirect));
				header('Content-type: application/json');
				echo $output;
				die();				
			}
		}
	}else{
		if(!isset($error)) $error = array('code'=>-1,'msg'=>'');	
	}

	if(!$access->isLoggedIn()){
		//output login form (including footer and header)
		include_once CLASS_PATH.'Page.class.php';
		$page = new Page();
		$page->setTitle('Login');
		$page->enableLogin(false);
		$page->addInlineScript('init_global_pre.add(Form.init_login);');
		if(!isset($_GET['viewmode']) || (int)$_GET['viewmode']==0)	echo $page->outputHeader();
		//else echo $page->outputHeaderSimple();//no header in popup mode

		$referer = '';
		if(@$_GET['r']==1){
			if(isset($_GET['photopopup'])) $referer = '<input type="hidden" name="redirect" class="sendable" value="'.htmlspecialchars(@$_SERVER['HTTP_REFERER']).'#photo-'.$_GET['photopopup'].'" />';
			else $referer = '<input type="hidden" name="redirect" class="sendable" value="'.htmlspecialchars(@$_SERVER['HTTP_REFERER']).'" />';
		}else if(isset($_GET['referer'])) $referer = '<input type="hidden" name="redirect" class="sendable" value="'.htmlspecialchars($_GET['referer']).'" />';
	?>
	<div id="MainContent">
		<div id="LoginForm">
			<div class="MainHeader DottedBottom clearfix"><h1>Log in to your Woophy account</h1></div>
			<?php if(isset($xml_login)) if($error['code'] != -1) echo '<p class="Error">'.$error['msg'].'</p>'; ?>
			<form id="frmLogin" class="xhr" name="frmLogin" method="post" action="<?php echo Utils::stripQueryString($_SERVER['REQUEST_URI']) ?>">
				<input type="hidden" name="output_mode" value="json" />
				<?php echo $referer ?>
				<div class="FormRow clearfix">
					<input id="email" name="email" tabindex="1" class="text sendable" value="<?php echo $email?>" type="text" alt="Email address" />
				</div>
				<div class="FormRow clearfix">
					<input type="password" name="password" tabindex="2" class="text sendable" value="<?php echo $password?>" alt="Password" />
					<div class="ForgotPassword"><a href="<?php echo ROOT_PATH ?>ForgotPasswd?r=1" target="_self">forgot password?</a></div>
				</div>
				<div class="SubmitRow clearfix">
					<input class="GreenButton large submit sendable" name="submit_login" id="submit_login" value="Log in" type="submit" />
					<div class="RememberMe"><input type="checkbox" id="remember" class="sendable" name="remember" tabindex="3" /><label class="small" for="remember">Remember me</label></div>
					<span class="Error"></span>	
				</div>
			</form>
			<div class="BottomText clearfix">
				<div class="left">Not a member yet? <a href="<?php echo ROOT_PATH ?>Register">Join now</a>, it's free!</div>
				<div class="right">Are you an advertiser? Send us an <a href="mailto:<?php echo INFO_EMAIL_ADDRESS ?>?subject=Advertising">email</a>.</div>
			</div>
		</div> <!-- end FormArea -->
	</div> <!-- end MainContent -->
<?php
		if(!isset($_GET['viewmode']) || (int)$_GET['viewmode']==0) echo $page->outputFooter();
		//else echo $page->outputFooterSimple();//no footer in popup mode
		exit;
	}else{
		if(!headers_sent()) {
			if(isset($_POST['redirect'])){
				header('Location: '.$_POST['redirect']);//redirect for clean url
				exit;
			}
		}
	}
?>