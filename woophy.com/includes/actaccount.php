<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}
?>
<div class="Section">
<div class="MainHeader DottedBottom clearfix"><h1>Activate account</h1></div>
<p>You successfully activated your account.</p>
<ul>
	<li>If you want to edit your your profile, go to <a href="<?php echo ROOT_PATH.'account/profile/'; ?>">My account</a></li>
	<li>If you want to upload photos, go to <a href="<?php echo ROOT_PATH.'account/upload/'; ?>">Upload photos</a></li>
</ul>
</div>