/*
 * mod_snippets.js
 *
 * Javascript snippets to be loaded when in mod mode
 *
 */

function populateFormJQuery(frm, data) {
	$.each(data, function(key, value){
		$('[name='+key+']', frm).val(value);
	});
}
