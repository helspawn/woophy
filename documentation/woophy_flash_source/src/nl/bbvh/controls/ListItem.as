package nl.bbvh.controls{

	public class ListItem extends LabelButton{

		public var index:int = -1;
		/*
		private var color:uint;
		private var textColor:uint;
		private var disabledColor:uint;
		private var textDisabledColor:uint;
		private var rollOverColor:uint;
		private var textRollOverColor:uint;
		private var selectedColor:uint;
		private var textSelectedColor:uint;
		*/
		public function ListItem():void{
			super();
			tabEnabled = false;
			_selectable = true;//do not add event listeners
		}
		override public function set selected(value:Boolean):void{
			if(_selected == value) return;
			_selected = value;
			drawBackground();
		}
		override protected function draw():void{
			drawBackground();
			var height:Number = _height;
			super.draw();
			_height = height;//rowHeight overrides height
		}
		protected function drawBackground():void{
			var color:uint = getStyle(enabled ? (_selected ? "selectedColor" : (mouseState =="over" ? "rollOverColor" : "color")) : "disabledColor") as uint;
			var textColor:uint = getStyle(enabled ? (_selected ? "textSelectedColor" : (mouseState == "over" ? "textRollOverColor" : "textColor")) : "textDisabledColor") as uint;
			graphics.beginFill(color, 100);
			graphics.drawRect(0, 0, width, height);
			graphics.endFill();
			textField.textColor = textColor;
		}
	}
}