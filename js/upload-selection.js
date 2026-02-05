/*
 * upload-selection.js - makes upload fields in post form more compact
 * https://github.com/vichan-devel/Tinyboard/blob/master/js/upload-selection.js
 *
 * Released under the MIT license
 * Copyright (c) 2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   //$config['additional_javascript'][] = 'js/wpaint.js';
 *   $config['additional_javascript'][] = 'js/upload-selection.js';
 *                                                  
 */

$(() => {
  const enabled_file = true;
  const enabled_url = document.getElementById('upload_url').length > 0;
  const enabled_embed = document.getElementById('upload_embed').length > 0;
  const enabled_oekaki = typeof window.oekaki != "undefined";

  const disable_all = () => {
    document.getElementById('upload').style.display = 'none';
    document.querySelector('[id^=upload_file]').style.display = 'none';
    document.querySelector('.file_separator').style.display = 'none';
    document.getElementById('upload_url').style.display = 'none';
    document.getElementById('upload_embed').style.display = 'none';
    document.querySelector('.add_image').style.display = 'none';
    document.querySelector('.dropzone-wrap').style.display = 'none';

    document.querySelector('[id^=upload_file]').each(function(i, v) {
        $(v).val('');
    });

    if (enabled_oekaki) {
      if (window.oekaki.initialized) {
        window.oekaki.deinit();
      }
    }
  };

  enable_file = () => {
    disable_all();
    document.getElementById('upload').style.display = '';
    document.querySelector('.dropzone-wrap').style.display = '';
    document.querySelector('.file_separator').style.display = '';
    document.querySelector('[id^=upload_file]').style.display = '';
    document.querySelector('.add_image').style.display = '';
  };

  enable_url = () => {
    disable_all();
    document.getElementById('upload').style.display = '';
    document.getElementById('upload_url').style.display = '';

    $('label[for="file_url"]').html(_("URL"));
  };

  enable_embed = () => {
    disable_all();
    document.getElementById('upload_embed').style.display = '';
  };

  enable_oekaki = () => {
    disable_all();

    window.oekaki.init();
  };

  if (enabled_url || enabled_embed || enabled_oekaki) {
    $("<tr><th>"+_("Select")+"</th><td id='upload_selection'></td></tr>").insertBefore("#upload");
    const my_html = "<a href='javascript:void(0)' onclick='enable_file(); return false;'>"+_("File")+"</a>";
    if (enabled_url) {
      my_html += " / <a href='javascript:void(0)' onclick='enable_url(); return false;'>"+_("Remote")+"</a>";
    }
    if (enabled_embed) {
      my_html += " / <a href='javascript:void(0)' onclick='enable_embed(); return false;'>"+_("Embed")+"</a>";
    }
    if (enabled_oekaki) {
      my_html += " / <a href='javascript:void(0)' onclick='enable_oekaki(); return false;'>"+_("Oekaki")+"</a>";

      document.getElementById('confirm_oekaki_label').style.display = 'none';
    }
    document.getElementById('upload_selection').html(my_html);

    enable_file();
  }
});
