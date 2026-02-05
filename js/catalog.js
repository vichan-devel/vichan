if (active_page === 'catalog') onReady(() => {
	let catalog;
	if (localStorage.catalog !== undefined) {
		catalog = JSON.parse(localStorage.catalog);
	} else {
		catalog = {};
		localStorage.catalog = JSON.stringify(catalog);
	}

	const sortByEl = document.getElementById('sort_by');
	if (sortByEl) {
		sortByEl.addEventListener('change', function() {
			const value = this.value;
			if (window.mixItUp) {
				document.getElementById('Grid').mixItUp('sort', (value === "random" ? value : "sticky:desc " + value));
			}
			catalog.sort_by = value;
			localStorage.catalog = JSON.stringify(catalog);
		});
	}

	const imageSizeEl = document.getElementById('image_size');
	if (imageSizeEl) {
		imageSizeEl.addEventListener('change', function() {
			const value = this.value;
			document.querySelectorAll(".grid-li").forEach(el => {
				el.classList.remove("grid-size-vsmall", "grid-size-small", "grid-size-large");
				el.classList.add("grid-size-" + value);
			});
			catalog.image_size = value;
			localStorage.catalog = JSON.stringify(catalog);
		});
	}

	if (window.mixItUp && document.getElementById('Grid')) {
		document.getElementById('Grid').mixItUp({
			animation: {
				enable: false
			}
		});
	}

	if (catalog.sort_by !== undefined && sortByEl) {
		sortByEl.value = catalog.sort_by;
		sortByEl.dispatchEvent(new Event('change'));
	}
	if (catalog.image_size !== undefined && imageSizeEl) {
		imageSizeEl.value = catalog.image_size;
		imageSizeEl.dispatchEvent(new Event('change'));
	}

	document.querySelectorAll('div.thread').forEach(el => {
		el.addEventListener('click', (e) => {
			const overflowY = window.getComputedStyle(el).overflowY;
			if (overflowY === 'hidden') {
				el.style.overflowY = 'auto';
				el.style.width = '100%';
			} else {
				el.style.overflowY = 'hidden';
				el.style.width = 'auto';
			}
		});
	});
});
