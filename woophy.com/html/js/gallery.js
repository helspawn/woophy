var Gallery = {
	is_loading: false,
	to_top_link:null,
	is_home: false,
	current_page:1,
	images_per_page: 9,
	offset_id:'',
	max_pages: 10,
	scroll_trigger_distance: -89,
	loading_bar: null,
	more_link: null,
	popup: false,
	current_photo_id: null,
	last_photo_id: null,
	is_infinite_scroll: false,
	ids: new Array(),
	current_gallery: null,
	gallery_loader_function: null,
	gallery_loader_data:null,
	gallery_type:'recent',
	gallery_sets: {},
	filters:null,
	header_text:'',
	ready: true,
	end_of_gallery:false,
	$main_gallery:null,
	$main_container:null,
	$gallery_container:null,
	$window:null,
	
	show_to_top_link: function(){
		if(Gallery.$window.scrollTop() > Gallery.$main_gallery.offset().top && Gallery.$main_container.offset().left-84>0){	
			if(Gallery.to_top_link==null){
				Gallery.to_top_link = jQuery('<div id="ToTopLink"><a href="#top" class="sprite">To Top</a></div>').appendTo('#MainContainer');
				jQuery('#ToTopLink').click(function(e){Gallery.$window.scrollTop(0);e.preventDefault()});
			}else Gallery.to_top_link.removeClass('js_nodisplay');
		}else{
			if(Gallery.to_top_link!=null)Gallery.to_top_link.addClass('js_nodisplay');
		}
	},
	
	add_photo_ids: function(gallery_id, $page){
		jQuery('img.gallery_image', $page).each(function(){
			Gallery.ids[gallery_id].push(parseInt(jQuery(this).attr('id')));
		}).click(function(e){
			if(!e.shiftKey) Gallery.get_photo_popup(this);
			return false;
		});	
	},
	
	get_photo_popup: function(){
		
		// if get_photo_popup() is called without arguments, it's launched because of the presence of a photo id in the URL's anchor. Use this.
		var $el = (arguments.length==0) ? jQuery('img[id='+PhotoPage.photo_id+']') : jQuery(arguments[0]);
		
		//if image element cannot be found in the page, it's not part of a gallery.
		if($el.length>0){
			var $gallery = $el.parents('.Gallery');
			if($gallery.length>0) Gallery.current_gallery = $gallery.attr('id');
			Gallery.current_photo_id=$el.attr('id');
			if(Gallery.popup && PhotoPage.photo_id == Gallery.current_photo_id)return;//popup already open
			PhotoPage.photo_id = Gallery.current_photo_id;
		}else if(Gallery.popup) return;//no image in the page and popup already open, no need to reload popup with same photo
		Gallery.popup = true;
		jQuery.colorbox({href:Page.root_url+'photopopup?photo_id='+PhotoPage.photo_id+'&viewmode=1',scrolling:false,transition:'fade',speed:100,close:'',onComplete:function(){Gallery.on_open_photo_popup()},onCleanup:Gallery.on_close_photo_popup,height:680,width:980});
	},
	
	on_open_photo_popup: function(){
		
		if(jQuery('.PrevNext').length==0 && Gallery.current_gallery!=null) jQuery('<div id="Prev" class="PrevNext sprite replace">Prev</div><div id="Next" class="PrevNext sprite replace">Next</div>').prependTo('#PhotoPopupContainer');
		PhotoPage.init(PhotoPage.photo_id);

		//Page.title = jQuery('title').first().text();//no need to change title because popup uses no header
		//jQuery('title').text(jQuery('title').last().text());
	},
	
	on_close_photo_popup: function(){
		Gallery.popup = false;
		PhotoPage.reset_address_bar();
		Gallery.infinite_scroll();
		//jQuery('title').text(Page.title);//no need to change title because popup uses no header
	},

	show_photo_info: function(){
		jQuery('.PhotoMetaContainer .PhotoMetaBackground', this).fadeTo('fast', 0.7).clearQueue();
		jQuery('.PhotoMetaContainer .PhotoMeta', this).fadeTo('fast', 1).clearQueue();
	},
	
	hide_photo_info: function(){
		$divs = jQuery('.PhotoMetaContainer > div', this);
		$divs.fadeTo('fast', 0);
		$divs.clearQueue();
	},
	
	get_gallery_height: function(){
		return (Gallery.$gallery_container.outerHeight() + Gallery.$gallery_container.offset().top);
	},
	
	reset: function(){
		Gallery.ids = [];
		Gallery.ready = false;
		Gallery.end_of_gallery = false;
		Gallery.gallery_div.html('');
		Gallery.remove_more_link();
		Gallery.current_page = 1;
		Gallery.offset_id='';
		Gallery.load();
		Gallery.init_photo_popup();
	},
	
	infinite_scroll: function(){
		if(Gallery.is_infinite_scroll && !Gallery.is_loading && !Gallery.popup){
			if(Gallery.get_gallery_height() < Gallery.$window.scrollTop() + Gallery.scroll_trigger_distance + Gallery.$window.height()){
				Gallery.offset_id = Gallery.get_last_photo_id(Gallery.current_page);
				Gallery.current_page++;
				
				if(Gallery.more_link==null && Gallery.ready && !Gallery.end_of_gallery) Gallery.load();
			}
		}
	},
	
	load: function(){
		var offset = (Gallery.current_page-1)*Gallery.images_per_page;
		if(Gallery.current_page <= Gallery.max_pages){			
			if(Gallery.filters == null){
				Gallery.gallery_loader_function = 'woophy.photo.getRecent';
				Gallery.gallery_loader_data = '&limit='+Gallery.images_per_page+'&offset=&offset_id='+Gallery.offset_id+'&user_id=&output_mode=html';
			}else{
				Gallery.gallery_loader_function = 'woophy.map.getPhotos';
				Gallery.gallery_loader_data = '&key='+Gallery.filters.key+'&val='+Gallery.filters.val+'&offset='+offset+'&limit='+Gallery.images_per_page+'&output_mode=html';
			}
			var content_uri = Page.root_url + 'services?&method='+Gallery.gallery_loader_function;
			var new_page = jQuery('<div id="Page-' + Gallery.current_page + '" class="Page clearfix"></div>');
			Gallery.show_loading_bar();
			Gallery.is_loading = true;
			new_page.load(content_uri + Gallery.gallery_loader_data,
				function(data){
					if(data != ''){
						new_page.appendTo(Gallery.gallery_div);
						page_id = '#Page-' + Gallery.current_page;
						Images.hide_missing(page_id);
						Gallery.set_last_photo_id(Gallery.current_page);
						Gallery.add_photo_ids(Gallery.gallery_div.attr('id'), jQuery(page_id));
						Gallery.init_meta_mouseovers();
						WoophyMap.init_map_links(jQuery(page_id)[0]);
						if(new_page.has('div#__ENDOFGALLERY__').length) Gallery.end_gallery();
						if(new_page.has('div#__EMPTYGALLERY__').length) Gallery.empty_gallery();
					}else{
						if(jQuery('.Page', Gallery.gallery_div).length>0) Gallery.end_gallery();
						else Gallery.empty_gallery();
					}
					$header_meta = jQuery('#GalleryMeta .HeaderMeta', new_page);
					Gallery.set_titles($header_meta);
					Gallery.hide_loading_bar();
					Gallery.is_loading = false;
					Gallery.infinite_scroll();
				}
			);
		}else{
			if(Gallery.more_link==null && !Gallery.end_of_gallery) Gallery.show_more_link(offset);
			Page.show_footer();
		}
		Gallery.ready = true;
	},
	
	set_last_photo_id: function(page_number){
		var gallery_page = jQuery('.Gallery #Page-'+page_number).first();
		var last_photo = jQuery('.GalleryPhoto img.gallery_image', gallery_page).last();
		Gallery.last_photo_id = last_photo.attr('id');
	},
	
	get_last_photo_id: function(){
		return Gallery.last_photo_id;		
	},
	
	show_loading_bar: function(){
		if(Gallery.loading_bar == null){
			Gallery.loading_bar = jQuery('<div id="GalleryLoadingBar">Loading images</div>').insertAfter('#GalleryContainer');
		}else{
			Gallery.loading_bar.show();
		}
		if(Gallery.current_page > 1) Gallery.$window.scrollTop(Gallery.get_gallery_height()-Gallery.$window.height()+200);
	},
	
	show_more_link: function(offset){
		var more_url = more_text = '';
		if(Gallery.filters==null){
			more_url = 'photo/browse/recent?&offset='+offset;
			more_text = 'Show more Recent Photos';
		}else{
			if(typeof Gallery.filters == 'object'){
				keys = Gallery.filters.key.split(',');
				vals = Gallery.filters.val.split(',');
				switch(keys[0]){
					case 'username':
						more_url = 'member/' + vals[0] + '/photos?&offset='+offset+'&order_by=recent&sort_order=desc';
						more_text = 'Show more Photos by ' + Gallery.header_text;				
						break;
					case 'city':
					case 'cityid':
						more_url = 'city/' + Gallery.header_text.replace(', ','/') + '?&offset='+offset+'&order_by=rating&sort_order=desc';
						more_text = 'Show more Photos from ' + Gallery.header_text;				
						break;
					case 'keywords':
						more_url = 'photo/search?&offset='+offset+'&order_by=rating&sort_order=desc&keyword=' + encodeURIComponent(vals[0]);
						more_text = 'Show more Photos with "' + vals[0] + '"';				
						break;
					default:
						more_url = 'photo/browse/recent?&offset='+offset;
						more_text = 'Show more Recent Photos';		
						break;
				}
			}
		}
		if(more_url != '' && more_text != '') Gallery.more_link = jQuery('<div id="ShowMorePhotos"><a href="' + Page.root_url + more_url + '">' + more_text + '</a></div>').insertAfter('#GalleryContainer');
	},
	
	remove_more_link: function(){
		jQuery('#ShowMorePhotos').remove();
		Gallery.more_link = null;
	},
	
	set_titles: function($header_meta){
		var $title_bar = jQuery('#TitleBar'),
		$gallery_header = jQuery('#GalleryContainer').parent().find('h2');
		if($header_meta.length >0){
			var header = '';
			$header_meta.each(function(){
				var $this = jQuery(this);
				Gallery.header_text = $this.text();
				var filter_type = $this.attr('id');
				if(filter_type == 'city') header += 'Photos from ' + Gallery.header_text + ' ';
				else if(filter_type == 'username') header += 'Photos by '+ Gallery.header_text + ' ';
				else if(filter_type == 'keywords') header += 'Photos containing "'+ Gallery.header_text + '" ';
				else if(filter_type == 'photoid') header += 'Photo #'+ Gallery.header_text + ' ';
			});
			
			jQuery('#WorldTitle', $title_bar).hide();
			if($title_bar.has('#FilterTitle').length) jQuery('#FilterTitle h1', $title_bar).text(header)
			else jQuery('<div id="FilterTitle" class="clearfix"><h1>'+header+'</h1></div>').appendTo($title_bar);
			$gallery_header.text(header);
		}else{
			jQuery('#FilterTitle', $title_bar).remove();
			jQuery('#WorldTitle', $title_bar).show();
			$gallery_header.text('Latest Photos');
		}
	},
	
	end_gallery: function(msg){
		if(!Gallery.is_infinite_scroll)return;
		Gallery.remove_more_link();
		Gallery.end_of_gallery = true;
		jQuery('.Notice', Gallery.gallery_div).remove();
		jQuery('<div class="Notice">'+(msg?msg:'There are no more photos to display!')+'</div>').appendTo(Gallery.gallery_div);
		Page.show_footer();
	},
	
	empty_gallery: function(){
		Gallery.end_gallery('Sorry, your search didn\'t result in any matches');
	},

	hide_loading_bar: function(){
		Gallery.loading_bar.hide();
	},
	
	init_meta_mouseovers: function(){
		if(Gallery.is_infinite_scroll){
			jQuery('.Gallery #Page-' + Gallery.current_page + ' .GalleryPhoto .PhotoMetaContainer > div').hide();
			$el = jQuery('.Gallery #Page-' + Gallery.current_page + ' .GalleryPhoto');
		}else{
			jQuery('.Gallery .GalleryPhoto .PhotoMetaContainer > div').hide();
			$el = jQuery('.Gallery .GalleryPhoto');
		}
		
		jQuery('.PhotoMetaContainer', $el).removeClass('js_nodisplay');
		//$el.bind('click', function(){location.href=jQuery(this).attr('rel');});
		$el.bind('mouseover', Gallery.show_photo_info);
		$el.bind('mouseout', Gallery.hide_photo_info);
	},
	
	init_photo_popup: function(){
		jQuery('.Gallery').each(function(){
			var $gallery = jQuery(this);
			$page = jQuery('.Page', $gallery).first();
			Gallery.ids[$gallery.attr('id')] = new Array();
			Gallery.add_photo_ids($gallery.attr('id'), $page);
		});
	},

	init: function(){
		Gallery.$main_gallery = jQuery('#MainGallery');
		Gallery.$main_container = jQuery('#MainContainer');
		Gallery.$gallery_container = jQuery('#GalleryContainer');
		Gallery.$window = jQuery(window);
		Gallery.gallery_div = jQuery('.Gallery');
		if(Gallery.gallery_div.length>0){
			if(Gallery.gallery_div.hasClass('infinite')){
				Gallery.is_infinite_scroll = true;
				Page.hide_footer();
			}
			Gallery.init_meta_mouseovers();
			Gallery.init_photo_popup();
		}
	},
	
	init_post: function(){
		if(Gallery.is_infinite_scroll){
			Gallery.$window.scroll(jQuery.proxy(Gallery.show_to_top_link, Gallery)).scroll(jQuery.proxy(Gallery.infinite_scroll, Gallery));
			Gallery.set_last_photo_id(1);
			Gallery.infinite_scroll();
		}
	}
}

init_global_pre.add(Gallery.init);
init_global_post.add(Gallery.init_post);