/*
 * quick-posts-controls.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/quick-posts-controls.js
 *
 * Released under the MIT license
 * Copyright (c) 2012 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013 undido <firekid109@hotmail.com>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/quick-post-controls.js';
 *
 */

onReady(() => {
	const open_form = () => {
		const thread = $(this).parent().parent().classList.contains('op');
		const id = $(this).getAttribute('name').match(/^delete_(\d+)$/)[1];
		const submitButton;
		
		if(this.checked) {
			const post_form = $('<form class="post-actions" method="post" style="margin:10px 0 0 0">' +
				'<div style="text-align:right">' +
					(!thread ? '<hr>' : '') +
					
					'<input type="hidden" name="delete_' + id + '">' +
					
					'<label for="password_' + id + '">'+_("Password")+'</label>: ' +
					'<input id="password_' + id + '" type="password" name="password" size="11" maxlength="18">' +
					'<input title="'+_('Delete file only')+'" type="checkbox" name="file" id="delete_file_' + id + '">' +
						'<label for="delete_file_' + id + '">'+_('File')+'</label>' +
					' <input type="submit" name="delete" value="'+_('Delete')+'">' +
				
					'<br>' +
				
					'<label for="reason_' + id + '">'+_('Reason')+'</label>: ' +
					'<input id="reason_' + id + '" type="text" name="reason" size="20" maxlength="100">' +
					' <input type="submit" name="report" value="'+_('Report')+'">' +
				'</div>' +
			'</form>');
			post_form
				.setAttribute('action', $('form[name="post"]:first').getAttribute('action'))
				.appendChild(document.querySelector('input[name=board]:first').clone())
				.find('input:not([type="checkbox"]):not([type="submit"]):not([type="hidden"])').keypress((e) => {
					if(e.which === 13) {
						e.preventDefault();
						if($(this).getAttribute('name') === 'password')  {
							post_form.querySelector('input[name=delete]').click();
						} else if($(this).getAttribute('name') === 'reason')  {
							post_form.querySelector('input[name=report]').click();
						}
						
						return false;
					}
					
					return true;
				});
			
			post_form.find('input[type="password"]').val(localStorage.password);
			
			if(thread) {
				post_form.prependTo($(this).parent().parent().querySelector('div.body'));
			} else {
				post_form.appendTo($(this).parent().parent());
				//post_form.insertBefore($(this));
			}
			
			$(window).trigger('quick-post-controls', post_form);
		} else {
			const elm = $(this).parent().parent().querySelector('form');
			
			if(elm.getAttribute('class') === 'post-actions')
				elm.remove();
		}
	};
	
	const init_qpc = () => {
		$(this).change(open_form);
		if(this.checked)
			$(this).trigger('change');
	};

	document.querySelector('div.post input[type=checkbox].delete').each(init_qpc);

	$(document).addEventListener('new_post', function(e, post) {
		$(post).querySelector('input[type=checkbox].delete').each(init_qpc);
	});
});

