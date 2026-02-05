/*
 * recent-posts.js
 *
 * Recent posts controlling script
 *
 * Released under the WTFPL license
 * Copyright (c) 2014 sinuca <#55ch@rizon.net>
 *
 * Requires jquery
 * incomplete
 *
 */

onReady(() => {
	
	if (!localStorage.hiddenrecentposts)
		localStorage.hiddenrecentposts = '{}';

	if (!localStorage.recentpostscount)
		localStorage.recentpostscount = 25;

	// Load data from HTML5 localStorage
	const hidden_data = JSON.parse(localStorage.hiddenrecentposts);

	const store_data_posts = () => {
		localStorage.hiddenrecentposts = JSON.stringify(hidden_data);
	}

	// Delete old hidden posts (7+ days old)
	for (var key in hidden_data) {
		for (var id in hidden_data[key]) {
			if (hidden_data[key][id] < Math.round(Date.now() / 1000) - 60 * 60 * 24 * 7) {
				delete hidden_data[key][id];
				store_data_posts();
			}
		}
	}

	const do_hide_posts = () => {
		const data = $(this).getAttribute('id');
		const splitted = data.split('-');
		const id = splitted[2];
		const post_container = $(this).parent();

		const board = post_container.data("board");
		
		if (!hidden_data[board]) {
			hidden_data[board] = {};
		}

		$('<a class="hide-post-link" href="javascript:void(0)"> Dismiss </a>')
		.insertBefore(post_container.querySelector('a.eita-link:first'))
		.click(() => {
			hidden_data[board][id] = Math.round(Date.now() / 1000);
			store_data_posts();

			post_container.closest('hr').style.display = 'none';
			post_container.children().style.display = 'none';
		});
		if(hidden_data[board][id])
			post_container.querySelector('a.hide-post-link').click();
	}

	document.querySelector('a.eita-link').each(do_hide_posts);

	document.getElementById('erase-local-data').click(() => {
		hidden_data = {};
		store_data_posts();
		$(this).html('Loading...');
		location.reload();
	});

});