function Photo(){}
Photo.prototype = {
	getRecent : function(limit, uid, offset, offset_id){
		if(offset == undefined) offset = 0;
		if(offset_id == undefined) offset_id = '';
		
		function formatDate(str){/*input: '2006-06-03 14:00:51', returns: June 3rd, 2006*/
			var s = '',a = str.split(/[-: ]/),d,n,m,sf,i;
			if(a.length>=3){
				m = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
				s += (n=m[parseInt(a[1],10)-1])?n+' ':'';
				if(d=parseInt(a[2],10)){
					s += d;
					sf = ['th','st','nd','rd'];
					i = d%10;
					s += sf[i>sf.length-1?0:i];
				}
				s += ', '+a[0];
			}
			return s;
		}
		jQuery.get(Page.root_url+'services?method=woophy.photo.getRecent&limit='+limit+'&offset='+offset+'&offset_id='+offset_id+'&user_id='+uid+'&output_mode=xml', function(xml, success){
			var $photos = jQuery('photo', xml);
			if($photos.length){
				var photo = $photos[0];
				var id = jQuery('id', photo).text();
				var user_name = jQuery('user_name', photo).text(),
				city_name = jQuery('city_name', photo).text(),
				country_name = jQuery('country_name', photo).text();
				var html = '<a class="Thumb sprite" href="'+Page.root_url + 'photo/'+id+'"><img src="'+jQuery('thumb_url', photo).text()+'" /></a>';
				html += '<div class="Content">';
				html += '<div><a href="'+Page.root_url + 'member/' +encodeURIComponent(user_name)+'">'+user_name+'</a></div>';
				html += '<div><a href="'+Page.root_url + 'city/' +encodeURIComponent(city_name)+'/'+encodeURIComponent(country_name)+'">'+jQuery('city_name', photo).text() + '</a>, <a href="'+Page.root_url + 'country/'+encodeURIComponent(country_name)+'">' + jQuery('country_name', photo).text()+'</a></div>';
				html += '<div class="Meta">Added on ' + formatDate(jQuery('date', photo).text())+'</div>';
				html+='</div>';
				jQuery('#MotM').html(html);
			}
		});
	}
};