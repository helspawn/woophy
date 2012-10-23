<?php

if(isset($_POST["photo_id"])){

	$photo_id = (int)$_POST["photo_id"];
	$result = DB::query('SELECT user_id FROM photos WHERE photo_id='.$photo_id);
	if($result && DB::numRows($result)==1){
		$user_id = DB::result($result, 0);
		DB::query('INSERT INTO photos_trash SELECT * FROM photos WHERE photo_id='.$photo_id);
		if(DB::affectedRows() == 1){
			DB::query('DELETE FROM photos WHERE photo_id='.$photo_id);
			if(DB::affectedRows() == 1){
				$error = "Photo #$photo_id has been moved to the trash!";
				//so good so far, rename image files:
				$prefix = '_trsh_';
				$files_old = array(
					Utils::getPhotoPath($user_id, $photo_id, 'thumb'),
					Utils::getPhotoPath($user_id, $photo_id, 'medium'),
					Utils::getPhotoPath($user_id, $photo_id, 'large'),
					Utils::getPhotoPath($user_id, $photo_id, 'full'),
					Utils::getPhotoPath($user_id, $photo_id, 'original'));

				$files_new = array(
					str_replace($photo_id, $prefix.$photo_id, $files_old[0]),
					str_replace($photo_id, $prefix.$photo_id, $files_old[1]),
					str_replace($photo_id, $prefix.$photo_id, $files_old[2]),
					str_replace($photo_id, $prefix.$photo_id, $files_old[3]),
					str_replace($photo_id, $prefix.$photo_id, $files_old[4]));
				
				for($i=0; $i<count($files_old);$i++){
					if(!Utils::s3_rename($files_old[$i], $files_new[$i]))$error .= ('<br/>Rename failed:'.$files_old[$i]);
				}

				//update cash:
				DB::query('UPDATE users SET photo_count = (SELECT count(0) FROM photos WHERE user_id='.$user_id.') WHERE user_id='.$user_id);
				//delete keywords:
				DB::query('DELETE FROM photo_tag2photo WHERE photo_id='.$photo_id);
			}else {
				DB::query('DELETE FROM photos_trash WHERE photo_id='.$photo_id);
				$error= "Photo #$photo_id could not be moved to trash!";
			}
		}else $error = DB::error();
	}else $error = 'Photo not found!';
	DB::close();
}
?>

<script type="text/javascript">//<![CDATA[
	function PreviewThumb(){
		jQuery('#show_preview').click(function(){
			var $h = jQuery('#preview_holder');
			if($h.length){
				function showError(){
					$h.html('<div class="Error">Photo not found.</div>');
				}
				var id = jQuery('input#photo_id').val();
				if(id && id.length){
					if(!isNaN(parseInt(id))){
						jQuery.get('<?php echo ROOT_PATH?>services?method=woophy.photo.getUrl&photo_id='+id+'&size=medium', function(data) {		
							var url = jQuery('url', data).text();
							if(url.length){
								$h.html('<img src="'+url+'">');
							}else showError();
						});
						return;
					}
				}
				showError();
			}
		});	
	}
	jQuery(document).ready(function(){
		var pt = new PreviewThumb();
	});
//]]></script>
<form 
	onsubmit="return confirm('Are you sure you want to move this photo to the trash bin?');"
	id="trashForm" 
	name="trashForm" 
	method="post" 
	action="" >
<fieldset>
<legend>Move to trash</legend>
<div style="padding:10px;">
<p>This will move the photo to the trash bin. The images will not be deleted, only be renamed. Option to restore photo is not yet implemented.</p>
<?php
if(isset($error)){
	print '<p class="Error">'.$error.'</p><hr/>';
}
?>
<h1>Photo id:</h1>
<input type="text" name="photo_id" id="photo_id" value="" />
<input type="submit" name="submit_trash" value="Move to trash" />&nbsp;<input type="button" id="show_preview" name="show_preview" value="Preview photo" />
<br/><br/>
<div id="preview_holder" style="margin:0"></div>
</div>
</fieldset>
</form>