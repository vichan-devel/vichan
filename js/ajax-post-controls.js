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

onReady(() => {
	let do_not_ajax = false;
	
	const setup_form = function(form) {
		const submitButtons = form.querySelectorAll('input[type="submit"]');
		submitButtons.forEach(btn => {
			btn.addEventListener('click', function() {
				form.dataset.submitBtn = this;
			});
		});

		form.addEventListener('submit', (e) => {
			if (!form.dataset.submitBtn)
				return true;
			if (do_not_ajax)
				return true;
			if (window.FormData === undefined)
				return true;
			
			e.preventDefault();
			
			const submitBtn = form.dataset.submitBtn;
			const submitBtnName = submitBtn.getAttribute('name');
			const submitBtnVal = submitBtn.value;
			
			const formData = new FormData(form);
			formData.append('json_response', '1');
			formData.append(submitBtnName, submitBtnVal);
			
			fetch(form.action, {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(post_response => {
				if (post_response.error) {
					alert(post_response.error);
				} else if (post_response.success) {
					if (submitBtnName === 'report') {
						alert(_('Reported post(s).'));
						if (form.classList.contains('post-actions')) {
							const checkbox = form.closest('div.post').querySelector('input[type="checkbox"].delete');
							if (checkbox) checkbox.click();
						} else {
							const reasonInput = form.querySelector('input[name="reason"]');
							if (reasonInput) reasonInput.value = '';
						}
					} else {
						window.location.reload();
					}
				} else {
					alert(_('An unknown error occured!'));
				}
				submitBtn.value = submitBtn.dataset.origVal || submitBtnVal;
				submitBtn.removeAttribute('disabled');
			})
			.catch(err => {
				console.error('Error:', err);
				alert(_('Something went wrong... An unknown error occured!'));
				submitBtn.value = submitBtn.dataset.origVal || submitBtnVal;
				submitBtn.removeAttribute('disabled');
			});
			
			submitBtn.dataset.origVal = submitBtn.value;
			submitBtn.setAttribute('disabled', 'disabled');
			submitBtn.value = _('Working...');
			
			return false;
		});
	};

	const postControlsForm = document.querySelector('form[name="postcontrols"]');
	if (postControlsForm) {
		setup_form(postControlsForm);
	}

	window.addEventListener('quick-post-controls', (e) => {
		const form = e.detail;
		if (form) {
			setup_form(form);
		}
	});
});
