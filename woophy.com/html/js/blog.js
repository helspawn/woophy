/*
	Requires: 
		SelectionFormat
		UploadProgress
 */
function Blog(param){
	this.user_id = param.user_id;
	this.offset = 0;
	this.limit = 6;

	this.form_action = document.forms[0].action;
	this.form_target = document.forms[0].target;
	
	Page.uploadprogress = new UploadProgress({key:param.upload_id});
	jQuery(Page.uploadprogress).on('uploadComplete', jQuery.proxy(this.onUploadComplete, this));

	this.selformat = new SelectionFormat(document.forms[0]['post_text']);

	jQuery('#submit_publish').click(jQuery.proxy(this.savePost, this, 'published'));
	jQuery('#submit_draft').click(jQuery.proxy(this.savePost, this, 'draft'));
	jQuery('#submit_preview').click(jQuery.proxy(this.previewPost, this));
	jQuery('#submit_insert').click(jQuery.proxy(this.showInsert, this, null));
	jQuery('#cancel_insert').click(jQuery.proxy(this.showInsert, this, false));
	jQuery('#btn_url').click(jQuery.proxy(this.selformat.addTags, this.selformat, '[url=http://]','[/url]'));
	jQuery('#btn_underlined').click(jQuery.proxy(this.selformat.addTags, this.selformat, '[u]','[/u]'));
	jQuery('#btn_italic').click(jQuery.proxy(this.selformat.addTags, this.selformat, '[i]','[/i]'));
	jQuery('#btn_bold').click(jQuery.proxy(this.selformat.addTags, this.selformat, '[b]','[/b]'));
	jQuery('#btn_header').click(jQuery.proxy(this.selformat.addTags, this.selformat, '[h]','[/h]'));
	jQuery('#btn_hr').click(jQuery.proxy(this.selformat.addTags, this.selformat, '[hr]',''));
	jQuery('#option_insert').change(jQuery.proxy(this.onChangeOption, this));

	this.onChangeOption(0);
}
Blog.prototype = {
	savePost : function(status, evt){
		var f = document.forms[0];
		if(f['post_title'].value.length == 0){
			alert('Please enter the title.');
			return false;
		}else if(f['publication_date'].value.length == 0){
			alert('Please select the publication date.');
			return false;
		}else{
			f['post_status'].value = status.length>0 ? status : 'draft';
			f.target = '_self';
			f.action = this.form_action;
		}
	},
	
	previewPost : function(){
		var target = 'winPreview';
		var win = window.open('',target);
		if(win.focus)win.focus();
		document.forms[0].target = target;
		document.forms[0].action = Page.root_url + 'previewpost';
		return true;
	},
	showInsert : function(bln){
		var $el = jQuery('#InsertImageHolder');
		if(bln == undefined) bln = $el.toggleClass('nodisplay');
		else (bln? $el.removeClass('nodisplay'):$el.addClass('nodisplay'));
	},
	onChangeOption : function(){
		var val = parseInt(jQuery('#option_insert').val())||0;
		var $e = jQuery('#InsertImage');
		$e.empty();
		jQuery('#upload_option').hide();
		
		if(val <= 3){
			var c = document.createElement('input');
			c.setAttribute('type',val == 3 ? 'file' : 'text');
			c.className = 'text';
			c.setAttribute('name','image_value');
			c.setAttribute('id','image_value');
			c.setAttribute('value',val == 1 || val == 2 ? 'http://' : '');
			$e.append(c);
			s = document.createElement('input');
			s.setAttribute('type', 'button');
			s.setAttribute('name','image_add');
			s.setAttribute('value',val == 3 ? 'Upload' : 'Add');
			s.className = 'button_simple';
			jQuery(s).click(jQuery.proxy(this.insertImage, this));

			$e.append(s);
			jQuery('#browse_option').hide();
			if(val==3)jQuery('#upload_option').show();
			//c.focus();
		}else{
			this.offset = 0;
			this.getBlogPhotos();
			jQuery('#browse_option').empty().show();
		}
	},
	insertImage : function(){
		var $el = jQuery('#image_value');
		if($el.length){
			var mode = parseInt(jQuery('#option_insert').val())||0;
			var value = jQuery.trim($el.val());
			switch(mode){
				case 0:
					jQuery.get(Page.root_url+'services?method=woophy.photo.getUrl&photo_id='+value+'&size=medium', jQuery.proxy(this.onGetUrl, this));
					break;
				case 1:
					this.showInsert(false);
					this.selformat.addTags('[img='+value+']','[/img]');
					break;
				case 2:
					this.showInsert(false);
					this.selformat.addTags('[youtube='+value+']','');
					break;
				case 3:
					//upload
					var f = document.forms[0];
					f.action = Page.root_url+'upload';
					f.target = 'target_upload';
					this.disableFormButtons(true);
					Page.uploadprogress.startProgress();
					f.submit();
					break;
			}
		}		
	},
	disableFormButtons:function(bln){
		for(var i=0,f=document.forms[0],t;i<f.elements.length;i++){
			t = f.elements[i].type;
			if(t=="submit" || t=="button")f.elements[i].disabled = bln;
		}
	},
	getBlogPhotos : function(){
		jQuery.get(Page.root_url+'services?&method=woophy.blog.getPhotosByUserId&user_id='+this.user_id+'&offset='+this.offset+'&limit='+this.limit, jQuery.proxy(this.onCompleteBlogPhotos, this));
	},
	onUploadComplete : function(success, msg, url, id){
		var f = document.forms[0];
		f.action = this.form_action;
		f.target = this.form_target;
		this.disableFormButtons(false);
	},
	onCompleteBlogPhotos : function(xml, success){
		var photos = xml.getElementsByTagName('photo');
		jQuery('#InsertImage').empty();
		
		var $e = jQuery('#browse_option');
		$e.empty();
		
		try{
			var total_photos = xml.getElementsByTagName('rsp').item(0).attributes.getNamedItem('total_photos').value;
		}catch(error){
			var total_photos = 0;
		}
		
		if(total_photos==0){
			var s = document.createElement('span');
			s.appendChild(document.createTextNode('No photo\'s uploaded'))
			$e.append(s);
		}else{
			if(this.next_btn)jQuery(this.next_btn).off('click');//avoid leak
			if(this.prev_btn)jQuery(this.prev_btn).off('click');
			this.next_btn = document.createElement('div');
			this.next_btn.className = 'PagingRight sprite';
			if(this.offset + this.limit < total_photos) jQuery(this.next_btn).click(jQuery.proxy(this.pageForward, this));
			else this.next_btn.className = 'PagingRight sprite inactive';
			$e.append(this.next_btn);
			this.prev_btn = document.createElement('div');
			this.prev_btn.className = 'PagingLeft sprite';
			if(total_photos>this.limit && this.offset > 0)jQuery(this.prev_btn).click(jQuery.proxy(this.pageBackward, this));
			else this.prev_btn.className = 'PagingLeft sprite inactive';
			$e.append(this.prev_btn);
			var l = photos.length;
			var i = -1;
			while(++i<l){
				var url = photos[i].getElementsByTagName('url').item(0).firstChild.data;
				var thumb_url = photos[i].getElementsByTagName('thumb_url').item(0).firstChild.data;

				var a = document.createElement('a');
				a.setAttribute('href','javascript:void(0)');
				jQuery(a).click(jQuery.proxy(this.selformat.addTags, this.selformat, '[img='+url+']','[/img]'));
				a.setAttribute('class','Thumb');
				var img = document.createElement('img');
				img.setAttribute('src', thumb_url);
				a.appendChild(img);
				$e.append(a);
			}
		}
	},
	pageForward : function(){
		this.offset += this.limit;
		this.getBlogPhotos();
	},
	pageBackward : function(){
		this.offset -= this.limit;
		this.offset = Math.max(0, this.offset);
		this.getBlogPhotos();
	},
	onGetUrl : function(xml, success){
		try{
			this.showInsert(false);
			this.selformat.addTags('[img='+xml.getElementsByTagName('url').item(0).firstChild.data+']','[/img]');
		}catch(error){
			alert('Photo not found!');
		};
	}
};