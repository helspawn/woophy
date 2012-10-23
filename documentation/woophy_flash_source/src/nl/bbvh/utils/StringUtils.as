package nl.bbvh.utils{
	/**
	 *
	 * Originally made by Ryan Matsikas, Feb 10 2006</p>
	 */
	public class StringUtils{
		public static const WHITESPACE:String = " \t\r\n";
		public static const CURRENCY_SYMBOL:String = "€ ";
		public function StringUtils(){
			throw new Error("The StringUtils shouldn't be instantiated.");
		}
		public static function capitalize(source:String, capitalizeAll:Boolean = false):String{
			var s:String = trimLeft(source);
			if (capitalizeAll) return s.replace(/^.|\s./g, _upperCase);
			else return s.replace(/(^\w)/, _upperCase);
        }
		public static function isNumeric(source:String):Boolean{
			if(source == null) return false;
			var regx:RegExp = /^[-+]?\d*\.?\d+(?:[eE][-+]?\d+)?$/;
			return regx.test(source);
		}
		public static function toBoolean(source:String):Boolean{
			if(source == null)return false;
			var regx:RegExp = /^(true|1|yes|on)$/i;
			return regx.test(source);
		}
		public static function padLeft(source:String, characterToPad:String, length:uint):String{
			var s:String = source;
			while (s.length < length)s = characterToPad + s;
			return s;
		}
		public static function padRight(source:String, characterToPad:String, length:uint):String{
			var s:String = source;
			while(s.length < length)s = s + characterToPad;
			return s;
		}
        public static function removeExtraWhitespace(source:String):String{
			if(source == null) return "";
			var str:String = trim(source);
			return str.replace(/\s+/g, " ");
		}
        public static function stripTags(source:String):String{
			if(source == null)return "";
			return source.replace(/<\/?[^>]+>/igm, "");
        }
		/*
		public static function trim(source:String):String{
			if(source == null)return '';
			return source.replace(/^\s+|\s+$/g, '');
		}
		public static function trimLeft(source:String):String{
			if(source == null)return '';
			return source.replace(/^\s+/, '');
		}
		public static function trimRight(source:String):String{
			if(source == null)return '';
			return source.replace(/\s+$/, '');
		}*/
		public static function trimLeft(source:String, removeChars:String = WHITESPACE):String{
			if(source == null)return '';
			return source.replace(new RegExp("^[" + removeChars + "]+", ""), "");
		}
		public static function trimRight(source:String, removeChars:String = WHITESPACE):String{
			if(source == null)return '';
			return source.replace(new RegExp("[" + removeChars + "]+$", ""), "");
		}
		public static function trim(source:String, removeChars:String = WHITESPACE):String{
			if(source == null)return '';
			return source.replace(new RegExp("^[" + removeChars + "]+|[" + removeChars + "]+$", "g"), "");
		}
		public static function wordCount(source:String):uint{
			if(source == null) return 0;
			return source.match(/\b\w+\b/g).length;
		}
		public static function truncate(source:String, length:uint, suffix:String = "..."):String{
			if(source == null)return '';
			length -= suffix.length;
			var trunc:String = source;
			if(trunc.length > length){
				trunc = trunc.substr(0, length);
				if(/[^\s]/.test(source.charAt(length)))trunc = trimRight(trunc.replace(/\w+$|\s+$/, ''));
				trunc += suffix;
			}
			return trunc;
		}
		public static function isValidEmail(email:String):Boolean{
			return Boolean(email.match(/^[A-Z0-9._%+-]+@(?:[A-Z0-9-]+\.)+[A-Z]{2,4}$/i));
		}
		public static function formatCurrency(source:String, symbol:String = CURRENCY_SYMBOL, useDecimals:Boolean=false, decimalPoint:String=",", thousandSeparator:String=".", locale:String="nl"):String{
			if(isNaN(parseFloat(source)))return source;
			var ret:String = symbol + formatNumber(source, useDecimals ? 2 : 0, decimalPoint, thousandSeparator);
			if(!useDecimals && locale.toLowerCase()=="nl")ret += ",-";
			return ret;
		}
		public static function formatNumber(source:String, decimals:int=0, decimalPoint:String=",", thousandSeparator:String=" "):String{
			var value:Number = parseFloat(source);
			if(isNaN(value))return source;
			var a:Array = value.toFixed(decimals).split(".");
			a[0] = a[0].replace(/(\d)(?=(\d\d\d)+$)/g, "$1"+thousandSeparator);
			if(decimals==0)a.length=1;//toFixed(0) bug: http://blog.funciton.com/2008/05/tofixed0-and-dategettime-bug.html
			return a.join(decimalPoint);
		}
		private static function _upperCase(character:String, ...args):String{
			return character.toUpperCase();
        }
	}
}
