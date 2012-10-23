<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}
	
	if(isset($_POST['submit_delaccount'])){
		include_once CLASS_PATH.'Account.class.php';
		$account = new Account();
		$xml = $account->remove();
		if($error = $xml->err){
			$msg_delaccount_err = $error['msg'];
		}else{
			//TODO: confirm delete!
			$msg_delaccount_ok = true;


		}
	}
?>
<div class="Section">
<div class="MainHeader DottedBottom"><h1>Delete account</h1></div>
<?php
if(isset($msg_delaccount_ok)){
	echo '<div class="warning">Your Woophy account has been deleted!</div>';
}else{
		
	if(isset($msg_delaccount_err)) echo '<p class="Error">'.$msg_delaccount_err.'</p>';
?>
<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" name="frmdelaccount" method="post" class="FormArea clearfix" onsubmit="return confirm('Are you really sure you want to delete your account and all your photos??');">
	<p class="floatleft">Are you sure you want to delete your account?<br/>This is permanent. All your photos will be deleted!</p>
	<input id="submit_delaccount" name="submit_delaccount" style="float:right;margin:0" class="submit RedButton" type="submit" value="Delete my account" />
</form>
</div>
<?php
}
?>