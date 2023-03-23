(function(){

var cache = new Array(),
	thread = false,
	loading = false,
	ukkotimer = false;

if (localStorage.hiddenboards === undefined) {
	localStorage.hiddenboards = "{}";
}

// Load data from HTML5 localStorage
var hiddenboards = JSON.parse(localStorage.hiddenboards);
                
var storeboards = function() {
        localStorage.hiddenboards = JSON.stringify(hiddenboards);
};

$(document).ready(function() {
	var addukkohide = function() {
		var ukkohide = $('<a href="javascript:void(0);" class="unimportant ukkohide"></a>');
		var board = $(this).next().data("board");
		var hr = $("<hr />");

		$(this).append(ukkohide);
		$(this).append(hr);

		if (hiddenboards[board] !== true) {
			ukkohide.html(_("(hide threads from this board)"));
			hr.hide();
		}
		else {
			ukkohide.html(_("(show threads from this board)"));
			$(this).next().hide();
		}
		ukkohide.click(function() {
			hiddenboards[board] = (hiddenboards[board] !== true);
			if (hiddenboards[board] !== true) {
                        	$('[data-board="'+board+'"]:not([data-cached="yes"])').show().prev().
					find('.ukkohide').html(_("(hide threads from this board)")).
					parent().find('hr').hide();
                	}
                	else {
                        	$('[data-board="'+board+'"]:not([data-cached="yes"])').hide().prev().
					find('.ukkohide').html(_("(show threads from this board)"))
					.parent().find('hr').show();
                	}
			storeboards();
			return false;
		});

	};
	$("h2").each(addukkohide);

	$('.pages').hide();
	var loadnext = function() {
		if (overflow.length == 0) {
			$('.pages').show().html(_("No more threads to display"));
		}
		while($(window).scrollTop() + $(window).height() + 1000 > $(document).height() && !loading && overflow.length > 0) {
			var page = modRoot + overflow[0].board + '/' + overflow[0].page;
			thread = $('div#thread_' + overflow[0].id + '[data-board="' + overflow[0].board + '"]');
			if (thread.length > 0 && thread.attr("data-cached") !== 'yes') { // already present
				overflow.shift();
				continue;
			}

			var boardheader = $('<h2><a href="/' + overflow[0].board + '/">/' + overflow[0].board + '/</a> </h2>');

			if($.inArray(page, cache) != -1) {
				if (thread.length > 0) {
					$('div[id*="thread_"]').last().after(thread.attr('data-board', overflow[0].board).attr("data-cached", "no").css('display', 'block'));
					boardheader.insertBefore(thread);
					addukkohide.call(boardheader);
					$(document).trigger('new_post', thread);
				}
				overflow.shift();
			} else {
				loading = true;
				$('.pages').show().html(_("Loading..."));
				$.get(page, function(data) {
					cache.push(page);

					$(data).find('div[id*="thread_"]').each(function() {
						var checkout = $(this).attr('id').replace('thread_', '');
						if ($('div#thread_' + checkout + '[data-board="' + overflow[0].board + '"]').length == 0) {
							$('form[name="postcontrols"]').prepend($(this).css('display', 'none').attr("data-cached", "yes").attr('data-board', overflow[0].board));
						}
					});

					thread = $('div#thread_' + overflow[0].id + '[data-board="' + overflow[0].board + '"][data-cached="yes"]');

					if(thread.length > 0) {
						$('div[id*="thread_"]').last().after(thread.attr('data-board', overflow[0].board).attr("data-cached", "no").css('display', 'block'));
						boardheader.insertBefore(thread);
						addukkohide.call(boardheader);
						$(document).trigger('new_post', thread);
					}
					overflow.shift();

					loading = false;
					$('.pages').hide().html("");
				});
				break;
			}
		}
		clearTimeout(ukkotimer);
		ukkotimer = setTimeout(loadnext, 1000);
	};

	$(window).on('scroll', loadnext);

	ukkotimer = setTimeout(loadnext, 1000);
});

})();
