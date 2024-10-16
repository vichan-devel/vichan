<?php
namespace Vichan\Data\Driver;

defined('TINYBOARD') or exit;


class MemcachedCacheDriver implements CacheDriver {
	private \Memcached $inner;

	public function __construct(string $prefix, string $memcached_server) {
		$this->inner = new \Memcached();
		if (!$this->inner->setOption(\Memcached::OPT_BINARY_PROTOCOL, true)) {
			throw new \RuntimeException('Unable to set the memcached protocol!');
		}
		if (!$this->inner->setOption(\Memcached::OPT_PREFIX_KEY, $prefix)) {
			throw new \RuntimeException('Unable to set the memcached prefix!');
		}
		if (!$this->inner->addServers($memcached_server)) {
			throw new \RuntimeException('Unable to add the memcached server!');
		}
	}

	public function get(string $key): mixed {
		$ret = $this->inner->get($key);
		// If the returned value is false but the retrival was a success, then the value stored was a boolean false.
		if ($ret === false && $this->inner->getResultCode() !== \Memcached::RES_SUCCESS) {
			return null;
		}
		return $ret;
	}

	public function set(string $key, mixed $value, mixed $expires = false): void {
		$this->inner->set($key, $value, (int)$expires);
	}

	public function delete(string $key): void {
		$this->inner->delete($key);
	}

	public function flush(): void {
		$this->inner->flush();
	}
}
