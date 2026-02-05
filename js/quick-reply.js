/*
 * quick-reply.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/quick-reply.js
 *
 * Released under the MIT license
 * Copyright (c) 2013 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/jquery-ui.custom.min.js'; // Optional; if you want the form to be draggable.
 *   $config['additional_javascript'][] = 'js/quick-reply.js';
 *
 */

(() => {
	const settings = new script_settings('quick-reply');
	
	const do_css = () => {
		document.getElementById('quick-reply-css').remove();
		
		// Find background of reply posts
		const dummy_reply = $('<div class="post reply"></div>').appendTo(document.querySelector('body'));
		const reply_background = dummy_reply.style.backgroundColor;
		const reply_border_style = dummy_reply.style.borderStyle;
		const reply_border_color = dummy_reply.style.borderColor;
		const reply_border_width = dummy_reply.style.borderWidth;
		dummy_reply.remove();
		
		$('<style type="text/css" id="quick-reply-css">\
		#quick-reply {\
			position: fixed;\
			right: 5%;\
			top: 5%;\
			float: right;\
			display: block;\
			padding: 0 0 0 0;\
			width: 300px;\
			z-index: 100;\
		}\
		#quick-reply table {\
			border-collapse: collapse;\
			background: ' + reply_background + ';\
			border-style: ' + reply_border_style + ';\
			border-width: ' + reply_border_width + ';\
			border-color: ' + reply_border_color + ';\
			margin: 0;\
			width: 100%;\
		}\
		#quick-reply tr td:nth-child(2) {\
			white-space: nowrap;\
			text-align: right;\
			padding-right: 4px;\
		}\
		#quick-reply tr td:nth-child(2) input[type="submit"] {\
			width: 100%;\
		}\
		#quick-reply th, #quick-reply td {\
			margin: 0;\
			padding: 0;\
		}\
		#quick-reply th {\
			text-align: center;\
			padding: 2px 0;\
			border: 1px solid #222;\
		}\
		#quick-reply th .handle {\
			float: left;\
			width: 100%;\
			display: inline-block;\
		}\
		#quick-reply th .close-btn {\
			float: right;\
			padding: 0 5px;\
		}\
		#quick-reply input[type="text"], #quick-reply select {\
			width: 100%;\
			padding: 2px;\
			font-size: 10pt;\
			box-sizing: border-box;\
			-webkit-box-sizing:border-box;\
			-moz-box-sizing: border-box;\
		}\
		#quick-reply textarea {\
			width: 100%;\
			min-width: 100%;\
			box-sizing: border-box;\
			-webkit-box-sizing:border-box;\
			-moz-box-sizing: border-box;\
			font-size: 10pt;\
			resize: vertical horizontal;\
		}\
		#quick-reply input, #quick-reply select, #quick-reply textarea {\
			margin: 0 0 1px 0;\
		}\
		#quick-reply input[type="file"] {\
			padding: 5px 2px;\
		}\
		#quick-reply .nonsense {\
			display: none;\
		}\
		#quick-reply td.submit {\
			width: 1%;\
		}\
		#quick-reply td.recaptcha {\
			text-align: center;\
			padding: 0 0 1px 0;\
		}\
		#quick-reply td.recaptcha span {\
			display: inline-block;\
			width: 100%;\
			background: white;\
			border: 1px solid #ccc;\
			cursor: pointer;\
		}\
		#quick-reply td.recaptcha-response {\
			padding: 0 0 1px 0;\
		}\
		@media screen and (max-width: 400px) {\
			#quick-reply {\
				display: none !important;\
			}\
		}\
		</style>').appendTo(document.querySelector('head'));
	};
	
	const show_quick_reply = () => {
		if(document.querySelector('div.banner').length === 0)
			return;
		if(document.getElementById('quick-reply').length != 0)
			return;
		
		do_css();
		
		const $postForm = $('form[name="post"]').clone();
		
		$postForm.clone();
		
		$dummyStuff = $('<div class="nonsense"></div>').appendTo($postForm);
		
		$postForm.querySelector('table tr').each(() => {
			const $th = $(this).children('th:first');
			const $td = $(this).children('td:first');		
			if ($th.length && $td.length) {
				$td.setAttribute('colspan', 2);
	
				if ($td.find('input[type="text"]').length) {
					// Replace <th> with input placeholders
					$td.find('input[type="text"]')
						.removeAttribute('size')
						.setAttribute('placeholder', $th.clone().children().remove().end().textContent);
				}
	
				// Move anti-spam nonsense and remove <th>
				$th.contents().filter(() => {
					return this.nodeType === 3; // Node.TEXT_NODE
				}).remove();
				$th.contents().appendTo($dummyStuff);
				$th.remove();
	
				if ($td.find('input[name="password"]').length) {
					// Hide password field
					$(this).style.display = 'none';
				}
	
				// Fix submit button
				if ($td.find('input[type="submit"]').length) {
					$td.removeAttribute('colspan');
					$('<td class="submit"></td>').appendChild($td.find('input[type="submit"]')).insertAfter($td);
				}
	
				// reCAPTCHA
				if ($td.querySelector('#recaptcha_widget_div').length) {
					// Just show the image, and have it interact with the real form.
					const $captchaimg = $td.querySelector('#recaptcha_image img');
					
					$captchaimg
						.removeAttribute('id')
						.removeAttribute('style')
						.classList.add('recaptcha_image')
						.click(() => {
							document.getElementById('recaptcha_reload').click();
						});
					
					// When we get a new captcha...
					document.getElementById('recaptcha_response_field').focus(() => {
						if ($captchaimg.getAttribute('src') != document.querySelector('#recaptcha_image img').getAttribute('src')) {
							$captchaimg.setAttribute('src', document.querySelector('#recaptcha_image img').getAttribute('src'));
							$postForm.find('input[name="recaptcha_challenge_field"]').val(document.getElementById('recaptcha_challenge_field').value);
							$postForm.find('input[name="recaptcha_response_field"]').val('').focus();
						}
					});
					
					$postForm.submit(() => {
						setTimeout(() => {
							document.getElementById('recaptcha_reload').click();
						}, 200);
					});
					
					// Make a new row for the response text
					const $newRow = $('<tr><td class="recaptcha-response" colspan="2"></td></tr>');
					$newRow.children().first().appendChild(
						$td.querySelector('input').removeAttribute('style')
					);
					$newRow.querySelector('#recaptcha_response_field')
						.removeAttribute('id')
						.classList.add('recaptcha_response_field')
						.setAttribute('placeholder', document.getElementById('recaptcha_response_field').getAttribute('placeholder'));
					
					document.getElementById('recaptcha_response_field').classList.add('recaptcha_response_field')
					
					$td.replaceWith($('<td class="recaptcha" colspan="2"></td>').appendChild(document.querySelector('<span></span>').appendChild($captchaimg)));
					
					$newRow.insertAfter(this);
				}
	
				// Upload section
				if ($td.find('input[type="file"]').length) {
					if ($td.find('input[name="file_url"]').length) {
						$file_url = $td.find('input[name="file_url"]');
						
						if (settings.get('show_remote', false)) {
							// Make a new row for it
							const $newRow = $('<tr><td colspan="2"></td></tr>');
						
							$file_url.clone().setAttribute('placeholder', _('Upload URL')).appendTo($newRow.querySelector('td'));
						
							$newRow.insertBefore(this);
						}
						$file_url.parent().remove();

						
						$td.querySelector('label').remove();
						$td.contents().filter(() => {
							return this.nodeType === 3; // Node.TEXT_NODE
						}).remove();
						$td.find('input[name="file_url"]').removeAttribute('id');
					}
					
					if ($(this).find('input[name="spoiler"]').length) {
						$td.removeAttribute('colspan');
					}
				}

				// Disable embedding if configured so
				if (!settings.get('show_embed', false) && $td.find('input[name="embed"]').length) {
					$(this).remove();
				}

				// Remove oekaki if existent
				if ($(this).is('#oekaki')) {
					$(this).remove();
				}

				// Remove upload selection
				if ($td.is('#upload_selection')) {
					$(this).remove();
				}
				
				// Remove mod controls, because it looks shit.
				if ($td.find('input[type="checkbox"]').length) {
					const tr = this;
					$td.find('input[type="checkbox"]').each(() => {
						if ($(this).getAttribute('name') === 'spoiler') {
							$td.querySelector('label').remove();
							$(this).setAttribute('id', 'q-spoiler-image');
							$postForm.find('input[type="file"]').parent()
								.removeAttribute('colspan')
								.after($('<td class="spoiler"></td>').appendChild(this, ' ', $('<label for="q-spoiler-image">').text(_('Spoiler Image'))));
						} else if ($(this).getAttribute('name') === 'no_country') {
							$td.find('label,input[type="checkbox"]').remove();
						} else {
							$(tr).remove();
						}
					});
				}
				
				$td.querySelector('small').style.display = 'none';
			}
		});
		
		$postForm.find('textarea[name="body"]').removeAttribute('id').removeAttribute('cols').setAttribute('placeholder', _('Comment'));
	
		$postForm.find('textarea:not([name="body"]),input[type="hidden"]:not(.captcha_cookie)').removeAttribute('id').appendTo($dummyStuff);
	
		$postForm.querySelector('br').remove();
		$postForm.querySelector('table').insertBefore('<tr><th colspan="2">\
			<span class="handle">\
				<a class="close-btn" href="javascript:void(0)">×</a>\
				' + _('Quick Reply') + '\
			</span>\
			</th></tr>');
		
		$postForm.setAttribute('id', 'quick-reply');
		
		$postForm.appendTo(document.querySelector('body')).style.display = 'none';
		$origPostForm = $('form[name="post"]:first');
		
		// Synchronise body text with original post form
		$origPostForm.find('textarea[name="body"]').on('change input propertychange', () => {
			$postForm.find('textarea[name="body"]').val($(this).value);
		});
		$postForm.find('textarea[name="body"]').on('change input propertychange', () => {
			$origPostForm.find('textarea[name="body"]').val($(this).value);
		});
		$postForm.find('textarea[name="body"]').focus(() => {
			$origPostForm.find('textarea[name="body"]').removeAttribute('id');
			$(this).setAttribute('id', 'body');
		});
		$origPostForm.find('textarea[name="body"]').focus(() => {
			$postForm.find('textarea[name="body"]').removeAttribute('id');
			$(this).setAttribute('id', 'body');
		});
		// Synchronise other inputs
		$origPostForm.find('input[type="text"],select').on('change input propertychange', () => {
			$postForm.find('[name="' + $(this).getAttribute('name') + '"]').val($(this).value);
		});
		$postForm.find('input[type="text"],select').on('change input propertychange', () => {
			$origPostForm.find('[name="' + $(this).getAttribute('name') + '"]').val($(this).value);
		});

		if (typeof $postForm.draggable != 'undefined') {	
			if (localStorage.quickReplyPosition) {
				const offset = JSON.parse(localStorage.quickReplyPosition);
				if (offset.top < 0)
					offset.top = 0;
				if (offset.right > $(window).width() - $postForm.width())
					offset.right = $(window).width() - $postForm.width();
				if (offset.top > $(window).height() - $postForm.height())
					offset.top = $(window).height() - $postForm.height();
				$postForm.css('right', offset.right).css('top', offset.top);
			}
			$postForm.draggable({
				handle: 'th .handle',
				containment: 'window',
				distance: 10,
				scroll: false,
				stop: () => {
					const offset = {
						top: $(this).offset().top - $(window).scrollTop(),
						right: $(window).width() - $(this).offset().left - $(this).width(),
					};
					localStorage.quickReplyPosition = JSON.stringify(offset);
					
					$postForm.css('right', offset.right).css('top', offset.top).css('left', 'auto');
				}
			});
			$postForm.querySelector('th .handle').css('cursor', 'move');
		}
		
		$postForm.querySelector('th .close-btn').click(() => {
			$origPostForm.find('textarea[name="body"]').setAttribute('id', 'body');
			$postForm.remove();
			floating_link();
		});
		
		// Fix bug when table gets too big for form. Shouldn't exist, but crappy CSS etc.
		$postForm.style.display = '';
		$postForm.width($postForm.querySelector('table').width());
		$postForm.style.display = 'none';
		
		$(window).trigger('quick-reply');
	
		onReady(() => {
			if (settings.get('hide_at_top', true)) {
				$(window).scroll(() => {
					if ($(this).width() <= 400)
						return;
					if ($(this).scrollTop() < $origPostForm.offset().top + $origPostForm.height() - 100)
						$postForm.fadeOut(100);
					else
						$postForm.fadeIn(100);
				}).scroll();
			} else {
				$postForm.style.display = '';
			}
			
			$(window).on('stylesheet', () => {
				do_css();
				if (document.querySelector('link#stylesheet').getAttribute('href')) {
					document.querySelector('link#stylesheet')[0].onload = do_css;
				}
			});
		});
	};
	
	$(window).addEventListener('cite', function(e, id, with_link) {
		if ($(this).width() <= 400)
			return;
		show_quick_reply();
		if (with_link) {
			onReady(() => {
				if ($('#' + id).length) {
					highlightReply(id);
					$(document).scrollTop($('#' + id).offset().top);
				}
				
				// Honestly, I'm not sure why we need setTimeout() here, but it seems to work.
				// Same for the "tmp" variable stuff you see inside here:
				setTimeout(() => {
					const tmp = $('#quick-reply textarea[name="body"]').value;
					$('#quick-reply textarea[name="body"]').val('').focus().val(tmp);
				}, 1);
			});
		}
	});
	
	const floating_link = () => {
		if (!settings.get('floating_link', false))
			return;
		$('<a href="javascript:void(0)" class="quick-reply-btn">'+_('Quick Reply')+'</a>')
			.click(() => {
				show_quick_reply();
				$(this).remove();
			}).appendTo(document.querySelector('body'));
		
		$(window).on('quick-reply', () => {
			document.querySelector('.quick-reply-btn').remove();
		});
	};
	
	if (settings.get('floating_link', false)) {
		onReady(() => {
			if(document.querySelector('div.banner').length === 0)
				return;
			$('<style type="text/css">\
			a.quick-reply-btn {\
				position: fixed;\
				right: 0;\
				bottom: 0;\
				display: block;\
				padding: 5px 13px;\
				text-decoration: none;\
			}\
			</style>').appendTo(document.querySelector('head'));
			
			floating_link();
			
			if (settings.get('hide_at_top', true)) {
				document.querySelector('.quick-reply-btn').style.display = 'none';
				
				$(window).scroll(() => {
					if ($(this).width() <= 400)
						return;
					if ($(this).scrollTop() < $('form[name="post"]:first').offset().top + $('form[name="post"]:first').height() - 100)
						document.querySelector('.quick-reply-btn').fadeOut(100);
					else
						document.querySelector('.quick-reply-btn').fadeIn(100);
				}).scroll();
			}
		});
	}
})();
