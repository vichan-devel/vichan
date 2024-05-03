<?php
namespace Vichan\Functions\Format;


function format_timestamp(int $delta): string {
	switch (true) {
		case $delta < 60:
			return $delta . ' ' . ngettext('second', 'seconds', $delta);
		case $delta < 3600: //60*60 = 3600
			return ($num = round($delta/ 60)) . ' ' . ngettext('minute', 'minutes', $num);
		case $delta < 86400: //60*60*24 = 86400
			return ($num = round($delta / 3600)) . ' ' . ngettext('hour', 'hours', $num);
		case $delta < 604800: //60*60*24*7 = 604800
			return ($num = round($delta / 86400)) . ' ' . ngettext('day', 'days', $num);
		case $delta < 31536000: //60*60*24*365 = 31536000
			return ($num = round($delta / 604800)) . ' ' . ngettext('week', 'weeks', $num);
		default:
			return ($num = round($delta / 31536000)) . ' ' . ngettext('year', 'years', $num);
	}
}

function until(int $timestamp): string {
	return format_timestamp($timestamp - time());
}

function ago(int $timestamp): string {
	return format_timestamp(time() - $timestamp);
}
