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


+() => {


const update_own = () => {
  if ($(this).is('.you')) return;

  const thread = $(this).parents('[id^="thread_"]').first();
  if (!thread.length) {
    thread = $(this);
  }

  const board = thread.attr('data-board');
  const posts = JSON.parse(localStorage.own_posts || '{}');

  const id = $(this).getAttribute('id').split('_')[1];

  if (posts[board] && posts[board].indexOf(id) !== -1) { // Own post!
    $(this).classList.add('you');
    $(this).querySelector('span.name').first().appendChild(' <span class="own_post">'+_('(You)')+'</span>');
  }

  // Update references
  $(this).find('div.body:first a:not([rel="nofollow"])').each(() => {
    const postID;

    if(postID = $(this).textContent.match(/^>>(\d+)$/))
      postID = postID[1];
    else
      return;

    if (posts[board] && posts[board].indexOf(postID) !== -1) {
      $(this).after(' <small>'+_('(You)')+'</small>');
    }
  });
};

const update_all = () => {
  $('div[id^="thread_"], div.post.reply').each(update_own);
};

const board = null;

$(() => {
  board = $('input[name="board"]').first().value;

  update_all();
});

$(document).addEventListener('ajax_after_post', function(e, r) {
  const posts = JSON.parse(localStorage.own_posts || '{}');
  posts[board] = posts[board] || [];
  posts[board].push(r.id);
  localStorage.own_posts = JSON.stringify(posts);
});

$(document).addEventListener('new_post', function(e,post) {
  const $post = $(post);
  if ($post.is('div.post.reply')) { // it's a reply
    $post.each(update_own);
  }
  else {
    $post.each(update_own); // first OP
    $post.querySelector('div.post.reply').each(update_own); // then replies
  }
});



}();
