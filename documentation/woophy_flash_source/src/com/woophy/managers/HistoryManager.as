package com.woophy.managers{
	import flash.external.*;
	import flash.system.Capabilities;
	import flash.utils.Timer;
	import flash.events.TimerEvent;

	public class HistoryManager{
		public static const HISTORY_LATITUDE:String = "lat";
		public static const HISTORY_LONGITUDE:String = "lng";
		public static const HISTORY_MAGNITUDE:String = "mag";
		public static const HISTORY_FILTERKEY:String = "key";
		public static const HISTORY_FILTERVALUE:String = "val";
		public static const HISTORY_OFFSET:String = "lmt";
		private static var instance:HistoryManager = new HistoryManager();
		private var keys:Object = {};
		private var timer:Timer;
		private var delay:Number = 500;
		private var querystring:String = "";

		function HistoryManager(){
			if(instance) throw new Error( "HistoryManager can only be accessed through HistoryManager.getInstance()");
			timer = new Timer(delay, 1);
			timer.addEventListener(TimerEvent.TIMER, timerHandler, false, 0, true);
		}
		public static function getInstance():HistoryManager{
			return instance;
		}
		public function setValue(key:String, val:String):void{
			if(typeof key == "string" && typeof val == "string") {
				if(keys[key] != val){
					keys[key] = val;
					timer.start();
				}
			}
		}
		public function getValue(key:String):String{
			return keys[key];
		}
		public function removeValue(key:String):void{
			if(keys[key] != undefined) delete keys[key];
		}
		private function timerHandler(event:TimerEvent):void{
			var a:Array = [];//use array to sort
			for(var k:String in keys)a.push("&" + k + "=" + keys[k]);
			if(a.length > 0){
				a = a.sort();
				var str:String = a.join("");
				if(str != querystring){
					if(Capabilities.playerType != "External"){
						ExternalInterface.call("setHistory", a.join(""));
					}
				}
				querystring = str;
			}
			timer.reset();
		}
	}
}