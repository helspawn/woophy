/*
 * Instatiate 2 jQuery Callbacks objects in which we can add all the init methods from different JS includes.
 *  _pre fires when DOM is loaded (but before images) and _post fires after all images are loaded.  
 */
var init_global_pre = jQuery.Callbacks();
var init_global_post = jQuery.Callbacks();

var Page = {
	title: '',
	page_context: 'body',
	is_home: false,
	root_url: '',
	lights: 'on',
	$switchanchor: null,
	lightsout_class: 'lightsout',
	
	show_footer: function(){
		jQuery('#FooterContainer').removeClass('nodisplay');
	},
	
	hide_footer: function(){
		jQuery('#FooterContainer').addClass('nodisplay');
	},
	
	init_login_window: function(){
		Utils.ajax_link(jQuery('.LoginButton'));
		jQuery('.LoginButton').colorbox({scrolling:false,speed:100,onComplete:Page.on_open_login,width:429+30});//height:280,
	},
	
	on_open_login: function(){
		if(jQuery('#CloseButton', '#LoginForm').length==0) jQuery('<div id="CloseButton" class="sprite replace">X</div>').prependTo('#colorbox #LoginForm').click(function(){jQuery.colorbox.close();});
		Form.init();
		Form.init_login();
		Utils.ajax_link(jQuery('.ForgotPassword a'));
		jQuery('.ForgotPassword a').click(function(){Page.show_loader('#cboxLoadedContent #LoginForm', 'white');jQuery('#cboxLoadedContent').load(jQuery(this).attr('href') + ' #MainContent', Page.on_open_forgot_password);return false;});
	},
	
	on_open_forgot_password: function(){
		jQuery.colorbox.resize();
		if(jQuery('#CloseButton', '#ForgotPasswordForm').length==0) jQuery('<div id="CloseButton" class="sprite replace">X</div>').prependTo('#colorbox #ForgotPasswordForm').click(function(){jQuery.colorbox.close();});
		jQuery('a.BackToLogin').click(function(){Page.show_loader('#cboxLoadedContent #ForgotPasswordForm', 'white');jQuery('#cboxLoadedContent').load(jQuery(this).attr('href') + ' #MainContent', Page.on_open_login);return false;});
		Form.props['frmForgotPassword'] = {'method':'post', 'callback':function(data){jQuery('#frmForgotPassword').remove();jQuery('<p>'+data.message+'</p>').appendTo('.FormArea')}};
		Form.init();
		Page.init();
	},
	
	check_popup_anchor: function(){
		var href = window.location.href,
		idx = href.search(/&photo\-/i);
		if(idx!=-1){
			if(typeof PhotoPage == 'object' && typeof Gallery == 'object'){
				if(!Gallery.popup){
					var id = parseInt(href.substring(idx+7));
					if(!isNaN(id)){
						PhotoPage.photo_id = id;
						Gallery.get_photo_popup();
					}
				}
			}
		}
	},
	
	show_loader: function(target, bg_color){
		var target_height= jQuery(target).height() + 'px';
		jQuery(target).html('<div style="text-align:center;width:100%;height:'+target_height+';line-height:'+target_height+';margin-auto;"><img style="vertical-align:middle;" src="'+Page.root_url+'images/loading_animated_'+bg_color+'.gif"></div>')
	},
	/*
	toggle_lights: function(){
		if(Page.lights == 'on'){
			jQuery('body').addClass(Page.lightsout_class);
			Page.$switchanchor.removeClass('on').addClass('off');
			Page.lights = 'off';
			Cookie.set('lights','off',999);
		}else{
			jQuery('body').removeClass(Page.lightsout_class);			
			Page.$switchanchor.removeClass('off').addClass('on');
			Page.lights = 'on';
			Cookie.set('lights','on',999);
		}
	},
	init_lightswitch: function(){
		if(jQuery('#lightswitch').length==0){
			var $switchcontainer = jQuery('<li id="lightswitch" class="sprite"></li>').appendTo('#TopLinks');
			this.$switchanchor = jQuery('<a class="sprite" href="#"></a>').addClass(this.lights).appendTo($switchcontainer);
			this.$switchanchor.bind('click', Page.toggle_lights);		
		}
	},
	*/
	init: function(){
		Page.init_login_window();
		Page.check_popup_anchor();
		//Page.init_lightswitch();
		//if(Cookie.read('lights') == 'off'){
		//	Page.toggle_lights();
		//}
	}
	
};

