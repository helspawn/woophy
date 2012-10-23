<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004-2006 Paul Puzyrev, Sergei Larionov. www.minibb.net
Latest File Update: 2006-Dec-18
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

include ($pathToFiles.'bb_codes.php');

$t=explode('/', $main_url);
$tUrl=implode('/', array($t[0], $t[1], $t[2]));

function special_substr($text, $limit){
/* analogue of default substr() with exception it cuts text off not by every symbol, but by actual symbols, even if they are encoded as unicode (something like &#...; is one symbol) */

$total=0;
$returned='';
$foundUni=0;
for($i=0;$i<strlen($text);$i++){
if($text[$i]=='&') $foundUni=1;
if($foundUni==1)  { if($text[$i]==';') { $total++; $foundUni=0; } }  else $total++;
$returned.=$text[$i];
if($total>=$limit) break;
}

return $returned;
}

//--------------->
function wrapText($wrap,$text){
$exploded=explode(' ',$text);

for($i=0;$i<sizeof($exploded);$i++) {
if(!isset($foundTag)) $foundTag=0;
$str=$exploded[$i];

if (substr_count($str, '<')>0) $foundTag=1;

if(substr_count($str, '&#')>0 or substr_count($str, '&quot;')>0 or substr_count($str, '&amp;')>0 or substr_count($str, '&lt;')>0 or substr_count($str, '&gt;')>0 or substr_count($str, "\n")>0) $fnAmp=1; else $fnAmp=0;

if(strlen($str)>$wrap and ($foundTag==1 or $fnAmp==1)) {

$chkPhr=''; $symbol=0;
$foundAmp=0;

for ($a=0; $a<strlen($str); $a++) {

if($foundTag==0 and $foundAmp==0) $symbol++;

if ($str[$a]=='<') { $foundTag=1; }
if ($str[$a]=='>' and $foundTag==1) { $foundTag=0;}

if ($str[$a]=='&') { $foundAmp=1; }
if ($str[$a]==';' and $foundAmp==1) { $foundAmp=0; }

if($str[$a]==' ' or $str[$a]=="\n") {$symbol=0;}
if($symbol>=$wrap and $foundTag==0 and $foundAmp==0 and isset($str[$symbol+1])) { $chkPhr.=$str[$a].' '; $symbol=0; }
else $chkPhr.=$str[$a];

}//a cycle

if (strlen($chkPhr)>0) $exploded[$i]=$chkPhr;

}
elseif (strlen($str)>$wrap) $exploded[$i]=chunk_split($exploded[$i],$wrap,' ');
else{
if (substr_count($str, '<')>0 or substr_count($str, '>')>0) {
for ($a=strlen($str)-1;$a>=0;$a--){
if($str[$a]=='>') {$foundTag=0;break;}
elseif($str[$a]=='<') {$foundTag=1;break;}
}
}
}
} //i cycle

return implode(' ',$exploded);
}

//--------------->
function urlMaker($text){

/*
Only alphanumerics [0-9a-zA-Z], the special characters "$-_.+!*'()," [not including the quotes and # - ed], and reserved characters used for their reserved purposes may be used unencoded within a URL. http://www.rfc-editor.org/rfc/rfc1738.txt
*/

//[0-9a-zA-Z$-_.+!*'(),&=\#~]

$patterns=array("#(^|[ \n])(https|http|ftp)://([^<> \[\]\n\r]+)#i", "#(^|[ \n])ftp\.([^<> \[\]\n\r]+)#i", "#(^|[ \n])www\.([^<> \[\]\n\r]+)#i");
$replacements=array('\\1<a href="\\2://\\3" target="_blank" rel="nofollow">\\2://\\3</a>', '\\1<a href="ftp://ftp.\\2" target="_blank" rel="nofollow">ftp.\\2</a>', '\\1<a href="http://www.\\2" target="_blank" rel="nofollow">www.\\2</a>');

return preg_replace($patterns, $replacements, $text);

}

//--------------->
function textFilter($text,$size,$wrap,$urls,$bbcodes,$eofs,$admin,$shorten=0){
if(get_magic_quotes_gpc()==0) $text=addslashes($text);

if(($admin==1 or (isset($GLOBALS['isMod']) and $GLOBALS['isMod']==1)) and isset($GLOBALS['adminHTML']) and $GLOBALS['adminHTML']) $text=str_replace(array("'", '"'), array('&#039;', '&quot;'), trim($text));
else $text=htmlspecialchars(trim($text),ENT_QUOTES);

$text=str_replace(array('\&#039;', '\&quot;', chr(92).chr(92).chr(92).chr(92), chr(92).chr(92), '&amp;#', '$'), array('&#039;', '&quot;', '&#92;&#92;', '&#92;', '&#', '&#036;'), $text);

if(isset($GLOBALS['l_meta']) and substr_count(strtolower($GLOBALS['l_meta']), 'utf-8')==0) $text=str_replace(array('“', '”', '‘', '’', '…'), array('&quot;', '&quot;', '&#039;', '&#039;', '...'), $text);

if(substr_count($text, '&#9')>0) $text=preg_replace("@&#9[0-9]{2,};@", '', $text);

if (!$bbcodes) {
$text=enCodeBB($text, $admin);
$text=str_replace('><img src=','> <img src=',$text);
}
if($urls and $GLOBALS['user_id']>0 and isset($GLOBALS['user_num_posts']) and $GLOBALS['user_num_posts']>5 and !$bbcodes) {
$text=urlMaker($text);
}
//echo $text; 
$text=wrapText($wrap,$text);

if($size and strlen($text)>$size) {
$text=special_substr($text, $size);
if ($shorten>0 and strlen($text)>$shorten) $text=substr($text,0,$shorten);

if(substr_count($text, '&')>0){
/* Avoid special symbols extract */
$tmpArr=explode ('&', $text);
$last=sizeof($tmpArr)-1;
if ($last>0) {
if (substr_count($tmpArr[$last], ';')==0) array_pop($tmpArr);
$text=implode ('&', $tmpArr);
}
}

}
if($eofs and !isset($GLOBALS['disableLineBreaks'])){
while (substr_count($text, "\r\n\r\n\r\n\r\n")>4) $text=str_replace("\r\n\r\n\r\n\r\n","\r\n",$text);
while (substr_count($text, "\n\n\n\n")>4) $text=str_replace("\n\n\n\n","\n",$text);
$text=str_replace(array("\r\n", "\n"),'<br />',$text);
}
while(substr($text,-1)==chr(92)) $text=substr($text,0,strlen($text)-1);
$text=str_replace(array('."', ',"', '-"', ':"', ';"', ')"'), '"', $text);

$text=preg_replace("#<a href=\"".$GLOBALS['tUrl']."(.*?)\" target=\"_blank\" rel=\"nofollow\">#", "<a href=\"".$GLOBALS['tUrl']."\\1\" target=\"_blank\">", $text);

return $text;
}

?>