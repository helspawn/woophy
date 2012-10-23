<?php
if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}	

$access = ClassFactory::create('Access');
$isLoggedIn = $access->isLoggedIn();
?>
		<div id="MainContent">
			<div <?php if(isset($simple) && $simple == false)echo ' class="Section"'?> id="SendMessage">
				<div class="MainHeader DottedBottom clearfix"><h1>Send <?php echo htmlspecialchars($user_name) ?> a message</h1></div>
	<?php 
		if($isLoggedIn){ ?>		
				<form name="frmcontact" id="SendMessageForm" class="FormArea xhr" action="<?php echo ABSURL ?>services?method=woophy.user.sendMessage" method="GET">
					<input type="hidden" id="user_id" name="user_id" class="sendable" value="<?php echo $user_id ?>" />
					<div class="FormRow clearfix">
						<label for="from_name">From</label>
						<input size="30" type="text" id="from_name" name="from_name" class="text" readonly="true" style="color:#999999" value="<?php echo $access->getUserName() ?>" />
					</div>
					<div class="FormRow clearfix">
						<label for="from_email">Your e-mail address</label>
						<input maxlength="100" size="30" type="text" id="from_email" class="text sendable" name="from_email" value="<?php echo $access->getUserEmail() ?>" />
					</div>
					<div class="FormRow clearfix">
						<label for="subject">Subject</label>
						<input maxlength="100" size="30" type="text" id="subject" class="text sendable" name="subject" value="" />*
					</div>
					<div class="FormRow clearfix">
						<label for="message">Message</label>
						<textarea cols="55" rows="4" style="width:240px;resize:none;" id="message" class="sendable" name="message" ></textarea>*
					</div>
					<div class="SubmitRow clearfix">
						<input name="submit_contact" class="submit GreenButton" type="submit" value="Send!" />
						<span class="Error"></span>
					</div>
				</form>
<?php 
	}else{
		$a = explode('/', Utils::stripQueryString(REQUEST_PATH));
		array_pop($a);//remove sendmessage
		$uri = implode('/', $a).'/Login?viewmode=1&r=1';
?>
			<a class="LoginButton" href="<?php echo ROOT_PATH.$uri ?>">Log in</a> to send <?php echo htmlspecialchars($user_name) ?> a message.
<?php } ?>
			</div>
		</div>