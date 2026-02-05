/*
 * options.js - allow users choose board options as they wish
 *
 * Copyright (c) 2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/options.js';
 */

+() => {

const options_button, options_handler, options_background, options_div
  , options_close, options_tablist, options_tabs, options_current_tab;

const Options = {};
window.Options = Options;

const first_tab = () => {
  for (var i in options_tabs) {
    return i;
  }
  return false;
};

Options.show = () => {
  if (!options_current_tab) {
    Options.select_tab(first_tab(), true);
  }
  options_handler.fadeIn();
};
Options.hide = () => {
  options_handler.fadeOut();
};

options_tabs = {};

Options.add_tab = function(id, icon, name, content) {
  const tab = {};

  if (typeof content === "string") {
    content = $("<div>"+content+"</div>");
  }

  tab.id = id;
  tab.name = name;
  tab.icon = $("<div class='options_tab_icon'><i class='fa fa-"+icon+"'></i><div>"+name+"</div></div>");
  tab.content = $("<div class='options_tab'></div>").css("display", "none");

  tab.content.appendTo(options_div);

  tab.icon.on("click", () => {
    Options.select_tab(id);
  }).appendTo(options_tablist);

  $("<h2>"+name+"</h2>").appendTo(tab.content);

  if (content) {
    content.appendTo(tab.content);
  }
  
  options_tabs[id] = tab;

  return tab;
};

Options.get_tab = (id) => {
  return options_tabs[id];
};

Options.extend_tab = function(id, content) {
  if (typeof content === "string") {
    content = $("<div>"+content+"</div>");
  }

  content.appendTo(options_tabs[id].content);

  return options_tabs[id];
};

Options.select_tab = function(id, quick) {
  if (options_current_tab) {
    if (options_current_tab.id === id) {
      return false;
    }
    options_current_tab.content.fadeOut();
    options_current_tab.icon.classList.remove('active');
  }
  const tab = options_tabs[id];
  options_current_tab = tab;
  options_current_tab.icon.classList.add('active');
  tab.content[quick? "show" : "fadeIn"]();

  return tab;
};

options_handler = $("<div id='options_handler'></div>").css("display", "none");
options_background = $("<div id='options_background'></div>").on("click", Options.hide).appendTo(options_handler);
options_div = $("<div id='options_div'></div>").appendTo(options_handler);
options_close = $("<a id='options_close' href='javascript:void(0)'><i class='fa fa-times'></i></div>")
  .on("click", Options.hide).appendTo(options_div);
options_tablist = $("<div id='options_tablist'></div>").appendTo(options_div);


$(() => {
  options_button = $("<a href='javascript:void(0)' title='"+_("Options")+"'>["+_("Options")+"]</a>").css("float", "right");

  if (document.querySelectorAll('.boardlist.compact-boardlist').length) {
    options_button.classList.add('cb-item cb-fa').html("<i class='fa fa-gear'></i>");
  }

  if (document.querySelectorAll('.boardlist:first').length) {
    options_button.appendTo(document.querySelectorAll('.boardlist:first'));
  }
  else {
    options_button.prependTo($(document.body));
  }

  options_button.on("click", Options.show);

  options_handler.appendTo($(document.body));
});



}();
