package com.woophy.search{
    public class SearchRequest{
        public var filters:Array;
        public var command:String;
        public function SearchRequest(command:String, filters:Array){
			this.command = command;
			this.filters = filters || [];
        }
    }
}