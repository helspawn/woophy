<?php
function reorder($publication_date, $post_id=NULL, $post_order=NULL){
	if(isset($publication_date)){
		$result = DB::query('SELECT post_id, post_order FROM blog_newsletters WHERE newsletter_publication_date = \''.$publication_date.'\' ORDER BY post_order ASC;');
		$posts = array();
		if($result){
			while($row = DB::fetchAssoc($result)){
				$posts[] = array('id'=>$row['post_id'],'order'=>$row['post_order']);
			}
			if(isset($post_id, $post_order)){
				foreach($posts as $k=>$v){
					if($v['id'] == $post_id) {
						$e = array_splice($posts, $k,1);//cut
						$e[0]['order'] = $post_order;
						array_splice($posts, $e[0]['order']-1,0,$e);//insert
						break;
					}
				}
			}
			//look for gaps in order:
			$do_reorder = false;
			reset($posts);
			foreach($posts as $k=>&$v){
				if($k+1 != $v['order']){
					$do_reorder = true;
					$v['order'] = $k+1;
				}
			}
			if($do_reorder){
				foreach($posts as $post){
					DB::query('UPDATE blog_newsletters SET post_order = \''.$post['order'].'\' WHERE post_id = \''.$post['id'].'\' AND newsletter_publication_date = \''.$publication_date.'\';');
				}
			}
		
		}else echo 'Error';	
	}
}
if(isset($_POST['submit_add'],$_POST['publication_date'],$_POST['post_id'],$_POST['post_order'])){
	DB::query('INSERT INTO blog_newsletters (newsletter_publication_date, post_id, post_order) VALUES (\''.$_POST['publication_date'].'\',\''.$_POST['post_id'].'\',\''.$_POST['post_order'].'\')');
}
if(isset($_POST['post_deletes'],$_POST['publication_date'])){
	if(is_array($_POST['post_deletes']) && count($_POST['post_deletes'])>0){
		DB::query('DELETE FROM blog_newsletters WHERE post_id IN ('.implode(',', $_POST['post_deletes']).') AND newsletter_publication_date= \''.$_POST['publication_date'].'\';');
		reorder($_POST['publication_date']);
	}
}
if(isset($_POST['publication_date'],$_POST['post_id'],$_POST['post_order'])){//after add post or reorder
	reorder($_POST['publication_date'],$_POST['post_id'],$_POST['post_order']);
}

