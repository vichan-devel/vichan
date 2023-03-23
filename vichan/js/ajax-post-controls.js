/*
 * ajax-post-controls.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/ajax-post-controls.js
 *
 * Released under the MIT license
 * Copyright (c) 2013 Michael Save <savetheinternet@tinyboard.org>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/ajax-post-controls.js';
 *
 */

$(window).ready(function() {
	var do_not_ajax = false;
	
	var setup_form = function($form) {
		$form.find('input[type="submit"]').click(function() {
			$form.data('submit-btn', this);
		});;
		$form.submit(function(e) {
			if (!$(this).data('submit-btn'))
				return true;
			if (do_not_ajax)
				return true;
			if (window.FormData === undefined)
				return true;
			
			var form = this;
						
			var formData = new FormData(this);
			formData.append('json_response', '1');
			formData.append($($(form).data('submit-btn')).attr('name'), $($(form).data('submit-btn')).val());
			
			$.ajax({
				url: this.action,
				type: 'POST',
				success: function(post_response) {
					if (post_response.error) {
						alert(post_response.error);
					} else if (post_response.success) {
						if ($($(form).data('submit-btn')).attr('name') == 'report') {
							alert(_('Reported post(s).'));
							if ($(form).hasClass('post-actions')) {
								$(form).parents('div.post').find('input[type="checkbox"].delete').click();
							} else {
								$(form).find('input[name="reason"]').val('');
							}
						} else {
							window.location.reload();
						}
					} else {
						alert(_('An unknown error occured!'));
					}
					$($(form).data('submit-btn')).val($($(form).data('submit-btn')).data('orig-val')).removeAttr('disabled');
				},
				error: function(xhr, status, er) {
					// An error occured
					// TODO
					alert(_('Something went wrong... An unknown error occured!'));
				},
				data: formData,
				cache: false,
				contentType: false,
				processData: false
			}, 'json');
			
			$($(form).data('submit-btn')).attr('disabled', true).data('orig-val', $($(form).data('submit-btn')).val()).val(_('Working...'));
			
			return false;
		});
	};
	setup_form($('form[name="postcontrols"]'));
	$(window).on('quick-post-controls', function(e, form) {
		setup_form($(form));
	});
});
