<?php

/*
 *  Copyright (c) 2010-2013 Tinyboard Development Group
 */

use Vichan\Data\Driver\{CacheDriver, ApcuCacheDriver, ArrayCacheDriver, FsCacheDriver, MemcachedCacheDriver, NoneCacheDriver, RedisCacheDriver};

defined('TINYBOARD') or exit;


class Cache {
	private static function buildCache(): CacheDriver {
		global $config;

		switch ($config['cache']['enabled']) {
			case 'memcached':
				return new MemcachedCacheDriver(
					$config['cache']['prefix'],
					$config['cache']['memcached']
				);
			case 'redis':
				$host = $config['cache']['redis'][0] ?? 'localhost';
				$port = $config['cache']['redis'][1] ?? 6379;
				$password = $config['cache']['redis'][2] ?? '';
				$database = $config['cache']['redis'][3] ?? 1;

				$host = getenv('VICHAN_CACHE_HOST') ?: $host;
				$port = getenv('VICHAN_CACHE_PORT') ?: $port;
				$password = getenv('VICHAN_CACHE_PASSWORD') ?: $password;
				$database = getenv('VICHAN_CACHE_DATABASE') ?: $database;
				
				return new RedisCacheDriver(
					$config['cache']['prefix'],
					$host,
					$port,
					$password,
					$database
				);
			case 'apcu':
				return new ApcuCacheDriver;
			case 'fs':
				return new FsCacheDriver(
					$config['cache']['prefix'],
					"tmp/cache/{$config['cache']['prefix']}",
					'.lock',
					$config['auto_maintenance'] ? 1000 : false
				);
			case 'none':
				return new NoneCacheDriver();
			case 'php':
			default:
				return new ArrayCacheDriver();
		}
	}

	public static function getCache(): CacheDriver {
		static $cache;
		return $cache ??= self::buildCache();
	}

	public static function get($key) {
		global $config, $debug;

		$ret = self::getCache()->get($key);
		if ($ret === null) {
			$ret = false;
		}

		if ($config['debug']) {
			$debug['cached'][] = $config['cache']['prefix'] . $key . ($ret === false ? ' (miss)' : ' (hit)');
		}

		return $ret;
	}
	public static function set($key, $value, $expires = false) {
		global $config, $debug;

		if (!$expires) {
			$expires = $config['cache']['timeout'];
		}

		self::getCache()->set($key, $value, $expires);

		if ($config['debug']) {
			$debug['cached'][] = $config['cache']['prefix'] . $key . ' (set)';
		}
	}
	public static function delete($key) {
		global $config, $debug;

		self::getCache()->delete($key);

		if ($config['debug']) {
			$debug['cached'][] = $config['cache']['prefix'] . $key . ' (deleted)';
		}
	}
	public static function flush() {
		self::getCache()->flush();
		return false;
	}
}
