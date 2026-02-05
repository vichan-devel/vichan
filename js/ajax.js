/*
 * ajax.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/ajax.js
 *
 * Released under the MIT license
 * Copyright (c) 2013 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Modernized to ES6 with Fetch API - No jQuery required
 */

onReady(() => {
	const settings = new script_settings('ajax');
	let do_not_ajax = false;

	// Enable submit button if disabled (cache problem)
	document.querySelectorAll('input[type="submit"]').forEach(btn => {
		btn.removeAttribute('disabled');
	});

	const postWithProgress = async (url, formData, form) => {
		return new Promise((resolve, reject) => {
			const xhr = new XMLHttpRequest();
			
			xhr.addEventListener('loadstart', () => {
				form.querySelector('input[type="submit"]')?.setAttribute('disabled', 'disabled');
			});

			xhr.upload.addEventListener('progress', (e) => {
				if (e.lengthComputable) {
					const percentage = Math.round(e.loaded * 100 / e.total);
					const submitBtn = form.querySelector('input[type="submit"]');
					if (submitBtn) {
						const originalText = submitBtn.dataset.originalText || submitBtn.value;
						submitBtn.dataset.originalText = originalText;
						submitBtn.value = _('Posting... (#%)').replace('#', percentage);
					}
				}
			});

			xhr.addEventListener('load', () => {
				try {
					const response = JSON.parse(xhr.responseText);
					resolve(response);
				} catch (e) {
					reject(new Error('Invalid JSON response'));
				}
			});

			xhr.addEventListener('error', () => {
				reject(new Error('Network error'));
			});

			xhr.addEventListener('abort', () => {
				reject(new Error('Request aborted'));
			});

			xhr.open('POST', url);
			xhr.setRequestHeader('Accept', 'application/json');
			xhr.send(formData);
		});
	};

	const setup_form = (form) => {
		form.addEventListener('submit', async (e) => {
			if (do_not_ajax) return true;

			e.preventDefault();

			const submit_txt = form.querySelector('input[type="submit"]')?.value || 'Post';
			if (!window.FormData) return true;

			const formData = new FormData(form);
			formData.append('json_response', '1');
			formData.append('post', submit_txt);

			// Trigger before post event
			document.dispatchEvent(new CustomEvent('ajax_before_post', { detail: formData }));

			try {
				const response = await postWithProgress(form.action, formData, form);

				if (response.error) {
					if (response.banned) {
						// You are banned - post normally so user sees ban message
						do_not_ajax = true;
						form.querySelectorAll('input[type="submit"]').forEach((submitBtn) => {
							const hidden = document.createElement('input');
							hidden.type = 'hidden';
							hidden.name = submitBtn.name;
							hidden.value = submit_txt;
							
							const button = document.createElement('input');
							button.type = 'button';
							button.value = submit_txt;
							
							submitBtn.parentNode.insertBefore(hidden, submitBtn);
							submitBtn.replaceWith(button);
						});
						form.submit();
					} else {
						alert(response.error);
						form.querySelectorAll('input[type="submit"]').forEach(btn => {
							btn.value = submit_txt;
							btn.removeAttribute('disabled');
						});
					}
				} else if (response.redirect && response.id) {
					const hasThread = form.querySelector('input[name="thread"]');
					const shouldNoko = settings.get('always_noko_replies', true) || response.noko;

					if (!hasThread || !shouldNoko) {
						document.location = response.redirect;
					} else {
						// Fetch updated page and insert new post
						const pageResponse = await fetch(document.location);
						const html = await pageResponse.text();
						const parser = new DOMParser();
						const doc = parser.parseFromString(html, 'text/html');

						doc.querySelectorAll('div.post.reply').forEach((postEl) => {
							const id = postEl.id;
							if (id && !document.getElementById(id)) {
								const lastPost = document.querySelector('div.post:last-of-type');
								const insertPoint = lastPost?.nextElementSibling || lastPost;
								if (insertPoint) {
									insertPoint.parentNode.insertBefore(postEl, insertPoint);
									const br = document.createElement('br');
									br.className = 'clear';
									insertPoint.parentNode.insertBefore(br, insertPoint);
								}

								// Trigger new post event
								document.dispatchEvent(new CustomEvent('new_post', { detail: postEl }));

								// Retrigger scroll for watch.js & auto-reload.js
								setTimeout(() => window.dispatchEvent(new Event('scroll')), 100);
							}
						});

						if (typeof highlightReply === 'function') {
							highlightReply(response.id);
						}
						window.location.hash = response.id;
						const replyEl = document.getElementById('reply_' + response.id);
						if (replyEl) {
							window.scrollTo(0, replyEl.getBoundingClientRect().top + window.scrollY);
						}

						form.querySelectorAll('input[type="submit"]').forEach(btn => {
							btn.value = submit_txt;
							btn.removeAttribute('disabled');
						});

						// Clear form fields
						form.querySelectorAll('input[name="subject"], input[name="file_url"], textarea[name="body"], input[type="file"]').forEach(field => {
							field.value = '';
							field.dispatchEvent(new Event('change'));
						});
					}

					form.querySelector('input[type="submit"]').value = _('Posted...');
					document.dispatchEvent(new CustomEvent('ajax_after_post', { detail: response }));
				} else {
					alert(_('An unknown error occured when posting!'));
					form.querySelectorAll('input[type="submit"]').forEach(btn => {
						btn.value = submit_txt;
						btn.removeAttribute('disabled');
					});
				}
			} catch (error) {
				console.error('AJAX Error:', error);
				alert(_('The server took too long to submit your post. Your post was probably still submitted. If it wasn\'t, we might be experiencing issues right now -- please try your post again later. Error: ') + error.message);
				form.querySelectorAll('input[type="submit"]').forEach(btn => {
					btn.value = submit_txt;
					btn.removeAttribute('disabled');
				});
			}
		});
	};

	// Setup main post form
	const postForm = document.querySelector('form[name="post"]');
	if (postForm) {
		setup_form(postForm);
	}

	// Setup quick reply form when it's created
	document.addEventListener('quick-reply', () => {
		const quickReplyForm = document.getElementById('quick-reply');
		if (quickReplyForm) {
			// Remove old listeners
			const newForm = quickReplyForm.cloneNode(true);
			quickReplyForm.replaceWith(newForm);
			setup_form(newForm);
		}
	});
});

