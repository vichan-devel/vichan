/*
 * show-op
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/show-op.js
 *
 * Adds "(OP)" to >>X links when the OP is quoted.
 *
 * Released under the MIT license
 * Copyright (c) 2012 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/show-op.js';
 *
 */

onReady(() => {
	const showOPLinks = () => {
		const OP;
		
		if (document.querySelector('div.banner').length === 0) {
			OP = parseInt($(this).parent().querySelector('div.post.op a.post_no:eq(1)').textContent);
		} else {
			OP = parseInt(document.querySelector('div.post.op a.post_no:eq(1)').textContent);
		}
		
		$(this).find('div.body a:not([rel="nofollow"])').each(() => {
			const postID;
			
			if(postID = $(this).textContent.match(/^>>(\d+)$/))
				postID = postID[1];
			else
				return;
			
			if (postID === OP) {
				$(this).after(' <small>(OP)</small>');
			}
		});
	};
	
	document.querySelector('div.post.reply').each(showOPLinks);
	
	// allow to work with auto-reload.js, etc.
	$(document).addEventListener('new_post', function(e, post) {
		if ($(post).is('div.post.reply')) {
			$(post).each(showOPLinks);
		}
		else {
			$(post).querySelector('div.post.reply').each(showOPLinks);
		}
	});
});


