<?php
if(isset($_POST["submit_unsubscribe"])){
	$e = $_POST["email"];
	$e = preg_replace ("/\s/", "", $e);
	$emails = preg_split ("/[;,]+/", $e);

	
	$error = '';
	include_once('../../includes/config.php');
	require_once(CLASS_PATH.'DB.class.php');
	
	DB::connect();

	$error = '';
	for($i=0;$i<count($emails);$i++){
		$email = $emails[$i];
		$result = DB::query('UPDATE users SET newsletter = 0 WHERE email = \''.DB::escape($email).'\';');
		if($result && DB::affectedRows() == 1){
			$error .= "'$email' unsubscribed<br/>";
		}else{
			$error .= "'$email' already unsubscribed!<br/>";
		}
	}
	DB::close();
}

?>

<form 
	id="unsubscribeForm" 
	name="unsubscribeForm" 
	method="post" 
	action="" >
<fieldset>
<legend>Unsubscribe Woophy member by email address:</legend>
<div style="padding:10px;">
<p><i>Note:</i> Separate multiple email addresses by a comma (,) or semicolon (;)</p>
<p>Email address(es):</p>
<p><textarea style="margin-right:5px;" rows="5" cols="60" name="email"></textarea></p>
<p><input type="submit" value="Submit" name="submit_unsubscribe"/></p>
<?php
	if(isset($error) && strlen($error)>0){
		echo '<p class="Error">'.$error.'</p>';
	}
?>
</div>
</fieldset>
</form>
