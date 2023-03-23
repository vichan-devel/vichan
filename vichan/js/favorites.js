/*
 * favorites.js - Allow user to favorite boards and put them in the bar
 *
 * Copyright (c) 2014 Fredrick Brennan <admin@8chan.co>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/favorites.js';
 *
 * XX: favorites.js may conflict with watch.js and compact-boardlist.js
 */

if (!localStorage.favorites) {
	localStorage.favorites = '[]';
}

function favorite(board) {
	var favorites = JSON.parse(localStorage.favorites);
	favorites.push(board);
	localStorage.favorites = JSON.stringify(favorites);
};

function unfavorite(board) {
	var favorites = JSON.parse(localStorage.favorites);
	var index = $.inArray(board, favorites);
	if (~index) {
		favorites.splice(index, 1);
	}
	localStorage.favorites = JSON.stringify(favorites);
};

function handle_boards(data) {
	var boards = new Array();
	data = JSON.parse(data);

	$.each(data, function(k, v) {
		boards.push('<a href="/'+v+'">'+v+'</a>');
	})

	if (boards[0]) {
		return $('<span class="favorite-boards"></span>').append(' [ '+boards.slice(0,10).join(" / ")+' ] ');
	}	
}

function add_favorites() {
	$('.favorite-boards').remove();
	
	var boards = handle_boards(localStorage.favorites);

	$('.boardlist').append(boards);
};

if (active_page == 'thread' || active_page == 'index' || active_page == 'catalog' || active_page == 'ukko') {
	$(document).ready(function(){
		var favorites = JSON.parse(localStorage.favorites);
		var is_board_favorite = ~$.inArray(board_name, favorites);

		$('header>h1').append('<a id="favorite-star" href="#" data-active="'+(is_board_favorite ? 'true' : 'false')+'" style="color: '+(is_board_favorite ? 'yellow' : 'grey')+'; text-decoration:none">\u2605</span>');
		add_favorites();

		$('#favorite-star').on('click', function(e) {
			e.preventDefault();
			if (!$(this).data('active')) {
				favorite(board_name);
				add_favorites();
				$(this).css('color', 'yellow');
				$(this).data('active', true);
			} else {
				unfavorite(board_name);
				add_favorites();
				$(this).css('color', 'grey');
				$(this).data('active', false);
			}
		});
	});
}
