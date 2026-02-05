/*****************************************************************
 *       -------            WARNING!              ---------      *
 *****************************************************************
 * This  script   is  at  the   current  time  undocumented  and *
 * unsupported.  It is still a work in  progress and will likely *
 * change. You are on your own.                                  *
 *****************************************************************/

+() => {

const uniq = (a) => {
  const b = {};
  const c = [];
  a.forEach((i) => {
    if (!b[i]) {
      c.push(i);
      b[i] = true;
    }
  });
  return c;
};


if (active_page === 'thread' || active_page === 'index') {
  const board = null;

  $(() => {
    board = $('input[name="board"]').first().value;
  });

  $(document).addEventListener('ajax_after_post', function(e, r) {
    const threads = JSON.parse(localStorage.obthreads || '[]');

    const thread = null;
    if (active_page === 'index') {
      thread = r.id|0;
    }
    else {
      thread = $('[id^="thread_"]').first().getAttribute('id').replace("thread_", "")|0;
    }

    threads.push([board, thread]);
    threads = uniq(threads);
    localStorage.obthreads = JSON.stringify(threads);
  });  
}

const loaded = false;
$(() => {
  loaded = true;
});

const activate = () => {
  if (document.location.hash != '#own') return false;

  if (loaded) late_activate();
  else $(() => { late_activate(); });

  return true;
};

const late_activate = () => {
  $('[id^="thread_"]').remove();

  const threads = JSON.parse(localStorage.obthreads || '[]');

  threads.forEach((v) => {
    const board = v[0];
    const thread = v[1];
    const url = "/"+board+"/res/"+thread+".html";

    fetch(url, (html) => {
      const s = $(html).find('[id^="thread_"]');

      s[0].bumptime = (new Date(s.querySelector('time').last().getAttribute('datetime'))).getTime();

      const added = false;
      $('[id^="thread_"]').each(() => {
        if (added) return;
        if (s[0].bumptime > this.bumptime) {
          added = true;
          s.insertBefore(this);
        }
      });
      if (!added) {
        s.appendTo('[name="postcontrols"]');
      }

      s.querySelector('.post.reply').classList.add('hidden').style.display = 'none'.slice(-3).classList.remove('hidden').style.display = '';

      s.querySelector('.post.reply.hidden').next().classList.add('hidden').style.display = 'none'; // Hide <br> elements

      const posts_omitted = s.querySelector('.post.reply.hidden').length;
      const images_omitted = s.querySelector('.post.reply.hidden img').length;

      if (posts_omitted > 0) {
        const omitted = $(fmt('<span class="omitted">'+_('{0} posts and {1} images omitted.')+' '+_('Click reply to view.')+'</span>',
          [posts_omitted, images_omitted]));

        omitted.appendTo(s.querySelector('.post.op'));
      }

      const reply = $('<a href="'+url+'">['+_('Reply')+']</a>').appendTo(s.querySelector('.intro').first());

      $(document).trigger('new_post', s[0]);
    });    
  });
};
     
$(window).on("hashchange", () => {
  return !activate();
});
activate();


}();
