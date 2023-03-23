/*
 * options/user-js.js - allow user enter custom javascripts
 *
 * Copyright (c) 2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/options.js';
 *   $config['additional_javascript'][] = 'js/options/user-js.js';
 */

+function(){

var tab = Options.add_tab("user-js", "code", _("User JS"));

var textarea = $("<textarea></textarea>").css({
  "font-size": 12,
  position: "absolute",
  top: 35, bottom: 35,
  width: "calc(100% - 20px)", margin: 0, padding: "4px", border: "1px solid black",
  left: 5, right: 5
}).appendTo(tab.content);
var submit = $("<input type='button' value='"+_("Update custom Javascript")+"'>").css({
  position: "absolute",
  height: 25, bottom: 5,
  width: "calc(100% - 10px)",
  left: 5, right: 5
}).click(function() {
  localStorage.user_js = textarea.val();
  document.location.reload();
}).appendTo(tab.content);

var apply_js = function() {
  var proc = function() {
    $('.user-js').remove();
    $('script')
      .last()
      .after($("<script></script>")
        .addClass("user-js")
        .text(localStorage.user_js)
      );
  }

  if (/immediate()/.test(localStorage.user_js)) {
    proc(); // Apply the script immediately
  }
  else {
    $(proc); // Apply the script when the page fully loads
  }
};

var update_textarea = function() {
  if (!localStorage.user_js) {
    textarea.text("/* "+_("Enter here your own Javascript code...")+" */\n" +
                  "/* "+_("Have a backup of your storage somewhere, as messing here\nmay render you this website unusable.")+" */\n" +
                  "/* "+_("You can include JS files from remote servers, for example:")+" */\n" +
                  'load_js("http://example.com/script.js");');
  }
  else {
    textarea.text(localStorage.user_js);
    apply_js();
  }
};

update_textarea();


// User utility functions
window.load_js = function(url) {
  $('script')
    .last()
    .after($("<script></script>")
      .prop("type", "text/javascript")
      .prop("src", url)
    );
};
window.immediate = function() { // A dummy function.
}

}();
