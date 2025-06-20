<?php

// PHP 8.0

if (!function_exists('str_starts_with')) {
	function str_starts_with(string $haystack, string $needle): bool {
		// https://wiki.php.net/rfc/add_str_starts_with_and_ends_with_functions#str_starts_with
		return \strncmp($haystack, $needle, \strlen($needle)) === 0;
	}
}
