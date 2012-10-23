<?php
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: text/xml; charset=utf-8");
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
	echo "<regions>";
	if(array_key_exists("cc",$_POST) && strlen($_POST['cc'])==2){
		include_once '../../includes/config.php';
		include_once CLASS_PATH.'DB.class.php';
		
		DB::connect();
		$query = "SELECT * FROM regions WHERE code LIKE '".$_POST['cc']."%' ORDER BY region;";
		$result = DB::query($query);
		if($result){
			$count =0;
			$cats = '';
			while($row = DB::fetchAssoc($result)){
				echo '<region code="'.$row['code'].'"><![CDATA['.utf8_encode($row['region']).']]></region>';
			}
		}else echo DB::error();
		
		DB::close();
	}
	echo "</regions>";
?>