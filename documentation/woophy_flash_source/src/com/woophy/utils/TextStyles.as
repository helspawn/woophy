package com.woophy.utils{
	import flash.text.StyleSheet;
	import flash.text.TextFormat;
	public class TextStyles{
		public static const COLOR_GREEN:uint =  0x66C029;
		public static const COLOR_DARKGREEN:uint =  0x003900;
		public static const FONT:String =  "Georgia";
		public static function get normal():TextFormat{
			var tf:TextFormat = new TextFormat();
			tf.font = FONT;
			tf.size = 12;
			tf.color = COLOR_DARKGREEN;
			tf.leftMargin = 2;
			return tf;
		}
		public static function get green():TextFormat{
			var tf:TextFormat = normal;
			tf.color = COLOR_GREEN;
			return tf;
		}
		public static function get white():TextFormat{
			var tf:TextFormat = normal;
			tf.color = 0xFFFFFF;
			return tf;
		}
		public static function get small():TextFormat{
			var tf:TextFormat = normal;
			tf.size = 11;
			return tf;
		}
		public static function get header():TextFormat{
			var tf:TextFormat = normal;
			tf.bold = true;
			return tf;
		}
		public static function get link():StyleSheet{
			var a:Object = {
				fontFamily:FONT,
				fontSize:12,
				color:"#"+COLOR_GREEN.toString(16)
			};
			var aHover:Object = {
				textDecoration:"underline"
			};
			var style:StyleSheet = new StyleSheet();
			style.setStyle("a", a);
			style.setStyle("a:hover", aHover);
			style.setStyle("a:active", aHover);
            return style;
		}
	}
}