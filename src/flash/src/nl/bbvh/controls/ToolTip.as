package nl.bbvh.controls{
	
	import flash.filters.DropShadowFilter;
	import flash.utils.Timer;
	import flash.events.Event;
	import flash.events.TimerEvent;
	import flash.events.MouseEvent;
	import flash.display.DisplayObject;
	import flash.text.TextField;
	import flash.text.TextFieldAutoSize;
	import flash.text.TextFormat;

	public class ToolTip extends UIControl{
		private var _owner:DisplayObject;
		private var _label:String;
		private var textField:TextField;
		private var showhideTimer:Timer;
		public static var sharedStyles:Object = {
			hideDelay:6000,
			showDelay:400,
			backgroundColor:0xFFFFFF,
			borderColor:0x000000,
			useShadow:true
		};

		public function ToolTip(owner:DisplayObject=null, label:String = ""){
			super();
			this.owner = owner;
			_label = label;
			showhideTimer= new Timer(getStyle("showDelay") as Number, 0);
			showhideTimer.addEventListener(TimerEvent.TIMER,showHide,false,0,true);
		}
		public static function setStyle(name:String, value:Object):void{
			sharedStyles[name] = value;
		}
		public function set label(value:String):void{
			_label = value;
			invalidate();
		}
		public function get label():String{
			return _label;
		}
		public function show():void{
			if(_owner.stage){
				var x:Number = _owner.stage.mouseX;
				var y:Number = _owner.stage.mouseY - textField.height;
				if((x+textField.width) > _owner.stage.stageWidth) x -= textField.width;
				if(y < 0) y += textField.height + 21;
				textField.x = x;
				textField.y = y;	
				_owner.stage.addChild(textField);
			}
		}
		public function hide():void{
			if(_owner.stage && textField.parent)_owner.stage.removeChild(textField);
			showhideTimer.reset();
		}
		public function set owner(value:DisplayObject):void{
			if(_owner != null){
				_owner.removeEventListener(MouseEvent.ROLL_OVER, mouseEventHandler, false);
				_owner.removeEventListener(MouseEvent.ROLL_OUT, mouseEventHandler, false);
				_owner.removeEventListener(MouseEvent.MOUSE_DOWN, mouseEventHandler, false);
			}
			_owner = value;
			if(_owner is DisplayObject){
				_owner.addEventListener(MouseEvent.ROLL_OVER, mouseEventHandler, false, 0, true);
				_owner.addEventListener(MouseEvent.ROLL_OUT, mouseEventHandler, false, 0, true);
				_owner.addEventListener(MouseEvent.MOUSE_DOWN, mouseEventHandler, false, 0, true);
			}
		}
		public function get owner():DisplayObject{
			return _owner;
		}
		protected function mouseEventHandler(event:MouseEvent):void{
			hide();
			if(event.type == MouseEvent.ROLL_OVER){
				showhideTimer.delay = getStyle("showDelay") as Number;
				showhideTimer.start();
			}
		}
		protected function showHide(event:TimerEvent):void{
			if(showhideTimer.currentCount == 1){
				showhideTimer.delay = getStyle("hideDelay") as Number;
				show();
			}else hide();
		}
		override protected function draw():void{
			if(invalidateStyles){	
				var filters:Array = [];
				if(getStyle("useShadow"))filters.push(new DropShadowFilter(4.0, 45, 0, 0.5, 4.0, 4.0, 1));
				textField.filters = filters;
				textField.borderColor = getStyle("borderColor") as uint;
				textField.backgroundColor = getStyle("backgroundColor") as uint;
				var tf:TextFormat = getStyle("textFormat") as TextFormat;
				if(tf != null)textField.defaultTextFormat = tf;
			}
			textField.htmlText = _label;
		}
		override protected function addChildren():void{
			textField = new TextField();
			textField.mouseEnabled = false;
			textField.autoSize = TextFieldAutoSize.LEFT;
			textField.border = true;
			textField.background = true;
			textField.selectable = false;
		}
	}
}