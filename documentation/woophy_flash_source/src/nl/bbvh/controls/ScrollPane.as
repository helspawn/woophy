package nl.bbvh.controls{
	
	import nl.bbvh.events.ScrollEvent;
	import flash.events.Event;
	import flash.events.MouseEvent;
	import flash.display.Sprite;
	import flash.display.Graphics;
	import flash.geom.Rectangle;
	import flash.display.DisplayObject;
	import flash.display.Shape;
	
	public class ScrollPane extends UIControl{
		
		protected var skin:DisplayObject;
		protected var border:Shape;
		protected var _scrollStepSize:Number = 1;
		protected var defaultLineScrollSize:Number = 4;
		protected var _verticalScrollBar:ScrollBar;
		protected var _horizontalScrollBar:ScrollBar;
		protected var _horizontalScroll:Boolean = true;
		protected var _verticalScroll:Boolean = true;
		protected var availableHeight:Number;
		protected var availableWidth:Number;
		protected var contentScrollRect:Rectangle;
		protected var contentClip:Sprite;
		protected var currentContent:DisplayObject;
		protected var contentWidth:Number = 0;
		protected var contentHeight:Number = 0;
		public static var sharedStyles:Object = {
			skin:ScrollPane_skin,
			disabledSkin:ScrollPane_skinDisabled,
			border:false,
			borderColor:0x666666,
			disabledBorderColor:0xCCCCCC
		};

		public function ScrollPane():void{
			super();
		}
		public static function setStyle(name:String, value:Object):void{
			sharedStyles[name] = value;
		}
		public function update():void{
			invalidateSize = true;
			invalidate();
		}
		public function set scrollContent(value:Object):void{
			clearContent();
			if(value == "" || value == null) return;
			if(value is Class) currentContent = new value();
			else if(value is DisplayObject) currentContent = DisplayObject(value);
			else{
				var instance:DisplayObject = getDisplayObjectInstance(value);
				if(instance != null)currentContent = instance;
			}
			if(currentContent != null){
				contentClip.addChild(currentContent as DisplayObject);
				update();
			}
		}
		protected function clearContent():void{
			if(contentClip.numChildren == 0) return;
			contentClip.removeChildAt(0);
			currentContent = null;
		}
		public function get horizontalScrollBar():ScrollBar{
			return _horizontalScrollBar;
		}
		public function get verticalScrollBar():ScrollBar{
			return _verticalScrollBar;
		}
		public function get horizontalScroll():Boolean{
			return _horizontalScroll;
		}
		public function set horizontalScroll(value:Boolean):void{
			_horizontalScroll = value;
			invalidate();
		}
		public function get verticalScroll():Boolean{
			return _verticalScroll;
		}		
		public function set verticalScroll(value:Boolean):void{
			_verticalScroll = value;
			invalidate();
		}
		public function get horizontalScrollPosition():Number{
			return _horizontalScrollBar.scrollPosition;
		}
		public function set horizontalScrollPosition(value:Number):void{
			drawNow();
			_horizontalScrollBar.scrollPosition = checkScrollPosition(value);
			setHorizontalScrollPosition(_horizontalScrollBar.scrollPosition);
		}
		public function get verticalScrollPosition():Number{
			return _verticalScrollBar.scrollPosition;
		}
		public function set verticalScrollPosition(value:Number):void{
			drawNow();
			_verticalScrollBar.scrollPosition = checkScrollPosition(value);
			setVerticalScrollPosition(_verticalScrollBar.scrollPosition);
		}
		public function set scrollStepSize(value:Number):void{
			_scrollStepSize = Math.max(1, value);
			_verticalScrollBar.lineScrollSize = _verticalScrollBar.pageScrollSize = _scrollStepSize;
			_horizontalScrollBar.lineScrollSize = _horizontalScrollBar.pageScrollSize = _scrollStepSize;
		}
		public function get scrollStepSize():Number{
			return _scrollStepSize;
		}
		override public function set enabled(value:Boolean):void{
			_verticalScrollBar.enabled = value;
			_horizontalScrollBar.enabled = value;
			super.enabled = value;
		}
		override protected function draw():void{
			if(invalidateStyles){
				var style:Object = getStyle(enabled?"skin":"disabledSkin");		
				if(style){
					if(!(skin is Class(style))){
						var s:DisplayObject = getDisplayObjectInstance(style);
						if(s != null && s != skin){
							s.width = width;
							s.height = height;
							addChildAt(s, 0);
							if(skin != null)removeChild(skin);
							skin = s;
						}
					}
				}
				drawBorder();
			}

			if(invalidateSize){
				if(currentContent != null){
					var rect:Rectangle = currentContent.getRect(currentContent);
					currentContent.x = -rect.left*currentContent.scaleX;
					currentContent.y = -rect.top*currentContent.scaleY;
					contentWidth = currentContent.width;
					contentHeight = currentContent.height;
				}
				var scrollBarWidth:Number = ScrollBar.WIDTH;
				//var padding:Number = getStyle("contentPadding") as Number;
				// figure out which scrollbars we need
				var availHeight:Number = height;
				var vScrollBar:Boolean = _verticalScroll && contentHeight > availHeight;
				var availWidth:Number = width - (vScrollBar ? scrollBarWidth : 0);
				var maxHScroll:Number = contentWidth - availWidth;
				var hScrollBar:Boolean = _horizontalScroll && maxHScroll > 0;
				if(hScrollBar) availHeight -= scrollBarWidth;
				// catch the edge case of the horizontal scroll bar necessitating a vertical one:
				if(hScrollBar && !vScrollBar && _verticalScroll && contentHeight > availHeight){
					vScrollBar = true;
					availWidth -= scrollBarWidth;
				}
				availableHeight = availHeight;
				availableWidth = availWidth;
				if(vScrollBar){
					_verticalScrollBar.visible = true;
					_verticalScrollBar.x = width - scrollBarWidth;
					_verticalScrollBar.y = 0;
					_verticalScrollBar.height = availableHeight;
				}else _verticalScrollBar.visible = false;
				_verticalScrollBar.setScrollProperties(availableHeight, 0, contentHeight - availableHeight);
				_verticalScrollBar.drawNow();
				setVerticalScrollPosition(_verticalScrollBar.scrollPosition);
				if(hScrollBar){
					_horizontalScrollBar.visible = true;
					_horizontalScrollBar.x = 0;
					_horizontalScrollBar.y = height - scrollBarWidth;
					_horizontalScrollBar.width = availableWidth;
					
				}else{
					_horizontalScrollBar.visible = false;
					_horizontalScrollBar.width = 0;
				}
				_horizontalScrollBar.setScrollProperties(availableWidth, 0, contentWidth - availableWidth);
				_horizontalScrollBar.drawNow();
				setHorizontalScrollPosition(_horizontalScrollBar.scrollPosition);
				contentScrollRect = contentClip.scrollRect;
				contentScrollRect.width = availableWidth;
				contentScrollRect.height = availableHeight;
				//contentClip.cacheAsBitmap = useBitmapScrolling;
				contentClip.scrollRect = contentScrollRect;
				contentClip.x = contentClip.y = 0;
				if(skin){
					skin.width = width;
					skin.height = height;
				}
				drawBorder();
			}
			super.draw();
		}
		private function drawBorder():void{
			var gr:Graphics = border.graphics;
			gr.clear();
			if(getStyle("border")){
				var thickness:int = 1;
				if(width>thickness && height>thickness){
					gr.beginFill(getStyle(enabled ? "borderColor" : "disabledBorderColor") as int, 100);
					gr.drawRect(0, 0, width, height);
					gr.drawRect(thickness, thickness, width - 2*thickness, height - 2*thickness);
					gr.endFill();
				}
			}
		}
		override protected function addChildren():void{
			super.addChildren();
			contentScrollRect = new Rectangle(0,0,85,85);
			contentClip = new Sprite();
			addChild(contentClip);
			contentClip.scrollRect = contentScrollRect;
			_verticalScrollBar = new ScrollBar();
			_verticalScrollBar.addEventListener(ScrollEvent.SCROLL,scrollHandler,false,0,true);
			_verticalScrollBar.visible = false;
			_verticalScrollBar.lineScrollSize = defaultLineScrollSize;
			addChild(_verticalScrollBar);
			_horizontalScrollBar = new ScrollBar();
			_horizontalScrollBar.direction = ScrollBar.HORIZONTAL;
			_horizontalScrollBar.addEventListener(ScrollEvent.SCROLL,scrollHandler,false,0,true);
			_horizontalScrollBar.visible = false;
			_horizontalScrollBar.lineScrollSize = defaultLineScrollSize;
			addChild(_horizontalScrollBar);
			border = new Shape();
			addChild(border);
			addEventListener(MouseEvent.MOUSE_WHEEL,wheelHandler,false,0,true);
		}
		protected function scrollHandler(event:ScrollEvent):void{
			var position:Number = checkScrollPosition(event.position);
			if(event.target == _verticalScrollBar) setVerticalScrollPosition(position);
			else setHorizontalScrollPosition(position);
		}
		protected function checkScrollPosition(value:Number):Number{
			return Math.floor(value / _scrollStepSize) * _scrollStepSize;
		}
		protected function wheelHandler(event:MouseEvent):void{
			if(!enabled || !_verticalScrollBar.visible || contentHeight <= availableHeight) return;
			_verticalScrollBar.scrollPosition = checkScrollPosition(_verticalScrollBar.scrollPosition - (event.delta / (_scrollStepSize>1 ? Math.abs(event.delta) : 1)) * _verticalScrollBar.lineScrollSize);
			setVerticalScrollPosition(_verticalScrollBar.scrollPosition);
			dispatchEvent(new ScrollEvent(ScrollBar.VERTICAL, event.delta, _verticalScrollBar.scrollPosition));
		}
		protected function setVerticalScrollPosition(scrollPos:Number):void{	
			var contentScrollRect:Rectangle = contentClip.scrollRect;
			contentScrollRect.y = scrollPos;
			contentClip.scrollRect = contentScrollRect;
		}
		protected function setHorizontalScrollPosition(scrollPos:Number):void{
			var contentScrollRect:Rectangle = contentClip.scrollRect;
			contentScrollRect.x = scrollPos;
			contentClip.scrollRect = contentScrollRect;
		}
	}
}