<?php

class Queues {
	private static $queues = array();


	/**
	 * This queue implementation isn't actually ordered, so it works more as a "bag".
	 */
	private static function filesystem(string $key, Lock $lock): Queue {
		$key = str_replace('/', '::', $key);
		$key = str_replace("\0", '', $key);
		$key = "tmp/queue/$key/";

		return new class($key, $lock) implements Queue {
			private Lock $lock;
			private string $key;


			function __construct(string $key, Lock $lock) {
				$this->lock = $lock;
				$this->key = $key;
			}

			public function push(string $str): bool {
				$this->lock->get_ex();
				$ret = file_put_contents($this->key . microtime(true), $str);
				$this->lock->free();
				return $ret !== false;
			}

			public function pop(int $n = 1): array {
				$this->lock->get_ex();
				$dir = opendir($this->key);
				$paths = array();

				while ($n > 0) {
					$path = readdir($dir);
					if ($path === false) {
						break;
					} elseif ($path == '.' || $path == '..') {
						continue;
					} else {
						$paths[] = $path;
						$n--;
					}
				}

				$out = array();
				foreach ($paths as $v) {
					$out[] = file_get_contents($this->key . $v);
					unlink($this->key . $v);
				}

				$this->lock->free();
				return $out;
			}
		};
	}

	/**
	 * No-op. Can be used for mocking.
	 */
	public static function none(): Queue {
		return new class() implements Queue {
			public function push(string $str): bool {
				return true;
			}

			public function pop(int $n = 1): array {
				return array();
			}
		};
	}

	public static function get_queue(array $config, string $name): Queue|false {
		if (!isset(self::$queues[$name])) {
			if ($config['queue']['enabled'] == 'fs') {
				$lock = Locks::get_lock($config, $name);
				if ($lock === false) {
					return false;
				}
				self::$queues[$name] = self::filesystem($name, $lock);
			} else {
				self::$queues[$name] = self::none();
			}
		}
		return self::$queues[$name];
	}
}

interface Queue {
	// Push a string in the queue.
	public function push(string $str): bool;

	// Get a string from the queue.
	public function pop(int $n = 1): array;
}
