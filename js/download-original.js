/*
 * download-original.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/download-original.js
 *
 * Makes image filenames clickable, allowing users to download and save files as their original filename.
 * Only works in newer browsers. http://caniuse.com/#feat=download
 *
 * Released under the MIT license
 * Copyright (c) 2012-2013 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/download-original.js';
 *
 */

onReady(() => {
	const doOriginalFilename = function(el) {
		let filename, truncated;
		if (el.getAttribute('title')) {
			filename = el.getAttribute('title');
			truncated = true;
		} else {
			filename = el.textContent;
		}

		const a = document.createElement('a');
		a.setAttribute('download', filename);
		a.href = el.parentElement?.parentElement?.querySelector('a')?.href || '';
		a.textContent = el.textContent;
		a.title = _('Save as original filename') + (truncated ? ' (' + filename + ')' : '');
		
		el.replaceWith(a);
	};

	document.querySelectorAll('.postfilename').forEach(doOriginalFilename);

	document.addEventListener('new_post', (e) => {
		const post = e.detail;
		if (post) {
			post.querySelectorAll('.postfilename').forEach(doOriginalFilename);
		}
	});
});
