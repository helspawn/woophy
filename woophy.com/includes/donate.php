<div class="Section">
<div class="MainHeader DottedBottom"><h1>Become a Woophy Monthly Sponsor</h1></div>
<?php
	$showForm = true;
	$action = '';
	if(isset($param[1]))$action = mb_strtolower($param[1]);
	if($action == 'success'){
		echo '<div class="Notice">Your have been successfully subscribed to a Woophy sponsorship.<br/>Thank you for supporting Woophy!</div>';
		$showForm = false;
	}else if($action == 'fail' || $action == 'failure'){
		echo '<div class="Error"><p>Oops, your subscription has failed. Please try again.</p></div>';
	}
	if($showForm):
?>
<form class="DottedBottom clearfix" action="https://www.paypal.com/cgi-bin/webscr" method="post">
<p>Support Woophy by signing up for a sponsorship for 2,- Euro a month. Sponsoring is made easy with a new subscription link via Paypal. Of course you can cancel any time you want. Sponsors could choose to do this anonymously or receive the &quot;I support Woophy&quot; icon <span class="award sprite award-9 replace"></span>.</p>
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="FXEYJSABMPAS8">
<input type="hidden" name="on0" value="icon"><p>
<div class="DropdownContainer"><select class="sprite" name="os0">
<option value="with sponsor icon">with sponsor icon</option>
<option value="no sponsor icon">no sponsor icon (anonymous)</option>
</select></div><input type="hidden" name="on1" value="user_name">
<input type="hidden" name="os1" value="<?php 
$access = ClassFactory::create('Access');
echo $access->isLoggedIn() ? htmlspecialchars($access->getUserName()) : ''?>"><br/>
<input class="input_img" type="image" src="https://www.paypal.com/en_US/i/btn/btn_subscribeCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypal.com/nl_NL/i/scr/pixel.gif" width="1" height="1" /></p>
</form>
<h2 class="MainHeader">Unsubscribe</h2>
<div class="DottedBottom">
<p>If you subscribed earlier to a woophy sponsorship and you want to stop sponsoring, you can unsubscribe here:</p>
<p>
<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_subscr-find&alias=73Z7T4TS8WRUY"><img src="https://www.paypal.com/en_US/i/btn/btn_unsubscribe_LG.gif" border="0" /></a></p>
</div>
<h2 class="MainHeader">Make a Donation</h2>
<div class="DottedBottom">
<a name="donation"></a><p>If you don't want to become a sponsor or buy a T-shirt or DVD but would like to support Woophy anyway you can always make a donation.</p>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_s-xclick" /><input type="hidden" name="hosted_button_id" value="724220" /><p><input type="image" src="https://www.paypal.com/en_GB/i/btn/btn_donate_LG.gif" style="border:0" name="submit" alt="" /><img alt="" border="0" src="https://www.paypal.com/nl_NL/i/scr/pixel.gif" width="1" height="1" /></p></form>
</div>
<h2 class="MainHeader">Bank Account</h2>
<p>You can also make a (monthly) donation directly to our bank account.</p>
<strong>Rabobank Rotterdam</strong>
<table>
<tr><td>Name</td><td>Woophy BV </td></tr>
<tr><td>Account</td><td>1232.89.084 </td></tr>
<tr><td>International bank account #(IBAN)&nbsp;</td><td>NL22 RABO 0123 2890 84 </td></tr>
<tr><td>BIC code</td><td>RABONL2U</td></tr>
</table>
<br/>
<?php
	endif;
?>
</div>