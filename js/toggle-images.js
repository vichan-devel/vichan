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

onReady(() => {
	const hide_images = localStorage['hideimages'] ? true : false;

	$('<style type="text/css"> img.hidden{ opacity: 0.1; background: grey; border: 1px solid #000; } </style>').appendTo(document.querySelector('head'));

	const hideImage = () => {
		if ($(this).parent().data('expanded') === 'true') {
			$(this).parent().click();
		}
		$(this)
			.attr('data-orig', this.src)
			.setAttribute('src', 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw===')
			.classList.add('hidden');
	};

	const restoreImage = () => {
		$(this)
			.setAttribute('src', $(this).attr('data-orig'))
			.classList.remove('hidden');
	};

	// Fix for hide-images.js
	const show_hide_hide_images_buttons = () => {
		if (hide_images) {
			document.querySelector('a.hide-image-link').each(() => {
				if ($(this).next().classList.contains('show-image-link')) {
					$(this).next().style.display = 'none';
				}
				$(this).style.display = 'none'.after('<span class="toggle-images-placeholder">'+_('hidden')+'</span>');
			});
		} else {
			document.querySelector('span.toggle-images-placeholder').remove();
			document.querySelector('a.hide-image-link').each(() => {
				if ($(this).next().classList.contains('show-image-link')) {
					$(this).next().style.display = '';
				} else {
					$(this).style.display = '';
				}
			});
		}
	};

        const selector, event;
        if (window.Options && Options.get_tab('general')) {  
                selector = '#toggle-images>input';
                event = 'change';
                Options.extend_tab("general", "<label id='toggle-images'><input type='checkbox' />"+_('Hide images')+"</label>");
        }
        else {
                selector = '#toggle-images a';
                event = 'click';
		document.querySelector('hr:first').before('<div id="toggle-images" style="text-align:right"><a class="unimportant" href="javascript:void(0)">-</a></div>');
		document.querySelector('div#toggle-images a')
			.text(hide_images ? _('Show images') : _('Hide images'));
        }

	$(selector)
		.on(event, () => {
			hide_images = !hide_images;
			if (hide_images) {
				document.querySelector('img.post-image, .theme-catalog .thread>a>img').each(hideImage);
				localStorage.hideimages = true;
			} else {
				document.querySelector('img.post-image, .theme-catalog .thread>a>img').each(restoreImage);
				delete localStorage.hideimages;
			}
			
			show_hide_hide_images_buttons();
			
			$(this).text(hide_images ? _('Show images') : _('Hide images'))
		});

	if (hide_images) {
		document.querySelector('img.post-image, .theme-catalog .thread>a>img').each(hideImage);
		show_hide_hide_images_buttons();

                if (window.Options && Options.get_tab('general')) {
                        document.querySelector('#toggle-images>input').prop('checked', true);
                }
	}
	
	$(document).addEventListener('new_post', function(e, post) {
		if (hide_images) {
			$(post).querySelector('img.post-image').each(hideImage);
		}
	});
});
