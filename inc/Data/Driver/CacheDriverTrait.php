<?php
namespace Vichan\Data\Driver;


trait CacheDriverTrait {
	/**
	 * Tries to interpret the uri as a path to a unix socket.
	 *
	 * @param string $uri
	 * @return ?string The path to the socket, null if it cannot be interpreted as such.
	 */
	private static function asUnixSocketPath(string $uri): ?string {
		if (str_starts_with($uri, 'unix:')) {
			return \substr($uri, 5);
		} elseif (str_starts_with($uri, ':')) {
			return \substr($uri, 1);
		}
		return null;
	}
}