init_global_pre.add(Page.init);

var Form = {
	
	page_context: 'body',
	text_input_default_value: new Array(),
	props: new Array(),

	clear_default_text_input_value: function(el){
		index = jQuery(el).index(this.page_context+' input[alt],'+this.page_context+' textarea[alt]');
		if(el.value == Form.text_input_default_value[index]){
			jQuery(el).val('');
			jQuery(el).attr('value', '')
		}
	},

	restore_text_input_default_value: function(el){
		index = jQuery(el).index(this.page_context+' input[alt],'+this.page_context+' textarea[alt]');
		if(el.value == ''){
			jQuery(el).val(Form.text_input_default_value[index]);
		}
	},
	
	clear_default_values: function(form){
		$els = jQuery(this.page_context+' input[alt],'+this.page_context+' textarea[alt]');
		$els.each(function(i,el){
			if(jQuery(el).val()==Form.text_input_default_value[i] && jQuery(el).val()!='') jQuery(el).val('');
		});
	},
	
	add_password_label: function(parent){
		$container = jQuery('<div class="PasswordContainer PositionRelative clearfix"></div>').appendTo(parent);
		$input = jQuery('input', parent).first();
		$input.appendTo($container);
		$label = jQuery('<div class="PasswordLabel PositionAbsolute">Password</div>').appendTo($container);
		// CHECK IF BROWSER HAS PRE-FILLED THE FIELD, HIDE LABEL IF SO
		if($input.val() != '') $label.hide();
		$label.bind('click', function(){Form.hide_password_label($container)});
		$input.bind('focus', function(){$label.hide()});
		$input.bind('blur', function(){Form.show_password_label($container)});
	},
	
	replace_content: function(parent, type){
		$el = jQuery('input', parent).first();
		if($el.val()=='' || $el.val()==$el.attr('alt')){
			$new_el = jQuery('<input type="' + type + '" />').insertAfter($el);
			jQuery($el.get(0).attributes).each(function(){
				if(this.nodeName != 'type'){
					$new_el.attr(this.nodeName, this.nodeValue);	
				}
			});
			$el.unbind().remove();
			if(type == 'password') $new_el.get(0).focus();
			if($new_el.val()=='' && type=='text')$new_el.val($new_el.attr('alt'));
			$new_el.bind('focus', function(){Form.replace_element($new_el.parent().get(0), 'password')});
			$new_el.bind('blur', function(){Form.replace_element($new_el.parent().get(0), 'text')});
			return $new_el;	
		}
	},

	hide_password_label: function($container){
		jQuery('.PasswordLabel', $container).hide();
		jQuery('input', $container).focus();
	},
	
	show_password_label: function($container){
		if(jQuery('input', $container).val() == '') jQuery('.PasswordLabel', $container).show();
	},
	
	do_quick_search: function(form){
		$input = jQuery('input#input', form);
		$dropdown = jQuery('select', form);
		$input.attr('name', $dropdown.val());
		if($input.val() != $input.attr('alt')) form.submit();
	},
	
	reset_form: function(){
		$form = jQuery(this).parent('form');
		jQuery('input[type!="hidden"][type!="button"][type!="submit"], select, textarea', $form).each(function(){
			$this = jQuery(this);
			if($this.is('select')) $this.prop('selectedIndex',0);
			else $this.val('');
		});
		$form.submit();
	},
	
	swap_submit: function(context){
		$submit = jQuery('input[type="submit"]', context);
		if($submit.length >0){
			$button = jQuery('<input type="button" />').attr('id', $submit.attr('id')).attr('class', $submit.attr('class')).attr('name', $submit.attr('name')).attr('value', $submit.attr('value'));
			$button.replaceAll($submit);
			return $button;
		}else return false;
	},
	/*
	swap_file_input: function(context){
		$file = jQuery('input[type="file"]', context);
		$file.bind('change',this.update_file_name);
		jQuery('<div id="FileSelectButton" class="LightGreenButton">Select Photo</div>').insertAfter($file).bind('click',function(){Form.select_file($file);});
		jQuery('<div id="FileName">No photo selected</div>').insertAfter($file).bind('click',function(){Form.select_file($file);});
		
	},

	select_file: function($file_input){
		$file_input.click();
	},

	update_file_name: function(){
		if(this.value != ''){
			filepath =  this.value.split( /[\\\/]/);
			file_name = filepath[filepath.length-1];
			jQuery('#FileName',jQuery(this).parents('form')).text(file_name);
		}
	},*/

	init_login: function(){
		Form.props['frmLogin'] = {'method':'post', 'callback':function(data){if(data.redirect && data.redirect!='')window.location.href=data.redirect; else window.location.reload()}};
	},
	
	
	
	init_add_folder: function(context){
		var $folder_select_container = jQuery('#FolderSelectContainer', context);
		jQuery('<div id="AddFolderButton" class="LightGreenButton">Add New Folder</div>').appendTo($folder_select_container).bind('click',function(){Form.add_folder(this, $folder_select_container);});
	},
	
	add_folder: function(button, $folder_select_container){
		var $input_area = $folder_select_container.parents('.InputArea'),
		$add_folder_form = jQuery('.AddFolderForm', $input_area);
		if($add_folder_form.length==0){
			jQuery('div.Message', $input_area).remove();
			jQuery('<form class="AddFolderForm clearfix" method="get" action="' +Page.root_url+ 'account/photos/edit"><label for="folder_name">New folder name</label><input type="text" class="text sendable" name="folder_name"/><input type="hidden" name="output_mode" value="json" class="sendable" /><input type="submit" name="submit_addfolder" class="submit OrangeButton sendable" value="Save" /></form>').appendTo($input_area);
			jQuery('.AddFolderForm', $input_area).bind('submit', function(){Form.do_add_folder(this, $folder_select_container);return false;});
			jQuery('.AddFolderForm input.text', $input_area).focus();
		}else{
			jQuery('input.text', $add_folder_form).focus();
		}
	},
	do_add_folder: function(form, $folder_select_container){
		$this = jQuery(form);		
		qs = this.concat_ajax_url($this);
		jQuery.get($this.attr('action')+qs, function(data){
			data = jQuery.parseJSON(data);
			if(data.error) jQuery('<div class="Error">' + data.error + '</div>').appendTo($this);
			else if(data.message){
				jQuery('<div class="Message orange">' + data.message + '</div>').insertAfter($this);
				$this.remove();
				$folder_dropdown = jQuery('#FolderDropdown select');
				if($folder_dropdown.length==0){
					jQuery('.Notice', $folder_select_container).remove();
					$dropdown_container = jQuery('<div id="FolderDropdown" class="DropdownContainer"></div>').prependTo($folder_select_container);
					$folder_dropdown = jQuery('<select name="folder_id" class="sprite"><option value="0" class="mainfolder">Main folder</option></select>').appendTo($dropdown_container);
				}
				$folder_dropdown.append('<option selected="true" value="'+data.folder_id+'">'+data.folder_name+'</option>').val(data.folder_id);
			}
		});
	},
	
	submit_social_form: function(form, form_id){
		$this = jQuery(form);
		qs = this.concat_ajax_url($this);
		error = false;
		jQuery.get($this.attr('action')+qs, function(data){
			$response = jQuery('rsp', data);
			if($response.attr('stat')=='fail'){
				error = true;
				output = '<span class="Error">' + jQuery('err', $response).attr('msg') + '</span>';
			}else{
				output = 'Your message has been sent!';
			}
			jQuery('.feedback', $this).html(output);
			if(!error) setTimeout(jQuery.proxy(Social.hide, Social, form_id), 2000);
		});
	},

	concat_ajax_url: function(form, fragment){
		var qs = fragment ? '&' : '?';
		jQuery('.sendable', form).each(function(){
			$el = jQuery(this);
			qs += $el.attr('name')+'='+encodeURIComponent($el.val())+'&';
		});
		return qs;
	},
	
	get_data_object: function(form){
		data = {};
		jQuery('.sendable', form).each(function(){
			$el = jQuery(this);
			data[$el.attr('name')] = $el.val();
		});
		return data;
	},
	
	submit_xhr: function(form){
		var url = jQuery(form).attr('action'),
		form_id = jQuery(form).attr('id'),
		props = Form.props[form_id],
		method = 'POST';
		if(typeof props != 'undefined'){
			if(props['method'] != 'undefined') method = props['method'];
		}
		var data = Form.get_data_object(form);
		jQuery('.ErrorRow, .Error').html('');
		var $wait = jQuery('<div class="Wait clearfix"><img src="'+Page.root_url+'images/loading_animated_white_small.gif" /><span>Please wait...</span></div>').appendTo('#'+form_id+' .SubmitRow');

		jQuery.ajax({'type':method, 'url':url, 'data':data, 'success': function(result){
			if(result.error_code){
				jQuery('.ErrorRow, .Error').html(result.error_message);
				$wait.remove();
				jQuery.colorbox.resize();
			}else{
				if(typeof props.callback == 'function') props.callback(result);
			}
		}});
	},

	init: function(){
		var i=0;
		if(arguments.length > 0) Form.page_context = arguments[0];
		else Form.page_context = '';
		Form.text_input_default_value = [];
		jQuery('form', this.page_context).each(function(){
			$form = jQuery(this);
			$form.unbind('submit');
			if($form.hasClass('xhr')){
				if(jQuery('input[name=xhr]', $form).length==0) jQuery('<input type="hidden" class="sendable" name="xhr" value="true">').prependTo($form);
				if(jQuery('input[name=output_mode]', $form).length==0) jQuery('<input type="hidden" class="sendable" name="output_mode" value="json">').prependTo($form);
				$form.bind('submit', function(e){e.preventDefault();Form.submit_xhr(this);});
			}
			$form.bind('submit', function(){Form.clear_default_values($form);});

			jQuery('input[alt],textarea[alt]', this).each(function(){
				$el = jQuery(this);
				Form.text_input_default_value[i] = $el.attr('alt');
				if($el.attr('type')=='password'){
					$el = Form.add_password_label($el.parent().get(0));
				}else{
					if($el.val()=='')$el.val($el.attr('alt'));
					$el.bind('focus', function(){Form.clear_default_text_input_value(this)}).bind('blur', function(){Form.restore_text_input_default_value(this)});			
				}
				i++;
			});
			if(jQuery(this).is('#Search')){
				var form = this,$button = Form.swap_submit(form);
				if($button)$button.bind('click', function(){Form.do_quick_search(form)});
				jQuery(form).bind('submit', function(){Form.do_quick_search(form);return false;});
			}
			//if(jQuery(this).is('#AddPhoto')) Form.swap_file_input(this);
			if(jQuery(this).is('#AddPhoto')) Form.init_add_folder(this);
			
			jQuery('.ResetForm').bind('click', Form.reset_form);
		});
	}
};

