/*
 * local-time.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/local-time.js
 *
 * Released under the MIT license
 * Copyright (c) 2012 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   // $config['additional_javascript'][] = 'js/jquery.min.js';
 *   // $config['additional_javascript'][] = 'js/strftime.min.js';
 *   $config['additional_javascript'][] = 'js/local-time.js';
 *
 */

onReady(() => {
	'use strict';

	const iso8601 = (s) => {
		const parts = s.split('T');
		if (parts.length === 2) {
			const timeParts = parts[1].split(':');
			if (timeParts.length === 3) {
				const seconds = timeParts[2];
				if (seconds.length > 2) {
					seconds = seconds.substr(0, 2) + '.' + seconds.substr(2);
				}
				// Ensure seconds part is valid
				if (parseFloat(seconds) > 59) {
					seconds = '59';
				}
				timeParts[2] = seconds;
			}
			parts[1] = timeParts.join(':');
		}
		s = parts.join('T');

		if (!s.endsWith('Z')) {
			s += 'Z';
		}
		return new Date(s);
	};

	const zeropad = function(num, count) {
		return [Math.pow(10, count - num.toString().length), num].join('').substr(1);
	};

	const dateformat = (typeof strftime === 'undefined') ? (t) => {
		return zeropad(t.getMonth() + 1, 2) + "/" + zeropad(t.getDate(), 2) + "/" + t.getFullYear().toString().substring(2) +
				" (" + ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"][t.getDay()] + ") " +
				// time
				zeropad(t.getHours(), 2) + ":" + zeropad(t.getMinutes(), 2) + ":" + zeropad(t.getSeconds(), 2);
	} : (t) => {
		// post_date is defined in templates/main.js
		return strftime(window.post_date, t, datelocale);
	};

	function timeDifference(current, previous) {
		const msPerMinute = 60 * 1000;
		const msPerHour = msPerMinute * 60;
		const msPerDay = msPerHour * 24;
		const msPerMonth = msPerDay * 30;
		const msPerYear = msPerDay * 365;

		const elapsed = current - previous;

		if (elapsed < msPerMinute) {
			return 'Just now';
		} else if (elapsed < msPerHour) {
			return Math.round(elapsed / msPerMinute) + (Math.round(elapsed / msPerMinute) <= 1 ? ' minute ago' : ' minutes ago');
		} else if (elapsed < msPerDay) {
			return Math.round(elapsed / msPerHour) + (Math.round(elapsed / msPerHour) <= 1 ? ' hour ago' : ' hours ago');
		} else if (elapsed < msPerMonth) {
			return Math.round(elapsed / msPerDay) + (Math.round(elapsed / msPerDay) <= 1 ? ' day ago' : ' days ago');
		} else if (elapsed < msPerYear) {
			return Math.round(elapsed / msPerMonth) + (Math.round(elapsed / msPerMonth) <= 1 ? ' month ago' : ' months ago');
		} else {
			return Math.round(elapsed / msPerYear) + (Math.round(elapsed / msPerYear) <= 1 ? ' year ago' : ' years ago');
		}
	}

	const do_localtime = (elem) => {
		const times = elem.getElementsByTagName('time');
		const currentTime = Date.now();

		for (var i = 0; i < times.length; i++) {
			const t = times[i].getAttribute('datetime');
			const postTime = iso8601(t);

			times[i].setAttribute('data-local', 'true');

			if (localStorage.show_relative_time === 'false') {
				times[i].innerHTML = dateformat(postTime);
				times[i].setAttribute('title', timeDifference(currentTime, postTime.getTime()));
			} else {
				times[i].innerHTML = timeDifference(currentTime, postTime.getTime());
				times[i].setAttribute('title', dateformat(postTime));
			}
		}
	};

	if (window.Options && Options.get_tab('general') && window.jQuery) {
		const interval_id;
		Options.extend_tab('general', '<label id="show-relative-time"><input type="checkbox">' + _('Show relative time') + '</label>');

		document.querySelector('#show-relative-time>input').on('change', () => {
			if (localStorage.show_relative_time !== 'false') {
				localStorage.show_relative_time = 'false';
				clearInterval(interval_id);
			} else {
				localStorage.show_relative_time = 'true';
				interval_id = setInterval(do_localtime, 30000, document);
			}
			// no need to refresh page
			do_localtime(document);
		});

		if (localStorage.show_relative_time !== 'false') {
			document.querySelector('#show-relative-time>input').setAttribute('checked', 'checked');
			interval_id = setInterval(do_localtime, 30000, document);
		}

		// allow to work with auto-reload.js, etc.
		$(document).addEventListener('new_post', function(e, post) {
			do_localtime(post);
		});
	}

	do_localtime(document);
});

