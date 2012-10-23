<?php
if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden');exit();}

$blog = ClassFactory::create('Blog');
$cache_id = 'rightcolumn_feeds';
$htmlstr = $blog->getFromCache($cache_id);
if($htmlstr == FALSE){

$widget = ClassFactory::create('Widget');
$photo = ClassFactory::create('Photo');
$user = ClassFactory::create('User');



$feeds = array(
	array(
		'id' 				=> 'WoophyBlog',
		'title'				=> 'Woophy Blog',
		'data'	 			=> $blog->getRecentPostsByCategoryId(Blog::CATEGORY_ID_NEWSLETTER, 5, TRUE),
	),
	array(
		'id'				=> 'WoophyTwitter',
		'title'				=> 'Woophy Tweets',
		'data'				=> $widget->getTwitterFeed('woophy',5),
	),
	array(
		'id'				=> 'NewMembers',
		'title'				=> 'New Members',
		'data'				=> $user->getRecent(0, 5, true),
	),
	array(
		'id' 				=> 'EditorsPicks',
		'title'				=> 'Editors\' Picks',
		'data'	 			=> $photo->getRecentEditorsPicksFromCache(5,TRUE),
	 )
);


$htmlstr = '<div class="FeedWidget" id="Feeds">';
$htmlstr.= '<div class="FeedsContainer">';
foreach($feeds as $feed):
	$feedout = $widget->outputFeed($feed['id'], $feed['title'], $feed['data']);
	$htmlstr.= $feedout['html'];
endforeach;
$htmlstr.= '</div><!-- end FeedsContainer -->';

$htmlstr.= '<div class="FeedNav unselectable">';
$htmlstr.= '<span class="PagingArrow"><a href="#" class="feed_prev">prev</a></span>';
$htmlstr.= '<ul class="pagination">';
for($x=0;$x>count($feeds);$x++):
	$htmlstr.= '<li><a href="#'.$x.'">'.($x+1).'</a></li>';
endfor;
$htmlstr.= '</ul> <!-- end pagination -->';
$htmlstr.= '<span class="PagingArrow"><a href="#" class="feed_next">next</a></span>';
$htmlstr.= '</div><!-- end FeedNav -->';
$htmlstr.= '</div><!-- end Feeds -->';

$blog->saveToCache($cache_id, $htmlstr, false, 1800);//store in cache for 30 minutes
}
echo $htmlstr;
