<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004-2006 Paul Puzyrev, Sergei Larionov. www.minibb.net
Latest File Update: 2006-Dec-05
*/
//@mysql_connect($DBhost, $DBusr, $DBpwd) or die ('<b>Database/configuration error.</b>');
//@mysql_select_db($DBname) or die ('<b>Database/configuration error (DB is missing).</b>');
require_once CLASS_PATH.'DB.class.php';

function makeLim($page,$numRows,$viewMax){
$page=pageChk($page,$numRows,$viewMax);
if(intval($numRows/$viewMax)!=0&&$numRows>0){
if ($page>0) return ($page*$viewMax).','.$viewMax;
else return $viewMax;
}
else return '';
}

function getClForums($closedForums,$more,$prefix,$field,$syntax,$condition){
$xtr=$more.' (';
if($prefix!='') $prefix=$prefix.'.';
$siz=sizeof($closedForums);
foreach($closedForums as $c) {
$xtr.=' '.$prefix.$field.$condition.$c;
$siz--;
if ($siz!=0) $xtr.=' '.$syntax;
}
return $xtr.') ';
}

function db_simpleSelect($sus,$table='',$fields='',$uniF='',$uniC='',$uniV='',$orderby='',$limit='',$uniF2='',$uniC2='',$uniV2='',$and2=true,$groupBy=''){
//@mysql_connect($DBSLAVEhost, $DBusr, $DBpwd) or die ('<b>Database/configuration error.</b>');
//@mysql_select_db($DBname) or die ('<b>Database/configuration error (DB is missing).</b>');
if(!$sus){
$where='';
if($uniF!='') $where=' WHERE '.$uniF.$uniC."'".$uniV."'";
if($uniF2!='') {
$q=(substr_count($uniV2,'.')>0?'':"'");
$a=($and2?'AND':'WHERE');
$where.=' '.$a.' '.$uniF2.$uniC2.$q.$uniV2.$q;
}
if($limit!='') $limit='limit '.$limit;
if($orderby!='') $orderby='order by '.$orderby;
if($groupBy!='') $groupBy='group by '.$groupBy;
$xtr=(!isset($GLOBALS['xtr'])?'':$GLOBALS['xtr']);
$sql='SELECT '.$fields.' FROM '.$table.$where.' '.$xtr.' '.$groupBy.' '.$orderby.' '.$limit;
//if($sus==0 and function_exists('parseSql')) $sql=parseSql($sql);
//echo "!-- ".$sql." --><br />";
file_put_contents("forum.log",$sql."\n",FILE_APPEND);
//$result=mysql_query($sql);// or die(mysql_error());//uncomment to debug
$result=DB::query($sql);
if($result) {
$GLOBALS['countRes']=DB::numRows($result);
$GLOBALS['result']=$result;
}
}
if(($sus==1||isset($result))&&isset($GLOBALS['countRes'])&&$GLOBALS['countRes']>0)  return mysql_fetch_row($GLOBALS['result']);
elseif($sus==2){
$a=(strlen($uniF2)?'AND':'');
$w=(strlen($uniF)||strlen($uniF2)?'WHERE':'');
$xtr=(isset($GLOBALS['xtr'])?$GLOBALS['xtr']:'');
return DB::result(DB::query('SELECT '.$fields.' FROM '.$table.' '.$w.' '.$uniF.$uniC.$uniV.' '.$a.' '.$uniF2.$uniC2.$uniV2.' '.$xtr),0);
}
else return FALSE;
}


