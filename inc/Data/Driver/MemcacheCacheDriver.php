<?php
namespace Vichan\Data\Driver;

defined('TINYBOARD') or exit;


class MemcachedCacheDriver implements CacheDriver {
	use CacheDriverTrait;

	private \Memcached $inner;


	/**
	 * @param string $prefix Key prefix. Needed if multiple programs share the same memcached server.
	 * @param array<array{string, int, ?int}> $server_tuple An array of arrays with the uri to the memcached server,
	 *                                                      port and weight.
	 * @throws \RuntimeException
	 */
	public function __construct(string $prefix, array $server_tuples) {
		$this->inner = new \Memcached();
		if (!$this->inner->setOption(\Memcached::OPT_BINARY_PROTOCOL, true)) {
			$err = $this->inner->getResultMessage();
			throw new \RuntimeException("Unable to set the memcached protocol: '$err'");
		}
		if (!$this->inner->setOption(\Memcached::OPT_PREFIX_KEY, $prefix)) {
			$err = $this->inner->getResultMessage();
			throw new \RuntimeException("Unable to set the memcached prefix: '$err'");
		}

		/*
		 * Memcached both keeps the server connections open across requests and supports multiple servers,
		 * this makes initialization a mess.
		 */
		$current_servers = $this->inner->getServerList();
		$change_detected = \count($current_servers) !== \count($server_tuples); // If the sizes differ, there's a change indeed.
		$parsed = [];

		foreach ($server_tuples as $tuple) {
			$server_uri = $tuple[0];
			$server_port = $tuple[1] ?? 11211; // Default memcached port.
			$server_weight = $tuple[2] ?? null;

			$maybe_unix_path = self::asUnixSocketPath($server_uri);
			$is_unix = $maybe_unix_path !== null;
			if ($is_unix) {
				$server_uri = $maybe_unix_path;
			}
			$parsed[] = [ $server_uri, $server_port, $server_weight ];

			foreach ($current_servers as $curr) {
				// Ignore the port if the server is connected with a unix socket.
				if ($curr['host'] !== $server_uri || (!$is_unix && $curr['port'] !== $server_port)) {
					$change_detected = true;
				}
			}
		}

		if ($change_detected) {
			if (!empty($current_servers)) {
				if (!$this->inner->resetServerList()) {
					$err = $this->inner->getResultMessage();
					throw new \RuntimeException("Unable to reset the memcached server list: '$err'");
				}
			}
			if (!empty($parsed)) {
				if (!$this->inner->addServers($parsed)) {
					$err = $this->inner->getResultMessage();
					throw new \RuntimeException("Unable to add memcached servers: '$err'");
				}
			}
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
