package nl.bbvh.events {
	import flash.events.Event;
    public class ZoomEvent extends Event {
		public static const DRAG_START:String = "dragStart";
		public static const DRAG_STOP:String = "dragStop";
		public static const ZOOM:String = "zoom";
		public static const MOVE:String = "move";
        public function ZoomEvent(type:String, bubbles:Boolean=false, cancelable:Boolean=false) {
			super(type, bubbles, cancelable);
        }
        override public function clone():Event {
            return new ZoomEvent(type, bubbles, cancelable);
        }
    }
}