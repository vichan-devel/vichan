/*
 * expand.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/expand.js
 *
 * Released under the MIT license
 * Copyright (c) 2012-2013 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013 Czterooki <czterooki1337@gmail.com>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/expand.js';
 */

$(document).ready(function() {
	if ($('span.omitted').length === 0) {
		// Nothing to expand.
		return;
	}

	let doExpand = function() {
		$(this)
			.html($(this).text().replace(_("Click reply to view."), '<a href="javascript:void(0)">' + _("Click to expand") + '</a>.'))
			.find('a').click(window.expandFun = function() {
				let thread = $(this).parents('[id^="thread_"]');
				$.ajax({
					url: thread.find('p.intro a.post_no:first').attr('href'),
					context: document.body,
					success: function(data) {
						let lastExpanded = false;
						$(data).find('div.post.reply').each(function() {
							thread.find('div.hidden').remove();
							let postInDoc = thread.find('#' + $(this).attr('id'));
							if (postInDoc.length === 0) {
								if (lastExpanded) {
									$(this).addClass('expanded').insertAfter(lastExpanded).before('<br class="expanded">');
								} else {
									$(this).addClass('expanded').insertAfter(thread.find('div.post:first')).after('<br class="expanded">');
								}
								lastExpanded = $(this);
								$(document).trigger('new_post', this);
							} else {
								lastExpanded = postInDoc;
							}
						});


						thread.find('span.omitted').css('display', 'none');

						$('<span class="omitted hide-expanded"><a href="javascript:void(0)">' + _('Hide expanded replies') + '</a>.</span>')
							.insertAfter(thread.find('.op div.body, .op span.omitted').last())
							.click(function() {
								thread.find('.expanded').remove();
								let parent = $(this).parent();
								parent.find('.omitted:not(.hide-expanded)').css('display', '');
								parent.find('.hide-expanded').remove();
							});
					}
				});
			});
	}

	$('div.post.op span.omitted').each(doExpand);

	$(document).on('new_post', function(e, post) {
		if (!$(post).hasClass('reply')) {
			$(post).find('div.post.op span.omitted').each(doExpand);
		}
	});
});
