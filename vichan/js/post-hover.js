/*
 * post-hover.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/post-hover.js
 *
 * Released under the MIT license
 * Copyright (c) 2012 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
 * Copyright (c) 2013 Macil Tech <maciltech@gmail.com>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/post-hover.js';
 *
 */

onready(function(){
	var dont_fetch_again = [];
	init_hover = function() {
		var $link = $(this);
		
		var id;
		var matches;

                if ($link.is('[data-thread]')) {
                        id = $link.attr('data-thread');
                }
		else if(matches = $link.text().match(/^>>(?:>\/([^\/]+)\/)?(\d+)$/)) {
			id = matches[2];
		}
		else {
			return;
		}
		
		var board = $(this);
		while (board.data('board') === undefined) {
			board = board.parent();
		}
		var threadid;
		if ($link.is('[data-thread]')) threadid = 0;
		else threadid = board.attr('id').replace("thread_", "");

		board = board.data('board');

		var parentboard = board;
		
		if ($link.is('[data-thread]')) parentboard = $('form[name="post"] input[name="board"]').val();
		else if (matches[1] !== undefined) board = matches[1];

		var $post = false;
		var hovering = false;
		var hovered_at;
		$link.hover(function(e) {
			hovering = true;
			hovered_at = {'x': e.pageX, 'y': e.pageY};
			
			var start_hover = function($link) {
				if ($post.is(':visible') &&
						$post.offset().top >= $(window).scrollTop() &&
						$post.offset().top + $post.height() <= $(window).scrollTop() + $(window).height()) {
					// post is in view
					$post.addClass('highlighted');
				} else {
					var $newPost = $post.clone();
					$newPost.find('>.reply, >br').remove();
					$newPost.find('span.mentioned').remove();
					$newPost.find('a.post_anchor').remove();

					$newPost
						.attr('id', 'post-hover-' + id)
						.attr('data-board', board)
						.addClass('post-hover')
						.css('border-style', 'solid')
						.css('box-shadow', '1px 1px 1px #999')
						.css('display', 'block')
						.css('position', 'absolute')
						.css('font-style', 'normal')
						.css('z-index', '100')
						.addClass('reply').addClass('post')
						.insertAfter($link.parent())

					$link.trigger('mousemove');
				}
			};
			
			$post = $('[data-board="' + board + '"] div.post#reply_' + id + ', [data-board="' + board + '"]div#thread_' + id);
			if($post.length > 0) {
				start_hover($(this));
			} else {
				var url = $link.attr('href').replace(/#.*$/, '');
				
				if($.inArray(url, dont_fetch_again) != -1) {
					return;
				}
				dont_fetch_again.push(url);
				
				$.ajax({
					url: url,
					context: document.body,
					success: function(data) {
						var mythreadid = $(data).find('div[id^="thread_"]').attr('id').replace("thread_", "");

						if (mythreadid == threadid && parentboard == board) {
							$(data).find('div.post.reply').each(function() {
								if($('[data-board="' + board + '"] #' + $(this).attr('id')).length == 0) {
									$('[data-board="' + board + '"]#thread_' + threadid + " .post.reply:first").before($(this).hide().addClass('hidden'));
								}
							});
						}
						else if ($('[data-board="' + board + '"]#thread_'+mythreadid).length > 0) {
							$(data).find('div.post.reply').each(function() {
								if($('[data-board="' + board + '"] #' + $(this).attr('id')).length == 0) {
									$('[data-board="' + board + '"]#thread_' + mythreadid + " .post.reply:first").before($(this).hide().addClass('hidden'));
								}
							});
						}
						else {
							$(data).find('div[id^="thread_"]').hide().attr('data-cached', 'yes').prependTo('form[name="postcontrols"]');
						}

						$post = $('[data-board="' + board + '"] div.post#reply_' + id + ', [data-board="' + board + '"]div#thread_' + id);

						if(hovering && $post.length > 0) {
							start_hover($link);
						}
					}
				});
			}
		}, function() {
			hovering = false;
			if(!$post)
				return;
			
			$post.removeClass('highlighted');
			if($post.hasClass('hidden') || $post.data('cached') == 'yes')
				$post.css('display', 'none');
			$('.post-hover').remove();
		}).mousemove(function(e) {
			if(!$post)
				return;
			
			var $hover = $('#post-hover-' + id + '[data-board="' + board + '"]');
			if($hover.length == 0)
				return;

			var scrollTop = $(window).scrollTop();
			if ($link.is("[data-thread]")) scrollTop = 0;
			var epy = e.pageY;
			if ($link.is("[data-thread]")) epy -= $(window).scrollTop();			

			var top = (epy ? epy : hovered_at['y']) - 10;
			
			if(epy < scrollTop + 15) {
				top = scrollTop;
			} else if(epy > scrollTop + $(window).height() - $hover.height() - 15) {
				top = scrollTop + $(window).height() - $hover.height() - 15;
			}
			
			
			$hover.css('left', (e.pageX ? e.pageX : hovered_at['x'])).css('top', top);
		});
	};
	
	$('div.body a:not([rel="nofollow"])').each(init_hover);
	
	// allow to work with auto-reload.js, etc.
	$(document).on('new_post', function(e, post) {
		$(post).find('div.body a:not([rel="nofollow"])').each(init_hover);
	});
});

