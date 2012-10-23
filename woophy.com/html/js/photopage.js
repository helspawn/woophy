var PhotoPage = {
	$img:null,
	$img_div:null,
	photo_id: null,
	is_popup: false,
	current_index: 0,
	has_prev: false,
	has_next: false,
	id_prev: null,
	id_next: null,
	comments_posted:0,	
	
	toggle_editors_pick: function(el){
		jQuery.get(
			Page.root_url+'services?method=woophy.photo.'+(jQuery(el).hasClass('Active')?'removeEditorsPick':'addEditorsPick')+'&photo_id='+PhotoPage.photo_id,
			function(data){
				var err = jQuery('err', data);
				if(err.length>0) alert(err[0].getAttribute('msg'));
				else jQuery(el).toggleClass('Active');
				jQuery('a', el).attr('title',jQuery(el).hasClass('Active')?'Remove Editor\'s Pick':'Make Editor\'s Pick');
			}
		);
	},
	
	add_rating : function(el){
		var rate = jQuery('#StarRating li a').index(el)+1;
		if(typeof rate == 'number'){
			jQuery.get(
				Page.root_url+'services?method=woophy.photo.addRating&photo_id='+PhotoPage.photo_id+'&value='+rate,				
				function(data){
					var err = jQuery('err', data);
					if(err.length==0){
						jQuery('div#FeedbackRating').html('You rated this '+rate);
						jQuery(el).focus();
					}else jQuery('div#FeedbackRating').html(err[0].getAttribute('msg'));
				}
			);
		}
	},

	post_comment: function(form){
		var url = jQuery(form).attr('action'),
		data = Form.get_data_object(form);
		jQuery('#SubmitComment').attr('disabled','disabled');
		jQuery('#Comments .Error').remove();
		var $wait = jQuery('<div class="Wait clearfix"><img src="'+Page.root_url+'images/loading_animated_white_small.gif" /><span>posting comment</span></div>').prependTo('#frmpostcomment');
		jQuery.post(url, data, function(result){
			if(result.error_code){
				var $error = jQuery('<div class="Error">' + result.error_message + '</div>').bind('click', PhotoPage.hide_error);;
				jQuery('#Comments').append($error);
				jQuery('#PhotoPopup .CommentPost textarea').focus(PhotoPage.hide_error);
				$wait.remove();
				jQuery('#SubmitComment').removeAttr('disabled');
			}else{
				jQuery('p.NoComments').remove();
				$wait.remove();
				jQuery('input[name=pid]').attr('value', parseInt(jQuery('input[name=pid]').attr('value'))+1);
				jQuery('<div class="Comment"><div class="yellow_bg PositionAbsolute"></div><div class="CommentContent PositionAbsolute"><div class="Meta clearfix"><div class="Date">'+result.comment_date+'</div> by <a href="'+Page.root_url+'member/'+result.poster_name+'" target="_top">'+result.poster_name+'</a></div><p>'+result.comment+'</p></div></div>').prependTo('#CommentsHolder', form);
				jQuery('#SubmitComment').removeAttr('disabled');
				jQuery('textarea',form).val('');
				setTimeout(jQuery.proxy(PhotoPage.fade_highlight, PhotoPage), 2000);
				PhotoPage.comments_posted++;
				if(PhotoPage.comments_posted%2==0) jQuery('#Comments .Comment:first').addClass('green_bg');
			}
		});
	},
	
	
	fade_highlight: function(){
		jQuery('div.Comment div.yellow_bg').fadeOut(500, function(){
			jQuery(this).remove();
		});
	},
	
	truncate_comments: function(){
		var $h = jQuery('#CommentsHolder'),
		$e = jQuery('#Comments');
		while($h.outerHeight() > $e.outerHeight()) jQuery('.Comment:last-child', $h).remove();	
	},
	
	update_address_bar: function(){
		var href = window.location.href,
		idx = href.search(/&photo\-/i);
		if(idx!=-1){
			var id = parseInt(href.substring(idx+7));
			if(!isNaN(id) && PhotoPage.photo_id == id)return;//no need to update
		}
		href = href.replace(/&photo\-[0-9]*/gi, '');
		if(href.indexOf('#')==-1)href += '#'
		window.location.href = href + '&photo-' + PhotoPage.photo_id;
	},
	
	reset_address_bar: function(){
		var href = window.location.href;
		href = href.replace(/&photo\-[0-9]*/gi, '');
		//if(href.substr(href.length - 1) == '#')href = href.slice(0, -1);//removing hash symbol (#) causes a page reload
		window.location.href = href;
	},
	
	update_paging: function(){
		//if a parent gallery jQuery object isn't available, the photo will be presented without any paging
		PhotoPage.index = jQuery(Gallery.ids[Gallery.current_gallery]).index(PhotoPage.photo_id);
		//console.log(Gallery.ids[Gallery.current_gallery]);

		PhotoPage.has_prev = (PhotoPage.index>0);
		PhotoPage.has_next = (PhotoPage.index<Gallery.ids[Gallery.current_gallery].length-1);
		prev_button = jQuery('#PhotoPopupContainer #Prev');
		next_button = jQuery('#PhotoPopupContainer #Next')

		prev_button.unbind('click');
		next_button.unbind('click');

		if(PhotoPage.has_prev){
			PhotoPage.id_prev = Gallery.ids[Gallery.current_gallery][PhotoPage.index-1];
			prev_button.removeClass('disabled');
			prev_button.bind('click', function(){PhotoPage.get_photo(PhotoPage.id_prev);});
		}else{
			PhotoPage.id_prev = null;
			prev_button.addClass('disabled');
		}

		if(PhotoPage.has_next){
			PhotoPage.id_next = Gallery.ids[Gallery.current_gallery][PhotoPage.index+1];
			next_button.removeClass('disabled');
			next_button.bind('click', function(){PhotoPage.get_photo(PhotoPage.id_next);});
		}else{
			PhotoPage.id_next = null;
			next_button.addClass('disabled');
		}
		PhotoPage.update_address_bar();
	},
	
	center_photo: function(){
		var img_h = PhotoPage.$img.height();
		// workaround for weird jQuery bug with images already loaded, returning 0x0 dimensions
		if(img_h==0){
			var img = new Image();
    		img.src = PhotoPage.$img.attr('src');
    		img.onload = function(){jQuery('#Image a').css({'height':+img.height+'px','margin-top':(480-img.height)/2+'px'});};
		}else{
			jQuery('#Image a').css({'height':+img_h+'px','margin-top':(480-img_h)/2+'px'});		
		}
	},
	
	get_photo: function(photo_id){
		PhotoPage.$img.addClass('js_hidden');
		PhotoPage.$img_div.addClass('Loading');
		jQuery('#PhotoPopup').load(
			Page.root_url+'photopopup?photo_id='+photo_id+'&viewmode=2',
			function(){
				PhotoPage.init(photo_id);
			}
		);
	},
	get_prev: function(){
		if(PhotoPage.id_prev!=null) PhotoPage.get_photo(PhotoPage.id_prev);
	},
	
	get_next: function(){
		if(PhotoPage.id_next!=null) PhotoPage.get_photo(PhotoPage.id_next);		
	},
	hide_error:function(){
		var $e = jQuery('#Comments .Error');
		if($e.length){
			jQuery('#PhotoPopup .CommentPost textarea').off('focus', Page.hide_error);
			jQuery('#Comments .Error').remove();
			jQuery('#PhotoPopup .CommentPost textarea').focus();
		}
	},
	bind_arrow_keys: function(){
		jQuery(document).bind('keydown', function(e){if(e.keyCode==37){PhotoPage.get_prev(); return false;}}).bind('keydown', function(e){if(e.keyCode==39){PhotoPage.get_next(); return false;}});		
	},
	unbind_arrow_keys: function(){
		jQuery(document).unbind('keydown').unbind('keydown');
	},
	img_load:function(){
		PhotoPage.center_photo();
		PhotoPage.$img.removeClass('js_hidden');
		PhotoPage.$img_div.removeClass('Loading');
	},
	init: function(id){
		PhotoPage.$img_div = jQuery('#PhotoPopup #Image');
		PhotoPage.$img = jQuery('img', PhotoPage.$img_div);
		if(PhotoPage.$img.length>0){
			if(!PhotoPage.$img[0].complete){
				PhotoPage.$img.addClass('js_hidden');
				PhotoPage.$img.bind('load', jQuery.proxy(PhotoPage.img_load,PhotoPage));
			}else{			
				PhotoPage.img_load();
			}
			PhotoPage.unbind_arrow_keys();
		}
		PhotoPage.photo_id = parseInt(id);
//		jQuery('.ActionButton#EditorsPick', '#StarRating li a.sprite').unbind('click');

		jQuery('.ActionButton#EditorsPick').bind('click', function(e){PhotoPage.toggle_editors_pick(this);e.preventDefault();});
		jQuery('#StarRating li a.sprite').bind('click', function(e){PhotoPage.add_rating(this);e.preventDefault();});
		if(typeof Gallery == 'object'){
			if(Gallery.popup){
				Social.init();
				jQuery('#PhotoPopup #frmpostcomment').append('<input type="hidden" name="xhr" class="sendable" value="true" />').bind('submit', function(){PhotoPage.post_comment(this);return false;});
				if(jQuery('#CloseButton').length==0) jQuery('<div id="CloseButton" class="sprite replace">X</div>').appendTo('#PhotoPopup #HeaderBar').bind('click', function(){jQuery.colorbox.close();PhotoPage.reset_address_bar()});
				if(Gallery.current_gallery!=null){
					PhotoPage.bind_arrow_keys();
					PhotoPage.update_paging();
				}
				jQuery('#PhotoPopup .CommentPost textarea').bind('focus', PhotoPage.unbind_arrow_keys);
				jQuery('#PhotoPopup .CommentPost textarea').bind('blur', PhotoPage.bind_arrow_keys);
				PhotoPage.truncate_comments();
			}
		}
 		Ads.loadAdzerk('azk92219', 6338, 12);
	}
};