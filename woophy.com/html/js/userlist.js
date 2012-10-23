function UserList(param){
	SelectList.apply(this,arguments);
}
UserList.prototype = new SelectList();
jQuery.extend(UserList.prototype,{
	onComplete : function (xml, success){	
		var $users = jQuery('user', xml);
		if($users.length){
			this.setSelectedIndex(-1);
			this.listItems = [];
			this.$holder.empty();
			var list = this.listItems, tbody = '', idx=-1, val = this.inputObj.value.toLowerCase();
			$users.each(function(i){
				var n = jQuery(this).attr('name');
				list[i] = n;
				tbody += '<tr class="inactive">';
				tbody += '<td>'+n+'</td>';
				tbody += '</tr>';
			});
			this.$holder.append(jQuery(document.createElement('table')).append(jQuery(document.createElement('tbody')).append(tbody)));
			jQuery('tr', this.$holder).on({mouseover: jQuery.proxy(this.onMouseOverItem, this), mouseup:jQuery.proxy(this.onMouseUpItem, this)});
			this.showList();
		}
	},
	getList : function (){	
		var u = this.inputObj.value;
		if(u.length>0){
			jQuery.get(Page.root_url+'services?&method=woophy.user.getUsersByName&user_name='+encodeURIComponent(u), jQuery.proxy(this.onComplete, this));
		}else this.hideList();
	},
	onMouseUpItem : function (evt){
		var idx = jQuery(evt.currentTarget).index();
		if(idx>-1){
			jQuery(this).trigger('clickItem', [(this.listItems[idx] ? this.listItems[idx] : null)]);
		}
	}
});