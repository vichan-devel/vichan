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

$(document).ready(function(){
	'use strict';

	var iso8601 = function(s) {
		s = s.replace(/\.\d\d\d+/,""); // remove milliseconds
		s = s.replace(/-/,"/").replace(/-/,"/");
		s = s.replace(/T/," ").replace(/Z/," UTC");
		s = s.replace(/([\+\-]\d\d)\:?(\d\d)/," $1$2"); // -04:00 -> -0400
		return new Date(s);
	};
	var zeropad = function(num, count) {
		return [Math.pow(10, count - num.toString().length), num].join('').substr(1);
	};

	var dateformat = (typeof strftime === 'undefined') ? function(t) {
		return zeropad(t.getMonth() + 1, 2) + "/" + zeropad(t.getDate(), 2) + "/" + t.getFullYear().toString().substring(2) +
				" (" + [_("Sun"), _("Mon"), _("Tue"), _("Wed"), _("Thu"), _("Fri"), _("Sat"), _("Sun")][t.getDay()]  + ") " +
				// time
				zeropad(t.getHours(), 2) + ":" + zeropad(t.getMinutes(), 2) + ":" + zeropad(t.getSeconds(), 2);

	} : function(t) {
		// post_date is defined in templates/main.js
		return strftime(window.post_date, t, datelocale);
	};

	function timeDifference(current, previous) {

		var msPerMinute = 60 * 1000;
		var msPerHour = msPerMinute * 60;
		var msPerDay = msPerHour * 24;
		var msPerMonth = msPerDay * 30;
		var msPerYear = msPerDay * 365;

		var elapsed = current - previous;

		if (elapsed < msPerMinute) {
			return 'Just now';
		} else if (elapsed < msPerHour) {
			return Math.round(elapsed/msPerMinute) + (Math.round(elapsed/msPerMinute)<=1 ? ' minute ago':' minutes ago');
		} else if (elapsed < msPerDay ) {
			return Math.round(elapsed/msPerHour ) + (Math.round(elapsed/msPerHour)<=1 ? ' hour ago':' hours ago');
		} else if (elapsed < msPerMonth) {
			return Math.round(elapsed/msPerDay) + (Math.round(elapsed/msPerDay)<=1 ? ' day ago':' days ago');
		} else if (elapsed < msPerYear) {
			return Math.round(elapsed/msPerMonth) + (Math.round(elapsed/msPerMonth)<=1 ? ' month ago':' months ago');
		} else {
			return Math.round(elapsed/msPerYear ) + (Math.round(elapsed/msPerYear)<=1 ? ' year ago':' years ago');
		}
	}

	var do_localtime = function(elem) {	
		var times = elem.getElementsByTagName('time');
		var currentTime = Date.now();

		for(var i = 0; i < times.length; i++) {
			var t = times[i].getAttribute('datetime');
			var postTime = new Date(t);

			times[i].setAttribute('data-local', 'true');

			if (localStorage.show_relative_time === 'false') {
				times[i].innerHTML = dateformat(iso8601(t));
				times[i].setAttribute('title', timeDifference(currentTime, postTime.getTime()));
			} else {
				times[i].innerHTML = timeDifference(currentTime, postTime.getTime());
				times[i].setAttribute('title', dateformat(iso8601(t)));
			}
		
		}
	};

	if (window.Options && Options.get_tab('general') && window.jQuery) {
		var interval_id;
		Options.extend_tab('general', '<label id="show-relative-time"><input type="checkbox">' + _('Show relative time') + '</label>');

		$('#show-relative-time>input').on('change', function() {
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
			$('#show-relative-time>input').attr('checked','checked');
			interval_id = setInterval(do_localtime, 30000, document);
		}

		// allow to work with auto-reload.js, etc.
		$(document).on('new_post', function(e, post) {
			do_localtime(post);
		});
	}

	do_localtime(document);
});
