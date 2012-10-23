package com.woophy{

	import com.woophy.core.Application;
	import com.woophy.events.SearchEvent;
	import com.woophy.managers.FilterManager;
	import com.woophy.managers.HistoryManager;
	import com.woophy.search.Filter;
	import com.woophy.search.MapSearchRequest;
	import com.woophy.search.PhotoSearchRequest;
	import com.woophy.search.SearchRequest;
	import com.woophy.search.SearchService;
	import com.woophy.ui.LinkBar;
	import com.woophy.ui.Map;
	import com.woophy.ui.MapBackground;
	import com.woophy.ui.ScaleBar;
	import com.woophy.ui.SearchLayout;
	import com.woophy.ui.Thumbnail;
	import com.woophy.ui.ZoomControls;
	import com.woophy.utils.MillerProjection;
	import com.woophy.utils.TextStyles;
	
	import flash.display.LoaderInfo;
	import flash.display.Sprite;
	import flash.display.StageAlign;
	import flash.display.StageQuality;
	import flash.display.StageScaleMode;
	import flash.events.Event;
	import flash.events.MouseEvent;
	import flash.events.TimerEvent;
	import flash.geom.Rectangle;
	import flash.utils.Timer;
	
	import nl.bbvh.events.ZoomEvent;
	import nl.bbvh.zoom.ZoomViewport;

	public class MapHome extends Sprite{

		public static const LEFT_MARGIN:Number = 5;
		public static const RIGHT_MARGIN:Number = 5;
		public static const TOP_MARGIN:Number = 26;

		private var zoomViewport:ZoomViewport;
		public var zoomControls:ZoomControls;
		public var map:Map;
		public var searchLayout:SearchLayout;
		public var scaleBar:ScaleBar;
		public var mapBackground:MapBackground;
		public var linkBar:LinkBar;
		private var searchService:SearchService;
		private var stageSizeTimer:Timer;
		function MapHome(){
			stage.scaleMode = StageScaleMode.NO_SCALE;
			stage.align = StageAlign.TOP_LEFT;
			stage.showDefaultContextMenu=false;
			stage.quality = StageQuality.LOW;	
			stageSizeTimer = new Timer(100, 0);//be sure stageWidth and stageHeight are correct, IE bug with cached swf
			stageSizeTimer.addEventListener(TimerEvent.TIMER, timerHandler);
			stageSizeTimer.start();
			timerHandler(null);
		}
		private function timerHandler(event:TimerEvent):void{
			if(stage.stageHeight > 0 && stage.stageWidth > 0){
				stageSizeTimer.reset();
				stageSizeTimer.removeEventListener(TimerEvent.TIMER, timerHandler);
				stageSizeTimer = null;
				stage.addEventListener(Event.RESIZE, resizeHandler, false, 0, true);	
				init();
			}
		}
		private function init():void{	
			if(searchService != null) return;//be sure to run once
			var param:Object = LoaderInfo(root.loaderInfo).parameters;
			if(param["base_url"] != null) Application.base_url = param["base_url"];
			if(param["service_url"] != null) Application.base_url = param["service_url"];
			if(param["photo_url"] != null) Application.photo_url = param["photo_url"];
			if(param["images_url"] != null) Application.images_url = param["images_url"];		

			searchService = new SearchService();
			searchService.addEventListener(PhotoSearchRequest.TYPE, searchLayout.searchResult.responseHandler, false, 0, true);
			searchService.addEventListener(SearchEvent.START, searchLayout.startSearchHandler, false, 0, true);
			searchService.addEventListener(SearchEvent.START, searchLayout.searchResult.startSearchHandler, false, 0, true);
			searchService.addEventListener(MapSearchRequest.TYPE, map.responseHandler, false, 0, true);

			linkBar.searchService = searchService;
			linkBar.getStatus();
			searchLayout.searchForm.searchService = searchService;
			searchLayout.searchResult.searchService = searchService;
			map.searchService = searchService;

			var showall:Boolean = false;
			var filters:Array = [];
			var numFilters:int = 0;
			if(param[HistoryManager.HISTORY_FILTERKEY] != undefined && param[HistoryManager.HISTORY_FILTERVALUE] != undefined){//restore search before restore zoom, because zoomarea is adjusted after search
				var keys:Array = param[HistoryManager.HISTORY_FILTERKEY].split(",");//look for multiple keys
				var values:Array = param[HistoryManager.HISTORY_FILTERVALUE].split(",");
				var offset:int = 0;
				//var get_city:Boolean = false;
				if(param[HistoryManager.HISTORY_OFFSET] != undefined){
					offset = int(param[HistoryManager.HISTORY_OFFSET]);
				}
				if(keys.length == values.length){
					var filterManager:FilterManager = FilterManager.getInstance();
					var i:int = keys.length;
					while(i--){
						if(keys[i] == FilterManager.SHOWALL_KEY){
							if(int(values[i]) == 1) showall = true;
						}else numFilters++;
						filterManager.addActiveFilter(keys[i], values[i]);
					}
					var req:SearchRequest;
					filters = filterManager.getActiveFilters();
					if(numFilters > 0){
						if(!showall){
							req = new MapSearchRequest(filters);
							searchService.call(req);
						}

						req = new PhotoSearchRequest(filters, offset);
						searchService.call(req);
						map.autoZoom = false;
					}
				}
			}
			//r'dam:
			//param[HISTORY_MAGNITUDE]=17.68802;
			//param[HISTORY_LONGITUDE]=4.497598;
			//param[HISTORY_LATITUDE]=51.915549;

			var lat:Number = param[HistoryManager.HISTORY_LATITUDE] || 14;
			var lng:Number = param[HistoryManager.HISTORY_LONGITUDE] || 0;
			var mag:Number = param[HistoryManager.HISTORY_MAGNITUDE] || 1;
			

			zoomViewport = new ZoomViewport();
			zoomViewport.zoomRectColor = TextStyles.COLOR_GREEN;
			addChildAt(zoomViewport, getChildIndex(map));
			map.viewport = zoomViewport;
			zoomControls.viewport = zoomViewport;
			zoomControls.zoomTool.selected = true;

			zoomViewport.addEventListener(ZoomEvent.ZOOM, map.zoomHandler, false, 0, true);
			zoomViewport.addEventListener(ZoomEvent.DRAG_START, map.zoomHandler, false, 0, true);
			zoomViewport.addEventListener(ZoomEvent.DRAG_STOP, map.zoomHandler, false, 0, true);
			zoomViewport.addEventListener(ZoomEvent.MOVE, map.zoomHandler, false, 0, true);
			zoomViewport.addEventListener(Event.RESIZE, map.resizeHandler, false, 0, true);
			zoomViewport.addEventListener(ZoomEvent.ZOOM, zoomControls.zoomHandler, false, 0, true);
			zoomViewport.addEventListener(Event.RESIZE, zoomControls.resizeHandler, false, 0, true);
			zoomViewport.addEventListener(ZoomEvent.ZOOM, scaleBar.zoomHandler, false, 0, true);

			if(numFilters == 0)searchLayout.showResults(false);//before adding event handlers
			else{
				var f:Filter = filters[numFilters-1];
				if(f.isPublic){
					searchLayout.searchForm.setInput(f.value);
					searchLayout.searchForm.setFilter(f.key);
				}
			}
			searchLayout.addEventListener(Event.CLOSE, closeHandler, false, 0, true);
			searchLayout.addEventListener(Event.OPEN, resizeHandler, false, 0, true);
			searchLayout.searchResult.thumbGrid.addEventListener(MouseEvent.MOUSE_OVER, thumbHandler, false, 0, true);
			searchLayout.searchResult.thumbGrid.addEventListener(MouseEvent.MOUSE_OUT, thumbHandler, false, 0, true);

			resizeHandler(null);//set bounds before zoomTo
			var projection:MillerProjection = new MillerProjection();
			projection.precision = map.precision;
			zoomViewport.content = map;//set content after bounds
			zoomViewport.zoomTo(mag, projection.computeX(lng), projection.computeY(lat), true);

			if(showall || numFilters == 0){
				map.showAll();
			}
		}
		private function resizeHandler(event:Event):void{
			linkBar.y = stage.stageHeight - LinkBar.HEIGHT;
			linkBar.width = stage.stageWidth;
			var w:Number = stage.stageWidth - LEFT_MARGIN - (searchLayout.searchResult.visible ? SearchLayout.WIDTH : RIGHT_MARGIN);
			var h:Number = linkBar.y - TOP_MARGIN;
			zoomViewport.bounds = new Rectangle(LEFT_MARGIN, TOP_MARGIN, w, h);
			zoomControls.y = h - zoomControls.height;
			scaleBar.x = w - zoomControls.x + 9;//9: 2*5px border - 1px linewidth
			scaleBar.y = h;
		}
		private function closeHandler(event:Event):void{
			resizeHandler(null);//resize before showAll!
			map.showAll();
		}
		private function thumbHandler(event:MouseEvent):void{
			if(event.target is Thumbnail){
				map.hightlightMarker(event.target.data, event.type == MouseEvent.MOUSE_OVER)
			}
		}
	}
}