function insertArray($insertArray,$tabh){
$into=''; $values='';
foreach($insertArray as $ia) {
$iia=$GLOBALS[$ia];
$into.=$ia.',';
$values.=($iia=='now()'?$iia.',':"'".$iia."',");
}
$into=substr($into,0,strlen($into)-1);
$values=substr($values,0,strlen($values)-1);
//@mysql_connect($DBhost, $DBusr, $DBpwd) or die ('<b>Database/configuration error.</b>');
//@mysql_select_db($DBname) or die ('<b>Database/configuration error (DB is missing).</b>');
$res=DB::query('insert into '.$tabh.' ('.$into.') values ('.$values.')') or die('<p>'.mysql_error().'. Please, try another name or value.');
$GLOBALS['insres']=DB::insertId();
return DB::error();
}

function updateArray($updateArray,$tabh,$uniq,$uniqVal){
$into='';
foreach($updateArray as $ia) {
$iia=$GLOBALS[$ia];
$into.=($iia=='now()'?$ia.'='.$iia.',':$ia."='".$iia."',");
}
$into=substr($into,0,strlen($into)-1);
$unupdate=($uniq!=''?' where '.$uniq.'='."'".$uniqVal."'":'');
//@mysql_connect($DBhost, $DBusr, $DBpwd) or die ('<b>Database/configuration error.</b>');
//@mysql_select_db($DBname) or die ('<b>Database/configuration error (DB is missing).</b>');
$res=DB::query('update '.$tabh.' set '.$into.' '.$unupdate) or die('<p>'.mysql_error().'. Please, try another name or value.');
return DB::affectedRows();
}

function db_delete($table,$uniF='',$uniC='',$uniV='',$uniF2='',$uniC2='',$uniV2=''){
$where=($uniF!=''?'where '.$uniF.$uniC.$uniV:'');
if($uniF2!='') {
$where.=' AND '.$uniF2.$uniC2.$uniV2;
}
//@mysql_connect($DBhost, $DBusr, $DBpwd) or die ('<b>Database/configuration error.</b>');
//@mysql_select_db($DBname) or die ('<b>Database/configuration error (DB is missing).</b>');
$sql='DELETE FROM '.$table.' '.$where;
$result=DB::query($sql);
if($result) return DB::affectedRows();
else return FALSE;
}

function db_ipCheck($thisIp,$thisIpMask,$user_id){
$res=DB::query('select id from '.$GLOBALS['Tb'].' where 
banip='."'".$thisIp."'".' or banip='."'".$thisIpMask[0]."'".' or 
banip='."'".$thisIpMask[1]."'".' or banip='."'".$user_id."'");
if($res and DB::numRows($res)>0) return TRUE; else return FALSE;
}

function db_inactiveUsers($sus,$what=''){
/*Admin - users that didnt any post */
//@mysql_connect($DBSLAVEhost, $DBusr, $DBpwd) or die ('<b>Database/configuration error.</b>');
//@mysql_select_db($DBname) or die ('<b>Database/configuration error (DB is missing).</b>');
if(!$sus) {
if($GLOBALS['makeLim']>0) $GLOBALS['makeLim']='LIMIT '.$GLOBALS['makeLim'];
$result=DB::query('select '.$what.' from '.$GLOBALS['Tu'].' LEFT JOIN '.$GLOBALS['Tp'].' ON '.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserId'].'='.$GLOBALS['Tp'].'.poster_id where '.$GLOBALS['Tp'].'.poster_id IS NULL order by '.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserId'].' '.$GLOBALS['makeLim']);
if($result) {
$GLOBALS['countRes']=DB::numRows($result);
$GLOBALS['result']=$result;
}
}
if(isset($GLOBALS['countRes']) and $GLOBALS['countRes']>0) return DB::fetchRow($GLOBALS['result']);
else return FALSE;
}

