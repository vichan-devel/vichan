/*
 * style-select-simple.js
 *
 * Changes the stylesheet chooser links to a <select>
 *
 * Released under the MIT license
 * Copyright (c) 2025 Zankaria Auxa <zankaria.auxa@mailu.io>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/style-select-simple.js';
 * //  $config['additional_javascript'][] = 'js/style-select.js'; // Conflicts with this file.
 */

onReady(function() {
	let newElement = document.createElement('div');
	newElement.className = 'styles';

	// styles is defined in main.js.
	for (styleName in styles) {
		if (styleName) {
			let style = document.createElement('a');
			style.innerHTML = `[${styleName}]`;
			style.onclick = function() {
				changeStyle(this.innerHTML.substring(1, this.innerHTML.length - 1), this);
			};
			if (styleName == selectedstyle) {
				style.className = 'selected';
			}
			style.href = 'javascript:void(0);';
			newElement.appendChild(style);
		}
	}

	document.getElementsByTagName('body')[0].insertBefore(newElement, document.getElementsByTagName('body')[0].lastChild.nextSibling);
});
