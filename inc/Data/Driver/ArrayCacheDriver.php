<?php
namespace Vichan\Data\Driver;

defined('TINYBOARD') or exit;


/**
 * A simple process-wide PHP array.
 */
class ArrayCacheDriver implements CacheDriver {
	private static array $inner = [];

	public function get(string $key): mixed {
		return isset(self::$inner[$key]) ? self::$inner[$key] : null;
	}

	public function set(string $key, mixed $value, mixed $expires = false): void {
		self::$inner[$key] = $value;
	}

	public function delete(string $key): void {
		unset(self::$inner[$key]);
	}

	public function flush(): void {
		self::$inner = [];
	}
}
