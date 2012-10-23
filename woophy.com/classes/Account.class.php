<?php
require_once CLASS_PATH.'Response.class.php';
class Account extends Response{

	private $access;
	const ERRBASE = 100;

	public function __construct(){
		$this->access = ClassFactory::create('Access');
		parent::__construct();
	}
	public function editPasswd($currPW='', $newPW1='', $newPW2=''){
		$XMLObject = $this->getXMLObject();
		$currPW = trim($currPW);
		$newPW1 = trim($newPW1);
		$newPW2 = trim($newPW2);
		if(mb_strlen($currPW)>0 && mb_strlen($newPW1)>0 && mb_strlen($newPW2)>0){
			if($newPW1 != $newPW2) $this->throwError(6);
			else{
				$uid = $this->access->getUserId();
				$result = DB::query('SELECT password FROM users WHERE user_id = '.(int)$uid.' AND password = \''.md5($currPW).'\'');
				if($result){
					if(DB::numRows($result) == 1){
						if(!DB::query('UPDATE users SET password = \''.md5($newPW1).'\' WHERE user_id = '.(int)$uid)) $this->throwError(4);
					}else $this->throwError(5);
				}else $this->throwError(4);
			}
		}else $this->throwError(1);
		return $XMLObject;
	}
	public function activate($uid, $pw){
		$XMLObject = $this->getXMLObject();
		$query = 'SELECT user_name, email, welcome_letter FROM users WHERE user_id = '.(int)$uid.' AND password = \''.DB::escape($pw).'\';';
		$result = DB::query($query);
		if($result && DB::numRows($result) == 1){
			if(DB::query('UPDATE users SET active = 1 WHERE user_id = \''.(int)$uid.'\';')){
				$affectedRows = (int)DB::affectedRows();
				$row = DB::fetchAssoc($result);
				$this->access->setSessionVars($row['user_name'], (int)$uid, $row['email']);
				if($affectedRows==1){//send only once (in case someone clicks the activation link several times)
					if(isset($row['welcome_letter'])){
						include_once CLASS_PATH.'Template.class.php';
						include_once CLASS_PATH.'Mail.class.php';
						try{
							$lang = mb_strtolower($row['welcome_letter']);
							$tpl = new Template('welcome_'.$lang.'.tpl');
						}catch(Exception $e){
							$lang = 'en';
							$tpl = new Template('welcome_en.tpl');
						}
						if(version_compare(phpversion(), '5.1.0', '>=')){
							$timezone = date_default_timezone_get();
						}
						if($result = DB::query('SELECT language_name FROM languages WHERE language_code = \''.DB::escape($lang).'\'')){
							if(DB::numRows($result)==1){
								setlocale(LC_TIME, mb_strtolower(DB::result($result, 0)));
							}
						}
						$date = strftime("%A, %B %e, %Y");
						if(isset($timezone))date_default_timezone_set($timezone);
						$body = (string)$tpl->parse(array('user_name'=>$row['user_name'],'date'=>$date));
						
						if(mb_strlen($body) >0){
							$mail = new Mail();
							$mail->From(EMAIL_SENDER, NOREPLY_EMAIL_ADDRESS);
							$mail->To($row['email']);
							$mail->Subject('Woophy Welcome Letter');
							$mail->Body($body, false);
							$mail->XHeader('Content-Type', 'text/html; charset=UTF-8');
							$mail->Send();
						}
					}
				}
			}else $this->throwError(11);
		}else $this->throwError(11);
		return $XMLObject;
	}
	public function updateForgotPasswd($uid, $pw){
		$XMLObject = $this->getXMLObject();
		if(isset($uid,$pw)){
			$pw = DB::escape($pw);
			$changed_password = false;
			$query = 'SELECT user_name, email FROM users WHERE user_id = '.(int)$uid.' AND forgot_password = \''.$pw.'\';';
			$result = DB::query($query);
			if($result && DB::numRows($result) == 1){
				$row = DB::fetchAssoc($result);	
				if(DB::query('UPDATE users SET password = \''.$pw.'\', forgot_password = NULL, active = 1 WHERE user_id ='.(int)$uid)){//set active to 1 in case user has not activated his account yet
					$this->access->setSessionVars($row['user_name'], $uid, $row['email']);
				}else $this->throwError(15);
			}else $this->throwError(14);
		}else $this->throwError(13);
		return $XMLObject;
	}
	public function randomForgotPasswd($email=''){
		$XMLObject = $this->getXMLObject();
		$email = DB::escape($email);
		if(mb_strlen($email)>0){
			$query = 'SELECT user_id, user_name, email FROM users WHERE email = \''.$email.'\' LIMIT 0,1;';
			$result = DB::query($query);
			if($result){
				if(DB::numRows($result) == 1) {
					$row = DB::fetchAssoc($result);
					$uid = (int)$row['user_id'];
					$to = $row['email'];
					$name = $row['user_name'];
					
					//generate random password:
					$chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';//exclude l to avoid confusion with 1
					srand((double)microtime()*1000000);  
					$i = 0; 
					$pw = '';
					while (++$i < 9) {
						$num = rand() % 33; 
						$tmp = substr($chars, $num, 1); 
						$pw = $pw . $tmp;
					}
					$result = DB::query('UPDATE users SET forgot_password = \''.md5($pw).'\' WHERE user_id = '.$uid.';');
					if($result){
						$forgot_url = ABSURL.'ForgotPasswd?uid='.$uid.'&pw='.md5($pw);
						include_once CLASS_PATH.'Mail.class.php';
						//echo $pw.'<br/>';
						//echo $forgot_url;
						$body = "To $name,\r\n\r\n";
						$body .= "Your new password is: $pw\r\n";
						$body .= "Click on the link below to activate your new password. Once signed in you can change your password on the My Account page.\r\n\r\n";
						$body .= $forgot_url;

						$mail = new Mail();
						$mail->From(EMAIL_SENDER, SUPPORT_EMAIL_ADDRESS);
						$mail->To($to);
						$mail->Subject('New password Woophy');
						$mail->Body($body);
						$success = $mail->Send();
						if(!$success) $this->throwError(17);
					}else $this->throwError(15);
				}else $this->throwError(16);
			}else $this->throwError(16);
		}else $this->throwError(1);
		return $XMLObject;
	}
	public function remove(){
		$XMLObject = $this->getXMLObject();
		if($this->access->isSecureLoggedIn()){
			$uid = (int)$this->access->getUserId();
			if($uid>1){//not allow admin to delete
				DB::query('INSERT INTO users_deleted (user_id, user_name,user_email,user_ip) SELECT user_id,user_name,email,last_ip FROM users WHERE user_id = '.$uid);//log
				DB::query('DELETE FROM favorite_photos WHERE user_id = '.$uid);
				DB::query('DELETE FROM favorite_users WHERE user_id = '.$uid);
				DB::query('DELETE FROM favorite_users WHERE favorite_user_id = '.$uid);
				DB::query('DELETE FROM awards WHERE user_id = '.$uid);
				DB::query('DELETE FROM photo_folders WHERE user_id = '.$uid);
				DB::query('DELETE FROM ambassadors WHERE user_id = '.$uid);
				DB::query('DELETE FROM users WHERE user_id = '.$uid);
				DB::query('DELETE FROM blog_posts WHERE user_id = '.$uid);//TODO: delete blog photos, blog_comments
				
				DB::query('UPDATE photo_comments SET poster_id = NULL WHERE poster_id = '.$uid);	
				$result = DB::query('SELECT city_id, photo_id FROM photos WHERE user_id = '.$uid);
				if($result){
					$city_ids = array();
					$photo = ClassFactory::create('Photo');
					while ($row = DB::fetchAssoc($result)){	
						$photo->removePhoto($row['photo_id'], true, true, false, false, false);
						$city_ids[$row['city_id']]=1;
					}
					$city = ClassFactory::create('City');
					foreach($city_ids as $k=>$v){
						$city->updatePhotoCount($k);
					}
					/*
					//do not update status on remove, only on add
					$status = ClassFactory::create('Status');
					$status->updateNumberOfPhotos();
					$status->updateNumberOfUsers();
					*/
				}
			}
			$this->access->logout();
		}
		return $XMLObject;
	}
	
