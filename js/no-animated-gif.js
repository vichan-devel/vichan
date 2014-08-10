/*
 * no-animated-gif.js - Toggle GIF animated thumbnails when gifsicle is enabled
 *
 * Copyright (c) 2014 Fredrick Brennan <admin@8chan.co>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/no-animated-gif.js';
 */

function unanimate_gif(e) {
	var c = $('<canvas class="post-image"></canvas>');
	$(e).parent().prepend(c);
	c.attr('width', $(e).width());
	c.attr('height',$(e).height());
	function draw_image() {
		c[0].getContext('2d').drawImage(e, 0, 0, $(e).width(), $(e).height())
	};
	
	// Fix drawing image before loaded. Note that Chrome needs to check .complete because load() is NOT called if loaded from cache.
	if (!e.complete) {
		e.onload = draw_image;
	} else {
		draw_image();
	}

	$(e).addClass("unanimated").hide();
}

function no_animated_gif() {
	var anim_gifs = $('img.post-image[src$=".gif"]');
	localStorage.no_animated_gif = true;
	$('#no-animated-gif>a').text(_('Animate GIFs'));

	$.each(anim_gifs, function(i, e) {unanimate_gif(e)} );
}

function animated_gif() {
	$('canvas.post-image').remove();
	$('img.post-image').removeClass("unanimated").show();
	localStorage.no_animated_gif = false;
	$('#no-animated-gif>a').text(_('Unanimate GIFs'));
	
}

if (active_page == 'thread' || active_page == 'index' || active_page == 'ukko') {
	onready(function(){
		$('hr:first').before('<div id="no-animated-gif" style="text-align:right"><a class="unimportant" href="javascript:void(0)">'+_('Unanimate GIFs')+'</a></div>')

		$('#no-animated-gif').on('click', function() {
			if (localStorage.no_animated_gif === 'true') {
				animated_gif();
			} else {
				no_animated_gif();
			}
		});

		if (localStorage.no_animated_gif === 'true')
			$(document).ready(no_animated_gif);
	});
}
