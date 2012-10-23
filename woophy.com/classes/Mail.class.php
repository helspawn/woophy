<?php
class Mail{ 
	
	private $_sendto;
	private $_subject;
	private $_cc;
	private $_bcc;
	private $_body;
	private $_xheaders;
	
	public function __construct(){
		$this->_sendto = array();
		$this->_subject = '';
		$this->_cc = array();
		$this->_bcc = array();
		$this->_body = '';
		$this->_xheaders = array();
		$this->From(EMAIL_SENDER, INFO_EMAIL_ADDRESS);//default sender
	}
	public function Subject($subject){
		$this->_subject = strtr($subject, "\r\n" , "  ");
	} 
	public function From($name, $email){
		if(is_string($name) && is_string($email)) {
			$this->_xheaders['From'] = 
			$this->_xheaders['Sender'] = 
			$this->_xheaders['Return-Path'] = 
			$this->_xheaders['Reply-To'] = $name.' <'.$email.'>';
		}
	}
	public function To($to){
		if(is_array($to)) $this->_sendto = $to;
		else $this->_sendto = preg_split('/[,;]/', $to);
	}
	public function Cc($cc){
		if(is_array($cc))$this->_cc = $cc;
		else $this->_cc = preg_split('/[,;]/', $cc);
	}
	public function Bcc($bcc){
		if(is_array($bcc)) $this->_bcc = $bcc;
		else $this->_bcc = preg_split('/[,;]/', $bcc);
	}
	public function XHeader($name, $value){
		if(!isset($this->_xheaders[$name])){//do not override
			if(is_string($name) && is_string($value)) $this->_xheaders[$name] = $value;
		}
	}
	public function Body($body, $footer=true){//appends default woophy footer
		$this->_body = $body;
		if($footer) $this->_body.=PHP_EOL.PHP_EOL.'____________________________________________'.PHP_EOL.PHP_EOL.'This email was sent to you through the email system of www.woophy.com.'.PHP_EOL.'The email form used on our website does not disclose your email address and we will never share your address with anybody.'.PHP_EOL.PHP_EOL.'For suggestions and support please contact us at '.SUPPORT_EMAIL_ADDRESS;
	}
	public function Validate($email){
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}
	public function Send(){
		if(count($this->_sendto) > 0){

			$this->_sendto = array_map('trim', $this->_sendto);
			$this->_sendto = array_filter($this->_sendto, array($this, 'Validate'));
			
			if(count($this->_sendto) > 0){

				$headers = '';
				if(count($this->_cc) > 0) $this->_xheaders['CC'] = implode( ", ", $this->_cc);
				if(count($this->_bcc) > 0) $this->_xheaders['BCC'] = implode( ", ", $this->_bcc);

				$this->XHeader('Mime-Version', '1.0');
				$this->XHeader('Content-Type', 'text/plain; charset=UTF-8');
				$this->XHeader('Content-Transfer-Encoding', '8bit');
				$this->XHeader('X-Mailer', 'PHP/'.phpversion());

				reset($this->_xheaders);
				while(list($hdr,$value) = each($this->_xheaders)){
					$headers .= "$hdr: $value\r\n";
				}
				
				$res = @mb_send_mail(implode(', ', $this->_sendto), $this->_subject, $this->_body, $headers);
				//$res = @mb_send_mail(implode(', ', $this->_sendto), $this->_subject, $this->_body, $headers, '-f'.SUPPORT_EMAIL_ADDRESS);//-f option is set in php.ini
				
				//begin: counter
				$f = ABSPATH.'html'.DIRECTORY_SEPARATOR.'mail_counter.txt';
				if(is_writable($f)) {
					if($handle = fopen($f, 'r')){
						$dat = (int)fread($handle, filesize($f));
						fclose($handle);
						if($handle = fopen($f, 'w')){
							fwrite($handle, $dat+1);
							fclose($handle);
						}
					}
				}
				//end: counter

				return $res;
			}
		}
	}
}
?>