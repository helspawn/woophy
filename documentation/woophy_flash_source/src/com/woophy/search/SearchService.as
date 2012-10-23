package com.woophy.search{
	import com.woophy.core.RemoteServlet;
	import com.woophy.events.SearchEvent;
	import com.woophy.managers.FilterManager;
	import com.woophy.managers.HistoryManager;

	import flash.events.EventDispatcher;


	public class SearchService extends EventDispatcher{
		protected var servlet:RemoteServlet;
		protected var isLoadingPhotos:Boolean = false;
		protected var isLoadingCities:Boolean = false;
		protected var filterManager:FilterManager;
		protected var historyManager:HistoryManager;
		public function SearchService(){
			servlet = new RemoteServlet();
			filterManager = FilterManager.getInstance();
			historyManager = HistoryManager.getInstance();
		}
		public function call(req:SearchRequest):Boolean{
			if(req.filters.length==0) return false;
			if(req is MapSearchRequest && isLoadingCities) return false;
			if(req is PhotoSearchRequest && isLoadingPhotos) return false;
			var i:int = req.filters.length;
			var keys:Array = [];
			var values:Array = [];
			var filters:Object = {};
			var value:String;
			var key:String;
			while(i--){
				key = req.filters[i].key;
				if(keys.indexOf(key)>=0) continue;
				value = req.filters[i].value;
				keys.push(key);
				values.push(encodeURIComponent(value));
				filters[key] = value;
			}

			//trace("keys:",keys.toString());
			//trace(values.toString());
			if(req.hasOwnProperty("offset"))historyManager.setValue(HistoryManager.HISTORY_OFFSET, String(req["offset"]));
			historyManager.setValue(HistoryManager.HISTORY_FILTERKEY, keys.join(","));
			historyManager.setValue(HistoryManager.HISTORY_FILTERVALUE, values.join(","));
			if(req is MapSearchRequest){
				isLoadingCities = true;
				servlet.call(req.command, citiesRequestHandler, citiesRequestHandlerFault, [filters]);
			}else if(req is PhotoSearchRequest){
				isLoadingPhotos = true;
				filters[FilterManager.LIMIT] = req["offset"]+","+req["limit"];//TRICKY: limit is passed as extra filter
				servlet.call(req.command, photosRequestHandler, photosRequestHandlerFault, [filters]);
			}
			dispatchEvent(new SearchEvent(SearchEvent.START, req.command));
			return true;
		}
		public function cancel():void{

		}
		public function clear():void{
		}
		protected function citiesRequestHandler(resp:Object):void{
			isLoadingCities = false;
			var event:SearchResponse = new SearchResponse(MapSearchRequest.TYPE);
			if(resp != null){
				event.result = resp["result"] || [];
				event.total = resp["total"] || 0;
			}
			dispatchEvent(event);
		}
		protected function photosRequestHandler(resp:Object):void{
			isLoadingPhotos = false;
			//trace("photosRequestHandler",resp)
			//for(var p:String in resp){
				//trace(p,resp[p]);
			//}
			var event:SearchResponse = new SearchResponse(PhotoSearchRequest.TYPE);
			if(resp != null){
				event.offset = resp["offset"] || 0;
				event.total = resp["total"] || 0;
				event.result = resp["result"] || [];
			}
			dispatchEvent(event);
		}
		protected function citiesRequestHandlerFault(obj:Object):void{
			isLoadingCities = false;
			//trace(obj["description"]);
		}
		protected function photosRequestHandlerFault(obj:Object):void{
			isLoadingPhotos = false;
			//trace(obj["description"]);
		}
	}
}