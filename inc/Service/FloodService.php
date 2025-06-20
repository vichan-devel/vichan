<?php

namespace Vichan\Service;

use Vichan\Data\FloodQueries;

/**
 * Manages post flood detection logic based on recent post history.
 * Uses filter configurations to determine relevant time windows.
 */
class FloodService {
	/**
	 * @var FloodQueries Handles database interactions for flood data.
	 */
	private FloodQueries $floodQueries;

	/**
	 * @var array<int, array<string, mixed>> Filter configurations.
	 */
	private array $filters;

	/**
	 * @var int Cache for the calculated maximum flood time (-1 means not calculated).
	 */
	private int $flood_cache;

	/**
	 * Constructor for FloodService.
	 *
	 * @param FloodQueries $floodQueries The database query handler for flood data.
	 * @param array<int, array<string, mixed>> $filters Filter configurations.
	 * @param int $flood_cache Optional pre-calculated max flood time.
	 */
	public function __construct(FloodQueries $floodQueries, array $filters, int $flood_cache = -1) {
		$this->floodQueries = $floodQueries;
		$this->filters = $filters;
		$this->flood_cache = $flood_cache;
	}

	/**
	 * Calculates or retrieves the maximum flood time window from filters.
	 *
	 * @return int The maximum 'flood-time' value found in filters, or 0 if none.
	 */
	public function getMaxFloodTime(): int {
		if (isset($this->flood_cache) && $this->flood_cache !== -1) {
			return $this->flood_cache;
		}

		$maxTime = 0;
		foreach ($this->filters as $filter) {
			if (isset($filter['condition']['flood-time']) && $filter['condition']['flood-time'] > $maxTime) {
				$maxTime = $filter['condition']['flood-time'];
			}
		}
		$this->flood_cache = $maxTime;

		return $maxTime;
	}

	/**
	 * Removes flood entries older than the maximum relevant flood time.
	*/
	public function purgeOldEntries(): void {
		$maxTime = $this->getMaxFloodTime();

		if ($maxTime > 0) {
			$this->floodQueries->purgeOldEntries($maxTime);
		}
	}

	/**
	 * Retrieves recent flood entries relevant to the given post's IP, body hash, or file hash.
	 *
	 * @param array<string, mixed> $post The post data array. Expects 'ip', 'body_nomarkup', optionally 'filehash', 'has_file'.
	 * @return array<int, array<string, mixed>> List of matching flood entries.
	 */
	public function getFloodEntries(array $post): array {
		$bodyHash = \make_comment_hex($post['body_nomarkup'] ?? '');
		$fileHash = isset($post['has_file']) && $post['has_file'] ? $post['filehash'] : null;

		return $this->floodQueries->getRecentFloodEntries($post['ip'], $bodyHash, $fileHash);
	}

	/**
	 * Compares a specific field between a historical flood entry and the current post.
	 *
	 * Valid conditions: 'ip', 'body', 'file', 'board', 'isreply'.
	 *
	 * @param string $condition The condition to check.
	 * @param array<string, mixed> $floodPost A single historical flood entry.
	 * @param array<string, mixed> $post The current post data.
	 * @return bool True if the condition matches between the two posts.
	 * @throws \InvalidArgumentException If the condition string is not recognized.
	 */
	public function checkFloodCondition(string $condition, array $floodPost, array $post): bool {
		switch ($condition) {
			case 'ip':
				return isset($floodPost['ip'], $post['ip']) && $floodPost['ip'] === $post['ip'];
			case 'body':
				$currentBodyHash = \make_comment_hex($post['body_nomarkup'] ?? '');
				return isset($floodPost['posthash']) && $floodPost['posthash'] === $currentBodyHash;
			case 'file':
				$hasFile = $post['has_file'] ?? false;
				$currentFileHash = ($hasFile && isset($post['filehash'])) ? $post['filehash'] : null;
				return isset($floodPost['filehash']) && $currentFileHash !== null && $floodPost['filehash'] === $currentFileHash;
			case 'board':
				return isset($floodPost['board'], $post['board']) && $floodPost['board'] === $post['board'];
			case 'isreply':
				$currentIsReply = isset($post['thread']);
				return isset($floodPost['isreply']) && (bool)$floodPost['isreply'] === $currentIsReply;
			default:
				throw new \InvalidArgumentException('Invalid filter flood condition: ' . $condition);
		}
	}

	/**
	 * Records a new entry in the flood table for the given post.
	 *
	 * @param array<string, mixed> $post The post data array. Expects 'ip', 'body_nomarkup', 'board', 'thread' optionally 'filehash', 'has_file'.
	 */
	public function recordFloodEntry(array $post): void {
		$this->floodQueries->addFloodEntry(
			$post['ip'],
			\make_comment_hex($post['body_nomarkup'] ?? ''),
			isset($post['has_file']) && $post['has_file'] ? $post['filehash'] : null,
			$post['board'],
			\time(),
			isset($post['thread'])
		);
	}

}
