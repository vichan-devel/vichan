/*
* settings.js
* https://github.com/savetheinternet/Tinyboard/blob/master/js/settings.js
*
* Optional settings. Used to customize some scripts without needing to tweak their code.
* Notes:
*   - You must include this script first.
*   - This file is just an example.
*   - You should copy settings.js to something like instance.settings.js to prevent conflicts when upgrading.
*   - This file should always be optional.
*
* Released under the MIT license
* Copyright (c) 2013 Michael Save <savetheinternet@tinyboard.org>
* Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
*
* Usage:
*   $config['additional_javascript'][] = 'js/jquery.min.js';
*   $config['additional_javascript'][] = 'js/instance.settings.js';
*   // $config['additional_javascript'][] = 'js/quick-reply.js';
*
* Usage in scripts:
*   var settings = new script_settings('my-script');
*   var some_value = settings.get('option', 'default value');
*
*/

var tb_settings = {};

// quick-reply.js
tb_settings['quick-reply'] = {
	// Hide form when scrolled to top of page (where original form is visible)
	hide_at_top: true,
	// "Quick reply" button floating at the top right hand corner of the page at all times
	floating_link: false,
	// Show remote in quick reply
	show_remote: false,
	// Show embedding in quick reply
	show_embed: false
};

// ajax.js
tb_settings['ajax'] = {
	// Always act as if "noko" was typed when posting replies with the ajax script
	always_noko_replies: false	
};

// wpaint.js
tb_settings['wpaint'] = {
	// Canvas width
	width: 500,
	// Canvas height
	height: 250
};

// auto-reload.js
tb_settings['auto-reload'] = {
	// Minimum delay before reloading the page when at the bottom
	min_delay_bottom: 5000,
	// Maximum delay before reloading the page
	max_delay: 600000,
	// Delay if there was an error
	error_delay: 30000,
	// Reset the delay to the minimum upon focussing the window.
	reset_focus: true
};
