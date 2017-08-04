<?php


class Whitelist {
	static public function check($ip, $cookie) {
		global $config;
		
		if (!$config['whitelist']['enabled'])
			return true;

		$query = prepare("SELECT COUNT(*) AS total FROM ``whitelist`` WHERE `ip` = :ip OR `cookie` = :cookie");
		$query->bindValue(':ip', $ip);
		$query->bindValue(':cookie', $cookie);
		$query->execute() or error(db_error($query));

		return $query->fetch(PDO::FETCH_ASSOC)['total'] > 0;
	}

	static public function add_user($ip, $cookie) {
		global $config;

		$expiration_time = time() + 60 * 60 * 24 * $config['whitelist']['expires_in'];

		$query = prepare("INSERT INTO ``whitelist`` VALUES (NULL, :expiration_time, :ip, :cookie)");
		$query->bindValue(':expiration_time', $expiration_time, PDO::PARAM_INT);
		$query->bindValue(':ip', $ip);
		$query->bindValue(':cookie', $cookie);
		$query->execute() or error(db_error($query));

		Whitelist::purge_expired_entries();
	}

	static public function remove_user($ip, $cookie) {
		global $config;

		$query = prepare("DELETE FROM ``whitelist`` WHERE `ip` = :ip OR `cookie` = :cookie");
		$query->bindValue(':ip', $ip);
		$query->bindValue(':cookie', get_uuser_cookie());
		$query->execute() or error(db_error($query));

		Whitelist::purge_expired_entries();
	}

	static public function purge_expired_entries() {
		global $config;

		$query = prepare("DELETE FROM ``whitelist`` WHERE `expiration_time` < :current_time");
		$query->bindValue(':current_time', time(), PDO::PARAM_INT);
		$query->execute() or error(db_error($query));
	}
}