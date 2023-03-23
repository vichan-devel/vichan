/*
 * hide-threads.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/hide-threads.js
 *
 * Released under the MIT license
 * Copyright (c) 2013 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/hide-threads.js';
 *
 */

$(document).ready(function(){
	if (active_page != "index" && active_page != "ukko")
		return; // not index
		
	if (!localStorage.hiddenthreads)
		localStorage.hiddenthreads = '{}';
	
	// Load data from HTML5 localStorage
	var hidden_data = JSON.parse(localStorage.hiddenthreads);
	
	var store_data = function() {
		localStorage.hiddenthreads = JSON.stringify(hidden_data);
	};
	
	// Delete old hidden threads (7+ days old)
	for (var key in hidden_data) {
		for (var id in hidden_data[key]) {
			if (hidden_data[key][id] < Math.round(Date.now() / 1000) - 60 * 60 * 24 * 7) {
				delete hidden_data[key][id];
				store_data();
			}
		}
	}

	var fields_to_hide = 'div.file,div.post,div.video-container,video,iframe,img:not(.unanimated),canvas,p.fileinfo,a.hide-thread-link,div.new-posts,br';
	
	var do_hide_threads = function() {
		var id = $(this).children('p.intro').children('a.post_no:eq(1)').text();
		var thread_container = $(this).parent();

		var board = thread_container.data("board");

		if (!hidden_data[board]) {
			hidden_data[board] = {}; // id : timestamp
		}
	
		$('<a class="hide-thread-link" style="float:left;margin-right:5px" href="javascript:void(0)">[–]</a><span> </span>')
			.insertBefore(thread_container.find(':not(h2,h2 *):first'))
			.click(function() {
				hidden_data[board][id] = Math.round(Date.now() / 1000);
				store_data();
				
				thread_container.find(fields_to_hide).hide();
				
				var hidden_div = thread_container.find('div.post.op > p.intro').clone();
				hidden_div.addClass('thread-hidden');
				hidden_div.find('a[href]:not([href$=".html"]),input').remove();
				hidden_div.html(hidden_div.html().replace(' [] ', ' '));
				hidden_div.html(hidden_div.html().replace(' [] ', ' '));
				
				$('<a class="unhide-thread-link" style="float:left;margin-right:5px;margin-left:0px;" href="javascript:void(0)">[+]</a><span> </span>')
					.insertBefore(hidden_div.find(':first'))
					.click(function() {
						delete hidden_data[board][id];
						store_data();
						thread_container.find(fields_to_hide).show();
						thread_container.find(".hidden").hide();
						$(this).remove();
						hidden_div.remove();
					});
				
				hidden_div.insertAfter(thread_container.find(':not(h2,h2 *):first'));
			});
		if (hidden_data[board][id])
			thread_container.find('.hide-thread-link').click();
	}

	$('div.post.op').each(do_hide_threads);

	$(document).on('new_post', function(e, post) {
		do_hide_threads.call($(post).find('div.post.op')[0]);
	});
});
