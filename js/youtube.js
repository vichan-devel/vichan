/*
* youtube
* https://github.com/savetheinternet/Tinyboard/blob/master/js/youtube.js
*
* Don't load the YouTube player unless the video image is clicked.
* This increases performance issues when many videos are embedded on the same page.
* Currently only compatiable with YouTube.
*
* Proof of concept.
*
* Released under the MIT license
* Copyright (c) 2013 Michael Save <savetheinternet@tinyboard.org>
* Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
*
* Usage:
*	$config['embedding'] = array();
*	$config['embedding'][0] = array(
*		'/^https?:\/\/(\w+\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9\-_]{10,11})(&.+)?$/i',
*		$config['youtube_js_html']);
*   $config['additional_javascript'][] = 'js/jquery.min.js';
*   $config['additional_javascript'][] = 'js/youtube.js';
*
*/

onReady(function() {
	let do_embed_yt = function(tag) {
		$('div.video-container a', tag).click(function() {
			let videoID = $(this.parentNode).data('video');

			$(this.parentNode).html('<iframe style="float:left;margin: 10px 20px" type="text/html" ' +
				'width="360" height="270" src="//www.youtube.com/embed/' + videoID +
				'?autoplay=1&html5=1" allowfullscreen frameborder="0"/>');

			const ON = "[Remove]";
			const OFF = "[Embed]";

			let videoNode = $('div.video-container', tag);
			let videoId = videoNode.data('video');
			let span = $("<span>[Embed]</span>");
			let embedNode = $('<iframe style="float:left;margin: 10px 20px" type="text/html" '+
					'width="360" height="270" src="//www.youtube.com/embed/' + videoId +
					'?autoplay=1&html5=1" allowfullscreen frameborder="0"/>')
			span.click(function() {
				if (span.text() == ON){
					embedNode.remove();
					span.text(OFF);
				} else {
					videoNode.append(embedNode);
					span.text(ON);
				}
			});
		});

		videoNode.append(span);
	};
	do_embed_yt(document);

	// allow to work with auto-reload.js, etc.
	$(document).on('new_post', function(e, post) {
		do_embed_yt(post);
	});
});