init_global_pre.add(Form.init);

var WoophyMap = {
	main_map_id:		'MapHome',
	search_container:	null,
	expanded:			false,
	MainMap : 			null,

	expand : function(){
		var $map = jQuery('#' + WoophyMap.main_map_id),
		$map_inner_container = jQuery('#MapInnerContainer'),
		$map_container = jQuery('#MapContainer'),
		$right_column = jQuery('#RightColumn'),
		$main_column = jQuery('#MainColumn'),
		width_closed = 640-8,//-2*4px padding
		width_expanded = 960-8,
		height_closed = 420,
		height_expanded = 600;
		
		if(!this.expanded){
			//must define expanded width and height before the animate, else Google Map loses the plot.
			$map.css('width',width_expanded+'px');
			$map.css('height',height_expanded+'px');
			$right_column.animate({'margin-top': (height_expanded+141)+'px'}, 250, function(){//159
				$main_column.css('overflow','visible');
				$map_inner_container.animate({'width':width_expanded+'px'},250);
				$map_inner_container.animate({'height':height_expanded+'px'},250);
				$map_container.animate({'width':width_expanded+'px'}, 250);
			});		
			jQuery('#ExpandButton').addClass('Expanded');
			this.expanded = true;
		}else{
			
			$map_inner_container.animate({'width':width_closed+'px'},250, function(){$map.css('width',width_closed+'px');});
			$map_inner_container.animate({'height':height_closed+'px'},250, function(){$map.css('height',height_closed+'px');});

			$map_container.animate({'width':width_closed+'px'}, 250, function(){
				$main_column.css('overflow','hidden');
				$right_column.animate({'margin-top':'18px'}, 250);
			});
			
			jQuery('#ExpandButton').removeClass('Expanded');
			this.expanded = false;
		}
		//WoophyMap.MainMap.googlemap.minZoom=b?2:1;
		if(WoophyMap.MainMap && google)google.maps.event.trigger(WoophyMap.MainMap.googlemap,'resize');
		
	},
	
	onGetCities : function(data){
		//console.log(data);
	},

	onGetPhotos : function(data){
		if(Gallery)Gallery.reset();
	},
	
	update: function(el){
		$el = jQuery(el);
		var rel = $el.attr('rel');
		if(rel){
			var a = rel.split(':'), f = {};
			if(a.length==2){
				f[a[0]] = a[1];
				if(WoophyMap.MainMap)WoophyMap.MainMap.getCities(f);
				return false;
			}
		}
		return true;
	},
	
	init_map_links: function(context){
		jQuery('a.MapLink', context).bind('click', function(){return WoophyMap.update(this);});	
	},
	
	init: function(){
		if(jQuery('#'+WoophyMap.main_map_id).length==1){
			WoophyMap.MainMap = new MapHome({
				map_id:				WoophyMap.main_map_id, 
				marker_image_dir:	Page.root_url+'images/map_markers/', 
				tooltip_id:			'MarkerTooltip', 
				service_url: 		Page.root_url+'services', 
				photo_limit:		9
			});
			jQuery(WoophyMap.MainMap).on(MapHome.events.MARKER_CLICK + ' ' + MapHome.events.GET_CITIES, function(){
				Gallery.filters = this.urlencodeFilters();
				this.getPhotos(0, Gallery.reset);
				var f = this.getFilters();
				for(var k in f){
					if(Search.setFilter(k, f[k]))break;
				}
			});
			jQuery('#ExpandButton').click(function(){WoophyMap.expand();return false;});	
			WoophyMap.init_map_links(document.body);
		}
	}
};
init_global_pre.add(WoophyMap.init);

