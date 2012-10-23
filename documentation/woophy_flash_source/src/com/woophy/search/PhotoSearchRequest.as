package com.woophy.search{
    public class PhotoSearchRequest extends SearchRequest{
		public static const TYPE:String = "Photos.getPhotos";
		public static const MAX_LIMIT:int = 32;
		public static const MAX_OFFSET:int = 1000;
		private static var _max_limit:int = MAX_LIMIT;//KLUDGE: limit depends on screen height, this prop gets set by SearchResult
		public var offset:int;
		public var limit:int;
        public function PhotoSearchRequest(filters:Array, offset:int=0, limit:int=MAX_LIMIT){
			super(TYPE, filters);
			this.limit = Math.max(0, Math.min(limit, max_limit, MAX_LIMIT));
			this.offset = Math.min(MAX_OFFSET-this.limit, Math.max(0, offset));
        }
        public static function set max_limit(value:int):void{
        	_max_limit = Math.min(value, MAX_LIMIT);
        }
        public static function get max_limit():int{
        	return _max_limit;
        }
    }
}