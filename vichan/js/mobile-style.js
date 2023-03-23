/*
 * mobile-style.js - adds some responsiveness to Tinyboard
 * https://github.com/vichan-devel/Tinyboard/blob/master/js/mobile-style.js
 *
 * Released under the MIT license
 * Copyright (c) 2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['api']['enabled'] = true;
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/mobile-style.js';
 */

if(navigator.userAgent.match(/iPhone|iPod|iPad|Android|Opera Mini|Blackberry|PlayBook|Windows Phone|Tablet PC|Windows CE|IEMobile/i)) {
	$('html').addClass("mobile-style");
	device_type = "mobile";
}
else {
	$('html').addClass("desktop-style");
	device_type = "desktop";
}
