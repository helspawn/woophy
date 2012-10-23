<?php
	//not used anymore, advertisements are now delivered by OpenX
?>
<fieldset>
<legend>Add Advertisement</legend>
<div style="padding:10px;">
<form name="form_add_ad" method="post" action="">
	<?php
		if(isset($_POST['size_id'],$_POST['section_id'],$_POST['ad_text'],$_POST['ad_weight'])){
			DB::query('INSERT INTO advertising_ads (ad_text,ad_weight,size_id,section_id) VALUES (\''.$_POST['ad_text'].'\',\''.$_POST['ad_weight'].'\',\''.$_POST['size_id'].'\',\''.$_POST['section_id'].'\');');
			if(DB::affectedRows() == 1){
				echo 'Advertisement added!';
				include_once CLASS_PATH.'Advertising.class.php';
				$adv = new Advertising();
				if($adv->deleteFromCache('Advertising::getAdsBySizeId')){
					echo ' Caches updated!';
				}else{
					echo ' Could not update caches!';
				}//KLUDGE!!!
			}else echo 'Could not add advertisement: '. DB::error();
		}else if(isset($_POST['size_id'],$_POST['section_id'])){
			echo '<h1>Enter ad text</h1>';
			echo '<input type="hidden" name="section_id" value="'.$_POST['section_id'].'" />';
			echo '<input type="hidden" name="size_id" value="'.$_POST['size_id'].'" />';
			echo 'HTML Code:';
			echo '<br/><textarea name="ad_text" rows="10" cols="60"></textarea>';
			echo '<br/>Weight: <select name="ad_weight">';
			$i=0;
			while(++$i<=5){
				echo '<option value="'.$i.'">'.$i.'</option>';
			}
			echo '</select> (default 1)';
			echo '<br/><br/><input type="submit" name="submit" value="Save"/>';
		}else if(isset($_POST['section_id'])){
			echo '<h1>Select ad size</h1>';
			echo '<input type="hidden" name="section_id" value="'.$_POST['section_id'].'" />';
			if((int)$_POST['section_id']==0){//all sections
				$qry ='SELECT advertising_sizes.size_id, size_width, size_height
				FROM advertising_sizes 
				INNER JOIN advertising_size2section ON advertising_sizes.size_id = advertising_size2section.size_id GROUP BY advertising_size2section.size_id';
			}else{
				$qry ='SELECT advertising_sizes.size_id, size_width, size_height
				FROM advertising_sizes 
				INNER JOIN advertising_size2section ON advertising_sizes.size_id = advertising_size2section.size_id 
				WHERE section_id = \''.$_POST['section_id'].'\';';
			}
			$result = DB::query($qry);
			if($result){
				if(DB::numRows($result)==0){
					echo 'No sizes available for this section!';
				}else{
					echo 'Size: <select name="size_id">';
					while($row = DB::fetchAssoc($result)){
						echo '<option value="'.$row['size_id'].'">';
						echo $row['size_width'] .' x '.$row['size_height'];
						echo '</option>';
					}
					echo '</select>';
					echo '&nbsp;<input type="submit" name="submit" value="Select"/>';
				}
			}else echo 'Error';
		}else{
			echo '<h1>Select site section</h1>';
			$result = DB::query('SELECT section_id,section_name FROM advertising_sections ORDER BY section_name;');
			if($result){
				echo 'Section: <select name="section_id">';
				echo '<option value="0">All sections</option>';
				while($row = DB::fetchAssoc($result)){
					echo '<option value="'.$row['section_id'].'">';
					echo $row['section_name'];
					echo '</option>';;
				
				}
				echo '</select>';
				echo '&nbsp;<input type="submit" name="submit" value="Select"/>';
			}else echo 'Error';
		}
	?>
</form>
</div>
</fieldset>