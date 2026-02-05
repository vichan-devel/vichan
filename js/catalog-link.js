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
    const boardInputs = document.querySelectorAll("input[name='board']");
    const boardValue = boardInputs.length > 0 ? boardInputs[0].value : null;

    let catalog_url = '';
    if (window.location.href.includes('mod.php?/')) {
        catalog_url = configRoot + 'mod.php?/' + boardValue + '/catalog.html';
    } else {
        catalog_url = configRoot + boardValue + '/catalog.html';
    }

    const pages = document.getElementsByClassName('pages')[0];
    const bottom = document.getElementsByClassName('boardlist bottom')[0];
    const subtitle = document.getElementsByClassName('subtitle')[0];

    const link = document.createElement('a');
    link.href = catalog_url;

    if (!pages) {
        link.textContent = '['+_('Catalog')+']';
        link.style.paddingLeft = '10px';
        link.style.textDecoration = "underline";
        document.body.insertBefore(link, bottom);
    }

    if (subtitle) { 
        const link2 = document.createElement('a');
        link2.textContent = _('Catalog');
        link2.href = catalog_url;

        const br = document.createElement('br');
        subtitle.appendChild(br);
        subtitle.appendChild(link2);    
    }
}

if (active_page === 'thread' || active_page === 'index') {
    onReady(catalog);
}
