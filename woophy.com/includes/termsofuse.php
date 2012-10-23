<div class="Section">
<form method="GET">
<div id="LanguageDropdown" class="DropdownContainer"><select class="sprite" name="lang" onchange="this.form.submit();">
<?php
	$lang = isset($_GET['lang']) ? mb_strtolower($_GET['lang']) : 'en';
	$options = array(	'en'=>'English',
						'fr'=>'French',
						'pt'=>'Portuguese',
						'sv'=>'Swedish');
	foreach($options as $code=>$value){
		echo '<option value="'.$code.'"';
		if($lang == $code) echo ' selected="true"';
		echo '>'.$value.'</option>';
	}
?>	
</select></div></form>
<?php	
	require_once CLASS_PATH.'Template.class.php';
	try{
		$tpl = new Template('termsofuse_'.$lang.'.tpl');
	}catch(Exception $e){
		$tpl = new Template('termsofuse_en.tpl');
	}
	echo (string)$tpl->parse(array());
?>
<div class="Notice">
Complaints or remarks can be sent to <a href="<?php echo INFO_EMAIL_ADDRESS ?>" target="_self"><?php echo INFO_EMAIL_ADDRESS ?></a>
</div>
</div>