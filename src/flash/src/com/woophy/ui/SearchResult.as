package com.woophy.ui{

	import com.woophy.core.RemoteServlet;
	import com.woophy.events.SearchEvent;
	import com.woophy.managers.FilterManager;
	import com.woophy.search.PhotoSearchRequest;
	import com.woophy.search.SearchResponse;
	import com.woophy.search.SearchService;
	import com.woophy.utils.TextStyles;

	import flash.display.Sprite;
	import flash.events.MouseEvent;
	import flash.events.TextEvent;
	import flash.text.TextField;
	import flash.text.TextFieldAutoSize;

	public class SearchResult extends Sprite{

		public var background:Sprite;
		public var header:TextField;
		public var backward:Sprite;
		public var forward:Sprite;
		public var status:TextField;
		public var thumbGrid:ThumbGrid;
		public var searchService:SearchService;
		public var servlet:RemoteServlet;
		protected var limit:int = PhotoSearchRequest.max_limit;
		protected var offset:int = 0;
		protected var total:int = 0;
		protected var pagingNumbers:TextField;
		protected var _height:Number;
		protected var noresults_str:String = "Your search did not match any pictures.";
		protected var inSearch_str:String = "Searching...";
		protected var last_user_id:String;
		protected var last_city_id:String;

		public function SearchResult(){
			header.setTextFormat(TextStyles.white);
			status.setTextFormat(TextStyles.green);
			pagingNumbers = new TextField();
			pagingNumbers.selectable = false;
			pagingNumbers.autoSize = TextFieldAutoSize.LEFT;
			pagingNumbers.defaultTextFormat = TextStyles.green;
			pagingNumbers.y = status.y;
			pagingNumbers.border = false;
			pagingNumbers.addEventListener(TextEvent.LINK, linkHandler, false, 0, true);
			addChild(pagingNumbers);
			backward.buttonMode = forward.buttonMode = true;
			backward.useHandCursor = forward.useHandCursor = true;
			forward.addEventListener(MouseEvent.CLICK, pageForward, false, 0, true);
			backward.addEventListener(MouseEvent.CLICK, pageBackward, false, 0, true);
			servlet = new RemoteServlet();
			clear();
		}
		public function clear():void{
			showButtons(false);
			pagingNumbers.htmlText = "";
			thumbGrid.clear();
			offset = 0;
			total = 0;
			//setHeader("");
		}
		public function setHeader(... args):void{
			var i:int = args.length;
			while(i--) if(args[i].length==0) args.splice(i, 1);
			header.text = args.join(", ");
		}
		public function setStatus(value:String):void{
			status.htmlText = value;
		}
		public function startSearchHandler(event:SearchEvent):void{
			if(event.command != PhotoSearchRequest.TYPE) return;
			//set header:
			//KLUDGE: we can only show one filter, pick the last one:
			//var idx:int = filters.length-1;
			//while(keys[idx] == FilterManager.SHOWALL_KEY && idx>0)idx--;//skip showall key
			//var last_key:String = keys[idx];
			//var last_value:String = decodeURIComponent(values[idx]);
			var filters:Array = FilterManager.getInstance().getActiveFilters();
			if(filters.length == 0) return;
			var idx:int = filters.length-1;
			var last_key:String = filters[idx].key;
			var last_value:String = filters[idx].value;
			if(last_key == FilterManager.USERID_KEY){
				last_city_id = null;
				if(last_user_id != last_value){
					last_user_id = last_value;
					servlet.call("Users.getInfo", onGetUserInfo, onGetUserInfoFault, [last_value]);
				}
			}else if(last_key == FilterManager.CITYID_KEY){
				last_user_id = null;
				if(last_city_id != last_value){
					last_city_id = last_value;
					servlet.call("Cities.getInfo", onGetCityInfo, onGetCityInfoFault, [last_value]);
				}
			}else{
				last_user_id = null;
				last_city_id = null;
				setHeader(FilterManager.getInstance().getFilterByKey(last_key).caption, last_value);
			}
			setStatus(inSearch_str);
		}
		public function responseHandler(resp:SearchResponse):void{
			clear();
			total = resp["total"] || 0;
			total = Math.min(total, PhotoSearchRequest.MAX_OFFSET);
			if(total>0){
				offset = resp["offset"];
				if(isNaN(offset))offset=0;
				var to:int = offset + limit;
				to = Math.min(to, total);
				setStatus("<b>"+String(offset+1)+"</b>-<b>" + String(to) + "</b> of <b>" + String(total) + (total>=PhotoSearchRequest.MAX_OFFSET?"+":"") + "</b>");
				thumbGrid.dataSource = resp["result"];
				if(total>=limit){
					showButtons(true);
					enableButtons(true);
					var numPages:int = Math.ceil(total/limit);
					var maxNum:int = 7;
					var pages:Array = [];
					var firstPage:int = 1;
					var lastPage:int = numPages;
					if(numPages>maxNum){
						var num:int = Math.floor(offset/limit)+1;//start counting at 1
						var d:int = Math.floor(maxNum/2);
						firstPage = Math.max(1, num - d);
						if(firstPage==1)lastPage=Math.min(numPages, maxNum);
						else lastPage = Math.min(numPages, num + d);
						if(lastPage == numPages)firstPage = Math.max(1, 1 + lastPage - maxNum);
					}
					if(firstPage > 1) pages.push("...");
					var i:int = firstPage;
					while(i <= lastPage){
						if(offset == (i-1)*limit) pages.push("<b>"+(i)+"</b>");
						else pages.push("<u><a href=\"event:"+((i-1)*limit)+"\">"+(i)+"</a></u>");
						i++;
					}
					if(lastPage < numPages) pages.push("...");
					pagingNumbers.htmlText = pages.join("&nbsp;&nbsp;");
					pagingNumbers.x = forward.x - pagingNumbers.width - 2;
					backward.x = pagingNumbers.x - 2;
				}
			}else setStatus(noresults_str);
		}
		public function pageForward(event:MouseEvent=null):void{
			if(offset+limit<total) pageTo(offset + limit);
		}
		public function pageBackward(event:MouseEvent=null):void{
			if(offset>0) pageTo(offset - limit);
		}
		public function pageTo(offset:int):void{
			if(searchService == null)return;
			searchService.call(new PhotoSearchRequest(FilterManager.getInstance().getActiveFilters(), offset, limit));
			enableButtons(false);
		}
		public function showButtons(bln:Boolean):void{
			forward.visible = backward.visible = pagingNumbers.visible = bln;
		}
		public function enableButtons(bln:Boolean):void{
			pagingNumbers.mouseEnabled = bln;
			backward.mouseEnabled = bln;
			forward.mouseEnabled = bln;
		}
		override public function set height(value:Number):void{
			_height = value;
			background.height = value;
			thumbGrid.height = value - thumbGrid.y;
			PhotoSearchRequest.max_limit = thumbGrid.colCount*thumbGrid.rowCount;
			limit = PhotoSearchRequest.max_limit;
		}
		protected function linkHandler(event:TextEvent):void{
			pageTo(int(event.text));
		}
		protected function onGetCityInfo(resp:Object):void{
			if(resp && resp["name"] != undefined && resp["country_name"] != undefined)setHeader(resp["name"], resp["country_name"]);
		}
		protected function onGetCityInfoFault(obj:Object):void{
			trace("onGetCityFault", obj["description"]);
		}
		protected function onGetUserInfo(resp:Object):void{
			if(resp && resp["name"] != undefined)setHeader("Member", resp["name"]);
		}
		protected function onGetUserInfoFault(obj:Object):void{
			trace("onGetUserInfoFault", obj["description"]);
		}
	}
}