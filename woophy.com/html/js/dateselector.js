function DateSelector(divObj){
	if(divObj == undefined) return;
	this.months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
	this.months_short = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
	this.weekdays = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
	this._showWeeks = false;
	this.divNextMonth = document.createElement('div');
	jQuery(this.divNextMonth).append(document.createTextNode('\u00a0')).addClass('DS_paging DS_pageforward sprite').click(jQuery.proxy(this.nextMonth, this));
	this.divPrevMonth = document.createElement('div');
	jQuery(this.divPrevMonth).append(document.createTextNode('\u00a0')).addClass('DS_paging DS_pagebackward sprite').click(jQuery.proxy(this.prevMonth, this));

	this.rangeStart = null;
	this.rangeEnd = null;
	
	this.selectYear = null;
	this.yearFrom = null;
	this.yearTo = null;
	this.divNextMonthDisabled = null;
	this.divPrevMonthDisabled = null;

	this.selectedDate = null;
	this.setCurrentDate(new Date());
	
	this.calendar = document.createElement('div');
	this.calendar.className = 'DS_calendar';
	
	this.setRowWeekdays();
	this.invalidate();
	jQuery(document).ready(
		jQuery.proxy(function(){
			divObj.appendChild(this.calendar);
		}, this)
	);
}
DateSelector.prototype = {
	//public:
	DATE_EVENT : 'onSelectDate',
	WEEK_EVENT : 'onSelectWeek',
	MONTH_EVENT : 'onSelectMonth',
	getSelectedDate : function(){
		return this.selectedDate;
	},
	setSelectedDate : function(date){
		date = date instanceof Date ? date : null;
		this.selectedDate = date;
		this.setCurrentDate(date);
		this.invalidate();
	},
	setSelectedMonth : function(year, month){/*month is zero based*/	
		var d = new Date();
		d.setFullYear(year,month,1);
		this.currentYear = d.getFullYear();
		this.currentMonth = d.getMonth();
		this.invalidate();
	},
	showWeeks : function(bln){
		if(this._showWeeks != bln){
			this._showWeeks = bln;
			this.setRowWeekdays();
			this.invalidate();
		}
	},
	setRange : function(s,e) {
		this.rangeStart = s;
		this.rangeEnd = e;
		this.invalidate();
	},
	setYearRange : function(f, t){
		if(typeof f == 'number' && typeof t == 'number' && f < t) {	
			this.yearFrom = f;
			this.yearTo = t;
			this.divNextMonthDisabled = document.createElement('div');
			this.divNextMonthDisabled.appendChild(document.createTextNode('\u00a0'));
			this.divNextMonthDisabled.className = 'DS_paging DS_pageforward_disabled sprite';
			this.divPrevMonthDisabled = document.createElement('div');
			this.divPrevMonthDisabled.appendChild(document.createTextNode('\u00a0'));
			this.divPrevMonthDisabled.className = 'DS_paging DS_pagebackward_disabled sprite';
			this.selectYear = document.createElement('select');
			this.selectYear.className = 'DS_year';
			for(var i=t; i>=f; i--){
				var o = document.createElement('option');
				if(this.currentYear == i) o.selected = true;
				o.value = i;
				o.appendChild(document.createTextNode(i.toString())); 
				this.selectYear.appendChild(o);
			}
			jQuery(this.selectYear).change(jQuery.proxy(this.onSelectYear, this));
			this.invalidate();
		}
	},
	dateFormatter : function(d){
		return d.toDateString();//default output
	},
	//private:
	setRowWeekdays : function(){
		this.rowWeekdays = document.createElement('tr');
		var a = this.weekdays.slice();
		if(this._showWeeks)a.unshift('\u00a0');
		var n = a.length;
		var i = -1;
		while(++i<n){
			c = document.createElement('td');
			c.className = 'DS_weekday';
			c.appendChild(document.createTextNode(a[i]));
			this.rowWeekdays.appendChild(c);
		}
	},
	getWeek : function(date) {
		return parseInt((date.getTime() - new Date(date.getFullYear(),0,1).getTime())/604800000 + 1);
	},
	isSelectedDate : function(date){
		if(this.selectedDate)return this.compareDates(this.selectedDate, date) == 0;
		return false;
	},
	compareDates : function(date1, date2){
		//Returns -1 if d1 is greater than d2. Returns 1 if d2 is greater than d1. Returns 0 if both dates are equal.
		var y1 = date1.getFullYear(), y2 = date2.getFullYear(), m1 = date1.getMonth(), m2 = date2.getMonth(), d1 = date1.getDate(), d2 = date2.getDate();
		date1 = new Date(y1, m1, d1);//skip time
		date2 = new Date(y2, m2, d2);
		if(date1 > date2) return -1;
		else if(date1 < date2) return 1;
		else if(y1 == y2 && m1 == m2 && d1 == d2) return 0;
		else return;
	},
	setCurrentDate : function(date){
		date = date instanceof Date ? date : new Date();
		this.currentDate = date;
		this.currentMonth = date.getMonth();
		this.currentYear = date.getFullYear();
	},
	prevMonth : function(){
		this.setCurrentDate(this.currentMonth > 0 ? new Date(this.currentYear, this.currentMonth - 1, 1) : new Date(this.currentYear - 1, 11, 1));//detect correct month/year for KHTML
		this.invalidate();
	},
	nextMonth : function(){
		this.setCurrentDate(new Date(this.currentYear, this.currentMonth + 1, 1));//works in all browsers
		this.invalidate();
	},
	onSelectYear : function(evt){
		this.setCurrentDate(new Date(evt.currentTarget.options[evt.currentTarget.selectedIndex].value, this.currentMonth));	
		this.invalidate();
	},
	setClassName : function (cell, date){
		cell.className = this.isSelectedDate(date) ? 'DS_date_selected' : this.compareDates(new Date(), date) == 0 ? 'DS_today' : 'DS_date';
	},
	setSelected : function (cell, date){
		if(this.selectedCell) var c = this.selectedCell, d = this.selectedDate;
		if(this.selectedCell === cell){
			this.selectedCell = null;
			this.selectedDate = null;
		}else{
			this.selectedDate = date;
			this.selectedCell = cell;
			this.setClassName(cell, date);
		}
		if(c) this.setClassName(c, d);
		jQuery(this).trigger(this.DATE_EVENT, [this.selectedDate?this.dateFormatter(this.selectedDate):null]);
	},
	invalidate : function(){
		clearTimeout(this.timer);
		this.timer = setTimeout(jQuery.proxy(this.onInvalidate, this), 0);
	},
	onInvalidate : function(){
		this.selectedCell = null;
		var n = this.weekdays.length;

		//header
		var header = document.createElement('div');
		var label = document.createElement('div');
		header.className = 'MonthHeader clearfix';
		label.className = 'DS_month';
		
		if(this.selectYear){
			header.appendChild((this.currentYear == this.yearFrom && this.currentMonth == 0) ? this.divPrevMonthDisabled : this.divPrevMonth);
			label.appendChild(document.createTextNode(this.months_short[this.currentMonth] + '\u00a0'));
			if(this.selectYear.options[this.selectYear.selectedIndex].value != this.currentYear){
				var i = -1;
				var l = this.selectYear.options.length;
				while(++i<l){
					if(this.currentYear == this.selectYear.options[i].value){
						this.selectYear.selectedIndex = i;
						break;
					}
				}
			}
			label.appendChild(this.selectYear);
			header.appendChild(label);
			header.appendChild((this.currentYear == this.yearTo && this.currentMonth == 11) ? this.divNextMonthDisabled : this.divNextMonth);
		}else{
			header.appendChild(this.divPrevMonth);
			label.appendChild(document.createTextNode(this.months[this.currentMonth]+ '\u00a0' + this.currentYear));
			jQuery(label).click(jQuery.proxy(function(){
				jQuery(this).trigger(this.MONTH_EVENT, [this.currentYear + '-' + (this.currentMonth+1)]);
			}, this));
			header.appendChild(label);
			header.appendChild(this.divNextMonth);
		}

		var tbl = document.createElement('table');
		var tbd = document.createElement('tbody');
		tbl.appendChild(tbd);

		//weekdays
		tbd.appendChild(this.rowWeekdays);

		//dates:
		var d = new Date(this.currentYear, this.currentMonth, 1);
		d.setDate(d.getDate() - d.getDay());
		var i = -1, m;
		while (++i < 6) {
			r = document.createElement('tr');
			var j = -1;
			while (++j < n) {
				m = d.getMonth() == this.currentMonth;
				if(this._showWeeks && j==0){
					c = document.createElement('td');
					if(i==0 || m){
						var week = this.getWeek(d);
						c.className = 'DS_week';
						c.appendChild(document.createTextNode(week.toString()));
						jQuery(c).click(jQuery.proxy(function(s){
							jQuery(this).trigger(this.WEEK_EVENT, [s]);
						}, this, this.currentYear+'-'+week));
					}else c.appendChild(document.createTextNode('\u00a0'));
					r.appendChild(c);
				}
				c = document.createElement('td');
				if(m) {
					if((this.rangeStart && this.compareDates(d,this.rangeStart)==1) || (this.rangeEnd && this.compareDates(d,this.rangeEnd)==-1)) c.className = 'DS_date_disabled';
					else{
						if(this.isSelectedDate(d)) this.selectedCell = c;
						this.setClassName(c, d);
						jQuery(c).click(jQuery.proxy(this.setSelected, this, c, new Date(d)));
					}	
					c.appendChild(document.createTextNode(d.getDate()));
				}else c.appendChild(document.createTextNode('\u00a0'));//&nbsp;

				d.setDate(d.getDate() + 1);
				r.appendChild(c);
			}
			tbd.appendChild(r);
			if(d.getMonth() != this.currentMonth) break;
		}
		jQuery(this.calendar).empty().append(header).append(tbl);
	}
}
function DateField(inputObj){
	if(inputObj == undefined) return;
	DateSelector.call(this, document.body);
	this.inputObj = inputObj;
	this.calendar.style.display = 'none';
	jQuery(this).on(this.DATE_EVENT, jQuery.proxy(this._onSelectDate, this));
	jQuery(document).mouseup(jQuery.proxy(this.onMouseUp, this));
}
DateField.prototype = new DateSelector();
jQuery.extend(DateField.prototype,{
	toggle : function(){
		if(this.calendar.style.display == 'block') this.hideCalendar();
		else this.showCalendar();
	},
	positionCalendar : function(){
		var pos = jQuery(this.inputObj).offset();
		this.calendar.style.top = (pos.top + this.inputObj.offsetHeight - 1) + 'px';
		this.calendar.style.left = (pos.left + (this.inputObj.offsetWidth - this.calendar.offsetWidth)) + 'px';
	},
	showCalendar : function(){
		this.calendar.style.display = 'block';
		this.positionCalendar();//in case window is resized
	},
	hideCalendar : function(){
		this.calendar.style.display = 'none';
	},	
	onMouseUp : function(event){
		if(event){
			var t = event.target || event.srcElement;
			while(t){
				if(t === this.calendar) return;
				if(t === this.inputObj){
					this.toggle();
					return;
				}
				t = t.parentNode;
			}
			this.hideCalendar();
		}
	},
	_onSelectDate : function(evt, date){
		this.inputObj.value = date?date:'';
		this.hideCalendar();
	}
});
