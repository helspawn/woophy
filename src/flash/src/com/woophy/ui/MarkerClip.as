package com.woophy.ui{
	import com.woophy.events.MarkerEvent;
	
	import flash.display.DisplayObject;
	import flash.display.Sprite;
	import flash.events.MouseEvent;

	public class MarkerClip extends Sprite{
		public function MarkerClip(){
			buttonMode = true;
			mouseChildren = true;
			addEventListener(MouseEvent.MOUSE_OVER, mouseHandler, false, 0, true);
			addEventListener(MouseEvent.MOUSE_DOWN, mouseHandler, false, 0, true);
			addEventListener(MouseEvent.MOUSE_UP, mouseHandler, false, 0, true);
			addEventListener(MouseEvent.MOUSE_OUT, mouseHandler, false, 0, true);
			addEventListener(MouseEvent.CLICK, mouseHandler, false, 0, true);
		}
		public function setScale(value:Number):void{
			var i:int = numChildren;
			if(i > 0){
				if(value == getChildAt(0).scaleX) return;
				while(i--){
					var child:DisplayObject = getChildAt(i);
					child.scaleX = child.scaleY = value;
				}
			}
		}
		protected function mouseHandler(event:MouseEvent):void{
			if(event.target is Marker){
				var type:String;
				switch(event.type){
					case MouseEvent.MOUSE_DOWN:
						event.stopPropagation();
						type = MarkerEvent.MOUSE_DOWN;
						break;
					case MouseEvent.MOUSE_UP:
						event.stopPropagation();
						type = MarkerEvent.MOUSE_UP;
						break;
					case MouseEvent.MOUSE_OVER:
						type = MarkerEvent.MOUSE_OVER;
						Marker(event.target).highlight(true);
						break;
					case MouseEvent.MOUSE_OUT:
						type = MarkerEvent.MOUSE_OUT;
						Marker(event.target).highlight(false);
						break;
					case MouseEvent.CLICK:
						type = MarkerEvent.CLICK;
						break;
				}
				dispatchEvent(new MarkerEvent(type, Marker(event.target)));
			}
		}
	}
}