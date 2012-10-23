package nl.bbvh.controls{

	import flash.utils.Timer;
	import flash.events.Event;
	import flash.events.TimerEvent;
	import flash.events.MouseEvent;
	import flash.display.DisplayObject;

	public class Button extends UIControl{

		protected var skin:DisplayObject;
		protected var _autoRepeat:Boolean = false;
		protected var pressTimer:Timer;
		protected var autoSize:Boolean = true;
		protected var mouseState:String = "up";
		protected var _selected:Boolean = false;
		protected var _selectable:Boolean = false;
		public static var sharedStyles:Object = {
			upSkin:null,
			downSkin:null,
			overSkin:null,
			disabledSkin:null,
			selectedUpSkin:null,
			selectedOverSkin:null,
			selectedDownSkin:null,
			selectedDisabledSkin:null,
			repeatDelay:500,
			repeatInterval:35
		};

		public function Button():void{
			super();
			mouseChildren = false;
			addEventListener(MouseEvent.ROLL_OVER,mouseEventHandler,false,0,true);
			addEventListener(MouseEvent.MOUSE_DOWN,mouseEventHandler,false,0,true);
			addEventListener(MouseEvent.MOUSE_UP,mouseEventHandler,false,0,true);
			addEventListener(MouseEvent.ROLL_OUT,mouseEventHandler,false,0,true);
			pressTimer = new Timer(1,0);
			pressTimer.addEventListener(TimerEvent.TIMER, buttonDown, false, 0, true);
		}
		override public function setSize(w:Number, h:Number):void{
			if(!isNaN(_width))autoSize = false;
			super.setSize(w, h);
		}
		public static function setStyle(name:String, value:Object):void{
			sharedStyles[name] = value;
		}
		public function get selected():Boolean{
			return (_selectable) ? _selected : false;
		}
		public function set selected(value:Boolean):void{
			if(_selected == value) return;
			_selected = value;
			if(_selectable) invalidate();
		}
		public function get selectable():Boolean{
			return _selectable;
		}
		public function set selectable(value:Boolean):void{
			if(_selectable == value) return;
			if(!value) _selected = false;
			_selectable = value;
			if(_selectable) addEventListener(MouseEvent.CLICK, clickHandler, false, 0, true);
			else removeEventListener(MouseEvent.CLICK, clickHandler, false);
			invalidate();
		}
		public function get autoRepeat():Boolean{
			return _autoRepeat;
		}
		public function set autoRepeat(value:Boolean):void{
			_autoRepeat = value;
		}
		override public function set enabled(value:Boolean):void{
			super.enabled = value;
			mouseChildren = false;
			if(_autoRepeat)pressTimer.reset();
		}
		override protected function draw():void{
			var styleName:String = enabled ? mouseState : "disabled";
			if(selected)styleName = "selected" + styleName.substr(0,1).toUpperCase() + styleName.substr(1);
			var style:Object = getStyle(styleName + "Skin");
			if(style){
				if(!(skin is Class(style))){
					var s:DisplayObject = getDisplayObjectInstance(style);
					if(s != null && s != skin){
						addChildAt(s, 0);
						if(skin != null)removeChild(skin);
						skin = s;
					}
				}
				if(skin){
					if(autoSize){
						_width = skin.width;
						_height = skin.height;
					}else{
						skin.width = _width;
						skin.height = _height;
					}
				}
			}
			super.draw();
		}
		protected function mouseEventHandler(event:MouseEvent):void{
			switch(event.type){
				case MouseEvent.ROLL_OUT:
					mouseState = "up";
					if(pressTimer.running)pressTimer.reset();
					break;
				case MouseEvent.MOUSE_DOWN:
					mouseState = "down";
					if(_autoRepeat){
						if(pressTimer.running){
							if(pressTimer.currentCount == 1) pressTimer.delay = Number(getStyle("repeatInterval"));
						}else{
							pressTimer.delay = Number(getStyle("repeatDelay"));
							pressTimer.start();
						}
					}
					break;
				case MouseEvent.MOUSE_UP:
				case MouseEvent.ROLL_OVER:
					mouseState = "over";
					if(pressTimer.running) pressTimer.reset();
					break;
			}
			draw();
			event.updateAfterEvent();
		}
		protected function clickHandler(event:MouseEvent):void{
			selected = !selected;
			dispatchEvent(new Event(Event.CHANGE, true));
		}
		protected function buttonDown(event:TimerEvent):void{
			if(!_autoRepeat)pressTimer.reset();
			dispatchEvent(new MouseEvent(MouseEvent.MOUSE_DOWN, true));
		}
	}
}