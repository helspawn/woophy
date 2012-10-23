package com.woophy.ui{

	import com.woophy.core.RemoteServlet;
	import com.woophy.events.MarkerEvent;
	import com.woophy.managers.FilterManager;
	import com.woophy.managers.HistoryManager;
	import com.woophy.managers.MarkerManager;
	import com.woophy.search.PhotoSearchRequest;
	import com.woophy.search.SearchResponse;
	import com.woophy.search.SearchService;
	import com.woophy.utils.MillerProjection;
	
	import flash.display.Sprite;
	import flash.events.Event;
	import flash.events.TimerEvent;
	import flash.geom.Rectangle;
	import flash.utils.Timer;
	
	import nl.bbvh.events.ZoomEvent;
	import nl.bbvh.zoom.ZoomViewport;

	public class Map extends Sprite{
		public var searchService:SearchService;
		public var autoZoom:Boolean = true;//if set to false, map won't zoom after first (only first!) load, used to restore state
		public var background:Sprite;
		public var viewport:ZoomViewport;

		protected var markers_cache:MarkerClip;//default cached set of markers, when no filters are applied
		protected var markers:MarkerClip;
		protected var mouseOverTimer:Timer;
		protected var markerToolTip:MarkerToolTip;
		protected var show_delay:Number = 500;
		protected var hide_delay:Number = 8000;
		protected var isLoadingCache:Boolean = false;
		protected var isLoadingToolTip:Boolean = false;
		protected var markerManager:MarkerManager;
		protected var servlet:RemoteServlet;
		protected var viewableArea:Rectangle = new Rectangle();
		protected var tooltipId:int;
		protected var citiesByAreaTimer:Timer;
		protected var showAllFlag:Boolean = false;
		protected var activeMarker:Marker;

		private var _precision:Number = 100;//precision of 100 sets marker position on 1/100 of a pixel
		public function Map(){
			servlet = new RemoteServlet();
			precision = _precision;
			citiesByAreaTimer = new Timer(1000, 1);
			citiesByAreaTimer.addEventListener(TimerEvent.TIMER, timerHandler, false, 0, true);
			mouseOverTimer = new Timer(500, 1);
			mouseOverTimer.addEventListener(TimerEvent.TIMER, timerHandler, false, 0, true);
			markerToolTip = new MarkerToolTip();
			markers = new MarkerClip();
			markers.addEventListener(MarkerEvent.MOUSE_OUT, mouseHandler, false, 0, true);
			markers.addEventListener(MarkerEvent.MOUSE_DOWN, mouseHandler, false, 0, true);
			markers.addEventListener(MarkerEvent.MOUSE_OVER, mouseHandler, false, 0, true);
			markers.addEventListener(MarkerEvent.CLICK, mouseHandler, false, 0, true);
			addChild(markers);
			markers_cache = new MarkerClip();
			markers_cache.addEventListener(MarkerEvent.MOUSE_OUT, mouseHandler, false, 0, true);
			markers_cache.addEventListener(MarkerEvent.MOUSE_DOWN, mouseHandler, false, 0, true);
			markers_cache.addEventListener(MarkerEvent.MOUSE_OVER, mouseHandler, false, 0, true);
			markers_cache.addEventListener(MarkerEvent.CLICK, mouseHandler, false, 0, true);
			addChild(markers_cache);
			markerManager = new MarkerManager(this);
			markerManager.addEventListener(Event.COMPLETE, completeHandler, false, 0, true);
		}
		public function get precision():Number{
			return _precision;
		}
		public function set precision(value:Number):void{
			_precision = value;
			background.scaleX = background.scaleY = value;
		}
		override public function get width():Number{
			return background.width*scaleX;
		}
		override public function get height():Number{
			return background.height*scaleY;
		}
		public function showAll():void{
			showAllFlag = true;
			markerManager.colorCoef = 40;
			FilterManager.getInstance().addActiveFilter(FilterManager.SHOWALL_KEY, "1");
			if(!isLoadingCache && markers_cache.numChildren == 0){
				isLoadingCache = true;
				servlet.call("Cities.getCitiesCache", citiesCacheHandler, citiesCacheFaultHandler, [viewport.bounds.width]);
				removeMarkers(markers);
			}
			if(activeMarker){
				activeMarker.setActive(false);
				activeMarker = null;
			}
			citiesByAreaTimer.reset();
			if(viewport.zoom > 1){
				getCitiesByArea();
			}
		}
		public function hightlightMarker(id:int, value:Boolean):void{
			var marker:Marker = markerManager.getMarkerById(markers, id);
			if(marker == null) markerManager.getMarkerById(markers_cache, id);
			if(marker) marker.highlight(value);
		}
		public function zoomHandler(event:ZoomEvent):void{
			switch(event.type){
				case ZoomEvent.DRAG_START:
					markers_cache.mouseChildren = false;
					markers.mouseChildren = false;
					break;
				case ZoomEvent.DRAG_STOP:
					markers_cache.mouseChildren = true;
					markers.mouseChildren = true;
					break;
				case ZoomEvent.ZOOM:
					var inverseScale:Number = 1/viewport.scale;
					markers_cache.setScale(inverseScale);
					markers.setScale(inverseScale);
					if(showAllFlag){
						citiesByAreaTimer.reset();
						if(viewport.zoom > 1)citiesByAreaTimer.start();
						else removeMarkers(markers);
					}else updateViewableArea();
					break;
				case ZoomEvent.MOVE:
					if(showAllFlag){
						citiesByAreaTimer.reset();
						if(viewport.zoom > 1)citiesByAreaTimer.start();
						else updateViewableArea();
					}else updateViewableArea();
					break;
			}
		}
		public function resizeHandler(event:Event):void{
			//resize of viewport bounds
			updateViewableArea();
		}
		public function responseHandler(resp:SearchResponse):void{
			showAllFlag = false;
			FilterManager.getInstance().addActiveFilter(FilterManager.SHOWALL_KEY, "0");
			mouseOverTimer.reset();
			citiesByAreaTimer.reset();
			removeMarkers(markers);
			removeMarkers(markers_cache);
			markerManager.colorCoef = 1;
			markerManager.addMarkers(resp.result, markers);
		}
		protected function completeHandler(event:Event):void{
			if(!showAllFlag && autoZoom){
				if(markers.numChildren > 0){
					var markersBounds:Rectangle = markers.getBounds(background);
					var delta:Number = 2*3/viewport.zoom;//width of 2x half of marker (3px)
					var margin:Number = 18;
					var markersBoundsOrigin:Rectangle = markers.getBounds(this);
					var viewportBounds:Rectangle = viewport.bounds;
					var coeff:Number = viewportBounds.width/background.getBounds(background).width;
					markersBounds.width *= coeff;
					markersBounds.height *= coeff;
					var percentage:Number = Math.min((viewportBounds.width-2*margin)/Math.max(1,markersBounds.width-delta), (viewportBounds.height-2*margin)/Math.max(1,markersBounds.height-delta));
					viewport.zoomTo(
						Math.min(20, percentage),
						(markersBoundsOrigin.x + markersBoundsOrigin.width/2),
						(markersBoundsOrigin.y + markersBoundsOrigin.height/2),
						true);
				}
			}
			autoZoom = true;
		}
		protected function mouseHandler(event:MarkerEvent):void{
			switch(event.type){
				case MarkerEvent.MOUSE_OVER:
					mouseOverTimer.reset();
					mouseOverTimer.delay = show_delay;
					mouseOverTimer.start();
					tooltipId = event.marker.id;
					isLoadingToolTip = false;
					break;
				case MarkerEvent.MOUSE_OUT:
				case MarkerEvent.MOUSE_DOWN:
					mouseOverTimer.reset();
					isLoadingToolTip = false;
					if(this.stage.contains(markerToolTip)) this.stage.removeChild(markerToolTip);
					break;
				case MarkerEvent.CLICK:
					var manager:FilterManager = FilterManager.getInstance();
					manager.addActiveFilter(FilterManager.CITYID_KEY, String(event.marker.id));
					searchService.call(new PhotoSearchRequest(manager.getActiveFilters()));
					if(activeMarker)activeMarker.setActive(false);
					activeMarker = event.marker;
					activeMarker.setActive(true);
					break;
			}
		}
		protected function timerHandler(event:TimerEvent):void{
			switch(event.currentTarget){
				case citiesByAreaTimer:
					citiesByAreaTimer.reset();
					if(updateViewableArea())getCitiesByArea();
					break;
				case mouseOverTimer:
					mouseOverTimer.reset();
					if(this.stage.contains(markerToolTip)){
						this.stage.removeChild(markerToolTip);
					}else{
						var filters:Object = {};
						filters[FilterManager.TOOLTIP_KEY] = tooltipId;
						var f:Array = FilterManager.getInstance().getActiveFilters();
						var i:int = f.length;
						while(i--) filters[f[i].key] = f[i].value;
						isLoadingToolTip = true;
						servlet.call("Cities.getCities", tooltipHandler, tooltipHandlerFault, [filters]);
						mouseOverTimer.delay = hide_delay;
						mouseOverTimer.start();
					}
					break;
			}
		}
		protected function getCitiesByArea():void{
			trace("getCitiesByArea", viewableArea.left, viewableArea.top, viewableArea.right, viewableArea.bottom, this.width, viewport.bounds.width);
			servlet.call("Cities.getCitiesByArea", citiesByAreaHandler, citiesByAreaHandlerFault, [viewableArea.left, viewableArea.top, viewableArea.right, viewableArea.bottom, Math.round(this.width), viewport.bounds.width]);
		}
		protected function tooltipHandler(resp:Object):void{
			if(isLoadingToolTip){
				try{
					if(resp.result.length == 1){
						markerToolTip.data = resp.result[0];
						this.stage.addChild(markerToolTip);
					}
				}catch(e:Error){}
			}
			isLoadingToolTip = false;
		}
		protected function tooltipHandlerFault(obj:Object):void{
			isLoadingToolTip = false;
			trace(obj["description"])
		}
		protected function citiesCacheHandler(resp:Array):void{
			trace("citiesCacheHandler", resp.length);
			isLoadingCache = false;
			if(showAllFlag){
				removeMarkers(markers_cache);
				if(resp)markerManager.addMarkers(resp, markers_cache);
			}
		}
		protected function citiesCacheFaultHandler(obj:Object):void{
			isLoadingCache = false;
			trace("citiesCacheHandlerFault::",obj["description"])
		}
		protected function citiesByAreaHandler(resp:Array):void{
			trace("citiesByAreaHandler", resp.length);
			if(showAllFlag){
				removeMarkers(markers);
				if(resp)markerManager.addMarkers(resp, markers);
			}
		}
		protected function citiesByAreaHandlerFault(obj:Object):void{
			
		}
		protected function updateViewableArea():Boolean{
			var viewportBounds:Rectangle = viewport.bounds;
			var mapBounds:Rectangle = background.getBounds(parent);
			var scale:Number = viewport.scale*precision;
			var height:Number = (viewportBounds.height)/scale;
			var rect:Rectangle = new Rectangle(
				(viewportBounds.x - mapBounds.x)/scale,
				(viewportBounds.y - mapBounds.y)/scale,
				(viewportBounds.width)/scale,
				(viewportBounds.height)/scale
			);
			if(!viewableArea.equals(rect)){
				viewableArea = rect;
				var manager:HistoryManager = HistoryManager.getInstance();
				var dec:int = 1000000;//6 decimals
				var projection:MillerProjection = new MillerProjection();
				projection.precision = 1;
				manager.setValue(HistoryManager.HISTORY_LATITUDE, String(Math.round(projection.computeLat((rect.top+rect.bottom)/2)*dec)/dec));
				manager.setValue(HistoryManager.HISTORY_LONGITUDE, String(Math.round(projection.computeLong((rect.left+rect.right)/2)*dec)/dec));
				manager.setValue(HistoryManager.HISTORY_MAGNITUDE, String(Math.round((viewport.zoom)*dec)/dec));
				return true;
			}
			return false;
		}
		private function removeMarkers(target:MarkerClip):void{
			if(activeMarker && target.contains(activeMarker))activeMarker = null;//remove reference for GC
			markerManager.removeMarkers(target);
		}
	}
}
