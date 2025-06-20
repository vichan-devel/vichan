<?php
class Locks {
    private static function filesystem(string $key) {
        $key = str_replace('/', '::', $key);
        $key = str_replace("\0", '', $key);

        $fd = fopen("tmp/locks/$key", "w");
        if ($fd === false) {
            return false;
        }

        return new class($fd) implements Lock {
            // Resources have no type in PHP.
            private $f;

            public function __construct($fd) {
                $this->f = $fd;
            }

            public function get(bool $nonblock = false) {
                $wouldblock = false;
                flock($this->f, LOCK_SH | ($nonblock ? LOCK_NB : 0), $wouldblock);
                if ($nonblock && $wouldblock) {
                    return false;
                }
                return $this;
            }

            public function get_ex(bool $nonblock = false) {
                $wouldblock = false;
                flock($this->f, LOCK_EX | ($nonblock ? LOCK_NB : 0), $wouldblock);
                if ($nonblock && $wouldblock) {
                    return false;
                }
                return $this;
            }

            public function free() {
                flock($this->f, LOCK_UN);
                return $this;
            }
        };
    }

    public static function none() {
        return new class() implements Lock {
            public function get(bool $nonblock = false) {
                return $this;
            }

            public function get_ex(bool $nonblock = false) {
                return $this;
            }

            public function free() {
                return $this;
            }
        };
    }

    public static function get_lock(array $config, string $key) {
        if ($config['lock']['enabled'] == 'fs') {
            return self::filesystem($key);
        } else {
            return self::none();
        }
    }
}

interface Lock {
    public function get(bool $nonblock = false);

    public function get_ex(bool $nonblock = false);

    public function free();
}
