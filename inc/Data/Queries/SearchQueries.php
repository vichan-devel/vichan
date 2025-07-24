<?php
namespace Vichan\Data\Queries;


/**
 * Implements flood control for search queries.
 */
class SearchQueries {
	private \PDO $pdo;
	private int $queries_for_single;
	private int $range_for_single;
	private int $queries_for_all;
	private int $range_for_all;
	private bool $auto_gc;


	private function checkFloodImpl(string $ip, string $phrase): bool {
		$now = \time();

		$query = $this->pdo->prepare("SELECT COUNT(2) FROM `search_queries` WHERE `ip` = :ip AND `time` > :time");
		$query->bindValue(':ip', $ip);
		$query->bindValue(':time', $now - $this->range_for_single, \PDO::PARAM_INT);
		$query->execute();
		if ($query->fetchColumn() > $this->queries_for_single) {
			return true;
		}

		$query = $this->pdo->prepare("SELECT COUNT(2) FROM `search_queries` WHERE `time` > :time");
		$query->bindValue(':time', $now - $this->range_for_all, \PDO::PARAM_INT);
		$query->execute();
		if ($query->fetchColumn() > $this->queries_for_all) {
			return true;
		}

		$query = $this->pdo->prepare("INSERT INTO `search_queries` VALUES (:ip, :time, :query)");
		$query->bindValue(':ip', $ip);
		$query->bindValue(':time', $now, \PDO::PARAM_INT);
		$query->bindValue(':query', $phrase);
		$query->execute();

		if ($this->auto_gc) {
			$this->purgeExpired();
		}

		return false;
	}

	/**
	 * @param \PDO $pdo PDO to access the DB.
	 * @param int $queries_for_single Maximum number of queries for a single IP, in seconds.
	 * @param int $range_for_single Maximum age of the oldest query to consider from a single IP.
	 * @param int $queries_for_all Maximum number of queries for all IPs.
	 * @param int $range_for_all Maximum age of the oldest query to consider from all IPs, in seconds.
	 * @param bool $auto_gc If to run the cleanup at every check. Must be invoked from the outside otherwise.
	 */
	public function __construct(
		\PDO $pdo,
		int $queries_for_single,
		int $range_for_single,
		int $queries_for_all,
		int $range_for_all,
		bool $auto_gc
	) {
		$this->pdo = $pdo;
		$this->queries_for_single = $queries_for_single;
		$this->range_for_single = $range_for_single;
		$this->queries_for_all = $queries_for_all;
		$this->range_for_all = $range_for_all;
		$this->auto_gc = $auto_gc;
	}

	/**
	 * Check if the IP-query pair overflows the limit.
	 *
	 * @param string $ip Source IP.
	 * @param string $phrase The search query.
	 * @return bool True if the request goes over the limit.
	 */
	public function checkFlood(string $ip, string $phrase): bool {
		$this->pdo->beginTransaction();
		try {
			$ret = $this->checkFloodImpl($ip, $phrase);
			$this->pdo->commit();
			return $ret;
		} catch (\Exception $e) {
			$this->pdo->rollBack();
			throw $e;
		}
	}

	public function purgeExpired(): int {
		// Cleanup search queries table.
		$query = $this->pdo->prepare("DELETE FROM `search_queries` WHERE `time` <= :expiry_limit");
		$query->bindValue(':expiry_limit', \time() - $this->range_for_all, \PDO::PARAM_INT);
		$query->execute();
		return $query->rowCount();
	}
}
