function SimpleBlogArchive(param){
	BaseArchive.apply(this,arguments);
}
SimpleBlogArchive.prototype = new BaseArchive();
jQuery.extend(SimpleBlogArchive.prototype,{
	onGetArchive : function(xml, success){
		var posts = xml.getElementsByTagName('post');
		var l = posts.length, html = '';
		if(l==0) {
			this.count_items = Math.max(0, this.offset - this.limit);
			this.offset = -this.limit;
		}else{
			var i = -1;
			while(++i<l){
				var p = posts[i], id = jQuery('id', p).text(), active = (this.current_item_id != undefined && id == this.current_item_id);
				html += '<div class="Excerpt BlogPost clearfix';
				if(active) html += ' active';
				if(i == l-1) html += ' last';
				html += '">';
				html += '<div class="ExcerptContent">';
				html += '<div class="Title clearfix"><a href="'+this.blog_url+id+'?&offset='+this.offset+'&total='+this.count_items+'">';
				html += jQuery('title', p).text() || '\u00a0';
				html += '</a></div>';
				html += '<div class="Meta">';
				var s = jQuery('post_age', p).text();
				html += s ? s + ' ago' : '\u00a0';
				html += '</div>';
				html += '</div>';
				html += '</div>';			
			}
		}
		jQuery(this.divObj).html(html);
		this.enablePageButtons();
	}
});