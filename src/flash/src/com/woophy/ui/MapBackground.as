package com.woophy.ui{

	import com.woophy.skins.MapBackgroundTile;
	
	import flash.display.Sprite;
	import flash.events.Event;
	
	public class MapBackground extends Sprite{

		private var tile:MapBackgroundTile;
		private var margin_top:Number=5;
		private var margin_bottom:Number=LinkBar.HEIGHT;
		private var margin_left:Number=5;
		private var margin_right:Number=5;
		private var offset_y:Number=21;
		private var canvas:Sprite;

		public function MapBackground(){
			tile = new MapBackgroundTile(24, 24);
			canvas = new Sprite();
			addChild(canvas);//above bitmap
			stage.addEventListener(Event.RESIZE, resizeHandler, false, 0, true);
			resizeHandler(null);
		}
		private function resizeHandler(event:Event):void{
			with(canvas.graphics){
				clear();
				beginFill(0x4A901D, 1);
				drawRect(500, 0, stage.stageWidth-500, offset_y);
				beginFill(0xFFFFFF, 1);
				drawRect(0, offset_y, stage.stageWidth, stage.stageHeight-offset_y);
				beginBitmapFill(tile);
				drawRect(margin_left, offset_y+margin_top, stage.stageWidth-margin_left-margin_right, stage.stageHeight-offset_y-margin_top-margin_bottom);
				endFill();
			}
		}
	}
}