var Search = {

	search_advanced : null,
	search_simple	: null,
	advanced_open : false,
	/*
	toggle_advanced : function(){
		if(Search.advanced_open){
			Search.search_advanced.slideUp('fast');
			Search.search_simple.slideDown('fast');
			Search.advanced_open = false;
		}else{
			Search.search_advanced.slideDown('fast');
			Search.search_simple.slideUp('fast');
			Search.advanced_open = true;
		}
	},*/

	clear : function(){
		jQuery('input[name="SearchValue"]').val('');
		WoophyMap.MainMap.getCities(null);
		Gallery.filters=null;
		Gallery.reset();
	},
	getFilter : function(){
		var f = {};
		f[jQuery('select[name="SearchKey"]').val()] = jQuery('input[name="SearchValue"]').val();
		return f;
	},
	setFilter : function(key, value){
		var f = Search.getFilter();
		if(f[key]==value)return true;
		var $e = jQuery('select[name="SearchKey"]').val(key);
  		if(jQuery('option', $e).filter(function(){
           return this.value === key;
        }).length !== 0){
			jQuery('input[name="SearchValue"]').val(value);
			return true;
        }
		return false;
	},
	init: function(){
		if(jQuery('#SearchContainer').length>0){
			Search.search_container = jQuery('#SearchContainer');
			jQuery('input[name="SearchSubmit"]', Search.search_container).bind('click', function(){WoophyMap.MainMap.getCities(Search.getFilter()); return false;});
			jQuery('input[name="SearchClear"]', Search.search_container).bind('click', function(){Search.clear();});
			//Search.search_advanced = jQuery('#SearchAdvanced');
			//Search.search_advanced.hide();
			//Search.search_advanced.removeClass('js_nodisplay');
			//jQuery('#LinkAdvanced').bind('click', Search.toggle_advanced);
			//jQuery('#LinkSimple').bind('click', Search.toggle_advanced);
		}
	}
};

