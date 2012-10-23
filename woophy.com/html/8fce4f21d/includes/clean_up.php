<?php
set_time_limit(0);//make sure script keeps on running
ignore_user_abort(TRUE);

if(isset($_POST["submit_cleanup"])){
	$num = 0;
	function deleteDependentRows($result, $photoTable = true){
		global $num;
		if($result){
			if(DB::numRows($result)>0){
				while($row = DB::fetchAssoc($result)){
					$pid = (int)$row['photo_id'];
					DB::query('DELETE FROM photo2category WHERE photo_id='.$pid);
					$num += DB::affectedRows();
					DB::query('DELETE FROM favorite_photos WHERE photo_id='.$pid);
					$num += DB::affectedRows();
					DB::query('DELETE FROM photo_comments WHERE photo_id='.$pid);
					$num += DB::affectedRows();
					DB::query('DELETE FROM rating WHERE photo_id='.$pid);
					$num += DB::affectedRows();
					DB::query('DELETE FROM photo_tag2photo WHERE photo_id='.$pid);
					$num += DB::affectedRows();
					if($photoTable){
						DB::query('DELETE FROM photos WHERE photo_id='.$pid);
						$num += DB::affectedRows();
					}
				}
			}
		}
	}

	deleteDependentRows(DB::query('SELECT p.photo_id FROM photos p LEFT JOIN users u ON p.user_id = u.user_id WHERE u.user_id IS NULL'), true);

	deleteDependentRows(DB::query('SELECT DISTINCT photo_id FROM photo2category AS p1 WHERE NOT EXISTS (SELECT photo_id FROM photos AS p2 WHERE p1.photo_id=p2.photo_id)'), false);
	
	//extra check on favorites:
	$result = DB::query('SELECT DISTINCT photo_id FROM favorite_photos AS p1 WHERE NOT EXISTS (SELECT photo_id FROM photos AS p2 WHERE p1.photo_id=p2.photo_id)');
	if($result){
		$num += DB::affectedRows();
		while($row = DB::fetchAssoc($result)){
			DB::query('DELETE FROM favorite_photos WHERE photo_id='.(int)$row['photo_id']);
		}
	}

	$error =  "Records removed: ". $num;
}

?>
<fieldset>
<legend>Clean up database</legend>
<div style="padding:10px;">
<?php
	if(isset($error))echo '<p class="Error">'.$error.'</p><hr/>';
?>
<form name="form_cleanup" method="post" action="">
	<p>Check and remove redundant records in the database.<br/><strong>This may take a couple of seconds. Use this when the server is NOT relatively busy!</strong></p>
	<input type="submit" name="submit_cleanup" value="Clean up" />
</form>
</div>
</fieldset>