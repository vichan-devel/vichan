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

	init_hover = function() {
		var link = $(this);

		var id;
		var matches;

		if (link.is('[data-thread]')) {
				id = link.attr('data-thread');
		}
		else if(matches = link.text().match(/^>>(?:>\/([^\/]+)\/)?(\d+)$/)) {
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
		if (link.is('[data-thread]')) threadid = 0;
		else threadid = board.attr('id').replace("thread_", "");

		board = board.data('board');

		var parentboard = board;
		
		if (link.is('[data-thread]')) parentboard = $('form[name="post"] input[name="board"]').val();
		else if (matches[1] !== undefined) board = matches[1];

		var post = false;
		var hovering = false;
		link.hover(function(e) {
			hovering = true;
			var start_hover = function(link) {
				if(post.is(':visible') &&
						post.offset().top >= $(window).scrollTop() &&
						post.offset().top + post.height() <= $(window).scrollTop() + $(window).height()) {
					// post is in view
					post.addClass('highlighted');
				} else {
					var newPost = post.clone();
					newPost.find('>.reply, >br').remove();
					newPost.find('a.post_anchor').remove();

					newPost
						.attr('id', 'post-hover-' + id)
						.attr('data-board', board)
						.addClass('post-hover')
						.css('border-style', 'solid')
						.css('box-shadow', '1px 1px 1px #999')
						.css('display', 'block')
						.css('position', 'absolute')
						.css('font-style', 'normal')
						.css('z-index', '100')
						.css('left', '0')
						.css('margin-left', '')
						.addClass('reply').addClass('post')
						.appendTo(link.closest('div.post'));
						
					// shrink expanded images
					newPost.find('div.file img.post-image').css({'display': '', 'opacity': ''});
					newPost.find('div.file img.full-image').remove();
					
					// Highlight references to the current post
					if (link.hasClass('mentioned-'+id)) {
						var postLinks = newPost.find('div.body a:not([rel="nofollow"])');
						if (postLinks.length > 1) {
							var originalPost = link.closest('div.post').attr('id').replace("reply_", "").replace("inline_", "");
							postLinks.each(function() {
								if ($(this).text() == ">>"+originalPost) {
									$(this).addClass('dashed-underline');
								}
							});
						}
					}
					
					var previewWidth = newPost.outerWidth(true);
					var widthDiff = previewWidth - newPost.width();
					var linkLeft = link.offset().left;
					var left, top;
					
					if (linkLeft < $(document).width() * 0.7) {
						left = linkLeft + link.width();
						if (left + previewWidth > $(window).width()) {
							newPost.css('width', $(window).width() - left - widthDiff);
						}
					} else {
						if (previewWidth > linkLeft) {
							newPost.css('width', linkLeft - widthDiff);
							previewWidth = linkLeft;
						}
						left = linkLeft - previewWidth;
					}
					newPost.css('left', left);
					
					top = link.offset().top - 10;
					
					var scrollTop = $(window).scrollTop();
					if (link.is("[data-thread]")) {
						scrollTop = 0;
						top -= $(window).scrollTop();	
					}
					
					if(top < scrollTop + 15) {
						top = scrollTop;
					} else if(top > scrollTop + $(window).height() - newPost.height() - 15) {
						top = scrollTop + $(window).height() - newPost.height() - 15;
					}
					
					if (newPost.height() > $(window).height()) {
						top = scrollTop;
					}
					
					newPost.css('top', top);
				}
			};
			
			
			post = $('[data-board="' + board + '"] div.post#reply_' + id + ', [data-board="' + board + '"]div#thread_' + id);
			if(post.length > 0) {
				start_hover($(this));
			} else {
				var url = link.attr('href').replace(/#.*$/, '').replace('.html', '.json');
				var dataPromise = getPost(id, url);

				dataPromise.done(function (data) {
					//	reconstruct post from json response
					var file_array = [];
					var multifile = false;

					var add_info = function (data) {
						var file = {
							'thumb_h': data.tn_h,
							'thumb_w': data.tn_w,
							'fsize': data.fsize,
							'filename': data.filename,
							'ext': data.ext,
							'tim': data.tim
						};

						if ('h' in data) {
							file.isImage = true; //(or video)
							file.h = data.h;
							file.w = data.w;
						} else {
							file.isImage = false;
						}
						// since response doens't indicate spoilered files,
						// we'll just make do by assuming any image with 128*128px thumbnail is spoilered.
						// which is probably 99% of the cases anyway.
						file.isSpoiler = (data.tn_h == 128 && data.tn_w == 128);

						file_array.push(file);
					};

					var bytesToSize = function (bytes) {
						var sizes = ['Bytes', 'KB', 'MB'];
						var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));

						return (i === 0) ? bytes +' '+ sizes[i] : (bytes / Math.pow(1024, i)).toFixed(2) +' ' +sizes[i];
					};

					var time = (!localStorage.show_relative_time || localStorage.show_relative_time === 'false') ? dateformat(new Date(data.time*1000)) : timeDifference(Date.now(), data.time*1000);
					var $post = $('<div class="post reply hidden" id="reply_'+ data.no +'">')
								.append($('<p class="intro"></p>')
									.append('<span class="name">'+ data.name +'</span> ')
									.append('<time datetime="'+ new Date(data.time*1000).toISOString() +'">'+ time +'</time>')
									.append('<a class="post_no"> No.'+ data.no +'</a>')
								)
								.append($('<div class="body"></div>')
									.html(data.com)
								)
								.css('display', 'none');

					//	other stuff
					if ('sub' in data) $post.find('.intro').prepend('<span class="subject">'+ data.sub +'</span> ');
					if ('trip' in data) $post.find('.name').after('<span class="trip">'+ data.trip +'</span>');
					if ('capcode' in data) $post.find('.post_no').before('<span class="capcode">## '+ data.capcode +'</span>');
					if ('id' in data) $post.find('.post_no').before('<span class="poster_id">'+ data.id +'</span>');
					if ('embed' in data) $post.find('p.intro').after(data.embed);

					if ('filename' in data) {
						var $files = $('<div class="files">');

						add_info(data);
						if ('extra_files' in data) {
							multifile = true;
							$.each(data.extra_files, function () {
								add_info(this);
							});
						}

						$.each(file_array, function () {
							var thumb_url;
							var file_ext = this.ext;

							if (this.isImage && !this.isSpoiler) {

								// at some point around 28/29th of Jan, 2015; all the newly  generated thumbnails switched to using jpg
								// this is a quick hack to ensure that external preview (mostly) works with old and new posts

									// Note: please update if a more accurate timestamp is known 
								if (data.last_modified > 1422489600) {
									this.ext = '.jpg';
								} else {
									if (this.ext === '.webm' || this.ext === '.mp4' || this.ext === '.jpeg') {
										this.ext = '.jpg';
									}
								}

								thumb_url = '/'+ board +'/thumb/' + this.tim + this.ext;

							} else {
								thumb_url = (this.isSpoiler) ? '/static/spoiler.png' : '/static/file.png';
							}

							// truncate long filenames
							if (this.filename.length > 23) {
								this.filename = this.filename.substr(0, 22) + '…';
							}

							// file infos
							var $ele = $('<div class="file">')
										.append($('<p class="fileinfo">')
											.append('<span>File: </span>')
											.append('<a>'+ this.filename + file_ext +'</a>')
											.append('<span class="unimportant"> ('+ bytesToSize(this.fsize) +', '+ this.w +'x'+ this.h +')</span>')
										);
							if (multifile) $ele.addClass('multifile').css('width', this.thumb_w + 30);

							// image
							var $img = $('<img class="post-image">')
												.css('width', this.thumb_w)
												.css('height', this.thumb_h)
												.attr('src', thumb_url);

							$ele.append($img);
							$files.append($ele);
						});
						
						$post.children('p.intro').after($files);

					}

					var mythreadid = (data.resto !== 0) ? data.resto : data.no;

					if (mythreadid != threadid || parentboard != board) {
						// previewing post from external thread/board
						if ($('div#thread_'+ mythreadid +'[data-board="'+ board +'"]').length === 0) {
							$('form[name="postcontrols"]').prepend('<div class="thread" id="thread_'+ mythreadid +'" data-board="'+ board +'" style="display: none;"></div>');
						}
					}
					if ($('div#thread_'+ mythreadid +'[data-board="'+ board +'"]').children('#reply_'+ data.no).length === 0) {
						$('div#thread_'+ mythreadid +'[data-board="'+ board +'"]').prepend($post);
					}

					post = $('[data-board="' + board + '"] div.post#reply_' + id + ', [data-board="' + board + '"]div#thread_' + id);
					if (hovering && post.length > 0) {
						start_hover(link);
					}
				});
			}
		}, function() {
			hovering = false;
			if(!post)
				return;
			
			post.removeClass('highlighted');
			if(post.hasClass('hidden'))
				post.css('display', 'none');
			$('.post-hover').remove();
		});
	};

	var getPost = (function () {
		var cache = {};
		return function (targetId, url) {
			var deferred = $.Deferred();
			var data, post;

			var findPost = function (targetId, data) {
				var arr = data.posts;
				for (var i=0; i<arr.length; i++) {
					if (arr[i].no == targetId)
						return arr[i];
				}
				return false;
			};
			var get = function (targetId, url) {
				$.ajax({
					url: url,
					success: function (response) {
						cache[url] = response;
						var post = findPost(targetId, response);
						deferred.resolve(post);
					}
				});
			};

			//	check for cached response and check if it's stale
			if ((data = cache[url]) !== undefined && (post = findPost(targetId, data))) {
				deferred.resolve(post);
			} else {
				get(targetId, url);
			}
			return deferred.promise();
		};
	})();

	var zeropad = function(num, count) {
		return [Math.pow(10, count - num.toString().length), num].join('').substr(1);
	};

	var dateformat = (typeof strftime === 'undefined') ? function(t) {
		return zeropad(t.getMonth() + 1, 2) + "/" + zeropad(t.getDate(), 2) + "/" + t.getFullYear().toString().substring(2) +
				" (" + [_("Sun"), _("Mon"), _("Tue"), _("Wed"), _("Thu"), _("Fri"), _("Sat"), _("Sun")][t.getDay()]  + ") " +
				// time
				zeropad(t.getHours(), 2) + ":" + zeropad(t.getMinutes(), 2) + ":" + zeropad(t.getSeconds(), 2);

	} : function(t) {
		// post_date is defined in templates/main.js
		return strftime(window.post_date, t, datelocale);
	};

	function timeDifference(current, previous) {

		var msPerMinute = 60 * 1000;
		var msPerHour = msPerMinute * 60;
		var msPerDay = msPerHour * 24;
		var msPerMonth = msPerDay * 30;
		var msPerYear = msPerDay * 365;

		var elapsed = current - previous;

		if (elapsed < msPerMinute) {
			return 'Just now';
		} else if (elapsed < msPerHour) {
			return Math.round(elapsed/msPerMinute) + (Math.round(elapsed/msPerMinute)<=1 ? ' minute ago':' minutes ago');
		} else if (elapsed < msPerDay ) {
			return Math.round(elapsed/msPerHour ) + (Math.round(elapsed/msPerHour)<=1 ? ' hour ago':' hours ago');
		} else if (elapsed < msPerMonth) {
			return Math.round(elapsed/msPerDay) + (Math.round(elapsed/msPerDay)<=1 ? ' day ago':' days ago');
		} else if (elapsed < msPerYear) {
			return Math.round(elapsed/msPerMonth) + (Math.round(elapsed/msPerMonth)<=1 ? ' month ago':' months ago');
		} else {
			return Math.round(elapsed/msPerYear ) + (Math.round(elapsed/msPerYear)<=1 ? ' year ago':' years ago');
		}
	}
	
	$('div.body a:not([rel="nofollow"])').each(init_hover);
	
	// allow to work with auto-reload.js, etc.
	$(document).on('new_post', function(e, post) {
		$(post).find('div.body a:not([rel="nofollow"])').each(init_hover);
	});
});
