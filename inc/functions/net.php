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
