/*
 * ajax.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/ajax.js
 *
 * Released under the MIT license
 * Copyright (c) 2013 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/ajax.js';
 *
 */

$(window).ready(function() {
	var settings = new script_settings('ajax');
	var do_not_ajax = false;

	// Enable submit button if disabled (cache problem)
	$('input[type="submit"]').removeAttr('disabled');
	
	var setup_form = function($form) {
		$form.submit(function() {
			if (do_not_ajax)
				return true;
			var form = this;
			var submit_txt = $(this).find('input[type="submit"]').val();
			if (window.FormData === undefined)
				return true;
			
			var formData = new FormData(this);
			formData.append('json_response', '1');
			formData.append('post', submit_txt);

			$(document).trigger("ajax_before_post", formData);

			var updateProgress = function(e) {
				var percentage;
				if (e.position === undefined) { // Firefox
					percentage = Math.round(e.loaded * 100 / e.total);
				}
				else { // Chrome?
					percentage = Math.round(e.position * 100 / e.total);
				}
				$(form).find('input[type="submit"]').val(_('Posting... (#%)').replace('#', percentage));
			};

			$.ajax({
				url: this.action,
				type: 'POST',
				xhr: function() {
					var xhr = $.ajaxSettings.xhr();
					if(xhr.upload) {
						xhr.upload.addEventListener('progress', updateProgress, false);
					}
					return xhr;
				},
				success: function(post_response) {
					if (post_response.error) {
						if (post_response.banned) {
							// You are banned. Must post the form normally so the user can see the ban message.
							do_not_ajax = true;
							$(form).find('input[type="submit"]').each(function() {
								var $replacement = $('<input type="hidden">');
								$replacement.attr('name', $(this).attr('name'));
								$replacement.val(submit_txt);
								$(this)
									.after($replacement)
									.replaceWith($('<input type="button">').val(submit_txt));
							});
							$(form).submit();
						} else {
							alert(post_response.error);
							$(form).find('input[type="submit"]').val(submit_txt);
							$(form).find('input[type="submit"]').removeAttr('disabled');
						}
					} else if (post_response.redirect && post_response.id) {
						if (!$(form).find('input[name="thread"]').length
							|| (!settings.get('always_noko_replies', true) && !post_response.noko)) {
							document.location = post_response.redirect;
						} else {
							$.ajax({
								url: document.location,
								success: function(data) {
									$(data).find('div.post.reply').each(function() {
										var id = $(this).attr('id');
										if($('#' + id).length == 0) {
											$(this).insertAfter($('div.post:last').next()).after('<br class="clear">');
											$(document).trigger('new_post', this);
											// watch.js & auto-reload.js retrigger
											setTimeout(function() { $(window).trigger("scroll"); }, 100);
										}
									});
									
									highlightReply(post_response.id);
									window.location.hash = post_response.id;
									$(window).scrollTop($('div.post#reply_' + post_response.id).offset().top);
									
									$(form).find('input[type="submit"]').val(submit_txt);
									$(form).find('input[type="submit"]').removeAttr('disabled');
									$(form).find('input[name="subject"],input[name="file_url"],\
										textarea[name="body"],input[type="file"]').val('').change();
								},
								cache: false,
								contentType: false,
								processData: false
							}, 'html');
						}
						$(form).find('input[type="submit"]').val(_('Posted...'));
						$(document).trigger("ajax_after_post", post_response);
					} else {
						alert(_('An unknown error occured when posting!'));
						$(form).find('input[type="submit"]').val(submit_txt);
						$(form).find('input[type="submit"]').removeAttr('disabled');
					}
				},
				error: function(xhr, status, er) {
					console.log(xhr);
					alert(_('The server took too long to submit your post. Your post was probably still submitted. If it wasn\'t, we might be experiencing issues right now -- please try your post again later. Error information: ') + "<div><textarea>" + JSON.stringify(xhr) + "</textarea></div>");
					$(form).find('input[type="submit"]').val(submit_txt);
					$(form).find('input[type="submit"]').removeAttr('disabled');
				},
				data: formData,
				cache: false,
				contentType: false,
				processData: false
			}, 'json');
			
			$(form).find('input[type="submit"]').val(_('Posting...'));
			$(form).find('input[type="submit"]').attr('disabled', true);
			
			return false;
		});
	};
	setup_form($('form[name="post"]'));
	$(window).on('quick-reply', function() {
		$('form#quick-reply').off('submit');
		setup_form($('form#quick-reply'));
	});
});
