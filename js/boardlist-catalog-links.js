/*
 * boardlist-catalog-links.js
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/boardlist-catalog-links.js';
 *
 */

$(document).ready(function() {
	
    function replace_index_links() {
        $('.boardlist').children('span[data-description="0"]').children('a').each(function() {
	        this.href = this.href.replace('index.html', 'catalog.html');
        });
    }

	if (window.Options && Options.get_tab('general')) {
		Options.extend_tab("general", "<label><input type='checkbox' id='boardlist-catalog-links' /> "+_('Use catalog links for the board list')+"</label>");

		$('#boardlist-catalog-links').on('change', function() {
			if (this.checked == true) {
				replace_index_links();
				localStorage.boardlist_catalog_links = 'true';
			} else {
				localStorage.boardlist_catalog_links = 'false';
			}
		});
		
		if (localStorage.boardlist_catalog_links === 'true') {
			$('#boardlist-catalog-links').prop('checked', true);
			replace_index_links();
		}
	}
});