/*
 * charcount.js
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/charcount.js';
 *
 */

$(document).ready(function(){

	// Storing this jQuery object outside of the event callback 
	// prevents jQuery from having to search the DOM for it again
	// every time an event is fired.
	var $inputArea = $('#body');
	if ($inputArea.length == 0)
		return;

	var $maxChars = 6000;

	// Preset countdown field to max initial content length
	$('.countdown').text($maxChars - $inputArea.val().length);

	// input           :: for all modern browsers [1]
	// selectionchange :: for IE9 [2]
	// propertychange  :: for <IE9 [3]
	$inputArea.on('input selectionchange propertychange', function() {
		$charCount = $maxChars - $inputArea.val().length;
		$('.countdown').text($charCount);
	});
});