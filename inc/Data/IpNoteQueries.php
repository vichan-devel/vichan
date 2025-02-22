<?php
namespace Vichan\Data;

use Vichan\Data\Driver\CacheDriver;


class IpNoteQueries {
	private \PDO $pdo;
	private CacheDriver $cache;


	public function __construct(\PDO $pdo, CacheDriver $cache) {
		$this->pdo = $pdo;
		$this->cache = $cache;
	}

	/**
	 * Get all the notes relative to an IP.
	 *
	 * @param string $ip The IP of the notes. THE STRING IS NOT VALIDATED.
	 * @return array Returns an array of notes sorted by the most recent. Includes the username of the mods.
	 */
	public function getByIp(string $ip) {
		$ret = $this->cache->get("ip_note_queries_$ip");
		if ($ret !== null) {
			return $ret;
		}

		$query = $this->pdo->prepare('SELECT `ip_notes`.*, `username` FROM `ip_notes` LEFT JOIN `mods` ON `mod` = `mods`.`id` WHERE `ip` = :ip ORDER BY `time` DESC');
		$query->bindValue(':ip', $ip);
		$query->execute();
		$ret = $query->fetchAll(\PDO::FETCH_ASSOC);

		$this->cache->set("ip_note_queries_$ip", $ret);
		return $ret;
	}

	/**
	 * Creates a new note relative to the given ip.
	 *
	 * @param string $ip The IP of the note. THE STRING IS NOT VALIDATED.
	 * @param int $mod_id The id of the mod who created the note.
	 * @param string $body The text of the note.
	 * @return void
	 */
	public function add(string $ip, int $mod_id, string $body) {
		$query = $this->pdo->prepare('INSERT INTO `ip_notes` (`ip`, `mod`, `time`, `body`) VALUES (:ip, :mod, :time, :body)');
		$query->bindValue(':ip', $ip);
		$query->bindValue(':mod', $mod_id);
		$query->bindValue(':time', time());
		$query->bindValue(':body', $body);
		$query->execute();

		$this->cache->delete("ip_note_queries_$ip");
	}

	/**
	 * Delete a note only if it's of a particular IP address.
	 *
	 * @param int $id The id of the note.
	 * @param int $ip The expected IP of the note. THE STRING IS NOT VALIDATED.
	 * @return bool True if any note was deleted.
	 */
	public function deleteWhereIp(int $id, string $ip): bool {
		$query = $this->pdo->prepare('DELETE FROM `ip_notes` WHERE `ip` = :ip AND `id` = :id');
		$query->bindValue(':ip', $ip);
		$query->bindValue(':id', $id);
		$query->execute();
		$any = $query->rowCount() != 0;

		if ($any) {
			$this->cache->delete("ip_note_queries_$ip");
		}
		return $any;
	}
}
