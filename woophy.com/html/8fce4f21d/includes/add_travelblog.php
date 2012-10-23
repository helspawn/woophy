<?php

$user_name = isset($_POST['user_name']) ? $_POST['user_name'] : '';
$travelblog_title = '';
$travelblog_description = '';
$success = false;
if(strlen($user_name)>0 && !isset($_POST['user_id'])){
	$result = DB::query('SELECT user_id FROM users WHERE user_name = \''.DB::escape($user_name).'\'');
	if($result && DB::numRows($result)==1){
		$user_id = (int)DB::result($result, 0, 0);
	}else $error = 'No member with nickname \''.$_POST['user_name'].'\' found!';
}
if(isset($_POST['submit_add'],$_POST['travelblog_title'],$_POST['travelblog_description'],$_POST['user_id'])){
	$user_id = $_POST['user_id'];
	$travelblog_title = $_POST['travelblog_title'];
	$travelblog_description = $_POST['travelblog_description'];
	
	if(DB::query('INSERT INTO travelblogs (travelblog_title, travelblog_description, user_id) VALUES (\''.DB::escape($travelblog_title).'\',\''.Utils::filterText($travelblog_description,true,false,true).'\','.(int)$user_id.')')){
		if(isset($_FILES['travelblog_image'])){
			$img = new Image($_FILES['travelblog_image']);
			if($img->isImage()){
				$blog_id = DB::insertId();
				if(!$img->output(230, 300, TRAVELBLOGS_PATH.$blog_id.'.jpg'))$error = 'Could not add image: '.$img->errorMessage;
			}//else $error = 'No valid image!';
		}

	}else $error = 'Could not add travelblog: '.DB::error();

	if(!isset($error)){
		$error = 'Travelblog added!<br/><br/>Do not forget to <a href="add_blog2user?&user_name='.urlencode($user_name).'">add blog permission</a> for member '.$user_name;
		$success = true;
	}
}
?>
<fieldset>
<legend>Add travelblog</legend>
<div style="padding:10px;">
<form name="form_add_travelblog" method="POST" action="" enctype="multipart/form-data">
<?php
if(isset($user_id) && $user_id>0){
	if(!$success){
?>
	<h1>Add Travelblog</h1>	
	<table>
	<tr><td>user name</td><td><?php echo $user_name;?></td></tr>
	<tr><td colspan="2"><hr/></td></tr>
	<tr><td colspan="2"><h3>Optional:</h3></td></tr>
	<tr><td>travelblog title</td><td><input id="travelblog_title" type="text" name="travelblog_title" value="" /></td></tr>
	<tr><td>travelblog description</td><td><textarea rows="8" cols="60" id="travelblog_description" name="travelblog_description"></textarea></td></tr>
	<tr><td>travelblog image</td><td><input name="travelblog_image" type="file" /></td></tr>
	<tr><td colspan="2"><hr/></td></tr>
	<tr><td>&nbsp;</td><td><input type="hidden" name="user_name" value="<?php echo $user_name;?>"/><input type="hidden" name="user_id" value="<?php echo $user_id?>"/><input type="submit" name="submit_add" value="Add travelblog"/></td></tr>
	</table>
<?php
	}	
	if(isset($error))echo '<hr/><p class="Error">'.$error.'</p>';
}else{
	if(isset($error))echo '<p class="Error">'.$error.'</p><hr/>';
	//select user
?>
	<h1>Member nickname:</h1>
	<input type="text" name="user_name" value="<?php echo $user_name;?>"/>&nbsp;<input type="submit" name="submit_select" value="Continue"/>
<?php
}
?>
</form>
</div>
</fieldset>