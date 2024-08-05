/*
 * inline-expanding-filename.js
 * Binds image filename link to expanding, to make kusaba x users somewhat more accustomed.
 * https://github.com/vichan-devel/Tinyboard/blob/master/js/inline-expanding.js
 *
 * Released under the MIT license
 * Copyright (c) 2012-2013 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/inline-expanding.js';
 *   $config['additional_javascript'][] = 'js/inline-expanding-filename.js';
 *
 */

onReady(function() {
	let inlineExpandingFilename = function() {
		$(this).find(".fileinfo > a").click(function() {
			let imagelink = $(this).parent().parent().find('a[target="_blank"]:first');
			if (imagelink.length > 0) {
				imagelink.click();
				return false;
			}
		});
	};

	$('div[id^="thread_"]').each(inlineExpandingFilename);

	// allow to work with auto-reload.js, etc.
	$(document).on('new_post', function(e, post) {
		inlineExpandingFilename.call(post);
	});
});
