<?php
set_time_limit(0);//make sure script keeps on running
ignore_user_abort(TRUE);

?>
<fieldset>
<legend>Reset city photo count</legend>
<div style="padding:10px;">
<?php

if(isset($_POST['submit_reset'])){
	echo '<p><strong>';
	if($result = DB::query('SELECT UNI FROM cities WHERE photo_count>0')){
		$unis = array();
		while($row = DB::fetchAssoc($result))$unis[(int)$row['UNI']] = 1;
		if($result = DB::query('SELECT DISTINCT city_id FROM photos')){
			$count = 0;
			$updated = count($unis);
			while($row = DB::fetchAssoc($result)){
				$city_id = (int)$row['city_id'];
				if(isset($unis[$city_id]))unset($unis[$city_id]);
				DB::query('UPDATE LOW_PRIORITY cities SET photo_count = (SELECT COUNT(0) FROM photos WHERE city_id ='.$city_id.') WHERE UNI = '.$city_id);
				$num = DB::affectedRows();
				if($num>0)$updated += $num;
				$count++;
				if(fmod($count, 100)==0)sleep(1);//use batches to prevent server jam
			}
			foreach($unis as $uni=>$val){
				DB::query('UPDATE LOW_PRIORITY cities SET photo_count = 0 WHERE UNI = '.$uni);
			}
			echo 'Done!<br/><br/>';
			echo '<br/>Total number of updates: '.$updated;
			echo '<br/>Number of cities disappeared from the map after this update: '.count($unis);
		}else  echo '<br/>Error: '.DB::error();
	}else echo '<br/>Error: '.DB::error();
	echo '</strong></p>';
}else{//display form:
?>
<p>The number of photos of each city displayed on the map is being cached for easy lookup. If this number is out of sync with the actual number of photos, use this form to reset the cache.<br/><br/>
<strong>Be aware! All cities with one or more photos are going to be updated. This may take several minutes. Use this when the server is NOT relatively busy!</strong>
<form name="form_reset" method="post" action="">
<input type="submit" onclick="this.value='Updating...'" name="submit_reset" value="Reset photo count" />
</form>
</p>
<?php
}
?>
</div>
</fieldset>