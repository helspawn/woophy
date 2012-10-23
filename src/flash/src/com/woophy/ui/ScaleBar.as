package com.woophy.ui{
	
	import com.woophy.utils.TextStyles;
	
	import flash.display.Sprite;
	import flash.text.TextField;
	import flash.text.TextFieldAutoSize;
	
	import nl.bbvh.events.ZoomEvent;
	import nl.bbvh.zoom.ZoomViewport;
	
	public class ScaleBar extends Sprite{

		public static const PERIMETER_EARTH:int = 40050;
		public var bar:Sprite;
		public var km:TextField;

		public function ScaleBar(){
			km.defaultTextFormat = TextStyles.small;
			km.autoSize = TextFieldAutoSize.LEFT;
			mouseEnabled = false;
			mouseChildren = false;
		}
		public function zoomHandler(event:ZoomEvent):void{
			//barwidth should be prox. 150px:
			var w:Number = ZoomViewport(event.currentTarget).content.width;
			var km:Number = 150 * PERIMETER_EARTH / w;
			var scales:Array = [20000,15000,10000,5000,2000,1500,1000,500,250,150,100,75,50,25,10,5,1];
			var i:int = scales.length-1;
			while(--i > 0){
				if(scales[i]>km){
					//find nearest scale:
					i = Math.abs(km-scales[i+1]) < Math.abs(scales[i]-km) ? i+1 : i;
					break;
				}
			}
			km = scales[i];
			bar.width = km * w / PERIMETER_EARTH;
			bar.x = -bar.width;
			this.km.text = String(km) + " km";
			this.km.x = -bar.width - 2;
		}
	}
}