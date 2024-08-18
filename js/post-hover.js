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

/*
 * Unknown media types always return false, so old browsers (css media 3 or prior to css media) which do support
 * any-hover or css media queries may return false negatives.
 * Handle it by checking if the query is explicitly NOT supported.
 */
if (!window.matchMedia('(any-hover: none)').matches) {
	onReady(function() {
		let isScreenSmall = false
		/*
		 * Set up screen size detection.
		 * If the method is not defined, suppose the screen is always not-small.
		 */
		if (window.matchMedia) {
			let query = window.matchMedia('(max-width: 48em)');

			query.addEventListener('change', (e) => isScreenSmall = e.matches);
			isScreenSmall = query.matches;
		}

		let dontFetchAgain = [];
		initHover = function() {
			let link = $(this);
			let id;
			let matches;

			if (link.is('[data-thread]')) {
				id = link.attr('data-thread');
			} else if (matches = link.text().match(/^>>(?:>\/([^\/]+)\/)?(\d+)$/)) {
				id = matches[2];
			} else {
				return;
			}

			let board = $(this);
			while (board.data('board') === undefined) {
				board = board.parent();
			}
			let threadid;
			if (link.is('[data-thread]')) {
				threadid = 0;
			} else {
				threadid = board.attr('id').replace("thread_", "");
			}

			board = board.data('board');

			let parentboard = board;

			if (link.is('[data-thread]')) {
				parentboard = $('form[name="post"] input[name="board"]').val();
			} else if (matches[1] !== undefined) {
				board = matches[1];
			}

			let post = false;
			let hovering = false;
			let hoveredAt;

			let updatePreviewPosition = function(pageX, pageY, hoverPreview) {
				let scrollTop = $(window).scrollTop();
				if (link.is("[data-thread]")) {
					scrollTop = 0;
				}
				let epy = pageY;
				if (link.is("[data-thread]")) {
					epy -= $(window).scrollTop();
				}

				let top = (epy ? epy : hoveredAt['y']) - 10;

				if (epy < scrollTop + 15) {
					top = scrollTop;
				} else if (epy > scrollTop + $(window).height() - hoverPreview.height() - 15) {
					top = scrollTop + $(window).height() - hoverPreview.height() - 15;
				}

				let hovery = pageY ? pageY : hoveredAt['y'];
				if ((hovery - top) > 20){
					top = hovery;
				}

				let previewX;
				if (isScreenSmall) {
					previewX = 0;
				} else {
					previewX = (pageX ? pageX : hoveredAt['x']) + 1
				}

				hoverPreview.css('left', previewX).css('top', top);
			};

			link.hover(function(e) {
				hovering = true;
				hoveredAt = {'x': e.pageX, 'y': e.pageY};

				let startHover = function(link) {
					if ($.contains(post[0], link[0])) {
						// link links to itself or to op; ignore
					} else if (post.is(':visible') &&
						post.offset().top >= $(window).scrollTop() &&
						post.offset().top + post.height() <= $(window).scrollTop() + $(window).height()) {
						// Post is in view, highlight it.
						post.addClass('highlighted');
					} else {
						// Creates the preview, and displays it,
						let hoverPreview = post.clone();
						hoverPreview.find('>.reply, >br').remove();
						hoverPreview.find('a.post_anchor').remove();

						hoverPreview
							.attr('id', 'post-hover-' + id)
							.attr('data-board', board)
							.addClass('post-hover')
							.css('border-style', 'solid')
							.css('display', 'inline-block')
							.css('position', 'absolute')
							.css('font-style', 'normal')
							.css('z-index', '100');

						if (isScreenSmall) {
							hoverPreview
								.css('margin-top', '1em')
								.css('border-left-style', 'none')
								.css('border-right-style', 'none');
						} else {
							hoverPreview.css('margin-left', '1em');
						}

						hoverPreview.addClass('reply').addClass('post')
							.insertAfter(link.parent())

						updatePreviewPosition(e.pageX, e.pageY, hoverPreview);
					}
				};

				post = $('[data-board="' + board + '"] div.post#reply_' + id + ', [data-board="' + board + '"]div#thread_' + id);
				if (post.length > 0) {
					startHover($(this));
				} else {
					let url = link.attr('href').replace(/#.*$/, '');

					if ($.inArray(url, dontFetchAgain) != -1) {
						return;
					}
					dontFetchAgain.push(url);

					$.ajax({
						url: url,
						context: document.body,
						success: function(data) {
							let mythreadid = $(data).find('div[id^="thread_"]').attr('id').replace("thread_", "");

							if (mythreadid == threadid && parentboard == board) {
								$(data).find('div.post.reply').each(function() {
									if ($('[data-board="' + board + '"] #' + $(this).attr('id')).length == 0) {
										$('[data-board="' + board + '"]#thread_' + threadid + " .post.reply:first").before($(this).hide().addClass('hidden'));
									}
								});
							} else if ($('[data-board="' + board + '"]#thread_' + mythreadid).length > 0) {
								$(data).find('div.post.reply').each(function() {
									if ($('[data-board="' + board + '"] #' + $(this).attr('id')).length == 0) {
										$('[data-board="' + board + '"]#thread_' + mythreadid + " .post.reply:first").before($(this).hide().addClass('hidden'));
									}
								});
							} else {
								$(data).find('div[id^="thread_"]').hide().attr('data-cached', 'yes').prependTo('form[name="postcontrols"]');
							}

							post = $('[data-board="' + board + '"] div.post#reply_' + id + ', [data-board="' + board + '"]div#thread_' + id);

							if (hovering && post.length > 0) {
								startHover(link);
							}
						}
					});
				}
			}, function() {
				// Remove the preview.

				hovering = false;
				if (!post) {
					return;
				}

				post.removeClass('highlighted');
				if (post.hasClass('hidden') || post.data('cached') == 'yes') {
					post.css('display', 'none');
				}
				$('.post-hover').remove();
			}).mousemove(function(e) {
				// Update the preview position if the mouse moves.

				if (!post) {
					return;
				}

				// The actual displayed preview.
				let hoverPreview = $('#post-hover-' + id + '[data-board="' + board + '"]');
				if (hoverPreview.length === 0) {
					return;
				}

				updatePreviewPosition(e.pageX, e.pageY, hoverPreview);
			});
		};

		$('div.body a:not([rel="nofollow"])').each(initHover);

		// allow to work with auto-reload.js, etc.
		$(document).on('new_post', function(e, post) {
			$(post).find('div.body a:not([rel="nofollow"])').each(initHover);
		});
	});
}
