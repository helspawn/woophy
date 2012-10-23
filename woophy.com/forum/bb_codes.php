<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004-2007 Paul Puzyrev, Sergei Larionov. www.minibb.net
Latest File Update: 2007-Jan-08
*/

$GLOBALS['imgsWidth']=150; //static width for shrinking images

function enCodeBB($msg,$admin) {

$pattern=array(); $replacement=array();

$userUrlsAllowed=($GLOBALS['user_id']>0 and isset($GLOBALS['user_num_posts']) and $GLOBALS['user_num_posts']>5);
//$userUrlsAllowed =true;

$pattern[]="/\[nourl\](.+?)\[\/nourl\]/i";
$replacement[]="<!-- nourl -->\\1<!-- /nourl -->";

//new woophy image:
//$pattern[]="/\[img=(".str_replace('/','\/',PHOTOS_URL)."([^<> \n\r\[\]]+?)([1-9][0-9]*)+?\.jpg)\](.*?)\[\/img\]/i";
//$replacement[]='<a href="'.ABSURL.'photo/\\3" target="_blank"><img src="\\1" alt="\\4" title="\\4" /></a>';
$pattern[]="/\[img=".Utils::getWoophyPhotoRegEx()."\](.*?)\[\/img\]/i";
$replacement[]='<a href="'.ABSURL.'photo/\\7" target="_blank"><img src="\\2\\3\\6\\7\\8.jpg" alt="\\9" title="\\9" /></a>';


//user url image
$pattern[]="/\[img=(http:\/\/([^<> \n\r\[\]]+?)\.?(gif|jpg|jpeg|png)?)\](.*?)\[\/img\]/i";
$replacement[]='<img src="\\1" alt="\\4" title="\\4" />';

$pattern[]="/\[url[=]?\](.+?)\[\/url\]/i";
if($userUrlsAllowed) $replacement[]="<a href=\"\\1\" target=\"_blank\" rel=\"nofollow\">\\1</a>";
else $replacement[]="\\1";

$pattern[]="/\[url=((f|ht)tp[s]?:\/\/[^<> \n\r\[\]]+?)\](.*?)\[\/url\]/i";
if($userUrlsAllowed) $replacement[]="<a href=\"\\1\" target=\"_blank\" rel=\"nofollow\">\\3</a>";
else $replacement[]="\\1";


$pattern[]="/\[[bB]\](.+?)\[\/[bB]\]/s";
$replacement[]='<strong>\\1</strong>';

$pattern[]="/\[[iI]\](.+?)\[\/[iI]\]/s";
$replacement[]='<em>\\1</em>';

$pattern[]="/\[[uU]\](.+?)\[\/[uU]\]/s";
$replacement[]='<u>\\1</u>';


$msg=preg_replace($pattern, $replacement, $msg);

if(substr_count($msg,'<img')>0) $msg=str_replace('align=""', '', $msg);
if(substr_count($msg,'"nofollow"></a>')>0) $msg=str_replace('"nofollow"></a>', '"nofollow">URL</a>', $msg);

return $msg;
}

//--------------->
function deCodeBB($msg) {

$pattern=array(); $replacement=array();

//new woophy image:
$pattern[]="/<a href=\"([^<> \n\r\[\]]+?)\" target=\"_blank\">[ ]<img src=\"([^<> \n\r\[\]]+?)\" alt=\"(.*?)\" title=\"(.*?)\" ?\/><\/a>/i";
$replacement[]="[img=\\2]\\3[/img]";

//new user url image
$pattern[]="/<img src=\"([^<> \n\r\[\]]+?)\" alt=\"(.*?)\" title=\"(.*?)\" ?\/>/i";
$replacement[]="[img=\\1]\\3[/img]";

$pattern[]="/<!-- nourl -->([^<> \n\r\[\]]+?)<!-- \/nourl -->/i";
$replacement[]="[nourl]\\1[/nourl]";

$pattern[]="/<a href=\"([^<> \n\r\[\]]+?)\" target=\"(_new|_blank)\"( rel=\"nofollow\")?>(.+?)<\/a>/i";
$replacement[]="[url=\\1]\\4[/url]";

$pattern[]="/<strong>(.+?)<\/strong>/is";
$replacement[]="[b]\\1[/b]";

$pattern[]="/<em>(.+?)<\/em>/is";
$replacement[]="[i]\\1[/i]";

$pattern[]="/<[uU]>(.+?)<\/[uU]>/s";
$replacement[]="[u]\\1[/u]";


$msg=preg_replace($pattern, $replacement, $msg);
$msg=str_replace ('<br />', "\n", $msg);
if(substr_count($msg, '[img\\2]')>0) $msg=str_replace('[img\\2]', '[img]', $msg);

if(function_exists('smileThis')) $msg=smileThis(FALSE,TRUE,$msg);

return $msg;
}

?>
