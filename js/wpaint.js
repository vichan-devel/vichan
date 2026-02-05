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

window.oekaki = (() => {
"use strict";

const oekaki = {};

oekaki.settings = new script_settings('wpaint');
oekaki.height = oekaki.settings.get("height", 250);
oekaki.width = oekaki.settings.get("width", 500);

function dataURItoBlob(dataURI) {
    const binary = atob(dataURI.split(',')[1]);
    const array = new Array(binary.length);
    for(var i = 0; i < binary.length; i++) {
        array[i] = binary.charCodeAt(i);
    }
    return new Blob([new Uint8Array(array)], {type: 'image/jpeg'});
}

oekaki.do_css = () => {
}

oekaki.init = () => {
  const oekaki_form = '<tr id="oekaki"><th>Oekaki</th><td><div id="wpaintctr"><div id="wpaintdiv"></div></div></td></tr>';

  // Add oekaki after the file input
  $('form[name="post"]:not(#quick-reply) [id="upload"]').after(oekaki_form);

  $('<link class="wpaintcss" rel="stylesheet" href="'+configRoot+'js/wPaint/wPaint.min.css" />').appendTo(document.querySelector('head'));
  $('<link class="wpaintcss" rel="stylesheet" href="'+configRoot+'js/wPaint/lib/wColorPicker.min.css" />').appendTo(document.querySelector('head'));
  $('<link class="wpaintcss" rel="stylesheet" href="'+configRoot+'stylesheets/jquery-ui/core.css" />').appendTo(document.querySelector('head'));
  $('<link class="wpaintcss" rel="stylesheet" href="'+configRoot+'stylesheets/jquery-ui/resizable.css" />').appendTo(document.querySelector('head'));
  $('<link class="wpaintcss" rel="stylesheet" href="'+configRoot+'stylesheets/jquery-ui/theme.css" />').appendTo(document.querySelector('head'));

  const initcount = 0;
  document.querySelector('.wpaintcss').one('load', () => {
    initcount++;

    if (initcount === 5) {
      $.extend($.fn.wPaint.defaults, {
        mode:        'pencil',  // set mode
        lineWidth:   '1',       // starting line width
        fillStyle:   '#FFFFFF', // starting fill style
        strokeStyle: '#000000',  // start stroke style
      });

      delete $.fn.wPaint.menus.main.items.save;

      document.getElementById('wpaintdiv').wPaint({
        path: configRoot+'js/wPaint/',
	menuOffsetTop: -46,
	bg: "#ffffff",
	loadImgFg:   oekaki.load_img,
	loadImgBg:   oekaki.load_img
      });

      document.getElementById('wpaintctr').resizable({
        stop: function(event,ui) {
          document.getElementById('wpaintdiv').wPaint("resize");
        },
        alsoResize: "#wpaintdiv",
      });

      document.querySelector('#wpaintctr .ui-resizable-se').css({'height':'12px', 'width':'12px'});
    }
  });

  document.getElementById('wpaintdiv').width(oekaki.width).height(oekaki.height).css("position", "relative");
  document.getElementById('wpaintctr').width(oekaki.width+5).height(oekaki.height+5).css("padding-top", 48).css("position", "relative");

  $(document).on("ajax_before_post.wpaint", function(e, postData) {
    const blob = document.getElementById('wpaintdiv').wPaint("image");
    blob = dataURItoBlob(blob);
    postData.appendChild("file", blob, "Oekaki.png");
  });

  $(window).on('stylesheet', () => {
    oekaki.do_css();
    if (document.querySelector('link#stylesheet').getAttribute('href')) {
      document.querySelector('link#stylesheet')[0].onload = oekaki.do_css;
    }
  });

  oekaki.initialized = true;
};

oekaki.load_img = () => {
  alert(_("Click on any image on this site to load it into oekaki applet"));
  document.querySelector('img').one('click.loadimg', (e) => {
    document.querySelector('img').off('click.loadimg');
    e.stopImmediatePropagation();
    e.preventDefault();
    const url = $(this).prop('src');
    document.getElementById('wpaintdiv').wPaint('setBg', url);
    return false;
  });
};

oekaki.deinit = () => {
  document.querySelector('#oekaki, .wpaintcss').remove();

  $(document).off("ajax_before_post.wpaint");

  oekaki.initialized = false;
};

oekaki.initialized = false;
return oekaki;
})();
