package com.woophy.ui{

	import com.woophy.utils.TextStyles;
	
	import flash.display.Sprite;
	import flash.events.Event;
	import flash.events.KeyboardEvent;
	import flash.events.MouseEvent;
	import flash.text.TextField;
	import flash.ui.Keyboard;
	import flash.utils.getDefinitionByName;
	
	import nl.bbvh.controls.Button;
	import nl.bbvh.controls.ToolTip;
	import nl.bbvh.events.ZoomEvent;
	import nl.bbvh.zoom.ZoomViewport;
	
	public class ZoomControls extends Sprite{
		
		public var viewport:ZoomViewport;
		public var zoomIn:Button;
		public var zoomOut:Button;
		public var zoomTool:Button;
		public var drag:Button;
		public var fitWidth:Button;
		public var scale:TextField;
		
		protected var toggleButtons:Array;
		protected var draggingEnabled:Boolean;
		
		function ZoomControls(){

			toggleButtons = [drag, zoomTool];
			for each(var tgl:Button in toggleButtons){
				tgl.selectable = true;
				tgl.addEventListener(MouseEvent.CLICK, toggle, false, 0, true);
			}
			var buttons:Array = ["zoomIn", "zoomOut", "zoomTool", "drag", "fitWidth"];
			var labels:Array = ["zoom in", "zoom out", "zoom tool", "drag", "fit width"];
			for(var i:int=0, l:int=buttons.length;i<l;i++){
				var btn:String = buttons[i];
				this[btn].setStyle("upSkin", getDefinitionByName(btn + "_up"));
				this[btn].setStyle("overSkin", getDefinitionByName(btn + "_over"));
				if(this[btn].selectable){
					this[btn].setStyle("selectedUpSkin", getDefinitionByName(btn + "_selected"));
					this[btn].setStyle("selectedOverSkin", getDefinitionByName(btn + "_selected"));
				}
				this[btn].addEventListener(MouseEvent.CLICK, this[btn+"Handler"], false, 0, true);
				new ToolTip(this[btn], labels[i]);
			}
			scale.defaultTextFormat = TextStyles.small;
			scale.restrict = "0-9%";
			scale.maxChars = 6;
			scale.addEventListener(KeyboardEvent.KEY_DOWN, keyDownHandler, false, 0, true);
			stage.addEventListener(KeyboardEvent.KEY_DOWN, keyDownHandler, false, 0, true);
			stage.addEventListener(KeyboardEvent.KEY_UP, keyUpHandler, false, 0, true);
		}
		public function zoomHandler(event:ZoomEvent):void{
			scale.text = String(Math.round(viewport.zoom*100))+"%";
		}
		public function resizeHandler(event:Event):void{
			zoomHandler(null);
		}
		protected function toggle(event:MouseEvent):void{
			for each(var tgl:Button in toggleButtons){
				tgl.selected = tgl == event.currentTarget;
			}
		}
		protected function zoomInHandler(event:MouseEvent):void{
			if(viewport)viewport.zoomBy(2);
		}
		protected function zoomOutHandler(event:MouseEvent):void{
			if(viewport)viewport.zoomBy(.5);
		}
		protected function zoomToolHandler(event:MouseEvent):void{
			if(viewport)viewport.draggingEnabled = draggingEnabled = false;
		}
		protected function dragHandler(event:MouseEvent):void{
			if(viewport)viewport.draggingEnabled = draggingEnabled = true;
		}
		protected function fitWidthHandler(event:MouseEvent):void{
			if(viewport)viewport.fitWidth();
		}
		protected function keyDownHandler(event:KeyboardEvent):void{
			switch(event.currentTarget){
				case scale:
					if(viewport){
						if(event.keyCode == Keyboard.ENTER)viewport.zoomTo(parseInt(TextField(event.currentTarget).text)/100);
					}
					break;
				case stage:
					if(event.keyCode == Keyboard.SPACE)viewport.draggingEnabled = true;
					break;
			}
		}
		protected function keyUpHandler(event:KeyboardEvent):void{
			if(event.keyCode == Keyboard.SPACE) viewport.draggingEnabled = draggingEnabled;
			else{
				if(event.target == stage){
					var code:uint = event.keyCode;
					if(code == 187 || code == 107)viewport.zoomBy(2);
					else if(code == 189 || code == 109)viewport.zoomBy(.5);
					else if(event.ctrlKey){
						if(code>=49 && code<=57)viewport.zoomTo(code-48);
						if(code>=Keyboard.NUMPAD_1 && code<=Keyboard.NUMPAD_9)viewport.zoomTo(code-Keyboard.NUMPAD_1+1);
					}
				}
			}
		}
	}
}