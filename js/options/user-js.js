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

+() => {

const tab = Options.add_tab("user-js", "code", _("User JS"));

const textarea = document.querySelector('<textarea></textarea>').css({
  "font-size": 12,
  position: "absolute",
  top: 35, bottom: 35,
  width: "calc(100% - 20px)", margin: 0, padding: "4px", border: "1px solid black",
  left: 5, right: 5
}).appendTo(tab.content);
const submit = $("<input type='button' value='"+_("Update custom Javascript")+"'>").css({
  position: "absolute",
  height: 25, bottom: 5,
  width: "calc(100% - 10px)",
  left: 5, right: 5
}).click(() => {
  localStorage.user_js = textarea.value;
  document.location.reload();
}).appendTo(tab.content);

const apply_js = () => {
  const proc = () => {
    document.querySelector('.user-js').remove();
    document.querySelector('script')
      .last()
      .after(document.querySelector('<script></script>')
        .classList.add('user-js')
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

const update_textarea = () => {
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
window.load_js = (url) => {
  document.querySelector('script')
    .last()
    .after(document.querySelector('<script></script>')
      .prop("type", "text/javascript")
      .prop("src", url)
    );
};
window.immediate = () => { // A dummy function.
}

}();
