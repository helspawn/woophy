function SelectionFormat(field){
	this.field = field;
}
SelectionFormat.prototype = {
	addTags : function(starttag, endtag){
		var idx = starttag.indexOf('=');
		if (document.selection) {//IE
			var str = document.selection.createRange().text;
			this.field.focus();
			sel = document.selection.createRange();
			sel.text = starttag + str + endtag;
			var l =0;
			if(idx > -1) l = -(endtag.length + str.length + 1);
			else if(str.length==0) l = -endtag.length;
			sel.moveStart('character', l);
			sel.moveEnd('character', l);
			sel.select();
		}else if(this.field.selectionStart || this.field.selectionStart == '0') {//moz
			var startPos = this.field.selectionStart;
			var endPos = this.field.selectionEnd;
			var str = this.field.value.substring(startPos, endPos);
			this.field.value = this.field.value.substring(0, startPos) + starttag + str + endtag + this.field.value.substring(endPos, this.field.value.length);
			var l = (idx > -1) ? starttag.length-1 : (str.length == 0) ? starttag.length : str.length+starttag.length+endtag.length;
			this.field.selectionStart = startPos + l;
			this.field.selectionEnd = startPos + l;
			this.field.focus();
			this.field.scrollTop=this.field.scrollHeight;
		}else this.field.value += starttag + endtag;
	}
}