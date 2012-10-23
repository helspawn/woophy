<?php
	require_once CLASS_PATH.'Response.class.php';
	class Access extends Response{
		
		const ERRBASE = 200;
		public function __construct() {
			parent::__construct();
			@session_start();
			if(!$this->isLoggedIn()){
				if(isset($_COOKIE['woophy'])){
					$c = $_COOKIE['woophy'];
					if(array_key_exists('password', $c)){
						if(array_key_exists('email', $c)){
							$this->login($c['password'], $c['email'], false);
						}
					}
				}
			}
		}
		public function login($password, $email, $remember=false){//password has to be md5 encrypted!
			$XMLObject = $this->getXMLObject();
			$result = DB::query('SELECT user_name, user_id, active FROM users WHERE email = \''.DB::escape($email).'\' AND password = \''.DB::escape($password).'\' LIMIT 0,1');	
			if($result && DB::numRows($result) > 0){
				$row = DB::fetchAssoc($result);
				if($row['active'] == 1){
					$this->setSessionVars($row['user_name'],$row['user_id'],$email);
					DB::query('UPDATE users SET last_ip = \''.DB::escape(Utils::getIP()).'\' WHERE user_id= '.$row['user_id']);
					if($remember){
						//remember for 100 days:
						@setcookie('woophy[email]', $email, time() + 3600*24*100, '/');
						@setcookie('woophy[password]', $password, time() + 3600*24*100, '/');
					}
				}else $this->throwError(3);
			}else{
				$result = DB::query('SELECT user_id FROM users WHERE email = \''.DB::escape($email).'\' LIMIT 0,1');
				if($result && DB::numRows($result) > 0)$this->throwError(2);
				else $this->throwError(1);
			}
			return $XMLObject;
		}
		public function logout(){
			$_SESSION = array();
			@session_write_close();
			@setcookie('woophy[email]', '', time() - 3600, '/');
			@setcookie('woophy[password]', '', time() - 3600, '/');

		}
		public function setSessionVars($name, $id, $email, $close_session=TRUE){
			$_SESSION = array();
			$_SESSION['username'] = trim($name);
			$_SESSION['userid'] = (int)$id;
			$_SESSION['useremail'] = trim($email);
			if($close_session)@session_write_close();
		}
		public function getUserEmail(){
			return ($this->isLoggedIn()) ? $_SESSION['useremail'] : NULL;
		}
		public function getUserName(){
			return ($this->isLoggedIn()) ? $_SESSION['username'] : NULL;
		}
		public function getUserId(){
			return ($this->isLoggedIn()) ? $_SESSION['userid'] : NULL;
		}
		public function isLoggedIn(){
			return (isset($_SESSION['username'],$_SESSION['userid']));
		}
		public function isSecureLoggedIn(){
			if($this->isLoggedIn()){
				$result = DB::query('SELECT last_ip FROM users WHERE user_id= '.(int)$_SESSION['userid']);
				if($result){
					if(Utils::getIP() == DB::result($result, 0))return true;
				}
			}
			return false;
		}
		protected function throwError($code=1, $msg = ''){
			switch($code){
				case 1:$msg='Wrong e-mail address. Try again.';break;
				case 2:$msg='Wrong password. Try again.';break;
				case 3:$msg='This account is not yet activated, please click the link in the confirmation email you received from Woophy when you signed up. If you get this message after you activated and used your account it is possible that the account is disabled or suspended. For help concerning activation of the account please mail to <a href="mailto:'.SUPPORT_EMAIL_ADDRESS.'">'.SUPPORT_EMAIL_ADDRESS.'</a>.';break;
			}
			parent::throwError(self::ERRBASE+$code, $msg);
		}
	}
?>