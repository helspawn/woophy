package nl.bbvh.controls{

	import flash.geom.Rectangle;
	import flash.text.TextField;
	import flash.text.TextFieldAutoSize;
	import flash.text.TextFormat;
	import flash.text.TextLineMetrics;

	public class LabelButton extends Button{

		public var textField:TextField;
		protected var _label:String = "Label";
		public static var sharedStyles:Object = {
			labelMargin:5,
			hoverTextFormat:null,
			selectedTextFormat:null
		};

		public function LabelButton():void{
			super();
			autoSize = false;
		}
		public static function setStyle(name:String, value:Object):void{
			sharedStyles[name] = value;
		}
		public function get label():String{
			return _label;
		}
		public function set label(value:String):void{
			if(textField.text != value){
				_label = value;
				invalidate();
			}
		}
		override public function set selected(value:Boolean):void{
			if(_selected != value){
				invalidateStyles = getStyle("selectedTextFormat") != null;
				super.selected = value;
			}
		}
		override protected function draw():void{
			if(textField.text != _label)textField.htmlText = _label;
			if(!invalidateStyles){
				if(mouseState == "over" || mouseState == "up")invalidateStyles = getStyle("hoverTextFormat") != null;
			}
			var invalidate:Boolean = invalidateStyles || invalidateSize;
			super.draw();
			if(skin){
				skin.scaleX = 1;
				skin.scaleY = 1;
			}
			if(invalidate){
				var tf:Object;
				if(!enabled) tf = getStyle("disabledTextFormat");
				if(!tf && selected) tf = getStyle("selectedTextFormat");
				if(!tf && mouseState == "over") tf = getStyle("hoverTextFormat");
				if(!tf) tf = getStyle("textFormat");
				if(tf){
					textField.defaultTextFormat = TextFormat(tf);
					textField.setTextFormat(TextFormat(tf));
				}
				textField.embedFonts = getStyle("embedFonts") as Boolean;
				var margin:Number = getStyle("labelMargin") as Number;
				var w:Number = skin ? skin.width : 0;
				textField.x = w + margin;//TRICKY: it appears x has to be set before calling getLineMetrics
				textField.width = Math.max(0, width - w - margin);
				_height = textField.height;
				if(skin){
					var metrics:TextLineMetrics = textField.getLineMetrics(0);
					var rect:Rectangle = skin.getBounds(skin);
					textField.y = rect.y+ (rect.bottom-(metrics.height - metrics.leading + 4))/2;
					//textField.y = (skin.height-(metrics.height - metrics.leading + 4))/2;
					_height = Math.max(skin.height, _height);
				}
			}
		}
		override protected function addChildren():void{
			textField = new TextField();
			textField.selectable = false;
			textField.mouseEnabled = false;
			textField.border = false;
			textField.autoSize = TextFieldAutoSize.LEFT;
			addChild(textField);
			super.addChildren();
		}
	}
}