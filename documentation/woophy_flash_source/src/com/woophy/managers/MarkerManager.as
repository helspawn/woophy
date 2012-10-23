package com.woophy.managers{
	import com.woophy.ui.Map;
	import com.woophy.ui.Marker;
	import com.woophy.ui.MarkerClip;
	
	import flash.display.DisplayObject;
	import flash.events.Event;
	import flash.events.EventDispatcher;
	import flash.utils.getDefinitionByName;

 	public class MarkerManager extends EventDispatcher{

		protected var batchSize:int = 50;
		protected var batchJobs:Array = [];//{target:MarkerClip, queue:array}
		protected var map:Map;
		protected var skins:Array = [];//hash object
		public var colorCoef:Number = 40;//show different colors on search
		
		public function MarkerManager(map:Map){
			this.map = map;
			var i:int= -1;
			while(++i<5)skins[i] = getDefinitionByName("MarkerSkin"+String(i+1));
		}
		public function get isProcessing():Boolean{
			return batchJobs.length > 0;
		}
		public function getMarkerById(target:MarkerClip, id:int):Marker{
			var i:int = target.numChildren;
			while(i--){
				var child:DisplayObject = target.getChildAt(i);
				if(child is Marker){
					if(Marker(child).id == id) {
						return Marker(child);
					}
				}
			}
			return null;
		}
		public function removeMarkers(target:MarkerClip):void{
			var i:int = batchJobs.length;
			while(i--){
				if(batchJobs[i].target === target){
					abortBatchJob(i);
					//break;
				}
			}
			i = target.numChildren;
			while(i--) {
				if(target.getChildAt(i) is Marker)target.removeChildAt(i);
			}
		}
		public function addMarkers(cities:Array, target:MarkerClip):void{
			cities.sortOn("q", Array.NUMERIC);
			batchJobs.push({target:target, queue:cities, isProcessing:false});
			processQueue();
		}
		protected function processQueue(event:Event=null):void{
			if(batchJobs.length){
				var batchJob:Object = batchJobs[0];
				var target:MarkerClip = batchJob.target;
				if(target){
					if(!batchJob.isProcessing){
						batchJob.isProcessing = true;
						target.addEventListener(Event.ENTER_FRAME, processQueue, false, 0, true);
					}
					var cities:Array = batchJob.queue.splice(0, batchSize);
					var l:int = cities.length;
					var q:int;
					var c:Object;
					var idx:int;
					var scale:Number = 1/map.scaleX;
					var i:int = -1;
					var j:int = skins.length - 1;
					while(++i<l){
						try{
							c = cities[i];
							q = Math.max(0, c.q);
							idx = 5;
							while(colorCoef*Math.pow(idx--,2)>=q);
							var m:Marker = new Marker();
							m.setSkin(skins[Math.min(j, Math.max(0, idx+1))]);
							m.scaleX = m.scaleY = scale;
							m.x = c.x*map.precision;
							m.y = c.y*map.precision;
							m.id = c.u;
							target.addChild(m);
						}catch(e:Error){}
					}
					if(batchJob.queue.length > 0)return;
				}
				abortBatchJob(0);
				processQueue();//start next job
			}else dispatchEvent(new Event(Event.COMPLETE));
		}
		protected function abortBatchJob(index:int):void{
			if(batchJobs[index] != undefined){
				var job:Object = batchJobs.splice(index, 1)[0];
				if(job.isProcessing) job.target.removeEventListener(Event.ENTER_FRAME, processQueue, false);
			}
		}
	}
}