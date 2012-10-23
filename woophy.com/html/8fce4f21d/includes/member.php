<?php

if(isset($_POST['submit_nickname']) || isset($_POST["submit_rating"])){
	$user_name = trim($_POST['user_name']);
	$result = DB::query('SELECT * FROM users WHERE user_name = \''.DB::escape($user_name).'\'');
	if(DB::numRows($result) == 1){
		$member = DB::fetchAssoc($result);
	}else{
		$error = 'Member with nickname '.$user_name.' doesn\'t exists!';
	}
}

if(isset($_POST['submit_id']) || isset($_GET['user_id'])){
	$uid = 0;
	if(isset($_POST['uid']))$uid = $_POST['uid'];
	else if(isset($_GET['user_id']))$uid = $_GET['user_id'];
	$result = DB::query('SELECT * FROM users WHERE user_id = '.(int)$uid);
	if(DB::numRows($result) == 1){
		$member = DB::fetchAssoc($result);
	}else{
		$error = 'Member with ID '.$uid.' doesn\'t exists!';
	}
}

if(isset($_POST['submit_email'])){
	$email = trim($_POST['email']);
	$result = DB::query('SELECT * FROM users WHERE email = \''.DB::escape($email).'\'');
	if(DB::numRows($result) == 1){
		$member = DB::fetchAssoc($result);
	}else{
		$error = 'Member with email '.$email.' doesn\'t exists!';
	}
}
if(isset($_POST['submit_ip'])){
	$ip = $_POST['ip'];
	$result_ip = DB::query("SELECT rating.user_id, rating.ip, users.user_name FROM rating INNER JOIN users ON rating.user_id = users.user_id WHERE ip = '$ip' GROUP BY rating.user_id;");
	if(DB::numRows($result_ip) == 1){
		$uid = DB::result($result_ip,0);
		$result = DB::query("SELECT * FROM users WHERE user_id = $uid;");
		if(DB::numRows($result) == 1){
			$member = DB::fetchAssoc($result);
		}
	}else if(DB::numRows($result_ip) > 1){
		$error = "<b>More members with IP ".$ip." found</b><br/>";
		while($row = DB::fetchAssoc($result_ip)){
			$error .= "<br/>".$row["user_name"];
		}
	}else{
		$error = "Member with IP ".$ip." doesn't exists!";
	}
}
?>
<fieldset>
<legend>Look up Member</legend>
<div style="padding:10px;">
<?php
if(isset($error)){
	echo '<p class="Error">'.$error.'</p><hr/>';
}
if(isset($member)){
?>
<table cellspacing="0">
<tr><td><b>id:</b></td><td><?php echo $member["user_id"]?></td></tr>
<tr><td><b>nickname:</b></td><td><?php echo $member["user_name"]?></td></tr>
<tr><td><b>e-mail:</b></td><td><a href="mailto:<?php echo $member["email"]?>" target="_blank"><?php echo $member["email"]?></a></td></tr>
<tr><td><b>registered:</b></td><td><?php echo $member["registration_date"]?></td></tr>
<tr><td><b>last login:</b></td><td><?php echo $member["last_login_date"]?></td></tr>
<tr><td><b>last upload:</b></td><td><?php echo $member["last_upload_date"]?></td></tr>
<tr><td><b>last ip:</b></td><td><?php echo $member["last_ip"]?></td></tr>

<tr><td><b>active:</b></td><td><?php echo $member["active"]==1 ? "yes": "no"; ?></td></tr>
<tr><td><b>number of photos:</b></td><td><?php echo $member["photo_count"]?></td></tr>
</table>
<hr/>
<?php
		if(isset($_POST["submit_rating"])){
			$id = $member["user_id"];
			$max = 200;
			$result_rating = DB::query("SELECT rating.date,rating.photo_id,rating.rate,photos.user_id FROM rating INNER JOIN photos ON rating.photo_id = photos.photo_id WHERE rating.user_id = $id ORDER BY rate ASC LIMIT $max;");
			
			if($result_rating){
				print "<table cellspacing=\"0\">";
				print "<tr><td><b>photo ID</b></td><td><b>member ID</b></td><td><b>rating</b></td><td><b>date</b></td></tr>";
				
				while($row=DB::fetchAssoc($result_rating)){
					print "<tr><td><a href=\"http://www.woophy.com/photo/".$row["photo_id"]."\" target=\"_blank\">".$row["photo_id"]."</a></td><td>".$row["user_id"]."</td><td>".$row["rate"]."</td><td>".$row["date"]."</td></tr>"; 
				}
				print "</table>";
				if(DB::numRows($result_rating)>=$max){
					print "<p><b>Warning: output cancelled after $max records!</b></p>";
				}
			}
		}else{
?>
		<form name="form_rating" method="post" action="">
			<input name="submit_rating" type="submit" id="submit_rating" value="View rating by <?php echo $member["user_name"]?>">
			<input type="hidden" name="user_name" value="<?php echo $member["user_name"]?>"/>
		</form>
<?php
		}

}else{
?>
		<h1>Member</h1>
		<form name="form_select" method="post" action="">
		<table>
		<tr><td>username:</td><td><input type="text" name="user_name" value=""/>&nbsp;&nbsp;<input name="submit_nickname" type="submit" id="submit_nickname" value="Submit"></td></tr>
		<tr><td>id:</td><td><input type="text" name="uid" value=""/>&nbsp;&nbsp;<input name="submit_id" type="submit" id="submit_id" value="Submit"></td></tr>
		<tr><td>IP:</td><td><input type="text" name="ip" value=""/>&nbsp;&nbsp;<input name="submit_ip" type="submit" id="submit_ip" value="Submit"></td></tr>
		<tr><td>email:</td><td><input type="text" name="email" value=""/>&nbsp;&nbsp;<input name="submit_email" type="submit" id="submit_email" value="Submit"></td></tr>
		</table>
		</form>
<?php
}
?>
</div>
</fieldset>