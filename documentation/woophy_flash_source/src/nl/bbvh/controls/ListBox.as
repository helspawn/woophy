package nl.bbvh.controls{
	
	import flash.events.MouseEvent;
	import flash.events.Event;
	
	public class ListBox extends BaseList{

		protected var _allowMultipleSelection:Boolean = false;

		public function ListBox():void{
			super();
			addEventListener(MouseEvent.MOUSE_DOWN, mouseEventHandler, false, 0, true);
		}
		public function get allowMultipleSelection():Boolean{
			return _allowMultipleSelection;
		}
		public function set allowMultipleSelection(value:Boolean):void{
			if(value == _allowMultipleSelection) return;
			_allowMultipleSelection = value;
			if(!value && _selectedIndices.length > 1){
				_selectedIndices = [_selectedIndices.pop()];
				invalidateSelected = true;
				invalidate();
			}
		}
		public function get selectedItems():Array{
			var items:Array = [];
			for(var i:int = 0, l:int=_selectedIndices.length; i < l; i++){
				items.push(getItemAt(_selectedIndices[i]));
			}
			return items;
		}
		public function set selectedItems(value:Array):void{
			var indices:Array = [];
			if(value != null){
				for(var i:int = 0, l:int = value.length; i < l; i++){
					var index:int = items.indexOf(value[i]);
					if(index != -1)indices.push(index);
				}
			}
			selectedIndices = indices;
		}
		override public function set enabled(value:Boolean):void{
			super.enabled = value;
			scrollPane.enabled = value;
		}
		override protected function createListItem(index:int):ListItem{
			var item:ListItem = super.createListItem(index);
			item.addEventListener(MouseEvent.MOUSE_DOWN, itemEventHandler, false, 0, true);
			item.addEventListener(MouseEvent.MOUSE_OVER, itemEventHandler, false, 0, true);
			return item;
		}
		protected function itemEventHandler(event:MouseEvent):void{
			if(!_enabled) return;
			if(!_selectable) return;
			if(event.type == MouseEvent.MOUSE_OVER && !inDrag)return;
			var item:ListItem = event.currentTarget as ListItem;
			var itemIndex:int = item.index;
			var selectIndex:int = selectedIndices.indexOf(itemIndex);
			var i:int;
			if(!_allowMultipleSelection){
				if(selectIndex != -1) return;
				else _selectedIndices = [itemIndex];
				lastCaretIndex = caretIndex = itemIndex;
			}else{
				if(event.shiftKey){
					var oldIndex:int = (_selectedIndices.length > 0) ? _selectedIndices[0] : itemIndex;
					_selectedIndices = [];
					if(oldIndex > itemIndex){
						for(i = oldIndex; i >= itemIndex; i--) _selectedIndices.push(i);
					}else{
						for(i = oldIndex; i <= itemIndex; i++) _selectedIndices.push(i);
					}
					caretIndex = itemIndex;
				}else if(event.ctrlKey){
					if(selectIndex != -1) _selectedIndices.splice(selectIndex,1);
					else _selectedIndices.push(itemIndex);
					caretIndex = itemIndex;
				}else{
					_selectedIndices = [itemIndex];
					lastCaretIndex = caretIndex = itemIndex;
				}
			}
			dispatchEvent(new Event(Event.CHANGE));
			invalidateSelected = true;
			drawNow();
			event.updateAfterEvent();
		}
		protected function mouseEventHandler(event:MouseEvent):void{
			stage.focus = this;
			if(!(event.target is ListItem)){
				if(list.mouseChildren){
					stage.addEventListener(MouseEvent.MOUSE_UP, stageEventHandler, false, 0, true);
					list.mouseChildren = false;
				}
			}
		}
		protected function stageEventHandler(event:Event):void{
			if(!list.mouseChildren){
				list.mouseChildren = true;
				stage.removeEventListener(MouseEvent.MOUSE_UP, stageEventHandler, false);
			}
		}
		override protected function addChildren():void{
			super.addChildren();
			addChild(scrollPane);
		}
	}
}