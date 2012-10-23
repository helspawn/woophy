<?php

$user_name = isset($_POST['user_name']) ? $_POST['user_name'] : (isset($_GET['user_name'])?$_GET['user_name']:'');

if(strlen($user_name)>0 && !isset($_POST['user_id'])){
	$result = DB::query('SELECT user_id FROM users WHERE user_name = \''.DB::escape($user_name).'\'');
	if($result && DB::numRows($result)==1){
		$uid = (int)DB::result($result, 0, 0);
		$camera_total = 0;
		//recalculate camera:
		$result = DB::query('SELECT camera, COUNT(*) AS num FROM photos WHERE user_id = '.$uid.' GROUP BY camera');
		//TODO: is php approach faster then mysql: ORDER BY num DESC LIMIT 1???
		while($row = DB::fetchAssoc($result)){
			if($row['num']>MIN_NUM_PHOTOS_AWARD){
				if($camera_total<$row['camera'])$camera_total = $row['camera'];
			}
		}
		DB::query('UPDATE users SET camera = '.$camera_total .' WHERE user_id = '.$uid);
		$error = 'Added camera: ';
		switch($camera_total){
			case 0:$error.='No camera';break;
			case 1:$error.='Bronze camera';break;
			case 2:$error.='Silver camera';break;
			case 3:$error.='Gold camera';break;
		}
	}else $error = 'No member with nickname \''.$_POST['user_name'].'\' found!';
}
?>
<fieldset>
<legend>Reset member camera</legend>
<div style="padding:10px;">
<form name="form_reset" method="post" action="">
<?php
if(isset($error))echo '<p class="Error">'.$error.'</p><hr/>';
?>

	<h1>Member nickname:</h1>
	<input type="text" name="user_name" value="<?php echo htmlspecialchars($user_name);?>"/>&nbsp;<input type="submit" name="submit_select" value="Continue"/>

</form>
</div>
</fieldset>