init_global_pre.add(Search.init);

var Social = {
	
	add_favorite: function(){
		var $this = jQuery(this),
		link = $this.attr('rel'),
		type = link.substring(link.indexOf('method=woophy.')+14, link.indexOf('.addToFavorites'));
		jQuery.get(link, function(data){
			var $errs = jQuery('err', data), message = 'This ' + type + ' has been added to your favorites';
			if($errs.length>0) message = $errs.attr('msg');
			alert(message);
		});
		return false;
	},
	
	init_send_message: function(){
		Utils.ajax_link(jQuery('#SendMessageButton a'));
		jQuery('#SendMessageButton a').colorbox({scrolling:false,transition:'fade',speed:100,onComplete:Social.on_open_send_message,width:429+30});
		Form.props['SendMessageForm'] = {'method':'GET', 'callback':function(result){jQuery('#SendMessageForm').remove();jQuery('<p>'+result.message+'</p>').appendTo('#SendMessage')}};
	},

	on_open_send_message: function(){
		if(jQuery('#CloseButton', '#SendMessage').length==0) jQuery('<div id="CloseButton" class="sprite replace">X</div>').prependTo('#colorbox #SendMessage').bind('click', function(){jQuery.colorbox.close();});
		Page.init();
		Form.init();
	},
	
	init: function(){
		jQuery('#AddFavorite a.enabled', '#PinterestButton a').unbind('click');
		Utils.hide_ajax_url(jQuery('#AddFavorite a.enabled')).bind('click', Social.add_favorite);
//		jQuery('#PinterestButton a').bind('click', function(){jQuery('<script type="text/javascript" charset="UTF-8" src="http://assets.pinterest.com/js/pinmarklet.js?r='+Math.random()*99999999+'"></script>').appendTo('body'); return false;});
		jQuery('#PinterestButton a').bind('click', function(){
			var page_url = window.location.href;
			var media_url =  jQuery(this).attr('rel');
			var page_description = jQuery(this).attr('alt');
			var pinterest_url = 'http://pinterest.com/pin/create/button/?url='+encodeURIComponent(page_url)+'&media='+encodeURIComponent(media_url)+'&description='+encodeURIComponent(page_description)+'&ref='+encodeURIComponent(page_url);
			var pinterest_window = window.open(pinterest_url, 'pinWin','width=600,height=260');
			return false;
		});

		Social.init_send_message();
	}	
};