function db_deadUsers($sus,$less){
/*Admin-dead users*/
//@mysql_connect($DBhost, $DBusr, $DBpwd) or die ('<b>Database/configuration error.</b>');
//@mysql_select_db($DBname) or die ('<b>Database/configuration error (DB is missing).</b>');
if(!$sus){
$GLOBALS['makeLim']=(isset($GLOBALS['makeLim'])&&$GLOBALS['makeLim']>0?'LIMIT '.$GLOBALS['makeLim']:'');
$result=DB::query('select '.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserId'].','.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserSheme']['username'][1].','.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserDate'].','.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserSheme']['user_password'][1].','.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserSheme']['user_email'][1].',max('.$GLOBALS['Tp'].'.post_time) as m, '.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserSheme']['num_posts'][1].' from '.$GLOBALS['Tu'].','.$GLOBALS['Tp'].' where '.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserId'].'='.$GLOBALS['Tp'].'.poster_id group by '.$GLOBALS['Tp'].'.poster_id having m<'."'".$less."' ".$GLOBALS['makeLim']);
if($result){
$GLOBALS['countRes']=DB::numRows($result);
$GLOBALS['result']=$result;
}
}
if(isset($GLOBALS['countRes']) and $GLOBALS['countRes']>0) return DB::fetchRow($GLOBALS['result']);
else return FALSE;
}

function db_calcAmount($tbName,$tbKey,$tbVal,$setName,$setField,$tbKey2=''){
/* Function to get amount of values from table $tbName by criteria $tbKey=$tbVal; then update table's $setName field $setField by this amount */
//@mysql_connect($DBhost, $DBusr, $DBpwd) or die ('<b>Database/configuration error.</b>');
//@mysql_select_db($DBname) or die ('<b>Database/configuration error (DB is missing).</b>');
$amount=0;
$amount=DB::result(DB::query('select count(*) from '.$tbName.' where '.$tbKey.'='.$tbVal),0);
if($tbKey2=='') $tbKey2=$tbKey;
DB::query('update '.$setName.' set '.$setField.'='."'".$amount."'".' where '.$tbKey2.'='.$tbVal);
return $amount;
}

function db_searchSelect($sus, $table='', $fields='', $sqlstr='', $makeLim='', $orderBy=''){
//@mysql_connect($DBhost, $DBusr, $DBpwd) or die ('<b>Database/configuration error.</b>');
//@mysql_select_db($DBname) or die ('<b>Database/configuration error (DB is missing).</b>');
if(!$sus){
$sql='SELECT '.$fields.' FROM '.$table.' WHERE '.$sqlstr.' ';
if($orderBy!='') $sql.="ORDER BY $orderBy ";
if($makeLim!='') $sql.='LIMIT '.$makeLim;
//echo "!-- ".$sql." --><br />";
$result=DB::query($sql);
if($result) {
$GLOBALS['countRes']=DB::numRows($result);
$GLOBALS['result']=$result;
}
}
if( ($sus==1 OR isset($result) ) AND isset($GLOBALS['countRes']) AND $GLOBALS['countRes']>0) return DB::fetchRow($GLOBALS['result']);
else return FALSE;
}

function db_genPhrase($phrase,$where,$searchType){
if($where==0) $field='post_text';
elseif($where==1) $field='topic_title';
$phrase=str_replace('$', '&#036;', $phrase);

$sql=' (';

if($searchType==0 or $searchType==3){
$words=explode(' ',$phrase);

$gen='';
foreach($words as $w) {
$w=trim(str_replace('%','',$w));
if($w!='' and strlen($w)>2) if($searchType==0) $gen.="{$w}% "; else $gen.="%{$w}";
}
$gen=trim($gen);
if($searchType==0) $sql=" ($field like '% {$gen}' or $field like '%>{$gen}' or $field like '{$gen}' or $field like '%;{$gen}' or $field like '".substr($gen,0,strlen($gen)-1).".') ";
else $sql=" ($field like '{$gen}%') ";
}
else $sql=" ($field like '% {$phrase} %' or $field like '{$phrase} %' or $field like '%>{$phrase} %' or $field like '%&quot;{$phrase} %' or $field like '% {$phrase}.%' or $field like '{$phrase}.') or $field like '{$phrase}' ";

//echo $sql;

return $sql;
}

?>
