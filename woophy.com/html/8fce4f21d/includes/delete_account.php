<?php

require_once CLASS_PATH.'ClassFactory.class.php';

if(isset($_POST['submit_delete']) || isset($_POST['submit_delete_all'])){

	$uid = 0;
	
	if(isset($_POST['user_name']) && strlen($_POST['user_name'])>0 ){
		$result = DB::query('SELECT user_id FROM users WHERE user_name = \''.DB::escape($_POST['user_name']).'\'');
		if($result && DB::numRows($result)==1){
			$uid = (int)DB::result($result, 0, 0);	
		}else $error = 'No member with nickname \''.$_POST['user_name'].'\' found!';	
	}else if(isset($_POST['user_id']) && strlen($_POST['user_id'])>0 ){
		$result = DB::query('SELECT user_id FROM users WHERE user_id = \''.DB::escape((int)$_POST['user_id']).'\'');
		if($result && DB::numRows($result)==1){
			$uid = (int)DB::result($result, 0, 0);	
		}else $error = 'No member with id \''.$_POST['user_id'].'\' found!';
	}else $error = 'Fill in all the required fields.';	
	
	if($uid>0){
		DB::query('DELETE FROM favorite_photos WHERE user_id = '.$uid);
		DB::query('DELETE FROM favorite_users WHERE user_id = '.$uid);
		DB::query('DELETE FROM favorite_users WHERE favorite_user_id = '.$uid);
		DB::query('DELETE FROM awards WHERE user_id = '.$uid);
		DB::query('DELETE FROM photo_folders WHERE user_id = '.$uid);
		DB::query('DELETE FROM ambassadors WHERE user_id = '.$uid);
		DB::query('DELETE FROM users WHERE user_id = '.$uid);
		DB::query('DELETE FROM blog_posts WHERE user_id = '.$uid);
		
		$result = DB::query('SELECT city_id, photo_id FROM photos WHERE user_id = '.$uid);
		if($result){
			
			include_once CLASS_PATH.'User.class.php';
			include_once CLASS_PATH.'Status.class.php';
			include_once CLASS_PATH.'City.class.php';
			include_once CLASS_PATH.'Blog.class.php';
			
			function removePhoto($pid){
				global $uid;
				if($uid>0){
					DB::query('DELETE FROM photos WHERE photo_id='.(int)$pid.' AND user_id='.(int)$uid.';');
					if(DB::affectedRows()>0){
						DB::query('DELETE FROM rating WHERE photo_id='.$pid);
						DB::query('DELETE FROM favorite_photos WHERE photo_id='.$pid);
						DB::query('DELETE FROM photo_comments WHERE photo_id='.$pid);
						DB::query('DELETE FROM photo2category WHERE photo_id='.$pid);
						DB::query('DELETE FROM photo_tag2photo WHERE photo_id='.$pid);
						DB::query('DELETE FROM editors_picks WHERE photo_id='.$pid);
					}

					//@unlink(Utils::getPhotoPath($uid,$pid,'s'));
					//@unlink(Utils::getPhotoPath($uid,$pid,'m'));
					//@unlink(Utils::getPhotoPath($uid,$pid,'l'));
					
					Utils::s3_delete(Utils::getPhotoPath($uid,$pid,'thumb'));
					Utils::s3_delete(Utils::getPhotoPath($uid,$pid,'medium'));
					Utils::s3_delete(Utils::getPhotoPath($uid,$pid,'large'));
					Utils::s3_delete(Utils::getPhotoPath($uid,$pid,'full'));
					Utils::s3_delete(Utils::getPhotoPath($uid,$pid,'original'));
				}
			}
			$city_ids = array();
			while ($row = DB::fetchAssoc($result)){	
				removePhoto($row['photo_id']);
				$city_ids[$row['city_id']]=1;
			}
			$city = new City();
			foreach($city_ids as $k=>$v){
				$city->updatePhotoCount($k);
			}
			$status = new Status();
			$status->updateNumberOfPhotos();
			$status->updateNumberOfCities();
			$status->updateNumberOfUsers();

			$blog = new Blog();
			$blog->deleteFromCache($blog->getCacheKey(Blog::CATEGORY_ID_USER));

			if(isset($_POST['submit_delete_all'])){
				DB::query('DELETE FROM blog_comments WHERE user_id = '.$uid);
				DB::query('DELETE FROM photo_comments WHERE poster_id= '.$uid);
				DB::query('DELETE FROM minibbtable_posts WHERE poster_id= '.$uid);
				DB::query('DELETE FROM minibbtable_topics WHERE topic_poster= '.$uid);
				include_once 'delete_rating_func.php';
				$result = DB::query('SELECT photo_id, rate FROM rating WHERE user_id = '.$uid);
				if($result){
					while ($row = DB::fetchAssoc($result)){
						deleteRating($row['photo_id'],$uid,$row['rate']);
					}
				}
			}else DB::query('UPDATE photo_comments SET poster_id = NULL WHERE poster_id = '.$uid);

			$error = 'Member has been deleted!';
		}
	}
}

if(isset($error)){
	echo '<div style="padding:10px;"><p class="Error">'.$error.'</p></div>';
}
?>
<fieldset>
<legend>Delete account by member nickname</legend>
<div style="padding:10px;">
<p>&quot;Delete All&quot; will delete an entire account including comments and rating.</p> 
<form name="form_delete_account" method="post" onsubmit="if(this.user_name.value.length)return confirm('Are you sure you want to delete '+this.user_name.value+'\'s account?\n\n(Do not close or refresh the browser window!)');else{alert('Fill in all the required fields.');return false;}" action="">

	<h1>Member nickname:</h1>
	<input type="text" name="user_name" value="<?php echo isset($_GET['user_name'])?$_GET['user_name']:''?>"/>&nbsp;<input type="submit" name="submit_delete" value="Delete"/>&nbsp;<input type="submit" name="submit_delete_all" value="Delete All"/>
</form>
</div>
</fieldset>

<fieldset>
<legend>Delete account by member id</legend>
<div style="padding:10px;">
<form name="form_delete_account" method="post" onsubmit="if(this.user_id.value.length)return confirm('Are you sure you want to delete user\'s #'+this.user_id.value+' account?\n\n(Do not close or refresh the browser window!)');else{alert('Fill in all the required fields.');return false;}" action="">
	<h1>Member id:</h1>
	<input type="text" name="user_id" value="<?php echo isset($_GET['user_id'])?$_GET['user_id']:''?>"/>&nbsp;<input type="submit" name="submit_delete" value="Delete"/>&nbsp;<input type="submit" name="submit_delete_all" value="Delete All"/>
</form>
</div>
</fieldset>