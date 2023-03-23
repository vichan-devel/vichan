/*
 * options/general.js - general settings tab for options panel
 *
 * Copyright (c) 2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/options.js';
 *   $config['additional_javascript'][] = 'js/style-select.js';
 *   $config['additional_javascript'][] = 'js/options/general.js';
 */

+function(){

var tab = Options.add_tab("general", "home", _("General"));

$(function(){
  var stor = $("<div>"+_("Storage: ")+"</div>");
  stor.appendTo(tab.content);

  $("<button>"+_("Export")+"</button>").appendTo(stor).on("click", function() {
    var str = JSON.stringify(localStorage);

    $(".output").remove();
    $("<input type='text' class='output'>").appendTo(stor).val(str);
  });
  $("<button>"+_("Import")+"</button>").appendTo(stor).on("click", function() {
    var str = prompt(_("Paste your storage data"));
    if (!str) return false;
    var obj = JSON.parse(str);
    if (!obj) return false;

    localStorage.clear();
    for (var i in obj) {
      localStorage[i] = obj[i];
    }

    document.location.reload();
  });
  $("<button>"+_("Erase")+"</button>").appendTo(stor).on("click", function() {
    if (confirm(_("Are you sure you want to erase your storage? This involves your hidden threads, watched threads, post password and many more."))) {
      localStorage.clear();
      document.location.reload();
    }
  });


  $("#style-select").detach().css({float:"none","margin-bottom":0}).appendTo(tab.content);
});

}();
