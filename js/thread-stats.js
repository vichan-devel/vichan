/*
 * thread-stats.js
 *   - Adds statistics of the thread below the posts area
 *   - Shows ID post count beside each postID on hover
 * 
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/thread-stats.js';
 */
if (active_page === 'thread') {
onReady(() => {
	//check if page uses unique ID
	const IDsupport = (document.querySelector('.poster_id').length > 0);
	const thread_id = (document.location.pathname + document.location.search).split('/');
	thread_id = thread_id[thread_id.length -1].split('+')[0].split('-')[0].split('.')[0];
	
	document.querySelectorAll('.boardlist.bottom, footer')
		.first()
		.before('<div id="thread_stats"></div>');
	const el = document.getElementById('thread_stats');
	el.insertBefore(_('Page')+' <span id="thread_stats_page">?</span>');
	if (IDsupport){
		el.insertBefore('<span id="thread_stats_uids">0</span> UIDs |&nbsp;');
	}
	el.insertBefore('<span id="thread_stats_images">0</span> '+_('images')+' |&nbsp;');
	el.insertBefore('<span id="thread_stats_posts">0</span> '+_('replies')+' |&nbsp;');
	delete el;
	function update_thread_stats(){
		const op = $('#thread_'+ thread_id +' > div.post.op:not(.post-hover):not(.inline)').first();
		const replies = $('#thread_'+ thread_id +' > div.post.reply:not(.post-hover):not(.inline)');
		// post count
		document.getElementById('thread_stats_posts').text(replies.length);
		// image count
		document.getElementById('thread_stats_images').text(replies.filter(() => { 
			return $(this).querySelector('> .files').textContent.trim() != false; 
		}).length);
		// unique ID count
		if (IDsupport) {
			const opID = op.querySelector('> .intro > .poster_id').textContent;
			const ids = {};
			replies.each(() => {
				const cur = $(this).querySelector('> .intro > .poster_id');
				const curID = cur.textContent;
				if (ids[curID] === undefined) {
					ids[curID] = 0;
				}
				ids[curID]++;
			});
			if (ids[opID] === undefined) {
				ids[opID] = 0;
			}
			ids[opID]++;
			const cur = op.querySelector('>.intro >.poster_id');
			cur.querySelector('+.posts_by_id').remove();
			cur.after('<span class="posts_by_id"> ('+ ids[cur.textContent] +')</span>');
			replies.each(() => {
				cur = $(this).querySelector('>.intro >.poster_id');
				cur.querySelector('+.posts_by_id').remove();
				cur.after('<span class="posts_by_id"> ('+ ids[cur.textContent] +')</span>');
			});
			const size = (obj) => {
				const size = 0, key;
				for (key in obj) {
					if (obj.hasOwnProperty(key)) size++;
				}
				return size;
			};
			document.getElementById('thread_stats_uids').text(size(ids));
		}
		const board_name = $('input[name="board"]').value;
		$.getJSON('//'+ document.location.host +'/'+ board_name +'/threads.json').success((data) => {
			const found, page = '???';
			for (var i=0;data[i];i++){
				const threads = data[i].threads;
				for (var j=0; threads[j]; j++){
					if (parseInt(threads[j].no) === parseInt(thread_id)) {
						page = data[i].page +1;
						found = true;
						break;
					}
				}
				if (found) break;
			}
			document.getElementById('thread_stats_page').text(page);
			if (!found) document.getElementById('thread_stats_page').css('color','red');
			else document.getElementById('thread_stats_page').css('color','');
		});
	}
	// load the current page the thread is on.
	// uses ajax call so it gets loaded on a delay (depending on network resources available)
	const thread_stats_page_timer = setInterval(() => {
		const board_name = $('input[name="board"]').value;
		$.getJSON('//'+ document.location.host +'/'+ board_name +'/threads.json').success((data) => {
			const found, page = '???';
			for (var i=0;data[i];i++){
				const threads = data[i].threads;
				for (var j=0; threads[j]; j++){
					if (parseInt(threads[j].no) === parseInt(thread_id)) {
						page = data[i].page +1;
						found = true;
						break;
					}
				}
				if (found) break;
			}
			document.getElementById('thread_stats_page').text(page);
			if (!found) document.getElementById('thread_stats_page').css('color','red');
			else document.getElementById('thread_stats_page').css('color','');
		});
	},30000);
		document.querySelector('body').appendChild('<style>.posts_by_id{display:none;}.poster_id:hover+.posts_by_id{display:initial}</style>');
		update_thread_stats();
		document.getElementById('update_thread').click(update_thread_stats);
		$(document).on('new_post',update_thread_stats);
});
}
