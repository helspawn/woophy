<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}

	include CLASS_PATH.'Page.class.php';
	
	$pages = array(	array('label'=>'about','inc'=>'about'),
					array('label'=>'contact','inc'=>'contact'),
					array('label'=>'terms of use','inc'=>'termsofuse'),
					array('label'=>'FAQ','inc'=>'faq'),
					array('label'=>'advertising','inc'=>'advertising'),
					array('label'=>'donate','inc'=>'donate'),
					array('label'=>'press','inc'=>'press'));
	
	$param = explode('/', rtrim(REQUEST_PATH, '/'));
	if(count($param) >= 1){
		$a = explode('.', Utils::stripQueryString($param[0]));
		$param[0] = $a[0];
	}else $param[0] = '';

	$param[0] = mb_strtolower($param[0]);
	$include_file = INCLUDE_PATH.$param[0].'.php';

	if(file_exists($include_file)){
		$html_docs = '<div id="MainContent" class="clearfix"><div id="MainColumn">'.PHP_EOL;
		$currentpage = 0;
		foreach($pages as $k => $v){
			if($v['inc']==$param[0]){
				$currentpage = $k;
				break;
			}
		}	
		$page = new Page();
		$page->setTitle(ucwords($pages[$currentpage]['label']));
		
		ob_start();
		$html_docs .= '<div class="MenuBar clearfix">';
		$html_docs .= '<div id="SubNav" class="clearfix"><ul>'.PHP_EOL;
		foreach($pages as $k => $v){
			$class = $currentpage==$k ? 'active' : 'inactive';
			if(mb_strlen($v['label'])<10){
				if($currentpage==$k)$class .= ' active_narrow';
				else $class .= ' narrow';
			}
			$html_docs .= '<li><a href="'.ROOT_PATH.$v['inc'].'" class="'.$class.'">'.$v['label'].'</a></li>';
		}
		$html_docs .= '</ul></div>'.PHP_EOL;
		$html_docs .= '</div>'.PHP_EOL;

		include $include_file;
		$html_docs .= ob_get_clean();


		$html_docs .= '</div> <!-- end MainColumn -->'.PHP_EOL;

		$html_docs .= '<div id="RightColumn">';
		
		if($currentpage==0 ||  $currentpage==2 || $currentpage==3){
			include CLASS_PATH.'Location.class.php';
			$location = new Location();
			$xml_country = $location->getRandomCountryCode();
			
			if($country_code = $xml_country->country_code){
				include CLASS_PATH.'City.class.php';
				$city = new City();
				$xml_cities = $city->getCitiesByCountryCode($country_code);
				$cities = $xml_cities->city;
				if(count($cities)>0){
					$html = '<div class="Section"><div class="Header clearfix"><h2>Popular cities in '.$cities[0]->country_name.'</h2></div>';
					$n = count($cities);
					$i = 0;
					foreach($cities as $c){		
						$i++;
						$html .= '<div class="Excerpt '.($i==$n?'last ':'').'clearfix"><a class="Thumb sprite" href="'.ROOT_PATH.'photo/'.urlencode($c->photo_id).'"><img src="'.Utils::getPhotoUrl($c->user_id, $c->photo_id, 'thumb').'" /></a>';
						$html .= '<div class="ExcerptContent"><div><a href="'.ROOT_PATH.'search?&country_code='.$country_code.'&city_name='.urlencode($c->name).'" class="Title">'.$c->name.'</a></div>';		
						$num = (int)$c->photo_count;
						$html .=  '<div><span class="strong">'.$num.'</span> photo'.($num==1?'':'s');
						$html .= '</div></div></div>';
					}

					$html .= '</div>';
					$html_docs .= $html;
				}
			}
		}else if($currentpage==1){
			
			$js = 'var map=new MapSideBar({map_id:\'MapSidebar\',marker_image_dir:Page.root_url+\'images/map_markers/\', base_url:Page.root_url,';
			$js .= 'latitude:\'51.9167\',';
			$js .= 'longitude:\'4.5\',';
			$js .= 'city_id:\'2984658\'});';

			$page->addInlineScript($js);
			
			$html_docs .= '<div id="MapSidebar" class="Section"></div>';
			$html_docs .= '<div class="Section"><div class="Header clearfix"><h2>Woophy.com</h2></div>';
			$html_docs .= '<div class="Content"><div class="clearfix"><span class="label">City:</span><span>Rotterdam</span></div><div class="clearfix"><span class="label">Country:</span><span>Netherlands&nbsp;&nbsp;<a class="flag flag-nl replace" href="'.ROOT_PATH.'country/NL">NL</a></span></div><div class="clearfix"><span class="label">Latitude:</span><span>51° 55\' 0\'\' N</span></div><div class="clearfix"><span class="label">Longitude:</span><span>4° 30\' 0\'\' E</span></div></div></div>';

		}else if($currentpage==5){
			
			$page->addInlineScript('jQuery(document).ready(function(){var e=jQuery(\'#tshirt_preview\');if(e.length){var el=e[0], orgsrc = el.src;jQuery(\'img.tn\').each(function(){var src = this.src.replace(/tn_/i,\'\');new Image().src=src;jQuery(this).mouseover(function(){el.src = src}).mouseout(function(){el.src = orgsrc});});}});');
			
			$html_docs .= '<div id="TShirtImage" class="Section"><img id="tshirt_preview" src="'.ROOT_PATH.'images/tshirt/tshirt_1.jpg" /></div>';
			$html_docs .= '<div id="TShirtForm" class="Section clearfix"><a name="tshirt"></a><div class="Header clearfix"><h2>Buy a Woophy T-shirt</h2></div>';
			$html_docs .= '<div class="clearfix"><form class="tshirt" action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_s-xclick" /><input type="hidden" name="hosted_button_id" value="194385" /><input type="hidden" name="on0" value="Size" /><div class="DropdownContainer"><select name="os0" class="select sprite"><option value="Size S">Size S / &euro;14,99</option><option value="Size M">Size M / &euro;14,99</option><option value="Size L">Size L / &euro;14,99</option><option value="Size XL">Size XL / &euro;14,99</option><option value="Size XXL">Size XXL / &euro;14,99</option></select></div><input name="submit" type="image" class="input_img" src="https://www.paypal.com/en_US/i/btn/btn_buynowCC_LG_global.gif" alt="" border="0" /></form>';
			$html_docs .= '<div class="Thumbs"><img src="'.ROOT_PATH.'images/tshirt/tshirt_tn_2.jpg" width="50" height="50" class="tn" /><img src="'.ROOT_PATH.'images/tshirt/tshirt_tn_3.jpg" width="50" height="50" class="tn" /><img src="'.ROOT_PATH.'images/tshirt/tshirt_tn_4.jpg" width="50" height="50" class="tn" /><img src="'.ROOT_PATH.'images/tshirt/tshirt_tn_5.jpg" width="50" height="50" class="tn" /><img src="'.ROOT_PATH.'images/tshirt/tshirt_tn_6.jpg" width="50" height="50" class="tn" /><img src="'.ROOT_PATH.'images/tshirt/tshirt_tn_7.jpg" width="50" height="50" class="tn" /></div></div>';
			$html_docs .= '<ul class="DottedTop"><li>Available in all sizes</li><li>100 % Cotton</li><li>Only &euro; 14,99 (excluding postage and packing)</li><li>Safe payment through paypal&trade;</li><li>International delivery using priority postal services</li><li>Available in any color as long as it is green</li></ul></div>';

			$html_docs .= '<div id="DVD" class="Section"><div class="Header clearfix"><h2>Buy a Woophy DVD</h2></div><p><img src="'.ROOT_PATH.'images/holland_heritage.png" alt="Holland Heritage" width="126" height="192" /><b>DVD Dutch windmills</b><br/>(&euro;8.50 of each DVD goes to Woophy)</p><p>The DVD can be played on any DVD player (PAL/NTSC) and is voiced over in Dutch and English and subtitled in Spanish, French, Italian, German, Japanese, Korean, Russian and Chinese.</p><p>The price of the DVD is <b>&euro;14.95</b> excluding <b>&euro;2.85</b> postage through international priority shipping and remember <b>&euro;8.50</b> of this amount goes directly to Woophy.</p><form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="VYRB22XVA2NQA"><input type="image" src="https://www.paypal.com/en_US/NL/i/btn/btn_buynowCC_LG.gif" style="border:0" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!"><img alt="" border="0" src="https://www.paypal.com/nl_NL/i/scr/pixel.gif" width="1" height="1"></form></div>';
			//$html_docs .= '<div class="sidebar_section"><a name="donation"></a><h2>Make a Donation</h2><p>If you don\'t want to buy a T-shirt but would like to support Woophy anyway you can always make a donation.</p><form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_s-xclick" /><input type="hidden" name="hosted_button_id" value="724220" /><input type="image" src="https://www.paypal.com/en_GB/i/btn/btn_donate_LG.gif" style="border:0" name="submit" alt="" /><img alt="" border="0" src="https://www.paypal.com/nl_NL/i/scr/pixel.gif" width="1" height="1" /></form></div>';


		}else if($currentpage==4 || $currentpage==6){
			include CLASS_PATH.'Status.class.php';
			$status = new Status();
			$xml_status = $status->getStatus();
			$html_docs .= '<div class="WoophyStats Section"><div class="Header clearfix"><h2>Statistics</h2></div><div class="Content">';
			$html_docs .= '<div class="clearfix"><span class="label">Photos uploaded:</span><span>'.number_format((int)$xml_status->num_of_photos,0).'</span></div>';
			$html_docs .= '<div class="clearfix"><span class="label">Photos viewed:</span><span>'.number_format((int)$xml_status->num_of_views,0).'</span></div>';
			$html_docs .= '<div class="clearfix"><span class="label">Cities:</span><span>'.number_format((int)$xml_status->num_of_cities,0).'</span></div>';
			$html_docs .= '<div class="clearfix"><span class="label">Members:</span><span>'.number_format((int)$xml_status->num_of_users,0).'</span></div>';
			$html_docs .= '</div></div>';
		}

		if($currentpage!=5){
			$html_docs .= '<div class="AdContainer" id="azk76744"></div>';
		}
		$html_docs .= '</div></div><!-- end RightColumn, MainContent -->';
				
		echo $page->outputHeader(2);
		echo $html_docs;
		echo $page->outputFooter();

	}else include INCLUDE_PATH.'404.php';
?>