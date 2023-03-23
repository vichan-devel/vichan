$('document').ready(function () {
	var autoScroll = localStorage['autoScroll'] ? true : false;
	if (window.Options && Options.get_tab('general')){
		Options.extend_tab('general','<label id=\'autoScroll\'><input type=\'checkbox\' />' + ' Scroll to new posts' + '</label>');
		$('#autoScroll').find('input').prop('checked', autoScroll);
	}
	$('#autoScroll').on('change', function() {
		if(autoScroll) {
			delete localStorage.autoScroll;
		} else {
			localStorage.autoScroll = true;
		}
		autoScroll =! autoScroll
		if(active_page == 'thread')
			$('input.auto-scroll').prop('checked', autoScroll);
	});
	if (active_page == 'thread') {
		$('span[id="updater"]').children('a').after(' (<input class="auto-scroll" type="checkbox"></input> Scroll to New posts)');
		$('input.auto-scroll').prop('checked', autoScroll);
		$(document).on('new_post', function (e, post) {
			if ($('input.auto-scroll').prop('checked')) 
			{
				scrollTo(0, $(post).offset().top - window.innerHeight + $(post).outerHeight(true)); 
			}
		});
	}
});
