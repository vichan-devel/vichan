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
	var submenu = Menu.add_submenu("delete_file_menu", _("Delete file(s)"));

	// Add sub menu elements for file deletion
	submenu.add_item("delete_file_menu_all", "All images");
    for (var i = 0; i < max_images; i++)
    {
        submenu.add_item('delete_file_menu_image_index-' + i, '#' + (i + 1));
	}
	

	Menu.onclick(function(e, $buf) {

		var ele = e.target.parentElement.parentElement;
		var $ele = $(ele);
		var threadId = $ele.parent().attr('id').replace('thread_', '');
		var postId = $ele.find('.post_no').not('[id]').text();
		var board_name = $ele.parent().data('board');

		var image_count = $ele.find('.files').children().length;
		if($ele.hasClass('op'))
			image_count = $ele.prev('.files').children().length;
			// image_count = $('.files:first').children().length;

		if(image_count != 0)
			$buf.find("#delete_file_menu").show();
		else
			$buf.find("#delete_file_menu").hide();
		
		
        for (var i = 0; i < max_images; i++)
        {
            if (i < image_count)
                $buf.find('#delete_file_menu_image_index-' + i).show();
            else
                $buf.find('#delete_file_menu_image_index-' + i).hide();
        }

		$buf.find('#delete_post_menu').click(function(e) {
			e.preventDefault();
			$('#delete_'+postId).prop('checked', 'checked');
			$('#delete_file').prop('checked', '');
			$('input[type="hidden"][name="board"]').val(board_name);
			$('input[name=delete][type=submit]').click();
		});

		$buf.find('#delete_file_menu_all').click(function(e) {
			e.preventDefault();
			$('#delete_'+postId).prop('checked', 'checked');
			$('#delete_file').prop('checked', 'checked');
			$('input[type="hidden"][name="board"]').val(board_name);
			$('input[name=delete][type=submit]').click();
		});

		// Add functions for deletion of given indexes
		for (var i = 0; i < max_images; i++)
		{
			$buf.find('#delete_file_menu_image_index-' + i).click(function(e){
				e.preventDefault();
				$('#delete_'+postId).prop('checked', 'checked');
				$('#delete_file').prop('checked', 'checked');

				// 0 is treated as empty by php
				var img_no = $(this).attr('id').split('-')[1];
				$('#delete_file_single').val(parseInt(img_no) + 1);
				
				$('input[type="hidden"][name="board"]').val(board_name);
				$('input[name=delete][type=submit]').click();
			});
		}
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
