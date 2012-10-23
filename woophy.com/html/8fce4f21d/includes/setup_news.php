<?php
	
if(isset($_SESSION["sending_newsletter"])){
	unset($_SESSION["sending_newsletter"]);
}

require_once CLASS_PATH.'ClassFactory.class.php';
require_once CLASS_PATH.'Newsletter.class.php';

$news = new Newsletter();
$news->buffer = false;

//to send newsletter first choose newsletter and write html to textfile:

if(isset($_POST['submit_preview']) || isset($_POST['submit_send'])){
	$pd = $_POST['pd'];
	function getLink($name,$url){
		return '<a href="'.$url.'" target="_blank"><font size="2" face="georgia,arial,helvetica,sans" color="#FE9900">'.$name.'</font></a>';
	}
	function getParagraph($text){

		$pattern[0] = ":<img([^>]*)>:si";
		$pattern[1] = ":<hr/>:si";
		$replacement[0] = "<p><img border=\"0\"\$1></p>";
		$replacement[1] = "<hr style=\"border:none 0;border-top:1px dotted #66C029;height:1px;\">";
		$text = preg_replace($pattern, $replacement, $text);

		return '<font size="2" face="georgia,arial,helvetica,sans" color="#003900">'.$text.'</font>';
	}
	function getHeader($text){
		return '<p><font size="3" face="georgia,arial,helvetica,sans" color="#003900"><b>'.$text.'</b></font></p>';
	}
	list($year, $month, $day) = split ('[/-]', $pd);
	$html = '<html><body link="#FE9900" alink="#FE9900" vlink="#FE9900">';
	$html.= '<table width="439" cellpadding="10" cellspacing="0">';
	$html.= '<tr><td align="center" style="padding:0;border-bottom:1px dotted #66C029"><p><a href="http://www.woophy.com"><img src="'.ABSURL.'images/woophy_logo.gif" border="0"></a></p></td></tr>';
	$html.= '<tr><td style="padding:10px;border-bottom:1px dotted #66C029">';
	$html.= '<b>'.getParagraph('Newsletter '.date('l, F j, Y', mktime (0,0,0,$month,$day,$year))).'</b>';
	$html.= '</td></tr>';

	$xml_news = $news->getNewsletterByDate($pd);
	$posts = $xml_news->post;

	for ($i = 0; $i < count($posts); $i++) {
			$a = $posts[$i];
			if($i>0){
				$html .= '</tr>';
			}
			$html .= '<tr>';
			
			$html .= '<td style="padding-top:20px;padding-bottom:20px;padding-right:10px;padding-left:10px;border-bottom:1px dotted #66C029" valign="top">';
			if(strlen($a->title)>0){
				$html .= getHeader($a->title);
			}
			if(strlen($a->text)>0){
				$html.=getParagraph($a->text);
			}
			$html.='</td>';
	}
	$html.= '</tr>';
	$html.= '<tr><td style="padding-top:20px;padding-bottom:20px;padding-right:10px;padding-left:10px;">';
	$html.= getHeader('Unsubscribe from this newsletter');
	$html.= '<p>'.getParagraph('Woophy occasionally sends its members and friends newsletters. If you are a member and don\'t want to receive the Woophy newsletters anymore go to \'my account\' and change your account settings or follow the unsubscribe link at the bottom of this email.').'</p>';

	$html.= '<p>'.getParagraph('This newsletter was send to [email] through the email system of ').getLink('www.woophy.com','http://www.woophy.com').'</p>';
	$html.= '<p>'.getParagraph('For suggestions and support please contact us at ').getLink('info@woophy.com','mailto:info@woophy.com').'</p>';
	$html.= '</td></tr>';
	$html.='</table></body></html>';

	$bodynews = 'body_newsletter.txt';
	if(is_writable($bodynews)){
		if (!$handle = fopen($bodynews, 'w')) {
			$error = "$bodynews doesn't exists!";
		}else{
			if (!fwrite($handle, $html)) {
				die("$bodynews not writable");
			}else{
				fclose($handle);
				
				if(isset($_POST['submit_preview'])){
					$blnPreview = true;
				}else if(isset($_POST['submit_send'])){
					$_SESSION['sending_newsletter'] = true;
					$lognews = 'log_newsletter.txt';
					if (is_writable($lognews)){
						if($handle = fopen($lognews, "w")){
							fclose($handle);
						}//empty file
					}
					echo '<script>window.location="http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/send_news"</script>';
				}
			}
		}
	}else $error = "$bodynews doesn't exists!";
}
 
if(isset($blnPreview)){
	echo "<script>var w = window.open('preview_news.php','p');if(w){if(w.focus){w.focus();}}</script>";
}
?>
<fieldset>
<legend>Preview Newsletter</legend>
<div style="padding:10px;">
<form name="form_news_select" method="post" action="">
<?php
	if(isset($error)) echo '<p class="Error">'.$error.'</p>';

	$xml_newsletters = $news->getNewsletterDates();

	$_pd = isset($_POST['pd']) ? $_POST['pd'] : 0;
	echo '<select name="pd" id="pd">';
	foreach($xml_newsletters as $newsletter){
		$pd = $newsletter['publication_date'];
		echo "	<option value=\"".$pd."\"".($pd==$_pd?' selected="true"':'').">".$pd."</option>\n";
	}
	echo '</select>';
	//print "&nbsp;&nbsp;<input onclick=\"return confirm('Are you sure you want to send the mailing of \''+document.getElementById('pd').value+'\' to all subscribers?\\n\\nDo NOT close the browser after clicking OK')\" type=\"submit\" name=\"submit_send\" value=\"Send newsletter\"/>";

	echo "&nbsp;&nbsp;<input type=\"submit\" name=\"submit_preview\" value=\"Preview\"/>";

	if(isset($blnPreview)){
		print "<p><br/>If you're using a popup-blocker, click <a href=\"preview_news.php\" target=\"_blank\">here</a> for the preview.</p>";
	}

?>
</form>
</div>
</fieldset>