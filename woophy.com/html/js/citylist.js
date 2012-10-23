function CityListItem(city, region, ufi){
	this.city = city;
	this.region = region;
	this.ufi = ufi;
}
function CityList(param){
	SelectList.apply(this,arguments);
}
CityList.prototype = new SelectList();
jQuery.extend(CityList.prototype,{
	setCountryCode : function(cc){
		this.cc = cc == undefined || cc.length == 0 ? null : cc;
		this.inputObj.disabled = (this.cc == (undefined||''||null));
	},
	getCountryCode : function(){
		return this.cc;
	},
	setInputValue : function(){
		if(this.listItems[this.selectedItemIndex])this.inputObj.value = this.listItems[this.selectedItemIndex].city;
		else this.inputObj.value = '';
	},
	onComplete : function (xml, success){
		var $cities = jQuery('city', xml);
		if($cities.length){
			this.setSelectedIndex(-1);
			this.listItems = [];
			this.$holder.empty();
			var list = this.listItems, tbody = '', val = this.inputObj.value.toLowerCase();
			$cities.each(function(i){
				var $c = jQuery(this),
				city = $c.text(),
				region = $c.attr('region');
				list[i] = new CityListItem(city, region, $c.attr('UFI'));
				tbody += '<tr class="inactive">';
				tbody += '<td>'+city+'</td>';
				tbody += '<td>'+region+'</td>';
				tbody += '<td class="info">'+$c.attr('lat')+'</td>';
				tbody += '<td class="info">'+$c.attr('long')+'</td>';
				tbody += '</tr>';
			});
			this.$holder.append(jQuery(document.createElement('table')).attr('class', 'citylist').append(jQuery(document.createElement('tbody')).append(tbody)));
			jQuery('tr', this.$holder).on('mouseover', jQuery.proxy(this.onMouseOverItem, this));
			this.showList();
		}
	},
	onKeyDownInput : function (evt){
		var code = evt.which || evt.keyCode;
		if(code == 13) {//enter
			if(this.selectedItemIndex > -1) this.setInputValue();
			this.hideList();
			evt.preventDefault();//disable submit
		}
	},
	getList : function (){	
		var city = this.inputObj.value;
		if(this.cc != undefined && city.length > 0){
			jQuery.get(Page.root_url+'services?&method=woophy.city.getCities&city_name='+encodeURIComponent(city)+'&country_code='+encodeURIComponent(this.cc), jQuery.proxy(this.onComplete, this));
		}else this.hideList();
	}
});