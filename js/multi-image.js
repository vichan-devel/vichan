/*
 * multi-image.js - Add support for multiple images to the post form
 *
 * Copyright (c) 2014 Fredrick Brennan <admin@8chan.co>
 *
 * Usage:
 *   $config['max_images'] = 3;
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/multi-image.js';
 */

function multi_image() {
    $('input[type=file]').after('<a href="#" class="add_image no-decoration">&nbsp;&nbsp;&nbsp;+</a>');
    
    $(document).on('click', 'a.add_image', function(e) {
        e.preventDefault();

        var images_len = $('form:not([id="quick-reply"]) [type=file]').length;
        
        if (!(images_len >= max_images)) {
            var new_file = '<br class="file_separator"/><input type="file" name="file'+(images_len+1)+'" id="upload_file'+(images_len+1)+'">';

            $('[type=file]:last').after(new_file);
            if ($("#quick-reply").length) {
                $('form:not(#quick-reply) [type=file]:last').after(new_file);
            }
            if (typeof setup_form !== 'undefined') setup_form($('form[name="post"]'));
        }
    })
}


function multi_image_upload() {
	$('input.upload_url_field').after('<a href="#" class="add_image_url no-decoration">&nbsp;&nbsp;&nbsp;&nbsp;+</a>');
    
    $(document).on('click', 'a.add_image_url', function(e) {
        e.preventDefault();

        var images_len = $('form:not([id="quick-reply"]) .upload_url_field').length;
        
        if (!(images_len >= max_images)) {
        	var new_file_url = '<br class="file_separator_url"/><label for="file_url' + (images_len + 1) + '">URL</label>: <input style="display:inline" class="upload_url_field" type="text" id="file_url' + (images_len + 1) + '" name="file_url[]" size="35">';

        	$('.upload_url_field:last').after(new_file_url);
            if ($("#quick-reply").length) {
            	$('form:not(#quick-reply) .upload_url_field:last').after(new_file_url);
            }
            if (typeof setup_form !== 'undefined') setup_form($('form[name="post"]'));
        }
    })
}


if (active_page == 'thread' || active_page == 'index' || active_page == 'catalog' && max_images > 1) {
	$(document).ready(multi_image);
	$(document).ready(multi_image_upload);
}



