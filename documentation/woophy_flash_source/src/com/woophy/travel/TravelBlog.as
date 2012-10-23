package com.woophy.travel{

	import com.woophy.core.Application;
	import com.woophy.core.RemoteServlet;
	import com.woophy.events.MarkerEvent;
	import com.woophy.skins.MarkerSkinActive;
	import com.woophy.ui.Marker;
	import com.woophy.ui.MarkerClip;
	import com.woophy.ui.Polyline;
	
	import flash.display.Sprite;
	import flash.events.MouseEvent;
	import flash.geom.Rectangle;
	import flash.net.URLRequest;
	import flash.net.navigateToURL;
	public class TravelBlog{

		private var container:MarkerClip;
		private var servlet:RemoteServlet;
		private var post_id:int;
		private var target:Sprite;
		
		public function TravelBlog(target:Sprite){
			this.target = target;
			target.alpha = 0;
			servlet = new RemoteServlet();
		}
		public function getBlogById(id:int, post_id:int=0):void{
			this.post_id = post_id;
			servlet.call("Cities.getCitiesByTravelBlogId", getCitiesHandler, getCitiesHandlerFault, [id]);
		}
		private function getCitiesHandler(cities:Array):void{
			var stageWidth:int = target.stage.stageWidth;
			var stageHeight:int = target.stage.stageHeight;
			if(cities && cities.length > 0){
				var markers:Array = new Array();
				container = new MarkerClip();
				container.addEventListener(MarkerEvent.CLICK, clickHandler, false, 0, true);
				var marker:Marker;
				for each(var city:Object in cities){
					marker = new Marker(city.x, city.y);
					marker.id = city.p;
					marker.buttonMode = true;
					if(post_id == city.p){
						marker.highlight(true);
						marker.mouseEnabled = false;
					}
					markers.push(marker);
					container.addChild(marker);
				}
				
				if(markers.length)markers[markers.length-1].setSkin(MarkerSkinActive);//highlight last dot
				
				//scale and position:
				var scale:Number = Math.min((stageHeight-20)/container.height, (stageWidth-20)/container.width);//10px margin
				scale = Math.min(scale, 6);//max 600% zoom
				if(target.width*scale < stageWidth) scale = stageWidth/target.width;
				if(target.height*scale < stageHeight) scale = stageHeight/target.height;
				var s:Number = 1/scale;
				for each(marker in markers){
					marker.scaleX = marker.scaleY = s;
				}		
				var rect:Rectangle = container.getRect(target);
				target.scaleX = target.scaleY = scale;
				target.x = Math.max(stageWidth-target.width, Math.min(0, stageWidth/2 - (rect.x+container.width/2)*scale));
				target.y = Math.max(stageHeight-target.height, Math.min(0, stageHeight/2 - (rect.y+container.height/2)*scale));
				container.addChildAt(new Polyline(markers), 0);
				target.addChild(container);
			
			}else{
				target.width = stageWidth;
				target.height = stageHeight;
			}
			target.alpha =1;
		}
		private function getCitiesHandlerFault(obj:Object):void{
			trace("onGetCitiesFault", obj.description);
		}
		protected function clickHandler(event:MarkerEvent):void{
			navigateToURL(new URLRequest(Application.blog_url + event.marker.id), "_top");
		}
	}
}