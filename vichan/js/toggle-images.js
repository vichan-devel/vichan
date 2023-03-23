/*
 * toggle-images.js
 *
 * Released under the MIT license
 * Copyright (c) 2012 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net> 
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   //$config['additional_javascript'][] = 'js/options.js';
 *   //$config['additional_javascript'][] = 'js/style-select.js';
 *   //$config['additional_javascript'][] = 'js/options/general.js';
 *   $config['additional_javascript'][] = 'js/toggle-images.js';
 *
 */

$(document).ready(function(){
	var hide_images = localStorage['hideimages'] ? true : false;

	$('<style type="text/css"> img.hidden{ opacity: 0.1; background: grey; border: 1px solid #000; } </style>').appendTo($('head'));

	var hideImage = function() {
		if ($(this).parent().data('expanded') == 'true') {
			$(this).parent().click();
		}
		$(this)
			.attr('data-orig', this.src)
			.attr('src', 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==')
			.addClass('hidden');
	};

	var restoreImage = function() {
		$(this)
			.attr('src', $(this).attr('data-orig'))
			.removeClass('hidden');
	};

	// Fix for hide-images.js
	var show_hide_hide_images_buttons = function() {
		if (hide_images) {
			$('a.hide-image-link').each(function() {
				if ($(this).next().hasClass('show-image-link')) {
					$(this).next().hide();
				}
				$(this).hide().after('<span class="toggle-images-placeholder">'+_('hidden')+'</span>');
			});
		} else {
			$('span.toggle-images-placeholder').remove();
			$('a.hide-image-link').each(function() {
				if ($(this).next().hasClass('show-image-link')) {
					$(this).next().show();
				} else {
					$(this).show();
				}
			});
		}
	};

        var selector, event;
        if (window.Options && Options.get_tab('general')) {  
                selector = '#toggle-images>input';
                event = 'change';
                Options.extend_tab("general", "<label id='toggle-images'><input type='checkbox' />"+_('Hide images')+"</label>");
        }
        else {
                selector = '#toggle-images a';
                event = 'click';
		$('hr:first').before('<div id="toggle-images" style="text-align:right"><a class="unimportant" href="javascript:void(0)">-</a></div>');
		$('div#toggle-images a')
			.text(hide_images ? _('Show images') : _('Hide images'));
        }

	$(selector)
		.on(event, function() {
			hide_images = !hide_images;
			if (hide_images) {
				$('img.post-image, .theme-catalog .thread>a>img').each(hideImage);
				localStorage.hideimages = true;
			} else {
				$('img.post-image, .theme-catalog .thread>a>img').each(restoreImage);
				delete localStorage.hideimages;
			}
			
			show_hide_hide_images_buttons();
			
			$(this).text(hide_images ? _('Show images') : _('Hide images'))
		});

	if (hide_images) {
		$('img.post-image, .theme-catalog .thread>a>img').each(hideImage);
		show_hide_hide_images_buttons();

                if (window.Options && Options.get_tab('general')) {
                        $('#toggle-images>input').prop('checked', true);
                }
	}
	
	$(document).on('new_post', function(e, post) {
		if (hide_images) {
			$(post).find('img.post-image').each(hideImage);
		}
	});
});
