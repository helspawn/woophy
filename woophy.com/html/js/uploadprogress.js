function UploadProgress(param){
	this.key = param.key;
	this.uploaded = false;
}
UploadProgress.prototype = {
	startProgress : function(){
		this.setStatus('Uploading...');
		jQuery('#ProgressOuter').removeClass('nodisplay');
		jQuery('#upload_status').addClass('small');
		this.uploaded=false;
		setTimeout(jQuery.proxy(this._getStatus, this), 1000);
	},
	clearStatus : function(){
		jQuery('#ProgressInner').width(0);
		this.setStatus('');
	},
	_getStatus : function(){
		jQuery.get(Page.root_url+'services?method=woophy.uploadprogress.getStatus&key='+this.key, jQuery.proxy(this.onGetStatus, this));
	},
	onUploadComplete : function(success, msg, url, id){
		this.setStatus(msg);
		jQuery('#ProgressInner').width('100%').addClass(success ? '':'error');
		if(!success)jQuery('#upload_status').addClass('error');
		this.uploaded = true;
		jQuery(this).trigger('uploadComplete', [success, msg, url, id]);
	},
	setStatus : function(s){
		jQuery('#upload_status').text(s);
	},
	onGetStatus : function(xml, success){
		if(!this.uploaded){
			var status = xml.getElementsByTagName('status');
			if(status.length){
				var c = parseInt(status[0].getAttribute('bytes_uploaded'));
				var t = parseInt(status[0].getAttribute('bytes_total'));

				if(!isNaN(c) && !isNaN(t) && t>0){
					var e = jQuery('#ProgressInner'),o= jQuery('#ProgressOuter'),w=o.width()-2,d=19;
					e.css('border-color', '#003900').width((Math.round((w*c/t)/d)*d));
					this.setStatus(this.mega(c)+'MB / '+this.mega(t)+'MB ('+Math.round(c*100/t)+'%'+')');
				}
				var d = status[0].getAttribute('files_uploaded');
				if(d=='1'){
					this.uploaded = true;
					return;
				}
			}

			setTimeout(jQuery.proxy(this._getStatus, this), 500);
			jQuery(this).trigger('getStatus', [xml]);
		}
	},
	mega : function(n){	
		return Math.round((parseFloat(n/(1024*1024)))*10)/10;
	}
};