package com.woophy.search{
	public class Filter{
	
		public var isActive:Boolean = false;
		public var isPublic:Boolean = true;//indicates if filter is for internal use only
		public var key:String;
		public var caption:String;
		public var value:String;

		public function Filter(key:String, value:String){
			this.key = key;
			this.value = value;
		}
	}
}