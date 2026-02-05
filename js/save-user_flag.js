function user_flag() {
	const flagStorage = "flag_" + document.getElementsByName('board')[0].value;
	const item = window.localStorage.getItem(flagStorage);
	document.querySelector('select[name=user_flag]').val(item);
	document.querySelector('select[name=user_flag]').change(() => {
		window.localStorage.setItem(flagStorage, $(this).value);
	});
	$(window).on('quick-reply', () => {
		$('form#quick-reply select[name="user_flag"]').val($('select[name="user_flag"]').value);
	});
}
if (active_page === 'thread' || active_page === 'index') {
	$(document).ready(user_flag);
}
