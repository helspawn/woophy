<?php
if(isset($_POST['submit_export'])){

	require_once '../../../includes/config.php';
	require_once CLASS_PATH.'DB.class.php';
	require_once CLASS_PATH.'Utils.class.php';
	
	$select = DB::query('SELECT email,user_name FROM users WHERE newsletter=1 AND email REGEXP \'^[a-zA-Z0-9][a-zA-Z0-9.-]*[a-zA-Z0-9.-]@[a-zA-Z0-9][a-zA-Z0-9._-]*[a-zA-Z0-9].[a-zA-Z]{2,4}$\'');

	if($select){
		$data =  '';
		while($row = DB::fetchAssoc($select)){
            $data .= '"' . str_replace( '"' , '""' , $row['email']) . '"';
			$data .= ",";
			$data .= '"' . str_replace( '"' , '""' , $row['user_name']) . '"';
			$data .= "\n";
		}
	}
	
	header('Content-type: application/csv');
	header('Content-Disposition: attachment; filename=woophy_emails_'.date('Y-m-d').'.csv');
	header('Pragma: no-cache');
	header('Expires: 0');
	print $data;
	exit();
}
?>
<form 
	id="exportForm" 
	name="exportForm" 
	method="post" 
	action="<?php echo dirname($_SERVER['PHP_SELF']).'/includes/export_newsletter_emails.php'?>">
<fieldset>
<legend>Export email addresses for newsletter mailings</legend>
<div style="padding:10px;">
<p>csv file with valid email addresses and usernames for newsletter mailings.</p>
<input type="submit" name="submit_export" value="Download" />
</div>
</form>