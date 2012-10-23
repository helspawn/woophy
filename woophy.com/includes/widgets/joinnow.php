<?php
if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden');exit();}

$widget = ClassFactory::create('Widget');
$current_photo = $widget->getJoinNowPhotoUrl();

echo '<div id="JoinNow" class="PositionRelative" style="background-image:url(\''.$current_photo.'\')"><div id="Callout" class="sprite PositionAbsolute"></div>';
echo '<div class="TextBoxContainer PositionRelative"><div class="TextBoxBackground opacity-70 PositionAbsolute"></div><div class="TextBox PositionAbsolute">';
echo '<p>Share and show your best photos and get in touch with great photographers and fans <a href="'.ROOT_PATH.'Register">read more</a></p>';
echo '<form action="'.ROOT_PATH.'Register" method="get"><input type="hidden" name="origin" value="widget"><input type="text" class="text" id="JoinnowUsername" name="username" alt="Choose username" value="Choose username"/>';
echo '<input type="submit" class="submit SubmitJoinNow OrangeButton" value="Create my account" /></form>';
echo '</div></div></div> <!-- End TextBox, TextBoxContainer, JoinNow -->';