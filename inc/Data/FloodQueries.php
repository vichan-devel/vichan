<?php

namespace Vichan\Data;

/**
 * Handles direct database interactions for the `flood` table.
 */
class FloodQueries {
	/**
	 * @var \PDO The PDO database connection instance.
	 */
	private \PDO $pdo;

	/**
	 * Constructor for FloodQueries.
	 *
	 * @param \PDO $pdo An active PDO database connection.
	 */
	public function __construct(\PDO $pdo) {
		$this->pdo = $pdo;
	}

	/**
	 * Retrieves recent flood entries matching IP, post hash, or optionally file hash if a post has files.
	 *
	 * @param string $ip The poster's IP address.
	 * @param string $postHash The hash of the post body content.
	 * @param string|null $fileHash Optional hash of the attached file.
	 * @return array<int, array<string, mixed>> An array of matching flood records.
	 */
	public function getRecentFloodEntries(string $ip, string $postHash, ?string $fileHash = null): array {
		$sql = "SELECT * FROM `flood` WHERE `ip` = :ip OR `posthash` = :posthash";

		if ($fileHash) {
			$sql .= " OR `filehash` = :filehash";
		}

		$stmt = $this->pdo->prepare($sql);
		$stmt->bindValue(':ip', $ip);
		$stmt->bindValue(':posthash', $postHash);

		if ($fileHash) {
			$stmt->bindValue(':filehash', $fileHash);
		}

		$stmt->execute();
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	/**
	 * Inserts a new record into the flood table.
	 *
	 * @param string $ip The poster's IP address.
	 * @param string $posthash The hash of the post body content.
	 * @param string|null $filehash The hash of the attached file, or null if post has no files.
	 * @param string $board The board URI where the post was made.
	 * @param int $time The unix timestamp of when the post was made.
	 * @param bool $isreply True if the post was a reply, false if it was a new thread.
	*/
	public function addFloodEntry(
		string $ip,
		string $posthash,
		?string $filehash,
		string $board,
		int $time,
		bool $isreply
	): void {
		$sql = "INSERT INTO `flood` (`ip`, `posthash`, `filehash`, `board`, `time`, `isreply`)
				VALUES (:ip, :posthash, :filehash, :board, :time, :isreply)";

		$stmt = $this->pdo->prepare($sql);
		$stmt->bindValue(':ip', $ip);
		$stmt->bindValue(':posthash', $posthash);
		$stmt->bindValue(':filehash', $filehash, $filehash === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR);
		$stmt->bindValue(':board', $board);
		$stmt->bindValue(':time', $time, \PDO::PARAM_INT);
		$stmt->bindValue(':isreply', $isreply, \PDO::PARAM_BOOL);

		$stmt->execute();
	}

	/**
	 * Deletes flood records older than a specified time window.
	 *
	 * @param int $maxTime The maximum age (in seconds) an entry should have to be kept.
	 * 						Entries older than (current_time - maxTime) will be deleted.
	 */
	public function purgeOldEntries(int $maxTime): void {
		$time = \time() - $maxTime;
		$sql = "DELETE FROM `flood` WHERE `time` < :time";

		$stmt = $this->pdo->prepare($sql);
		$stmt->bindValue(':time', $time, \PDO::PARAM_INT);
		$stmt->execute();
	}
}
