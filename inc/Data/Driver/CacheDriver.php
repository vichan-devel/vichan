<?php
namespace Vichan\Data\Driver;

defined('TINYBOARD') or exit;


interface CacheDriver {
        /**
         * Get the value of associated with the key.
         *
         * @param string $key The key of the value.
         * @return mixed|null The value associated with the key, or null if there is none.
         */
        public function get(string $key);

        /**
         * Set a key-value pair.
         *
         * @param string $key The key.
         * @param mixed $value The value.
         * @param int|false $expires After how many seconds the pair will expire. Use false or ignore this parameter to keep
         *                           the value until it gets evicted to make space for more items. Some drivers will always
         *                           ignore this parameter and store the pair until it's removed.
         */
        public function set(string $key, $value, $expires = false);

        /**
         * Delete a key-value pair.
         *
         * @param string $key The key.
         */
        public function delete(string $key);

        /**
         * Delete all the key-value pairs.
         */
        public function flush();
}
