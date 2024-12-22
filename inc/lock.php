<?php

class Locks {
	private static function filesystem(string $key): Lock|false {
		$key = str_replace('/', '::', $key);
		$key = str_replace("\0", '', $key);

		$fd = fopen("tmp/locks/$key", "w");
		if ($fd === false) {
			return false;
		}

		return new class($fd) implements Lock {
			// Resources have no type in php.
			private mixed $f;


			function __construct($fd) {
				$this->f = $fd;
			}

			public function get(bool $nonblock = false): Lock|false {
				$wouldblock = false;
				flock($this->f, LOCK_SH | ($nonblock ? LOCK_NB : 0), $wouldblock);
				if ($nonblock && $wouldblock) {
					return false;
				}
				return $this;
			}

			public function get_ex(bool $nonblock = false): Lock|false {
				$wouldblock = false;
				flock($this->f, LOCK_EX | ($nonblock ? LOCK_NB : 0), $wouldblock);
				if ($nonblock && $wouldblock) {
					return false;
				}
				return $this;
			}

			public function free(): Lock {
				flock($this->f, LOCK_UN);
				return $this;
			}
		};
	}

	/**
	 * No-op. Can be used for mocking.
	 */
	public static function none(): Lock|false {
		return new class() implements Lock {
			public function get(bool $nonblock = false): Lock|false {
				return $this;
			}

			public function get_ex(bool $nonblock = false): Lock|false {
				return $this;
			}

			public function free(): Lock {
				return $this;
			}
		};
	}

	public static function get_lock(array $config, string $key): Lock|false {
		if ($config['lock']['enabled'] == 'fs') {
			return self::filesystem($key);
		} else {
			return self::none();
		}
	}
}

interface Lock {
	// Get a shared lock
	public function get(bool $nonblock = false): Lock|false;

	// Get an exclusive lock
	public function get_ex(bool $nonblock = false): Lock|false;

	// Free a lock
	public function free(): Lock;
}
