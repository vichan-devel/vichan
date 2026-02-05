onReady(() => {
	let autoScroll = localStorage['autoScroll'] ? true : false;
	if (window.Options && Options.get_tab('general')){
		Options.extend_tab('general','<label id=\'autoScroll\'><input type=\'checkbox\' />' + ' Scroll to new posts' + '</label>');
		document.getElementById('autoScroll').querySelector('input').checked = autoScroll;
	}
	const autoScrollCheckbox = document.getElementById('autoScroll');
	if (autoScrollCheckbox) {
		autoScrollCheckbox.addEventListener('change', () => {
			if(autoScroll) {
				delete localStorage.autoScroll;
			} else {
				localStorage.autoScroll = true;
			}
			autoScroll = !autoScroll;
			if(active_page === 'thread') {
				document.querySelector('input.auto-scroll').checked = autoScroll;
			}
		});
	}
	if (active_page === 'thread') {
		const updaterSpan = document.querySelector('span[id="updater"]');
		if (updaterSpan) {
			const linkEl = updaterSpan.querySelector('a');
			const newInput = document.createElement('input');
			newInput.className = 'auto-scroll';
			newInput.type = 'checkbox';
			newInput.textContent = ' Scroll to New posts';
			linkEl.parentNode.insertBefore(document.createTextNode(' ('), linkEl.nextSibling);
			linkEl.parentNode.insertBefore(newInput, linkEl.nextSibling.nextSibling);
			linkEl.parentNode.insertBefore(document.createTextNode(') Scroll to New posts'), newInput.nextSibling);
		}
		document.querySelector('input.auto-scroll').checked = autoScroll;
		document.addEventListener('new_post', (e) => {
			const post = e.detail;
			if (document.querySelector('input.auto-scroll').checked) 
			{
				const rect = post.getBoundingClientRect();
				scrollTo(0, rect.top - window.innerHeight + post.offsetHeight); 
			}
		});
	}
});
