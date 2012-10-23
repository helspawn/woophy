<?php
$fluff_filename = realpath('fluff_words.txt');
$msg = '';

if(isset($_POST['fluff'])){
	$fluff_words = $_POST['fluff'];
	
	$fluff_words_array = explode("\n", $fluff_words);
	if(saveArrayToFile($fluff_words_array) !== FALSE) $msg = 'Your file has been saved.';	
}else{
	$fluff_words_fh = fopen($fluff_filename,'r');
	$fluff_words = fread($fluff_words_fh, filesize($fluff_filename));
	fclose($fluff_words_fh);

	if(isset($_POST['new_word'])){
		$fluff_words_array = explode("\n", $fluff_words);
		array_push($fluff_words_array, trim($_POST['new_word']));
		if(saveArrayToFile($fluff_words_array) !== FALSE) $msg = 'Your word \"'.$_POST['new_word'].'\" has been added.';
		else $msg = 'Error adding word.';
		echo '{"msg": "'.$msg.'"}';	
		die();
	}
}

function saveArrayToFile($fluff_words_array){
	global $fluff_filename;
	$fw_clean = array();

	//remove empty entries
	foreach($fluff_words_array as $word){
		$word = trim($word);
		if($word != '') $fw_clean[count($fw_clean)] = $word; 
	}
	//remove duplicates
	$fw_clean = array_unique($fw_clean);
	
	//sort alphabetically
	natsort($fw_clean);
	
	//save to file
	$fluff_words = implode("\n", $fw_clean);
	$fluff_words_fh = fopen($fluff_filename,'w');
	$fwrite = fwrite($fluff_words_fh, $fluff_words);
	fclose($fluff_words_fh);
	return $fwrite;
}

?>
<div style="font-weight:bold;color:#22aa22;"><?php echo $msg ?></div>
<form action="fluff_words.php" method="post">
	<textarea cols="50" rows="30" name="fluff"><?php echo $fluff_words ?></textarea>
	<div><input type="submit" value="save" /></div>
</form>
