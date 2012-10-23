<?php
	$category_id = 1;//default: TODO: retreive dynamically
	if(isset($param[2])){
		$p = Utils::stripQueryString($param[2]);
		if(is_numeric($p))$category_id = (int)$p;
	}
	$entries_offset = 0;//default
	if(isset($_GET['offset']))$entries_offset = $_GET['offset'];
	$entries_limit = 5;
	include CLASS_PATH.'Contest.class.php';
	$contest = new Contest();
	$contest->buffer = false;
	$xml_cat = $contest->getCategoryById($category_id);
	echo '<div class="Section">';
	if($error = $xml_cat->err){
		echo $error['msg'];
	}else{
		if($xml_cat->name){
			echo '<div class="MainHeader DottedBottom"><h1>Submissions - '.$xml_cat->name.'</h1>';
		
			$xml_entries = $contest->getEntriesByCategoryId($category_id, $entries_offset, $entries_limit);
			
			if($error = $xml_entries->err){
				echo '</div><div class="Error">'.$error['msg'].'</div>';
			}else{
				$entries = $xml_entries->entry;
				if(count($entries)==0)echo '</div><div class="formarea">No submissions yet.</div>';
				else{
					$entries_total = $xml_entries['total_entries'];
					
					echo ($entries_offset+1).'&nbsp;-&nbsp;'.(min($entries_offset+$entries_limit,$entries_total)).'&nbsp;of&nbsp;'.$entries_total.'&nbsp;total</div>';
					$nav = Utils::getPagingNav($entries_offset, $entries_total, $entries_limit);
					echo $nav;

					//entries:
					$num = count($entries);
					$i = 0;
					foreach($entries as $entry){
						$i++;
						if($i < $num)echo '<div class="ContestEntry DottedBottom">';
						else echo '<div class="ContestEntry">';
						echo '<div class="Header">'.$entry->city_name.', '.$entry->country_name.' by <a href="'.ROOT_PATH.'member/'.urlencode($entry->user_name).'">'.$entry->user_name.'</a></div>';
						echo '<a href="'.ROOT_PATH.'photo/'.$entry->photo_id.'"><img src="'.Utils::getPhotoUrl($entry->user_id,$entry->photo_id,'medium').'"/></a>';
						echo '<div class="Meta">Submitted by '.$entry->submitted_by.' on '.Utils::formatDateShort($entry->date).'</div>';
						if(strlen($entry->remark)>0){
							echo '<p>'.$entry->remark.'</p>';
						}
						echo '</div>'.PHP_EOL;
					}
					echo $nav;
				}
			}
		}else echo 'Contest category not found!';
	}
	echo '</div>';
?>

