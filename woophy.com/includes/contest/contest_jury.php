<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}
	
	if(isset($_GET['month'])) $current_month = $_GET['month'];
	else $current_month = date('m-Y');
	
	$html_jury = '';
	$nav = array(
		array('label'=>'not voted','path'=>'notvoted'),
		array('label'=>'yes','path'=>'yes'),
		array('label'=>'no','path'=>'no'),
		array('label'=>'maybe','path'=>'maybe')
	);
	
	$param = explode('/', rtrim(REQUEST_PATH, '/'));
	foreach($param as $k=>$v)$param[$k] = Utils::stripQueryString($v);

	//resolve sub nav:
	$currentnavId = 0;
	if(isset($param[2])){
		foreach($nav as $k=>$v){		
			 if($v['path'] == $param[2]) {
				$currentnavId = $k;
				break 1;
			}
		}
	}
	$html_jury .= '<div class="MenuBar clearfix"><div id="SubNav2"><ul>'.PHP_EOL;
	$flag = false;
	foreach($nav as $k=>$v){	
		if($flag)$html_jury .= '<li></li>';
		$class = 'inactive';
		if($currentnavId == $k) $class = 'active';
		$html_jury .= '<li><a href="'.ROOT_PATH.$param[0].'/'.$param[1].'/'.$v['path'].'?month='.$current_month.'" class="'.$class.'">'.$v['label'].'</a></li>';
		$flag = true;
	}
	$html_jury .= '</ul>';
	$html_jury .= '</div></div>';

	$html_jury.= '<div class="Section">';


	$html_jury .= '<div id="MonthDropdown" class="DropdownContainer"><select onchange="document.location.href=\'?&month=\'+this.value" class="sprite" name="month">';
	$start = $month = strtotime('2011-11-01');
	$end = strtotime('2012-12-01');
	
	while($month < $end){
		$val = date('m-Y', $month);
		$sel = '';
		if($current_month == $val)$sel = ' selected="selected"';
		$html_jury .= '<option value="'.date('m-Y', $month).'"'.$sel.'>'.date('F Y', $month).'</option>';
		$month = strtotime("+1 month", $month);
	}
	$html_jury .= '</select></div>';
	
	$contest = ClassFactory::create('Contest');
	$contest->buffer = false;
	$access = ClassFactory::create('Access');
	$user_ids = explode(',', CONTEST_JURY);
	$user_id = $access->getUserId();
	$user_name = $access->getUserName();
	$html_jury .= '<div class="MainHeader DottedBottom"><h1>Contest 2011 - 2012, jury</h1></div>';
	if(in_array($user_id, $user_ids)){
		
		$last_entry_id= 0;
		$vote_value = NULL;
		
		if(isset($_POST['entry_id'], $_POST['value'])){		
			$last_entry_id = (int)$_POST['entry_id'];
			$xml_add = $contest->addVote($user_id, $_POST['entry_id'], $_POST['value']);
			if($err = $xml_add->err) $html_jury .= '<div class="Error">'.$err['msg'].'</div>';
		}
		if(isset($_GET['last_entry_id'])){
			$last_entry_id = (int)$_GET['last_entry_id'];//nav shortcut
		}
		
		
		switch($currentnavId){
			case 1:$vote_value = 'YES';break;
			case 2:$vote_value = 'NO';break;
			case 3:$vote_value = 'MAYBE';break;
		}

		if($currentnavId==0)$xml_entry = $contest->getUnVotedEntriesByUserId($user_id, 1, $current_month);
		else $xml_entry = $contest->getNextVotedEntryByUserId($user_id, $last_entry_id, $vote_value, $current_month);

		if($entry = $xml_entry->entry){


			$html_all_entries = '';

			if(isset($vote_value)){
			
				$xml_all_entries = $contest->getAllVotedEntriesByUserId($user_id, $vote_value, $current_month);
				$ids = array();
				foreach($xml_all_entries as $e){
					$ids[] = $e;
				}
				$num_votes = count($ids);
				$num_cols = 15;
				$html_all_entries .= '<br/><p>All entries voted with &quot;'.$vote_value.'&quot;:</p><table style="width:100%;table-layout:fixed;"><tr>';
				$num = ceil($num_votes/$num_cols)*$num_cols;
				for($i=0;$i<$num;$i++){
					if($i>=$num_votes){
						$html_all_entries .= '<td class="empty">&nbsp;</td>';
					}else{
						$id = $ids[$i];
						if((int)$entry->id == (int)$ids[$i]) $id = '<b><u>'.$id.'</u></b>';
						
						$html_all_entries .= '<td style="text-align:center"><a href="'.Utils::stripQueryString($_SERVER['REQUEST_URI']).'?last_entry_id='.($ids[$i]-1).'&month='.$current_month.'">'.$id.'</a></td>'.PHP_EOL;
					}
					if($i<$num_votes-1 && fmod($i+1, $num_cols)==0)$html_all_entries .= '</tr><tr>';
				}
				$html_all_entries .= '</tr></table>';			
			}else{
				$xml_voted = $contest->getNumberOfVotesByUserId($user_id, $current_month);
			}
			
			$xml_total = $contest->getNumberOfEntries($current_month);

			if(isset($vote_value))$html_jury .= '<p>You have voted '.$num_votes.' of total '.$xml_total->count.' entries with &quot;'.$vote_value.'&quot;</p>';
			else $html_jury .= '<p>You have voted '.$xml_voted->count.' of total '.$xml_total->count.' entries</p>';

			$html_jury .= '<form class="FormArea" method="post" action="'.Utils::stripQueryString($_SERVER['REQUEST_URI']).'?month='.$current_month.'" target="_self">';
			$html_jury .= '<div class="clearfix"><input class="GreenButton" type="submit" name="yes" value="YES" onclick="jQuery(\'#value\').val(this.value);return true;" /><input class="GreenButton" type="submit" name="no" value="NO" onclick="jQuery(\'#value\').val(this.value);return true;" /><input class="GreenButton" type="submit" name="maybe" value="MAYBE" onclick="jQuery(\'#value\').val(this.value);return true;" /><input type="hidden" value="'.$entry->id.'" name="entry_id" /><input type="hidden" value="" id="value" name="value" /></div>';
			$html_jury .= '<br/><a href="'.ROOT_PATH.'download/'.$entry->photo_id.'" target="_blank"><img src="'.Utils::getPhotoUrl($entry->user_id, $entry->photo_id, 'medium').'"/></a>';
			if(mb_strlen($entry->remark)>0)$html_jury .= '<br/><br/>'.$entry->remark;
			
			$xml_category = $contest->getCategoryById($entry->category_id);
			$html_jury .= '<br/><br/>Photo ID: <a href="'.ABSURL.'photo/'.$entry->photo_id.'" target="_blank">'.$entry->photo_id.'</a>';
			$html_jury .= '<br/>Category: '.$xml_category->name;
			$html_jury .= '<br/>Date: '.$entry->date;
			$html_jury .= '</form>';

			$html_jury .= $html_all_entries;

		}else{
			if($currentnavId==0) $html_jury .= '<div class="Notice">No entries found</div>';//You have voted every entry!
			else $html_jury .= '<div class="Notice">No &quot;'.$vote_value.'&quot; entries found</div>';
		}
	}else $html_jury .= '<div class="Notice Error">You are not allowed to view this page.</div>';
	
	$html_jury.='</div>';
	echo $html_jury;
?>