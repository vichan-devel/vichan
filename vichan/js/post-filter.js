if (active_page === 'thread' || active_page === 'index' || active_page === 'catalog' || active_page === 'ukko') {
	$(document).on('menu_ready', function () {
		'use strict';
		
		// returns blacklist object from storage
		function getList() {
			return JSON.parse(localStorage.postFilter);
		}

		// stores blacklist into storage and reruns the filter
		function setList(blacklist) {
			localStorage.postFilter = JSON.stringify(blacklist);
			$(document).trigger('filter_page');
		}

		// unit: seconds
		function timestamp() {
			return Math.floor((new Date()).getTime() / 1000);
		}

		function initList(list, boardId, threadId) {
			if (typeof list.postFilter[boardId] == 'undefined') {
				list.postFilter[boardId] = {};
				list.nextPurge[boardId] = {};
			}
			if (typeof list.postFilter[boardId][threadId] == 'undefined') {
				list.postFilter[boardId][threadId] = [];
			}
			list.nextPurge[boardId][threadId] = {timestamp: timestamp(), interval: 86400};  // 86400 seconds == 1 day
		}

		function addFilter(type, value, useRegex) {
			var list = getList();
			var filter = list.generalFilter;
			var obj = {
				type: type,
				value: value,
				regex: useRegex
			};

			for (var i=0; i<filter.length; i++) {
				if (filter[i].type == type && filter[i].value == value && filter[i].regex == useRegex)
					return;
			}

			filter.push(obj);
			setList(list);
			drawFilterList();
		}

		function removeFilter(type, value, useRegex) {
			var list = getList();
			var filter = list.generalFilter;

			for (var i=0; i<filter.length; i++) {
				if (filter[i].type == type && filter[i].value == value && filter[i].regex == useRegex) {
					filter.splice(i, 1);
					break;
				}
			}

			setList(list);
			drawFilterList();
		}

		function nameSpanToString(el) {
			var s = ''; 

			$.each($(el).contents(), function(k,v) {
				if (v.nodeName === 'IMG')
					s=s+$(v).attr('alt')
				
				if (v.nodeName === '#text')
					s=s+v.nodeValue
			});
			return s.trim();
		}

		var blacklist = {
			add: {
				post: function (boardId, threadId, postId, hideReplies) {
					var list = getList();
					var filter = list.postFilter;

					initList(list, boardId, threadId);

					for (var i in filter[boardId][threadId]) {
						if (filter[boardId][threadId][i].post == postId) return;
					}
					filter[boardId][threadId].push({
						post: postId,
						hideReplies: hideReplies
					});
					setList(list);
				},
				uid: function (boardId, threadId, uniqueId, hideReplies) {
					var list = getList();
					var filter = list.postFilter;

					initList(list, boardId, threadId);

					for (var i in filter[boardId][threadId]) {
						if (filter[boardId][threadId][i].uid == uniqueId) return;
					}
					filter[boardId][threadId].push({
						uid: uniqueId,
						hideReplies: hideReplies
					});
					setList(list);
				}
			},
			remove: {
				post: function (boardId, threadId, postId) {
					var list = getList();
					var filter = list.postFilter;

					// thread already pruned
					if (typeof filter[boardId] == 'undefined' || typeof filter[boardId][threadId] == 'undefined')
						return;

					for (var i=0; i<filter[boardId][threadId].length; i++) {
						if (filter[boardId][threadId][i].post == postId) {
							filter[boardId][threadId].splice(i, 1);
							break;
						}
					}

					if ($.isEmptyObject(filter[boardId][threadId])) {
						delete filter[boardId][threadId];
						delete list.nextPurge[boardId][threadId];

						if ($.isEmptyObject(filter[boardId])) {
							delete filter[boardId];
							delete list.nextPurge[boardId];
						}
					}
					setList(list);
				},
				uid: function (boardId, threadId, uniqueId) {
					var list = getList();
					var filter = list.postFilter;

					// thread already pruned
					if (typeof filter[boardId] == 'undefined' || typeof filter[boardId][threadId] == 'undefined')
						return;

					for (var i=0; i<filter[boardId][threadId].length; i++) {
						if (filter[boardId][threadId][i].uid == uniqueId) {
							filter[boardId][threadId].splice(i, 1);
							break;
						}
					}

					if ($.isEmptyObject(filter[boardId][threadId])) {
						delete filter[boardId][threadId];
						delete list.nextPurge[boardId][threadId];

						if ($.isEmptyObject(filter[boardId])) {
							delete filter[boardId];
							delete list.nextPurge[boardId];
						}
					}
					setList(list);
				}
			}
		};

		/* 
		 *  hide/show the specified thread/post
		 */
		function hide(ele) {
			var $ele = $(ele);

			if ($(ele).data('hidden'))
				return;

			$(ele).data('hidden', true);
			if ($ele.hasClass('op')) {
				$ele.parent().find('.body, .files, .video-container').not($ele.children('.reply').children()).hide();

				// hide thread replies on index view
				if (active_page == 'index' || active_page == 'ukko') $ele.parent().find('.omitted, .reply:not(.hidden), post_no, .mentioned, br').hide();
			} else {
				// normal posts
				$ele.children('.body, .files, .video-container').hide();
			}
		}
		function show(ele) {
			var $ele = $(ele);

			$(ele).data('hidden', false);
			if ($ele.hasClass('op')) {
				$ele.parent().find('.body, .files, .video-container').show();
				if (active_page == 'index') $ele.parent().find('.omitted, .reply:not(.hidden), post_no, .mentioned, br').show();
			} else {
				// normal posts
				$ele.children('.body, .files, .video-container').show();
			}
		}

		/* 
		 *  create filter menu when the button is clicked
		 */
		function initPostMenu(pageData) {
			var Menu = window.Menu;
			var submenu;
			Menu.add_item('filter-menu-hide', _('Hide post'));
			Menu.add_item('filter-menu-unhide', _('Unhide post'));

			submenu = Menu.add_submenu('filter-menu-add', _('Add filter'));
				submenu.add_item('filter-add-post-plus', _('Post +'), _('Hide post and all replies'));
				submenu.add_item('filter-add-id', _('ID'));
				submenu.add_item('filter-add-id-plus', _('ID +'), _('Hide ID and all replies'));
				submenu.add_item('filter-add-name', _('Name'));
				submenu.add_item('filter-add-trip', _('Tripcode'));

			submenu = Menu.add_submenu('filter-menu-remove', _('Remove filter'));
				submenu.add_item('filter-remove-id', _('ID'));
				submenu.add_item('filter-remove-name', _('Name'));
				submenu.add_item('filter-remove-trip', _('Tripcode'));

			Menu.onclick(function (e, $buffer) {
				var ele = e.target.parentElement.parentElement;
				var $ele = $(ele);

				var threadId = $ele.parent().attr('id').replace('thread_', '');
				var boardId = $ele.parent().data('board');
				var postId = $ele.find('.post_no').not('[id]').text();
				if (pageData.hasUID) {
					var postUid = $ele.find('.poster_id').text();
				}

				var postName;
				var postTrip = '';
				if (!pageData.forcedAnon) {
					postName = (typeof $ele.find('.name').contents()[0] == 'undefined') ? '' : nameSpanToString($ele.find('.name')[0]);
					postTrip = $ele.find('.trip').text();
				}

				/*  display logic and bind click handlers
				 */

				 // unhide button
				if ($ele.data('hidden')) {
					$buffer.find('#filter-menu-unhide').click(function () {
						//  if hidden due to post id, remove it from blacklist
						//  otherwise just show this post
						blacklist.remove.post(boardId, threadId, postId);
						show(ele);
					});
					$buffer.find('#filter-menu-hide').addClass('hidden');
				} else {
					$buffer.find('#filter-menu-unhide').addClass('hidden');
					$buffer.find('#filter-menu-hide').click(function () {
						blacklist.add.post(boardId, threadId, postId, false);
					});
				}

				//  post id
				if (!$ele.data('hiddenByPost')) {
					$buffer.find('#filter-add-post-plus').click(function () {
						blacklist.add.post(boardId, threadId, postId, true);
					});
				} else {
					$buffer.find('#filter-add-post-plus').addClass('hidden');
				}

				// UID
				if (pageData.hasUID && !$ele.data('hiddenByUid')) {
					$buffer.find('#filter-add-id').click(function () {
						blacklist.add.uid(boardId, threadId, postUid, false);
					});
					$buffer.find('#filter-add-id-plus').click(function () {
						blacklist.add.uid(boardId, threadId, postUid, true);
					});

					$buffer.find('#filter-remove-id').addClass('hidden');
				} else if (pageData.hasUID) {
					$buffer.find('#filter-remove-id').click(function () {
						blacklist.remove.uid(boardId, threadId, postUid);
					});

					$buffer.find('#filter-add-id').addClass('hidden');
					$buffer.find('#filter-add-id-plus').addClass('hidden');
				} else {
					// board doesn't use UID
					$buffer.find('#filter-add-id').addClass('hidden');
					$buffer.find('#filter-add-id-plus').addClass('hidden');
					$buffer.find('#filter-remove-id').addClass('hidden');
				}

				//  name
				if (!pageData.forcedAnon && !$ele.data('hiddenByName')) {
					$buffer.find('#filter-add-name').click(function () {
						addFilter('name', postName, false);
					});

					$buffer.find('#filter-remove-name').addClass('hidden');
				} else if (!pageData.forcedAnon) {
					$buffer.find('#filter-remove-name').click(function () {
						removeFilter('name', postName, false);
					});

					$buffer.find('#filter-add-name').addClass('hidden');
				} else {
					// board has forced anon
					$buffer.find('#filter-remove-name').addClass('hidden');
					$buffer.find('#filter-add-name').addClass('hidden');
				}

				//  tripcode
				if (!pageData.forcedAnon && !$ele.data('hiddenByTrip') && postTrip !== '') {
					$buffer.find('#filter-add-trip').click(function () {
						addFilter('trip', postTrip, false);
					});

					$buffer.find('#filter-remove-trip').addClass('hidden');
				} else if (!pageData.forcedAnon && postTrip !== '') {
					$buffer.find('#filter-remove-trip').click(function () {
						removeFilter('trip', postTrip, false);
					});

					$buffer.find('#filter-add-trip').addClass('hidden');
				} else {
					// board has forced anon
					$buffer.find('#filter-remove-trip').addClass('hidden');
					$buffer.find('#filter-add-trip').addClass('hidden');
				}

				/*  hide sub menus if all items are hidden
				 */
				if (!$buffer.find('#filter-menu-remove > ul').children().not('.hidden').length) {
					$buffer.find('#filter-menu-remove').addClass('hidden');
				}
				if (!$buffer.find('#filter-menu-add > ul').children().not('.hidden').length) {
					$buffer.find('#filter-menu-add').addClass('hidden');
				}
			});
		}

		/* 
		 *  hide/unhide thread on index view
		 */
		function quickToggle(ele, threadId, pageData) {
			/*if ($(ele).find('.hide-thread-link').length)
				$('.hide-thread-link').remove();*/

			if ($(ele).hasClass('op') && !$(ele).find('.hide-thread-link').length) {
				$('<a class="hide-thread-link" style="float:left;margin-right:5px" href="javascript:void(0)">[' + ($(ele).data('hidden') ? '+' : '&ndash;') + ']</a>')
					.insertBefore($(ele).find(':not(h2,h2 *):first'))
					.click(function() {
						var postId = $(ele).find('.post_no').not('[id]').text();
						var hidden = $(ele).data('hidden');
						var boardId = $(ele).parents('.thread').data('board');
					
						if (hidden) {
							blacklist.remove.post(boardId, threadId, postId, false);
							$(this).html('[&ndash;]');
						} else {
							blacklist.add.post(boardId, threadId, postId, false);
							$(this).text('[+]');
						}
					});
			}
		}

		/*
		 *  determine whether the reply post should be hidden
		 *   - applies to all posts on page load or filtering rule change
		 *   - apply to new posts on thread updates
		 *   - must explicitly set the state of each attributes because filter will reapply to all posts after filtering rule change
		 */
		function filter(post, threadId, pageData) {
			var $post = $(post);

			var list = getList();
			var postId = $post.find('.post_no').not('[id]').text();
			var name, trip, uid, subject, comment;
			var i, length, array, rule, pattern;  // temp variables

			var boardId	      = $post.data('board');
			if (!boardId) boardId = $post.parents('.thread').data('board');

			var localList   = pageData.localList;
			var noReplyList = pageData.noReplyList;
			var hasUID      = pageData.hasUID;
			var forcedAnon  = pageData.forcedAnon;

			var hasTrip = ($post.find('.trip').length > 0);
			var hasSub = ($post.find('.subject').length > 0);

			$post.data('hidden', false);
			$post.data('hiddenByUid', false);
			$post.data('hiddenByPost', false);
			$post.data('hiddenByName', false);
			$post.data('hiddenByTrip', false);
			$post.data('hiddenBySubject', false);
			$post.data('hiddenByComment', false);

			// add post with matched UID to localList
			if (hasUID &&
				typeof list.postFilter[boardId] != 'undefined' &&
				typeof list.postFilter[boardId][threadId] != 'undefined') {
				uid = $post.find('.poster_id').text();
				array = list.postFilter[boardId][threadId];

				for (i=0; i<array.length; i++) {
					if (array[i].uid == uid) {
						$post.data('hiddenByUid', true);
						localList.push(postId);
						if (array[i].hideReplies) noReplyList.push(postId);
						break;
					}
				}
			}

			// match localList
			if (localList.length) {
				if ($.inArray(postId, localList) != -1) {
					if ($post.data('hiddenByUid') !== true) $post.data('hiddenByPost', true);
					hide(post);
				}
			}

			// matches generalFilter
			if (!forcedAnon)
				name = (typeof $post.find('.name').contents()[0] == 'undefined') ? '' : nameSpanToString($post.find('.name')[0]);
			if (!forcedAnon && hasTrip)
				trip = $post.find('.trip').text();
			if (hasSub)
				subject = $post.find('.subject').text();

			array = $post.find('.body').contents().filter(function () {if ($(this).text() !== '') return true;}).toArray();
			array = $.map(array, function (ele) {
				return $(ele).text().trim();
			});
			comment = array.join(' ');


			for (i = 0, length = list.generalFilter.length; i < length; i++) {
				rule = list.generalFilter[i];

				if (rule.regex) {
					pattern = new RegExp(rule.value);
					switch (rule.type) {
						case 'name':
							if (!forcedAnon && pattern.test(name)) {
								$post.data('hiddenByName', true);
								hide(post);
							}
							break;
						case 'trip':
							if (!forcedAnon && hasTrip && pattern.test(trip)) {
								$post.data('hiddenByTrip', true);
								hide(post);
							}
							break;
						case 'sub':
							if (hasSub && pattern.test(subject)) {
								$post.data('hiddenBySubject', true);
								hide(post);
							}
							break;
						case 'com':
							if (pattern.test(comment)) {
								$post.data('hiddenByComment', true);
								hide(post);
							}
							break;
					}
				} else {
					switch (rule.type) {
						case 'name':
							if (!forcedAnon && rule.value == name) {
								$post.data('hiddenByName', true);
								hide(post);
							}
							break;
						case 'trip':
							if (!forcedAnon && hasTrip && rule.value == trip) {
								$post.data('hiddenByTrip', true);
								hide(post);
							}
							break;
						case 'sub':
							pattern = new RegExp('\\b'+ rule.value+ '\\b');
							if (hasSub && pattern.test(subject)) {
								$post.data('hiddenBySubject', true);
								hide(post);
							}
							break;
						case 'com':
							pattern = new RegExp('\\b'+ rule.value+ '\\b');
							if (pattern.test(comment)) {
								$post.data('hiddenByComment', true);
								hide(post);
							}
							break;
					}
				}
			}

			// check for link to filtered posts
			$post.find('.body a').not('[rel="nofollow"]').each(function () {
				var replyId = $(this).text().match(/^>>(\d+)$/);

				if (!replyId)
					return;

				replyId = replyId[1];
				if ($.inArray(replyId, noReplyList) != -1) {
					hide(post);
				}
			});

			// post didn't match any filters
			if (!$post.data('hidden')) {
				show(post);
			}
		}

		/*  (re)runs the filter on the entire page
		 */
		 function filterPage(pageData) {
			var list = getList();

			if (active_page != 'catalog') {

				// empty the local and no-reply list
				pageData.localList = [];
				pageData.noReplyList = [];

				$('.thread').each(function () {
					var $thread = $(this);
					// disregard the hidden threads constructed by post-hover.js
					if ($thread.css('display') == 'none')
						return;

					var threadId = $thread.attr('id').replace('thread_', '');
					var boardId = $thread.data('board');
					var op = $thread.children('.op')[0];
					var i, array;  // temp variables

					// add posts to localList and noReplyList
					if (typeof list.postFilter[boardId] != 'undefined' && typeof list.postFilter[boardId][threadId] != 'undefined') {
						array = list.postFilter[boardId][threadId];
						for (i=0; i<array.length; i++) {
							if ( typeof array[i].post == 'undefined')
								continue;

							pageData.localList.push(array[i].post);
							if (array[i].hideReplies) pageData.noReplyList.push(array[i].post);
						}
					}
					// run filter on OP
					filter(op, threadId, pageData);
					quickToggle(op, threadId, pageData);

					// iterate filter over each post
					if (!$(op).data('hidden') || active_page == 'thread') {
						$thread.find('.reply').not('.hidden').each(function () {
							filter(this, threadId, pageData);
						});
					}

				});
			} else {
				var postFilter = list.postFilter[pageData.boardId];
				var $collection = $('.mix');

				if ($.isEmptyObject(postFilter))
					return;

				// for each thread that has filtering rules
				// check if filter contains thread OP and remove the thread from catalog
				$.each(postFilter, function (key, thread) {
					var threadId = key;
					$.each(thread, function () {
						if (this.post == threadId) {
							$collection.filter('[data-id='+ threadId +']').remove();
						}
					});
				});
			}
		 }

		function initStyle() {
			var $ele, cssStyle, cssString;

			$ele = $('<div>').addClass('post reply').hide().appendTo('body');
			cssStyle = $ele.css(['background-color', 'border-color']);
			cssStyle.hoverBg = $('body').css('background-color');
			$ele.remove();

			cssString = '\n/*** Generated by post-filter ***/\n' +
				'#filter-control input[type=text] {width: 130px;}' +
				'#filter-control input[type=checkbox] {vertical-align: middle;}' +
				'#filter-control #clear {float: right;}\n' +
				'#filter-container {margin-top: 20px; border: 1px solid; height: 270px; overflow: auto;}\n' +
				'#filter-list {width: 100%; border-collapse: collapse;}\n' +
				'#filter-list th {text-align: center; height: 20px; font-size: 14px; border-bottom: 1px solid;}\n' +
				'#filter-list th:nth-child(1) {text-align: center; width: 70px;}\n' +
				'#filter-list th:nth-child(2) {text-align: left;}\n' +
				'#filter-list th:nth-child(3) {text-align: center; width: 58px;}\n' +
				'#filter-list tr:not(#header) {height: 22px;}\n' +
				'#filter-list tr:nth-child(even) {background-color:rgba(255, 255, 255, 0.5);}\n' +
				'#filter-list td:nth-child(1) {text-align: center; width: 70px;}\n' +
				'#filter-list td:nth-child(3) {text-align: center; width: 58px;}\n' +
				'#confirm {text-align: right; margin-bottom: -18px; padding-top: 2px; font-size: 14px; color: #FF0000;}';

			if (!$('style.generated-css').length) $('<style class="generated-css">').appendTo('head');
			$('style.generated-css').html($('style.generated-css').html() + cssString);
		}

		function drawFilterList() {
			var list = getList().generalFilter;
			var $ele = $('#filter-list');
			var $row, i, length, obj, val;

			var typeName = {
				name: 'name',
				trip: 'tripcode',
				sub: 'subject',
				com: 'comment'
			};

			$ele.empty();

			$ele.append('<tr id="header"><th>Type</th><th>Content</th><th>Remove</th></tr>');
			for (i = 0, length = list.length; i < length; i++) {
				obj = list[i];

				// display formatting
				val = (obj.regex) ? '/'+ obj.value +'/' : obj.value;

				$row = $('<tr>');
				$row.append(
					'<td>'+ typeName[obj.type] +'</td>',
					'<td>'+ val +'</td>',
					$('<td>').append(
						$('<a>').html('X')
							.addClass('del-btn')
							.attr('href', '#')
							.data('type', obj.type)
							.data('val', obj.value)
							.data('useRegex', obj.regex)
					)
				);
				$ele.append($row);
			}
		}

		function initOptionsPanel() {
			if (window.Options && !Options.get_tab('filter')) {
				Options.add_tab('filter', 'list', _('Filters'));
				Options.extend_tab('filter',
					'<div id="filter-control">' +
						'<select>' +
							'<option value="name">'+_('Name')+'</option>' +
							'<option value="trip">'+_('Tripcode')+'</option>' +
							'<option value="sub">'+_('Subject')+'</option>' +
							'<option value="com">'+_('Comment')+'</option>' +
						'</select>' +
						'<input type="text">' +
						'<input type="checkbox">' +
						'regex ' +
						'<button id="set-filter">'+_('Add')+'</button>' +
						'<button id="clear">'+_('Clear all filters')+'</button>' +
						'<div id="confirm" class="hidden">' +
							_('This will clear all filtering rules including hidden posts.')+' <a id="confirm-y" href="#">'+_('yes')+'</a> | <a id="confirm-n" href="#">'+_('no')+'</a>' +
						'</div>' +
					'</div>' +
					'<div id="filter-container"><table id="filter-list"></table></div>'
				);
				drawFilterList();

				// control buttons
				$('#filter-control').on('click', '#set-filter', function () {
					var type = $('#filter-control select option:selected').val();
					var value = $('#filter-control input[type=text]').val();
					var useRegex = $('#filter-control input[type=checkbox]').prop('checked');

					//clear the input form
					$('#filter-control input[type=text]').val('');

					addFilter(type, value, useRegex);
					drawFilterList();
				});
				$('#filter-control').on('click', '#clear', function () {
					$('#filter-control #clear').addClass('hidden');
					$('#filter-control #confirm').removeClass('hidden');
				});
				$('#filter-control').on('click', '#confirm-y', function (e) {
					e.preventDefault();

					$('#filter-control #clear').removeClass('hidden');
					$('#filter-control #confirm').addClass('hidden');
					setList({
						generalFilter: [],
						postFilter: {},
						nextPurge: {},
						lastPurge: timestamp()
					});
					drawFilterList();
				});
				$('#filter-control').on('click', '#confirm-n', function (e) {
					e.preventDefault();

					$('#filter-control #clear').removeClass('hidden');
					$('#filter-control #confirm').addClass('hidden');
				});


				// remove button
				$('#filter-list').on('click', '.del-btn', function (e) {
					e.preventDefault();

					var $ele = $(e.target);
					var type = $ele.data('type');
					var val = $ele.data('val');
					var useRegex = $ele.data('useRegex');

					removeFilter(type, val, useRegex);
				});
			}
		}

		/* 
		 *  clear out pruned threads
		 */
		function purge() {
			var list = getList();
			var board, thread, boardId, threadId;
			var deferred;
			var requestArray = [];

			var successHandler = function (boardId, threadId) {
				return function () {
					// thread still alive, keep it in the list and increase the time between checks.
					var list = getList();
					var thread = list.nextPurge[boardId][threadId];

					thread.timestamp = timestamp();
					thread.interval = Math.floor(thread.interval * 1.5);
					setList(list);
				};
			};
			var errorHandler = function (boardId, threadId) {
				return function (xhr) {
					if (xhr.status == 404) {
						var list = getList();

						delete list.nextPurge[boardId][threadId];
						delete list.postFilter[boardId][threadId];
						if ($.isEmptyObject(list.nextPurge[boardId])) delete list.nextPurge[boardId];
						if ($.isEmptyObject(list.postFilter[boardId])) delete list.postFilter[boardId];
						setList(list);
					}
				};
			};

			if ((timestamp() - list.lastPurge) < 86400)  // less than 1 day
				return;
			
			for (boardId in list.nextPurge) {
				board = list.nextPurge[boardId];
				for (threadId in board) {
					thread = board[threadId];
					if (timestamp() > (thread.timestamp + thread.interval)) {
						// check if thread is pruned
						deferred = $.ajax({
							cache: false,
							url: '/'+ boardId +'/res/'+ threadId +'.json',
							success: successHandler(boardId, threadId),
							error: errorHandler(boardId, threadId)
						});
						requestArray.push(deferred);
					}
				}
			}

			// when all requests complete
			$.when.apply($, requestArray).always(function () {
				var list = getList();
				list.lastPurge = timestamp();
				setList(list);
			});
		}

		function init() {
			if (typeof localStorage.postFilter === 'undefined') {
				localStorage.postFilter = JSON.stringify({
					generalFilter: [],
					postFilter: {},
					nextPurge: {},
					lastPurge: timestamp()
				});
			}

			var pageData = {
				boardId: board_name,  // get the id from the global variable
				localList: [],  // all the blacklisted post IDs or UIDs that apply to the current page
				noReplyList: [],  // any posts that replies to the contents of this list shall be hidden
				hasUID: (document.getElementsByClassName('poster_id').length > 0),
				forcedAnon: ($('th:contains(Name)').length === 0)  // tests by looking for the Name label on the reply form
			};

			initStyle();
			initOptionsPanel();
			initPostMenu(pageData);
			filterPage(pageData);

			// on new posts
			$(document).on('new_post', function (e, post) {
				var threadId;

				if ($(post).hasClass('reply')) {
					threadId = $(post).parents('.thread').attr('id').replace('thread_', '');
				} else {
					threadId = $(post).attr('id').replace('thread_', '');
					post = $(post).children('.op')[0];
				}

				filter(post, threadId, pageData);
				quickToggle(post, threadId, pageData);
			});

			$(document).on('filter_page', function () {
				filterPage(pageData);
			});

			// shift+click on catalog to hide thread
			if (active_page == 'catalog') {
				$(document).on('click', '.mix', function(e) {
					if (e.shiftKey) {
						var threadId = $(this).data('id').toString();
						var postId = threadId;
						blacklist.add.post(pageData.boardId, threadId, postId, false);
					}
				});
			}

			// clear out the old threads
			purge();
		}
		init();
	});
	
	if (typeof window.Menu !== "undefined") {
		$(document).trigger('menu_ready');
	}
}
