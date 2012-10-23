<?php
if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden');exit();}

$user = ClassFactory::create('User');
$widget = ClassFactory::create('Widget');
$count = 0;

$notifications = array(

	array(
		'id' 				=> 'Mixed',
		'title'				=> ucfirst($access->getUserName()).(substr($access->getUserName(), strlen($access->getUserName())-1)=='s'?'\'':'\'s').' Woophy Feed',
		'data'	 			=> $user->getNotifications(NOTIFICATION_PHOTO_COMMENTS+NOTIFICATION_PHOTO_FAVORITES+NOTIFICATION_FAVORITE_SUBMISSIONS+NOTIFICATION_FANS, 2, TRUE),
		'list_empty'		=> array('title'=>'Here you will find information about your photos, your favorite photographers and people who like your pictures','text'=>'','url'=>''),
		'get_more'			=> array('title'=>'','text'=>'','url'=>'')
  	),
	array(
		'id' 				=> 'NewComments',
		'title'				=> 'New Comments on your Photos',
		'data'	 			=> $user->getNotifications(NOTIFICATION_PHOTO_COMMENTS),
		'list_empty'		=> array('title'=> 'Here you will find the latest comments on your photos', 'text'=>'Want comments? Upload new photos!', 'url'=>ROOT_PATH.'account/upload'),
		'get_more'			=> array('title'=> 'Want more comments?', 'text'=>'Upload new photos!', 'url'=>ROOT_PATH.'account/upload')

	),
	array(
		'id'				=> 'NewFavorites',
		'title'				=> 'New Favorites on your Photos',
		'data'				=> $user->getNotifications(NOTIFICATION_PHOTO_FAVORITES),
		'list_empty'		=> array('title'=>'Here you will see which photos of you are liked by others', 'text'=>'Want some favorites? Upload new photos!', 'url'=>ROOT_PATH.'account/upload'),
		'get_more'			=> array('title'=>'Want more favorites?', 'text'=>'Upload new photos!', 'url'=>ROOT_PATH.'account/upload')
	),
	array(
		'id'				=> 'NewFans',
		'title'				=> 'Your Newest Fans',
		'data'				=> $user->getNotifications(NOTIFICATION_FANS),
		'list_empty'		=> array('title'=>'Here you will find the members who added you to their favorite list', 'text'=>'Want some fans? Upload new photos!', 'url'=>ROOT_PATH.'account/upload'),
		'get_more'			=> array('title'=>'Want more fans?', 'text'=>'Upload new photos!', 'url'=>ROOT_PATH.'account/upload')
	),
	array(
		'id'				=> 'PhotosByFavorites',
		'title'				=> 'Latest Uploads by your Favorites',
		'data'				=> $user->getNotifications(NOTIFICATION_FAVORITE_SUBMISSIONS),
		'list_empty'		=> array('title'=>'Here you will see the activity of your favorite photographers', 'text'=>'Find some favorite photographers here!', 'url'=>ROOT_PATH.'photo/browse/editors'),
		'get_more'			=> array('title'=>'You don\'t have enough favorite photographers', 'text'=>'Find more favorite photographers here!', 'url'=>ROOT_PATH.'photo/browse/editors')
	)
);

//first get the most amount of items any of the feeds will contain
$max_rows=0;
foreach($notifications as $feed) if(count($feed['data'])>$max_rows) $max_rows = count($feed['data']); 

echo '<div class="FeedWidget" id="Notifications">';
echo '<div class="FeedsContainer">';
foreach($notifications as $feed):
	$feedout = $widget->outputFeed($feed['id'], $feed['title'], $feed['data'], $feed['list_empty'], $feed['get_more'], min(5, $max_rows));
	echo $feedout['html'];
	$count++;
endforeach;
	
echo '</div><!-- end FeedsContainer -->';

echo '<div class="FeedNav unselectable">';
echo '<span class="PagingArrow"><a href="#" class="feed_prev">prev</a></span>';
echo '<ul class="pagination">';
for($x=0;$x>count($notifications);$x++):
	echo '<li><a href="#'.$x.'">'.($x+1).'</a></li>';
endfor;
echo '</ul> <!-- end pagination -->';
echo '<span class="PagingArrow"><a href="#" class="feed_next">next</a></span>';
echo '</div><!-- end FeedNav -->';
echo '</div><!-- end Feeds -->';
