function MembersBirthday(param){
	BaseArchive.apply(this,arguments);
}
MembersBirthday.prototype = new BaseArchive();
jQuery.extend(MembersBirthday.prototype,{
	getChildByName : function(xml, name){
		var e = xml.getElementsByTagName(name);
		if(e.length)if(e.item(0).firstChild)return e.item(0).firstChild.data;
		return false;
	},
	onGetArchive : function(xml, success){
		var users = xml.getElementsByTagName('user');
		var l = users.length;
		var i = -1, name, href, n;
		while(this.divObj.firstChild) this.divObj.removeChild(this.divObj.firstChild);//remove old nodes
		while(++i<l){
			var u = users[i];
			name = this.getChildByName(u, 'name') || '';
			href = Page.root_url + 'member/' + encodeURIComponent(name);
			var d1 = document.createElement('div');
			d1.className = 'User DottedBottom clearfix';
			var a1 = document.createElement('a');
			a1.setAttribute('href', href);
			a1.className = 'Thumb sprite';
			
			var im = document.createElement('img');
			im.setAttribute('src', this.getChildByName(u, 'avatar_url'));
			a1.appendChild(im);
			d1.appendChild(a1);
			
			var d2 = document.createElement('div');
			d2.className = 'Content';
			d1.appendChild(d2);
						
			var d3 = document.createElement('div');
			var a2 = document.createElement('a');
			a2.setAttribute('href', href);
			a2.appendChild(document.createTextNode(name));
			d3.appendChild(a2);
			d2.appendChild(d3);
			
			var d4 = document.createElement('div');	
			if(n = this.getChildByName(u, 'photo_count'))d4.appendChild(document.createTextNode(n));
			d4.appendChild(document.createTextNode(' photo'+(parseInt(n)==1?'':'s')));
			d2.appendChild(d4);
			var d5 = document.createElement('div');
			d5.className = 'Meta';
			d5.appendChild(document.createTextNode('registered: '+(this.getChildByName(u, 'time_registered')||'')+' ago'));
			d2.appendChild(d5);
			this.divObj.appendChild(d1);
		}
		this.count_items = this.getChildByName(xml, 'total')||0;
		this.enablePageButtons();
		Images.hide_missing('#MembersBirthday');
	}
});