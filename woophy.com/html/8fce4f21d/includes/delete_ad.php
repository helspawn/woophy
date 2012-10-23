<?php
	//not used anymore, advertisements are now delivered by OpenX
?>
<fieldset>
<legend>Delete Advertisement</legend>
<div style="padding:10px;">
<form name="form_delete_ad" method="post" action="">
	<?php
		if(isset($_POST['ad_ids']) && is_array($_POST['ad_ids']) && count($_POST['ad_ids'])>0){
			DB::query('DELETE FROM advertising_ads WHERE ad_id IN ('.implode(',', $_POST['ad_ids']).');');
			if(DB::affectedRows() > 0){
				echo 'Advertisement deleted!';
				include_once CLASS_PATH.'Advertising.class.php';
				$adv = new Advertising();
				if($adv->deleteFromCache('Advertising::getAdsBySizeId')){//KLUDGE!!!
					echo ' Caches updated!';
				}else{
					echo ' Could not update caches!';
				}
			}else echo 'Could not delete advertisement: '. DB::error();
		}else if(isset($_POST['section_id'])){
			$qry ='SELECT ad_id, ad_text, section_id 
			FROM advertising_ads 
			WHERE section_id = \''.$_POST['section_id'].'\';';
			$result = DB::query($qry);
			if($result){
				if(DB::numRows($result)==0){
					echo 'No ads available for this section!';
				}else{			
					echo '<table>';
					while($row = DB::fetchAssoc($result)){
						echo '<tr><td><input type="checkbox" name="ad_ids[]" value="'.$row['ad_id'].'" /></td>';
						echo '<td>'.$row['ad_text'].'</td></tr>';
					}
					echo '<tr><td>&nbsp;</td><td><input type="submit" name="submit" value="Delete selected"/></td></tr>';
					echo '</table>';
				}
			}else echo 'Error';
		}else{
			$result = DB::query('SELECT section_id,section_name FROM advertising_sections ORDER BY section_name;');
			if($result){
				echo 'Section: <select name="section_id">';
				echo '<option value="0">All sections</option>';
				while($row = DB::fetchAssoc($result)){
					echo '<option value="'.$row['section_id'].'">';
					echo $row['section_name'];
					echo '</option>';
				
				}
				echo '</select>';
				echo '&nbsp;<input type="submit" name="submit" value="Select"/>';
			}else echo 'Error';
		}
	?>
</form>
</div>
</fieldset>