init_global_pre.add(Social.init);

var Images = {
	hide_missing: function(){

		if(typeof(arguments[0]) == 'string'){
			$imgs = jQuery("img", arguments[0]);
		}else{
			$imgs = jQuery("img");
		}
		$imgs.error(function(){
			$this = jQuery(this);
    		$this.css('visibility','hidden');
    		$this.parent().removeClass('ImageContainer'); 
		});
	},
	
	remove_flag_text: function(){
		jQuery('.sprite.flag').text('');
	},
	
	remove_award_text: function(){
		jQuery('.sprite.award').text('');
	}
};
init_global_pre.add(Images.remove_flag_text);
init_global_pre.add(Images.remove_award_text);
//init_global_pre.add(Images.hide_missing);//added to footer

var Ads = {
	keywords:null,
	loadAdzerk: function(container_id, zone_id, format_id){
		ados.run = ados.run || [];
		ados.run.push(function() {
			ados_add_placement(2559, 17178, container_id, format_id).setZone(zone_id);
			if(Ads.keywords!=null) ados_setKeywords(Ads.keywords);
			ados_load();
		});
	},

	initAdzerk: function(){
		var p="http", d="static";
		if(document.location.protocol=="https:"){p+="s";d="engine";}
		var z=document.createElement("script");
		z.type="text/javascript";
		z.async=true;
		z.src=p+"://"+d+".adzerk.net/ados.js";
		var s=document.getElementsByTagName("script")[0];
		s.parentNode.insertBefore(z,s);

		switch(jQuery('body').attr('id')){
			case 'HomePage':
				Ads.loadAdzerk('azk82889', 6339, 5);
				break;
			default:
				Ads.loadAdzerk('azk76744', 6320, 5);
				break;			
		}
	}
};

