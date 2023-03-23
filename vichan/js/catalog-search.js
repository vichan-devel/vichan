/*
 * catalog-search.js
 *   - Search and filters threads when on catalog view
 *   - Optional shortcuts 's' and 'esc' to open and close the search.
 * 
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/comment-toolbar.js';
 */
if (active_page == 'catalog') {
	onready(function () {
		'use strict';

		//	'true' = enable shortcuts
		var useKeybinds = true;

		//	trigger the search 400ms after last keystroke
		var delay = 400;
		var timeoutHandle;

		//search and hide none matching threads
		function filter(search_term) {
			$('.replies').each(function () {
				var subject = $(this).children('.intro').text().toLowerCase();
				var comment = $(this).clone().children().remove(':lt(2)').end().text().trim().toLowerCase();
				search_term = search_term.toLowerCase();

				if (subject.indexOf(search_term) == -1 && comment.indexOf(search_term) == -1) {
					$(this).parents('div[id="Grid"]>.mix').css('display', 'none');
				} else {
					$(this).parents('div[id="Grid"]>.mix').css('display', 'inline-block');
				}
			});
		}

		function searchToggle() {
			var button = $('#catalog_search_button');

			if (!button.data('expanded')) {
				button.data('expanded', '1');
				button.text('Close');
				$('.catalog_search').append(' <input id="search_field" style="border: inset 1px;">');
				$('#search_field').focus();
			} else {
				button.removeData('expanded');
				button.text('Search');
				$('.catalog_search').children().last().remove();
				$('div[id="Grid"]>.mix').each(function () { $(this).css('display', 'inline-block'); });
			}
		}

		$('.threads').before('<span class="catalog_search">[<a id="catalog_search_button" style="text-decoration:none; cursor:pointer;"></a>]</span>');
		$('#catalog_search_button').text('Search');

		$('#catalog_search_button').on('click', searchToggle);
		$('.catalog_search').on('keyup', 'input#search_field', function (e) {
			window.clearTimeout(timeoutHandle);
			timeoutHandle = window.setTimeout(filter, 400, e.target.value);
		});

		if (useKeybinds) {
			//	's'
			$('body').on('keydown', function (e) {
				if (e.which === 83 && e.target.tagName === 'BODY' && !(e.ctrlKey || e.altKey || e.shiftKey)) {
					e.preventDefault();
					if ($('#search_field').length !== 0) { 
						$('#search_field').focus();
					} else {
						searchToggle();
					}
				}
			});
			//	'esc'
			$('.catalog_search').on('keydown', 'input#search_field', function (e) {
				if (e.which === 27 && !(e.ctrlKey || e.altKey || e.shiftKey)) {
					window.clearTimeout(timeoutHandle);
					searchToggle();
				}
			});
		}
	});
}
