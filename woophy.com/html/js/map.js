var Map = {
	
	style: [
		{elementType: "labels",stylers: [{ visibility: "off" }]},
		{featureType: "poi",elementType: "geometry",stylers: [{ visibility: "off" }]},
		{featureType: "transit",elementType: "geometry",stylers: [{ visibility: "off" }]},
		{featureType: "water", stylers: [ { visibility: "simplified" }, { lightness: 100 } ]},
		{featureType: "landscape",elementType: "geometry",stylers: [{ visibility: "on" },{ hue: "#55ff00" }, { saturation: 52 }, { lightness: -59 } ]},
		{featureType: "road",elementType: "geometry",stylers: [{ visibility: "off" }]},
		{featureType: "road.local",elementType: "geometry",stylers: [{ hue: "#5eff00" },{ visibility: "simplified" },{ lightness: -55 },{ saturation: 30 }]},
		{featureType: "road.arterial",elementType: "geometry",stylers: [{ hue: "#5eff00" },{ lightness: -38 },{ visibility: "simplified" },{ saturation: -59 }]},
		{featureType: "administrative", stylers: [ { visibility: "off" },{ hue: "#33ff00" },{ saturation: 40 },{ lightness: -55 } ] },
		{featureType: "administrative.country",stylers: [{ visibility: "simplified" },{ hue: "#33ff00" },{ lightness: 14 },{ saturation: 22 }]}
	],
	
	markerShape : {type:'circle', coords:[9,9,7]},
	markerSize : new google.maps.Size(18, 18),
	markerOrigin : new google.maps.Point(0, 0),
	markerAnchor : new google.maps.Point(9, 9)
}


function MarkerTooltip(opt_options) {
    this.setValues(opt_options);
}
MarkerTooltip.prototype = new google.maps.OverlayView;
MarkerTooltip.prototype.onAdd = function() {}
MarkerTooltip.prototype.onRemove = function() {
    var el = this.get('element');
	if(el)el.parentNode.removeChild(this.div_);
};
/*
{	u:"-2984658", 
	x:"335.466", 
	y:"109.891", 
	pid:"137789", 
	uid:"791", 
	n:"Rotterdam", 
	c:"Netherlands", 
	q:"915", 
	url:"/woophy/html/images/photos/791/s/137789.jpg"}*/

