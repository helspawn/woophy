<?php
if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden');exit();}

function outputSearchBox($search_str, $baseurl, $options){
	$selected = '';
	$output = '<form action="'. $baseurl .'#search_results" id="Search" class="Section SearchBar clearfix" method="get">';
	$output .= '<input type="text" alt="'. $search_str .'" class="text" id="input" name="search" value="'.$search_str.'" />';
	$output .= '<div class="DropdownContainer"><select name="category" class="sprite">';
	foreach($options as $option){
		if(isset($_GET[$option['name']])) $selected = ' selected="selected"';
		$output .= '<option value="'.$option['name'].'"'.$selected.'>'.$option['label'].'</option>';
		$selected = '';
	}
	$output .= '</select></div>';
	$output .= '<input class="submit GreenButton" id="submit_search" type="submit" name="search" value="Go"/></form>';
	
	return $output;
}
