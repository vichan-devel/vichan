<?php
namespace Vichan\Functions\Net;


/**
 * @param bool $trust_headers. If true, trust the `HTTP_X_FORWARDED_PROTO` header to check if the connection is HTTPS.
 * @return bool Returns if the client-server connection is an encrypted one (HTTPS).
 */
function is_connection_secure(bool $trust_headers): bool {
	if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
		return true;
	} elseif ($trust_headers && isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
		return true;
	}
	return false;
}

/**
 * Encodes a string into a base64 variant without characters illegal in urls.
 */
function base64_url_encode(string $input): string {
	return str_replace([ '+', '/', '=' ], [ '-', '_', '' ], base64_encode($input));
}

/**
 * Decodes a string from a base64 variant without characters illegal in urls.
 */
function base64_url_decode(string $input): string {
	return base64_decode(strtr($input, '-_', '+/'));
}

/**
 * Encodes a typed cursor.
 *
 * @param string $type The type for the cursor. Only the first character is considered.
 * @param array $map A map of key-value pairs to encode.
 * @return string An encoded string that can be sent through urls. Empty if either parameter is empty.
 */
function encode_cursor(string $type, array $map): string {
	if (empty($type) || empty($map)) {
		return '';
	}

	$acc = $type[0];
	foreach ($map as $key => $value) {
		$acc .= "|$key#$value";
	}
	return base64_url_encode($acc);
}

/**
 * Decodes a typed cursor.
 *
 * @param string $cursor A string emitted by `encode_cursor`.
 * @return array An array with the type of the cursor and an array of key-value pairs. The type is null and the map
 *               empty if either there are no key-value pairs or the encoding is incorrect.
 */
function decode_cursor(string $cursor): array {
	$map = [];
	$type = '';
	$acc = base64_url_decode($cursor);
	if ($acc === false || empty($acc)) {
		return [ null, [] ];
	}

	$type = $acc[0];
	foreach (explode('|', substr($acc, 2)) as $pair) {
		$pair = explode('#', $pair);
		if (count($pair) >= 2) {
			$key = $pair[0];
			$value = $pair[1];
			$map[$key] = $value;
		}
	}
	return [ $type, $map ];
}
