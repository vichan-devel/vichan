/*
 * compact-boardlist.js - a compact boardlist implementation making it
 *                        act more like a menubar
 * https://github.com/vichan-devel/Tinyboard/blob/master/js/compact-boardlist.js
 *
 * Released under the MIT license
 * Copyright (c) 2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['boards'] = array(
 *     "icon_vichan" => array('*'), # would refer to /static/icons/vichan.png
 *     "Regular" => array('b', 'cp', 'r+oc', 'id', 'waifu'),
 *     "Topical" => array('sci', "Offsite board name" => '//int.vichan.net/s/'),
 *     "fa_search" => array("search" => "/search.php") # would refer to a search 
 *                                                     # font-awesome icon
 *   )
 *
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/mobile-style.js';
 *   $config['additional_javascript'][] = 'js/compact-boardlist.js';
 *   //$config['additional_javascript'][] = 'js/watch.js';
 *
 */

if (device_type == 'desktop') {
  compact_boardlist = true;

  do_boardlist = function() {
    var categories = [];
    var topbl = $('.boardlist:first');

    topbl.find('>.sub').each(function() {
      var cat = {name: $(this).data('description'), boards: []};
      $(this).find('a').each(function() {
        var board = {name: $(this).prop('title'), uri: $(this).html(), href: $(this).prop('href') }
        cat.boards.push(board);
      });
      categories.push(cat);
    });

    topbl.addClass("compact-boardlist")
       .html("");

    for (var i in categories) {
      var item = categories[i];

      if (item.name.match(/^icon_/)) {
        var icon = item.name.replace(/^icon_/, '')
        $("<a class='cb-item cb-icon' href='"+categories[i].boards[0].href+"'><img src='/static/icons/"+icon+".png'></a>")
  	  .appendTo(topbl)
      }
      else if (item.name.match(/^fa_/)) {
        var icon = item.name.replace(/^fa_/, '')
        $('<a class="cb-item cb-fa" href="'+categories[i].boards[0].href+'"><i class="fa-'+icon+' fa"></i></a>')
          .appendTo(topbl)
      }
      else if (item.name.match(/^d_/)) {
        var icon = item.name.replace(/^d_/, '')
        $('<a class="cb-item cb-cat" href="'+categories[i].boards[0].href+'">'+icon+'</a>')
          .appendTo(topbl)
      }
      else {
        $("<a class='cb-item cb-cat' href='javascript:void(0)'>"+item.name+"</a>")
 	  .appendTo(topbl)
	  .mouseenter(function() {
	    var list = $("<div class='boardlist top cb-menu'></div>")
	      .css("top", $(this).position().top + 13 + $(this).height())
	      .css("left", $(this).position().left)
	      .css("right", "auto")
	      .appendTo(this);
	    for (var j in this.item.boards) {
	      var board = this.item.boards[j];
            
	      var tag;
              if (board.name) {
	        tag = $("<a href='"+board.href+"'><span>"+board.name+"</span><span class='cb-uri'>/"+board.uri+"/</span></a>")
	      }
	      else {
	        tag = $("<a href='"+board.href+"'><span>"+board.uri+"</span><span class='cb-uri'><i class='fa fa-globe'></i></span></a>")
	      }
	      tag
		.addClass("cb-menuitem")
                .appendTo(list)
	    }
	  })
	  .mouseleave(function() {
	    topbl.find(".cb-menu").remove();
	  })[0].item = item;
      }
    }
    do_boardlist = undefined;
  };
}
