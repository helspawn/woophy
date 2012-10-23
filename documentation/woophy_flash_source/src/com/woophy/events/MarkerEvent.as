package com.woophy.events{
	import com.woophy.ui.Marker;
	
	import flash.events.Event;

	public class MarkerEvent extends Event{
		public static const MOUSE_OVER:String = "markerMouseOver";
		public static const MOUSE_OUT:String = "markerMouseOut";
		public static const MOUSE_DOWN:String = "markerMouseDown";
		public static const MOUSE_UP:String = "markerMouseUp";
		public static const CLICK:String = "markerClick";
		public var marker:Marker;
		public function MarkerEvent(type:String, marker:Marker, bubbles:Boolean=false, cancelable:Boolean=false){
			super(type, bubbles, cancelable);
			this.marker = marker;
		}
		public override function clone():Event{
            return new MarkerEvent(type, this.marker);
        }
	}
}