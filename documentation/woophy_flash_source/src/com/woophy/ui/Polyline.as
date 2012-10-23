package com.woophy.ui{
	import flash.display.Sprite;

	public class Polyline extends Sprite{
		public function Polyline(points:Array, color:uint=0xFF9900){/*array of movieclips or points*/
			if(points.length>1){
				var newX:Number, newY:Number;
				var dCx:Number, dCy:Number, dx:Number, dy:Number, dCLength:Number, dLength:Number;
				var newCx:Number, newCy:Number, cScale:Number;
				var trail:Object = {
					prevx:points[0].x,
					prevy:points[0].y,
					prevCx:points[0].x/2,
					prevCy:points[0].y/2
				};
				graphics.lineStyle(0.1, color, 1);
				for(var j:int=1, l:int=points.length; j<l; j++){

					newX = points[j].x;
					newY = points[j].y;
					
					// Find a position for the control point to make the line smooth         
					// First find the triangle between the old end point and the old control point         
					dCx = trail.prevx-trail.prevCx;
					dCy = trail.prevy-trail.prevCy;
					dCLength = Math.sqrt(dCx*dCx+dCy*dCy);
					// Now find the triangle between the previous endpoint and the new one         
					dx = trail.prevx-newX;
					dy = trail.prevy-newY;
					dLength = Math.sqrt(dx*dx+dy*dy);
					// Find the scale factor for the control point triangle         
					cScale = 0;
					if (dCLength != 0){
						cScale = 0.5*dLength/dCLength;
					}
					// Apply the scale factor to create a similar triangle so the control point stays in line           
					newCx = trail.prevx+dCx*cScale;
					newCy = trail.prevy+dCy*cScale;
					// Line drawing - enable to visualise control point 
					//clip.moveTo(trail.prevx, trail.prevy);
					//clip.lineTo(newCx, newCy);
					//clip.lineTo(newX, newY);
					// Draw curve  
					graphics.moveTo(trail.prevx, trail.prevy);
					graphics.curveTo(newCx, newCy, newX, newY);
					// Update record of previous trail points         
					trail.prevx = newX;
					trail.prevy = newY;
					trail.prevCx = newCx;
					trail.prevCy = newCy;
				}
			}
		}
	}
}
