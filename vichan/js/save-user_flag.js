function user_flag() {
	var flagStorage = "flag_" + document.getElementsByName('board')[0].value;
	var item = window.localStorage.getItem(flagStorage);
	$('select[name=user_flag]').val(item);
	$('select[name=user_flag]').change(function() {
		window.localStorage.setItem(flagStorage, $(this).val());
	});
	$(window).on('quick-reply', function() {
		$('form#quick-reply select[name="user_flag"]').val($('select[name="user_flag"]').val());
	});
}
if (active_page == 'thread' || active_page == 'index') {
	$(document).ready(user_flag);
}
