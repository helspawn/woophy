<?php

if(isset($_POST['submit_delete'])){
	$pid = (int)$_POST['pid'];
	if($pid>0){	

		if($result = DB::query('SELECT user_id FROM blog_posts WHERE post_id = '.$pid)){
			if(DB::numRows($result)>0){		
				$uid = DB::result($result, 0);
				DB::query('DELETE FROM blog_posts WHERE post_id = '.$pid);
				DB::query('DELETE FROM blog_comments WHERE post_id = '.$pid);
				DB::query('DELETE FROM blog_newsletters WHERE post_id = '.$pid);

				DB::query('UPDATE users set blog_post_count = (SELECT count(0) FROM blog_posts WHERE user_id ='.(int)$uid.' AND post_status = \'published\' AND post_publication_date <= NOW()) WHERE user_id='.(int)$uid);
				include_once CLASS_PATH.'ClassFactory.class.php';
				include_once CLASS_PATH.'Status.class.php';
				$status = new Status();
				$status->updateLastBlogPost();
				$error = 'Blogpost has been deleted!';
			}else $error = 'No blogpost with id <b>'.$pid.'</b> found!';
		}else $error = 'ERROR: '.DB::error();	
	}
}
?>
<fieldset>
<legend>Delete blogpost</legend>
<div style="padding:10px;">
<?php
if(isset($error)){
	echo '<p class="Error">'.$error.'</p><hr/>';
}
?>
<form name="form_select" method="post" onsubmit="return confirm('Are you sure you want to delete blogpost #'+this.pid.value+' and all the comments?')" action="">
<h1>Blogpost id:</h1>
<input type="text" name="pid" value=""/>&nbsp;&nbsp;<input name="submit_delete" type="submit" id="submit_delete" value="Delete"></form>
</div>
</fieldset>