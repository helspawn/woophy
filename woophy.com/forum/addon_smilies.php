<?php
/*
addon_smilies.php : smilies addon file for miniBB 2.
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2007 Paul Puzyrev, 3rd parties (graphics). www.minibb.net
Latest File Update: 2007-Jun-22
*/

/* Options */
$dirname = 'smilies';
$splitRow=3;

/* Code */
if (!defined('INCLUDED776')) die ('Fatal error.');

$hackSmilies=1;

include($pathToFiles.'/'.$GLOBALS['dirname'].'/smdesc.php');

function smileThis($aPost, $aEdit, $postText) {

if ($aPost) {
foreach($GLOBALS['smilies'] as $key=>$val) $postText=str_replace($key, "[img=".$GLOBALS['img_url']."/".$GLOBALS['dirname']."/".$val."][/img]", $postText);
}
elseif ($aEdit) {
foreach($GLOBALS['smilies'] as $key=>$val) $postText=str_replace("[img=".$GLOBALS['img_url']."/".$GLOBALS['dirname']."/".$val."][/img]", $key, $postText);
}
return $postText;
}

$disbbcode=(isset($_POST['disbbcode']) and $_POST['disbbcode']==1?1:0);
//$p=(isset($_GET['p'])?$_GET['p']:'');


$aPost=(isset($action) and ($action=='ptopic' or $action=='pthread' or $action=='editmsg2' or $action=='pmail' or (isset($_POST['prevForm']) and $_POST['prevForm']==1)));

$dissmilies=(isset($_POST['dissmilies'])?$_POST['dissmilies']:'');

if($aPost and !$disbbcode and $dissmilies!='on') {
if(isset($_POST['postText'])) $_POST['postText']=smileThis($aPost, FALSE, $_POST['postText']);
}
elseif ($action=='displaysmilies') {
if(!isset($pathToFiles)) include('./setup_options.php');

echo <<<out
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>{$sitename}</title>
<meta name="ROBOTS" content="NOINDEX,NOFOLLOW" />
<link href="{$css_url}/forum.css" type="text/css" rel="stylesheet" />
</head>
<body class="gbody">
<table class="smilies">
out;

$s=1;
$listedFile=array();
foreach($smilies as $key=>$val) {
if(!in_array($val, $listedFile)){
$listedFile[]=$val;
if($s==1) echo '<tr>';
echo "<td class=\"caption2\" style=\"text-align:center\"><a href=\"#\" onclick=\"JavaScript:window.opener.paste_strinL('{$key}',3,'','',''); self.focus(); return true;\"><img src=\"{$img_url}/{$dirname}/{$val}\" alt=\"{$key}\" title=\"{$key}\" /></a></td>\n";

$s++;
if($s>$splitRow) {
$s=1;
echo '</tr>';
}

}
}
echo '</table></body></html>';
exit;
}
?>