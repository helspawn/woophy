package com.woophy.managers{
	import com.woophy.search.Filter;

 	public class FilterManager{

		public static const KEYWORDS_KEY:String = "keywords";
		public static const CITY_KEY:String = "city";
		public static const USERNAME_KEY:String = "username";
		public static const PHOTOID_KEY:String = "photoid";
		public static const LAST24H_KEY:String = "last24H";
		public static const CITYID_KEY:String = "cityid";
		public static const USERID_KEY:String = "userid";
		public static const TRAVELBLOG_KEY:String = "travelblog";
		public static const SHOWALL_KEY:String = "showall";//not used to filter, only flag to indicate if all cities should initially be loaded on map

		public static const TOOLTIP_KEY:String = "tooltip";
		public static const LIMIT:String = "limit";

		private var filters:Array = [];
		private static var instance:FilterManager = new FilterManager();

		function FilterManager(){
			if(instance) throw new Error("FilterManager can only be accessed through FilterManager.getInstance()");

			addFilter(KEYWORDS_KEY, "Keywords", true);
			addFilter(CITY_KEY, "City", true);
			addFilter(USERNAME_KEY, "Member", true);
			addFilter(PHOTOID_KEY, "Photo Id", true);
			addFilter(LAST24H_KEY, "Added last 24h", false);
			addFilter(CITYID_KEY, "City Id", false);
			addFilter(USERID_KEY, "User Id", false);
			addFilter(TRAVELBLOG_KEY, "Travelblog", false);
			addFilter(SHOWALL_KEY, "Showall", false);
		}
		public static function getInstance():FilterManager{
			return instance;
		}
		public function getActiveFilters():Array{
			var i:int = filters.length, a:Array = [];
			while(i--)if(filters[i].isActive)a.push(filters[i]);
			return a;
		}
		public function setActiveFilter(key:String, val:String):Boolean{
			removeActiveFilters();
			return addActiveFilter(key, val);
		}
		public function addActiveFilter(key:String, val:String):Boolean{
			var f:Filter = getFilterByKey(key);
			if(f != null){
				if(f.isActive == true && f.value === val)return false;
				f.isActive = true;
				f.value = val;
				return true;
			}
			return false;
		}
		public function removeActiveFilters():void{
			var i:int = filters.length;
			while(i--)filters[i].isActive = false;
		}
		public function getFilters():Array{
			return filters;
		}
		public function getFilterByKey(key:String):Filter{
			var i:int = filters.length;
			while(i--)if(filters[i].key == key)return filters[i];
			return null;
		}
		private function addFilter(key:String, caption:String, isPublic:Boolean):void{
			var filter:Filter = new Filter(key, null);
			filter.caption = caption;
			filter.isPublic = isPublic;
			filters.push(filter);
		}
	}
}