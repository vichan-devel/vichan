var cache = new Array(),
	thread = false,
	loading = false;
$(document).ready(function() {
	$(window).on('scroll', function() {
		if($(window).scrollTop() + $(window).height() + 100 > $(document).height() && !loading && overflow.length > 0) {
			var page = '../' + overflow[0].board + '/' + overflow[0].page;
			if($.inArray(page, cache) != -1) {
				thread = $('div#thread_' + overflow[0].id);
				if(thread.length > 0) {
					thread.prepend('<h2><a href="/' + overflow[0].board + '/">/' + overflow[0].board + '/</a></h2>');
					$('div[id*="thread_"]').last().after(thread.attr('data-board', overflow[0].board).css('display', 'block'));
					overflow.shift();
				}
			} else {
				loading = true;
				$.get(page, function(data) {
					cache.push(page);

					$(data).find('div[id*="thread_"]').each(function() {
						$('body').prepend($(this).css('display', 'none').attr('data-board', overflow[0].board));
					});

					thread = $('div#thread_' + overflow[0].id + '[data-board="' + overflow[0].board + '"]');
					if(thread.length > 0) {
						thread.prepend('<h2><a href="/' + overflow[0].board + '/">/' + overflow[0].board + '/</a></h2>');
						$('div[id*="thread_"]').last().after(thread.attr('data-board', overflow[0].board).css('display', 'block'));
						overflow.shift();
					}

					loading = false;
				});
			}
		}
	});

});