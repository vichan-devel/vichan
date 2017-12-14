/*
 * pepe-colored-quotes.js
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/pepe-colored-quotes.js';
 *
 */

$(document).ready(function() {
	
	if (window.Options && Options.get_tab('general')) {
		Options.extend_tab("general", "<label><input type='checkbox' id='orange-quotes' /> "+_('Orange colored quotes')+"</label>");
		
		$('#orange-quotes').on('change', function() {
			if (localStorage.orange_colored_quotes === 'true') {
				localStorage.orange_colored_quotes = 'false';
			} else {
				localStorage.orange_colored_quotes = 'true';
			}
			$(".quote, .quote-orange").toggleClass("quote quote-orange");
		});
		
		if (localStorage.orange_colored_quotes === 'true') {
			$('#orange-quotes').attr('checked', 'checked');
			$(".quote, .quote-orange").toggleClass("quote quote-orange");
		}
	}
});