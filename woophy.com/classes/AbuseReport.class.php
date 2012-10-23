<?php
require_once CLASS_PATH.'Mail.class.php';
require_once CLASS_PATH.'Response.class.php';
class AbuseReport extends Response{
	const ERRBASE = 1500;
	public function post($url, $user_id, $user_name, $user_email, $message){
		$XMLObject = $this->getXMLObject();
		if(mb_strlen(trim($url))>0){
			if((isset($user_name, $user_email) && mb_strlen(trim($user_name))>0 && mb_strlen(trim($user_email))>0) || isset($user_id)){
				
				if(isset($user_id)){
					//user is logged in:
					$access = ClassFactory::create('Access');
					$user_name = $access->getUserName();
					$result = DB::query('SELECT email FROM users WHERE user_id='.(int)$user_id);
					if($result) $user_email = DB::result($result, 0);
				}
				
				$user_id = isset($user_id) ? (int)$user_id : 'NULL';
				$name = isset($user_name) ? '\''.DB::escape($user_name).'\'' : 'NULL';
				$email = isset($user_email) ? '\''.DB::escape($user_email).'\'' : 'NULL';
				DB::query('INSERT INTO abuse_reports (report_url, user_id, user_name, user_email, message, user_ip) VALUES (\''.DB::escape($url).'\','.$user_id.','.$name.','.$email.',\''.DB::escape($message).'\',\''.DB::escape(Utils::getIP()).'\')');

				$mail = new Mail();
				$mail->From($user_name, INFO_EMAIL_ADDRESS);
				$mail->To(SUPPORT_EMAIL_ADDRESS);
				$mail->Cc(INFO_EMAIL_ADDRESS);
				$mail->Subject('Abuse Report '.$url);
				$mail->Body($url."\n\n".$user_name.(isset($user_email)?' ('.$user_email.') ':'')." wrote:\n\n".$message);
				if(!$mail->Send())$this->throwError(3);
			}else $this->throwError(2);
		}else $this->throwError(1);
		return $XMLObject;
	}
	protected function throwError($code=1, $msg = ''){
		switch($code){
			case 1:$msg='Please fill in the url of the abuse.';break;
			case 2:$msg='Fill in all the required fields.';break;
			case 3:$msg='Could not send e-mail.';break;
		}
		parent::throwError(self::ERRBASE+$code, $msg);
	}
}
?>
