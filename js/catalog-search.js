/*
 * catalog-search.js
 *   - Search and filters threads when on catalog view
 *   - Optional shortcuts 's' and 'esc' to open and close the search.
 *
 * Usage (no jQuery needed anymore):
 *   $config['additional_javascript'][] = 'js/catalog-search.js';
 */
if (active_page === 'catalog') {
	onReady(() => {
		'use strict';

		// 'true' = enable shortcuts
		const useKeybinds = true;
		let timeoutHandle;

		// search and hide none matching threads
		const filter = (search_term) => {
			const replies = document.querySelectorAll('.replies');
			search_term = search_term.toLowerCase();

			replies.forEach(replyEl => {
				const subject = replyEl.querySelector('.intro')?.textContent.toLowerCase() || '';
				
				// Clone and remove first 2 children to get comment
				const commentEl = replyEl.cloneNode(true);
				const children = commentEl.querySelectorAll(':nth-child(1), :nth-child(2)');
				children.forEach(child => child.remove());
				const comment = commentEl.textContent.trim().toLowerCase();

				const matchesSearch = subject.includes(search_term) || comment.includes(search_term);
				const mixElement = replyEl.closest('div[id="Grid"]')?.querySelector('.mix[id]') || 
									replyEl.closest('.mix');
				
				if (mixElement) {
					mixElement.style.display = matchesSearch ? 'inline-block' : 'none';
				}
			});
		};

		const searchToggle = () => {
			const button = document.getElementById('catalog_search_button');
			const catalogSearch = document.querySelector('.catalog_search');

			if (!button.dataset.expanded) {
				button.dataset.expanded = '1';
				button.textContent = 'Close';
				const input = document.createElement('input');
				input.id = 'search_field';
				input.style.border = 'inset 1px';
				catalogSearch.appendChild(input);
				input.focus();
			} else {
				delete button.dataset.expanded;
				button.textContent = 'Search';
				const searchField = document.getElementById('search_field');
				searchField?.remove();
				
				document.querySelectorAll('div[id="Grid"] .mix').forEach(el => {
					el.style.display = 'inline-block';
				});
			}
		};

		// Initialize search button
		const threads = document.querySelector('.threads');
		if (threads) {
			const searchSpan = document.createElement('span');
			searchSpan.className = 'catalog_search';
			searchSpan.innerHTML = '[<a id="catalog_search_button" style="text-decoration:none; cursor:pointer;">Search</a>]';
			threads.parentNode.insertBefore(searchSpan, threads);

			const button = document.getElementById('catalog_search_button');
			button.addEventListener('click', searchToggle);

			const catalogSearch = document.querySelector('.catalog_search');
			catalogSearch.addEventListener('keyup', (e) => {
				if (e.target.id === 'search_field') {
					window.clearTimeout(timeoutHandle);
					timeoutHandle = window.setTimeout(() => filter(e.target.value), 400);
				}
			});

			if (useKeybinds) {
				// 's' key binding
				document.addEventListener('keydown', (e) => {
					if (e.key === 's' && e.target === document.body && 
						!(e.ctrlKey || e.altKey || e.shiftKey)) {
						e.preventDefault();
						const searchField = document.getElementById('search_field');
						if (searchField) {
							searchField.focus();
						} else {
							searchToggle();
						}
					}
				});

				// 'esc' key binding
				catalogSearch.addEventListener('keydown', (e) => {
					if (e.target.id === 'search_field' && e.key === 'Escape' && 
						!(e.ctrlKey || e.altKey || e.shiftKey)) {
						window.clearTimeout(timeoutHandle);
						searchToggle();
					}
				});
			}
		}
	});
}
