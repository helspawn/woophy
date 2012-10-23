package com.woophy.utils{
	import flash.external.ExternalInterface;
	import flash.net.navigateToURL;
	import flash.net.URLRequest;
	import com.woophy.core.Application;
	public class NetUtils{
		public static function getPhotoUrl(user_id:*, photo_id:*, size:String="m"):String{/*php equivalent found in classes\Utils.class.php */
			var url:String = "", a:Array = [];
			if(user_id == null || photo_id == null) return url;
			if(arguments.length == 3){
				var uid:String = user_id.toString();
				var l:int = uid.length, i:int = 0, d:int = 3;
				do{
					a.push(uid.substr(i, d));
					i += d;
				}while(i<l);
				url = Application.base_url + Application.images_url + a.join("/") + "/" + size + "/" + photo_id + ".jpg";
			}
			return url;
		}

		public static function getPhotoPopup(index:String, collection:Array=null):void{
			collection = collection || [index];
			if(ExternalInterface.available){
				ExternalInterface.call("getPhotoPopup", index, collection);
			}else{

				navigateToURL(new URLRequest("javascript:void(getPhotoPopup('"+index+"',["+collection+"]))"), "_self");
			}
		}
	}
}