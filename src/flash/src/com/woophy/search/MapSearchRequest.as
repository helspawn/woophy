package com.woophy.search{
    public class MapSearchRequest extends SearchRequest{
		public static const TYPE:String = "Cities.getCities";
		public function MapSearchRequest(filters:Array){
           super(TYPE, filters);
        }
    }
}