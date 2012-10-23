<?php

$limit = 100;
?>
<fieldset>
<legend>Deleted accounts</legend>
<div style="padding:10px;">
<?php
$query = "SELECT SQL_CALC_FOUND_ROWS user_id,user_name,user_email,user_ip,deletion_date FROM users_deleted ORDER BY deletion_date DESC LIMIT 0,$limit";
$result = DB::query($query) or die(DB::error());
$result_total = DB::query('SELECT FOUND_ROWS();');
$total = DB::result($result_total, 0);
$num = DB::numRows($result);
if($num==0){
	print '<i>No deleted accounts.</i>';
}else{
	if($num == $limit){
		print '<p><i>Showing '.$num.' of '.$total.'</i></p><hr/>';
	}
	print '<table>';
	print '<tr><td><b>id</b></td><td><b>username</b></td><td><b>email</b></td><td><b>ip</b></td><td><b>deletion date</b></td></tr>';
	while ($row = DB::fetchAssoc($result)) {
		print '<tr><td>'.$row['user_id'].'</td><td>'.$row['user_name'].'</td><td>'.$row['user_email'].'</td><td>'.$row['user_ip'].'</td><td>'.$row['deletion_date'].'</td></tr>';
	}
	echo '</table>';
}

?>
</div>
</fieldset>