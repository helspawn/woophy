<?php
require CLASS_PATH.'Page.class.php';

$GLOBALS['woophy_page'] = new Page();

if(!isset($GLOBALS['title']) or $GLOBALS['title']=='') $GLOBALS['title']=$GLOBALS['sitename'];
$GLOBALS['woophy_page']->setTitle($GLOBALS['title']);
$GLOBALS['woophy_page']->addStyle('forum.css');
$GLOBALS['woophy_page']->addScript('forum.js');
$GLOBALS['woophy_page']->addScript('slides.min.jquery.js');
$GLOBALS['woophy_page']->addInlineScript('var root_url=\''.ROOT_PATH.'\';
init_global_pre.add(function(){
		jQuery(\'#Feeds\').slides({
			container: \'FeedsContainer\',
			preload: false,
			play: 0,
			pause: 2500,
			effect: \'slide\',
			randomize: true,
			fadeSpeed: 300,
			hoverPause: true,
			prev: \'feed_prev\',
			next: \'feed_next\',
			autoHeight: \'true\'
		});
		jQuery(\'#Feeds .pagination\').last().remove();		
		jQuery(\'#Feeds .Feed\').removeClass(\'js_hidden\');
});
');

$GLOBALS['woophy_page']->addRSS(ABSURL.'forum/rss2.php', 'Woophy Forum\'s latest discussions');

echo $GLOBALS['woophy_page']->outputHeader(2);

?>
<div id="MainContent" class="clearfix">
<div id="MainColumn">
<div id="ForumNav">
<?php
	echo isset($GLOBALS['forumsList'])?$GLOBALS['forumsList']:'';
?>
</div> <!-- end ForumNav -->
<div class="MenuBar">
<div id="SubNav"><ul class="clearfix">
<li><?php echo $GLOBALS['l_menu'][0]; ?></li>
<li><?php echo $GLOBALS['l_menu'][1]; ?></li>
<li><?php echo $GLOBALS['l_menu'][3]; ?></li>
</ul>
</div>
</div>
<div id="Forum">