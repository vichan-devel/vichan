/*
 * fix-report-delete-submit.js
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/post-menu.js';
 *   $config['additional_javascript'][] = 'js/fix-report-delete-submit.js';
 *
 */

if (active_page == 'thread' || active_page == 'index' || active_page == 'ukko') {
$(document).on('menu_ready', function(){
var Menu = window.Menu;
	
if ($('#delete-fields #password').length) {
	Menu.add_item("delete_post_menu", _("Delete post"));
	Menu.add_item("delete_file_menu", _("Delete file"));
	Menu.onclick(function(e, $buf) {
		var ele = e.target.parentElement.parentElement;
		var $ele = $(ele);
		var threadId = $ele.parent().attr('id').replace('thread_', '');
		var postId = $ele.find('.post_no').not('[id]').text();
		var board_name = $ele.parent().data('board');

		$buf.find('#delete_post_menu,#delete_file_menu').click(function(e) {
			e.preventDefault();
			$('#delete_'+postId).prop('checked', 'checked');
		
			if ($(this).attr('id') === 'delete_file_menu') {
				$('#delete_file').prop('checked', 'checked');
			} else {
				$('#delete_file').prop('checked', '');
			}
			$('input[type="hidden"][name="board"]').val(board_name);
			$('input[name=delete][type=submit]').click();
		});
	});
}

Menu.add_item("report_menu", _("Report"));
//Menu.add_item("global_report_menu", _("Global report"));
Menu.onclick(function(e, $buf) {
	var ele = e.target.parentElement.parentElement;
	var $ele = $(ele);
	var threadId = $ele.parent().attr('id').replace('thread_', '');
	var postId = $ele.find('.post_no').not('[id]').text();
	var board_name = $ele.parent().data('board');

	$buf.find('#report_menu,#global_report_menu').click(function(e) {
		if ($(this).attr('id') === "global_report_menu") {
			var global = '&global';
		} else {
			var global = '';
		}
		window.open(configRoot+'report.php?board='+board_name+'&post=delete_'+postId+global, "", (global?"width=600, height=575":"width=500, height=275"));
	});
});

$(document).on('new_post', function(){
	$('input.delete').hide();
});
$('input.delete').hide();
$('#post-moderation-fields').hide();
});

if (typeof window.Menu !== "undefined") {
	$(document).trigger('menu_ready');
}
}
