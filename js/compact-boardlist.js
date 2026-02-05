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

if (device_type === 'desktop') {
  compact_boardlist = true;

  do_boardlist = function() {
    const categories = [];
    const topbl = document.querySelector('.boardlist:first-of-type') || document.querySelector('.boardlist');

    if (!topbl) return;

    topbl.querySelectorAll('>.sub').forEach((subEl) => {
      const cat = {name: subEl.dataset.description, boards: []};
      subEl.querySelectorAll('a').forEach((linkEl) => {
        const board = {name: linkEl.getAttribute('title'), uri: linkEl.innerHTML, href: linkEl.getAttribute('href')};
        cat.boards.push(board);
      });
      categories.push(cat);
    });

    topbl.classList.add("compact-boardlist");
    topbl.innerHTML = "";

    for (const i in categories) {
      const item = categories[i];

      if (item.name && item.name.match(/^icon_/)) {
        const icon = item.name.replace(/^icon_/, '');
        const a = document.createElement('a');
        a.className = 'cb-item cb-icon';
        a.href = categories[i].boards[0].href;
        a.innerHTML = '<img src="/static/icons/' + icon + '.png">';
        topbl.appendChild(a);
      }
      else if (item.name && item.name.match(/^fa_/)) {
        const icon = item.name.replace(/^fa_/, '');
        const a = document.createElement('a');
        a.className = 'cb-item cb-fa';
        a.href = categories[i].boards[0].href;
        a.innerHTML = '<i class="fa-' + icon + ' fa"></i>';
        topbl.appendChild(a);
      }
      else if (item.name && item.name.match(/^d_/)) {
        const icon = item.name.replace(/^d_/, '');
        const a = document.createElement('a');
        a.className = 'cb-item cb-cat';
        a.href = categories[i].boards[0].href;
        a.textContent = icon;
        topbl.appendChild(a);
      }
      else {
        const a = document.createElement('a');
        a.className = 'cb-item cb-cat';
        a.href = 'javascript:void(0)';
        a.textContent = item.name;
        
        a.addEventListener('mouseenter', function() {
          const list = document.createElement('div');
          list.className = 'boardlist top cb-menu';
          list.style.top = (this.offsetTop + 13 + this.offsetHeight) + 'px';
          list.style.left = this.offsetLeft + 'px';
          list.style.right = 'auto';
          this.appendChild(list);
          
          for (const j in this.dataset.item_boards) {
            const board = this.dataset.item_boards[j];
            
            let tag;
            if (board.name) {
              tag = document.createElement('a');
              tag.href = board.href;
              tag.innerHTML = '<span>' + board.name + '</span><span class="cb-uri">/' + board.uri + '/</span>';
            }
            else {
              tag = document.createElement('a');
              tag.href = board.href;
              tag.innerHTML = '<span>' + board.uri + '</span><span class="cb-uri"><i class="fa fa-globe"></i></span>';
            }
            tag.classList.add("cb-menuitem");
            list.appendChild(tag);
          }
        });
        
        a.addEventListener('mouseleave', function() {
          topbl.querySelectorAll(".cb-menu").forEach(el => el.remove());
        });
        
        // Store the boards data on the element
        a.dataset.item_boards = JSON.stringify(item.boards);
        
        topbl.appendChild(a);
      }
    }
    do_boardlist = undefined;
  };
}
