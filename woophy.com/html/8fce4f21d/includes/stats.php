<?php

function writeToLog($num, $log){
	$newline =  $num.'  '.date('F j, Y H:i:s').PHP_EOL;
	if (is_writable($log)) {
		if (!$handle = fopen($log, 'a')) {
			echo "No access to ($filename)";
		}else fwrite($handle, $newline);
		fclose($handle);
	}else echo "($filename) is not writeable";
}

//views:
$numviews = '-';
$query = "SELECT SUM(views) FROM photos;";
$result = DB::query($query);
if($result){
	$numviews = DB::result($result,0);
	writeToLog($numviews, 'log_views.txt');
}

//users:
$numusers = '-';
$query = 'SELECT count( 0 ) FROM `users` WHERE photo_count >0;';
$result = DB::query($query);
if($result){
	$numusers = DB::result($result,0);
}

//registrations:
$registrations = '-';
$query = 'SELECT count( 0 ) FROM `users`;';
$result = DB::query($query);
if($result){
	$registrations = DB::result($result,0);
}
$numphotos = '-';
$query = 'SELECT count( 0 ) FROM `photos`;';
$result = DB::query($query);
if($result){
	$numphotos = DB::result($result,0);
}
$numrates = '-';
$query = 'SELECT count( 0 ) FROM `rating`;';
$result = DB::query($query);
if($result){
	$numrates = DB::result($result,0);
}
$numcomments = '-';
$query = 'SELECT count( 0 ) FROM `photo_comments`;';
$result = DB::query($query);
if($result){
	$numcomments = DB::result($result,0);
}
$numposts = '-';
$query = 'SELECT count( 0 ) FROM `minibbtable_posts`;';
$result = DB::query($query);
if($result){
	$numposts = DB::result($result,0);
}


?>
<fieldset>
<legend>Statistics</legend>
<div style="padding:10px;">
	<h2><?php echo date('F j, Y H:i:s');?></h2>
	<table>
		<tr><td>Number of photos:</td><td><?php echo $numphotos?></td></tr>
		<tr><td>Number of rates:</td><td><?php echo $numrates?></td></tr>
		<tr><td>Number of registered members:</td><td><?php echo $registrations?></td></tr>
		<tr><td>Number of members with 1+ photos:</td><td><?php echo $numusers?></td></tr>
		<tr><td>Number of photo popup views:</td><td><?php echo $numviews?> - <a href="log_views.txt" target="_blank">log_views.txt</a></td></tr>
		<tr><td>Number of comments:</td><td><?php echo $numcomments?></td></tr>
		<tr><td>Number of forum posts:</td><td><?php echo $numposts?></td></tr>
	</table>
</div>
</fieldset>