/*
 * treeview.js
 * https://github.com/vichan-devel/vichan/blob/master/js/treeview.js
 *
 * Released under the MIT license
 * Copyright (c) 2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/treeview.js';
 *
 */

if (active_page === 'thread' || active_page === 'ukko' || active_page === 'index')
$(() => {
	if (window.Options && Options.get_tab('general')) {
		const selector = '#treeview-global>input';
		Options.extend_tab("general", "<label id='treeview-global'><input type='checkbox' /> "+_('Use tree view by default')+"</label>");
		$(selector).on('change', () => {
			if (localStorage.treeview === 'true') {
				localStorage.treeview = 'false';
			} else {
				localStorage.treeview = 'true';
			}
		});
		if (localStorage.treeview === 'true') {
			$(selector).setAttribute('checked', 'checked');
		}
	}
});

if (active_page === 'thread')
$(() => {
	const treeview = (enable) => {
		if (enable === true) {
			document.querySelectorAll('.post.reply').each(() => {
				const references = [];
				$(this).querySelector('.body a').each(() => {
					if ($(this).innerHTML.match('^&gt;&gt;[0-9]+$')) {
						references.push(parseInt($(this).innerHTML.replace('&gt;&gt;', '')));
					}
				});
				const maxref = references.reduce(function(a,b) { return a > b ? a : b; }, 0);

				const parent_post = $("#reply_"+maxref);
				if (parent_post.length === 0) return;

				const margin = parseInt(parent_post.css("margin-left"))+32;

				const post = $(this);
				const br = post.next();

				post.detach().css("margin-left", margin).insertAfter(parent_post.next());
				br.detach().insertAfter(post);
			});
		} else {
			document.querySelectorAll('.post.reply').sort(function(a,b) {
				return parseInt(a.id.replace('reply_', '')) - parseInt(b.id.replace('reply_', ''));
			}).each(() => {
				const post = $(this);
				const br = post.next();
				post.detach().css('margin-left', '').appendTo('.thread');
				br.detach().insertAfter(post);
			});
		}
	}

	document.querySelector('hr:first').before('<div class="unimportant" style="text-align:right"><label for="treeview"><input type="checkbox" id="treeview"> '+_('Tree view')+'</label></div>');
	document.querySelector('input#treeview').on('change', (e) => { treeview($(this).is(':checked')); });

	if (localStorage.treeview === 'true') {
		treeview(true);
		document.querySelector('input#treeview').setAttribute('checked', true);
	}
});
