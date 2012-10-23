package nl.bbvh.controls{
	
	import nl.bbvh.events.ScrollEvent;
	import flash.events.Event;
	import flash.events.MouseEvent;
	
	public class ScrollBar extends UIControl{

		public static const VERTICAL:String = "vertical";
		public static const HORIZONTAL:String = "horizontal";
		public static const WIDTH:Number = 15;
		private var _pageSize:Number;
		private var _pageScrollSize:Number = 10;
		private var _lineScrollSize:Number;
		private var _minScrollPosition:Number;
		private var _maxScrollPosition:Number;
		private var _scrollPosition:Number = 0;
		private var _direction:String;
		private var thumbScrollOffset:Number;
		protected var minThumbHeight:Number = 8;
		protected var inDrag:Boolean = false;
		protected var useDefaultLineScrollSize:Boolean = true;
		protected var upArrow:Button;
		protected var downArrow:Button;
		protected var thumb:Button;
		protected var track:Button;
		public static var sharedStyles:Object = {
			downArrowDownSkin:ScrollArrowDown_down,
			downArrowOverSkin:ScrollArrowDown_over,
			downArrowUpSkin:ScrollArrowDown_up,
			downArrowDisabledSkin:ScrollArrowDown_disabled,
			trackUpSkin:ScrollTrack_up,
			trackOverSkin:null,
			trackDownSkin:null,
			trackDisabledSkin:ScrollTrack_disabled,
			upArrowDownSkin:ScrollArrowUp_down,
			upArrowOverSkin:ScrollArrowUp_over,
			upArrowUpSkin:ScrollArrowUp_up,
			upArrowDisabledSkin:ScrollArrowUp_disabled,
			thumbUpSkin:ScrollThumb_up,
			thumbOverSkin:ScrollThumb_over,
			thumbDownSkin:ScrollThumb_down,
			thumbDisabledSkin:null
		};

		public function ScrollBar():void{
			super();
			setScrollProperties(0, 0, 0);//update thumb and enabled
			direction = VERTICAL;
		}
		public static function setStyle(name:String, value:Object):void{
			sharedStyles[name] = value;
		}
		override public function get width():Number{
			return (_direction == HORIZONTAL) ? super.height : super.width;
		}
		override public function get height():Number{
			return (_direction == HORIZONTAL) ? super.width : super.height;
		}
		override public function setSize(width:Number, height:Number):void{
			if(_direction == HORIZONTAL) super.setSize(height,width);
			else super.setSize(width,height);
		}
		public function setScrollProperties(pageSize:Number, minScrollPosition:Number, maxScrollPosition:Number):void{
			if(minScrollPosition != _minScrollPosition || maxScrollPosition != _maxScrollPosition || this.pageSize != pageSize){			
				_minScrollPosition = minScrollPosition;
				_maxScrollPosition = maxScrollPosition;
				this.pageSize = pageSize;
				var isEnabled:Boolean = enabled;
				enabled = (enabled && _maxScrollPosition > _minScrollPosition);//updates thumb
				super.enabled = isEnabled;
				// ensure our scroll position is still in range:
				setScrollPosition(_scrollPosition, false);//updates thumb
			}
		}
		public function get scrollPosition():Number{
			return _scrollPosition;
		}
		public function set scrollPosition(newScrollPosition:Number):void{
			setScrollPosition(newScrollPosition, true);
		}
		public function get minScrollPosition():Number{
			return _minScrollPosition;
		}		
		public function set minScrollPosition(value:Number):void{
			setScrollProperties(_pageSize, value, _maxScrollPosition);//update thumb and enabled
		}
		public function get maxScrollPosition():Number{
			return _maxScrollPosition;
		}
		public function set maxScrollPosition(value:Number):void{
			setScrollProperties(_pageSize, _minScrollPosition, value);//update thumb and enabled
		}
		public function get pageScrollSize():Number{
			return _pageScrollSize;
		}
		public function set pageScrollSize(value:Number):void{
			if(value>=0) _pageScrollSize = value;
		}
		public function get pageSize():Number{
			return _pageSize;
		}
		public function set pageSize(value:Number):void{
			if(_pageSize != value){
				if(value>=0) _pageSize = value;
				updateThumb();
			}
		}
		public function get lineScrollSize():Number{
			return _lineScrollSize;
		}
		public function set lineScrollSize(value:Number):void{
			if(value>0){
				_lineScrollSize = value;
				useDefaultLineScrollSize = false;
			}
		}
		override public function set enabled(value:Boolean):void{
			super.enabled = value;
			downArrow.enabled = track.enabled = thumb.enabled = upArrow.enabled = (enabled && _maxScrollPosition > _minScrollPosition);
			updateThumb();
		}
		public function get direction():String{
			return _direction;
		}
		public function set direction(value:String):void{
			if(_direction == value) return;
			_direction = value;
			scaleY = 1;
			if(_direction == HORIZONTAL){
				if(useDefaultLineScrollSize) _lineScrollSize = 5;
				if(rotation == 90) return;
				scaleX = -1;
				rotation = -90;
			}else if(useDefaultLineScrollSize) _lineScrollSize = 1;
			invalidateSize = true;
			invalidate();
		}
		override protected function draw():void{
			if(invalidateStyles){
				var btns:Array = ["upArrow", "downArrow", "thumb", "track"];
				for each(var btn:String in btns){
					var styles:Object = {};
					for(var style:String in instanceStyles){
						if(style.indexOf(btn) == 0){
							var l:int = btn.length;
							styles[style.substr(l,1).toLowerCase()+style.substr(l+1)] = instanceStyles[style];
						}
					}
					copyStylesToChild(this[btn], styles);
				}
			}
			downArrow.drawNow();
			upArrow.drawNow();
			track.drawNow();
			thumb.drawNow();
			if(invalidateSize){			
				var h:Number = _height;
				downArrow.x = 0;
				downArrow.y = Math.max(upArrow.height, h-downArrow.height);
				track.y = upArrow.height;
				track.height = Math.max(0, h-(downArrow.height + upArrow.height));
				updateThumb();
			}
			super.draw();
		}
		override protected function addChildren():void{
			super.addChildren();

			downArrow = new Button();
			downArrow.autoRepeat = true;
			addChild(downArrow);
			
			upArrow = new Button();
			upArrow.autoRepeat = true;
			addChild(upArrow);
			
			track = new Button();
			track.setSize(WIDTH,0);
			track.useHandCursor = false;
			track.autoRepeat = true;
			addChild(track);
			
			thumb = new Button();
			thumb.setSize(WIDTH,0);
			addChild(thumb);

			upArrow.addEventListener(MouseEvent.MOUSE_DOWN, scrollPressHandler, false, 0,true);
			downArrow.addEventListener(MouseEvent.MOUSE_DOWN, scrollPressHandler, false, 0,true);
			track.addEventListener(MouseEvent.MOUSE_DOWN, scrollPressHandler, false, 0, true);
			thumb.addEventListener(MouseEvent.MOUSE_DOWN, thumbPressHandler, false, 0, true);
		}
		protected function setScrollPosition(newScrollPosition:Number, fireEvent:Boolean=true):void{
			var oldPosition:Number = scrollPosition;
			_scrollPosition = Math.max(_minScrollPosition, Math.min(_maxScrollPosition, newScrollPosition));
			if(oldPosition == _scrollPosition) return;
			if(fireEvent) dispatchEvent(new ScrollEvent(_direction, scrollPosition-oldPosition, scrollPosition));
			updateThumb();
		}
		protected function scrollPressHandler(event:Event):void{
			event.stopImmediatePropagation();
			switch(event.currentTarget){
				case upArrow:
					setScrollPosition(_scrollPosition-_lineScrollSize);
					break;
				case downArrow:
					setScrollPosition(_scrollPosition+_lineScrollSize);
					break;
				default:
					var mousePosition:Number = track.mouseY/track.height * (_maxScrollPosition - _minScrollPosition) + _minScrollPosition;
					if(_scrollPosition < mousePosition) setScrollPosition(Math.min(mousePosition, _scrollPosition + _pageScrollSize));
					else if(_scrollPosition > mousePosition) setScrollPosition(Math.max(mousePosition, _scrollPosition - _pageScrollSize));
			}
		}
		protected function thumbPressHandler(event:MouseEvent):void{
			inDrag = true;
			thumbScrollOffset = mouseY-thumb.y;
			mouseChildren = false;
			stage.addEventListener(MouseEvent.MOUSE_MOVE, thumbDragHandler, false, 0, true);
			stage.addEventListener(MouseEvent.MOUSE_UP, thumbReleaseHandler, false, 0, true);
		}
		protected function thumbDragHandler(event:MouseEvent):void{
			if(track.height>thumb.height){
				var pos:Number = Math.max(0, Math.min(track.height - thumb.height, mouseY - track.y - thumbScrollOffset));
				setScrollPosition(pos / (track.height - thumb.height) * (_maxScrollPosition - _minScrollPosition) + _minScrollPosition);
				event.updateAfterEvent();
			}
		}
		protected function thumbReleaseHandler(event:MouseEvent):void{
			inDrag = false;
			mouseChildren = true;
			if(stage){
				stage.removeEventListener(MouseEvent.MOUSE_MOVE, thumbDragHandler, false);
				stage.removeEventListener(MouseEvent.MOUSE_UP, thumbReleaseHandler, false);
			}
		}
		protected function updateThumb():void{
			var per:Number = _maxScrollPosition - _minScrollPosition + _pageSize;
			if(!enabled || _maxScrollPosition <= _minScrollPosition || per == 0)thumb.visible = false;
			else{
				thumb.height = Math.max(Math.min(track.height, minThumbHeight), _pageSize / per * track.height);
				thumb.y = track.y + (track.height - thumb.height) * ((_scrollPosition - _minScrollPosition) / (_maxScrollPosition - _minScrollPosition));
				thumb.visible = true;
			}
		}
	}
}