if(isset($_POST['publication_date']) && strlen($_POST['publication_date'])>0){
	$publication_date = $_POST['publication_date'];
	echo '<form name="form_edit" method="post" action="">';
	echo '<input type="hidden" value="'.$publication_date.'" name="publication_date" />';
	echo '<input type="hidden" value="" name="post_id" />';//used for reorder
	echo '<input type="hidden" value="" name="post_order" />';//used for reorder
	echo '<fieldset>';
	echo '<legend>Newsletter '.$publication_date.'</legend><div style="padding:10px;">';
	echo '<table>';
	
	$query = 'SELECT  blog_posts.post_id, post_title, blog_newsletters.post_order FROM blog_newsletters INNER JOIN blog_posts ON blog_newsletters.post_id = blog_posts.post_id WHERE newsletter_publication_date = \''.$publication_date.'\' ORDER BY blog_newsletters.post_order ASC;';
	$options = '';
	$result = DB::query($query);
	$num = 0;
	if($result){
		$num = DB::numRows($result);
		if($num==0){
			echo '<tr><td>No posts have been added yet!</td></tr>';
		}else{
			echo '<tr><th colspan="2">Order</th><th>Post title</th><th>Delete</th><tr>';
			$i = 0;
			while($row = DB::fetchAssoc($result)){
				//store options for later use:
				$options .= '<option';
				if($i == $num-1) $options .= ' selected="true"';
				$options .= ' value="'.($row['post_order']+1).'">After '.$row['post_title'].'</option>';
				
				echo '<tr><td>';
				if($i == 0) echo '&nbsp;';
				else echo '<input onclick="reorder('.$row['post_id'].','.($row['post_order']-1).')" type="button" style="font-family:courier" value="&uarr;" />';
				echo '</td><td>';
				if($i < $num-1)echo '<input onclick="reorder('.$row['post_id'].','.($row['post_order']+1).')" type="button" style="font-family:courier" value="&darr;" />';
				else echo '&nbsp;';
				echo '</td><td>'.$row['post_title'].'</td><td><input type="checkbox" name="post_deletes[]" value="'.$row['post_id'].'"></td></tr>';
				$i++;
			}
		}
	}else echo 'Query failed';
	echo '</table>';
	if($num>0)echo '<br/><input type="submit" name="submit_delete" value="Delete selected" />';
	//echo '&nbsp;<input type="submit" name="submit_preview" value="Preview newsletter" />';
	echo '</div></fieldset>';
	echo '</form>';
	?>
<script type="text/javascript">
<!--
	function reorder(post_id, post_order){
		var f = document.forms[0];
		f.post_id.value = post_id;
		f.post_order.value = post_order;
		f.submit();
	}	
//-->
</script>
<?php
			
		if(strlen($options)>0){					
			$select_options = '<tr><td>Placement</td><td><select name="post_order"><option value="1">At the beginning</option>'.$options.'</select></td></tr>';
		}else $select_options = '';
		
		//add post by title	
		$query = 'SELECT DISTINCT post_publication_date, post_id, post_title FROM blog_posts
		WHERE blog_posts.category_id = 2 AND NOT EXISTS (SELECT * FROM blog_newsletters WHERE blog_posts.post_id = blog_newsletters.post_id) ORDER BY post_publication_date DESC;';

		$result = DB::query($query);
		if(!$result) echo 'Query failed';
		else{
			if(DB::numRows($result)>0){
				echo '<form name="form_add1" method="post" action="">';
				echo '<input type="hidden" value="'.$publication_date.'" name="publication_date" />';
				echo '<fieldset><legend>Add post by title</legend><div style="padding:10px;">';
				echo '<table>';
				echo '<tr><td>Post title</td><td>';
				echo '<select name="post_id">';
				while ($row = DB::fetchAssoc($result)) echo '<option value="'.$row['post_id'].'">'.date('Y-m-d',strtotime($row['post_publication_date'])).' | '.$row['post_title'].'</option>';
				echo '</select>';
				echo '</td></tr>';
				echo $select_options;
				echo '</table>';
				if(strlen($options)==0) echo '<input type="hidden" value="1" name="post_order" />';
				echo '<br/><input type="submit" name="submit_add" value="Add post" />';
				echo '</div></fieldset></form>';
			}
		}

		//add post by id
		echo '<form name="form_add2" method="post" action="">';
		echo '<input type="hidden" value="'.$publication_date.'" name="publication_date" />';
		echo '<fieldset><legend>Add post by id</legend><div style="padding:10px;">';

		echo '<table>';
		echo '<tr><td>Post id</td><td><input name="post_id" value="" type="text" /></td></tr>';
		echo $select_options;
		echo '</table>';
		if(strlen($options)==0) echo '<input type="hidden" value="1" name="post_order" />';
		echo '<br/><input type="submit" name="submit_add" value="Add post" />';
		echo '</div></fieldset></form>';


	}else{
		//select/start new
		echo '<form name="form_select" method="post" action="">';
		echo '<fieldset>';
		echo '<legend>Continue with newsletter</legend><div style="padding:10px;">';
		echo 'Publication date: <select name="publication_date">';
		$query = 'SELECT DISTINCT newsletter_publication_date FROM blog_newsletters ORDER BY newsletter_publication_date DESC;';
		$result = DB::query($query);
		if(!$result) echo '<option>Query failed</option>';
		else{
			if(DB::numRows($result)>0){
				while ($row = DB::fetchAssoc($result)) {
					$pd = $row['newsletter_publication_date'];
					echo '<option value="'.$pd.'">'.$pd.'</option>';
				}
			}else echo '<option>No news yet</option>';
		}
		echo '</select>';
		echo '&nbsp;<input type="submit" name="submit_select" value="Select" /></div>';
		echo '</fieldset>';
		echo '</form>';
		echo '<form name="form_select" method="post" action="">';
		echo '<fieldset>';
		echo '<legend>Start new newsletter</legend><div style="padding:10px;">';
		echo 'Publication date: <input type="text" class="datefield" name="publication_date" readonly="true" value="" />';
		echo '&nbsp;<input type="submit" name="submit_add" value="Add" /></div>';
		echo '</fieldset>';
		echo '</form>';
		?>
		<script type="text/javascript">//<![CDATA[
		var delimiter = '-';
		var f1 = document.forms[1]['publication_date'];
		var sel1 = new DateField(f1);
		sel1.dateFormatter = function(d) {return d.getFullYear() + delimiter + (d.getMonth()+1) + delimiter + d.getDate();}
//]]></script>
<?php
	}
?>