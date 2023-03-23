/*
 * options/user-css.js - allow user enter custom css entries
 *
 * Copyright (c) 2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/options.js';
 *   $config['additional_javascript'][] = 'js/options/user-css.js';
 */

+function(){

var tab = Options.add_tab("user-css", "css3", _("User CSS"));

var textarea = $("<textarea></textarea>").css({
  "font-size": 12,
  position: "absolute",
  top: 35, bottom: 35,
  width: "calc(100% - 20px)", margin: 0, padding: "4px", border: "1px solid black",
  left: 5, right: 5
}).appendTo(tab.content);
var submit = $("<input type='button' value='"+_("Update custom CSS")+"'>").css({
  position: "absolute",
  height: 25, bottom: 5,
  width: "calc(100% - 10px)",
  left: 5, right: 5
}).click(function() {
  localStorage.user_css = textarea.val();
  apply_css();
}).appendTo(tab.content);

var apply_css = function() {
  $('.user-css').remove();
  $('link[rel="stylesheet"]')
    .last()
    .after($("<style></style>")
      .addClass("user-css")
      .text(localStorage.user_css)
    );
};

var update_textarea = function() {
  if (!localStorage.user_css) {
    textarea.text("/* "+_("Enter here your own CSS rules...")+" */\n" +
                  "/* "+_("If you want to make a redistributable style, be sure to\nhave a Yotsuba B theme selected.")+" */\n" +
                  "/* "+_("You can include CSS files from remote servers, for example:")+" */\n" +
                  '@import "http://example.com/style.css";');
  }
  else {
    textarea.text(localStorage.user_css);
    apply_css();
  }
};

update_textarea();


}();
