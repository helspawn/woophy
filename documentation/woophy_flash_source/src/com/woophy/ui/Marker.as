package com.woophy.ui{

	import com.woophy.skins.HaloSkin;
	
	import flash.display.DisplayObject;
	import flash.display.Sprite;

	public class Marker extends Sprite{
		private var _highlight:Sprite;
		private var _skin:DisplayObject;
		private var _active:Boolean = false;
		public var id:int;

		public function Marker(x:Number=0, y:Number=0){
			this.x = x;
			this.y = y;
			mouseChildren = false;
			if(numChildren>0) _skin = getChildAt(0);
		}
		public function setSkin(cls:Class):void{
			var skin:Sprite = new cls();
			addChildAt(skin, _skin ? getChildIndex(_skin) : 0);
			if(_skin) removeChild(_skin);
			_skin = skin;
		}
		public function setActive(value:Boolean=true):void{
			_active = value;
			highlight(value);
		}
		public function highlight(value:Boolean=true):void{
			if(value){
				if(_highlight == null)_highlight = new HaloSkin();
				addChildAt(_highlight, 0);
			}else if(!_active){
				if(_highlight != null){
					removeChild(_highlight);
					_highlight = null;
				}
			}
		}
	}
}