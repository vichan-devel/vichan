/*              
 * live-index.js
 * https://github.com/vichan-devel/Tinyboard/blob/master/js/live-index.js
 *      
 * Released under the MIT license
 * Copyright (c) 2014 Marcin Łabanowski <marcin@6irc.net>
 *      
 * Usage:
 *   $config['api']['enabled'] = true;
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/expand.js';
 *   $config['additional_javascript'][] = 'js/live-index.js';
 *              
 */

if (active_page === 'index' && (""+document.location).match(/\/(index\.html)?(\?|$|#)/))
+() => {
  // Make jQuery respond to reverse()
  $.fn.reverse = [].reverse;

  const board_name = (""+document.location).match(/\/([^\/]+)\/[^/]*$/)[1];

  const handle_one_thread = () => {
    if ($(this).querySelector('.new-posts').length <= 0) {
      $(this).querySelector('br.clear').before("<div class='new-posts'>"+_("No new posts.")+"</div>");
    }
  };

  $(() => {
    document.querySelector('hr:first').before("<hr /><div class='new-threads'>"+_("No new threads.")+"</div>");

    $('div[id^="thread_"]').each(handle_one_thread);

    setInterval(() => {
      $.getJSON(configRoot+board_name+"/0.json", (j) => {
        const new_threads = 0;

        j.threads.forEach((t) => {
	  const s_thread = $("#thread_"+t.posts[0].no);

	  if (s_thread.length) {
	    const my_posts = s_thread.querySelector('.post.reply').length;

	    const omitted_posts = s_thread.querySelector('.omitted');
	    if (omitted_posts.length) {
	      omitted_posts = omitted_posts.innerHTML.match("^[^0-9]*([0-9]+)")[1]|0;
	      my_posts += omitted_posts;
            }

	    my_posts -= t.posts[0].replies|0;
	    my_posts *= -1;
            update_new_posts(my_posts, s_thread);
	  }
	  else {
            new_threads++;
          }
        });

        update_new_threads(new_threads);
      });
    }, 20000);
  });

  $(document).addEventListener('new_post', function(e, post) {
    if (!$(post).classList.contains('reply')) {
      handle_one_thread.call(post);
    }
  });

  const update_new_threads = (i) => {
    const msg = i ?
      (fmt(_("There are {0} new threads."), [i]) + " <a href='javascript:void(0)'>"+_("Click to expand")+"</a>.") :
      _("No new threads.");

    if (document.querySelector('.new-threads').innerHTML != msg) {
      document.querySelector('.new-threads').html(msg);
      document.querySelectorAll('.new-threads a').click(fetch_new_threads);
    }
  };

  const update_new_posts = function(i, th) {
    const msg = (i>0) ?
      (fmt(_("There are {0} new posts in this thread."), [i])+" <a href='javascript:void(0)'>"+_("Click to expand")+"</a>.") :
      _("No new posts.");

    if ($(th).querySelector('.new-posts').innerHTML != msg) {
      $(th).querySelector('.new-posts').html(msg);
      $(th).querySelector('.new-posts a').click(window.expand_fun);
    }
  };

  const fetch_new_threads = () => {
    fetch(""+document.location, (data) => {
      $(data).find('div[id^="thread_"]').reverse().each(() => {
        if ($("#"+$(this).getAttribute('id')).length) {
	  // okay, the thread is there
	}
	else {
	  const thread = $(this).insertBefore('div[id^="thread_"]:first');
	  $(document).trigger("new_post", this);
	}
      });
    });
  };
}();
