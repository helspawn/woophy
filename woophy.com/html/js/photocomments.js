function PhotoComments(param){
	BaseArchive.apply(this,arguments);
}
PhotoComments.prototype = new BaseArchive();
jQuery.extend(PhotoComments.prototype,{
	getChildByName : function(xml, name){
		var e = xml.getElementsByTagName(name);
		if(e.length)if(e.item(0).firstChild)return e.item(0).firstChild.data;
		return false;
	},
	getArchive : function(){
		jQuery.get(this.service_url+'&offset='+this.offset+'&limit='+this.limit+'&total='+this.count_items+'&output_mode='+this.output_mode, jQuery.proxy(this.onGetArchive, this));
	},
	onGetArchive : function(xml, success){
		var comments = xml.getElementsByTagName('comment');
		var l = comments.length;
		while(this.divObj.firstChild) this.divObj.removeChild(this.divObj.firstChild);//remove old nodes
		if(l==0){
			var d = document.createElement('div');
			d.className = 'Excerpt last';
			d.appendChild(document.createTextNode('No comments yet'));
			this.divObj.appendChild(d);
		}else{
			var i = -1;
			while(++i<l){
				var d1 = document.createElement('div');
				d1.className = 'Excerpt clearfix';
				if(i==l-1)d1.className+= ' last';
				var c = comments[i];
				var name = this.getChildByName(c, 'user_name') || '';
				var href = Page.root_url + 'photo/' + (this.getChildByName(c, 'photo_id')||'');
				var a1 = document.createElement('a');
				a1.setAttribute('href', href);
				a1.setAttribute('class', 'Thumb sprite');
				var im = document.createElement('img');
				var url = this.getChildByName(c, 'thumb_url');
				im.setAttribute('src', url);
				a1.appendChild(im);
				d1.appendChild(a1);
				
				var d2 = document.createElement('div');
				d2.className = 'ExcerptContent';
				var d3 = document.createElement('div');
				d3.className = 'Meta clearfix';
				href = Page.root_url + 'member/' + encodeURIComponent(name);
				var a2 = document.createElement('a');
				a2.setAttribute('href', href);
				a2.appendChild(document.createTextNode(name));
				d3.appendChild(a2);
				d2.appendChild(d3);
				d1.appendChild(d2);
				
				var d4 = document.createElement('div');
				var d5 = document.createElement('div');
				d5.className = 'Comment';
				d5.appendChild(document.createTextNode(this.getChildByName(c, 'text')||''));
				d2.appendChild(d5);
				d4.appendChild(document.createTextNode(' '+(this.getChildByName(c, 'time_posted')||'')+' ago'));
				d4.className = 'Date';
				d3.appendChild(d4);
				
				this.divObj.appendChild(d1);
			}
			this.divObj.innerHTML += '';//IE7 hack to force css applied to newly inserted html
		}
		if(this.count_items==0)this.count_items = this.getChildByName(xml, 'total')||0;
		this.enablePageButtons();
	}
});