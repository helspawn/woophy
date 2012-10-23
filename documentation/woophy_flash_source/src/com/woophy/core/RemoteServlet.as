package com.woophy.core{
	import flash.events.AsyncErrorEvent;
	import flash.events.Event;
	import flash.events.IOErrorEvent;
	import flash.events.NetStatusEvent;
	import flash.events.SecurityErrorEvent;
	import flash.net.NetConnection;
	import flash.net.ObjectEncoding;
	import flash.net.Responder;

	public class RemoteServlet{
		private static var connection:NetConnection;

		public function RemoteServlet(){
		}
		public function call(command:String, result:Function, status:Function = null, args:Array = null):void{
			if(connection == null){
				connection = new NetConnection();
				connection.objectEncoding = ObjectEncoding.AMF3;
				connection.addEventListener(NetStatusEvent.NET_STATUS, netStatusHandler);
				connection.addEventListener(IOErrorEvent.IO_ERROR, errorHandler);
				connection.addEventListener(AsyncErrorEvent.ASYNC_ERROR, errorHandler);
				connection.addEventListener(SecurityErrorEvent.SECURITY_ERROR, errorHandler);
				connection.connect(Application.base_url + Application.service_url);
			}
			connection.call.apply(connection, [command, new Responder(result, status)].concat(args || []));
		}
		public function close():void{
			connection.close();
		}
		public function destroy():void{
			if(connection){
				close();
				connection = null;
			}
		}
		private function netStatusHandler(event:NetStatusEvent):void{
			//trace(event.info.code)
			switch(event.info.code) {
				case "NetConnection.Connect.Closed":
					//The connection was closed successfully.
				case "NetConnection.Connect.Success":
					// The connection attempt succeeded.
				case "NetConnection.Call.BadVersion":
					// Packet encoded in an unidentified format.
				case "NetConnection.Call.Failed":
					//The NetConnection.call method was not able to invoke the server-side method or command.
				case "NetConnection.Call.Prohibited":
					//An Action Message Format (AMF) operation is prevented for security reasons. Either the AMF URL is not in the same domain as the SWF file, or the AMF server does not have a policy file that trusts the domain of the SWF file.
				default:
			}
		}
		private function errorHandler(event:Event):void{
			trace(event.type, event["text"]);
		}
	}
}