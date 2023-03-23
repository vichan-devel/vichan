/*
 * wpaint.js - wPaint integration javascript
 * https://github.com/vichan-devel/Tinyboard/blob/master/js/wpaint.js
 *
 * Released under the MIT license
 * Copyright (c) 2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Contains parts of old oekaki code:
 * Copyright (c) 2013 copypaste <wizardchan@hush.com>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/jquery-ui.custom.min.js';
 *   $config['additional_javascript'][] = 'js/ajax.js';
 *   $config['additional_javascript'][] = 'js/wPaint/8ch.js';
 *   $config['additional_javascript'][] = 'js/wpaint.js';
 *   $config['additional_javascript'][] = 'js/upload-selection.js';
 *
 */

window.oekaki = (function(){
"use strict";

var oekaki = {};

oekaki.settings = new script_settings('wpaint');
oekaki.height = oekaki.settings.get("height", 250);
oekaki.width = oekaki.settings.get("width", 500);

function dataURItoBlob(dataURI) {
    var binary = atob(dataURI.split(',')[1]);
    var array = new Array(binary.length);
    for(var i = 0; i < binary.length; i++) {
        array[i] = binary.charCodeAt(i);
    }
    return new Blob([new Uint8Array(array)], {type: 'image/jpeg'});
}

oekaki.do_css = function() {
}

oekaki.init = function() {
  var oekaki_form = '<tr id="oekaki"><th>Oekaki</th><td><div id="wpaintctr"><div id="wpaintdiv"></div></div></td></tr>';

  // Add oekaki after the file input
  $('form[name="post"]:not(#quick-reply) [id="upload"]').after(oekaki_form);

  $('<link class="wpaintcss" rel="stylesheet" href="'+configRoot+'js/wPaint/wPaint.min.css" />').appendTo($("head"));
  $('<link class="wpaintcss" rel="stylesheet" href="'+configRoot+'js/wPaint/lib/wColorPicker.min.css" />').appendTo($("head"));
  $('<link class="wpaintcss" rel="stylesheet" href="'+configRoot+'stylesheets/jquery-ui/core.css" />').appendTo($("head"));
  $('<link class="wpaintcss" rel="stylesheet" href="'+configRoot+'stylesheets/jquery-ui/resizable.css" />').appendTo($("head"));
  $('<link class="wpaintcss" rel="stylesheet" href="'+configRoot+'stylesheets/jquery-ui/theme.css" />').appendTo($("head"));

  var initcount = 0;
  $('.wpaintcss').one('load', function() {
    initcount++;

    if (initcount == 5) {
      $.extend($.fn.wPaint.defaults, {
        mode:        'pencil',  // set mode
        lineWidth:   '1',       // starting line width
        fillStyle:   '#FFFFFF', // starting fill style
        strokeStyle: '#000000',  // start stroke style
      });

      delete $.fn.wPaint.menus.main.items.save;

      $('#wpaintdiv').wPaint({
        path: configRoot+'js/wPaint/',
	menuOffsetTop: -46,
	bg: "#ffffff",
	loadImgFg:   oekaki.load_img,
	loadImgBg:   oekaki.load_img
      });

      $("#wpaintctr").resizable({
        stop: function(event,ui) {
          $("#wpaintdiv").wPaint("resize");
        },
        alsoResize: "#wpaintdiv",
      });

      $('#wpaintctr .ui-resizable-se').css({'height':'12px', 'width':'12px'});
    }
  });

  $("#wpaintdiv").width(oekaki.width).height(oekaki.height).css("position", "relative");
  $("#wpaintctr").width(oekaki.width+5).height(oekaki.height+5).css("padding-top", 48).css("position", "relative");

  $(document).on("ajax_before_post.wpaint", function(e, postData) {
    var blob = $('#wpaintdiv').wPaint("image");
    blob = dataURItoBlob(blob);
    postData.append("file", blob, "Oekaki.png");
  });

  $(window).on('stylesheet', function() {
    oekaki.do_css();
    if ($('link#stylesheet').attr('href')) {
      $('link#stylesheet')[0].onload = oekaki.do_css;
    }
  });

  oekaki.initialized = true;
};

oekaki.load_img = function() {
  alert(_("Click on any image on this site to load it into oekaki applet"));
  $('img').one('click.loadimg', function(e) {
    $('img').off('click.loadimg');
    e.stopImmediatePropagation();
    e.preventDefault();
    var url = $(this).prop('src');
    $('#wpaintdiv').wPaint('setBg', url);
    return false;
  });
};

oekaki.deinit = function() {
  $('#oekaki, .wpaintcss').remove();

  $(document).off("ajax_before_post.wpaint");

  oekaki.initialized = false;
};

oekaki.initialized = false;
return oekaki;
})();
