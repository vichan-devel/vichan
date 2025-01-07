/*
 * thread-stats.js
 *   - Adds statistics of the thread below the posts area
 *   - Shows ID post count beside each postID on hover
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/thread-stats.js';
 */

if (active_page == 'thread') {
	$(document).ready(function() {
		// Check if page uses unique ID.
		let idSupport = ($('.poster_id').length > 0);
		let threadId = (document.location.pathname + document.location.search).split('/');
		threadId = threadId[threadId.length -1].split('+')[0].split('-')[0].split('.')[0];

		$('.boardlist.bottom, footer')
			.first()
			.before('<div id="thread_stats"></div>');

		let el = $('#thread_stats');
		el.prepend(_('Page')+ ' <span id="thread_stats_page">?</span>');
		if (idSupport) {
			el.prepend('<span id="thread_stats_uids">0</span> UIDs |&nbsp;');
		}
		el.prepend('<span id="thread_stats_images">0</span> ' +_('images')+ ' |&nbsp;');
		el.prepend('<span id="thread_stats_posts">0</span> ' +_('replies')+ ' |&nbsp;');
		delete el;

		function updateThreadStats() {
			let op = $('#thread_' + threadId + ' > div.post.op:not(.post-hover):not(.inline)').first();
			let replies = $('#thread_' + threadId + ' > div.post.reply:not(.post-hover):not(.inline)');
			// Post count.
			$('#thread_stats_posts').text(replies.length);
			// Image count.
			$('#thread_stats_images').text(replies.filter(function() {
				return $(this).find('> .files').text().trim() != false;
			}).length);

			// Unique ID count.
			if (idSupport) {
				let opID = op.find('> .intro > .poster_id').text();
				let ids = {};
				replies.each(function() {
					let cur = $(this).find('> .intro > .poster_id');
					let curID = cur.text();
					if (ids[curID] === undefined) {
						ids[curID] = 0;
					}
					ids[curID]++;
				});

				if (ids[opID] === undefined) {
					ids[opID] = 0;
				}
				ids[opID]++;

				let cur = op.find('>.intro >.poster_id');
				cur.find(' +.posts_by_id').remove();
				cur.after('<span class="posts_by_id"> (' + ids[cur.text()] + ')</span>');
				replies.each(function() {
					cur = $(this).find('>.intro >.poster_id');
					cur.find(' +.posts_by_id').remove();
					cur.after('<span class="posts_by_id"> (' + ids[cur.text()] + ')</span>');
				});
				let size = function(obj) {
					let size = 0;
					for (key in obj) {
						if (obj.hasOwnProperty(key)) {
							size++;
						}
					}
					return size;
				};
				$('#thread_stats_uids').text(size(ids));
			}

			let boardName = $('input[name="board"]').val();
			$.getJSON('//' + document.location.host + '/' + boardName + '/threads.json').success(function(data) {
				let found, page = '???';
				for (let i = 0; data[i]; i++) {
					let threads = data[i].threads;
					for (let j = 0; threads[j]; j++) {
						if (parseInt(threads[j].no) == parseInt(threadId)) {
							page = data[i].page +1;
							found = true;
							break;
						}
					}
					if (found) {
						break;
					}
				}
				$('#thread_stats_page').text(page);
				if (!found) {
					$('#thread_stats_page').css('color', 'red');
				} else {
					$('#thread_stats_page').css('color', '');
				}
			});
		}

		// Load the current page the thread is on.
		// Uses ajax call so it gets loaded on a delay (depending on network resources available).
		let thread_stats_page_timer = setInterval(function() {
			let boardName = $('input[name="board"]').val();
			$.getJSON('//' + document.location.host + '/' + boardName + '/threads.json').success(function(data) {
				let found = false;
				let page = '???';
				for (let i = 0; data[i]; i++) {
					let threads = data[i].threads;
					for (let j = 0; threads[j]; j++) {
						if (parseInt(threads[j].no) == parseInt(threadId)) {
							page = data[i].page +1;
							found = true;
							break;
						}
					}
					if (found) {
						break;
					}
				}

				$('#thread_stats_page').text(page);
				if (!found) {
					$('#thread_stats_page').css('color', 'red');
				} else {
					$('#thread_stats_page').css('color', '');
				}
			});
		}, 30000);

		$('body').append('<style>.posts_by_id{display:none;}.poster_id:hover+.posts_by_id{display:initial}</style>');
		updateThreadStats();
		$('#update_thread').click(updateThreadStats);
		$(document).on('new_post', updateThreadStats);
	});
}
