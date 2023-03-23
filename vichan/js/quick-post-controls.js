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

$(document).ready(function(){
	var open_form = function() {
		var thread = $(this).parent().parent().hasClass('op');
		var id = $(this).attr('name').match(/^delete_(\d+)$/)[1];
		var submitButton;
		
		if(this.checked) {
			var post_form = $('<form class="post-actions" method="post" style="margin:10px 0 0 0">' +
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
				.attr('action', $('form[name="post"]:first').attr('action'))
				.append($('input[name=board]:first').clone())
				.find('input:not([type="checkbox"]):not([type="submit"]):not([type="hidden"])').keypress(function(e) {
					if(e.which == 13) {
						e.preventDefault();
						if($(this).attr('name') == 'password')  {
							post_form.find('input[name=delete]').click();
						} else if($(this).attr('name') == 'reason')  {
							post_form.find('input[name=report]').click();
						}
						
						return false;
					}
					
					return true;
				});
			
			post_form.find('input[type="password"]').val(localStorage.password);
			
			if(thread) {
				post_form.prependTo($(this).parent().parent().find('div.body'));
			} else {
				post_form.appendTo($(this).parent().parent());
				//post_form.insertBefore($(this));
			}
			
			$(window).trigger('quick-post-controls', post_form);
		} else {
			var elm = $(this).parent().parent().find('form');
			
			if(elm.attr('class') == 'post-actions')
				elm.remove();
		}
	};
	
	var init_qpc = function() {
		$(this).change(open_form);
		if(this.checked)
			$(this).trigger('change');
	};

	$('div.post input[type=checkbox].delete').each(init_qpc);

	$(document).on('new_post', function(e, post) {
		$(post).find('input[type=checkbox].delete').each(init_qpc);
	});
});

