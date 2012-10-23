<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}	
	
	$contest = ClassFactory::create('Contest');
	$contest->buffer = false;
	$access = ClassFactory::create('Access');

	$user_ids = explode(',', CONTEST_JURY);
	$user_id = $access->getUserId();
	$user_name = $access->getUserName();

	$html_results = '<div class="Section">';
	$html_results .= '<div id="MonthDropdown" class="DropdownContainer"><select onchange="document.location.href=\'?&month=\'+this.value" class="sprite" name="month">';
	$start = $month = strtotime('2011-11-01');
	$end = strtotime('2012-12-01');
	
	if(isset($_GET['month'])) $current_month = $_GET['month'];
	else $current_month = date('m-Y');
	
	while($month < $end){
		$val = date('m-Y', $month);
		$sel = '';
		if($current_month == $val)$sel = ' selected="selected"';
		$html_results .= '<option value="'.date('m-Y', $month).'"'.$sel.'>'.date('F Y', $month).'</option>';
		$month = strtotime("+1 month", $month);
	}
	$html_results .= '</select></div>';
	$html_results .= '<div class="MainHeader DottedBottom"><h1>Contest 2011 - 2012, results</h1></div>';

	if(in_array($user_id, $user_ids)){
		$xml_entries = $contest->getTop100($current_month);
		$entries = $xml_entries->entry;
		if(count($entries)==0) $html_results .= '<div class="Notice">There are no votes yet!</div>';
		else{
			$html_results .= '<table>';
			foreach ($entries as $entry){	
				$html_results .= '<tr><td style="padding:5px;"><a href="'.ROOT_PATH.'download/'.$entry->photo_id.'" target="_blank"><img src="'.Utils::getPhotoUrl($entry->user_id, $entry->photo_id, 'thumb').'" /></a></td><td>entry id: '.$entry->id.'<br/>photo id: <a href="'.ROOT_PATH.'photo/'.$entry->photo_id.'" target="_blank">'.$entry->photo_id.'</a><br/>num votes: '.$entry->vote_count.'</td></tr>';
			}
			$html_results .= '</table>';
		}
	}else $html_results .= '<div class="Notice Error">You are not allowed to view this page.</div>';
	$html_results .= '</div>';
	echo $html_results;
?>