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

$(function(){
  var enabled_file = true;
  var enabled_url = $("#upload_url").length > 0;
  var enabled_embed = $("#upload_embed").length > 0;
  var enabled_oekaki = typeof window.oekaki != "undefined";
  var enabled_tegaki = $("#tegaki-form").length > 0;

  var disable_all = function() {
    $("#upload").hide();
    $("[id^=upload_file]").hide();
    $(".file_separator").hide();
    $("#upload_url").hide();
    $("#upload_embed").hide();
    $(".add_image").hide();
    $(".dropzone-wrap").hide();
    $("#tegaki-form").hide();

    $('[id^=upload_file]').each(function(i, v) {
        $(v).val('');
    });

    if (enabled_oekaki) {
      if (window.oekaki.initialized) {
        window.oekaki.deinit();
      }
    }
  };

  enable_file = function() {
    disable_all();
    $("#upload").show();
    $(".dropzone-wrap").show();
    $(".file_separator").show();
    $("[id^=upload_file]").show();
    $(".add_image").show();
  };

  enable_url = function() {
    disable_all();
    $("#upload").show();
    $("#upload_url").show();

    $('label[for="file_url"]').html(_("URL"));
  };

  enable_embed = function() {
    disable_all();
    $("#upload_embed").show();
  };

  enable_oekaki = function() {
    disable_all();

    window.oekaki.init();
  };

  enable_tegaki = function() {
    disable_all();
    $("#tegaki-form").show();
  }

  if (enabled_url || enabled_embed || enabled_oekaki || enabled_tegaki) {
    $("<tr><th>"+_("Select")+"</th><td id='upload_selection'></td></tr>").insertBefore("#upload");
    var my_html = "<a href='javascript:void(0)' onclick='enable_file(); return false;'>"+_("File")+"</a>";
    if (enabled_url) {
      my_html += " / <a href='javascript:void(0)' onclick='enable_url(); return false;'>"+_("Remote")+"</a>";
    }
    if (enabled_embed) {
      my_html += " / <a href='javascript:void(0)' onclick='enable_embed(); return false;'>"+_("Embed")+"</a>";
    }
    if (enabled_oekaki) {
      my_html += " / <a href='javascript:void(0)' onclick='enable_oekaki(); return false;'>"+_("Oekaki")+"</a>";

      $("#confirm_oekaki_label").hide();
    }
    if (enabled_tegaki) {
      my_html += " / <a href='javascript:void(0)' onclick='enable_tegaki(); return false;'>"+_("Tegaki")+"</a>";
    }
    $("#upload_selection").html(my_html);

    enable_file();
  }
});
