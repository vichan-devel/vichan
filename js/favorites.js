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
	const favorites = JSON.parse(localStorage.favorites);
	favorites.push(board);
	localStorage.favorites = JSON.stringify(favorites);
}

function unfavorite(board) {
	const favorites = JSON.parse(localStorage.favorites);
	const index = favorites.indexOf(board);
	if (index !== -1) {
		favorites.splice(index, 1);
	}
	localStorage.favorites = JSON.stringify(favorites);
}

function handle_boards(data) {
	const boards = [];
	const parsed_data = JSON.parse(data);

	Object.values(parsed_data).forEach((v) => {
		boards.push('<a href="/'+v+'">'+v+'</a>');
	});

	if (boards[0]) {
		const span = document.createElement('span');
		span.className = 'favorite-boards';
		span.innerHTML = ' [ '+boards.slice(0,10).join(" / ")+' ] ';
		return span;
	}
	return null;
}

function add_favorites() {
	document.querySelectorAll('.favorite-boards').forEach(el => el.remove());
	
	const boards = handle_boards(localStorage.favorites);
	
	if (boards) {
		document.querySelectorAll('.boardlist').forEach(el => {
			el.appendChild(boards.cloneNode(true));
		});
	}
}

if (active_page === 'thread' || active_page === 'index' || active_page === 'catalog' || active_page === 'ukko') {
	onReady(() => {
		const favorites = JSON.parse(localStorage.favorites);
		const is_board_favorite = favorites.includes(board_name);

		const header = document.querySelector('header>h1');
		if (header) {
			const star = document.createElement('a');
			star.id = 'favorite-star';
			star.href = '#';
			star.dataset.active = is_board_favorite ? 'true' : 'false';
			star.style.color = is_board_favorite ? 'yellow' : 'grey';
			star.style.textDecoration = 'none';
			star.textContent = '★';
			header.appendChild(star);
		}
		
		add_favorites();

		const favoriteBtn = document.getElementById('favorite-star');
		if (favoriteBtn) {
			favoriteBtn.addEventListener('click', (e) => {
				e.preventDefault();
				if (favoriteBtn.dataset.active !== 'true') {
					favorite(board_name);
					add_favorites();
					favoriteBtn.style.color = 'yellow';
					favoriteBtn.dataset.active = 'true';
				} else {
					unfavorite(board_name);
					add_favorites();
					favoriteBtn.style.color = 'grey';
					favoriteBtn.dataset.active = 'false';
				}
			});
		}
	});
}
