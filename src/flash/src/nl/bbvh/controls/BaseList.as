package nl.bbvh.controls{

	import flash.events.MouseEvent;
	import flash.events.Event;
	import flash.utils.Timer;
	import flash.events.TimerEvent;
	import flash.ui.Keyboard;
	import flash.events.KeyboardEvent;
	import flash.display.Sprite;

	public class BaseList extends UIControl{

		public var scrollPane:ScrollPane;
		protected var _rowHeight:Number = 22;
		protected var invalidateData:Boolean = false;
		protected var invalidateSelected:Boolean = false;
		protected var _selectedIndices:Array = [];
		protected var _selectable:Boolean=true;
		protected var items:Array = [];
		protected var list:Sprite;
		protected var caretIndex:int = -1;
		protected var lastCaretIndex:int = -1;
		protected var keysPressed:String;
		protected var keypressTimer:Timer;
		protected var inDrag:Boolean = false;
		protected var mousedownTimer:Timer;
		protected var autoScrollDirection:Number = 0;
		public static var sharedStyles:Object = {
			textFormat:null,
			disabledTextFormat:null,
			color:0xFFFFFF,
			textColor:0x000000,
			disabledColor:0xFFFFFF,
			textDisabledColor:0x999999,
			rollOverColor:0xAAAAAA,
			textRollOverColor:0xFFFFFF,
			selectedColor:0x808080,
			textSelectedColor:0xFFFFFF,
			labelMargin:5
		};

		public function BaseList():void{
			super();
			addEventListener(KeyboardEvent.KEY_DOWN, keyPressHandler, false, 0, true);
			keypressTimer = new Timer(1000, 1);
			keypressTimer.addEventListener(TimerEvent.TIMER, resetKeyPressTimer, false, 0, true);
			mousedownTimer = new Timer(1000, 0);
			mousedownTimer.addEventListener(TimerEvent.TIMER, autoScroll, false, 0, true);
		}
		public static function setStyle(name:String, value:Object):void{
			sharedStyles[name] = value;
		}
		public function set rowCount(value:int):void{
			scrollPane.height = rowHeight*Math.max(value, 1);
		}
		public function get rowCount():int {
			return Math.ceil(scrollPane.height/rowHeight);
		}
		public function get rowHeight():Number{
			return _rowHeight;
		}
		public function set rowHeight(value:Number):void{
			if(_rowHeight == value) return;
			_rowHeight = value;
			scrollPane.scrollStepSize = value;
			invalidateData = true;
			invalidate();
		}
		public function get selectable():Boolean{
			return _selectable;
		}
		public function set selectable(value:Boolean):void{
			if(value == _selectable) return;
			if(!value) selectedIndices = [];
			_selectable = value;
		}
		public function get selectedIndex():int {
			return (_selectedIndices.length == 0) ? -1 : _selectedIndices[_selectedIndices.length-1];
		}
		public function set selectedIndex(value:int):void{
			selectedIndices = (value < 0) ? null : [Math.min(value, length-1)];
		}
		public function get selectedIndices():Array {
			return _selectedIndices.concat();
		}
		public function set selectedIndices(value:Array):void{
			if(!_selectable) return;
			_selectedIndices = (value == null) ? [] : value.concat();
			invalidateSelected = true;
			invalidate();
		}
		public function get selectedItem():Object{
			return (_selectedIndices.length == 0) ? null : getItemAt(selectedIndex);
		}
		public function set selectedItem(item:Object):void{
			selectedIndex = items.indexOf(item);
		}
		public function get length():int{
			return items.length;
		}
		public function addItem(item:Object):void{
			items.push(item);
			invalidateData = true;
			invalidate();
		}
		public function addItemAt(item:Object,index:int):void{
			items.splice(index,0,item);
			invalidateData = true;
			invalidate();
		}
		public function addItems(it:Array):void{
			items = items.concat(it);
			invalidateData = true;
			invalidate();
		}
		public function removeAll():void{
			items = [];
			invalidateData = true;
            selectedIndices = [];
			//invalidate();
		}
		public function getItemAt(index:int):Object{
			return items[index];
		}
		public function removeItem(item:Object):Object{
			return removeItemAt(items.indexOf(item));
		}
		public function removeItemAt(index:int):Object{
			var arr:Array = items.splice(index,1);
			if(arr.length){
				invalidateData = true;
                _selectedIndices.splice(selectedIndices.indexOf(index),1);
                invalidateSelected = true;
                invalidate();
				return arr[0];
			}
			return null;
		}
		public function replaceItemAt(item:Object, index:int):Object{
			var arr:Array = items.splice(index,1,item);
			invalidateData = true;
			invalidateSelected = true;
			invalidate();
			return arr[0]||null;
		}
		public function clearSelection():void{
			selectedIndex = -1;
		}
		public function scrollToIndex(value:int):void{
			drawNow();
			var scrollPosition:Number = scrollPane.verticalScrollPosition;
			var lastVisibleIndex:int = Math.floor((scrollPosition + scrollPane.height) / rowHeight) - 1;
			var firstVisibleIndex:int = Math.ceil(scrollPosition / rowHeight);
			if(value < firstVisibleIndex) scrollPane.verticalScrollPosition = value * rowHeight;
			else if(value > lastVisibleIndex) scrollPane.verticalScrollPosition = (value + 1) * rowHeight - scrollPane.height;
		}
		override protected function draw():void{
			var item:ListItem;
			var i:int;
			if(invalidateSize){
				_width = width;
				_height = Math.floor(_height/_rowHeight)*_rowHeight;
				scrollPane.setSize(width, _height);
			}
			if(invalidateData){
				scrollPane.scrollContent = null;
				while(list.numChildren > 0) list.removeChildAt(0);
				var l:int = items.length;
				for(i=0; i<l; i++){
					item = createListItem(i);
					item.selected = selectedIndices.indexOf(i) > -1;
					item.setSize(width, rowHeight);
					list.addChild(item);
				}
                scrollPane.scrollContent = list;
                scrollPane.update();
			}
			if(invalidateStyles || invalidateData){
				i = list.numChildren;
                while(i--) {
					item = list.getChildAt(i) as ListItem;
					copyStylesToChild(item, sharedStyles);
					item.enabled = enabled;
					item.drawNow();
				}
			}
			if(invalidateSelected){
                i = list.numChildren;
				while(i--) {
					item = list.getChildAt(i) as ListItem;
					item.selected = selectedIndices.indexOf(item.index) > -1;
				}
			}
			invalidateData = false;
			invalidateSelected = false;
			super.draw();
		}
        protected function createListItem(index:int):ListItem{
			var item:ListItem = new ListItem();
			item.label = items[index].label;
			item.index = index;
			item.setSize(width, rowHeight);
			item.y = index*rowHeight;
			item.addEventListener(MouseEvent.MOUSE_DOWN, initDrag, false, 0, true);
			return item;
		}
		protected function getIndexByChars(chars:String):int{
			var l:int = chars.length;
			var i:int = -1;
			while(++i<items.length){
				if(chars == items[i].label.substr(0,l).toLowerCase())return i;
			}
			return -1;
		}
		protected function keyPressHandler(event:KeyboardEvent):void{
			if(!selectable) return;
			var index:int = selectedIndex;
			var code:uint = event.keyCode;
			switch(code) {
				case Keyboard.UP:
				case Keyboard.LEFT:
					if(index > 0)index--;
					break;
				case Keyboard.DOWN:
				case Keyboard.RIGHT:
					if(index < items.length - 1)index++;
					break;
				case Keyboard.HOME:
				case Keyboard.PAGE_UP:
					index = 0;
					break;
				case Keyboard.END:
				case Keyboard.PAGE_DOWN:
					index = items.length - 1;
					break;
				default:
					if(code>=32 && code<=126) {
						if(!keypressTimer.running){
							keysPressed = "";
							keypressTimer.start();
						}
						keysPressed += String.fromCharCode(code).toLowerCase();
						var i:int = getIndexByChars(keysPressed);
						if(i>=0)index = i;
					}
			}
			if(index != selectedIndex){
				selectedIndex = index;
				dispatchEvent(new Event(Event.CHANGE));
				invalidateSelected = true;
				drawNow();
			}
			var oldScrollPosition:Number = scrollPane.verticalScrollPosition;
			var newScrollPosition:Number = rowHeight * index - Math.floor(oldScrollPosition / rowHeight) * rowHeight;
			if(newScrollPosition < 0)scrollPane.verticalScrollPosition = rowHeight * index;
			else if(newScrollPosition >= scrollPane.height) scrollPane.verticalScrollPosition = rowHeight * (index - scrollPane.height/rowHeight + 1);
			event.stopPropagation();
		}
		protected function initDrag(event:MouseEvent=null):void{
			if(!inDrag){
				if(scrollPane.height < length * rowHeight){
					inDrag = true;
					stage.addEventListener(MouseEvent.MOUSE_MOVE, updateDrag, false, 0, true);
					stage.addEventListener(MouseEvent.MOUSE_UP, endDrag, false, 0, true);
				}
			}
		}
		protected function endDrag(event:MouseEvent=null):void{
			if(inDrag && stage){
				inDrag = false;
				stage.removeEventListener(MouseEvent.MOUSE_MOVE, updateDrag, false);
				stage.removeEventListener(MouseEvent.MOUSE_UP, endDrag, false);
			}
		}
		protected function updateDrag(event:MouseEvent=null):void{
			if(inDrag){
				var delay:Number= 1;
				if(scrollPane.mouseY < 0){
					delay = Math.abs(scrollPane.mouseY);
					autoScrollDirection = -1;
				}else if(scrollPane.mouseY > scrollPane.height){
					delay = scrollPane.mouseY - scrollPane.height;
					autoScrollDirection = 1;
				}else autoScrollDirection = 0;
				delay = Math.floor(Math.max(10 + 1000/delay, 10) / 10) * 10;
				if(mousedownTimer.delay != delay) mousedownTimer.delay = delay;
				if(!mousedownTimer.running){
					autoScroll(null);
					mousedownTimer.start();
				}
			}
		}
		protected function autoScroll(event:TimerEvent):void{
			var index:int = selectedIndex + autoScrollDirection;
			if(index < 0 || index >= length || autoScrollDirection==0 || !inDrag){
				if(mousedownTimer.running) mousedownTimer.stop();
			}else{
				scrollToIndex(index);
				_selectedIndices = [index];//TODO: handle multipleSelection?
				invalidateSelected = true;
				drawNow();
			}
		}
		protected function resetKeyPressTimer(event:TimerEvent):void{
			keypressTimer.reset();
		}
		override protected function addChildren():void{
			super.addChildren();
			scrollPane = new ScrollPane();
			scrollPane.horizontalScroll = false;
			scrollPane.scrollStepSize = _rowHeight;
			scrollPane.setStyle("border", true);
			list = new Sprite();
		}
	}
}