init_global_pre.add(Ads.initAdzerk);

var Cookie = {
	set: function(name,value,days) {
		if(days) {
			var date = new Date();
			date.setTime(date.getTime()+(days*24*60*60*1000));
			var expires = "; expires="+date.toGMTString();
		}
		else var expires = "";
		document.cookie = name+"="+value+expires+"; path=/";
	},

	read: function(name) {
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for(var i=0;i < ca.length;i++) {
			var c = ca[i];
			while (c.charAt(0)==' ') c = c.substring(1,c.length);
			if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
		}
		return null;
	},

	erase: function(name) {
		this.set(name,"",-1);
	}
};

var Utils = {
	hide_ajax_url: function($el){
		$el.each(function(){
			var $this = jQuery(this),
			href = $this.attr('href');
			$this.attr('href', '#');
			$this.attr('rel', href);
		});
		return $el;
	},
	
	ajax_link: function($el){
		var url = $el.attr('href');
		if(url) {
			if(url.indexOf('viewmode=1') == -1) {
				var s = url.indexOf('?') >-1 ? '&' : '?';
				$el.attr('href', url+s+'viewmode=1');
			}
		}
	}
};

/*****
	V2 code from LIBRARY.js
*****/
function ToolTip(elementId, label){
	jQuery('#'+elementId.replace('#','')).each(function(i){
		var timeout, $this = jQuery(this),
		pos = $this.offset(), tt = jQuery(document.createElement('div')).css({top:(pos.top + $this.height()) + 'px', left:Math.max(0, pos.left) + 'px'}).attr('class', 'Tooltip').html(label).hide();
		function show(){
			window.clearTimeout(timeout);
			tt.fadeIn(200);
		}	
		$this.mouseover(function(){
			window.clearTimeout(timeout);
			timeout = window.setTimeout(show, 500);
		}).mouseout(function(){
			window.clearTimeout(timeout);
		}).click(function(){
			show();
			return false;
		});
		jQuery(document.body).append(tt).click(function(){
			tt.hide();
		});
	});
}
function PreviewThumb(){
	jQuery('#show_preview').click(function(){
		var $h = jQuery('#preview_holder');
		if($h.length){
			function showError(){
				$h.html('<div class="Error">Photo not found.</div>');
			}
			var id = jQuery('input#photo_id').val();
			if(id && id.length){
				if(!isNaN(parseInt(id))){
					$h.html('<img src="'+Page.root_url+'images/loading_animated_white_small.gif" /><div>Please wait...</div>');
					jQuery.get(Page.root_url+'services?method=woophy.photo.getUrl&photo_id='+id+'&size=medium', function(data) {		
						var url = jQuery('url', data).text();
						if(url.length)$h.html('<img src="'+url+'">');
						else showError();
					});
					return;
				}
			}
			showError();
		}
	});	
}

