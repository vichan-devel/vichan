/*
 * titlebar-notifications.js - a library for showing number of new events in titlebar
 * https://github.com/vichan-devel/Tinyboard/blob/master/js/titlebar-notifications.js
 *
 * Released under the MIT license
 * Copyright (c) 2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/titlebar-notifications.js';
 *   //$config['additional_javascript'][] = 'js/auto-reload.js';
 *   //$config['additional_javascript'][] = 'js/watch.js';
 *
 */

const orig_title = document.title;

$(() => {
  orig_title = document.title;
});

update_title = () => {
  const updates = 0;
  for(var i in title_collectors) {
    updates += title_collectors[i]();
  }
  document.title = (updates ? "("+updates+") " : "") + orig_title;
};

const title_collectors = [];
add_title_collector = (f) => {
  title_collectors.push(f);
};
