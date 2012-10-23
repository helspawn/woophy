package com.woophy.ui{
	import com.woophy.utils.NetUtils;
	import com.woophy.utils.TextStyles;
	
	import flash.display.Loader;
	import flash.display.Shape;
	import flash.display.Sprite;
	import flash.events.Event;
	import flash.events.IOErrorEvent;
	import flash.filters.DropShadowFilter;
	import flash.net.URLRequest;
	import flash.text.TextField;
	import flash.text.TextFieldAutoSize;
	import flash.text.TextFormat;

	public class MarkerToolTip extends Sprite{
		protected var _width:Number = 180;
		protected var _height:Number = 60;
		protected var borderWidth:Number = 1;
		protected var isLoading:Boolean = false;
		protected var textField:TextField;
		protected var loader:Loader;
		protected var loaderMask:Shape;
		public function MarkerToolTip(){
			this.filters = [new DropShadowFilter(2, 45, 0x000000, 0.5, 3, 3)];
			textField = new TextField();
			textField.autoSize = TextFieldAutoSize.LEFT;
			textField.y = 2*borderWidth;
			textField.x = _height;
			loaderMask = new Shape();
			loaderMask.graphics.beginFill(0xFFFFFF);
			loaderMask.graphics.drawRect(2*borderWidth, 2*borderWidth, _height-4*borderWidth, _height-4*borderWidth);
			loaderMask.graphics.endFill();
			addChild(loaderMask);
			loader = new Loader();
			loader.contentLoaderInfo.addEventListener(Event.INIT, initHandler, false, 0, true);
			loader.contentLoaderInfo.addEventListener(IOErrorEvent.IO_ERROR, ioErrorHandler);
			loader.mask = loaderMask;
			addChild(loader);
			addChild(textField);
			addEventListener(Event.ADDED_TO_STAGE, addedToStageHandler, false, 0 ,true);
		}
		/*
		value:Object={
		pid: 4568
		n: Narsarsuaq
		y: 85.9854
		c: Greenland
		x: 244.881
		uid: 791
		u: -2893384
		q: 1
		}
		*/
		public function set data(value:Object):void{
			this.graphics.clear();
			var tf:TextFormat = TextStyles.white;
			tf.leading = 2;
			textField.text = value.n || "";
			textField.setTextFormat(tf);
			var len:int = textField.length;
			textField.appendText(",\n" + (value.c || ""));
			tf.leading = 4;
			textField.setTextFormat(tf, len, textField.length);
			len = textField.length;
			textField.appendText("\n" + String(int(value.q)) + " ");
			textField.appendText(int(value.q)==1?"photo":"photos");
			textField.setTextFormat(TextStyles.normal, len, textField.length);
			_width = textField.x + textField.width + 3 + 2*borderWidth;
			graphics.beginFill(0x235F0E, 1);
			graphics.drawRect(0, 0, _width, _height);
			graphics.beginFill(0xFFFFFF, 1);
			graphics.drawRect(borderWidth, borderWidth, _width-2*borderWidth, _height-2*borderWidth);
			graphics.beginFill(0xEDF8E6, 1);
			graphics.drawRect(2*borderWidth, 2*borderWidth, _height-4*borderWidth, _height-4*borderWidth);
			graphics.beginFill(0x66C029, 1);
			var x:Number = _height-borderWidth;
			graphics.drawRect(x, 2*borderWidth, _width - x - 2*borderWidth, 36);
			graphics.endFill();
			var url:String = NetUtils.getPhotoUrl(value.uid, value.pid, "s");
			if(loader.contentLoaderInfo.url != url){
 				loader.unload();
 				loader.load(new URLRequest(url));
 			}
		}
		protected function initHandler(event:Event):void{
			loader.x = loaderMask.x + (loaderMask.width - loader.width)/2;
			loader.y = loaderMask.y + (loaderMask.height - loader.height)/2;
		}
		protected function ioErrorHandler(event:IOErrorEvent):void {
            //trace("ioErrorHandler: " + event);
        }
		protected function addedToStageHandler(event:Event):void{
			var x:Number = parent.mouseX + 5;
			var y:Number = parent.mouseY - _height - 4;
			if(x+_width>stage.stageWidth) x -= _width;
			if(y < 0) y += _height + 4 + 16;
			this.x = x;
			this.y = y;
		}
	}
}