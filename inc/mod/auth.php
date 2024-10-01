<?php

/*
 *  Copyright (c) 2010-2013 Tinyboard Development Group
 */

use Vichan\Context;
use Vichan\Functions\Net;

defined('TINYBOARD') or exit;

// create a hash/salt pair for validate logins
function mkhash(string $username, string $password, mixed $salt = false): array|string {
	global $config;

	if (!$salt) {
		// create some sort of salt for the hash
		$salt = substr(base64_encode(sha1(rand() . time(), true) . $config['cookies']['salt']), 0, 15);

		$generated_salt = true;
	}

	// generate hash (method is not important as long as it's strong)
	$hash = substr(
		base64_encode(
			md5(
				$username . $config['cookies']['salt'] . sha1(
					$username . $password . $salt . (
						$config['mod']['lock_ip'] ? $_SERVER['REMOTE_ADDR'] : ''
					), true
				) . sha1($config['password_crypt_version']) // Log out users being logged in with older password encryption schema
				, true
			)
		), 0, 20
	);

	if (isset($generated_salt)) {
		return [ $hash, $salt ];
	} else {
		return $hash;
	}
}

function crypt_password(string $password): array {
	global $config;
	// `salt` database field is reused as a version value. We don't want it to be 0.
	$version = $config['password_crypt_version'] ? $config['password_crypt_version'] : 1;
	$new_salt = generate_salt();
	$password = crypt($password, $config['password_crypt'] . $new_salt . "$");
	return [ $version, $password ];
}

function test_password(string $password, string $salt, string $test): array {
	// Version = 0 denotes an old password hashing schema. In the same column, the
	// password hash was kept previously
	$version = strlen($salt) <= 8 ? (int)$salt : 0;

	if ($version == 0) {
		$comp = hash('sha256', $salt . sha1($test));
	} else {
		$comp = crypt($test, $password);
	}
	return [ $version, hash_equals($password, $comp) ];
}

function generate_salt(): string {
	return strtr(base64_encode(random_bytes(16)), '+', '.');
}

function calc_cookie_name(bool $is_https, bool $is_path_jailed, string $base_name): string {
	if ($is_https) {
		if ($is_path_jailed) {
			return "__Host-$base_name";
		} else {
			return "__Secure-$base_name";
		}
	} else {
		return $base_name;
	}
}

function login(string $username, string $password): array|false {
	global $mod, $config;

	$query = prepare("SELECT `id`, `type`, `boards`, `password`, `version` FROM ``mods`` WHERE BINARY `username` = :username");
	$query->bindValue(':username', $username);
	$query->execute() or error(db_error($query));

	if ($user = $query->fetch(PDO::FETCH_ASSOC)) {
		list($version, $ok) = test_password($user['password'], $user['version'], $password);

		if ($ok) {
			if ($config['password_crypt_version'] > $version) {
				// It's time to upgrade the password hashing method!
				list ($user['version'], $user['password']) = crypt_password($password);
				$query = prepare("UPDATE ``mods`` SET `password` = :password, `version` = :version WHERE `id` = :id");
				$query->bindValue(':password', $user['password']);
				$query->bindValue(':version', $user['version']);
				$query->bindValue(':id', $user['id']);
				$query->execute() or error(db_error($query));
			}

			return $mod = [
				'id' => $user['id'],
				'type' => $user['type'],
				'username' => $username,
				'hash' => mkhash($username, $user['password']),
				'boards' => explode(',', $user['boards'])
			];
		}
	}

	return false;
}

function setCookies(): void {
	global $mod, $config;
	if (!$mod) {
		error('setCookies() was called for a non-moderator!');
	}

	$is_https = Net\is_connection_secure($config['cookies']['secure_login_only'] === 1);
	$is_path_jailed = $config['cookies']['jail'];
	$name = calc_cookie_name($is_https, $is_path_jailed, $config['cookies']['mod']);

	// <username>:<password>:<salt>
	$value = "{$mod['username']}:{$mod['hash'][0]}:{$mod['hash'][1]}";

	$options = [
		'expires' => time() + $config['cookies']['expire'],
		'path' => $is_path_jailed ? $config['cookies']['path'] : '/',
		'secure' => $is_https,
		'httponly' => $config['cookies']['httponly'],
		'samesite' => 'Strict'
	];

	setcookie($name, $value, $options);
}

