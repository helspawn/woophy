package com.woophy.utils{
	
	public class MillerProjection{

		public var precision:Number = 1;//default 100%
		public static const CENTER_LONGITUDE:Number = 327.3;
		public static const CENTER_LATITUDE:Number = 213.72;
		public static const SCALE_LATITUDE:Number = 239.6;
		public static const SCALE_LONGITUDE:Number = 653.3 / 360;

		public function MillerProjection(){
		}
		public function computeY(lat:Number):Number{//calculation of latitude of Miller projection
			var radlat:Number = lat * Math.PI/180.0;
			var dY:Number = -SCALE_LATITUDE * precision * 5/4 * Math.log(Math.tan(Math.PI/4+2*radlat/5))/Math.LN10;
			return (CENTER_LATITUDE * precision)+dY;
		}
		public function computeX(long:Number):Number{
			return (CENTER_LONGITUDE * precision + long * SCALE_LONGITUDE * precision);
		}
		public function computeLat(y:Number):Number{//inv miller
			return 5/2*(Math.atan(Math.exp((y - CENTER_LATITUDE * precision) * 4 * Math.LN10/(-SCALE_LATITUDE * precision * 5))) -Math.PI/4) * 180/Math.PI;
		}
		public function computeLong(x:Number):Number{
			return (x - CENTER_LONGITUDE * precision)/(SCALE_LONGITUDE * precision);
		}
		/*
		//calculation of latitude of Mercator projection
		function computeMercator(lat){
			var radlat = parseFloat(lat) * Math.PI/180.0;
			var dY = -1 * scale_latitude * Math.log(Math.tan(Math.PI/4 + radlat/2))/Math.LN10;
			return center_latitude + dY;
		}*/
		
		//DAY NIGHT VIEW
		/*

		private function computeDeclination(d:Number, m:Number, y:Number, h:Number) {
			var n, x;
			var rad = Math.PI/180.0;
			var ecliptic, y2000;
			n = 365*y+d+31*m-46;
			if (m<3) {
				n = n+Math.floor(((y-1)/4));
			} else {
				n = n-Math.floor((0.4*m+2.3))+Math.floor((y/4.0));
			}
			x = (n-693960)/1461.0;
			x = (x-Math.floor(x))*1440.02509+Math.floor(x)*0.0307572;
			x = x+h/24.0*0.9856645+356.6498973;
			x = x+1.91233*Math.sin(0.9999825*x*rad);
			x = (x+Math.sin(1.999965*x*rad)/50.0+282.55462)/360.0;
			x = (x-Math.floor(x))*360.0;
			y2000 = (y-2000)/100.0;
			ecliptic = 23.43929111-(46.8150+(0.00059-0.001813*y2000)*y2000)*y2000/3600.0;
			x = Math.sin(x*rad)*Math.sin(rad*ecliptic);
			return Math.atan(x/Math.sqrt(1.0-x*x))/rad+0.00075;
		}
		private function computeLat(longitude, dec) {
			var tan, itan;
			tan = -Math.cos(longitude*rad)/Math.tan(dec*rad);
			itan = Math.atan(tan);
			itan = itan/rad;
			return computeMiller(itan);
		}
		//Greenwich Hour Angle (GHA) is the East-West angle between the body's GP and the Greenwich meridian
		private function computeGHA(d, m, y, h) {
			var n, x, xx, p;
			n = 365*y+d+31*m-46;
			if (m<3) {
				n = n+Math.floor((y-1)/4);
			} else {
				n = n-Math.floor(0.4*m+2.3)+Math.floor(y/4.0);
			}
			
			p = h/24.0;
			x = (p+n-7.22449)*0.98564734+279.306;
			x = x*rad;
			
			xx = -104.55*Math.sin(x)-429.266*Math.cos(x)+595.63*Math.sin(2.0*x)-2.283*Math.cos(2.0*x);
			xx = xx+4.6*Math.sin(3.0*x)+18.7333*Math.cos(3.0*x);
			xx = xx-13.2*Math.sin(4.0*x)-Math.cos(5.0*x)-Math.sin(5.0*x)/3.0+0.5*Math.sin(6.0*x)+0.231;
			xx = xx/240.0+360.0*(p+0.5);
			if (xx>360) {
				xx = xx-360.0;
			}
			return xx;
		}
		private function paintDayNight(){
			//var dat = new Date(2005,7,20,13,0,0);
			var dat = new Date();
			var day = dat.getDay();
			var date = dat.getDate();
			var month = dat.getMonth();
			var year = dat.getYear();
			var hours = dat.getHours();
			var minutes = dat.getMinutes();
			var seconds = dat.getSeconds();
			var browserOffset = dat.getTimezoneOffset();
			browserOffset = -browserOffset/60;
			var locOffset = browserOffset;
			hours = hours-locOffset+minutes/60.0+seconds/3600.0;
			var x0 = 180;
			var y0 = 90;
			var dec = computeDeclination(date, month+1, year+1900, hours);

			var GHA = computeGHA(date, month+1, year+1900, hours);
			var x = x0-GHA;
			if (x<0) x = x+360;
			if (x>360) x = x-360;
			var y = computeMiller(dec);
			//Declination point:
			//mcDayNight.beginFill(0xff00ff, 100);
			//mcDayNight.drawCircle((scale_longitude/100)*x-1, y-1, 2*1, 2*1);

			var ys=dec>0 ? map_mc._height : 0;
			//curve:
			mcDayNight.beginFill(0x000000, 10);
			mcDayNight.moveTo(0, ys);

			mcDayNight.lineTo(0, computeLat(-x, dec));
			for (var i = -x; x+i<=360; i++) {
				mcDayNight.lineTo((scale_longitude*(x+i))/nScale, computeLat(i, dec));
			}
			mcDayNight.lineTo((scale_longitude*(x+i-1))/nScale, ys);
			mcDayNight.lineTo(0, ys);
			mcDayNight.endFill();
		}*/
		//END DAY NIGHT VIEW
	}
}