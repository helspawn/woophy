<?php
	set_time_limit(0);//make sure script keeps on running
	ignore_user_abort(TRUE);
	
	$mailMax = 100;//reload script every $mailMax/$mailSize time
	$mailSize = 10;//num of mails per batch
	$mailDelay = 5;//batch every 5 seconds

?>
<fieldset>
<legend>Status Sending Newsletter</legend>
<div style="padding:10px;">
<?php
if(isset($_SESSION['sending_newsletter']) && $_SESSION['sending_newsletter'] == true){
	if(isset($_SESSION['last_user_id'])){
		
		$last_user_id = $_SESSION['last_user_id'];
	
		$query = "SELECT email, user_name, user_id FROM users WHERE user_id > $last_user_id AND newsletter=1 AND active=1 ORDER BY user_id ASC LIMIT 0, $mailMax;";
		$result = DB::query($query) or die(DB::error());
		$numRows = DB::numRows($result);
		
		$lognews = 'log_newsletter.txt';

		$batches = array();
		$z = 0;
		$i = 0;
		$id =0;
		while($row = DB::fetchAssoc($result)){
			$email = $row['email'];
			$id = $row['user_id'];
			if(eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$", $email)) {	
				$batches[$z][] = array('e'=>$email,'n'=>$row['user_name'],'i'=>$id);
				if (++$i == $mailSize) {
					$i = 0;
					$z++;
				}
			}
		}
		$_SESSION['last_user_id'] = $id;
		DB::close();
		$bodynews = 'body_newsletter.txt';
		$handle = fopen($bodynews, "r");
		$message = fread($handle, filesize($bodynews));
		fclose($handle);

		foreach ($batches as $batch) {
			foreach ($batch as $recepient) {
				$to = $recepient['e'];
				$name = $recepient['n'];
				$lastId = $recepient['i'];
				$subject = 'Woophy newsletter';
				$from = NOREPLY_EMAIL_ADDRESS;
				$headers = "From: Woophy <$from>\r\n";
				$headers .= "Sender: Woophy <$from>\r\n";
				$headers .= "Return-Path: Woophy <$from>\r\n";
				$headers .= "Reply-To: Woophy <$from>\r\n";
				$headers .= "X-Mailer: PHP v".phpversion()."\r\n";
				$headers .= "MIME-Version: 1.0\r\n";
				$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";

				@mb_send_mail($to, $subject, $message, $headers);

			}
			if(isset($lastId)){
				echo 'last user id: '.$lastId.'<br/>';
			}
			if(is_writable($lognews)) {
				if ($handle = fopen($lognews, "w")) {
					fwrite($handle, $lastId);//log last user id per batch
					fclose($handle);
				}
			}
			sleep($mailDelay);//pause script between batches
		}
		if($numRows < $mailMax){
			echo '<h1>Done! Newsletter has been sent to all subscribers.</h1>';
			$_SESSION = array();
			@session_write_close();
			echo '</div></div></fieldset><body></html>';
			exit; //stop script
		}else{
			@session_write_close();
			echo '<h1>Sending.. Do not refresh the page or close the browser window!</h1>';
		}
	}else{
		$_SESSION['last_user_id'] = 0;
		//$_SESSION["last_user_id"] = 3337;
		@session_write_close();
	}
	echo '<script>document.location.href=\'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/send_news\';</script>';

}else{
	echo 'Session expired! Contact the administrator.<p><a href="log_newsletter.txt" target="_blank">log_newsletter.txt</a></p>';
}
?>
</div>
</fieldset>