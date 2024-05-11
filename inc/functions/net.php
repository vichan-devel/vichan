<?php
namespace Vichan\Functions\Net;


/**
 * @return bool Returns if the client-server connection is an encrypted one (HTTPS).
 */
function is_connection_secure(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return true;
    }
    else {
	return false;
    }
}