MarkerTooltip.prototype.draw = function(data) {
	if(data==undefined)return;
	var el = this.get('element');
	if(el){
		var loc = data.n + ',<br/>' + data.c,
		q = parseInt(data.q)||0,
		qty = q + ' photo' + (q==1?'':'s'),
		img = 'url('+data.url+')';
		jQuery('#tt_location', el).html(loc);
		jQuery('#tt_quantity', el).html(qty);
		jQuery('#tt_thumb', el).css('background-image', img);
	}
};
MarkerTooltip.prototype.hide = function() {
	var el = this.get('element');
	if(el)jQuery(el).hide();
};
MarkerTooltip.prototype.show = function() {
	var el = this.get('element');
	if(el){
		var $el = jQuery(el),
		margin = 10,
		position = this.getProjection().fromLatLngToContainerPixel(this.get('position')),
		offset = jQuery(this.getMap().getDiv()).offset();
		position.y += offset.top;
		position.x += offset.left;
		var y = position.y - $el.height() - margin;
		if(y<margin) y = position.y + margin;
		var x = position.x + margin,
		w = $el.width();
		if(x + w + margin > jQuery(document).width()) x = position.x - w;

		$el.css({left:x + 'px',top:y + 'px'});
		$el.show();
	}
};
function MapHome(options){

	//private vars
	var map,
	self = this,
	markers = {},
	markerImages = [],
	markerImagesHover = [],
	activeMarker,
	activeHoverMarker,
	activeUni = NaN,
	tooltipShowTimeout,
	tooltipHideTimeout,
	historyTimeout,
	markerTooltip,
	fitBounds = true,//flag, on initial load set to false to use lat,lng from hash 
	useFilters = false,
	isLoadingToolTip = false,
	useCache = false,//flag, when true don't reload cache on bounds change
	defaultColorCoeff = 50,//when no filters are used
	filterColorCoeff = 1,
	colorCoeff = defaultColorCoeff,
	numMarkerSkins = 5,
	options = jQuery.extend({}, options),
	unis = [],//keep track of unis for easy lookup
	filters = {},
	history = {},
	extraAnchor = '';

	//private methods:
	function setHistory(key, value){
		if(history[key] == value)return;
		var ret = true;
		if(value == undefined || value.length==0){
			if(history[key])delete history[key];
		}else{
			ret = false; 
			for(var k in MapHome.historyKeys){
				if(MapHome.historyKeys[k] == key){//filter exists
					history[key] = value+'';//convert to string
					ret = true;
					break;
				}
			}
		}
		clearTimeout(historyTimeout);
		historyTimeout = setTimeout(_setHistory, 10);
		return ret;
	}
	function _setHistory(){
		var a = [];//use array to sort
		for(var k in history){
			if(history[k] != undefined && history[k].length>0)a.push("&" + k + "=" + history[k]);
		}
		if(a.length > 0){
			a = a.sort();
			window.location.hash = a.join('') + extraAnchor;
		}
	}
	function setFilter(key, value){
		if(filters[key] == value)return;
		var ret = true;
		var b = false;
		if(value == undefined || value.length==0){
			if(filters[key])delete filters[key];
		}else{
			ret = false;
			for(var k in MapHome.filterKeys){
				if(MapHome.filterKeys[k] == key){//filter exists
					var s = MapHome.filterKeys.SHOWALL;
					if(key == s){
						for(var f in filters){//look for valid filter
							if(f != s){
								b =true;
								break;
							}
						}
					}else b =true;
					filters[key] = value;
					ret = true;
					break;
				}
			}
		}
		useFilters = b;
		var f = self.urlencodeFilters();
		//console.log('setfilter', key, f.key);
		setHistory(MapHome.historyKeys.FILTERKEY, f.key);
		setHistory(MapHome.historyKeys.FILTERVALUE, f.val);
		return ret;
	}
	function clearFilters(){
		if(useFilters){
			filters = {};
			setFilter(MapHome.filterKeys.SHOWALL, null);
			useFilters = false;
			setActiveMarker(null);
			setHistory(MapHome.historyKeys.FILTERKEY, null);
			setHistory(MapHome.historyKeys.FILTERVALUE, null);
		}
	}
	function getTooltip(){
		if(activeHoverMarker){
			markerTooltip.bindTo('position', activeHoverMarker, 'position');

			var f = self.urlencodeFilters();
			var k = f.key.length ? [f.key] : [];
			var v = f.val.length ? [f.val] : [];
			k.push(MapHome.filterKeys.TOOLTIP);
			v.push(activeHoverMarker.u);
			isLoadingToolTip = true;
			jQuery.get(options.service_url, {method:'woophy.map.getCities', key:k.join(','), val:v.join(','), output_mode:'json'}, showTooltip, 'json');
		}
	}
	function showTooltip(response,status,xhr){
		if(isLoadingToolTip){
			markerTooltip.draw(response.length?response[0]:null);
			markerTooltip.show();
			clearTimeout(tooltipHideTimeout);
			tooltipHideTimeout = setTimeout(hideTooltip, 8000);
			isLoadingToolTip = false;
		}
	}
	function hideTooltip(){
		clearTimeout(tooltipShowTimeout);
		tooltipShowTimeout = null;
		clearTimeout(tooltipHideTimeout);
		tooltipHideTimeout = null;
		markerTooltip.hide();
		isLoadingToolTip = false;
	}
	function markerMouseOver(){
		this.setIcon(markerImagesHover[this.i]);
		hideTooltip();
		tooltipShowTimeout = setTimeout(getTooltip, 750);
		activeHoverMarker = this;
	}
	function markerMouseOut(){
		if(!this.a)this.setIcon(markerImages[this.i]);
		hideTooltip();
	}
	function setActiveMarker(marker){
		if(activeMarker){
			activeMarker.a = false;
			activeMarker.setIcon(markerImages[activeMarker.i]);
		}
		if(marker){
			marker.a = true;
			marker.setIcon(markerImagesHover[marker.i]);
			activeMarker = marker;
			activeUni = marker.u;

			if(!useFilters)setFilter(MapHome.filterKeys.SHOWALL, null);
			setFilter(MapHome.filterKeys.CITYID, activeUni);
		}else{
			activeMarker = null;
			activeUni = NaN;
		}
	}
	function markerClick(){
		hideTooltip();
		setActiveMarker(this);
		jQuery(self).trigger(MapHome.events.MARKER_CLICK);
	}
	function getMarkerImageIndex(q){
		q = Math.max(1, q);
		var idx = numMarkerSkins -1;
		while(colorCoeff*Math.pow(idx--,2)>=q);
		return Math.min(numMarkerSkins, Math.max(0, idx+1));
	}
	function createMarkers(cities){
		//console.log('createMarkers', cities, activeUni);
		if(cities == undefined)return;
		i = j = cities.length;
		var u = [], uni;
		while(i--){
			uni = cities[i].u;
			u.push(uni);	
			if(markers[uni] == undefined){		
				markers[uni] = createMarker(cities[i].x, cities[i].y, cities[i].q, uni, j-i);
			}else{//marker already on map:
				markers[uni].setZIndex(j-i);
				var idx = getMarkerImageIndex(cities[i].q);
				if(markers[uni].i != idx){
					markers[uni].i = idx;
					markers[uni].setIcon(markers[uni].a ? markerImagesHover[idx] : markerImages[idx]);
				}
			}
		}
		//clean up markers
		i = unis.length;
		while(i--){
			uni = unis[i];
			if(jQuery.inArray(uni, u)<0){
			//if(u.indexOf(uni)<0){//ie7 doesn't support indexOf		
				if(markers[uni]){		
					if(markers[uni].a){
						u.push(uni);//leave active marker on map
						continue;
					}
					j = markers[uni].e.length;
					while(j--)google.maps.event.removeListener(markers[uni].e[j]);
					markers[uni].setMap(null);
					delete markers[uni];
				}
			}
		}
		if(colorCoeff != defaultColorCoeff && cities.length && fitBounds){
			var bounds = new google.maps.LatLngBounds();
			for(uni in markers){
				bounds.extend(markers[uni].position);
			}
			map.fitBounds(bounds);
		}
		fitBounds = true;
		unis = u.slice(0);
	}
	
	function createMarker(x,y,q,u,z){
		var i = getMarkerImageIndex(q);
		var a = activeUni == u;
		
		var marker = new google.maps.Marker({
			  position: new google.maps.LatLng(y, x),
			  map: map,
			  icon: a ? markerImagesHover[i] : markerImages[i],
			  shape: Map.markerShape
		 });

		marker.e = [];//store events to prevent memory leaks
		marker.u = u;//uni
		marker.i = i;//imageindex
		marker.a = a;//active
		if(a) activeMarker = marker;
		marker.setZIndex(z);
	
		marker.e.push(google.maps.event.addListener(marker, 'mouseover', markerMouseOver));
		marker.e.push(google.maps.event.addListener(marker, 'mouseout', markerMouseOut));
		marker.e.push(google.maps.event.addListener(marker, 'click', markerClick));
		return marker;
	}
	function boundsHandler(cities){
		if(colorCoeff == defaultColorCoeff){
			createMarkers(cities);
		}
	}
	function boundsChanged(e){
		
		var c = map.getCenter();
		setHistory(MapHome.historyKeys.LATITUDE, (c.lat()||0).toFixed(6));
		setHistory(MapHome.historyKeys.LONGITUDE, (c.lng()||0).toFixed(6));
		setHistory(MapHome.historyKeys.MAGNITUDE, map.zoom);
		
		if(colorCoeff == defaultColorCoeff){//showall
			if(map.zoom==map.minZoom){
				if(!useCache){
					useCache = true;
					jQuery.get(options.service_url, {method:'woophy.map.getCitiesCache', zoomlevel:map.zoom, output_mode:'json'}, boundsHandler, 'json');
				}
			}else{
				useCache = false;
				var b = map.getBounds(),
				x1 = b.getSouthWest().lng(),
				y1  = b.getSouthWest().lat(),
				x2  = b.getNorthEast().lng(),
				y2  = b.getNorthEast().lat();
				jQuery.get(options.service_url, {method:'woophy.map.getCitiesByArea',x1:x1, y1:y1, x2:x2, y2:y2, zoomlevel:map.zoom, output_mode:'json'}, boundsHandler, 'json');
			}
		}
	}
	function sizeChanged(){
		useCache = false;
	}
	function getCitiesHandler(cities){
		if(colorCoeff == filterColorCoeff){
			createMarkers(cities);
			jQuery(self).trigger(MapHome.events.GET_CITIES);
		}
		jQuery('body').scrollTop(0);
	}
	function initialize(){
		if(jQuery('#'+options.map_id).length>0){
			var mag = 2, lat = 30, lng = 0, keys = [], values = [], h = window.location.hash, f, showAll=false;
			if(h.length>1){
				h=h.substr(1);
				var a=h.split('&'),i=a.length,k;
				while(i--){
					k=a[i].split('=');
					if(k.length==2){
						switch(k[0]){
							case MapHome.historyKeys.LATITUDE:lat=parseFloat(k[1])||lat;fitBounds=false;break;
							case MapHome.historyKeys.LONGITUDE:lng=parseFloat(k[1])||lng;break;
							case MapHome.historyKeys.MAGNITUDE:mag=parseInt(k[1])||1;break;
							case MapHome.historyKeys.FILTERKEY:
								keys = k[1].split(',');
								break;
							case MapHome.historyKeys.FILTERVALUE:
								values = k[1].split(',');
								break;
							case MapHome.historyKeys.OFFSET:break;
						}
					}else if(k.length==1){
						if(k[0].length && extraAnchor == '') extraAnchor = '&'+ k[0];//photopopup id
					}
				}
				var j = keys.length;
				if(j && j == values.length){
					f = {};
					while(j--){
						switch(keys[j]){
							case MapHome.filterKeys.SHOWALL:
								showAll = values[j] == '1' || values[j].toLowerCase() == 'true';
								break;
							case MapHome.filterKeys.CITYID:
								activeUni = values[j];
								break;
						}
						f[keys[j]] = values[j];
					}
				}	
			}
	
			self.googlemap = map = new google.maps.Map(document.getElementById(options.map_id), {
				zoom: mag,
				center: new google.maps.LatLng(lat, lng),
				maxZoom:11,
				minZoom:1,
				mapTypeControl: false,
				streetViewControl:false,
				mapTypeControlOptions: {mapTypeIds:['Styled']},
				mapTypeId: 'Styled'}
			);
	
			var styledMapType = new google.maps.StyledMapType(Map.style, { name: 'Styled' });
			
			map.mapTypes.set('Styled', styledMapType);
	
			var i = numMarkerSkins;
			while(i--){
				markerImages[i] = new google.maps.MarkerImage(options.marker_image_dir+'markerskin'+(i+1)+'.png', Map.markerSize, Map.markerOrigin, Map.markerAnchor);
				markerImagesHover[i] = new google.maps.MarkerImage(options.marker_image_dir+'markerskin'+(i+1)+'_hover.png', Map.markerSize, Map.markerOrigin, Map.markerAnchor);
			}
			markerTooltip = new MarkerTooltip({map: map, zIndex:99999, element:document.getElementById(options.tooltip_id)});
			google.maps.event.addListener(map, 'idle', boundsChanged);
			google.maps.event.addListener(map, 'resize', sizeChanged);
			
			if(!showAll){
				self.getCities(f);
			}else if(!isNaN(activeUni)){
				setFilter(MapHome.filterKeys.SHOWALL, '1');
				setFilter(MapHome.filterKeys.CITYID, activeUni);
			}
		}
	}
	
	//public vars:
	this.googlemap = null;
	
	//public methods:
	this.getCities = function(filters){//filters: object with key value pairs {username:marek,city:berlin}
		if(filters != undefined){	
			clearFilters();
			for(var f in filters){
				setFilter(f, filters[f]);
			}
			if(useFilters){
				colorCoeff = filterColorCoeff;
				useCache = false;
				var f = self.urlencodeFilters();
				jQuery.get(options.service_url,{
					method:'woophy.map.getCities',
					key:f.key,
					val:f.val,
					output_mode:'json'},
					getCitiesHandler,'json'
				);
			}
		}else{
			if(useFilters){
				clearFilters();
				colorCoeff = defaultColorCoeff;
				boundsChanged(null);//force reload
			}
		}
	};
	this.getPhotos = function(offset, callback){
		if(useFilters){
			callback();
		}
	}
	this.getFilters = function(){
		return filters;
	}
	this.urlencodeFilters = function(){
		var k = [];
		var v = [];
		for(var f in filters){
			//To prevent URI encoding of an already encoded string, first decode them
			//if(f == MapHome.filterKeys.SHOWALL)continue;
			k.push(encodeURIComponent(decodeURIComponent(f)));
			v.push(encodeURIComponent(decodeURIComponent(filters[f])));
		}
		return {key:k.join(','),val:v.join(',')};
	}
	google.maps.event.addDomListener(window, 'load', initialize);
};
//static vars
MapHome.events = {
	MARKER_CLICK:'marker_click',
	GET_CITIES:'get_cities'
};
MapHome.filterKeys = {
	KEYWORDS:'keywords',
	CITY:'city',
	USERNAME:'username',
	PHOTOID:'photoid',
	LAST24H:'last24H',
	CITYID:'cityid',
	USERID:'userid',
	TRAVELBLOG:'travelblog',
	TOOLTIP:'tooltip',
	SHOWALL:'showall',//not used to filter, only flag to indicate if all cities should initially be loaded on map
	LIMIT:'limit'
};
MapHome.historyKeys = {
	LATITUDE:'lat',
	LONGITUDE:'lng',
	MAGNITUDE:'mag',
	FILTERKEY:'key',
	FILTERVALUE:'val',
	OFFSET:'lmt'
};

