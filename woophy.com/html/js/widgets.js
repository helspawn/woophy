var Widgets = {
	
	init_slides: function(){
		jQuery("#Slides").slides({
			container: 'SlidesContainer',
			preload: false,
			play: 10000,
			pause: 2500,
			effect: 'slide',
			randomize: true,
			fadeSpeed: 300,
			hoverPause: true,
			prev: 'slide_prev',
			next: 'slide_next'
		});
		// We are manually generating a pagination so we need to automatically-generated one to go away.
		jQuery('#Slides .pagination').last().remove();
		jQuery('#Slides .Slide').removeClass('js_hidden');
	},
	
	init_feeds: function(){
		jQuery("#Feeds").slides({
			container: 'FeedsContainer',
			preload: false,
			play: 10000,
			pause: 2500,
			effect: 'slide',
			randomize: true,
			fadeSpeed: 300,
			hoverPause: true,
			prev: 'feed_prev',
			next: 'feed_next',
			autoHeight: 'true'
		});
		// We are manually generating a pagination so we need to automatically-generated one to go away.
		jQuery('#Feeds .pagination').last().remove();		
		jQuery('#Feeds .Feed').removeClass('js_hidden');
	},
	
	init_notifications: function(){
		jQuery("#Notifications").slides({
			container: 'FeedsContainer',
			preload: false,
			play: 10000,
			play:0,
			pause: 2500,
			effect: 'slide',
			fadeSpeed: 300,
			hoverPause: true,
			prev: 'feed_prev',
			next: 'feed_next',
			autoHeight: 'true'
		});
		// We are manually generating a pagination so we need to automatically-generated one to go away.

		jQuery('#Notifications .pagination').last().remove();		
		jQuery('#Notifications .Feed').removeClass('js_hidden');
	}
};

init_global_pre.add(Widgets.init_slides);
init_global_pre.add(Widgets.init_feeds);
init_global_pre.add(Widgets.init_notifications);
