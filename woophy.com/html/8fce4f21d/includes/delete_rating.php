<?php

	require_once 'delete_rating_func.php';

	$num = 1000;
	$frm = 0;
	if(isset($_GET["frm"])){
		$frm = $_GET["frm"];
	}

	
	if(isset($_POST["submit_member"])){
		$memberId = $_POST["memberId"];
	}
	if(isset($_POST["submit_photo"])){
		$photoId = $_POST["photoId"];
	}
	if(isset($_POST["submit_username"])){
		$userName = trim($_POST["userName"]);
		$result = DB::query('SELECT user_id FROM users WHERE user_name = \''.DB::escape($userName).'\'');
		if(DB::numRows($result) == 1){
			$memberId = DB::result($result, 0);
		}else{
			$error = 'Member with nickname '.$userName.' doesn\'t exists!';
		}
	}

	//get vars passed via paging
	if(isset($_GET["memberId"])){
		$memberId = $_GET["memberId"];
	}
	if(isset($_GET["photoId"])){
		$photoId = $_GET["photoId"];
	}

	if(isset($_POST["ids"]) && is_array($_POST["ids"])){
		//stay on same page:
		if(isset($_POST["photoId"])){
			$photoId = $_POST["photoId"];
		}else{
			$memberId = $_POST["memberId"];
		}
		

		foreach($_POST["ids"] as $k => $v){
			$a = explode(",",$v);
			$pid = (int)$a[0];
			$rate = $a[1];
			$uid = (int)$a[2];
			
			deleteRating($pid,$uid,$rate);

			//TODO: error handling
			$error = "Ratings deleted!";
		}
	}
?>
<fieldset>
<legend>Delete rating</legend>
<div style="padding:10px;">
<?php
if(isset($error)){
	echo '<p class="Error">'.$error.'</p><hr/>';
}

if(!isset($photoId) && !isset($memberId)){
?>
		<form name="form_select" method="post" action="">
		<h1 style="margin-top:0px;">Photo Id:</h1>
		<input type="text" name="photoId" value=""/>&nbsp;&nbsp;<input name="submit_photo" type="submit" id="submit_photo" value="View ratings">
		<p><hr/></p>
		<h1 style="margin-top:0px;">Member nickname:</h1>
		<input type="text" name="userName" value=""/>&nbsp;&nbsp;<input name="submit_username" type="submit" id="submit_username" value="View ratings">
		<p><hr/></p>
		<h1 style="margin-top:0px;">Member Id:</h1>
		<input type="text" name="memberId" value=""/>&nbsp;&nbsp;<input name="submit_member" type="submit" id="submit_member" value="View ratings"></form>
<?php

}else{

	if(isset($photoId)){
		$sql = "SELECT SQL_CALC_FOUND_ROWS photo_id, user_id, rate, ip, date FROM rating WHERE photo_id = $photoId ORDER BY date DESC LIMIT $frm,$num;";//slow query!
		$title = 'ratings by photo id $photoId';
	}else{
		$sql = "SELECT SQL_CALC_FOUND_ROWS photo_id, user_id, rate, ip, date FROM rating WHERE user_id = $memberId ORDER BY date DESC LIMIT $frm,$num;";//slow query!
		$title = 'ratings by member id $memberId';
	}
	$result = DB::query($sql);
	$result2 = DB::query("SELECT FOUND_ROWS();");
	$total = DB::fetchRow($result2);
	//paging:
	print "<p>";

	if(isset($photoId)){
		$qs = '&photoId='.$photoId;
	}else{
		$qs = '&memberId='.$memberId;
	}
	
	$prev = max($frm-$num,0);
	print '<input type="button" onclick="document.location.href=\'?'.$qs.'&frm='.$prev.'\'" value="Prev" id="prev"';
	if($frm==0){
		print ' disabled="true"';
	}
	print '>&nbsp;';
	print '<input type="button" onclick="document.location.href=\'?'.$qs.'&frm='.($frm+$num).'\'" value="Next" id="next"';
	$to = $frm+$num;
	if($frm+$num>=$total[0]){
		$to = $total[0];
		print ' disabled="true"';
	}
	print '>';
	print '&nbsp;&nbsp;'.$frm.' - '.$to.' / '.$total[0];
	print '</p>';
	
	print '<p><hr/></p>';
	
	print '<form onsubmit="return confirm(\'Are you sure you want to delete these ratings?\');" name="form_delete" method="post" action="">';
	
	print '<input type="button" onclick="checkUncheckAll(\'form_delete\')" value="Check/Uncheck all">';
	print '&nbsp;&nbsp;<input type="submit" name="submit_photoIds" value="Delete checked"><p><hr/></p>';
	
	print '<table><tr><td><b>delete</b></td><td><b>photo id</b></td><td><b>member id</b></td><td><b>ip</b></td><td><b>rating</b></td><td><b>date</b></td></tr>';
	
	
	if($result && DB::numRows($result)>0){
		while($row = DB::fetchAssoc($result)){
			print '<tr>';
			print '<td><input class="noborder" type="checkbox" name="ids[]" value="'.$row["photo_id"].','.$row["rate"].','.$row["user_id"].'"></td>';
			print '<td><a href="'.ROOT_URL.'photo/'.$row["photo_id"].'" target="_blank">'.$row["photo_id"].'</a></td>';
			print '<td>'.$row["user_id"].'</td>';
			print '<td>'.$row["ip"].'</td>';
			print '<td>'.$row["rate"].'</td>';
			print '<td>'.$row["date"].'</td>';
			print '</tr>';
		}
	}else{
		print '<tr><td colspan=\"6\">No ratings found!</td></tr>';
	}
	print '</table>';
	print '<p><hr/></p>';
	
	if(isset($photoId)){
		print '<input type="hidden" name="photoId" value="'.$photoId.'"/>';
	}else{
		print '<input type="hidden" name="memberId" value="'.$memberId.'"/>';
	}
	print '<input type="button" onclick="checkUncheckAll(\'form_delete\')" value="Check/Uncheck all">';
	print '&nbsp;&nbsp;<input type="submit" name="submit_photoIds2" value="Delete checked">';
	print '</form>';

}
?>
</div>
</fieldset>