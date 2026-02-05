if (active_page === 'thread' || active_page === 'index' || active_page === 'catalog' || active_page === 'ukko') {
	$(document).on('menu_ready', () => {
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
			if (typeof list.postFilter[boardId] === 'undefined') {
				list.postFilter[boardId] = {};
				list.nextPurge[boardId] = {};
			}
			if (typeof list.postFilter[boardId][threadId] === 'undefined') {
				list.postFilter[boardId][threadId] = [];
			}
			list.nextPurge[boardId][threadId] = {timestamp: timestamp(), interval: 86400};  // 86400 seconds === 1 day
		}

		function addFilter(type, value, useRegex) {
			const list = getList();
			const filter = list.generalFilter;
			const obj = {
				type: type,
				value: value,
				regex: useRegex
			};

			for (var i=0; i<filter.length; i++) {
				if (filter[i].type === type && filter[i].value === value && filter[i].regex === useRegex)
					return;
			}

			filter.push(obj);
			setList(list);
			drawFilterList();
		}

		function removeFilter(type, value, useRegex) {
			const list = getList();
			const filter = list.generalFilter;

			for (var i=0; i<filter.length; i++) {
				if (filter[i].type === type && filter[i].value === value && filter[i].regex === useRegex) {
					filter.splice(i, 1);
					break;
				}
			}

			setList(list);
			drawFilterList();
		}

		function nameSpanToString(el) {
			const s = ''; 

			$.each($(el).contents(), function(k,v) {
				if (v.nodeName === 'IMG')
					s=s+$(v).getAttribute('alt')
				
				if (v.nodeName === '#text')
					s=s+v.nodeValue
			});
			return s.trim();
		}

		const blacklist = {
			add: {
				post: function (boardId, threadId, postId, hideReplies) {
					const list = getList();
					const filter = list.postFilter;

					initList(list, boardId, threadId);

					for (var i in filter[boardId][threadId]) {
						if (filter[boardId][threadId][i].post === postId) return;
					}
					filter[boardId][threadId].push({
						post: postId,
						hideReplies: hideReplies
					});
					setList(list);
				},
				uid: function (boardId, threadId, uniqueId, hideReplies) {
					const list = getList();
					const filter = list.postFilter;

					initList(list, boardId, threadId);

					for (var i in filter[boardId][threadId]) {
						if (filter[boardId][threadId][i].uid === uniqueId) return;
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
					const list = getList();
					const filter = list.postFilter;

					// thread already pruned
					if (typeof filter[boardId] === 'undefined' || typeof filter[boardId][threadId] === 'undefined')
						return;

					for (var i=0; i<filter[boardId][threadId].length; i++) {
						if (filter[boardId][threadId][i].post === postId) {
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
					const list = getList();
					const filter = list.postFilter;

					// thread already pruned
					if (typeof filter[boardId] === 'undefined' || typeof filter[boardId][threadId] === 'undefined')
						return;

					for (var i=0; i<filter[boardId][threadId].length; i++) {
						if (filter[boardId][threadId][i].uid === uniqueId) {
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
			const $ele = $(ele);

			if ($(ele).data('hidden'))
				return;

			$(ele).data('hidden', true);
			if ($ele.classList.contains('op')) {
				$ele.parent().querySelector('.body, .files, .video-container').not($ele.children('.reply').children()).style.display = 'none';

				// hide thread replies on index view
				if (active_page === 'index' || active_page === 'ukko') $ele.parent().querySelector('.omitted, .reply:not(.hidden), post_no, .mentioned, br').style.display = 'none';
			} else {
				// normal posts
				$ele.children('.body, .files, .video-container').style.display = 'none';
			}
		}
		function show(ele) {
			const $ele = $(ele);

			$(ele).data('hidden', false);
			if ($ele.classList.contains('op')) {
				$ele.parent().querySelector('.body, .files, .video-container').style.display = '';
				if (active_page === 'index') $ele.parent().querySelector('.omitted, .reply:not(.hidden), post_no, .mentioned, br').style.display = '';
			} else {
				// normal posts
				$ele.children('.body, .files, .video-container').style.display = '';
			}
		}

		/* 
		 *  create filter menu when the button is clicked
		 */
		function initPostMenu(pageData) {
			const Menu = window.Menu;
			const submenu;
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
				const ele = e.target.parentElement.parentElement;
				const $ele = $(ele);

				const threadId = $ele.parent().getAttribute('id').replace('thread_', '');
				const boardId = $ele.parent().data('board');
				const postId = $ele.querySelector('.post_no').not('[id]').textContent;
				if (pageData.hasUID) {
					const postUid = $ele.querySelector('.poster_id').textContent;
				}

				const postName;
				const postTrip = '';
				if (!pageData.forcedAnon) {
					postName = (typeof $ele.querySelector('.name').contents()[0] === 'undefined') ? '' : nameSpanToString($ele.querySelector('.name')[0]);
					postTrip = $ele.querySelector('.trip').textContent;
				}

				/*  display logic and bind click handlers
				 */

				 // unhide button
				if ($ele.data('hidden')) {
					$buffer.querySelector('#filter-menu-unhide').click(() => {
						//  if hidden due to post id, remove it from blacklist
						//  otherwise just show this post
						blacklist.remove.post(boardId, threadId, postId);
						show(ele);
					});
					$buffer.querySelector('#filter-menu-hide').classList.add('hidden');
				} else {
					$buffer.querySelector('#filter-menu-unhide').classList.add('hidden');
					$buffer.querySelector('#filter-menu-hide').click(() => {
						blacklist.add.post(boardId, threadId, postId, false);
					});
				}

				//  post id
				if (!$ele.data('hiddenByPost')) {
					$buffer.querySelector('#filter-add-post-plus').click(() => {
						blacklist.add.post(boardId, threadId, postId, true);
					});
				} else {
					$buffer.querySelector('#filter-add-post-plus').classList.add('hidden');
				}

				// UID
				if (pageData.hasUID && !$ele.data('hiddenByUid')) {
					$buffer.querySelector('#filter-add-id').click(() => {
						blacklist.add.uid(boardId, threadId, postUid, false);
					});
					$buffer.querySelector('#filter-add-id-plus').click(() => {
						blacklist.add.uid(boardId, threadId, postUid, true);
					});

					$buffer.querySelector('#filter-remove-id').classList.add('hidden');
				} else if (pageData.hasUID) {
					$buffer.querySelector('#filter-remove-id').click(() => {
						blacklist.remove.uid(boardId, threadId, postUid);
					});

					$buffer.querySelector('#filter-add-id').classList.add('hidden');
					$buffer.querySelector('#filter-add-id-plus').classList.add('hidden');
				} else {
					// board doesn't use UID
					$buffer.querySelector('#filter-add-id').classList.add('hidden');
					$buffer.querySelector('#filter-add-id-plus').classList.add('hidden');
					$buffer.querySelector('#filter-remove-id').classList.add('hidden');
				}

				//  name
				if (!pageData.forcedAnon && !$ele.data('hiddenByName')) {
					$buffer.querySelector('#filter-add-name').click(() => {
						addFilter('name', postName, false);
					});

					$buffer.querySelector('#filter-remove-name').classList.add('hidden');
				} else if (!pageData.forcedAnon) {
					$buffer.querySelector('#filter-remove-name').click(() => {
						removeFilter('name', postName, false);
					});

					$buffer.querySelector('#filter-add-name').classList.add('hidden');
				} else {
					// board has forced anon
					$buffer.querySelector('#filter-remove-name').classList.add('hidden');
					$buffer.querySelector('#filter-add-name').classList.add('hidden');
				}

				//  tripcode
				if (!pageData.forcedAnon && !$ele.data('hiddenByTrip') && postTrip !== '') {
					$buffer.querySelector('#filter-add-trip').click(() => {
						addFilter('trip', postTrip, false);
					});

					$buffer.querySelector('#filter-remove-trip').classList.add('hidden');
				} else if (!pageData.forcedAnon && postTrip !== '') {
					$buffer.querySelector('#filter-remove-trip').click(() => {
						removeFilter('trip', postTrip, false);
					});

					$buffer.querySelector('#filter-add-trip').classList.add('hidden');
				} else {
					// board has forced anon
					$buffer.querySelector('#filter-remove-trip').classList.add('hidden');
					$buffer.querySelector('#filter-add-trip').classList.add('hidden');
				}

				/*  hide sub menus if all items are hidden
				 */
				if (!$buffer.querySelector('#filter-menu-remove > ul').children().not('.hidden').length) {
					$buffer.querySelector('#filter-menu-remove').classList.add('hidden');
				}
				if (!$buffer.querySelector('#filter-menu-add > ul').children().not('.hidden').length) {
					$buffer.querySelector('#filter-menu-add').classList.add('hidden');
				}
			});
		}

		/* 
		 *  hide/unhide thread on index view
		 */
		function quickToggle(ele, threadId, pageData) {
			/*if ($(ele).querySelector('.hide-thread-link').length)
				document.querySelector('.hide-thread-link').remove();*/

			if ($(ele).classList.contains('op') && !$(ele).querySelector('.hide-thread-link').length) {
				$('<a class="hide-thread-link" style="float:left;margin-right:5px" href="javascript:void(0)">[' + ($(ele).data('hidden') ? '+' : '&ndash;') + ']</a>')
					.insertBefore($(ele).querySelector(':not(h2,h2 *):first'))
					.click(() => {
						const postId = $(ele).querySelector('.post_no').not('[id]').textContent;
						const hidden = $(ele).data('hidden');
						const boardId = $(ele).parents('.thread').data('board');
					
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
			const $post = $(post);

			const list = getList();
			const postId = $post.querySelector('.post_no').not('[id]').textContent;
			const name, trip, uid, subject, comment, flag;
			const i, length, array, rule, pattern;  // temp variables

			const boardId	      = $post.data('board');
			if (!boardId) boardId = $post.parents('.thread').data('board');

			const localList   = pageData.localList;
			const noReplyList = pageData.noReplyList;
			const hasUID      = pageData.hasUID;
			const forcedAnon  = pageData.forcedAnon;

			const hasTrip = ($post.querySelector('.trip').length > 0);
			const hasSub = ($post.querySelector('.subject').length > 0);
			const hasFlag = ($post.querySelector('.flag').length > 0);

			$post.data('hidden', false);
			$post.data('hiddenByUid', false);
			$post.data('hiddenByPost', false);
			$post.data('hiddenByName', false);
			$post.data('hiddenByTrip', false);
			$post.data('hiddenBySubject', false);
			$post.data('hiddenByComment', false);
			$post.data('hiddenByFlag', false);

			// add post with matched UID to localList
			if (hasUID &&
				typeof list.postFilter[boardId] != 'undefined' &&
				typeof list.postFilter[boardId][threadId] != 'undefined') {
				uid = $post.querySelector('.poster_id').textContent;
				array = list.postFilter[boardId][threadId];

				for (i=0; i<array.length; i++) {
					if (array[i].uid === uid) {
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
				name = (typeof $post.querySelector('.name').contents()[0] === 'undefined') ? '' : nameSpanToString($post.querySelector('.name')[0]);
			if (!forcedAnon && hasTrip)
				trip = $post.querySelector('.trip').textContent;
			if (hasSub)
				subject = $post.querySelector('.subject').textContent;

			array = $post.querySelector('.body').contents().filter(() => {if ($(this).textContent !== '') return true;}).toArray();
			array = $.map(array, (ele) => {
				return $(ele).textContent.trim();
			});
			comment = array.join(' ');

			if (hasFlag)
				flag = $post.querySelector('.flag').getAttribute('title')

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
						case 'flag':
							if (hasFlag && pattern.test(flag)) {
								$post.data('hiddenByFlag', true);
								hide(post);
							}
							break;
					}
				} else {
					switch (rule.type) {
						case 'name':
							if (!forcedAnon && rule.value === name) {
								$post.data('hiddenByName', true);
								hide(post);
							}
							break;
						case 'trip':
							if (!forcedAnon && hasTrip && rule.value === trip) {
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
						case 'flag':
							pattern = new RegExp('\\b'+ rule.value+ '\\b');
							if (hasFlag && pattern.test(flag)) {
								$post.data('hiddenByFlag', true);
								hide(post);
							}
							break;
					}
				}
			}

			// check for link to filtered posts
			$post.querySelector('.body a').not('[rel="nofollow"]').each(() => {
				const replyId = $(this).textContent.match(/^>>(\d+)$/);

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
			const list = getList();

			if (active_page != 'catalog') {

				// empty the local and no-reply list
				pageData.localList = [];
				pageData.noReplyList = [];

				document.querySelector('.thread').each(() => {
					const $thread = $(this);
					// disregard the hidden threads constructed by post-hover.js
					if ($thread.style.display === 'none')
						return;

					const threadId = $thread.getAttribute('id').replace('thread_', '');
					const boardId = $thread.data('board');
					const op = $thread.children('.op')[0];
					const i, array;  // temp variables

					// add posts to localList and noReplyList
					if (typeof list.postFilter[boardId] != 'undefined' && typeof list.postFilter[boardId][threadId] != 'undefined') {
						array = list.postFilter[boardId][threadId];
						for (i=0; i<array.length; i++) {
							if ( typeof array[i].post === 'undefined')
								continue;

							pageData.localList.push(array[i].post);
							if (array[i].hideReplies) pageData.noReplyList.push(array[i].post);
						}
					}
					// run filter on OP
					filter(op, threadId, pageData);
					quickToggle(op, threadId, pageData);

					// iterate filter over each post
					if (!$(op).data('hidden') || active_page === 'thread') {
						$thread.querySelector('.reply').not('.hidden').each(() => {
							filter(this, threadId, pageData);
						});
					}

				});
			} else {
				const postFilter = list.postFilter[pageData.boardId];
				const $collection = document.querySelector('.mix');

				if ($.isEmptyObject(postFilter))
					return;

				// for each thread that has filtering rules
				// check if filter contains thread OP and remove the thread from catalog
				$.each(postFilter, function (key, thread) {
					const threadId = key;
					$.each(thread, () => {
						if (this.post === threadId) {
							$collection.filter('[data-id='+ threadId +']').remove();
						}
					});
				});
			}
		 }

		function initStyle() {
			const $ele, cssStyle, cssString;

			$ele = document.querySelector('<div>').classList.add('post reply').style.display = 'none'.appendTo('body');
			cssStyle = $ele.css(['background-color', 'border-color']);
			cssStyle.hoverBg = document.querySelector('body').css('background-color');
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

			if (!document.querySelector('style.generated-css').length) $('<style class="generated-css">').appendTo('head');
			document.querySelector('style.generated-css').html(document.querySelector('style.generated-css').innerHTML + cssString);
		}

		function drawFilterList() {
			const list = getList().generalFilter;
			const $ele = document.getElementById('filter-list');
			const $row, i, length, obj, val;

			const typeName = {
				name: 'name',
				trip: 'tripcode',
				sub: 'subject',
				com: 'comment',
				flag: 'flag'
			};

			$ele.innerHTML = '';

			$ele.appendChild('<tr id="header"><th>Type</th><th>Content</th><th>Remove</th></tr>');
			for (i = 0, length = list.length; i < length; i++) {
				obj = list[i];

				// display formatting
				val = (obj.regex) ? '/'+ obj.value +'/' : obj.value;

				$row = document.querySelector('<tr>');
				$row.appendChild(
					'<td>'+ typeName[obj.type] +'</td>',
					'<td>'+ val +'</td>',
					document.querySelector('<td>').appendChild(
						document.querySelector('<a>').html('X')
							.classList.add('del-btn')
							.setAttribute('href', '#')
							.data('type', obj.type)
							.data('val', obj.value)
							.data('useRegex', obj.regex)
					)
				);
				$ele.appendChild($row);
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
							'<option value="flag">'+_('Flag')+'</option>' +
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
				document.getElementById('filter-control').on('click', '#set-filter', () => {
					const type = document.querySelector('#filter-control select option:selected').value;
					const value = document.querySelector('#filter-control input[type=text]').value;
					const useRegex = document.querySelector('#filter-control input[type=checkbox]').prop('checked');

					//clear the input form
					document.querySelector('#filter-control input[type=text]').val('');

					addFilter(type, value, useRegex);
					drawFilterList();
				});
				document.getElementById('filter-control').on('click', '#clear', () => {
					document.querySelector('#filter-control #clear').classList.add('hidden');
					document.querySelector('#filter-control #confirm').classList.remove('hidden');
				});
				document.getElementById('filter-control').on('click', '#confirm-y', (e) => {
					e.preventDefault();

					document.querySelector('#filter-control #clear').classList.remove('hidden');
					document.querySelector('#filter-control #confirm').classList.add('hidden');
					setList({
						generalFilter: [],
						postFilter: {},
						nextPurge: {},
						lastPurge: timestamp()
					});
					drawFilterList();
				});
				document.getElementById('filter-control').on('click', '#confirm-n', (e) => {
					e.preventDefault();

					document.querySelector('#filter-control #clear').classList.remove('hidden');
					document.querySelector('#filter-control #confirm').classList.add('hidden');
				});


				// remove button
				document.getElementById('filter-list').on('click', '.del-btn', (e) => {
					e.preventDefault();

					const $ele = $(e.target);
					const type = $ele.data('type');
					const val = $ele.data('val');
					const useRegex = $ele.data('useRegex');

					removeFilter(type, val, useRegex);
				});
			}
		}

		/* 
		 *  clear out pruned threads
		 */
		function purge() {
			const list = getList();
			const board, thread, boardId, threadId;
			const deferred;
			const requestArray = [];

			const successHandler = function (boardId, threadId) {
				return () => {
					// thread still alive, keep it in the list and increase the time between checks.
					const list = getList();
					const thread = list.nextPurge[boardId][threadId];

					thread.timestamp = timestamp();
					thread.interval = Math.floor(thread.interval * 1.5);
					setList(list);
				};
			};
			const errorHandler = function (boardId, threadId) {
				return (xhr) => {
					if (xhr.status === 404) {
						const list = getList();

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
						deferred = fetch({
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
			$.when.apply($, requestArray).always(() => {
				const list = getList();
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

			const pageData = {
				boardId: board_name,  // get the id from the global variable
				localList: [],  // all the blacklisted post IDs or UIDs that apply to the current page
				noReplyList: [],  // any posts that replies to the contents of this list shall be hidden
				hasUID: (document.getElementsByClassName('poster_id').length > 0),
				forcedAnon: (document.querySelector('th:contains(Name)').length === 0)  // tests by looking for the Name label on the reply form
			};

			initStyle();
			initOptionsPanel();
			initPostMenu(pageData);
			filterPage(pageData);

			// on new posts
			$(document).addEventListener('new_post', function (e, post) {
				const threadId;

				if ($(post).classList.contains('reply')) {
					threadId = $(post).parents('.thread').getAttribute('id').replace('thread_', '');
				} else {
					threadId = $(post).getAttribute('id').replace('thread_', '');
					post = $(post).children('.op')[0];
				}

				filter(post, threadId, pageData);
				quickToggle(post, threadId, pageData);
			});

			$(document).on('filter_page', () => {
				filterPage(pageData);
			});

			// shift+click on catalog to hide thread
			if (active_page === 'catalog') {
				$(document).on('click', '.mix', (e) => {
					if (e.shiftKey) {
						const threadId = $(this).data('id').toString();
						const postId = threadId;
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