function MapSideBar(options){
	var self = this,
	options = jQuery.extend({}, options);

	function markerClick(event){
		window.top.location.href = options.base_url+'#&key='+MapHome.filterKeys.SHOWALL+','+MapHome.filterKeys.CITYID+'&val=0,'+options.city_id;
	}
	function travelblogClick(event){
		window.top.location.href = options.blog_url + this.post_id;
	}
	function getMarkerImage(filename){
		return new google.maps.MarkerImage(options.marker_image_dir+filename, Map.markerSize, Map.markerOrigin, Map.markerAnchor);
	}
	function travelBlogHandler(cities){
		if(cities && cities.length){
			var markerImage = getMarkerImage('markerskin1.png');
			var lastMarkerImage = getMarkerImage('markerskin3.png');
			var activeMarkerImage = getMarkerImage('markerskin1_hover.png');
			var points = [];
			var bounds = new google.maps.LatLngBounds();
			var i = -1, l = cities.length;
			while(++i<l){
				var c = cities[i];
				var marker = new google.maps.Marker({
					position: new google.maps.LatLng(c.y, c.x),
					map: self.googlemap,
					icon: markerImage,
					cursor:'pointer',
					shape: Map.markerShape
				});
				marker.post_id = c.p;
				if(c.p == options.post_id) marker.setIcon(activeMarkerImage);
				else if(i==l-1){
					marker.setZIndex(9999);
					marker.setIcon(lastMarkerImage);
				}
				google.maps.event.addListener(marker, 'click', travelblogClick);
				points.push(marker.position);
				bounds.extend(marker.position);
			}
			var path = new google.maps.Polyline({
				path: points,
				geodesic:true,
				cursor:'default',
				strokeColor: "#FF9900",
				strokeOpacity: 1.0,
				strokeWeight: 1.5
			});
			path.setMap(self.googlemap);
			self.googlemap.fitBounds(bounds);
		}
	}
	function initialize(){
		if(jQuery('#'+options.map_id).length>0){
			var lat = 30, lng = 0, mag =1, showMarker = false;
			if(options.latitude != undefined && options.longitude != undefined){
				lat = parseFloat(options.latitude) || lat;
				lng = parseFloat(options.longitude) || lat;
				showMarker = true;
			}
			self.googlemap = new google.maps.Map(document.getElementById(options.map_id), {
				zoom: mag,
				center: new google.maps.LatLng(lat, lng),
				maxZoom:10,
				minZoom:1,
				disableDefaultUI: true,
				draggable: false,
				scrollwheel: false,
				disableDoubleClickZoom: true,
				mapTypeControlOptions: {mapTypeIds:['Styled']},
				mapTypeId: 'Styled'}
			);
	
			var styledMapType = new google.maps.StyledMapType(Map.style, { name: 'Styled' });
			self.googlemap.mapTypes.set('Styled', styledMapType);

			if(showMarker){
				var markerImage = getMarkerImage('markerskin3_hover.png');
				var marker = new google.maps.Marker({
					position: new google.maps.LatLng(lat, lng),
					map: self.googlemap,
					icon: markerImage,
					cursor:'default',
					shape: Map.markerShape
				 });
				 if(options.city_id != undefined){
					marker.setCursor('pointer');
					google.maps.event.addListener(marker, 'click', markerClick);
				 }
			}else if(options.travelblog_id !=undefined){
				jQuery.get(options.service_url, {method:'woophy.map.getCitiesByTravelBlogId', travelblog_id:options.travelblog_id, output_mode:'json'}, travelBlogHandler, 'json');
			}
		}
	}

	//public vars:
	this.googlemap = null;

	//google.maps.event.addDomListener(window, 'load', initialize);
	jQuery(document).ready(initialize);
}