/*
 * no-animated-gif.js - Toggle GIF animated thumbnails when gifsicle is enabled
 *
 * Copyright (c) 2014 Fredrick Brennan <admin@8chan.co>
 * Copyright (c) 2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   //$config['additional_javascript'][] = 'js/options.js';
 *   //$config['additional_javascript'][] = 'js/style-select.js';
 *   //$config['additional_javascript'][] = 'js/options/general.js';
 *   $config['additional_javascript'][] = 'js/no-animated-gif.js';
 */
function unanimate_gif(e) {
	if ($(e).closest('.thread').children('.thread-hidden').length > 0) return;

	if (active_page === "catalog")
		var c = $('<canvas class="thread-image"></canvas>');
	else
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

$(function(){

var gif_finder = 'img.post-image[src$=".gif"], img.thread-image[src$=".gif"]';

function no_animated_gif() {
	var anim_gifs = $(gif_finder);
	localStorage.no_animated_gif = true;
	$('#no-animated-gif>a').text(_('Animate GIFs'));
	$('#no-animated-gif>input').prop('checked', true);

	$.each(anim_gifs, function(i, e) {unanimate_gif(e)} );

	$(document).on('new_post', new_post_handler);
}

function animated_gif() {
	$('canvas.post-image').remove();
	$('img.post-image').removeClass("unanimated").show();
	localStorage.no_animated_gif = false;
	$('#no-animated-gif>a').text(_('Unanimate GIFs'));
	$('#no-animated-gif>input').prop('checked', false);	

	$(document).off('new_post', new_post_handler);
}

function new_post_handler(e, post) {
	$(post).find(gif_finder).each(function(k, v) {
		unanimate_gif(v);
	});
}

if (active_page == 'thread' || active_page == 'index' || active_page == 'ukko' || active_page == 'catalog') {
		var selector, event;
		if (window.Options && Options.get_tab('general')) {
			selector = '#no-animated-gif>input';
			event = 'change';
			Options.extend_tab("general", "<label id='no-animated-gif'><input type='checkbox' />"+_('Unanimate GIFs')+"</label>");
		}
		else {
			selector = '#no-animated-gif';
			event = 'click';
			$('hr:first').before('<div id="no-animated-gif" style="text-align:right"><a class="unimportant" href="javascript:void(0)">'+_('Unanimate GIFs')+'</a></div>')
		}

		$(selector).on(event, function() {
			if (localStorage.no_animated_gif === 'true') {
				animated_gif();
			} else {
				no_animated_gif();
			}
		});

		if (localStorage.no_animated_gif === 'true')
			no_animated_gif();
}

});
