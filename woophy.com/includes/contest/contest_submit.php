<?php

/*<h2>Contest 2007, submit a photo</h2>
The 2007 News competition has ended. We will announce the 2008 contest soon.*/

//if(0){//disable submission
	
	$contest = ClassFactory::create('Contest');
	$contest->buffer = false;
	$access = ClassFactory::create('Access');
	$user_id = $access->getUserId();
	$user_name = $access->getUserName();
	
	if(isset($_POST['submit_photo'])){
		$xml_submit = $contest->addEntry($_POST['photo_id'], $_POST['category_id'], $user_id, $user_name, $_POST['remark']);
		if($error = $xml_submit->err) $error_msg = $error['msg'];
		else{
			$succes_msg = 'Thank you for your submission! See your submission <a href="'.ROOT_PATH.'contest/submissions">here</a>.';
			unset($_POST['category_id'],$_POST['photo_id'],$_POST['remark']);
		}
	}
?>
<script type="text/javascript">//<![CDATA[
	jQuery(document).ready(function(){
		var tt = new ToolTip('showhelp','Every Woophy photo has an unique ID. You can find this number in the lower left corner of the photo view window');
		var pt = new PreviewThumb();
	});
	
//]]></script>
<div class="Section">
<?php
	$themes = array();
	$themes[12] = 'Nature';
	$themes[1] = 'Culture';
	$themes[2] = 'Cityscapes';
	$themes[3] = 'Animals';
	$themes[4] = 'Landscapes';
	$themes[5] = 'People';
	$themes[6] = 'Work';
	$themes[7] = 'Science';
	$themes[8] = 'Money';
	$themes[9] = 'Leisure';
	$themes[10] = 'Home';
	$themes[11] = 'Life';
	if(date('mY') == '112011') $hdr = 'December 2011, &quot;Nature&quot;';//start in november
	else $hdr =  date('F Y').', &quot;'.$themes[(int)date('n')].'&quot;';
	echo '<div class="MainHeader DottedBottom"><h1>'.$hdr.'</h1></div>';

	if(isset($error_msg)) echo '<p class="Error">'.$error_msg.'</p>';
	else if(isset($succes_msg)) echo '<p class="Notice">'.$succes_msg.'</p>';
	else echo '<p>Before you can take part in the competition make sure you have <a href="'.ROOT_PATH.'account/upload">uploaded your photo to Woophy</a>.</p>';
?>
<form id="AddContestEntry" method="post" action="<?php echo Utils::stripSpecialAction($_SERVER['REQUEST_URI']);?>" name="frmpostentry" id="frmpostentry">
<div class="FormArea">
<div class="FormRow clearfix">	
<label for="photo_id">Photo ID&nbsp;&nbsp;<span class="strong" id="showhelp">[?]</span></label><input type="text" class="text" id="photo_id" name="photo_id" value="<?php
	if(isset($_POST['photo_id']))echo $_POST['photo_id']?>" />
<input id="show_preview" type="button" name="preview" class="submit GreenButton" value="Preview" />
<div id="preview_holder"></div>
<?php
	
	$xml_cats = $contest->getCategories();
	$cats = $xml_cats->category;
	$cat_id = isset($_POST['category_id'])?$_POST['category_id'] : null;
	$num = count($cats);
	$str = '';
	$i =0;
	foreach($cats as $cat){
		if(!isset($cat_id))$cat_id = $cat['id'];

		if($num>1){
			$str.= '<input ';
			if($cat_id == $cat['id']) $str.= ' checked="true"';
			$str .= ' type="radio" id="category_'.$cat['id'].'" name="category_id" value="'.$cat['id'].'"/><label for="category_'.$cat['id'].'">'.$cat.'</label>';
			if(++$i < $num)$str.= '<br/>';
		}else{
			$str .= '<input name="category_id" type="hidden" value="'.$cat['id'].'" />';
			break;
		}
	}
	if($num>1){
		echo '<label for="category_id">Category</label><div class="radiogroup">';
		echo $str;
		echo '</div>';
	}else echo $str;
?>
</div>
<div class="FormRow clearfix">
	<label>Tell us why this photo should be nominated [optional]</label>
	<textarea name="remark" rows="6" cols="65"><?php if(isset($_POST['remark'])) echo $_POST['remark']; ?></textarea>
	<input type="hidden" name="user_name" value="<?php echo $user_name;?>" />
	<input type="hidden" name="user_id" value="<?php echo $user_id;?>" />
</div>
<div class="SubmitRow clearfix"><input class="GreenButton submit" type="submit" name="submit_photo" value="Submit" /></div>
</div>
</form>
</div>
<?php
//}
?>
