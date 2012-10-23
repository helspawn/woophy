package nl.bbvh.controls{

	import flash.events.Event;
	import flash.events.MouseEvent;
	import flash.events.KeyboardEvent;
	import flash.ui.Keyboard;
	import flash.geom.Point;
	import flash.text.TextField;
	import flash.text.TextFormat;
	import flash.text.TextFieldAutoSize;
	import flash.text.TextLineMetrics;

	public class DropDownList extends BaseList{

		protected var _text:String;
		protected var _rowCount:int = 5;
		protected var _selectedIndex:int;
		protected var skin:Button;
		protected var textField:TextField;
		protected var isOpen:Boolean = false;
		protected var buttonWidth:Number = 20;
		protected var mouseDown:Boolean = false;
		public static var sharedStyles:Object = {
			upSkin:DropDownList_up,
			overSkin:DropDownList_over,
			downSkin:DropDownList_down,
			disabledSkin:DropDownList_disabled,
			rollOverColor:0xFFFFFF,
			textRollOverColor:0x000000
		};

		public function DropDownList():void{
			super();
			_selectedIndex = selectedIndex;
		}
		public static function setStyle(name:String, value:Object):void{
			sharedStyles[name] = value;
		}
		public function get text():String{
			return _text;
		}
		public function set text(value:String):void{
			_text = value;
			invalidateSelected = true;
			invalidate();
		}
		override public function set rowCount(value:int):void{
			_rowCount = value;
			invalidateSize = true;
			invalidate();
		}
		override public function get rowCount():int{
			return _rowCount;
		}
		override public function set selectedIndices(value:Array):void{
			super.selectedIndices = value == null ? value : value.slice(value.length-1);
			var index:int = selectedIndex;
			if(_selectedIndex != index){
				_selectedIndex = index;
				dispatchEvent(new Event(Event.CHANGE));
			}
		}
		public function get value():Object{
			if(_selectedIndex>-1) return items[_selectedIndex].data;
			return null;
		}
		public function set value(value:Object):void{
			var index:int = length;
			while(index--){
				if(items[index].data == value) break;
			}
			selectedIndex = index;
		}
		public function get selectedLabel():String{
			if(_selectedIndex>-1) return items[_selectedIndex].label;
			return null;
		}
		public function open():void{
			if(isOpen || length == 0) return;
			isOpen = true;
			var point:Point = localToGlobal(new Point(0,0));
			scrollPane.x = point.x;
			if(point.y + height + scrollPane.height > stage.stageHeight) scrollPane.y = point.y - scrollPane.height;
			else scrollPane.y = point.y + height - 1;//-1 pixel border
			if(_selectedIndex != selectedIndex) selectedIndex = _selectedIndex;
			scrollToIndex(selectedIndex);
			stage.addChild(scrollPane);
			stage.addEventListener(MouseEvent.MOUSE_DOWN, stageEventHandler, false, 0, true);
			stage.addEventListener(MouseEvent.MOUSE_UP, stageEventHandler, false, 0, true);
		}
		public function close():void{
			if(!isOpen) return;
			stage.removeEventListener(MouseEvent.MOUSE_DOWN, stageEventHandler, false);
			stage.removeEventListener(MouseEvent.MOUSE_UP, stageEventHandler, false);
			isOpen = false;
			stage.removeChild(scrollPane);
			endDrag(null);
		}
		override public function set enabled(value:Boolean):void {
			super.enabled = value;
			skin.enabled = enabled;
			close();
		}
		override protected function draw():void{
			var margin:Number = getStyle("labelMargin") as Number;
			if(invalidateStyles){
				copyStylesToChild(skin, sharedStyles);
				var tf:TextFormat = getStyle(enabled?"textFormat":"disabledTextFormat") as TextFormat;
				if(tf != null){
					textField.setTextFormat(tf);
					textField.defaultTextFormat = tf;
				}
				textField.embedFonts = getStyle("embedFonts") as Boolean;
				textField.x = margin;	
				var metrics:TextLineMetrics = textField.getLineMetrics(0);
				textField.y = (height-(metrics.height - metrics.leading + 4))/2;
			}
			if(invalidateSize){
				textField.width = Math.max(0, width - margin - buttonWidth);
				skin.setSize(width, height);
			}
			if(invalidateSelected){
				textField.text = _selectable ? selectedLabel || _text || "" : "";
			}
			var invalidate:Boolean = invalidateSize || invalidateData;
			super.draw();
			if(invalidate){
				_height = skin.height;
				scrollPane.setSize(width, Math.min(length, _rowCount)*rowHeight);
			}
			//if(invalidateSelected){
			//	textField.text = _selectable ? selectedLabel || _text || "" : "";
			//}
		}
		override protected function createListItem(index:int):ListItem{
			var item:ListItem = super.createListItem(index);
			item.addEventListener(MouseEvent.MOUSE_OVER, itemRollOverHandler, false, 0, true);
			return item;
		}
		override protected function addChildren():void{
			super.addChildren();
			skin = new Button();
			skin.addEventListener(MouseEvent.MOUSE_DOWN, skinEventHandler, false, 0, true);
			addChild(skin);
			textField = new TextField();
			textField.selectable = false;
			textField.mouseEnabled = false;
			addChild(textField);
		}
		protected function itemRollOverHandler(event:MouseEvent):void{
			if(!_enabled) return;
			if(!_selectable) return;
			var item:ListItem = event.currentTarget as ListItem;
			var itemIndex:int = item.index;
			_selectedIndices = [itemIndex];
			if(mouseDown)initDrag();
			invalidateSelected = true;
			drawNow();
			event.updateAfterEvent();
		}
		protected function skinEventHandler(event:MouseEvent):void{
			if(isOpen)close();
			else{
				open();
				mouseDown = true;
			}
		}
		protected function stageEventHandler(event:MouseEvent):void{
			if(!isOpen) return;
			if(event.type == MouseEvent.MOUSE_UP){
				if(inDrag || event.target is ListItem){
					selectedIndex = selectedIndex;
					close();
				}
				list.mouseChildren = true;
				mouseDown = false;
			}else{//MOUSE_DOWN
				//stage.focus = this;
				if(!mouseDown){
					//if(!scrollPane.contains(event.target as DisplayObject))//doesn't work with Button instances
					if(!scrollPane.hitTestPoint(stage.mouseX, stage.mouseY, false)){
						selectedIndex = _selectedIndex;
						close();
						return;
					}
					stage.focus = this;
					if(!(event.target is ListItem)) list.mouseChildren = false;//hit scrollbar, disable listitems
				}
			}
		}
		override protected function keyPressHandler(event:KeyboardEvent):void{
			if(event.keyCode == Keyboard.ESCAPE || event.keyCode == Keyboard.ENTER){
				selectedIndex = selectedIndex;
				close();
				return;
			}
			super.keyPressHandler(event);
		}
	}
}