package com.woophy.events{
	import flash.events.Event;

	public class SearchEvent extends Event{
		public static const START:String = "start";
		public var command:String;
		public function SearchEvent(type:String, command:String, bubbles:Boolean=false, cancelable:Boolean=false){
			super(type, bubbles, cancelable);
			this.command = command;
		}
		 public override function clone():Event{
            return new SearchEvent(type, this.command);
        }
	}
}
