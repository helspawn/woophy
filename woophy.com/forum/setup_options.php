<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004-2006 Paul Puzyrev, Sergei Larionov. www.minibb.net
Latest File Update: 2006-Apr-13
*/

$DB='mysql';

$DBhost=MYSQL_HOST;
$DBname=MYSQL_DBASE;
$DBusr=MYSQL_USER;
$DBpwd=MYSQL_PASSWORD;
$DBSLAVEhost=MYSQL_SLAVE_HOST;

$Tf='minibbtable_forums';
$Tp='minibbtable_posts';
$Tt='minibbtable_topics';
//$Tu='minibbtable_users';
$Tu='users';
$Ts='minibbtable_send_mails';
$Tb='minibbtable_banned';

$admin_usr='__*__';//not used
$admin_pwd='__*__';//not used
$admin_email=NOREPLY_EMAIL_ADDRESS;

$main_url=rtrim(FORUM_URL,'/');

//added by MG:
$img_url = ABSURL.'images';
$img_path = ABSPATH.'html'.DIRECTORY_SEPARATOR.'images';
$css_url = ABSURL.'css';
$tpl_url = '';

//$bb_admin='bb_admin.php?';
$bb_admin='forum_admin?';

$lang='eng';
$skin='default';
$sitename='Forum';

$emailadmin=0;
$emailusers=1;
$userRegName='_A-Za-z0-9 ';
$l_sepr='-';

$post_text_maxlength=10240;
$post_word_maxlength=50;
$topic_max_length=100;
$viewmaxtopic=25;
$viewlastdiscussions=25;
$viewmaxreplys=20;
$viewmaxsearch=25;
$viewpagelim=5000;
$viewTopicsIfOnlyOneForum=0;

$protectWholeForum=0;
$protectWholeForumPwd='pwd';

$postRange=60;

//$dateFormat='d M y';
$dateFormat='j M y H:i';

$cookiedomain='';
$cookiename='miniBBsite';
$cookiepath='';
$cookiesecure=FALSE;
$cookie_expires=108000;
$cookie_renew=1800;
$cookielang_exp=2592000;

/* New options for miniBB 1.1 */

$disallowNames=array('Anonymous', 'Fuck', 'Shit');
//$disallowNamesIndex=array('admin'); // 2.0 RC1f

/* New options for miniBB 1.2 */
$sortingTopics=0;
$topStats=4;
$genEmailDisable=0;

/* New options for miniBB 1.3 */
$defDays=60;
$userUnlock=0;

/* New options for miniBB 1.5 */
$emailadmposts=0;
$useredit=86400;

/* New options for miniBB 1.6 */
//$metaLocation='go';
//$closeRegister=1;
//$timeDiff=21600;

/* New options for miniBB 1.7 */
$stats_barWidthLim='31';

/* New options for miniBB 2.0 */
/*
$dbUserSheme=array(
'username'=>array(1,'username','login'),
'user_password'=>array(3,'user_password','passwd'),
'user_email'=>array(4,'user_email','email'),
'user_icq'=>array(5,'user_icq','icq'),
'user_website'=>array(6,'user_website','website'),
'user_occ'=>array(7,'user_occ','occupation'),
'user_from'=>array(8,'user_from','from'),
'user_interest'=>array(9,'user_interest','interest'),
'user_viewemail'=>array(10,'user_viewemail','user_viewemail'),
'user_sorttopics'=>array(11,'user_sorttopics','user_sorttopics'),
'language'=>array(14,'language','language'),
'num_topics'=>array(16,'num_topics',''),
'num_posts'=>array(17,'num_posts',''),
'user_custom1'=>array(18,'user_custom1','user_custom1'),
'user_custom2'=>array(19,'user_custom2','user_custom2'),
'user_custom3'=>array(20,'user_custom3','user_custom3')
);
*/
$dbUserSheme=array(
'username'=>array(1,'user_name','login'),
'user_password'=>array(2,'password','passwd'),
'user_email'=>array(4,'email','email'),
'user_viewemail'=>array(5,'anonymous','user_viewemail'),
'user_sorttopics'=>array(28,'minibb_sorttopics','user_sorttopics'),
'language'=>array(29,'minibb_language','language'),
'num_topics'=>array(30,'minibb_num_topics',''),
'num_posts'=>array(31,'minibb_num_posts','')
);
$dbUserId='user_id';
$dbUserDate='registration_date';
$dbUserDateKey=26;
$dbUserAct='minibb_activity';
$dbUserNp='user_newpasswd';
$dbUserNk='user_newpwdkey';

$enableNewRegistrations=FALSE;
$enableProfileUpdate=FALSE;

$indexphp='index.php?';
$useSessions=FALSE;
$usersEditTopicTitle=FALSE;
$pathToFiles=FORUM_PATH;
//$pathToFiles='./';//'./'

$includeHeader=$pathToFiles.'header.php';
$includeFooter=$pathToFiles.'footer.php';

//$emptySubscribe=TRUE;
$allForumsReg=TRUE;
//$registerInactiveUsers=TRUE;
$mod_rewrite=TRUE;
$enableViews=TRUE;
//$userInfoInPosts=array('user_custom1');//??? 
//$userDeleteMsgs=1;

$description='miniBB is a free complete PHP forum software, bulletin board, having very strong bulletin board idea beside. Modern free forums script is mostly too large, too cool, sometimes funny and cumbersome, written by freelancers. mini bb is free from these lacks due its clear concepts of the whole search engine friendly forums solution, also freelance avalaible. mysql is the default database for minibb. Open source bulletin board mostly\'s oriented to users; having a website design concept behind, small bulletin board becomes further leader in building, integrating and embedding forums into website. miniBB supports multilingual content, language packs, rss, postgresql, mssql, bad words, smilies, instant online modules, mod rewrite, SEO. By bulletin bird, we mean the easiest forums solution for a website, speed, simplicity. Whatever your community, discussion is related to, you can download our bulletin forum software and use it on your site! www.miniBB.net has all useful software downloads for anyone using our bulletin board PHP solution.';

//$startIndex='index.php'; // or 'index.html' for mod_rewrite
$startIndex='index.html';
$manualIndex='index.php?action=manual'; // or 'manual.html' for mod_rewrite

//$enableGroupMsgDelete=TRUE;
$post_text_minlength=2;
$loginsCase=TRUE;

?>
