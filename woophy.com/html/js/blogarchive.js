function BlogArchive(param){
	BaseArchive.apply(this,arguments);
}
BlogArchive.prototype = new BaseArchive();
jQuery.extend(BlogArchive.prototype,{
	onGetArchive : function(xml, success){
		var posts = xml.getElementsByTagName('post');
		var l = posts.length,i = -1,html='';
		while(++i<l){
			var p = posts[i], name = jQuery('user_name', p).text(), url = Page.root_url + 'member/' + encodeURIComponent(name), blog_url = url+'/blog/'+jQuery('id', p).text();
			html+='<div class="Excerpt BlogPost clearfix';
			if(i == l-1) html+=' last';
			html+='"><a href="'+blog_url+'" class="Thumb sprite">';		
			html+='<img src="'+jQuery('avatar_url', p).text()+'"/>';
			html+='</a>';
			html+='<div class="ExcerptContent">';
			html+='<div><a href="'+blog_url+'" class="Title">'+jQuery('title', p).text()+'</a></div>';
			html+='<div>posted by <a href="'+url+'">'+name+'</a></div>';
			html+='<div class="Meta">'+jQuery('post_age', p).text()+' ago</div>';
			html+='</div>';
			html+='</div>';
		}
		jQuery(this.divObj).html(html);
		this.enablePageButtons();
		Images.hide_missing(this.divObj);
	}
});