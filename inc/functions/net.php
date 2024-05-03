<?php
namespace Vichan\Functions\Net;


/**
 * @return bool Returns if the client-server connection is an encrypted one (HTTPS).
 */
function is_connection_secure(): bool {
	return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
}
