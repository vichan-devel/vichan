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
		Options.extend_tab("general", "<label><input type='checkbox' id='pepe-quotes' /> "+_('Pepe colored quotes')+"</label>");
		
		$('#pepe-quotes').on('change', function() {
			if (localStorage.pepe_colored_quotes === 'true') {
				localStorage.pepe_colored_quotes = 'false';
			} else {
				localStorage.pepe_colored_quotes = 'true';
			}
			$(".quote, .quote-pepe").toggleClass("quote quote-pepe");
		});
		
		if (localStorage.pepe_colored_quotes === 'true') {
			$('#pepe-quotes').attr('checked', 'checked');
			$(".quote, .quote-pepe").toggleClass("quote quote-pepe");
		}
	}
});