function destroyCookies(): void {
	global $config;
	$base_name = $config['cookies']['mod'];
	$del_time = time() - 60 * 60 * 24 * 365; // 1 year.
	$jailed_path = $config['cookies']['jail'] ? $config['cookies']['path'] : '/';
	$http_only = $config['cookies']['httponly'];

	$options_multi = [
		$base_name => [
			'expires' => $del_time,
			'path' => $jailed_path ,
			'secure' => false,
			'httponly' => $http_only,
			'samesite' => 'Strict'
		],
		"__Host-$base_name" => [
			'expires' => $del_time,
			'path' => $jailed_path,
			'secure' => true,
			'httponly' => $http_only,
			'samesite' => 'Strict'
		],
		"__Secure-$base_name" => [
			'expires' => $del_time,
			'path' => '/',
			'secure' => true,
			'httponly' => $http_only,
			'samesite' => 'Strict'
		]
	];

	foreach ($options_multi as $name => $options) {
		if (isset($_COOKIE[$name])) {
			setcookie($name, 'deleted', $options);
			unset($_COOKIE[$name]);
		}
	}
}

function modLog(string $action, ?string $_board = null): void {
	global $mod, $board, $config;
	$query = prepare("INSERT INTO ``modlogs`` VALUES (:id, :ip, :board, :time, :text)");
	$query->bindValue(':id', (isset($mod['id']) ? $mod['id'] : -1), PDO::PARAM_INT);
	$query->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
	$query->bindValue(':time', time(), PDO::PARAM_INT);
	$query->bindValue(':text', $action);
	if (isset($_board))
		$query->bindValue(':board', $_board);
	elseif (isset($board))
		$query->bindValue(':board', $board['uri']);
	else
		$query->bindValue(':board', null, PDO::PARAM_NULL);
	$query->execute() or error(db_error($query));

	if ($config['syslog']) {
		_syslog(LOG_INFO, '[mod/' . $mod['username'] . ']: ' . $action);
	}
}

function create_pm_header(): mixed {
	global $mod, $config;

	if ($config['cache']['enabled'] && ($header = cache::get('pm_unread_' . $mod['id'])) != false) {
		if ($header === true) {
			return false;
		}

		return $header;
	}

	$query = prepare("SELECT `id` FROM ``pms`` WHERE `to` = :id AND `unread` = 1");
	$query->bindValue(':id', $mod['id'], PDO::PARAM_INT);
	$query->execute() or error(db_error($query));

	if ($pm = $query->fetch(PDO::FETCH_ASSOC)) {
		$header = [ 'id' => $pm['id'], 'waiting' => $query->rowCount() - 1 ];
	} else {
		$header = true;
	}

	if ($config['cache']['enabled']) {
		cache::set('pm_unread_' . $mod['id'], $header);
	}

	if ($header === true) {
		return false;
	}

	return $header;
}

function make_secure_link_token(string $uri): string {
	global $mod, $config;
	return substr(sha1($config['cookies']['salt'] . '-' . $uri . '-' . $mod['id']), 0, 8);
}

function check_login(Context $ctx, bool $prompt = false): void {
	global $config, $mod;

	$is_https = Net\is_connection_secure($config['cookies']['secure_login_only'] === 1);
	$is_path_jailed = $config['cookies']['jail'];
	$expected_cookie_name = calc_cookie_name($is_https, $is_path_jailed, $config['cookies']['mod']);

	// Validate session
	if (isset($_COOKIE[$expected_cookie_name])) {
		// Should be username:hash:salt
		$cookie = explode(':', $_COOKIE[$expected_cookie_name]);
		if (count($cookie) != 3) {
			// Malformed cookies
			destroyCookies();
			if ($prompt) {
				mod_login($ctx);
			}
			exit;
		}

		$query = prepare("SELECT `id`, `type`, `boards`, `password` FROM ``mods`` WHERE `username` = :username");
		$query->bindValue(':username', $cookie[0]);
		$query->execute() or error(db_error($query));
		$user = $query->fetch(PDO::FETCH_ASSOC);

		// validate password hash
		if ($cookie[1] !== mkhash($cookie[0], $user['password'], $cookie[2])) {
			// Malformed cookies
			destroyCookies();
			if ($prompt) {
				mod_login($ctx);
			}
			exit;
		}

		$mod = array(
			'id' => (int)$user['id'],
			'type' => (int)$user['type'],
			'username' => $cookie[0],
			'boards' => explode(',', $user['boards'])
		);
	}
}
