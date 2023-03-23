/*
 * smartphone-spoiler.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/smartphone-spoiler.js
 *
 * Released under the MIT license
 * Copyright (c) 2012 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net> 
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/mobile-style.js';
 *   $config['additional_javascript'][] = 'js/smartphone-spoiler.js';
 *
 */

onready(function(){
	if(device_type == 'mobile') {
		var fix_spoilers = function(where) {
			var spoilers = where.getElementsByClassName('spoiler');
			for(var i = 0; i < spoilers.length; i++) {
				spoilers[i].onmousedown = function() {
					this.style.color = 'white';
				};
			}
		};
		fix_spoilers(document);

	        // allow to work with auto-reload.js, etc.
	        $(document).on('new_post', function(e, post) {
	                fix_spoilers(post);
        	});             
	
	}
});