	//account is inactive when registered
	public function register($user_name, $password, $password2, $email){
		$XMLObject = $this->getXMLObject();
		$user_name = trim($user_name);
		$password = trim($password);
		$password2 = trim($password2);
		$email = trim($email);
		$welcome_letter = 'en'; //welcome letter for all new users, only in English (since the rest of the site is also only in English)
		if(mb_strlen($user_name)>0 && mb_strlen($password)>0 && mb_strlen($password2)>0 && mb_strlen($email)>0){	
			if($password != $password2) $this->throwError(12);
			else{
				if(Utils::isValidUserName($user_name)){
					$result = DB::query('SELECT user_name FROM users WHERE user_name = \''.DB::escape($user_name).'\'');
					if($result && DB::numRows($result) > 0) $this->throwError(2);
					else{
						$query = 'SELECT email FROM users WHERE email = \''.DB::escape($email).'\'';
						$result = DB::query($query);
						if($result && DB::numRows($result) > 0) $this->throwError(9);
						else{
							$pw = md5($password);
							$result = DB::query('INSERT INTO users (user_name, password, email, active, welcome_letter, public_favorites, registration_date) VALUES (\''.DB::escape($user_name).'\', \''.$pw.'\', \''.DB::escape($email).'\', 0, '.(isset($welcome_letter)?'\''.DB::escape($welcome_letter).'\'':'NULL').', 1, NOW());');
							if($result && DB::affectedRows() == 1){
								$uid = DB::insertId();
								$activation_url = ABSURL.'activate?&uid='.$uid.'&pw='.$pw;

								$body = "Thank you for registering at Woophy, $user_name.\r\n\r\n";
								$body .= "Your account has been created with the following details:\r\n\r\n";
								$body .= "Nickname: $user_name\r\n";
								$body .= "Login: $email\r\n";
								$body .= "Password: $password\r\n\r\n";
								$body .= "(keep this email for future reference because we don't know your password)\r\n\r\n";
								$body .= "Click on the link below to activate your account and you can start uploading your photos right away!\r\n\r\n";
								$body .= $activation_url;
								$body .= "\r\n\r\n";
								$body .= "If the link does not work or you can't open a link from your email software copy paste the above link in your browser.";
								if(isset($welcome_letter)) $body .= "\r\nYou will receive a welcome mail after you activated your account.";
								
								include_once CLASS_PATH.'Mail.class.php';
								$mail = new Mail();
								$mail->From(EMAIL_SENDER, SUPPORT_EMAIL_ADDRESS);
								$mail->To($email);
								$mail->Subject('Your Woophy account');
								$mail->Body($body);
								$mail->Send();
								
								$status = ClassFactory::create('Status');
								$status->updateNumberOfUsers();

							}else $this->throwError(10);
						}
					}
				}else $this->throwError(18);
			}
		}else $this->throwError(1);
		return $XMLObject;
	}
	protected function throwError($code=1){
		switch($code){
			case 1:$msg="Please fill in all the required fields.";break;
			case 2:$msg='This nickname already exists. Try another name.';break;
			case 3:$msg='This e-mail address already exists. Try again.';break;
			case 4:$msg="Could not update your account. Try again.";break;
			case 5:$msg="The password you submitted does not match your current password.";break;
			case 6:$msg="Your new password entry did not match. Try again.";break;
			case 7:$msg='Could not delete your account. Try again.';break;
			case 8:$msg='Avatar could not be uploaded.';break;
			case 9:$msg='This e-mail address already exists. <a href="'.ABSURL.'ForgotPasswd">Forgot password</a>?';break;
			case 10:$msg='Could not create account. Try again.';break;
			case 11:$msg="We could not activate your account. Copy the entire link from the email in your browser and try again.";break;
			case 12:$msg='The password entries do not match.';break;
			case 13:$msg='Missing parameter.';break;
			case 14:$msg='Your password could not be changed! Copy the entire link from the email in your browser and try again.';break;
			case 15:$msg='Could not update your password.';break;
			case 16:$msg='Unknown e-mail address. Try again.';break;
			case 17:$msg='Could not send email';break;
			case 18:$msg='Sorry, this nickname is not allowed.';break;
		}
		parent::throwError(self::ERRBASE+$code, $msg);
	}
}
?>