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

$("<h3 style='margin:0'>"+_("Do not paste code here unless you absolutely trust the source or have read it yourself!")+"</h3><span class='unimportant'>"+_("Untrusted code pasted here could do malicious things such as spam the site under your IP.")+"</span>").appendTo(tab.content);

var textarea = $("<textarea></textarea>").css({
  "height"     : "74%",
  "width"      : "100%",
  "font-size"  : "9pt",
  "font-family": "monospace",
}).appendTo(tab.content);
var submit = $("<input type='button' value='"+_("Save custom Javascript")+"'>").css({
  "width": "100%",
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
    textarea.text("/* "+_("Enter your own Javascript code here...")+" */\n" +
                  "/* "+_("You can include JS files from remote servers, for example:")+" */\n" +
                  '/* load_js("http://example.com/script.js"); */');
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
