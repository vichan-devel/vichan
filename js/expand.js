/*
 * expand.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/expand.js
 *
 * Released under the MIT license
 * Copyright (c) 2012-2013 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013 Czterooki <czterooki1337@gmail.com>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/expand.js';
 *
 */

onReady(() => {
	if(document.querySelectorAll('span.omitted').length === 0)
		return; // nothing to expand

	const do_expand = function() {
		const omittedSpan = this;
		const clickToExpand = omittedSpan.textContent.replace(_("Click reply to view."), '<a href="javascript:void(0)">'+_("Click to expand")+'</a>.');
		omittedSpan.innerHTML = clickToExpand;
		
		const link = omittedSpan.querySelector('a');
		if (link) {
			link.addEventListener('click', window.expand_fun = async (e) => {
				e.preventDefault();
				const thread = link.closest('[id^="thread_"]');
				if (!thread) return;
				
				const id = thread.id.replace(/^thread_/, '');
				const introLink = thread.querySelector('p.intro a.post_no:first');
				if (!introLink) return;
				
				try {
					const response = await fetch(introLink.href);
					const data = await response.text();
					
					const parser = new DOMParser();
					const htmlDoc = parser.parseFromString(data, 'text/html');
					
					let last_expanded = false;
					const newPosts = htmlDoc.querySelectorAll('div.post.reply');
					
					newPosts.forEach((post) => {
						thread.querySelectorAll('div.hidden').forEach(el => el.remove());
						const post_in_doc = thread.querySelector('#' + post.id);
						if(!post_in_doc) {
							const clonedPost = post.cloneNode(true);
							clonedPost.classList.add('expanded');
							
							if(last_expanded) {
								last_expanded.insertAdjacentElement('afterend', clonedPost);
								const br = document.createElement('br');
								br.className = 'expanded';
								clonedPost.insertAdjacentElement('beforebegin', br);
							} else {
								const firstPost = thread.querySelector('div.post:first-of-type');
								if (firstPost) {
									firstPost.insertAdjacentElement('afterend', clonedPost);
									const br = document.createElement('br');
									br.className = 'expanded';
									clonedPost.insertAdjacentElement('afterend', br);
								}
							}
							last_expanded = clonedPost;
							document.dispatchEvent(new CustomEvent('new_post', { detail: clonedPost }));
						} else {
							last_expanded = post_in_doc;
						}
					});

					thread.querySelectorAll("span.omitted").forEach(el => {
						el.style.display = 'none';
					});

					const hideExpandedDiv = document.createElement('span');
					hideExpandedDiv.className = 'omitted hide-expanded';
					const hideLink = document.createElement('a');
					hideLink.href = 'javascript:void(0)';
					hideLink.textContent = _('Hide expanded replies');
					hideExpandedDiv.appendChild(hideLink);
					
					const lastOmittedOrBody = thread.querySelector('.op div.body, .op span.omitted');
					if (lastOmittedOrBody) {
						lastOmittedOrBody.insertAdjacentElement('afterend', hideExpandedDiv);
					}
					
					hideLink.addEventListener('click', () => {
						thread.querySelectorAll('.expanded').forEach(el => el.remove());
						thread.querySelectorAll(".omitted:not(.hide-expanded)").forEach(el => {
							el.style.display = '';
						});
						thread.querySelectorAll(".hide-expanded").forEach(el => el.remove());
					});
				} catch (error) {
					console.error('Error expanding posts:', error);
				}
			});
		}
	}

	document.querySelectorAll('div.post.op span.omitted').forEach(do_expand);

	document.addEventListener("new_post", (e) => {
		const post = e.detail;
		if (post && !post.classList.contains("reply")) {
			post.querySelectorAll('div.post.op span.omitted').forEach(do_expand);
		}
	});
});
