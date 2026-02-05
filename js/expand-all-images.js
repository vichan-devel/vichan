/*
 * expand-all-images.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/expand-all-images.js
 *
 * Adds an "Expand all images" button to the top of the page.
 *
 * Released under the MIT license
 * Copyright (c) 2012-2013 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
 * Copyright (c) 2014 sinuca <#55ch@rizon.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/inline-expanding.js';
 *   $config['additional_javascript'][] = 'js/expand-all-images.js';
 *
 */

if (active_page === 'ukko' || active_page === 'thread' || active_page === 'index') {
	onReady(() => {
		const firstHr = document.querySelector('hr');
		const expandDiv = document.createElement('div');
		expandDiv.id = 'expand-all-images';
		expandDiv.style.textAlign = 'right';
		const expandLink = document.createElement('a');
		expandLink.className = 'unimportant';
		expandLink.href = 'javascript:void(0)';
		expandLink.textContent = _('Expand all images');
		expandDiv.appendChild(expandLink);
		if (firstHr) {
			firstHr.parentNode.insertBefore(expandDiv, firstHr);
		}

		expandLink.addEventListener('click', () => {
			document.querySelectorAll('a img.post-image').forEach((img) => {
				// Don't expand YouTube embeds
				if (img.parentElement?.parentElement?.classList.contains('video-container')) {
					return;
				}

				// or WEBM
				if (/^\/player\.php\?/.test(img.parentElement?.getAttribute('href'))) {
					return;
				}

				if (!img.parentElement?.dataset.expanded) {
					img.parentElement?.click();
				}
			});

			if (!document.getElementById('shrink-all-images')) {
				const shrinkDiv = document.createElement('div');
				shrinkDiv.id = 'shrink-all-images';
				shrinkDiv.style.textAlign = 'right';
				const shrinkLink = document.createElement('a');
				shrinkLink.className = 'unimportant';
				shrinkLink.href = 'javascript:void(0)';
				shrinkLink.textContent = _('Shrink all images');
				shrinkDiv.appendChild(shrinkLink);
				if (firstHr) {
					firstHr.parentNode.insertBefore(shrinkDiv, firstHr);
				}

				shrinkLink.addEventListener('click', () => {
					document.querySelectorAll('a img.full-image').forEach((img) => {
						if (img.parentElement?.dataset.expanded) {
							img.parentElement?.click();
						}
					});
					shrinkDiv.remove();
				});
			}
		});
	});
}