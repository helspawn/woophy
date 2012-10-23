<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}
	include_once CLASS_PATH.'User.class.php';
	$user = new User();
	$user->buffer = false;
	
	$pid = isset($_SESSION['pid']) ? (int)$_SESSION['pid'] : 0;
	
	if(isset($_POST['pid']) && $_POST['pid']==$pid){//prevent submitting data twice through redirect
	
		$user_name = trim($_POST['user_name']);
		$email = trim($_POST['email']);
		$newsletter = isset($_POST['newsletter']) ? 1 : 0;
		$anonymous = isset($_POST['anonymous']) ? 1 : 0;
		$notify_comments = isset($_POST['notify_comments']) ? 1 : 0;
		$exif = isset($_POST['exif']) ? 1 : 0;
		$public_favorites = isset($_POST['public_favorites']) ? 1 : 0;

		$avatar_file = $_FILES['avatar_file'];
		$country_code = $_POST['country_code'];
		$UFI = $_POST['city_id'];
		$photogear = trim($_POST['photogear']);
		$date_of_birth = $_POST['date_of_birth'];
		$about = trim($_POST['about']);
		$xml_update = $user->updateProfile(
			$user_name,
			$email,
			$newsletter,
			$anonymous,
			$notify_comments,
			$public_favorites,
			$exif,
			$avatar_file,
			$country_code,
			$UFI,
			$photogear,
			$date_of_birth,
			$about);
		$err_code = -1;
		if($err = $xml_update->err){
			$update_msg = '<p class="Error">'.$err['msg'].'</p>';
			$err_code = $err['code'];
		}else $update_msg = '<div class="Notice">Your profile has been updated.</div>';

		$pid = $pid + 1;
		$_SESSION['pid'] = $pid;
		session_write_close();
	}
	$xml = $user->getProfile();
	if($error = $xml->err) echo '<p class="Error">'.$error['msg'].'</p>';
	else{

		$page = ClassFactory::create('Page');
		$page->addScript('dateselector.js');
		$page->addScript('citylist.js');
		$page->addStyle('datefield.css');
		$page->addStyle('selectlist.css');		
		$js = 'jQuery(document).ready(function(){new ToolTip(\'showhelp\',\'Displays a small graphic image in your profile. The image will be resized to a maximum width and height of '.MAX_AVATAR_WIDTH.' pixels.\');';
		$js .= "var f = document.forms[0];var citylist = new CityList({inputObj:f['city']});jQuery(citylist).on('selectItem', function(evt, listItem){f['city_id'].value = listItem ? listItem.ufi : ''});";
		if(strlen($xml->country_code)>0) $js .= 'citylist.setCountryCode(\''.$xml->country_code.'\');';
		$js .= "var e = f['country_code'];e.onchange = function(){var v = this.options[this.selectedIndex].value;f['city'].value = '';f['city_id'].value = '';citylist.setCountryCode(v);};var delimiter = '-';e = f['date_of_birth'];var sel = new DateField(e);sel.dateFormatter = function(d) {return d.getFullYear() + delimiter + (d.getMonth()+1) + delimiter + d.getDate();};var y = new Date().getFullYear();sel.setYearRange(y-100,y);sel.setRange(null,new Date());if(e.value.length > 0){var d = e.value.split(delimiter);sel.setSelectedDate(new Date(parseInt(d[0]), parseInt(d[1])-1, parseInt(d[2])));}});";

		$page->addInlineScript($js);
?>
<div class="Section">
	<div class="MainHeader DottedBottom clearfix"><h1>Edit my profile</h1></div>
<?php
	if(isset($update_msg))echo $update_msg;
	
	$newsletter_cb = $xml->newsletter=='1'?'checked="true" ':'';
	$anonymous_cb = $xml->anonymous=='1'?'checked="true" ':'';
	$notify_comments_cb = $xml->notify_comments=='1'?'checked="true" ':'';
	$public_favorites_cb = $xml->public_favorites=='1'?'checked="true" ':'';
	$exif_cb = $xml->exif=='1'?'checked="true" ':'';
	$user_name = htmlspecialchars($xml->name);
	$email = htmlspecialchars($xml->email);
echo <<<EOD
	<form id="EditProfile" action="{$_SERVER['REQUEST_URI']}" name="frmprofile"	method="post" enctype="multipart/form-data">
	<div id="MandatoryInfo" class="FormArea clearfix DottedBottom">
		<div class="FormRow clearfix">
			<label for="user_name">Nickname</label>
			<input maxlength="30" id="user_name" name="user_name" value="$user_name" type="text" class="text" />
		</div>
		<div class="FormRow clearfix">
			<label for="email">E-mail</label>
			<input id="email" name="email" value="$email" type="text" class="text" />
		</div>
		<div class="FormRow clearfix">
			<label>Settings</label>
			<div class="RadioGroup">
				<div class="clearfix"><input id="newsletter" name="newsletter" {$newsletter_cb}type="checkbox" /><label for="newsletter">Send me the Woophy newsletter</label></div>
				<div class="clearfix"><input id="anonymous" name="anonymous" {$anonymous_cb}type="checkbox" /><label for="anonymous">Do not disclose my email address on Woophy</label></div>
				<div class="clearfix"><input id="notify_comments" name="notify_comments" {$notify_comments_cb}type="checkbox" /><label for="notify_comments">Notify me of comments via e-mail</label></div>
				<div class="clearfix"><input id="exif" name="exif" {$exif_cb}type="checkbox" /><label for="exif">Show my EXIF Data</label></div>
				<div class="clearfix"><input id="public_favorites" name="public_favorites" {$public_favorites_cb}type="checkbox" /><label for="public_favorites">Share my favorites with other members</label></div>
			</div>
		</div>
		<div class="SubmitRow clearfix">
			<input type="submit" class="submit GreenButton" name="submitprofile" value="Submit" />
		</div>
	</div>
	<h2>Optional</h2>
	<div id="OptionalInfo" class="FormArea">
		<div class="FormRow clearfix">
			<label>Avatar <span id="showhelp">[?]</span></label>
			<input type="file" name="avatar_file" accept="image/pjpeg,image/x-png,image/jpeg, image/gif" value="" /><input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
EOD;
	
	echo '<div id="preview_avatar">';
	//TODO: logging should be optional, turn on/off by config setting
	//file_put_contents("profile.log", AVATARS_PATH.$xml->id.".jpg\n", FILE_APPEND);
	echo '<img src="'.AVATARS_PATH.$xml->id.'.jpg?'.time().'" onerror="this.style.display=\'none\'" />';
	echo '</div></div>';
	echo '<div class="FormRow clearfix"><label>My country</label><div class="CountryDropdown DropdownContainer"><select name="country_code" class="sprite">';
	echo '<option value="">-</option>';

	include CLASS_PATH.'Location.class.php';
	$location = new Location();
	$country_xml = $location->getAllCountries();
	if($error = $country_xml->err){
		print $error['msg'];
	}else{
		foreach ($country_xml->country as $c){
			echo '<option ';
			if(mb_strtolower($xml->country_code) == mb_strtolower($c['cc'])) echo 'selected="true" ';
			echo 'value="'.$c['cc'].'">'.$c.'</option>';
		}
	}

echo <<<EOD
		</select></div></div>
		<div class="FormRow clearfix">
			<label>My city</label>
			<input autocomplete="off" value="$xml->city_name" name="city" type="text" class="text" />
			<input name="city_id" id="city_id" type="hidden" value="$xml->city_id" />
		</div>
		<div class="FormRow clearfix">
			<label>Photo gear</label>
			<input name="photogear" value="$xml->photogear" type="text" class="text" />
		</div>
		<div class="FormRow clearfix">
			<label>My Birthday</label>
			<input type="text" class="DateField text sprite_admin" name="date_of_birth" readonly="true" value="$xml->date_of_birth" />
		</div>
		<div class="FormRow clearfix">
			<label>About me</label>
			<textarea rows="4" cols="60" style="width:300px;max-width:300px;" name="about">$xml->about</textarea>
		</div>
		<div class="SubmitRow clearfix">
			<input type="submit" class="submit GreenButton" name="submitprofile" value="Submit" />
		</div>
		<input type="hidden" name="pid" value="$pid" />
		</div></form></div>
EOD;
	}
?>