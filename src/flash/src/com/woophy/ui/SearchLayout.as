package com.woophy.ui{
	import com.woophy.managers.FilterManager;
	import com.woophy.managers.HistoryManager;
	import com.woophy.utils.TextStyles;

	import flash.display.Sprite;
	import flash.events.Event;
	import flash.events.TextEvent;
	import flash.text.TextField;
	import flash.text.TextFieldAutoSize;

	public class SearchLayout extends Sprite{
		public var searchForm:SearchForm;
		public var searchResult:SearchResult;
		public var clearResult:Sprite;
		public static const WIDTH:Number = 350;
		protected var clear_str:String = "Clear search";
		public function SearchLayout(){
			var label:TextField = new TextField();
			label.x = 26;
			label.y = 13;
			label.htmlText = "<a href=\"event:\">"+clear_str+"</a>";
			label.autoSize = TextFieldAutoSize.LEFT;
			label.selectable = false;
			label.styleSheet = TextStyles.link;
			label.addEventListener(TextEvent.LINK, linkHandler, false, 0, true);
			clearResult.addChild(label);
			stage.addEventListener(Event.RESIZE, resizeHandler, false, 0, true);
			resizeHandler(null);
		}
		public function startSearchHandler(event:Event):void{
			showResults(true);
		}
		public function showResults(value:Boolean = true):void{
			if(searchResult.visible != value){
				searchResult.visible = value;
				clearResult.visible = value;
				dispatchEvent(new Event(value ? Event.OPEN : Event.CLOSE));
			}
		}
		protected function linkHandler(event:TextEvent):void{
			searchForm.clear();
			searchResult.clear();
			searchResult.setHeader("");
			FilterManager.getInstance().removeActiveFilters();
			with(HistoryManager.getInstance()){
				removeValue(HistoryManager.HISTORY_OFFSET);
				removeValue(HistoryManager.HISTORY_FILTERKEY);
				removeValue(HistoryManager.HISTORY_FILTERVALUE);
			}
			showResults(false);
		}
		protected function resizeHandler(event:Event):void{
			x = stage.stageWidth - WIDTH;
			clearResult.y = stage.stageHeight - y - clearResult.height - LinkBar.HEIGHT;
			searchResult.height = clearResult.y - searchResult.y;
		}
	}
}