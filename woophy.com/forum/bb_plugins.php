<?php
if (!defined('INCLUDED776')) die ('Fatal error.');

/* last images */
//if($action=='')include($pathToFiles.'addon_lastimages.php');
/* --last images */

/* Who's Online */
include($pathToFiles.'addon_whosonline.php');
/* --Who's Online */

/* Smilies addon */
if(($action=='ptopic' or $action=='pthread' or $action=='editmsg' or $action=='editmsg2' or $action=='pmail') or (isset($_POST['prevForm']) and trim($_POST['postText'])!='') or $action=='displaysmilies') include ($pathToFiles.'addon_smilies.php');
/* --Smilies addon */

/* Preview addon */
if(isset($_POST['prevForm']) and $_POST['prevForm']==1) include($pathToFiles.'addon_preview2.php');
/* --Preview addon */


/* RSS addon */
if($action=='rss')include($pathToFiles.'addon_rss2.php');
/* --RSS addon */

if($action=='userinfo'){//redefine because of $dbUserScheme
	function parseUserInfo_email($val){
		if ($GLOBALS['row'][3]==1) return $GLOBALS['usEmail']; elseif($GLOBALS['user_id']>0) return '<a href="mailto:'.$val.'">'.$val.'</a>'; else return '';
	}
	function parseUserInfo_minibb_num_posts($val){
		return $val-$GLOBALS['row'][10];
	}
	function parseUserInfo_minibb_num_topics($val){
		if($val=='0') return ''; else return $val;
	}
	function parseUserInfo_registration_date($val){
		if(strstr($val,'-')) return convert_date($val); else return convert_date(date('Y-m-d H:i:s',$val));
	}

}

?>