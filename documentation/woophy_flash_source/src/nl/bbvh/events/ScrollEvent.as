package nl.bbvh.events {
	import flash.events.Event;
	public class ScrollEvent extends Event {		
		public static const SCROLL:String = "scroll";
        public var direction:String;
        public var delta:Number;
		public var position:Number;
		public function ScrollEvent(direction:String, delta:Number, position:Number) {
			super(ScrollEvent.SCROLL,false,false);
			this.direction = direction;
			this.delta = delta;
			this.position = position;
		}
		override public function clone():Event {
			return new ScrollEvent(direction, delta, position);
		}
	}
}
