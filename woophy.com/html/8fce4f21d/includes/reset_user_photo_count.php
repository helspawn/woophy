<?php
set_time_limit(0);//make sure script keeps on running
ignore_user_abort(TRUE);

?>
<fieldset>
<legend>Reset user photo count</legend>
<div style="padding:10px;">
<?php

if(isset($_POST['submit_reset'])){
	echo '<p class="Error">';
	if($result = DB::query('UPDATE LOW_PRIORITY users SET photo_count = (SELECT COUNT(0) FROM photos WHERE users.user_id = photos.user_id)'))echo '<br/>Photo count has been reset. Number of updates: '. DB::affectedRows();
	else echo '<br/>Error: '.DB::error();
	echo '</p>';
}else{//display form:
?>
<p>The number of photos of each user is being cached for easy lookup. If this number is out of sync with the actual number of photos, use this form to reset the cache.<br/><br/>
<strong>Be aware! All user entries are going to be updated. The more users, the longer it takes. Use this when the server is NOT relatively busy!</strong>
<form name="form_reset" method="post" action="">
<input type="submit" onclick="this.value='Updating...'" name="submit_reset" value="Reset photo count" />
</form>
</p>
<?php
}
?>
</div>
</fieldset>