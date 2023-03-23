/*
 * show-own-posts.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/show-op.js
 *
 * Adds "(You)" to a name field when the post is yours. Update references as well.
 *
 * Released under the MIT license
 * Copyright (c) 2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/ajax.js';
 *   $config['additional_javascript'][] = 'js/show-own-posts.js';
 *
 */


+function(){


var update_own = function() {
  if ($(this).is('.you')) return;

  var thread = $(this).parents('[id^="thread_"]').first();
  if (!thread.length) {
    thread = $(this);
  }

  var board = thread.attr('data-board');
  var posts = JSON.parse(localStorage.own_posts || '{}');

  var id = $(this).attr('id').split('_')[1];

  if (posts[board] && posts[board].indexOf(id) !== -1) { // Own post!
    $(this).addClass('you');
    $(this).find('span.name').first().append(' <span class="own_post">'+_('(You)')+'</span>');
  }

  // Update references
  $(this).find('div.body:first a:not([rel="nofollow"])').each(function() {
    var postID;

    if(postID = $(this).text().match(/^>>(\d+)$/))
      postID = postID[1];
    else
      return;

    if (posts[board] && posts[board].indexOf(postID) !== -1) {
      $(this).after(' <small>'+_('(You)')+'</small>');
    }
  });
};

var update_all = function() {
  $('div[id^="thread_"], div.post.reply').each(update_own);
};

var board = null;

$(function() {
  board = $('input[name="board"]').first().val();

  update_all();
});

$(document).on('ajax_after_post', function(e, r) {
  var posts = JSON.parse(localStorage.own_posts || '{}');
  posts[board] = posts[board] || [];
  posts[board].push(r.id);
  localStorage.own_posts = JSON.stringify(posts);
});

$(document).on('new_post', function(e,post) {
  var $post = $(post);
  if ($post.is('div.post.reply')) { // it's a reply
    $post.each(update_own);
  }
  else {
    $post.each(update_own); // first OP
    $post.find('div.post.reply').each(update_own); // then replies
  }
});



}();
