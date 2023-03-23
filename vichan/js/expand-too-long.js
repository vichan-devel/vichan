/*
 * expand-too-long.js
 * https://github.com/vichan-devel/vichan/blob/master/js/expand-too-long.js
 *
 * Released under the MIT license
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/expand-too-long.js';
 *      
 */

$(function() {
	var do_expand = function() {
		$(this).find('a').click(function(e) {
			e.preventDefault();

			var url = $(this).attr('href');
			var body = $(this).parents('.body');

			$.ajax({
				url: url,
				context: document.body,
				success: function(data) {
					var content = $(data).find('#'+url.split('#')[1]).parent().parent().find(".body").first().html();

					body.html(content);
				}
			});
		});
	};

        $('.toolong').each(do_expand);
                       
        $(document).on("new_post", function(e, post) {
		$(post).find('.toolong').each(do_expand)
	});
});
