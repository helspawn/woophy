<div class="Section">
<?php
	$page = ClassFactory::create('Page');
	$page->addInlineScript("jQuery(document).ready(function(){jQuery('#MainColumn ol>li>a').click(function(evt){jQuery('div',jQuery(this).parent()).slideToggle(200);evt.preventDefault()})});");
?>
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
		$tpl = new Template('faq_'.$lang.'.tpl');
	}catch(Exception $e){
		$tpl = new Template('faq_en.tpl');
	}
	echo (string)$tpl->parse(array('info_email_address'=>INFO_EMAIL_ADDRESS,'support_email_address'=>SUPPORT_EMAIL_ADDRESS,'root_url'=>ROOT_PATH));
?>
</div>