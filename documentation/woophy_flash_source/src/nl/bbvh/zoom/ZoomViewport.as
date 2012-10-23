package nl.bbvh.zoom{

	import flash.display.DisplayObject;
	import flash.display.Shape;
	import flash.display.Sprite;
	import flash.events.Event;
	import flash.events.MouseEvent;
	import flash.geom.Point;
	import flash.geom.Rectangle;

	import nl.bbvh.events.ZoomEvent;

	public class ZoomViewport extends Sprite{

		public var zoomRectColor:uint = 0x000000;
		public var maxZoomScale:Number = 200;//*100 = 20000%;
		public var minZoomScale:Number = 1;//*100 = 100%;
		public var draggingEnabled:Boolean = false;
		public var mouseWheelEnabled:Boolean = true;
		protected var _enabled:Boolean = true;
		protected var _bounds:Rectangle;
		protected var _content:DisplayObject;
		protected var _autoWidth:Boolean = true;//content fits horizontally in the viewport
		protected var autoWidthFactor:Number = 1;
		protected var inDrag:Boolean = false;
		protected var dragPoint:Point;
		protected var contentMask:Shape = new Shape();
		protected var zoomCanvas:Shape = new Shape();//drawing zoom rectangle

		function ZoomViewport(){
			bounds = getBounds(this);
			while(numChildren>0)removeChildAt(0);
			content = new Sprite();
			addChild(contentMask);
			addChild(zoomCanvas);
		}
		public function get zoom():Number{
			if(_autoWidth){
				if(_bounds.width)return _content.width/_bounds.width;
			}
			return scale;
		}
		public function get scale():Number{
			return _content.scaleX;
		}
		public function get enabled():Boolean{
			return _enabled;
		}
		public function set enabled(value:Boolean):void{
			_enabled = value;
		}
		public function set content(value:DisplayObject):void{
			if(_content == value) return;
			if(_content != null){
				_content.mask = null;
				_content.removeEventListener(MouseEvent.MOUSE_DOWN, mouseDownHandler, false);
				_content.removeEventListener(MouseEvent.MOUSE_UP, mouseUpHandler, false);
				_content.removeEventListener(MouseEvent.MOUSE_WHEEL, mouseWheelHandler, false);
				if(contains(_content))removeChild(_content);
			}
			_content = value;
			_content.mask = contentMask;
			_content.addEventListener(MouseEvent.MOUSE_DOWN, mouseDownHandler, false, 0, true);
			_content.addEventListener(MouseEvent.MOUSE_UP, mouseUpHandler, false, 0, true);
			_content.addEventListener(MouseEvent.MOUSE_WHEEL, mouseWheelHandler, false, 0, true);
			resetAutoWidthFactor();
			if(_autoWidth) fitWidth();
			else zoomTo(scale/autoWidthFactor);//check scale and position
			addChildAt(_content, 0);
			dispatchEvent(new Event(Event.CHANGE));
		}
		public function get content():DisplayObject{
			return _content;
		}
		public function set bounds(value:Rectangle):void{
			if(_bounds && _bounds.equals(value)) return;
			_bounds = value;
			with(contentMask.graphics){
				clear();
				beginFill(0x000000, 1);
				drawRect(value.x, value.y, value.width, value.height);
				endFill();
			}
			if(_content){
				resetAutoWidthFactor();
				if(_autoWidth && _content.width < _bounds.width) fitWidth();
				else zoomTo(scale/autoWidthFactor);//check scale and position
			}
			dispatchEvent(new Event(Event.RESIZE));
		}
		public function get bounds():Rectangle{
			return _bounds.clone();
		}
		public function set autoFill(value:Boolean):void{
			_autoWidth = value;
			resetAutoWidthFactor();
		}
		public function get autoFill():Boolean{
			return _autoWidth;
		}
		//originX, originY: content coordinate system, centerpoint of scaling if doCenter=false
		public function zoomTo(percent:Number, originX:Number=NaN, originY:Number=NaN, doCenter:Boolean=false):void{
			if(!_enabled || isNaN(percent) || !isFinite(percent))return;
			var min:Number = _autoWidth ? 1 : minZoomScale;
			var max:Number = _autoWidth ? Math.max(1, maxZoomScale) : maxZoomScale;
			percent = Math.min(max*autoWidthFactor, Math.max(min*autoWidthFactor, percent*autoWidthFactor));
			if(percent>0 && _content.width>0){
				var centerBounds:Point = getRectCenter(_bounds);
				if(isNaN(originX)) originX = (centerBounds.x - _content.x)/_content.scaleX;
				if(isNaN(originY)) originY = (centerBounds.y - _content.y)/_content.scaleY;
				var oldScale:Number = scale;
				if(oldScale != percent){
					_content.scaleX = _content.scaleY = percent;
					dispatchEvent(new ZoomEvent(ZoomEvent.ZOOM));
				}
				if(doCenter) moveTo(centerBounds.x - (originX*percent), centerBounds.y - (originY*percent));
				else{
					var delta:Number = percent - oldScale;
					moveBy(-originX*delta, -originY*delta);
				}
			}
		}
		public function zoomBy(factor:Number, originX:Number=NaN, originY:Number=NaN, doCenter:Boolean=false):void{
			zoomTo(_content.scaleX*factor/autoWidthFactor, originX, originY, doCenter);
		}
		public function moveTo(x:Number, y:Number):void{
			if(!_enabled)return;
			if(y + _content.height < _bounds.y + _bounds.height) y = _bounds.y + _bounds.height - _content.height;//bottom
			if(y > _bounds.y)y = _bounds.y;//top
			if(x + _content.width < _bounds.x + _bounds.width)x = _bounds.x + _bounds.width - _content.width;//left
			if(x > _bounds.x)x = _bounds.x;//right
			if(_content.x == x && _content.y == y) return;
			_content.x = x;
			_content.y = y;
			dispatchEvent(new ZoomEvent(ZoomEvent.MOVE));
		}
		public function moveBy(x:Number, y:Number):void{
			moveTo(content.x + x, content.y + y);
		}
		public function fitWidth():void{
			zoomTo(_bounds.width/(_content.width/_content.scaleX)/autoWidthFactor);
		}
		protected function mouseDownHandler(event:MouseEvent):void{
			if(!_enabled)return;
			inDrag = true;
			_content.cacheAsBitmap = true;
			dragPoint = new Point(mouseX, mouseY);
			stage.addEventListener(MouseEvent.MOUSE_MOVE, mouseMoveHandler, false, 0, true);
			stage.addEventListener(MouseEvent.MOUSE_UP, mouseUpHandler, false, 0, true);
			dispatchEvent(new ZoomEvent(ZoomEvent.DRAG_START));
		}
		protected function mouseMoveHandler(event:MouseEvent):void{
			if(draggingEnabled){
				var oldPoint:Point = dragPoint.clone();
				dragPoint = new Point(mouseX, mouseY);
				moveBy(dragPoint.x - oldPoint.x, dragPoint.y - oldPoint.y);
			}else{
				var w:Number = mouseX - dragPoint.x;
				var minW:Number = _bounds.x - dragPoint.x;
				var maxW:Number = _bounds.x + _bounds.width - dragPoint.x -1;//1px line
				if(w < minW) w = minW;
				if(w > maxW) w = maxW;
				var h:Number = mouseY - dragPoint.y;
				var minH:Number = _bounds.y - dragPoint.y;
				var maxH:Number = _bounds.y + _bounds.height - dragPoint.y -1;//1px line
				if(h < minH) h = minH;
				if(h > maxH) h = maxH;
				with(zoomCanvas.graphics){
					clear();
					beginFill(zoomRectColor, .2);
					lineStyle(0, zoomRectColor);
					drawRect(dragPoint.x, dragPoint.y, w, h);
					endFill();
				}
			}
			event.updateAfterEvent();
		}
		protected function mouseUpHandler(event:MouseEvent):void{
			if(inDrag){
				stage.removeEventListener(MouseEvent.MOUSE_MOVE, mouseMoveHandler, false);
				stage.removeEventListener(MouseEvent.MOUSE_UP, mouseUpHandler, false);
				inDrag = false;
				_content.cacheAsBitmap = false;
				dispatchEvent(new ZoomEvent(ZoomEvent.DRAG_STOP));
				if(!draggingEnabled){
					var zoomRect:Rectangle = zoomCanvas.getRect(this);
					if(zoomRect.width <= 3 && zoomRect.height <= 3) zoomBy(2, _content.mouseX, _content.mouseY);
					else{
						var centerZoom:Point = getRectCenter(zoomRect);
						var factor:Number = Math.min(_bounds.width/zoomRect.width, _bounds.height/zoomRect.height);
						zoomBy(factor, (centerZoom.x - _content.x)/_content.scaleX, (centerZoom.y - _content.y)/_content.scaleY, true);
					}
					zoomCanvas.graphics.clear();
				}
			}
		}
		protected function mouseWheelHandler(event:MouseEvent):void{
			if(mouseWheelEnabled)zoomBy(event.delta<0 ? .5 : 2, _content.mouseX, _content.mouseY);
		}
		private function getRectCenter(rect:Rectangle):Point{
			return new Point(rect.x + .5*rect.width, rect.y + .5*rect.height);
		}
		private function resetAutoWidthFactor():void{
			if(_content.width>0){
				autoWidthFactor = _autoWidth ? _bounds.width/(_content.width/_content.scaleX) : 1;
			}
		}
	}
}