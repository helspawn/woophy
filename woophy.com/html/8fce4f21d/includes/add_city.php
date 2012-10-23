<?php
if(isset($_POST["submit_add"])){
	if(	strlen(trim($_POST["LATI"])) > 0 && 
		strlen(trim($_POST["LONGI"])) > 0 && 
		strlen(trim($_POST["CC1"])) > 0 && 
		strlen(trim($_POST["ADM1"])) > 0 && 
		strlen(trim($_POST["NT"])) > 0 && 
		strlen(trim($_POST["FULL_NAME_ND"])) > 0){

		$ADM2 = strlen($_POST["ADM2"])==0 ? "NULL" : "'".$_POST["ADM2"]."'";
		$PC = strlen($_POST["PC"])==0 ? 0 : $_POST["PC"];

		$LATI = (float)$_POST["LATI"];
		$LONGI = (float)$_POST["LONGI"];
		$CC1 = "'".DB::escape($_POST["CC1"])."'";
		$ADM1 = "'".DB::escape($_POST["ADM1"])."'";
		$NT = "'".DB::escape($_POST["NT"])."'";
		$FULL_NAME_ND = "'".DB::escape($_POST["FULL_NAME_ND"])."'";
		
		if(strlen($_POST["UNI"]) == 0){
			$result = DB::query("SELECT MAX(UNI) FROM cities");
			if($result){
				$UNI = DB::result($result,0) + 1;
			}
		}else{
			$UNI = $_POST["UNI"];
		}
		
		if(strlen($_POST["UFI"]) == 0){
			$result = DB::query("SELECT MAX(UFI) FROM cities");
			if($result){
				$UFI = (int)DB::result($result,0) + 1;
			}
		}else{
			$UFI = (int)$_POST["UFI"];
		}
		
		
		$qry = "INSERT INTO cities (UFI,UNI,LATI,LONGI,PC,CC1,ADM1,ADM2,NT,FULL_NAME_ND) VALUES ($UFI,$UNI,$LATI,$LONGI,$PC,$CC1,$ADM1,$ADM2,$NT,$FULL_NAME_ND)";

		if(DB::query($qry)){
			$error = "City added!<br/><br/>".$qry;
		}else{
			$error = "City could not be added: ". DB::error();
		}
	}else{
		$error = "Fill in all the required fields!";
	}
}
?>
<script type="text/javascript">
function onGetRegions (xml, success){
	var regions = xml.getElementsByTagName('region');
	if(regions){
		var e = document.forms['form_add_city'].ADM1;
		e.disabled = false;
		var o = e.options;
		var i = o.length;
		while(i--){
			o[i] = null;
		}
		while(++i<regions.length){
			var cc = regions[i].getAttribute('code').substr(2);
			e.options[i] = new Option(regions[i].firstChild.nodeValue+" ("+cc+")",cc);
		}
	}
};
function getRegions(id){
	jQuery.post('regions_results.php', {cc:id}, onGetRegions);
};
</script>
<fieldset>
<legend>Add city</legend>
<div style="padding:10px;">
<?php
if(isset($error)){
	echo '<p class="Error">'.$error.'</p><hr/>';
}
?>
<form name="form_add_city" method="post" action="">
<table>
	<tr><td width="200">Unique Feature Identifier</td><td width="200"><input type="text" name="UFI" value="" /></td><td>A number which uniquely identifies the feature.<br/>If unknown please leave blank.</td></tr>
	<tr><td>Unique Name Identifier</td><td><input type="text" name="UNI" value="" /></td><td>A number which uniquely identifies a name.<br/>If unknown please leave blank.</td></tr>
	<tr><td>Latitude</td><td><input type="text" name="LATI" value="" /> <span style="color:red">*</span></td><td>Decimal degrees<br/>no sign (+) = North, negative sign (-) = South.</td></tr>
	<tr><td>Longitude</td><td><input type="text" name="LONGI" value="" /> <span style="color:red">*</span><td>Decimal degrees<br/>no sign (+) = East, negative sign (-) = West</td></tr>
	<tr><td>Populated Place Classification</td><td><input type="text" name="PC" value="" /></td><td>A graduated numerical scale denoting the relative importance of a populated place. The scale ranges from 1, relatively high, to 5, relatively low.<br/>If unknown please leave blank.</td></tr>
	<tr><td>Country</td><td colspan="2">
	<select name="CC1" onchange="getRegions(this.options[this.selectedIndex].value)">
		<option value=""></option>
	  <?php
	$query = "SELECT country_name, country_code FROM countries ORDER BY country_name ASC";
	$result = DB::query($query) or die(DB::error());
	  while ($row = DB::fetchAssoc($result)) {
			print "	<option value=\"".$row['country_code']."\">".$row['country_name']."</option>\n";
	}
	?>
			</select> <span style="color:red">*</span></td></tr>
	<tr><td>First-order administrative division</td><td colspan="2"><select name="ADM1" disabled><option value="">First select a country</option></select> <span style="color:red">*</span></td></tr>
	<tr><td>Second-order administrative division</td><td><input type="text" name="ADM2" value="" /></td><td>If unknown please leave blank.</td></tr>
	<tr><td>Name</td><td><input type="text" name="FULL_NAME_ND" value="" /> <span style="color:red">*</span></td><td>Complete name which identifies the named feature (only Roman characters)</td></tr>
	<tr><td>Name type</td><td colspan="2">
	<select name="NT" id="NT">
		<option value="C">C (convedential)</option>
		<option value="N" selected>N (native)</option>
		<option value="V">V (variant)</option>
		<option value="D">D (not verified)</option>
	</select> <span style="color:red">*</span></td></tr>
	<tr><td colspan="3"><input type="submit" name="submit_add" value="Add city"/></td></tr>
</table>

</form>
<script type="text/javascript">
function DMS2DEC(degrees, minutes, seconds) {
	var e = document.getElementById;
	var degrees = parseInt(e('deg').value);
	var minutes = parseInt(e('min').value);
	var seconds = parseInt(e('sec').value);
	if(isNaN(degrees) || isNaN(minutes) || isNaN(seconds)){
		alert("Fill in all the required fields!");return;
	}
	var lenSeconds = String(seconds).length;
	var minsec = seconds;
	for (var i = 0; i < lenSeconds; i++) {
		minsec /= 10;
	}
	minsec += minutes;
	e('DEC').value = (degrees + (minsec / 60));
}
</script>
</div>
</fieldset>
<fieldset>
<legend>Convert DMS to DEC</legend>
<div style="padding:10px;">
DMS: <input type="text" size="3" name="deg" id="deg" value="deg"/>&nbsp;<input type="text" size="3" name="min" id="min"  value="min"/>&nbsp;<input type="text" size="3" name="sec" id="sec" value="sec"/> DEC: <input type="text" name="DEC" id="DEC" value=""/>
<br/><br/><input type="button" onclick="DMS2DEC();" value="Convert" />
</div>
</fieldset>