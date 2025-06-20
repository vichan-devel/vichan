/*
 * style-select.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/style-select.js
 *
 * Changes the stylesheet chooser links to a <select>
 *
 * Released under the MIT license
 * Copyright (c) 2013 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
 * Copyright (c) 2025 Zankaria Auxa <zankaria.auxa@mailu.io>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/style-select.js';
 * //  $config['additional_javascript'][] = 'js/style-select-simple.js'; // Conflicts with this file.
 */

onReady(function() {
	let stylesSelect = document.createElement('select');
	stylesSelect.style.setProperty('float', 'none');

	let i = 1;
	for (styleName in styles) {
		if (styleName) {
			let opt = document.createElement('option');
			opt.innerText = styleName;
			opt.value = i;
			if (selectedstyle == styleName) {
				opt.setAttribute('selected', true);
			}
			opt.setAttribute('id', 'style-select-' + i);
			stylesSelect.appendChild(opt);
			i++;
		}
	}

	stylesSelect.addEventListener('change', function() {
		let sel = document.getElementById(`style-select-${this.value}`);
		sel.click();
	});

	stylesSelect.addEventListener('change', function() {
		let sel = document.getElementById(`style-select-${this.value}`);
		let styleName = sel.innerHTML;
		changeStyle(styleName, sel);
	});

	let newElement = document.createElement('div');
	newElement.className = 'styles';
	newElement.innerHTML = _('Select theme: ');
	newElement.appendChild(stylesSelect);

	document.getElementsByTagName('body')[0].insertBefore(newElement, document.getElementsByTagName('body')[0].lastChild.nextSibling);
});
