<?php

/*
addon_lastimages.php
*/


$imageList = '';
$limit = 10;

$result = @mysql_query('SELECT post_id, topic_id, img_url FROM forum_last_images ORDER BY post_id DESC LIMIT '.$limit);

if($result && @mysql_num_rows($result)==$limit){
	while($row = @mysql_fetch_assoc($result)){
		$imageList .= '<div style="float: left; width: 78px; height: 78px; overflow: hidden;"><a href="index.php?action=vthread&topic='.$row['topic_id'].'#msg'.$row['post_id'].'"><img src="'.$row['img_url'].'"></a></div>';
	}
$lastImages=<<<out
<table class="tbTransparent" style="margin-top:12pt">
<tr>
<td class="tbTransparent lastimagetitle" style="text-align:left"><span class="txtNr"><b>Last uploaded photos</b></span> <span class="txtCm">Click images to jump to corresponding forum post</span></td>
</tr>
<tr>
<td class="tbTransparent lastimageholder" style="text-align:left"><div class="lastimagesize"><span class="txtSm lastimages">{$imageList}<div style="clear: both;"></div></span></div></td>
</tr>
</table>
out;

}
?>