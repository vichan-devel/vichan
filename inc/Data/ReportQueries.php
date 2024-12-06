<?php
namespace Vichan\Data;


class ReportQueries {
	private \PDO $pdo;
	private bool $auto_maintenance;


	private function deleteReportImpl(string $board, int $post_id) {
		$query = $this->pdo->prepare('DELETE FROM `reports` WHERE `post` = :id AND `board` = :board');
		$query->bindValue(':id', $post_id, \PDO::PARAM_INT);
		$query->bindValue(':board', $board);
		$query->execute();
	}

	private function joinReportPosts(array $raw_reports, ?int $limit): array {
		// Group the reports rows by board.
		$reports_by_boards = [];
		foreach ($raw_reports as $report) {
			if (!isset($reports_by_boards[$report['board']])) {
				$reports_by_boards[$report['board']] = [];
			}
			$reports_by_boards[$report['board']][] = $report['post'];
		}

		// Join the reports with the actual posts.
		$report_posts = [];
		foreach ($reports_by_boards as $board => $posts) {
			$report_posts[$board] = [];

			$query = $this->pdo->prepare(\sprintf('SELECT * FROM `posts_%s` WHERE `id` IN (' . \implode(',', $posts) . ')', $board));
			$query->execute();
			while ($post = $query->fetch(\PDO::FETCH_ASSOC)) {
				$report_posts[$board][$post['id']] = $post;
			}
		}

		// Filter out the reports without a valid post.
		$valid = [];
		foreach ($raw_reports as $report) {
			if (isset($report_posts[$report['board']][$report['post']])) {
				$report['post_data'] = $report_posts[$report['board']][$report['post']];
				$valid[] = $report;

				if ($limit !== null && \count($valid) >= $limit) {
					return $valid;
				}
			} else {
				// Invalid report (post has been deleted).
				if ($this->auto_maintenance != false) {
					$this->deleteReportImpl($report['board'], $report['post']);
				}
			}
		}
		return $valid;
	}

	/**
	 * Filters out the invalid reports.
	 *
	 * @param array $raw_reports Array with the raw fetched reports. Must include a `board`, `post` and `id` fields.
	 * @param bool $get_invalid True to reverse the filter and get the invalid reports instead.
	 * @return array An array of filtered reports.
	 */
	private function filterReports(array $raw_reports, bool $get_invalid): array {
		// Group the reports rows by board.
		$reports_by_boards = [];
		foreach ($raw_reports as $report) {
			if (!isset($reports_by_boards[$report['board']])) {
				$reports_by_boards[$report['board']] = [];
			}
			$reports_by_boards[$report['board']][] = $report['post'];
		}

		// Join the reports with the actual posts.
		$report_posts = [];
		foreach ($reports_by_boards as $board => $posts) {
			$report_posts[$board] = [];

			$query = $this->pdo->prepare(\sprintf('SELECT `id` FROM `posts_%s` WHERE `id` IN (' . \implode(',', $posts) . ')', $board));
			$query->execute();
			while ($post = $query->fetch(\PDO::FETCH_ASSOC)) {
				$report_posts[$board][$post['id']] = $post;
			}
		}

		if ($get_invalid) {
			// Get the reports without a post.
			$invalid = [];
			foreach ($raw_reports as $report) {
				if (isset($report_posts[$report['board']][$report['post']])) {
					$invalid[] = $report;
				}
			}
			return $invalid;
		} else {
			// Filter out the reports without a valid post.
			$valid = [];
			foreach ($raw_reports as $report) {
				if (isset($report_posts[$report['board']][$report['post']])) {
					$valid[] = $report;
				} else {
					// Invalid report (post has been deleted).
					if ($this->auto_maintenance != false) {
						$this->deleteReportImpl($report['board'], $report['post']);
					}
				}
			}
			return $valid;
		}
	}

