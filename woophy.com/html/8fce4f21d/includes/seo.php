<?php
$mysqli = new mysqli('v3.db.woophy.com', 'helspawn99', 'katja99', 'woophy_beta');
$query = 'SELECT MAX(photo_id) as newest_photo_id FROM photos';
$result = $mysqli->query($query);
$row = $result->fetch_assoc();
$start_id = $row['newest_photo_id'];

$limit=2000;

if(isset($_POST['start_id'])) $start_id = $_POST['start_id']; 
if(isset($_POST['limit'])) $limit = $_POST['limit']; 

$total_time = 0;
set_time_limit(180000);

$query = 'SELECT photos.photo_id, MAX(photos.photo_id) as newest_id, photos.user_id, photos.keywords, photos.alt_text as current_alt_text, photos.seo_suffix as current_seo_suffix, GROUP_CONCAT(DISTINCT photo_tags.tag_text SEPARATOR \', \') as tags, photo_categories.category_name, users.user_name, cities.FULL_NAME_ND as city_name, countries.country_name FROM photos 
		INNER JOIN users ON photos.user_id = users.user_id 
		INNER JOIN cities ON photos.city_id = cities.UNI 
		INNER JOIN countries ON cities.CC1 = countries.country_code 
		LEFT JOIN photo2category ON photos.photo_id = photo2category.photo_id
		LEFT JOIN photo_categories ON photo_categories.category_id = photo2category.category_id
		JOIN photo_tag2photo ON photo_tag2photo.photo_id = photos.photo_id
		JOIN photo_tags ON photo_tags.tag_id = photo_tag2photo.tag_id
		WHERE photos.photo_id<='.$start_id.' 
		GROUP BY photos.photo_id
		ORDER BY photos.photo_id DESC LIMIT 0, '.$limit.';';

