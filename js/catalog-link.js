// This script puts a link to the catalog below the board subtitle, and next to the board list.

function catalog() {
var board = $("input[name='board']");

if (board.length>0) { 
var catalog_url = 'catalog.html';
var pages = document.getElementsByClassName('pages')[0];
var bottom = document.getElementsByClassName('boardlist bottom')[0]
var subtitle = document.getElementsByClassName('subtitle')[0];

var link = document.createElement('a');
link.href = catalog_url;

if (pages) {
	link.textContent = 'Catalog';
	link.style.color = '#F10000';
	link.style.padding = '4px';
	link.style.paddingLeft = '9px';
	link.style.borderLeft = '1px solid'
	link.style.borderLeftColor = '#A8A8A8';
	link.style.textDecoration = "underline";

	pages.appendChild(link)
}
else {
	link.textContent = '[Catalog]';
	link.style.paddingLeft = '10px';
	link.style.textDecoration = "underline";
	document.body.insertBefore(link, bottom);
}

if (subtitle) { 
	var link2 = document.createElement('a');
	link2.textContent = 'Catalog';
	link2.href = catalog_url;

	var br = document.createElement('br');
	subtitle.appendChild(br);
	subtitle.appendChild(link2);	
}
}
}
$(document).ready(catalog); 
