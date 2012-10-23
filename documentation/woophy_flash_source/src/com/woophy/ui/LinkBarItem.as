package com.woophy.ui{
	
	import com.woophy.utils.TextStyles;
	
	import flash.display.Sprite;
	import flash.events.IOErrorEvent;
	import flash.events.MouseEvent;
	import flash.text.TextField;
	import flash.text.TextFieldAutoSize;
	
	public class LinkBarItem extends Sprite{
		
		public var thumbWidth:Number = 43;
		public var thumbHeight:Number = 43;
		
		public var _header:TextField;
		public var background:Sprite;
		public var seperator:Sprite;
		public var text:TextField;
		
		protected var _image:Thumbnail;
		protected var numListeners:int = 0;
		
		function LinkBarItem(){
			mouseChildren = false;
			background.alpha = 0;
			_header.defaultTextFormat = TextStyles.header;
			_header.autoSize = TextFieldAutoSize.LEFT;
			text = new TextField();//create dynamically because of defaultTextFormat bug
			text.defaultTextFormat = TextStyles.normal;
			text.multiline = true;
			text.wordWrap = true;
			text.y = 24;
			text.height = background.height - text.y;
			addChild(text);
			setTextProps();
		}
		override public function set width(value:Number):void{
			_header.width = value - 10;
			background.width = value;
			seperator.x = width -seperator.width;
			setTextProps();
		}
		override public function get width():Number{
			return background.width;
		}
		public function set header(value:String):void{
			_header.text = value;
		}
		public function get header():String{
			return _header.text;
		}
		public function set image(url:String):void{
			if(url == null){
				if(_image != null)removeChild(_image);
			}else{
				if(_image == null){
					_image = new Thumbnail(null, thumbWidth, thumbHeight);
					_image.x = 10;
					_image.y = text.y + 3;//3px gutter
					_image.addEventListener(IOErrorEvent.IO_ERROR, ioErrorHandler, false, 0, true);
					setTextProps();
				}
				addChild(_image);
				_image.url = url;
				//loader.load(new URLRequest("http://localhost/woophy/html/images/photos/1/s/539.jpg"));
			}
		}
		public function setText(...lines):void{
			text.wordWrap = lines.length>1 ? false : true;
			text.htmlText = lines.join("<br/>");
		}
		public function getText():String{
			return text.htmlText;
		}
		override public function addEventListener(type:String, listener:Function, useCapture:Boolean = false, priority:int = 0, useWeakReference:Boolean = false):void{
			super.addEventListener.apply(this, arguments);
			if(numListeners == 0){
				super.addEventListener(MouseEvent.MOUSE_OVER, rollOverHandler, false, 0, true);
				super.addEventListener(MouseEvent.MOUSE_OUT, rollOutHandler, false, 0, true);
				buttonMode = true;
			}
			numListeners++;
		}
		override public function removeEventListener(type:String, listener:Function, useCapture:Boolean = false):void{
			super.removeEventListener.apply(this, arguments);
			numListeners = Math.max(0, numListeners-1);
			if(numListeners == 0){
				super.removeEventListener(MouseEvent.MOUSE_OVER, rollOverHandler, false);
				super.removeEventListener(MouseEvent.MOUSE_OUT, rollOutHandler, false);
				buttonMode = false;
			}
		}
		protected function rollOverHandler(event:MouseEvent):void{
			background.alpha = 1;
		}
		protected function rollOutHandler(event:MouseEvent):void{
			background.alpha = 0;
		}
		protected function ioErrorHandler(event:IOErrorEvent):void{
			//trace("ioErrorHandler: " + event.text);
		}
		private function setTextProps():void{
			text.x = _image == null ? 5 : thumbWidth + 15;
			text.width = width - text.x - 5;
		}
	}
}
