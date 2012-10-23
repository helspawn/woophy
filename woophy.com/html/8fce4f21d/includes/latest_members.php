<fieldset>
<legend>Latest Members</legend>
<div style="padding:10px;">
<?php
$result = DB::query('SELECT users.user_id, user_name, email, last_ip, photo_count, COUNT(rating.photo_id) AS rating_count, photo_comments.poster_id, registration_date FROM users LEFT JOIN rating ON users.user_id = rating.user_id 
LEFT JOIN photo_comments ON users.user_id = photo_comments.poster_id
GROUP BY users.user_id ORDER BY users.user_id DESC LIMIT 0,100');
if($result){
	echo '<table><tr><th>user id</th><th>user name</th><th>email</th><th>ip</th><th>photo count</th><th>rating count</th><th>comments</th><th>registration date</th><th>&nbsp;</th></tr>';
	while($row = DB::fetchAssoc($result)){
		echo '<tr><td>'.$row['user_id'].'</td><td><a href="./member?user_id='.urlencode($row['user_id']).'">'.htmlspecialchars($row['user_name']).'</a></td><td>'.htmlspecialchars($row['email']).'</td><td>'.$row['last_ip'].'</td><td>'.(int)$row['photo_count'].'</td><td>'.$row['rating_count'].'</td><td>'.(isset($row['poster_id'])?'y':'n').'</td><td>'.$row['registration_date'].'</td><td><a href="./delete_account?user_name='.urlencode($row['user_name']).'&user_id='.urlencode($row['user_id']).'">Delete account</a></td></tr>';
	}
	echo '</table>';
}else echo 'Error database';
?>
</div>
</fieldset>