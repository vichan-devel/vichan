$(document).ready(function() {
  var App = {
    cache: {},
    get: function(url, cb) {
      var $page = App.cache[url]
      if ($page)
        return cb($page)

      $.get(url, function(data) {
        var $page = $(data)
        App.cache[url] = $page
        cb($page)
      })
    },
    options: {
      add: function(key, description, tab) {
        tab || (tab = 'general')

        var checked = App.options.get(key)
        var $el = $(
          '<div>' +
            '<label>' +
              '<input type="checkbox">' +
              description +
            '</label>' +
          '</div>')

        $el
          .find('input')
          .prop('checked', checked)
          .on('change', App.options.check(key))

        window.Options.extend_tab(tab, $el)
      },
      get: function(key) {
        if (localStorage[key])
          return JSON.parse(localStorage[key])
      },
      check: function(key) {
        return function(e) {
          var val = this.checked
          localStorage[key] = JSON.stringify(val)
        }
      }
    }
  }

  var inline = function(e) {
    e.preventDefault()

    var $root = $(this).closest('.post')
    var targetNum = this.textContent.slice(2)

    var srcOP = $root.closest('[id^=thread]').attr('id').match(/\d+/)[0]

    var node, targetOP
    var isBacklink = !!this.className
    if (isBacklink) {
      node = $root.find('> .intro')
      targetOP = srcOP
    } else {
      node = $(this)

      var to_search = inMod ? this.search : this.pathname;
      targetOP = to_search.match(/(\d+).html/)[1]
    }

    var link = {
      id: 'inline_' + targetNum,
      isBacklink: isBacklink,
      node: node
    }

    var selector = targetNum === targetOP
      ? '#op_' + srcOP
      : '#reply_' + targetNum

    var $clone = $root.find('#inline_' + targetNum)
    if ($clone.length) {
      $clone.remove()
      $(selector)
        .show()
        .next()
        .show()
      return
    }

    if (srcOP === targetOP) {
      if (targetNum === targetOP)
        link.node = link.node.next()// bypass `(OP)`

      var $target = $(selector)
      if ($target.length)
        return add(link, $target)
    }

    var $loading = $('<div class="inline post">loading...</div>')
      .attr('id', link.id)
      .insertAfter(link.node)

    App.get(this.pathname, function($page) {
      $loading.remove()
      var $target = $page.find(selector)
      add(link, $target)
    })
  }

  var add = function(link, $target) {
    var $clone = $target.clone(true)

    if (link.isBacklink && App.options.get('hidePost'))
      $target
        .hide()
        .next()
        .hide()

    $clone.find('.inline').remove()
    $clone.attr({
      "class": 'inline post',
      id: link.id,
      style: null// XXX remove post hover styling
    })
    $clone.insertAfter(link.node)
  }

  App.options.add('useInlining', _('Enable inlining'))
  App.options.add('hidePost', _('Hide inlined backlinked posts'))

  $('head').append(
    '<style>' +
      '.inline {' +
        'border: 1px dashed black;' +
        'white-space: normal;' +
        'overflow: auto;' + // clearfix
      '}' +
    '</style>')

  // don't attach to outbound links

  if (App.options.get('useInlining')) {
    var assign_inline = function() {
        $('.body a[href*="'+location.pathname+'"]').not('[rel]').not('.toolong > a').add('.mentioned a')
          .attr('onclick', null)// XXX disable highlightReply
          .off('click')
          .click(inline)
    }

    assign_inline();

    $(document).on('new_post', function(e, post) {
      assign_inline();
    });
  }
});
