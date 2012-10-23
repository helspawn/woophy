package com.woophy.ui{

	import com.woophy.core.Application;
	import com.woophy.utils.NetUtils;
	
	import flash.display.Sprite;
	import flash.events.ContextMenuEvent;
	import flash.events.Event;
	import flash.events.IOErrorEvent;
	import flash.events.MouseEvent;
	import flash.net.URLRequest;
	import flash.net.navigateToURL;
	import flash.ui.ContextMenu;
	import flash.ui.ContextMenuItem;

	public class ThumbGrid extends Sprite{

		public var padding:int = 8;
		public var margin:int = 15;
		public var rowCount:int = 1;
		public var colCount:int = 4;
		public var rowHeight:int = 74;
		public var colWidth:int = 74;
		public var borderColor:uint = 0x66C02A;
		public var selectedBorderColor:uint = 0xFFFFFF;
		protected var data:Array = [];
		protected var collection:Array = [];//collection photo_ids
		protected var loadCount:int = 0;
		protected var selectedThumb:Thumbnail;
		protected var thumbContextMenu:ContextMenu;
		protected var contextMenuCaptions:Array = ["View Image", "Enlarge Image", "Open Link in New Window"];
		protected var contextMenuItems:Array = [
			new ContextMenuItem("View Image"),
			new ContextMenuItem("Enlarge Image"),
			new ContextMenuItem("Open Link in New Window", true)
		];

		public function ThumbGrid(){
			mouseChildren = true;
			thumbContextMenu = new ContextMenu();
			thumbContextMenu.hideBuiltInItems();
			for each(var item:ContextMenuItem in contextMenuItems){
				//var item:ContextMenuItem = new ContextMenuItem(caption);
				item.addEventListener(ContextMenuEvent.MENU_ITEM_SELECT, menuItemSelectHandler, false, 0, true);
				thumbContextMenu.customItems.push(item);
			}
		}
		override public function set height(value:Number):void{
			rowCount = Math.max(1, Math.floor((value - 2*margin + padding)/(rowHeight + padding)));
			var miny:Number = height - rowHeight;
			var i:int = numChildren;
			var thumb:Sprite;
			while(i--){
				thumb = Sprite(getChildAt(i));
				thumb.visible = thumb.y < miny;
			}
		}
		override public function get height():Number{
			return rowCount*rowHeight+(rowCount-1)*padding + 2*margin;
		}
		public function clear():void{
			while(numChildren>0){
				var thumb:Thumbnail = getChildAt(0) as Thumbnail;
				thumb.removeEventListener(MouseEvent.CLICK, clickHandler, false);
				thumb.contextMenu = null;
				removeChild(thumb);
			}
			loadCount = 0;
			data.length = 0;
			collection.length = 0;
		}
		public function set dataSource(value:Array):void{
			clear();
			data = value;
			addThumb(null);//start loading
		}
		private function addThumb(event:Event):void{
			var thumb:Thumbnail;
			if(event && event.type == Event.COMPLETE){
				thumb = Thumbnail(event.currentTarget);
				thumb.removeEventListener(Event.COMPLETE, addThumb);
				thumb.removeEventListener(IOErrorEvent.IO_ERROR, addThumb);
				thumb.addEventListener(MouseEvent.CLICK, clickHandler, false, 0, true);
				thumb.buttonMode = true;
				thumb.contextMenu = thumbContextMenu;
			}
			if(loadCount < data.length){
				var o:Object = data[loadCount];

				thumb = new Thumbnail(NetUtils.getPhotoUrl(o["uid"], o["id"], "s"), colWidth, rowHeight);
				thumb.index = collection.push(o["id"]) - 1;
				thumb.data = o["cid"];
				var row:int = Math.ceil((loadCount+1)/colCount);
				var col:int = loadCount%colCount;
				thumb.visible = row<=rowCount;//in case window is resized during load
				thumb.y = margin+(rowHeight+padding)*(row-1);
				thumb.x = margin+(colWidth+padding)*col;
				thumb.addEventListener(Event.COMPLETE, addThumb, false, 0, true);
				thumb.addEventListener(IOErrorEvent.IO_ERROR, addThumb);
				addChild(thumb);
				loadCount++;
			}else data.length = 0;
		}
		protected function menuItemSelectHandler(event:ContextMenuEvent):void{
			var idx:int = contextMenuItems.indexOf(ContextMenuItem(event.currentTarget));
			switch(idx){
				case 0:
					selectThumb(Thumbnail(event.contextMenuOwner));
					break;
				case 1:
				case 2:
					navigateToURL(new URLRequest(Application.base_url+(idx==1?Application.download_url:Application.photo_url)+collection[Thumbnail(event.contextMenuOwner).index]), "_blank");
					break;
			}
		}
		protected function clickHandler(event:MouseEvent):void{
			if(event.shiftKey) navigateToURL(new URLRequest(Application.base_url+Application.photo_url+collection[Thumbnail(event.currentTarget).index]), "_blank");
			else selectThumb(Thumbnail(event.currentTarget));
		}
		protected function selectThumb(thumb:Thumbnail):void{
			if(selectedThumb != null)selectedThumb.borderColor = borderColor;
			selectedThumb = thumb;
			selectedThumb.borderColor = selectedBorderColor;
			NetUtils.getPhotoPopup(collection[selectedThumb.index], collection);
		}
	}
}