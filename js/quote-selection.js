/*
 * quote-selection.js
 *
 * This is a little buggy.
 * Allows you to quote a post by just selecting some text, then beginning to type.
 *
 * Released under the MIT license
 * Copyright (c) 2012 Michael Save <savetheinternet@tinyboard.org>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/quote-selection.js';
 */

onReady(() => {
	if (!window.getSelection) {
		return;
	}

	$.fn.selectRange = function(start, end) {
		return this.each(() => {
			if (this.setSelectionRange) {
				this.focus();
				this.setSelectionRange(start, end);
			} else if (this.createTextRange) {
				let range = this.createTextRange();
				range.collapse(true);
				range.moveEnd('character', end);
				range.moveStart('character', start);
				range.select();
			}
		});
	};

	let altKey = false;
	let ctrlKey = false;
	let metaKey = false;

	$(document).keyup((e) => {
		if (e.keyCode === 18) {
			altKey = false;
		} else if (e.keyCode === 17) {
			ctrlKey = false;
		} else if (e.keyCode === 91) {
			metaKey = false;
		}
	});

	$(document).keydown((e) => {
		if (e.altKey) {
			altKey = true;
		} else if (e.ctrlKey) {
			ctrlKey = true;
		} else if (e.metaKey) {
			metaKey = true;
		}

		if (altKey || ctrlKey || metaKey) {
			return;
		}

		if (e.keyCode < 48 || e.keyCode > 90) {
			return;
		}

		let selection = window.getSelection();
		let post = $(selection.anchorNode).parents('.post');
		if (post.length === 0) {
			return;
		}

		let postID = post.querySelector('.post_no:eq(1)').textContent;

		if (postID != $(selection.focusNode).parents('.post').querySelector('.post_no:eq(1)').textContent) {
			return;
		}

		let selectedText = selection.toString();

		if (document.querySelector('body').classList.contains('debug')) {
			alert(selectedText);
		}

		if (selectedText.length === 0) {
			return;
		}

		let body = document.querySelector('textarea#body')[0];

		let last_quote = body.value.match(/[\S.]*(^|[\S\s]*)>>(\d+)/);
		if (last_quote) {
			last_quote = last_quote[2];
		}

		/* to solve some bugs on weird browsers, we need to replace \r\n with \n and then undo that after */
		let quote = (last_quote != postID ? '>>' + postID + '\r\n' : '') + $.trim(selectedText).replace(/\r\n/g, '\n').replace(/^/mg, '>').replace(/\n/g, '\r\n') + '\r\n';

		selection.removeAllRanges();

		if (body.selectionStart || body.selectionStart === '0') {
			let start = body.selectionStart;
			let end = body.selectionEnd;

			if (!body.value.substring(0, start).match(/(^|\n)$/)) {
				quote = '\r\n\r\n' + quote;
			}

			body.value = body.value.substring(0, start) + quote + body.value.substring(end, body.value.length);
			$(body).selectRange(start + quote.length, start + quote.length);
		} else {
			// ???
			body.value += quote;
			body.focus();
		}
	});
});
