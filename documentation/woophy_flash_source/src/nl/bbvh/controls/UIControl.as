package nl.bbvh.controls{

	import flash.display.Sprite;
	import flash.display.DisplayObject;
	import flash.events.Event;
	import flash.text.TextFormat;
	import flash.text.TextFormatAlign;
	import flash.utils.getDefinitionByName;
	import flash.utils.getQualifiedClassName;
	import flash.utils.getQualifiedSuperclassName;

	public class UIControl extends Sprite{

		protected var _width:Number;
		protected var _height:Number;
		protected var instanceStyles:Object = {};
		protected var _enabled:Boolean=true;
		protected var invalidateStyles:Boolean = true;
		protected var invalidateSize:Boolean = true;
		public static var sharedStyles:Object = {
			textFormat:new TextFormat("_sans", 12, 0x000000, false, false, false, "", "", TextFormatAlign.LEFT, 0, 0, 0, 0),
			disabledTextFormat:new TextFormat("_sans", 12, 0x999999, false, false, false, "", "", TextFormatAlign.LEFT, 0, 0, 0, 0),
			embedFonts:null
		};

		public function UIControl():void{
			focusRect = false;
			tabEnabled = true;
			var cls:Class = getClassDefinition(getQualifiedClassName(this)) as Class;
			var styles:Object;
			while(cls){
				if(cls["sharedStyles"] != null) {
					styles = cls["sharedStyles"];
					for (var style:String in styles){
						if(instanceStyles[style] == undefined){
							instanceStyles[style] = styles[style];
						}
					}
					if(cls == UIControl)break;
				}
				cls = getClassDefinition(getQualifiedSuperclassName(cls)) as Class;
			}
			var r:Number = rotation;
			rotation = 0;
			var w:Number = super.width;
			var h:Number = super.height;
			super.scaleX = super.scaleY = 1;
			if(numChildren > 0)removeChildAt(0);//remove preview
			setSize(w, h);
			addChildren();
			rotation = r;
			//invalidate();//called by setSize
		}
		public static function setStyle(name:String, value:*):void{//KLUDGE: you have to set a component style before instantiation
			sharedStyles[name] = value;
		}
		public function setStyle(name:String, value:*):void{
			instanceStyles[name] = value;
			invalidateStyles = true;
			invalidate();
		}
		public function getStyle(name:String):*{
			return instanceStyles[name];
		}
		protected function copyStylesToChild(child:UIControl, styles:Object):void{
			var value:*;
			for(var style:String in styles){
				value = getStyle(style) || styles[style];
				if(value != null)child.setStyle(style, value);
			}
		}
		protected function invalidate():void{
			addEventListener(Event.ENTER_FRAME, invalidateHandler);
		}
		protected function getClassDefinition(name:String):Object{
			var classDef:Object = null;
			try{
				classDef = getDefinitionByName(name);
			}catch(e:Error){
				try{
					classDef = loaderInfo.applicationDomain.getDefinition(name) as Object;
				}catch(e:Error){}
			}
			return classDef;
		}
		protected function getDisplayObjectInstance(skin:Object):DisplayObject{
			if(skin is Class)return (new skin()) as DisplayObject;
			var classDef:Object = getClassDefinition(skin.toString());
			if(classDef == null)return null;
			return (new classDef()) as DisplayObject;
		}
		protected function draw():void{//override in subclasses
			invalidateStyles = false;
			invalidateSize = false;
		}
		public function drawNow():void {
			draw();
		}
		protected function addChildren():void{//override in subclasses
		}
		private function invalidateHandler(event:Event):void{
			removeEventListener(Event.ENTER_FRAME, invalidateHandler);
			draw();
		}
		public function get enabled():Boolean{
			return _enabled;
		}
		public function set enabled(value:Boolean):void{
			if(value == _enabled) return;
			_enabled = value;
			mouseEnabled = value;
			mouseChildren = value;//TRICKY: setting enabled to true will set mouseChildren to true!
			invalidateStyles = true;
			invalidate();
		}
		public function setSize(w:Number, h:Number):void{
			if(_width == w && _height == h) return;
			_width = w;
			_height = h;
			invalidateSize = true;
			invalidate();
		}
		override public function set width(value:Number):void{
			if(_width == value) return;
			setSize(value, height);
		}
		override public function get width():Number{
			return _width;
		}
		override public function set height(value:Number):void{
			if(_height == value) return;
			setSize(width, value);
		}
		override public function get height():Number{
			return _height;
		}
	}
}