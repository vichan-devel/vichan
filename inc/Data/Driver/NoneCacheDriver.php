<?php
namespace Vichan\Data\Driver;

defined('TINYBOARD') or exit;


/**
 * No-op cache. Useful for testing.
 */
class NoneCacheDriver implements CacheDriver {
	public function get(string $key): mixed {
		return null;
	}

	public function set(string $key, mixed $value, mixed $expires = false): void {
		// No-op.
	}

	public function delete(string $key): void {
		// No-op.
	}

	public function flush(): void {
		// No-op.
	}
}
