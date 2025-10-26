document.addEventListener("DOMContentLoaded", function() {
	let controls = document.getElementsByClassName('controls');

	for (let i = 0; i < controls.length; i++) {
		const c = controls.item(i);

		const btn = c.getElementsByClassName('submenu-btn')[0];
		const submenu = c.getElementsByClassName('controls-submenu')[0];

		btn.addEventListener('click', function(e) {
			e.preventDefault();
			const closed = submenu.classList.contains('controls-submenu-hidden');

			if (closed) {
				btn.classList.add('submenu-btn-open');
				submenu.classList.remove('controls-submenu-hidden');
			} else {
				btn.classList.remove('submenu-btn-open');
				submenu.classList.add('controls-submenu-hidden');
			}

			return false;
		});
	}
});
