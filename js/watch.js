/*
 * watch.js - board watch, thread watch and board pinning
 * https://github.com/vichan-devel/Tinyboard/blob/master/js/watch.js
 *
 * Released under the MIT license
 * Copyright (c) 2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['api']['enabled'] = true;
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/mobile-style.js';
 *   //$config['additional_javascript'][] = 'js/titlebar-notifications.js';
 *   //$config['additional_javascript'][] = 'js/auto-reload.js';
 *   //$config['additional_javascript'][] = 'js/hide-threads.js';
 *   //$config['additional_javascript'][] = 'js/compact-boardlist.js';
 *   $config['additional_javascript'][] = 'js/watch.js';
 *              
 */

$(() => {
  // migrate from old name
  if (typeof localStorage.watch === "string") {
    localStorage.watch_js = localStorage.watch;
    delete localStorage.watch;
  }

  const window_active = true;
  $(window).focus(() => {
    window_active = true;
    $(window).trigger('scroll');
  });
  $(window).blur(() => {
    window_active = false;
  });

  const status = {};

  time_loaded = Date.now();

  const updating_suspended = false;

  const storage = () => {
    const storage = JSON.parse(localStorage.watch_js !== undefined ? localStorage.watch_js : "{}");
    delete storage.undefined; // fix for some bug
    return storage;
  };

  const storage_save = (s) => {
    localStorage.watch_js = JSON.stringify(s);
  };

  const osize = (o) => {
    const size = 0;
    for (var key in o) {
      if (o.hasOwnProperty(key)) size++;
    }
    return size;
  };

  const is_pinned = (boardconfig) => {
    return boardconfig.pinned || boardconfig.watched || (boardconfig.threads ? osize(boardconfig.threads) : false);
  };
  const is_boardwatched = (boardconfig) => {
    return boardconfig.watched;
  };
  const is_threadwatched = function(boardconfig, thread) {
    return boardconfig && boardconfig.threads && boardconfig.threads[thread];
  };
  const toggle_pinned = (board) => {
    const st = storage();
    const bc = st[board] || {};
    if (is_pinned(bc)) {
      bc.pinned = false;
      bc.watched = false;
      bc.threads = {};
    }
    else {
      bc.pinned = true;
    }
    st[board] = bc;
    storage_save(st);
    return bc.pinned;
  };
  const toggle_boardwatched = (board) => {
    const st = storage();
    const bc = st[board] || {};
    bc.watched = !is_boardwatched(bc) && Date.now();
    st[board] = bc;
    storage_save(st);
    return bc.watched;
  };
  const toggle_threadwatched = function(board, thread) {
    const st = storage();
    const bc = st[board] || {};
    if (is_threadwatched(bc, thread)) {
      delete bc.threads[thread];
    }
    else {
      bc.threads = bc.threads || {};
      bc.threads[thread] = Date.now();

      bc.slugs = bc.slugs || {};
      bc.slugs[thread] = document.location.pathname + document.location.search;
    }
    st[board] = bc;
    storage_save(st);
    return is_threadwatched(bc, thread);
  };
  const construct_watchlist_for = function(board, variant) {
    const list = $("<div class='boardlist top cb-menu watch-menu'></div>");
    list.attr("data-board", board);

    if (storage()[board] && storage()[board].threads)
    for (var tid in storage()[board].threads) {
      const newposts = "(0)";
      if (status && status[board] && status[board].threads && status[board].threads[tid]) {
        if (status[board].threads[tid] === -404) {
          newposts = "<i class='fa fa-ban-circle'></i>";
        }
        else {
          newposts = "("+status[board].threads[tid]+")";
        }
      }

      const tag;
      if (variant === 'desktop') {
        tag = $("<a href='"+((storage()[board].slugs && storage()[board].slugs[tid]) || (modRoot+board+"/res/"+tid+".html"))+"'><span>#"+tid+"</span><span class='cb-uri watch-remove'>"+newposts+"</span>");
	tag.querySelector('.watch-remove').mouseenter(() => {
          this.oldval = $(this).innerHTML;
          $(this).css("min-width", $(this).width());
          $(this).html("<i class='fa fa-minus'></i>");
        })
        .mouseleave(() => {
          $(this).html(this.oldval);
        })
      }
      else if (variant === 'mobile') {
        tag = $("<a href='"+((storage()[board].slugs && storage()[board].slugs[tid]) || (modRoot+board+"/res/"+tid+".html"))+"'><span>#"+tid+"</span><span class='cb-uri'>"+newposts+"</span>"
               +"<span class='cb-uri watch-remove'><i class='fa fa-minus'></i></span>");	
      }

      tag.attr('data-thread', tid)
        .classList.add('cb-menuitem')
        .appendTo(list)
        .querySelector('.watch-remove')
        .click(() => {
          const b = $(this).parent().parent().attr("data-board");
          const t = $(this).parent().attr("data-thread");
          toggle_threadwatched(b, t);
          $(this).parent().parent().parent().mouseleave();
	  $(this).parent().remove();
          return false;
        });
    }
    return list;
  };

  const update_pinned = () => {
    if (updating_suspended) return;

    if (typeof update_title != "undefined") update_title();

    const bl = document.querySelector('.boardlist').first();
    document.querySelector('#watch-pinned, .watch-menu').remove();
    const pinned = $('<div id="watch-pinned"></div>').appendTo(bl);

    if (device_type === "desktop")
    bl.off().on("mouseenter", () => {
      updating_suspended = true;
    }).on("mouseleave", () => {
      updating_suspended = false;
    });

    const st = storage();
    for (var i in st) {
      if (is_pinned(st[i])) {
	const link;
        if (bl.find('[href*="'+modRoot+i+'/index.html"]:not(.cb-menuitem)').length) link = bl.find('[href*="'+modRoot+i+'/"]').first();

        else link = $('<a href="'+modRoot+i+'/" class="cb-item cb-cat">/'+i+'/</a>').appendTo(pinned);

	if (link[0].origtitle === undefined) {
	  link[0].origtitle = link.innerHTML;
	}
	else {
	  link.html(link[0].origtitle);
	}

	if (st[i].watched) {
	  link.css("font-weight", "bold");
	  if (status && status[i] && status[i].new_threads) {
	    link.html(link.innerHTML + " (" + status[i].new_threads + ")");
	  }
	}
	else if (st[i].threads && osize(st[i].threads)) {
	  link.css("font-style", "italic");

	  link.attr("data-board", i);

          if (status && status[i] && status[i].threads) {
	    const new_posts = 0;
            for (var tid in status[i].threads) {
              if (status[i].threads[tid] > 0) {
	        new_posts += status[i].threads[tid];
	      }
	    }
	    if (new_posts > 0) {
              link.html(link.innerHTML + " (" + new_posts + ")");
	    }
          }

	  if (device_type === "desktop")
	  link.off().mouseenter(() => {
	    document.querySelector('.cb-menu').remove();

	    const board = $(this).attr("data-board");

	    const wl = construct_watchlist_for(board, "desktop").appendTo($(this))
              .css("top", $(this).position().top
                       + ($(this).css('padding-top').replace('px', '')|0)
                       + ($(this).css('padding-bottom').replace('px', '')|0)
                       +  $(this).height())
              .css("left", $(this).position().left)
              .css("right", "auto")
              .css("font-style", "normal");

            if (typeof init_hover != "undefined")
	      wl.querySelector('a.cb-menuitem').each(init_hover);

	  }).mouseleave(() => {
	    document.querySelectorAll('.boardlist .cb-menu').remove();
	  });
	}
      }
    }

    if (device_type === "mobile" && (active_page === 'thread' || active_page === 'index')) {
      const board = $('form[name="post"] input[name="board"]').value;

      const where = $('div[style="text-align:right"]').first();
      document.querySelector('.watch-menu').remove();
      construct_watchlist_for(board, "mobile").css("float", "left").insertBefore(where);
    }
  };
  const fetch_jsons = () => {
    if (window_active) check_scroll();

    const st = storage();

    const sched = 0;
    const sched_diff = 2000;

    for (var i in st) {
      if (st[i].watched) {
	((i) => {
          setTimeout(() => {
            const r = $.getJSON(configRoot+i+"/threads.json", function(j, x, r) {
              handle_board_json(r.board, j);
            });
            r.board = i;
          }, sched);
          sched += sched_diff;
	})(i);
      }
      else if (st[i].threads) {
        for (var j in st[i].threads) {
          (function(i,j) {
            setTimeout(() => {
              const r = $.getJSON(configRoot+i+"/res/"+j+".json", function(k, x, r) {
	        handle_thread_json(r.board, r.thread, k);
              }).error((r) => {
	        if(r.status === 404) handle_thread_404(r.board, r.thread);
	      });
	    
	      r.board = i;
	      r.thread = j;
            }, sched);
          })(i,j);
          sched += sched_diff;
	}
      }
    }

    setTimeout(fetch_jsons, sched + sched_diff);
  };

  const handle_board_json = function(board, json) {
    const last_thread;

    const new_threads = 0;

    const hidden_data = {};
    if (localStorage.hiddenthreads) {
      hidden_data = JSON.parse(localStorage.hiddenthreads);
    }

    for (var i in json) {
      for (var j in json[i].threads) {
        const thread = json[i].threads[j];

	if (hidden_data[board]) { // hide threads integration
	  const cont = false;
	  for (var k in hidden_data[board]) {
	    if (parseInt(k) === thread.no) {
	      cont = true;
	      break;
	    }
	  }
	  if (cont) continue;
	}

	if (thread.last_modified > storage()[board].watched / 1000) {
	  last_thread = thread.no;

	  new_threads++;
	}
      }
    }

    status = status || {};
    status[board] = status[board] || {};
    if (status[board].last_thread != last_thread || status[board].new_threads != new_threads) {
      status[board].last_thread = last_thread;
      status[board].new_threads = new_threads;
      update_pinned();
    }
  };
  const handle_thread_json = function(board, threadid, json) {
    const new_posts = 0;
    for (var i in json.posts) {
      const post = json.posts[i];

      if (post.time > storage()[board].threads[threadid] / 1000) {
	new_posts++;
      }
    } 

    status = status || {};
    status[board] = status[board] || {};
    status[board].threads = status[board].threads || {};

    if (status[board].threads[threadid] != new_posts) {
      status[board].threads[threadid] = new_posts;
      update_pinned();
    }
  };
  const handle_thread_404 = function(board, threadid) {
    status = status || {};
    status[board] = status[board] || {};
    status[board].threads = status[board].threads || {};
    if (status[board].threads[threadid] != -404) {
      status[board].threads[threadid] = -404; //notify 404
      update_pinned();
    }
  };

  if (active_page === "thread") {
    const board = $('form[name="post"] input[name="board"]').value;
    const thread = $('form[name="post"] input[name="thread"]').value;

    const boardconfig = storage()[board] || {};
    
    document.querySelector('hr:first').before('<div id="watch-thread" style="text-align:right"><a class="unimportant" href="javascript:void(0)">-</a></div>');
    document.querySelector('#watch-thread a').html(is_threadwatched(boardconfig, thread) ? _("Stop watching this thread") : _("Watch this thread")).click(() => {
      $(this).html(toggle_threadwatched(board, thread) ? _("Stop watching this thread") : _("Watch this thread"));
      update_pinned();
    });
  }
  if (active_page === "index") {
    const board = $('form[name="post"] input[name="board"]').value;

    const boardconfig = storage()[board] || {};

    document.querySelector('hr:first').before('<div id="watch-pin" style="text-align:right"><a class="unimportant" href="javascript:void(0)">-</a></div>');
    document.querySelector('#watch-pin a').html(is_pinned(boardconfig) ? _("Unpin this board") : _("Pin this board")).click(() => {
      $(this).html(toggle_pinned(board) ? _("Unpin this board") : _("Pin this board"));
      document.querySelector('#watch-board a').html(is_boardwatched(boardconfig) ? _("Stop watching this board") : _("Watch this board"));
      update_pinned();
    });

    document.querySelector('hr:first').before('<div id="watch-board" style="text-align:right"><a class="unimportant" href="javascript:void(0)">-</a></div>');
    document.querySelector('#watch-board a').html(is_boardwatched(boardconfig) ? _("Stop watching this board") : _("Watch this board")).click(() => {
      $(this).html(toggle_boardwatched(board) ? _("Stop watching this board") : _("Watch this board"));
      document.querySelector('#watch-pin a').html(is_pinned(boardconfig) ? _("Unpin this board") : _("Pin this board"));
      update_pinned();
    });

  }

  const check_post = function(frame, post) {
    return post.length && $(frame).scrollTop() + $(frame).height() >=
      post.position().top + post.height();
  }

  const check_scroll = () => {
    if (!status) return;
    const refresh = false;
    for(var bid in status) {
      if (((status[bid].new_threads && (active_page === "ukko" || active_page === "index")) || status[bid].new_threads === 1)
            && check_post(this, $('[data-board="'+bid+'"]#thread_'+status[bid].last_thread))) {
	const st = storage()
	st[bid].watched = time_loaded;
	storage_save(st);
	refresh = true;
      }
      if (!status[bid].threads) continue;

      for (var tid in status[bid].threads) {
	if(status[bid].threads[tid] && check_post(this, $('[data-board="'+bid+'"]#thread_'+tid))) {
	  const st = storage();
	  st[bid].threads[tid] = time_loaded;
	  storage_save(st);
	  refresh = true;
	}
      }
    }
    return refresh;
  };

  $(window).scroll(() => { 
    const refresh = check_scroll();
    if (refresh) {
      //fetch_jsons();
      refresh = false;
    }
  });

  if (typeof add_title_collector != "undefined")
  add_title_collector(() => {
    if (!status) return 0;
    const sum = 0;
    for (var bid in status) {
      if (status[bid].new_threads) {
	sum += status[bid].new_threads;
        if (!status[bid].threads) continue;
        for (var tid in status[bid].threads) {
	  if (status[bid].threads[tid] > 0) {
            if (auto_reload_enabled && active_page === "thread") {
              const board = $('form[name="post"] input[name="board"]').value;
              const thread = $('form[name="post"] input[name="thread"]').value;
              
              if (board === bid && thread === tid) continue;
            }
	    sum += status[bid].threads[tid];
	  }
	}
      }
    }
    return sum;
  });

  update_pinned();
  fetch_jsons();
});
