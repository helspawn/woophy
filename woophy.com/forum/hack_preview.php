<?php

if(isset($_POST['prevForm'])){
include ("./lang/$lang.php");
require('./bb_func_txt.php');
ob_end_clean ();
$logged_admin=($user_id == 1);
$topicTitle2=stripslashes(textFilter($_POST['topicTitle'],$topic_max_length,$post_word_maxlength,0,1,0,0));
$postText2=stripslashes(textFilter($_POST['postText'],$post_text_maxlength,$post_word_maxlength,1,$_POST['disbbcode'],1,$logged_admin));
echo ParseTpl(makeUp('hack_preview'));
exit;
}
?>