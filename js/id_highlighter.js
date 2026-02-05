if (active_page === 'thread' || active_page === 'index') {
	onReady(() => {
		const idshighlighted = [];

		const getPostsById = (id) => {
			return Array.from(document.querySelectorAll(".poster_id"))
				.filter(el => el.textContent === id);
		};

		const getMasterPosts = (parents) => {
			return parents
				.map(parent => {
					let curr = parent;
					while (curr && !curr.classList.contains('post')) {
						curr = curr.parentElement;
					}
					return curr;
				})
				.filter((el, idx, arr) => el && arr.indexOf(el) === idx);
		};

		const id_highlighter = function() {
			const id = this.textContent;
			const idx = idshighlighted.indexOf(id);

			if (idx !== -1) {
				idshighlighted.splice(idx, 1);

				const posts = getMasterPosts(getPostsById(id).map(el => el.closest('.post')));
				posts.forEach(post => post?.classList.remove('highlighted'));
			} else {
				idshighlighted.push(id);

				const posts = getMasterPosts(getPostsById(id).map(el => el.closest('.post')));
				posts.forEach(post => post?.classList.add('highlighted'));
			}
		};

		document.querySelectorAll(".poster_id").forEach(el => {
			el.addEventListener('click', id_highlighter);
		});

		document.addEventListener('new_post', (e) => {
			const post = e.detail;
			const posterIds = post.querySelectorAll('.poster_id');
			posterIds.forEach(el => {
				el.addEventListener('click', id_highlighter);
			});
		});
	});
}
