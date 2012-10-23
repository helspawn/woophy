package com.woophy{

	import com.woophy.core.Application;
	import com.woophy.skins.MarkerSkinActive;
	import com.woophy.travel.TravelBlog;
	import com.woophy.ui.Marker;
	import com.woophy.utils.MillerProjection;
	
	import flash.display.DisplayObject;
	import flash.display.LoaderInfo;
	import flash.display.Sprite;
	import flash.display.StageAlign;
	import flash.display.StageScaleMode;
	import flash.events.MouseEvent;
	import flash.events.TimerEvent;
	import flash.geom.ColorTransform;
	import flash.net.URLRequest;
	import flash.net.navigateToURL;
	import flash.utils.Timer;

	public class MapSideBar extends Sprite{
	
		public var map:Sprite;
		protected var post_id:int;
		protected var latitude:Number;
		protected var longitude:Number;
		protected var city_id:int;
		protected var travelblog_id:int;
		protected var country_code:String;
		protected var colorActive:uint = 0xFE9900;
		private var stageSizeTimer:Timer;
		function MapSideBar(){
			stage.scaleMode = StageScaleMode.NO_SCALE;
			stage.align = StageAlign.TOP_LEFT;
			stage.showDefaultContextMenu=false;
			stageSizeTimer = new Timer(100, 0);//be sure stageWidth and stageHeight are correct, IE bug with cached swf
			stageSizeTimer.addEventListener(TimerEvent.TIMER, timerHandler);
			map.alpha = 0;
			stageSizeTimer.start();
			timerHandler(null);
		}
		private function timerHandler(event:TimerEvent):void{
			if(stage.stageHeight > 0 && stage.stageWidth > 0){
				stageSizeTimer.reset();
				stageSizeTimer.removeEventListener(TimerEvent.TIMER, timerHandler);
				stageSizeTimer = null;	
				init();
			}
		}
		private function init():void{	
			var param:Object = LoaderInfo(root.loaderInfo).parameters;			
			if(param["base_url"] != null) Application.base_url = param["base_url"];
			if(param["service_url"] != null) Application.service_url = param["service_url"];
			if(param["blog_url"] != null) Application.blog_url = param["blog_url"];
			if(param["post_id"] != null) post_id = int(param["post_id"]);
			if(param["latitude"] != null) latitude = Number(param["latitude"]);
			if(param["longitude"] != null) longitude = Number(param["longitude"]);
			if(param["city_id"] != null) city_id = int(param["city_id"]);
			if(param["travelblog_id"] != null) travelblog_id = int(param["travelblog_id"]);
			if(param["cc"] != null) country_code = param["cc"];
			if(param["country_code"] != null) country_code = param["country_code"];
			
			//latitude = 51.9167;
			//longitude = 4.5;
			//city_id = -2984658;
			//travelblog_id = 1;
			//post_id = 476;
			//country_code = "US";

			var stageWidth:int = stage.stageWidth;
			var stageHeight:int = stage.stageHeight;
			
			if(!(isNaN(latitude) || isNaN(longitude))){
				//show city:
				var projection:MillerProjection = new MillerProjection();
				var marker:Marker = new Marker(projection.computeX(longitude), projection.computeY(latitude));
				marker.setSkin(MarkerSkinActive);
				map.addChild(marker);
				map.x = Math.max(stageWidth - map.width+1, Math.min(0, stageWidth/2 - marker.x));
				map.y = Math.max(stageHeight - map.height+1, Math.min(0, stageHeight/2 - marker.y));
				map.alpha = 1;
				if(city_id !=0){
					marker.buttonMode = true;
					marker.id = city_id;
					marker.addEventListener(MouseEvent.CLICK, cityHandler, false, 0, true);
				}
			}else if(travelblog_id !=0){
				//show travelblog
				var blog:TravelBlog = new TravelBlog(map);
				blog.getBlogById(travelblog_id, post_id);
			}else{
				//show country:
				var country:DisplayObject;
				map.width = stageWidth;
				map.height = stageHeight;
				for(var i:int=0,l:int=map.numChildren; i<l; i++){    
					country = map.getChildAt(i);
					if(country is Sprite){
						country.addEventListener(MouseEvent.MOUSE_OVER, mouseOverHandler, false, 0, true);
						country.addEventListener(MouseEvent.MOUSE_OUT, mouseOutHandler, false, 0, true);
						country.addEventListener(MouseEvent.CLICK, countryHandler, false, 0, true);
						Sprite(country).buttonMode = true;
					}
				}
				if(country_code != null){
					country = map.getChildByName(country_code.toUpperCase());
					if(country is Sprite){
						setActive(country);
						Sprite(country).mouseEnabled = false;
						var scale:Number = Math.min((stageHeight-20)/country.height, (stageWidth-20)/country.width);//10px margin
						map.scaleX = map.scaleY = Math.min(scale, 5);//*100
						if(map.width < stageWidth) map.scaleX = map.scaleY = stageWidth/country.width;
						if(map.height < stageHeight) map.scaleX = map.scaleY = stageHeight/country.height;
						map.x = Math.max(stageWidth-map.width, Math.min(0, stageWidth/2 - (country.x+country.width/2)*map.scaleX));
						map.y = Math.max(stageHeight-map.height, Math.min(0, stageHeight/2 - (country.y+country.height/2)*map.scaleY));
					}
				}
				map.alpha = 1;
			}
		}
		private function mouseOverHandler(event:MouseEvent):void{
			setActive(DisplayObject(event.currentTarget));
		}
		private function mouseOutHandler(event:MouseEvent):void{
			Sprite(event.currentTarget).transform.colorTransform = new ColorTransform();
		}
		private function countryHandler(event:MouseEvent):void{
			navigateToURL(new URLRequest(Application.base_url + "country/"+Sprite(event.currentTarget).name), "_top");
		}
		private function cityHandler(event:MouseEvent):void{
			navigateToURL(new URLRequest(Application.base_url+"#&key=showall,cityid&val=0,"+Marker(event.currentTarget).id), "_top");
		}
		private function setActive(obj:DisplayObject):void{
			var tr:ColorTransform = obj.transform.colorTransform;
			tr.color = colorActive;
			obj.transform.colorTransform = tr;
		}
	}
}