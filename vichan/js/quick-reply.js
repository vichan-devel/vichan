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

(function() {
	var settings = new script_settings('quick-reply');
	
	var do_css = function() {
		$('#quick-reply-css').remove();
		
		// Find background of reply posts
		var dummy_reply = $('<div class="post reply"></div>').appendTo($('body'));
		var reply_background = dummy_reply.css('backgroundColor');
		var reply_border_style = dummy_reply.css('borderStyle');
		var reply_border_color = dummy_reply.css('borderColor');
		var reply_border_width = dummy_reply.css('borderWidth');
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
		</style>').appendTo($('head'));
	};
	
	var show_quick_reply = function(){
		if($('div.banner').length == 0)
			return;
		if($('#quick-reply').length != 0)
			return;
		
		do_css();
		
		var $postForm = $('form[name="post"]').clone();
		
		$postForm.clone();
		
		$dummyStuff = $('<div class="nonsense"></div>').appendTo($postForm);
		
		$postForm.find('table tr').each(function() {
			var $th = $(this).children('th:first');
			var $td = $(this).children('td:first');		
			if ($th.length && $td.length) {
				$td.attr('colspan', 2);
	
				if ($td.find('input[type="text"]').length) {
					// Replace <th> with input placeholders
					$td.find('input[type="text"]')
						.removeAttr('size')
						.attr('placeholder', $th.clone().children().remove().end().text());
				}
	
				// Move anti-spam nonsense and remove <th>
				$th.contents().filter(function() {
					return this.nodeType == 3; // Node.TEXT_NODE
				}).remove();
				$th.contents().appendTo($dummyStuff);
				$th.remove();
	
				if ($td.find('input[name="password"]').length) {
					// Hide password field
					$(this).hide();
				}
	
				// Fix submit button
				if ($td.find('input[type="submit"]').length) {
					$td.removeAttr('colspan');
					$('<td class="submit"></td>').append($td.find('input[type="submit"]')).insertAfter($td);
				}
	
				// reCAPTCHA
				if ($td.find('#recaptcha_widget_div').length) {
					// Just show the image, and have it interact with the real form.
					var $captchaimg = $td.find('#recaptcha_image img');
					
					$captchaimg
						.removeAttr('id')
						.removeAttr('style')
						.addClass('recaptcha_image')
						.click(function() {
							$('#recaptcha_reload').click();
						});
					
					// When we get a new captcha...
					$('#recaptcha_response_field').focus(function() {
						if ($captchaimg.attr('src') != $('#recaptcha_image img').attr('src')) {
							$captchaimg.attr('src', $('#recaptcha_image img').attr('src'));
							$postForm.find('input[name="recaptcha_challenge_field"]').val($('#recaptcha_challenge_field').val());
							$postForm.find('input[name="recaptcha_response_field"]').val('').focus();
						}
					});
					
					$postForm.submit(function() {
						setTimeout(function() {
							$('#recaptcha_reload').click();
						}, 200);
					});
					
					// Make a new row for the response text
					var $newRow = $('<tr><td class="recaptcha-response" colspan="2"></td></tr>');
					$newRow.children().first().append(
						$td.find('input').removeAttr('style')
					);
					$newRow.find('#recaptcha_response_field')
						.removeAttr('id')
						.addClass('recaptcha_response_field')
						.attr('placeholder', $('#recaptcha_response_field').attr('placeholder'));
					
					$('#recaptcha_response_field').addClass('recaptcha_response_field')
					
					$td.replaceWith($('<td class="recaptcha" colspan="2"></td>').append($('<span></span>').append($captchaimg)));
					
					$newRow.insertAfter(this);
				}
	
				// Upload section
				if ($td.find('input[type="file"]').length) {
					if ($td.find('input[name="file_url"]').length) {
						$file_url = $td.find('input[name="file_url"]');
						
						if (settings.get('show_remote', false)) {
							// Make a new row for it
							var $newRow = $('<tr><td colspan="2"></td></tr>');
						
							$file_url.clone().attr('placeholder', _('Upload URL')).appendTo($newRow.find('td'));
						
							$newRow.insertBefore(this);
						}
						$file_url.parent().remove();

						
						$td.find('label').remove();
						$td.contents().filter(function() {
							return this.nodeType == 3; // Node.TEXT_NODE
						}).remove();
						$td.find('input[name="file_url"]').removeAttr('id');
					}
					
					if ($(this).find('input[name="spoiler"]').length) {
						$td.removeAttr('colspan');
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
					var tr = this;
					$td.find('input[type="checkbox"]').each(function() {
						if ($(this).attr('name') == 'spoiler') {
							$td.find('label').remove();
							$(this).attr('id', 'q-spoiler-image');
							$postForm.find('input[type="file"]').parent()
								.removeAttr('colspan')
								.after($('<td class="spoiler"></td>').append(this, ' ', $('<label for="q-spoiler-image">').text(_('Spoiler Image'))));
						} else if ($(this).attr('name') == 'no_country') {
							$td.find('label,input[type="checkbox"]').remove();
						} else {
							$(tr).remove();
						}
					});
				}
				
				$td.find('small').hide();
			}
		});
		
		$postForm.find('textarea[name="body"]').removeAttr('id').removeAttr('cols').attr('placeholder', _('Comment'));
	
		$postForm.find('textarea:not([name="body"]),input[type="hidden"]:not(.captcha_cookie)').removeAttr('id').appendTo($dummyStuff);
	
		$postForm.find('br').remove();
		$postForm.find('table').prepend('<tr><th colspan="2">\
			<span class="handle">\
				<a class="close-btn" href="javascript:void(0)">×</a>\
				' + _('Quick Reply') + '\
			</span>\
			</th></tr>');
		
		$postForm.attr('id', 'quick-reply');
		
		$postForm.appendTo($('body')).hide();
		$origPostForm = $('form[name="post"]:first');
		
		// Synchronise body text with original post form
		$origPostForm.find('textarea[name="body"]').on('change input propertychange', function() {
			$postForm.find('textarea[name="body"]').val($(this).val());
		});
		$postForm.find('textarea[name="body"]').on('change input propertychange', function() {
			$origPostForm.find('textarea[name="body"]').val($(this).val());
		});
		$postForm.find('textarea[name="body"]').focus(function() {
			$origPostForm.find('textarea[name="body"]').removeAttr('id');
			$(this).attr('id', 'body');
		});
		$origPostForm.find('textarea[name="body"]').focus(function() {
			$postForm.find('textarea[name="body"]').removeAttr('id');
			$(this).attr('id', 'body');
		});
		// Synchronise other inputs
		$origPostForm.find('input[type="text"],select').on('change input propertychange', function() {
			$postForm.find('[name="' + $(this).attr('name') + '"]').val($(this).val());
		});
		$postForm.find('input[type="text"],select').on('change input propertychange', function() {
			$origPostForm.find('[name="' + $(this).attr('name') + '"]').val($(this).val());
		});

		if (typeof $postForm.draggable != 'undefined') {	
			if (localStorage.quickReplyPosition) {
				var offset = JSON.parse(localStorage.quickReplyPosition);
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
				stop: function() {
					var offset = {
						top: $(this).offset().top - $(window).scrollTop(),
						right: $(window).width() - $(this).offset().left - $(this).width(),
					};
					localStorage.quickReplyPosition = JSON.stringify(offset);
					
					$postForm.css('right', offset.right).css('top', offset.top).css('left', 'auto');
				}
			});
			$postForm.find('th .handle').css('cursor', 'move');
		}
		
		$postForm.find('th .close-btn').click(function() {
			$origPostForm.find('textarea[name="body"]').attr('id', 'body');
			$postForm.remove();
			floating_link();
		});
		
		// Fix bug when table gets too big for form. Shouldn't exist, but crappy CSS etc.
		$postForm.show();
		$postForm.width($postForm.find('table').width());
		$postForm.hide();
		
		$(window).trigger('quick-reply');
	
		$(window).ready(function() {
			if (settings.get('hide_at_top', true)) {
				$(window).scroll(function() {
					if ($(this).width() <= 400)
						return;
					if ($(this).scrollTop() < $origPostForm.offset().top + $origPostForm.height() - 100)
						$postForm.fadeOut(100);
					else
						$postForm.fadeIn(100);
				}).scroll();
			} else {
				$postForm.show();
			}
			
			$(window).on('stylesheet', function() {
				do_css();
				if ($('link#stylesheet').attr('href')) {
					$('link#stylesheet')[0].onload = do_css;
				}
			});
		});
	};
	
	$(window).on('cite', function(e, id, with_link) {
		if ($(this).width() <= 400)
			return;
		show_quick_reply();
		if (with_link) {
			$(document).ready(function() {
				if ($('#' + id).length) {
					highlightReply(id);
					$(document).scrollTop($('#' + id).offset().top);
				}
				
				// Honestly, I'm not sure why we need setTimeout() here, but it seems to work.
				// Same for the "tmp" variable stuff you see inside here:
				setTimeout(function() {
					var tmp = $('#quick-reply textarea[name="body"]').val();
					$('#quick-reply textarea[name="body"]').val('').focus().val(tmp);
				}, 1);
			});
		}
	});
	
	var floating_link = function() {
		if (!settings.get('floating_link', false))
			return;
		$('<a href="javascript:void(0)" class="quick-reply-btn">'+_('Quick Reply')+'</a>')
			.click(function() {
				show_quick_reply();
				$(this).remove();
			}).appendTo($('body'));
		
		$(window).on('quick-reply', function() {
			$('.quick-reply-btn').remove();
		});
	};
	
	if (settings.get('floating_link', false)) {
		$(window).ready(function() {
			if($('div.banner').length == 0)
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
			</style>').appendTo($('head'));
			
			floating_link();
			
			if (settings.get('hide_at_top', true)) {
				$('.quick-reply-btn').hide();
				
				$(window).scroll(function() {
					if ($(this).width() <= 400)
						return;
					if ($(this).scrollTop() < $('form[name="post"]:first').offset().top + $('form[name="post"]:first').height() - 100)
						$('.quick-reply-btn').fadeOut(100);
					else
						$('.quick-reply-btn').fadeIn(100);
				}).scroll();
			}
		});
	}
})();
