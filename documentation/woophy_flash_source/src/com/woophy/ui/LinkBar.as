package com.woophy.ui{

	import com.woophy.core.Application;
	import com.woophy.core.RemoteServlet;
	import com.woophy.managers.FilterManager;
	import com.woophy.search.MapSearchRequest;
	import com.woophy.search.PhotoSearchRequest;
	import com.woophy.search.SearchService;
	import com.woophy.utils.NetUtils;

	import flash.display.Sprite;
	import flash.events.MouseEvent;
	import flash.events.TimerEvent;
	import flash.net.URLRequest;
	import flash.net.navigateToURL;
	import flash.text.TextField;
	import flash.utils.Timer;

	import nl.bbvh.utils.StringUtils;

	public class LinkBar extends Sprite{

		public static const HEIGHT:Number = 80;
		public static const REFRESH_TIME:int = 60000;//millisec, 1 min

		public var searchService:SearchService;

		public var background:Sprite;
		protected var items:Array = [];
		protected var servlet:RemoteServlet;
		protected var firstRun:Boolean = true;
		protected var info:Object;//holds all data

		function LinkBar(){
			addItem("Stats", 160);
			addItem("... Added last 24h", 185, "");
			addItem("Latest Comment", 185, "");
			addItem("Latest Blogpost", 160, null);
			addItem("City of the Day", 185, "");
			addItem("Member of the Month", 185, "");
			addItem("News", 160, null);
			addItem("Did you know", 185, null);

			servlet = new RemoteServlet();
			var timer:Timer = new Timer(REFRESH_TIME, 0);
			timer.addEventListener(TimerEvent.TIMER, getStatus, false, 0, true);
			timer.start();
			//getStatus(null);
		}
		public function getStatus(event:TimerEvent=null):void{
			servlet.call("Info.getStatus", getStatusHandler, getStatusHandlerFault);
		}
		override public function set width(value:Number):void{
			background.width = value;
			for each(var item:Sprite in items){
				item.visible = value > (item.x + item.width) ? true : false;
			}
		}
		override public function get width():Number{
			return background.width;
		}
		protected function addItem(header:String, width:Number, img:String=null):Sprite{
			var item:LinkBarItem = new LinkBarItem();
			var x:Number = 0;
			if(items.length>0){
				var lastItem:LinkBarItem =items[items.length-1];
				x = lastItem.x + lastItem.width;
			}
			item.x = x;
			item.width = width;
			item.header = header;
			item.image = img;
			items.push(item);
			addChild(item);
			return item;
		}
/*
Array(
    [status_id] => 1
    [city_of_the_day_name] => Malaga
    [city_of_the_day_country] => Spain
    [city_of_the_day_uni] => -579795
    [city_of_the_day_pid] => 79784
    [city_of_the_day_date] => 2009-02-01
    [city_of_the_day_userid] => 5368
    [num_of_cities] => 4249
    [num_of_photos_today] => 2
    [num_of_photos] => 115938
    [num_of_users] => 1936
    [num_of_views] => 22583433
    [last_photo_id] => 147760
    [last_photo_country] => United States
    [last_photo_uni] => 11372320
    [last_photo_city] => Arlington
    [last_photo_userid] => 1
    [last_photo_username] => M�rcel
    [last_photo_date] => 2009-01-13 01:16:02
    [motm_date] => 2008-03-03
    [motm_name] => M�rcel
    [motm_country] => Netherlands
    [motm_pid] => 840
    [motm_id] => 1
    [last_comment] => yy
    [last_comment_pid] => 147755
    [last_comment_name] => Marcel
    [last_comment_userid] => 1
    [last_comment_pids] => 147755,147753,234,246,96,66,273,59,149,41,189,274,40,168,35,25,173,198,381,393
    [last_blog_post] => test
    [last_blog_user_name] => Marcel
    [news] => 22-10-08 Big and small improvements
    [status_date] => 2009-02-01 01:03:00
)*/

		protected function getStatusHandler(resp:Object):void{
			if(resp==null)return;
			//facts & figures
			items[0].setText(	"<b>"+StringUtils.formatNumber(resp["num_of_cities"])+"</b> cities present",
								"<b>"+StringUtils.formatNumber(resp["num_of_photos"])+"</b> photos online",
								"<b>"+StringUtils.formatNumber(resp["num_of_users"])+"</b> users registered");

			//latest photos:
			items[1].header = items[1].header.replace(/(\.\.\.|[0-9]+)/, resp["num_of_photos_today"]);
			items[1].setText(resp["last_photo_city"]+",", resp["last_photo_country"], "<i>"+resp["last_photo_username"]+"</i>");
			items[1].image = NetUtils.getPhotoUrl(resp["last_photo_userid"], resp["last_photo_id"], "s");

			//latest comment:
			items[2].setText(getExcerpt(resp["last_comment"], items[2].text)+"<br/><i>"+resp["last_comment_name"].replace(" ", "&nbsp;")+"</i>");
			items[2].image = NetUtils.getPhotoUrl(resp["last_comment_userid"], resp["last_comment_pid"], "s");

			//tip:
			items[7].setText(resp["tip"]);

			if(firstRun){
				//blog:
				items[3].setText(getExcerpt(resp["last_blog_post"], items[3].text), "<i>"+resp["last_blog_user_name"]+"</i>");

				//city of the day:
				items[4].setText(resp["city_of_the_day_name"]+",", resp["city_of_the_day_country"]);
				items[4].image = NetUtils.getPhotoUrl(resp["city_of_the_day_userid"], resp["city_of_the_day_pid"], "s");

				//member of the month:
				var date:String = "";
				if(resp["motm_date"]){
					var months:Array = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
					var a:Array = resp["motm_date"].split("-");
					if(a.length >= 2)date = months[parseInt(a[1])-1] + " '"+a[0].substr(-2);
				}
				items[5].setText("<i>"+date+"</i>", resp["motm_name"]+(resp["motm_country.length"]>0?",":""), resp["motm_country"]);
				items[5].image = NetUtils.getPhotoUrl(resp["motm_id"], resp["motm_pid"], "s");

				//news:
				items[6].setText(getExcerpt(resp["news"].replace("\r\n", "\n"), items[6].text, 3));

				//add listeners:
				for(var i:int=1; i<=6; i++){
					items[i].addEventListener(MouseEvent.CLICK, clickHandler, false, 0, true);
				}
			}
			firstRun = false;
			this.info = resp;
		}
		protected function getStatusHandlerFault(obj:Object):void{
			trace("onGetStatusFault", obj.description);
		}
		private function clickHandler(event:MouseEvent):void{
			var key:String;
			var value:String;
			switch(items.indexOf(event.currentTarget)){
				case 1:
					key = FilterManager.LAST24H_KEY;
					value = "";
					break;
				case 2:
					NetUtils.getPhotoPopup(info["last_comment_pid"], info["last_comment_pids"].split(","));
					return;
				case 3:
					navigateToURL(new URLRequest(Application.base_url+"member/"+encodeURIComponent(info["last_blog_user_name"])+"/blog/"), "_top");
					return;
				case 4:
					key = FilterManager.CITYID_KEY;
					value = info["city_of_the_day_uni"];
					break;
				case 5:
					key = FilterManager.USERID_KEY;
					value = info["motm_id"];
					break;
				case 6:
					navigateToURL(new URLRequest(Application.base_url+"news"), "_top");
					return;
			}
			if(key != null){
				if(searchService != null){
					var manager:FilterManager = FilterManager.getInstance();
					manager.setActiveFilter(key, value);
					var filters:Array = manager.getActiveFilters();
					searchService.call(new MapSearchRequest(filters));
					searchService.call(new PhotoSearchRequest(filters));
				}
			}
		}
		private function getExcerpt(str:String, txt:TextField, numLines:int=2, suffix:String="..."):String{
			txt.text = str;
			txt.wordWrap = true;
			while(txt.numLines>numLines){
				str = str.substr(0,-1);
				txt.text = str+suffix;
			}
			return txt.text;
		}
	}
}