$result = $mysqli->query($query);
$num_rows = $result->num_rows;
$i=0;
$timer = '';
$start_time = microtime(true);
$tags = array();
$seo_suffix='';
$alt_text='';
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:og="http://ogp.me/ns#" xmlns:fb="http://www.facebook.com/2008/fbml">
<head>
	<style type="text/css">
		body{margin:0;padding:0;font:13px Tahoma, Helvetica, sans-serif;}
		#form_div{padding:10px;background:#ffffff;width:100%;position:fixed;bottom:0;left:235px;border-top:2px solid #000000;height:30px;margin-top:50px;}
		input{margin-left:10px;padding:3px 10px;}
		table{border-collapse:collapse;width:100%;margin:0;padding-bottom:50px;table-layout:fixed;}
		tr{margin:0;width:100%;}
		tr#header_row{width:100%;}
		td{overflow:hidden;padding:2px 10px;margin:0;color:#000000;font-size:13px;line-height:16px;word-wrap: break-word;border-top:1px solid #333333;}
		th{padding:5px 10px;margin:0;color:#000000;font-weight:bold;background:#eeeeee;text-align:center;font-size:16px;line-height:20px;border-bottom:1px solid #444444;}
		.photo_id{width:5%;}
		.city{width:8%;}
		.country{width:7%;}
		.category{width:5%;}
		.tags{width:10%;}
		.desc{width:22%;}
		.seo{width:20%;}
		.alt_text{23%;}
		.updated{background-color:#EDF9E3 ! important;}
		.error{color:#bb4444;}
		.notice{float:left;margin:7px 20px;font-weight:bold;color:#2222dd;}
		.alt{background:#eeeeee;}
	</style>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script type="text/javascript">
		function addFluffWord(a){
			word = jQuery(a).text();
			if(confirm('Are you sure you want to add "' + word + '" to the fluff word list?')){
				jQuery.post('/8fce4f21d/fluff_words.php', {'new_word':word}, function(data){
					data = jQuery.parseJSON(data);
					alert(data.msg);
				});
			}
			return false;
		}
		
		jQuery(document).ready(function(){
			jQuery('.seo a, .alt_text a').bind('click', function(){addFluffWord(this);return false;});
		});

	</script>	
</head>
<body>
	
<!-- <div style="font-size:0px;color:#ffffff;line-height:0"> -->

<table>
	<tr id="header_row">
		<th class="photo_id">Photo ID</th>
		<th class="city">City</th>
		<th class="country">Country</th>
		<th class="category">Category</th>
		<th class="tags">Tags</th>
		<th class="desc">Description</th>
		<th class="seo">SEO</th>
		<th class="alt_text">Alt</th>
	</tr>
<?php
while($row = $result->fetch_assoc()){
	$update = FALSE;
	$i++;
	$metadata = array('city'=>$row['city_name'],'country'=>$row['country_name'],'tags'=>explode(', ', $row['tags']),'category'=>$row['category_name'],'description'=>$row['keywords'],'username'=>$row['user_name']);
	if($i%2==0) $alt=''; else $alt = ' alt';
	$status_alt = '';
	$status_seo = '';
	$row_class='';

	$seo_suffix = Utils::getSEOText($metadata, false);
	$alt_text = Utils::getSEOText($metadata, true);
	
	if($row['current_seo_suffix'] == $seo_suffix['raw']) $status_seo = ' updated';
	if($row['current_alt_text'] == $alt_text['raw']) $status_alt = ' updated';
		
	if(@$_POST['update']=='on'){
		$query2 = 'UPDATE photos SET seo_suffix=\''.$mysqli->real_escape_string($seo_suffix['raw']).'\', alt_text=\''. $mysqli->real_escape_string($alt_text['raw']) . '\' WHERE photo_id='.$row['photo_id'];
		$update = $mysqli->query($query2);
//			echo $query2 . '<br>';
		if($update === FALSE) $row_class = ' class="Error"';
	}
		
		echo '<tr'.$row_class.'">';
		echo '<td class="photo_id'.$alt.'"><a href="http://woophy.com/photo/'.$row['photo_id'].'" target="_blank">'. $row['photo_id'] . "</a></td>\r\n";
		echo '<td class="city'.$alt.'">'. $row['city_name'] . "</td>\r\n";
		echo '<td class="country'.$alt.'">'. $row['country_name'] . "</td>\r\n";
		echo '<td class="category'.$alt.'">'. $row['category_name'] . "</td>\r\n";
		echo '<td class="tags'.$alt.'">'. $row['tags'] . "</td>\r\n";
		echo '<td class="keywords'.$alt.'">'. $row['keywords'] . "</td>\r\n";
		echo '<td class="seo'.$alt.$status_seo.'">'. $seo_suffix['linked'] . "</td>\r\n";
		echo '<td class="alt_text'.$alt.$status_alt.'">'. $alt_text['linked'] . "</td>\r\n";
		echo '</tr>';

	//ob_flush();
}
?>
</table>

<div id="form_div">
	<form style="float:left" action="./seo/" method="post">
		<input type="hidden" name="go" value="true" />
		<label for="start_id">Starting photo ID</label><input style="width:100px" type="text" name="start_id" value="<?php echo $start_id ?>" />
		<label for="limit">How many records to show</label><input style="width:50px" type="text" name="limit" value="<?php echo $limit ?>" />
		<label for="update">Write these to database</label><input type="checkbox" name="update" />
		<input type="submit" value="go" />
	</form>
	<div style="float:left;margin:7px 0 0 50px;"><a href="./fluff_words.php" target="_blank">Edit Fluff Text</a></div>
<?php
if(@$_POST['update']=='on'){
		$elapsed_time = (microtime(true)- $start_time);
		echo '<div class="notice">Update sucessful! '.$num_rows.' records updated in about '.(int)$elapsed_time.' seconds. (~'.round($elapsed_time/$num_rows, 4).' sec/row)</div>';
	}
?>
	<div style="clear:both"></div>
</div>

<div style="height:50px;"></div>
</body>
</html>