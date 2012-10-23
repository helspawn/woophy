<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}

	include CLASS_PATH.'Page.class.php';

	$access = ClassFactory::create('Access');
	$url_default = 'http://';
	
	if(isset($_POST['submit_report'])){
		include CLASS_PATH.'AbuseReport.class.php';
		$report = new AbuseReport();
		if($access->isLoggedIn()){
			$uid = $access->getUserId();
			$name = NULL;
			$email = NULL;
		}else{
			$uid = NULL;
			$name = $_POST['name'];
			$email = $_POST['email'];
		}
		if($_POST['url']==$url_default)$_POST['url']='';
		$xml_report = $report->post($_POST['url'], $uid, $name, $email, $_POST['message']);
		if($err = $xml_report->err)$msg_err = $err['msg'];
		else $msg_success = 'Thank you for taking the time to report abuse found in Woophy.';
	}
	
	$url = $url_default;
	if(isset($_GET['url']))$url = $_GET['url'];	
	$message = '';
	if(isset($_POST['message']))$message = $_POST['message'];

	$page = new Page();
	$page->setTitle('Report Abuse');
	$page->addInlineStyle('input.report{width:350px}.Notice{padding:6px;margin-top:15px;}');
	echo $page->outputHeader();
?>
<div id="MainContent">
	<div id="ReportAbuseForm" class="FormArea clearfix">
	<div class="MainHeader DottedBottom"><h1>Report Abuse</h1></div>
	<?php
		if(isset($msg_err)) echo '<p class="Error">'.$msg_err.'</p>';
		else if(isset($msg_success)) echo '<div class="Notice">'.$msg_success.'</div>';
		else echo 'Use the form below to report abuse or a violation of our <a href="'.ROOT_PATH.'termsofuse">Terms of Use</a>.';

		if(!isset($msg_success)){
			if($access->isLoggedIn()){
			
	?>
	<form class="FormArea" name="frmReport" method="post" action="<?php echo Utils::stripSpecialAction($_SERVER['REQUEST_URI']) ?>">
		<?php
			//since 09.12.2012 it's not possible anymore to post anonymously
			/*
			if(!$access->isLoggedIn()){
				$name = isset($_POST['name']) ? $_POST['name'] : '';
				$email = isset($_POST['email']) ? $_POST['email'] : '';
				$error = isset($err) ? ($err['code']=='1502') : false;
				echo '<div class="FormRow clearfix"><label for="name"';
				if($error && strlen($name)==0) echo ' class="Error"';
				echo '>Your name</label><input type="text" id="name" class="report text" name="name" value="'.$name.'" /></div>';
				echo '<div class="FormRow clearfix"><label for="email"';
				if($error && strlen($email)==0) echo ' class="Error"';
				echo '>Your e-mail address</label><input class="report text" type="text" id="email" name="email" value="'.$email.'" /></div>';
			}*/
		?>
		<div class="FormRow clearfix">
			<label for="url"<?php if(isset($err) && $err['code']=='1501')echo' class="Error"'?>>URL of the abuse</label>
			<input class="report text" type="text" id="url" name="url" value="<?php echo $url?>" />
		</div>
		<div class="FormRow clearfix">
			<label for="message">Message</label>
			<textarea name="message" id="message" cols="65" style="width:350px;max-width:350px;" rows="6"><?php echo $message?></textarea>
		</div>
		<div class="FormRow clearfix"><input class="submit RedButton" type="submit" name="submit_report" value="Submit" /></div>
	</form>
	<?php
			}else{
				echo '<div class="Notice">Please, <a href="'.ROOT_PATH.'Login?r=1" target="_top">log in</a> to report abuse.</div>';
			}
		}
	?>
	</div>
</div>
<?php
	echo $page->outputFooter();
?>