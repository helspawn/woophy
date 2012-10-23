<?php

if(isset($_POST['src'],$_POST['out'])){
	include 'class.JavaScriptPacker.php';
	
	$src = $_POST['src'];
	$out = $_POST['out'];
	if($src!=$out){
		$script = file_get_contents($src);
		if($script){
			$t1 = microtime(true);

			$packer = new JavaScriptPacker($script, 'Normal', true, false);
			$packed = $packer->pack();

			$t2 = microtime(true);
			$time = sprintf('%.4f', ($t2 - $t1) );
			$msg = 'Unable to write to file \''.$out.'\'';
			$success = false;
			$handle = fopen($out, 'wb');
			if($handle)if(fwrite($handle, $packed)) $success = true;
			if($success) $msg = 'script '. $src. ' packed in ' .$out. ', in ' .$time. ' s.'. "\n";
			fclose($handle);
			//if(!file_put_contents($out, $packed))$msg = 'Unable to write to file \''.$out.'\'';
		}else $msg = 'file \''.$src.'\' doesn\'t exist!';
	}else $msg = 'Enter different output file!';
}

if(isset($_POST['submit_minify'], $_POST['jsfiles'], $_POST['output'])){
	$files = $_POST['jsfiles'];
	foreach($files as $k=>$file){
		if(strlen(trim($file))==0)unset($files[$k]);
	}
	$output = trim($_POST['output']);
	if(count($files)>0 && strlen($output)>0){
		$t1 = microtime(true);
		$tmpfile = date('Ymd').'.tmp.js';
		$js = '';
		foreach($files as $file)$js .= file_get_contents($file)."\n";//add carriage return because last line of previous file can be comment without CR
		file_put_contents($tmpfile, $js);
		if(!file_exists($output))file_put_contents($output, '');//create empty file
		$app = 'C:\\Program Files (x86)\\Microsoft\\Microsoft Ajax Minifier';
		if(!file_exists($app))$app = 'C:\\Program Files\\Microsoft\\Microsoft Ajax Minifier';
		exec('"'.$app.'\\ajaxmin.exe" '.realpath($tmpfile).' -out '.realpath($output).' -clobber');
		unlink($tmpfile);
		$t2 = microtime(true);
		$time = sprintf('%.4f', ($t2 - $t1) );
		$msg2 = 'Files are packed in ' .$time. ' s.'. "\n";
	}else $msg2 = 'Fill in input output files';
}
?>
<!--
<fieldset>
<legend>Pack Javascript File (based on Dean Edwards JavaScript's Packer)</legend>
<div style="padding:10px;">
<form name="form_compress" method="post" action="">
<?php
	if(isset($msg))echo '<p class="Error">'.$msg.'</p>';
?>
<table>
<tr><td>Source file</td><td><input size="60" type="text" name="src" value="../js/global.js" /></td></tr>
<tr><td>Out file</td><td><input size="60" type="text" name="out" value="../js/global_packed.js" /></td></tr>
<tr><td>&nbsp;</td><td><input type="submit" name="submit" value="Compress"/></td></tr>
</table>
</form>
</div>
</fieldset>
-->
<fieldset>
<legend>Pack Javascript File (Microsoft Ajax Minifier)</legend>
<div style="padding:10px;">
<p>Windows only, Microsoft Ajax Minifier must be installed!</p>
<form name="form_compress" method="post" action="">
<?php
	if(isset($msg2))echo '<p class="Error">'.$msg2.'</p>';
?>
<table>
<tr><td>Source file</td><td><input size="60" type="text" name="jsfiles[]" value="../js/global.js" /></td></tr>
<tr><td>Source file</td><td><input size="60" type="text" name="jsfiles[]" value="../js/jquery.colorbox.js" /></td></tr>
<tr><td>Source file</td><td><input size="60" type="text" name="jsfiles[]" value="../js/map.js" /></td></tr>
<tr><td>Source file</td><td><input size="60" type="text" name="jsfiles[]" value="" /></td></tr>
<tr><td>Out file</td><td><input size="60" type="text" name="output" value="../js/lib.packed.js" /></td></tr>
<tr><td>&nbsp;</td><td><input type="submit" name="submit_minify" value="Compress"/></td></tr>
</table>
</form>
</div>
</fieldset>





