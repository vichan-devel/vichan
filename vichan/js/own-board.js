/*****************************************************************
 *       -------            WARNING!              ---------      *
 *****************************************************************
 * This  script   is  at  the   current  time  undocumented  and *
 * unsupported.  It is still a work in  progress and will likely *
 * change. You are on your own.                                  *
 *****************************************************************/

+function() {

var uniq = function(a) {
  var b = {};
  var c = [];
  a.forEach(function(i) {
    if (!b[i]) {
      c.push(i);
      b[i] = true;
    }
  });
  return c;
};


if (active_page == 'thread' || active_page == 'index') {
  var board = null;

  $(function() {
    board = $('input[name="board"]').first().val();
  });

  $(document).on('ajax_after_post', function(e, r) {
    var threads = JSON.parse(localStorage.obthreads || '[]');

    var thread = null;
    if (active_page == 'index') {
      thread = r.id|0;
    }
    else {
      thread = $('[id^="thread_"]').first().attr('id').replace("thread_", "")|0;
    }

    threads.push([board, thread]);
    threads = uniq(threads);
    localStorage.obthreads = JSON.stringify(threads);
  });  
}

var loaded = false;
$(function() {
  loaded = true;
});

var activate = function() {
  if (document.location.hash != '#own') return false;

  if (loaded) late_activate();
  else $(function() { late_activate(); });

  return true;
};

var late_activate = function() {
  $('[id^="thread_"]').remove();

  var threads = JSON.parse(localStorage.obthreads || '[]');

  threads.forEach(function(v) {
    var board = v[0];
    var thread = v[1];
    var url = "/"+board+"/res/"+thread+".html";

    $.get(url, function(html) {
      var s = $(html).find('[id^="thread_"]');

      s[0].bumptime = (new Date(s.find("time").last().attr("datetime"))).getTime();

      var added = false;
      $('[id^="thread_"]').each(function() {
        if (added) return;
        if (s[0].bumptime > this.bumptime) {
          added = true;
          s.insertBefore(this);
        }
      });
      if (!added) {
        s.appendTo('[name="postcontrols"]');
      }

      s.find('.post.reply').addClass('hidden').hide().slice(-3).removeClass('hidden').show();

      s.find('.post.reply.hidden').next().addClass('hidden').hide(); // Hide <br> elements

      var posts_omitted = s.find('.post.reply.hidden').length;
      var images_omitted = s.find('.post.reply.hidden img').length;

      if (posts_omitted > 0) {
        var omitted = $(fmt('<span class="omitted">'+_('{0} posts and {1} images omitted.')+' '+_('Click reply to view.')+'</span>',
          [posts_omitted, images_omitted]));

        omitted.appendTo(s.find('.post.op'));
      }

      var reply = $('<a href="'+url+'">['+_('Reply')+']</a>').appendTo(s.find('.intro').first());

      $(document).trigger('new_post', s[0]);
    });    
  });
};
     
$(window).on("hashchange", function() {
  return !activate();
});
activate();


}();
