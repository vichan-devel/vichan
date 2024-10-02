<?php
namespace Vichan\Data\Driver;

defined('TINYBOARD') or exit;


class ApcuCacheDriver implements CacheDriver {
	public function get(string $key): mixed {
		$success = false;
		$ret = \apcu_fetch($key, $success);
		if ($success === false) {
			return null;
		}
		return $ret;
	}

	public function set(string $key, mixed $value, mixed $expires = false): void {
		\apcu_store($key, $value, (int)$expires);
	}

	public function delete(string $key): void {
		\apcu_delete($key);
	}

	public function flush(): void {
		\apcu_clear_cache();
	}
}
