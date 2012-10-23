<?php
set_time_limit(0);//make sure script keeps on running
ignore_user_abort(TRUE);

?>
<fieldset>
<legend>Reset city cache</legend>
<div style="padding:10px;">
<?php

if(isset($_POST['submit_reset'])){

	include_once(CLASS_PATH.'ClassFactory.class.php');
	include_once(CLASS_PATH.'Map.class.php');
	
	DB::connect();
	$map = new Map();
	$map->getCitiesCache(1, true);
	echo '<p class="Error">Caches have been reset!</p>';
	DB::close();
}
?>
<p>To load the cities on the map quickly, Woophy uses cached presets for 2 map sizes. These presets are recalculated every 3 days. If the sets are out of synch with the real data, you can use this function to reset the cache directly.<br/><br/>
<form name="form_reset" method="post" action="">
<input type="submit" onclick="this.value='Updating...'" name="submit_reset" value="Reset city cache" />
</form>
</p>
</div>
</fieldset>