	/**
	 * @param \PDO $pdo PDO connection.
	 * @param bool $auto_maintenance If the auto maintenance should be enabled.
	 */
	public function __construct(\PDO $pdo, bool $auto_maintenance) {
		$this->pdo = $pdo;
		$this->auto_maintenance = $auto_maintenance;
	}

	/**
	 * Get the number of reports.
	 *
	 * @return int The number of reports.
	 */
	public function getCount(): int {
		$query = $this->pdo->prepare('SELECT `board`, `post`, `id` FROM `reports`');
		$query->execute();
		$raw_reports = $query->fetchAll(\PDO::FETCH_ASSOC);
		$valid_reports = $this->filterReports($raw_reports, false, null);
		$count = \count($valid_reports);
		return $count;
	}

	/**
	 * Get the report with the given id. DOES NOT PERFORM VALIDITY CHECK.
	 *
	 * @param int $id The id of the report to fetch.
	 * @return ?array An array of the given report with the `board`, `ip` and `post` fields. Null if no such report exists.
	 */
	public function getReportById(int $id): ?array {
		$query = $this->pdo->prepare('SELECT `board`, `ip`, `post` FROM `reports` WHERE `id` = :id');
		$query->bindValue(':id', $id);
		$query->execute();

		$ret = $query->fetch(\PDO::FETCH_ASSOC);
		if ($ret !== false) {
			return $ret;
		} else {
			return null;
		}
	}

	/**
	 * Get the reports with the associated post data.
	 *
	 * @param int $count The maximum number of rows in the return array.
	 * @return array The reports with the associated post data.
	 */
	public function getReportsWithPosts(int $count): array {
		$query = $this->pdo->prepare('SELECT * FROM `reports` ORDER BY `time`');
		$query->execute();
		$raw_reports = $query->fetchAll(\PDO::FETCH_ASSOC);
		return $this->joinReportPosts($raw_reports, $count);
	}

	/**
	 * Purge the invalid reports.
	 *
	 * @return int The number of reports deleted.
	 */
	public function purge(): int {
		$query = $this->pdo->prepare('SELECT `board`, `post`, `id` FROM `reports`');
		$query->execute();
		$raw_reports = $query->fetchAll(\PDO::FETCH_ASSOC);
		$invalid_reports = $this->filterReports($raw_reports, true, null);

		foreach ($invalid_reports as $report) {
			$this->deleteReportImpl($report['board'], $report['post']);
		}
		return \count($invalid_reports);
	}

	/**
	 * Deletes the given report.
	 *
	 * @param int $id The report id.
	 */
	public function deleteById(int $id) {
		$query = $this->pdo->prepare('DELETE FROM `reports` WHERE `id` = :id');
		$query->bindValue(':id', $id, \PDO::PARAM_INT);
		$query->execute();
	}

	/**
	 * Deletes all reports from the given ip.
	 *
	 * @param string $ip The reporter ip.
	 */
	public function deleteByIp(string $ip) {
		$query = $this->pdo->prepare('DELETE FROM `reports` WHERE `ip` = :ip');
		$query->bindValue(':ip', $ip);
		$query->execute();
	}

	/**
	 * Inserts a new report.
	 *
	 * @param string $ip Ip of the user sending the report.
	 * @param string $board_uri Board uri of the reported thread. MUST ALREADY BE SANITIZED.
	 * @param int $post_id Post reported.
	 * @param string $reason Reason of the report.
	 * @return void
	 */
	public function add(string $ip, string $board_uri, int $post_id, string $reason) {
		$query = $this->pdo->prepare('INSERT INTO `reports` VALUES (NULL, :time, :ip, :board, :post, :reason)');
		$query->bindValue(':time', time(), \PDO::PARAM_INT);
		$query->bindValue(':ip', $ip);
		$query->bindValue(':board', $board_uri);
		$query->bindValue(':post', $post_id, \PDO::PARAM_INT);
		$query->bindValue(':reason', $reason);
		$query->execute();
	}
}
