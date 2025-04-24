<?php
namespace Vichan\Data\Driver;

// Prevent direct access to this file for security
defined('TINYBOARD') or exit;


// Handles caching using Redis, a fast in-memory data store
class RedisCacheDriver implements CacheDriver {
	private string $prefix;
	private \Redis $inner;

	// Sets up the Redis connection
	public function __construct(string $prefix, string $host, int $port, ?string $password, int $database) {
		$this->inner = new \Redis();
		$this->inner->connect($host, $port);

		if ($password) {
			$this->inner->auth($password);
		}

		if (!$this->inner->select($database)) {
			throw new \RuntimeException('Unable to select Redis database ' . $database);
		}

		$this->prefix = $prefix;
	}

	// Retrieves a value from the cache by key
	public function get(string $key): mixed {

		$ret = $this->inner->get($this->prefix . $key);
		if ($ret === false) {
			// Return null if the key doesn't exist
			return null;
		}

		return \json_decode($ret, true);
	}

	// Stores a value in the cache with an optional expiration time
	public function set(string $key, mixed $value, mixed $expires = false): void {
		// Convert the value to JSON for storage
		$encodedValue = \json_encode($value);

		if ($expires === false || !is_numeric($expires) || $expires <= 0) {
			// Store the value without an expiration
			$this->inner->set($this->prefix . $key, $encodedValue);
		} else {
			// Store the value with an expiration time (in seconds)
			$ttl_seconds = (int)$expires;
			$this->inner->setex($this->prefix . $key, $ttl_seconds, $encodedValue);
		}
	}

	// Deletes a specific key from the cache
	public function delete(string $key): void {
		// Remove the key from Redis
		$this->inner->del($this->prefix . $key);
	}

	// Clears all data in the current Redis database
	public function flush(): void {
		$this->inner->flushDB();
	}
}
