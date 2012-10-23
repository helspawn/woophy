package com.woophy.search{
	import flash.events.Event;
    public class SearchResponse extends Event{
		public var result:Array = [];
		public var total:int = 0;
		public var offset:int = 0;
        public function SearchResponse(type:String, bubbles:Boolean=false, cancelable:Boolean=false){
            super(type, bubbles, cancelable);
        }
    }
}