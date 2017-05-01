window.oekaki = (function($){
"use strict";

var oekaki = {};
var configRoot = "http://www2.2ch.net/";

oekaki.height = 250;
oekaki.width = 500;

oekaki.base64size = function(input) {
  var input_length = input.length - "data:image/png;base64,".length;

  return ((input_length + ( (input_length % 3) ? (3 - (input_length % 3)) : 0) ) / 3) * 4;
}

oekaki.init = function() {
  var oekaki_form = '<h3><label><input type="checkbox" id="oekaki_toggle" checked="checked">'+_('Post my oekaki')+'</label></h3><div id="wpaintdiv" style="margin-top:50px"></div><br><input type="button" id="size_check" value="'+_('Check image size')+'">';

  // Add oekaki after the file input
  $('form[method="POST"]').append(oekaki_form);

  $('<link class="wpaintcss" rel="stylesheet" href="http://www2.2ch.net/wpaint/oekaki_css.css" />').appendTo($("head"));

  var initcount = 0;
  $('.wpaintcss').on('load', function() {
    initcount++;

    if (initcount == 1) {
      $.extend($.fn.wPaint.defaults, {
        mode:        'pencil',  // set mode
        lineWidth:   '1',       // starting line width
        fillStyle:   '#FFFFFF', // starting fill style
        strokeStyle: '#000000',  // start stroke style
      });

      delete $.fn.wPaint.menus.main.items.save;

      $('#wpaintdiv').wPaint({
        path: configRoot+"/wpaint/",
	menuOffsetTop: -46,
	bg: "#ffffff",
	loadImgFg:   oekaki.load_img,
	loadImgBg:   oekaki.load_img
      });
    }
  });

  $("#size_check").on("click", function(e) {
    e.preventDefault()
    alert(_("Your image size is %size%/%maxsize%.").replace("%size%", oekaki.base64size($("#wpaintdiv").wPaint("image"))).replace("%maxsize%", "128000"));
  });

  $('form[method="POST"]').on("submit", function(e) {
    if (!$('#oekaki_toggle').is(':checked')) return true;

    if ($('canvas.wPaint-canvas').width() != oekaki.width || $('canvas.wPaint-canvas').height() != oekaki.height) {
      alert(_("Image must be 500x250 to be uploaded. Please resize it.")); e.preventDefault(); return false;
    }

    if (oekaki.base64size($("#wpaintdiv").wPaint("image"))>128000) {
     alert(_("Your image is too large, it needs to be 128KB or less.")); e.preventDefault(); return false;
    }

    var oekaki_data_url = $('<input type="hidden" name="oekaki" id="oekaki_data_url">');
    oekaki_data_url.val($("#wpaintdiv").wPaint("image"));
    $(this).append(oekaki_data_url);
    return true;
  });

  $("#wpaintdiv").width(oekaki.width).height(oekaki.height).css("position", "relative");
  $("#wpaintctr").width(oekaki.width+5).height(oekaki.height+5).css("padding-top", 48).css("position", "relative");

  oekaki.initialized = true;
};

oekaki.load_img = function() {
  alert(_("Click on any image on this site to load it into oekaki applet."));
  $('img').one('click.loadimg', function(e) {
    $('img').off('click.loadimg');
    e.stopImmediatePropagation();
    e.preventDefault();
    var url = $(this).prop('src');
    $('#wpaintdiv').wPaint('setImage', url, '', false);
    return false;
  });
};

oekaki.initialized = false;
return oekaki;
})(jQuery);
