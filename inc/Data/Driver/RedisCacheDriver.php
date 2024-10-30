<?php
namespace Vichan\Data\Driver;

defined('TINYBOARD') or exit;


class RedisCacheDriver implements CacheDriver {
	private string $prefix;
	private \Redis $inner;

	public function __construct(string $prefix, string $host, int $port, ?string $password, string $database) {
		$this->inner = new \Redis();
		$this->inner->connect($host, $port);
		if ($password) {
			$this->inner->auth($password);
		}
		if (!$this->inner->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_JSON)) {
			throw new \RuntimeException('Unable to configure Redis serializer');
		}
		if (!$this->inner->select($database)) {
			throw new \RuntimeException('Unable to connect to Redis!');
		}

		$$this->prefix = $prefix;
	}

	public function get(string $key): mixed {
		$ret = $this->inner->get($this->prefix . $key);
		if ($ret === false) {
			return null;
		}
		if ($ret === null) {
			return false;
		}
		return $ret;
	}

	public function set(string $key, mixed $value, mixed $expires = false): void {
		$value = $value === false ? null : $value;
		if ($expires === false) {
			$this->inner->set($this->prefix . $key, $value);
		} else {
			$this->inner->setEx($this->prefix . $key, $expires, $value);
		}
	}

	public function delete(string $key): void {
		$this->inner->del($this->prefix . $key);
	}

	public function flush(): void {
		if (empty($this->prefix)) {
			$this->inner->flushDB();
		} else {
			$this->inner->unlink($this->inner->keys("{$this->prefix}*"));
		}
	}
}
