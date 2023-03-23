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
$(document).ready(function(){
	//check if page uses unique ID
	var IDsupport = ($('.poster_id').length > 0);
	var thread_id = (document.location.pathname + document.location.search).split('/');
	thread_id = thread_id[thread_id.length -1].split('+')[0].split('-')[0].split('.')[0];
	
	$('.boardlist.bottom, footer')
		.first()
		.before('<div id="thread_stats"></div>');
	var el = $('#thread_stats');
	el.prepend(_('Page')+' <span id="thread_stats_page">?</span>');
	if (IDsupport){
		el.prepend('<span id="thread_stats_uids">0</span> UIDs |&nbsp;');
	}
	el.prepend('<span id="thread_stats_images">0</span> '+_('images')+' |&nbsp;');
	el.prepend('<span id="thread_stats_posts">0</span> '+_('replies')+' |&nbsp;');
	delete el;
	function update_thread_stats(){
		var op = $('#thread_'+ thread_id +' > div.post.op:not(.post-hover):not(.inline)').first();
		var replies = $('#thread_'+ thread_id +' > div.post.reply:not(.post-hover):not(.inline)');
		// post count
		$('#thread_stats_posts').text(replies.length);
		// image count
		$('#thread_stats_images').text(replies.filter(function(){ 
			return $(this).find('> .files').text().trim() != false; 
		}).length);
		// unique ID count
		if (IDsupport) {
			var opID = op.find('> .intro > .poster_id').text();
			var ids = {};
			replies.each(function(){
				var cur = $(this).find('> .intro > .poster_id');
				var curID = cur.text();
				if (ids[curID] === undefined) {
					ids[curID] = 0;
				}
				ids[curID]++;
			});
			if (ids[opID] === undefined) {
				ids[opID] = 0;
			}
			ids[opID]++;
			var cur = op.find('>.intro >.poster_id');
			cur.find('+.posts_by_id').remove();
			cur.after('<span class="posts_by_id"> ('+ ids[cur.text()] +')</span>');
			replies.each(function(){
				cur = $(this).find('>.intro >.poster_id');
				cur.find('+.posts_by_id').remove();
				cur.after('<span class="posts_by_id"> ('+ ids[cur.text()] +')</span>');
			});
			var size = function(obj) {
				var size = 0, key;
				for (key in obj) {
					if (obj.hasOwnProperty(key)) size++;
				}
				return size;
			};
			$('#thread_stats_uids').text(size(ids));
		}
		var board_name = $('input[name="board"]').val();
		$.getJSON('//'+ document.location.host +'/'+ board_name +'/threads.json').success(function(data){
			var found, page = '???';
			for (var i=0;data[i];i++){
				var threads = data[i].threads;
				for (var j=0; threads[j]; j++){
					if (parseInt(threads[j].no) == parseInt(thread_id)) {
						page = data[i].page +1;
						found = true;
						break;
					}
				}
				if (found) break;
			}
			$('#thread_stats_page').text(page);
			if (!found) $('#thread_stats_page').css('color','red');
			else $('#thread_stats_page').css('color','');
		});
	}
	// load the current page the thread is on.
	// uses ajax call so it gets loaded on a delay (depending on network resources available)
	var thread_stats_page_timer = setInterval(function(){
		var board_name = $('input[name="board"]').val();
		$.getJSON('//'+ document.location.host +'/'+ board_name +'/threads.json').success(function(data){
			var found, page = '???';
			for (var i=0;data[i];i++){
				var threads = data[i].threads;
				for (var j=0; threads[j]; j++){
					if (parseInt(threads[j].no) == parseInt(thread_id)) {
						page = data[i].page +1;
						found = true;
						break;
					}
				}
				if (found) break;
			}
			$('#thread_stats_page').text(page);
			if (!found) $('#thread_stats_page').css('color','red');
			else $('#thread_stats_page').css('color','');
		});
	},30000);
		$('body').append('<style>.posts_by_id{display:none;}.poster_id:hover+.posts_by_id{display:initial}</style>');
		update_thread_stats();
		$('#update_thread').click(update_thread_stats);
		$(document).on('new_post',update_thread_stats);
});
}
