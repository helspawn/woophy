package com.woophy.ui{

	import com.woophy.managers.FilterManager;
	import com.woophy.search.Filter;
	import com.woophy.search.MapSearchRequest;
	import com.woophy.search.PhotoSearchRequest;
	import com.woophy.search.SearchService;
	import com.woophy.utils.TextStyles;

	import flash.display.SimpleButton;
	import flash.display.Sprite;
	import flash.events.FocusEvent;
	import flash.events.KeyboardEvent;
	import flash.events.MouseEvent;
	import flash.filters.BitmapFilterQuality;
	import flash.filters.DropShadowFilter;
	import flash.text.TextField;
	import flash.text.TextFormat;
	import flash.ui.Keyboard;

	import nl.bbvh.controls.DropDownList;
	import nl.bbvh.utils.StringUtils;

	public class SearchForm extends Sprite{

		public var submit:SimpleButton;
		public var search_text:TextField;
		public var dropDownList:DropDownList;
		public var searchService:SearchService;

		protected var borderColor:uint = 0x4B911E;
		protected var isLoadingThumbs:Boolean = false;
		protected var isLoadingCities:Boolean = false;
		protected var search_str:String = "Search";

		function SearchForm(){
			var tf:TextFormat = TextStyles.normal;
			dropDownList.scrollPane.setStyle("borderColor", borderColor);
			dropDownList.scrollPane.filters = [new DropShadowFilter(2,90,0x000000,0.5,5,5,0.65,BitmapFilterQuality.LOW)];
			with(dropDownList){
				setStyle("textColor", tf.color);
				setStyle("selectedColor", borderColor);
				setStyle("textFormat", tf);
				setStyle("labelMargin", 2);
			}
			for each(var filter:Filter in FilterManager.getInstance().getFilters()){
				if(filter.isPublic) dropDownList.addItem({label:filter.caption, data:filter.key});
			}
			dropDownList.selectedIndex = 0;

			with(search_text){
				defaultTextFormat = tf;
				text = search_str;
				addEventListener(FocusEvent.FOCUS_IN, focusHandler, false, 0, true);
				addEventListener(FocusEvent.FOCUS_OUT, focusHandler, false, 0, true);
				addEventListener(KeyboardEvent.KEY_DOWN, keyDownHandler, false, 0, true);
				border = true;
			}
			search_text.borderColor = borderColor;
			submit.addEventListener(MouseEvent.CLICK, clickHandler, false, 0, true);
		}
		public function clear():void{
			dropDownList.selectedIndex = 0;
			setInput(search_str);
		}
		public function setInput(value:String):void{
			search_text.text = value;
		}
		public function setFilter(value:String):void{
			dropDownList.value = value;
		}
		protected function enableSubmit():void{
			submit.enabled = submit.mouseEnabled = (!isLoadingThumbs && !isLoadingCities);
		}
		protected function focusHandler(event:FocusEvent):void{
			if(event.type == FocusEvent.FOCUS_IN){
				if(search_text.text == search_str)setInput("");
			}else{
				if(search_text.text.length==0)setInput(search_str);
			}
		}
		protected function keyDownHandler(event:KeyboardEvent):void{
			if(event.keyCode == Keyboard.ENTER) clickHandler(null);
		}
		protected function clickHandler(event:MouseEvent):void{
			var val:String = StringUtils.removeExtraWhitespace(search_text.text);
			if(val != search_str && val.length >0){
				var manager:FilterManager = FilterManager.getInstance();
				manager.setActiveFilter(String(dropDownList.value), val);
				var filters:Array = manager.getActiveFilters();
				if(searchService.call(new PhotoSearchRequest(filters))){
					searchService.call(new MapSearchRequest(filters));
				}
			}
		}
	}
}