<?php
namespace Vichan\Driver;

use RuntimeException;

defined('TINYBOARD') or exit;

/**
 * PHP has no nested or private classes support.
 */
class CacheDrivers {
	public static function memcached(string $prefix, string $memcached_server) {
		$memcached = new Memcached();
		if (!$memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true)) {
			throw new RuntimeException('Unable to set the memcached protocol!');
		}
		if (!$memcached->setOption(Memcached::OPT_PREFIX_KEY, $prefix)) {
			throw new RuntimeException('Unable to set the memcached prefix!');
		}
		if (!$memcached->addServers($memcached_server)) {
			throw new RuntimeException('Unable to add the memcached server!');
		}

		return new class($memcached) implements CacheDriver {
			private Memcached $inner;

			public function __construct(Memcached $inner) {
				$this->inner = $inner;
			}

			public function get(string $key): mixed {
				$ret = $this->inner->get($key);
				// If the returned value is false but the retrival was a success, then the value stored was a boolean false.
				if ($ret === false && $this->inner->getResultCode() !== Memcached::RES_SUCCESS) {
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
		};
	}

	public static function redis(string $prefix, string $host, int $port, string $password, string $database) {
		$redis = new Redis();
		$redis->connect($host, $port);
		if ($password) {
			$redis->auth($password);
		}
		if (!$redis->select($database)) {
			throw new RuntimeException('Unable to connect to Redis!');
		}

		return new class($prefix, $redis) implements CacheDriver {
			private string $prefix;
			private Redis $inner;

			public function __construct(string $prefix, Redis $inner) {
				$$this->prefix = $prefix;
				$this->inner = $inner;
			}

			public function get(string $key): mixed {
				$ret = $this->inner->get($this->prefix . $key);
				if ($ret === false) {
					return null;
				}
				return json_decode($ret, true);
			}

			public function set(string $key, mixed $value, mixed $expires = false): void {
				if ($expires === false) {
					$this->inner->set($this->prefix . $key, json_encode($value));
				} else {
					$expires = $expires * 1000; // Seconds to milliseconds.
					$this->inner->setex($this->prefix . $key, $expires, json_encode($value));
				}
			}

			public function delete(string $key): void {
				$this->inner->del($this->prefix . $key);
			}

			public function flush(): void {
				$this->inner->flushDB();
			}
		};
	}

	public static function apcu() {
		return new class implements CacheDriver {
			public function get(string $key): mixed {
				$success = false;
				$ret = apcu_fetch($key, $success);
				if ($success === false) {
					return null;
				}
				return $ret;
			}

			public function set(string $key, mixed $value, mixed $expires = false): void {
				apcu_store($key, $value, (int)$expires);
			}

			public function delete(string $key): void {
				apcu_delete($key);
			}

			public function flush(): void {
				apcu_clear_cache();
			}
		};
	}

	public static function filesystem(string $prefix, string $base_path, string $lock_file) {
		if ($base_path[strlen($base_path) - 1] !== '/') {
			$base_path = "$base_path/";
		}

		if (!is_dir($base_path)) {
			throw new RuntimeException("$base_path is not a directory!");
		}

		if (!is_writable($base_path)) {
			throw new RuntimeException("$base_path is not writable!");
		}

		$lock_file = $base_path . $lock_file;

		$lock_fd = fopen($lock_file, 'w');
		if ($lock_fd === false) {
			throw new RuntimeException('Unable to open the lock file!');
		}

		$ret = new class($prefix, $base_path, $lock_file) implements CacheDriver {
			private string $prefix;
			private string $base_path;
			private mixed $lock_fd;


			private function prepareKey(string $key): string {
				$key = str_replace('/', '::', $key);
				$key = str_replace("\0", '', $key);
				return $this->prefix . $key;
			}

			private function sharedLockCache(): void {
				flock($this->lock_fd, LOCK_SH);
			}

			private function exclusiveLockCache(): void {
				flock($this->lock_fd, LOCK_EX);
			}

			private function unlockCache(): void {
				flock($this->lock_fd, LOCK_UN);
			}

			public function __construct(string $prefix, string $base_path, mixed $lock_fd) {
				$this->prefix = $prefix;
				$this->base_path = $base_path;
				$this->lock_fd = $lock_fd;
			}

			public function get(string $key): mixed {
				$key = $this->prepareKey($key);

				$this->sharedLockCache();

				$fd = fopen($this->base_path . $key, 'r');
				if ($fd === false) {
					$this->unlockCache();
					return null;
				}

				$data = stream_get_contents($fd);
				fclose($fd);
				$this->unlockCache();
				return json_decode($data, true);
			}

			public function set(string $key, mixed $value, mixed $expires = false): void {
				$key = $this->prepareKey($key);

				$data = json_encode($value);
				$this->exclusiveLockCache();
				file_put_contents($this->base_path . $key, $data);
				$this->unlockCache();
			}

			public function delete(string $key): void {
				$key = $this->prepareKey($key);

				$this->exclusiveLockCache();
				@unlink($this->base_path . $key);
				$this->unlockCache();
			}

			public function flush(): void {
				$this->exclusiveLockCache();
				$files = glob($this->base_path . $this->prefix . '*', GLOB_NOSORT);
				foreach ($files as $file) {
					@unlink($file);
				}
				$this->unlockCache();
			}

			public function close() {
				fclose($this->lock_fd);
			}
		};

		register_shutdown_function([$ret, 'close']);

		return $ret;
	}

	public static function phpArray() {
		return new class implements CacheDriver {
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
		};
	}

	/**
	 * No-op cache. Useful for testing.
	 */
	public static function none() {
		return new class implements CacheDriver {
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
		};
	}
}

interface CacheDriver {
	/**
	 * Get the value of associated with the key.
	 *
	 * @param string $key The key of the value.
	 * @return mixed|null The value associated with the key, or null if there is none.
	 */
	public function get(string $key): mixed;

	/**
	 * Set a key-value pair.
	 *
	 * @param string $key The key.
	 * @param mixed $value The value.
	 * @param int|false $expires After how many seconds the pair will expire. Use false or ignore this parameter to keep
	 *                           the value until it gets evicted to make space for more items. Some drivers will always
	 *                           ignore this parameter and store the pair until it's removed.
	 */
	public function set(string $key, mixed $value, mixed $expires = false): void;

	/**
	 * Delete a key-value pair.
	 *
	 * @param string $key The key.
	 */
	public function delete(string $key): void;

	/**
	 * Delete all the key-value pairs.
	 */
	public function flush(): void;
}
