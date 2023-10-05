/*
 * catalog-link.js - This script puts a link to the catalog below the board
 *                   subtitle and next to the board list.
 * https://github.com/vichan-devel/Tinyboard/blob/master/js/catalog-link.js
 *
 * Released under the MIT license
 * Copyright (c) 2013 copypaste <wizardchan@hush.com>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/catalog-link.js';
 */

function catalog() {
    var board = $("input[name='board']");
    var boardValue = board.first().val();

    var catalog_url = '';
    if (window.location.href.includes('mod.php?/')) {
        catalog_url = configRoot + 'mod.php?/' + boardValue + '/catalog.html';
    } else {
        catalog_url = configRoot + boardValue + '/catalog.html';
    }

    var pages = document.getElementsByClassName('pages')[0];
    var bottom = document.getElementsByClassName('boardlist bottom')[0];
    var subtitle = document.getElementsByClassName('subtitle')[0];

    var link = document.createElement('a');
    link.href = catalog_url;

    if (pages) {
        link.textContent = _('Catalog');
        link.style.color = '#F10000';
        link.style.padding = '4px';
        link.style.paddingLeft = '9px';
        link.style.borderLeft = '1px solid';
        link.style.borderLeftColor = '#A8A8A8';
        link.style.textDecoration = "underline";

        pages.appendChild(link);
    } else {
        link.textContent = '['+_('Catalog')+']';
        link.style.paddingLeft = '10px';
        link.style.textDecoration = "underline";
        document.body.insertBefore(link, bottom);
    }

    if (subtitle) { 
        var link2 = document.createElement('a');
        link2.textContent = _('Catalog');
        link2.href = catalog_url;

        var br = document.createElement('br');
        subtitle.appendChild(br);
        subtitle.appendChild(link2);    
    }
}

if (active_page == 'thread' || active_page == 'index') {
    $(document).ready(catalog);
}
