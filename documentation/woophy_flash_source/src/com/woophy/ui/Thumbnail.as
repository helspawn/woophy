package com.woophy.ui{

	import flash.display.Shape;
	import flash.display.Sprite;
	import flash.display.Loader;
	import flash.events.Event;
	import flash.events.IOErrorEvent;
	import flash.net.URLRequest;

	public class Thumbnail extends Sprite{
		public var index:int;//index of grid
		public var data:*;//public var to hold any kind of data
		protected var _borderColor:uint = 0x66C02A;
		protected var _url:String = "";
		protected var border:Shape;
		protected var background:Shape;
		protected var loaderMask:Shape;
		protected var loader:Loader;
		protected var borderWidth:int = 1;
		protected var _backgroundColor:uint = 0x032202;
		public function Thumbnail(url:String=null, width:Number=43, height:Number=43){
			mouseChildren = false;
			background = new Shape();
			addChild(background);
			loaderMask = new Shape();
			with(loaderMask.graphics){
				beginFill(0xFFFFFF);
				drawRect(0, 0, width, height);
				endFill();
			}
			addChild(loaderMask);
			loader = new Loader();
			loader.mask = loaderMask;
			loader.contentLoaderInfo.addEventListener(Event.COMPLETE, completeHandler, false, 0, true);
			loader.contentLoaderInfo.addEventListener(IOErrorEvent.IO_ERROR, ioErrorHandler);
			addChild(loader);
			border = new Shape();
			drawBorder();
			addChild(border);
			this.url = url;
		}
		public function set url(value:String):void{
			if(value == null || value.length == 0){
				if(_url.length > 0)loader.unload();
			}else if(_url != value){
				loader.load(new URLRequest(value));
				_url = value;
			}
		}
		public function get url():String{
			return _url;
		}
		override public function set width(value:Number):void{
			loaderMask.width = background.width = value;
			center();
			drawBorder();
		}
		override public function get width():Number{
			return loaderMask.width;
		}
		override public function set height(value:Number):void{
			loaderMask.height = background.height = value;
			center();
			drawBorder();
		}
		override public function get height():Number{
			return loaderMask.height;
		}
		public function set borderColor(value:uint):void{
			_borderColor = value;
			drawBorder();
		}
		public function get borderColor():uint{
			return _borderColor;
		}
		public function set backgroundColor(value:uint):void{
			_backgroundColor = value;
			drawBackground();
		}
		public function get backgroundColor():uint{
			return _backgroundColor;
		}
		protected function completeHandler(event:Event):void{
			drawBackground();
			center();
			dispatchEvent(event);
		}
		protected function ioErrorHandler(event:IOErrorEvent):void{
			dispatchEvent(event);
		}
		protected function drawBackground():void{
			with(background.graphics){
				clear();
				beginFill(_backgroundColor);
				drawRect(0, 0, width, height);
				endFill();
			}
		}
		protected function drawBorder():void{
			with(border.graphics){
				clear();
				lineStyle(borderWidth, _borderColor);
				beginFill(0x000000, 0);
				drawRect(0, 0, width, height);
				endFill();
			}
		}
		protected function center():void{
			loader.x = (width+borderWidth-loader.width)/2;
			loader.y = (height+borderWidth-loader.height)/2;
		}
	}
}