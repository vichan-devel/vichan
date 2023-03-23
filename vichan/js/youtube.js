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


onready(function(){
	var do_embed_yt = function(tag) {
		$('div.video-container a', tag).click(function() {
			var videoID = $(this.parentNode).data('video');
		
			$(this.parentNode).html('<iframe style="float:left;margin: 10px 20px" type="text/html" '+
				'width="360" height="270" src="//www.youtube.com/embed/' + videoID +
				'?autoplay=1&html5=1" allowfullscreen frameborder="0"/>');

			return false;
		});
	};
	do_embed_yt(document);

        // allow to work with auto-reload.js, etc.
        $(document).on('new_post', function(e, post) {
                do_embed_yt(post);
        });
});