function SelectList(param){
	if(param){
		this.inputObj = param.inputObj;
		this.listItems = [];
		this.selectedItemIndex = -1;
		this.$holder = jQuery(document.createElement('div')).addClass('selectlist').attr('id','selectlist').css({position:'absolute',display:'none'});

		this.mouseUpEvent = jQuery.proxy(this.hideList, this);
		this.keyDownEvent = jQuery.proxy(this.onKeyDownInput, this);
		this.keyUpEvent = jQuery.proxy(this.onKeyUpInput, this);
		
		this.setEnabled(true);

		var e = this.$holder;
		jQuery(document).ready(function(){
			jQuery(document.body).append(e);
		});
	}
};
SelectList.prototype = {
	setEnabled : function(bln){
		if(this.enabled != bln){
			this.enabled = bln;
			if(bln){
				jQuery(document).on('mouseup', this.mouseUpEvent);
				jQuery(this.inputObj).on({keydown:this.keyDownEvent, keyup:this.keyUpEvent});
			}else{
				jQuery(document).off('mouseup', this.mouseUpEvent);
				jQuery(this.inputObj).off({keydown:this.keyDownEvent, keyup:this.keyUpEvent});
			}
		}
	},
	onKeyDownInput : function (evt){
		var code = evt.which || evt.keyCode;
		if(code == 13) {
			if(this.selectedItemIndex > -1) this.setInputValue();
			this.hideList();
		}
	},
	onKeyUpInput : function (evt){
		
		var doselect = this.$holder.css('display') == 'block';
		var tgt = evt.target || evt.srcElement;
		if(tgt === this.inputObj){//moz
			var code = evt.which || evt.keyCode;
			
			if(this.listItems.length > 0){
				switch(code){
					case 37: //left arrow
					case 38: //up arrow
						if(doselect && this.selectedItemIndex > 0) this.setSelectedIndex(this.selectedItemIndex-1);
						break;
					case 39: //right arrow
					case 40: //down arrow
						if(doselect && this.selectedItemIndex < this.listItems.length-1)this.setSelectedIndex(this.selectedItemIndex+1);
						break;
					case 13://enter
						break;
					default:
						this.getList();
				}
			}else this.getList();
		}
	},
	getList : function (){
		//implemented in subclass
	},
	onMouseOverItem : function(evt){
		var idx = jQuery(evt.currentTarget).index();
		if(idx>-1){
			this.setSelectedIndex(idx);
		}
	},
	setInputValue : function(){
		if(this.listItems[this.selectedItemIndex])this.inputObj.value = this.listItems[this.selectedItemIndex];
	},
	setSelectedIndex : function(idx){
		if(this.selectedItemIndex != idx){
			if(idx < this.listItems.length){
				var rows = jQuery('tr', this.$holder);//this.holder.firstChild.rows;
				if(rows[this.selectedItemIndex])rows[this.selectedItemIndex].className = 'inactive';
				if(rows[idx])rows[idx].className = 'active';
				this.selectedItemIndex = idx;
				this.setInputValue();
				jQuery(this).trigger('selectItem',[(this.listItems[idx] ? this.listItems[idx] : null)]);
			}
		}
	},
	positionList : function(){
		var pos = jQuery(this.inputObj).offset();
		this.$holder.css({top:(pos.top + this.inputObj.offsetHeight - 1) + 'px', left:pos.left + 'px'});
	},
	showList : function(){
		this.positionList();
		this.$holder.show();
	},
	hideList : function(){
		this.$holder.hide();
	}
};

/*
	param:{
		divObj,
		count_items,
		offset,
		limit,
		service_url,
		page_forward,
		page_backward
	}
*/

function BaseArchive(param){
	if(param){
		this.count_items = 0;
		this.limit = 0;
		this.offset = 0;
		this.output_mode = 'xml';
		jQuery.extend(this, param);
		this.enablePageButtons();
		jQuery(this.page_forward).click(jQuery.proxy(this.pageForward, this));
		jQuery(this.page_backward).click(jQuery.proxy(this.pageBackward, this));
		this.getArchive();
	}
}
BaseArchive.prototype = {
	getArchive : function(){
		jQuery.get(this.service_url+'&offset='+this.offset+'&limit='+this.limit+'&output_mode='+this.output_mode, jQuery.proxy(this.onGetArchive, this));
	},
	onGetArchive : function(xml, success){
		//implemented in subclass
	},
	pageBackward : function(){
		var _offset = Math.max(0, Math.min(this.offset,this.count_items)-this.limit);
		if(_offset != this.offset){
			this.offset = _offset;
			this.getArchive();
		}
		return false;
	},
	pageForward : function(){
		var _offset = Math.max(0, this.offset+this.limit);
		if(_offset < this.count_items){
			this.offset = _offset;
			this.getArchive();
		}
		return false;
	},
	enablePageButtons : function(){
		this.enablePageButton(this.page_forward, this.offset+this.limit < this.count_items);
		this.enablePageButton(this.page_backward, this.offset > 0);
	},
	enablePageButton : function(e, bln){
		var $e = jQuery(e); 
		bln?$e.removeClass('inactive'):$e.addClass('inactive');
	}
};
/*
 *	end V2 code
 */
// fire init_global_pre callback functions when DOM is loaded
jQuery(document).ready(function(){
	init_global_pre.fire();
});
// only call init_global_post callback functions if something must be initialized *after* images are loaded
jQuery(window).load(function(){
	init_global_post.fire();
});
