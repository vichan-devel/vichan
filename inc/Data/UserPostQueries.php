<?php
namespace Vichan\Data;

use Vichan\Functions\Net;


/**
 * Browse user posts
 */
class UserPostQueries {
	private const CURSOR_TYPE_PREV = 'p';
	private const CURSOR_TYPE_NEXT = 'n';

	private \PDO $pdo;

	public function __construct(\PDO $pdo) {
		$this->pdo = $pdo;
	}

	private function paginate(array $board_uris, int $page_size, ?string $cursor, callable $callback): PageFetchResult {
		// Decode the cursor.
		if ($cursor !== null) {
			list($cursor_type, $uri_id_cursor_map) = Net\decode_cursor($cursor);
		} else {
			// Defaults if $cursor is an invalid string.
			$cursor_type = null;
			$uri_id_cursor_map = [];
		}
		$next_cursor_map = [];
		$prev_cursor_map = [];
		$rows = [];

		foreach ($board_uris as $uri) {
			// Extract the cursor relative to the board.
			$start_id = null;
			if ($cursor_type !== null && isset($uri_id_cursor_map[$uri])) {
				$value = $uri_id_cursor_map[$uri];
				if (\is_numeric($value)) {
					$start_id = (int)$value;
				}
			}

			$posts = $callback($uri, $cursor_type, $start_id, $page_size);

			$posts_count = \count($posts);

			// By fetching one extra post bellow and/or above the limit, we know if there are any posts beside the current page.
			if ($posts_count === $page_size + 2) {
				$has_extra_prev_post = true;
				$has_extra_end_post = true;
			} else {
				/*
				 * If the id we start fetching from is also the first id fetched from the DB, then we exclude it from
				 * the results, noting that we fetched 1 more posts than we needed, and it was before the current page.
				 * Hence, we have no extra post at the end and no next page.
				*/
				$has_extra_prev_post = $start_id !== null && $start_id === (int)$posts[0]['id'];
				$has_extra_end_post = !$has_extra_prev_post && $posts_count > $page_size;
			}

			// Get the previous cursor, if any.
			if ($has_extra_prev_post) {
				\array_shift($posts);
				$posts_count--;
				// Select the most recent post.
				$prev_cursor_map[$uri] = $posts[0]['id'];
			}
			// Get the next cursor, if any.
			if ($has_extra_end_post) {
				\array_pop($posts);
				// Select the oldest post.
				$next_cursor_map[$uri] = $posts[$posts_count - 2]['id'];
			}

			$rows[$uri] = $posts;
		}

		$res = new PageFetchResult();
		$res->by_uri = $rows;
		$res->cursor_prev = !empty($prev_cursor_map) ? Net\encode_cursor(self::CURSOR_TYPE_PREV, $prev_cursor_map) : null;
		$res->cursor_next = !empty($next_cursor_map) ? Net\encode_cursor(self::CURSOR_TYPE_NEXT, $next_cursor_map) : null;

		return $res;
	}

	/**
	 * Fetch a page of user posts.
	 *
	 * @param array $board_uris The uris of the boards that should be included.
	 * @param string $ip The IP of the target user.
	 * @param integer $page_size The Number of posts that should be fetched.
	 * @param string|null $cursor The directional cursor to fetch the next or previous page. Null to start from the beginning.
	 * @return PageFetchResult
	 */
	public function fetchPaginatedByIp(array $board_uris, string $ip, int $page_size, ?string $cursor = null): PageFetchResult {
		return $this->paginate($board_uris, $page_size, $cursor, function($uri, $cursor_type, $start_id, $page_size) use ($ip) {
			if ($cursor_type === null) {
				$query = $this->pdo->prepare(sprintf('SELECT * FROM `posts_%s` WHERE `ip` = :ip ORDER BY `sticky` DESC, `id` DESC LIMIT :limit', $uri));
				$query->bindValue(':ip', $ip);
				$query->bindValue(':limit', $page_size + 1, \PDO::PARAM_INT); // Always fetch more.
				$query->execute();
				return $query->fetchAll(\PDO::FETCH_ASSOC);
			} elseif ($cursor_type === self::CURSOR_TYPE_NEXT) {
				$query = $this->pdo->prepare(sprintf('SELECT * FROM `posts_%s` WHERE `ip` = :ip AND `id` <= :start_id ORDER BY `sticky` DESC, `id` DESC LIMIT :limit', $uri));
				$query->bindValue(':ip', $ip);
				$query->bindValue(':start_id', $start_id, \PDO::PARAM_INT);
				$query->bindValue(':limit', $page_size + 2, \PDO::PARAM_INT); // Always fetch more.
				$query->execute();
				return $query->fetchAll(\PDO::FETCH_ASSOC);
			} elseif ($cursor_type === self::CURSOR_TYPE_PREV) {
				$query = $this->pdo->prepare(sprintf('SELECT * FROM `posts_%s` WHERE `ip` = :ip AND `id` >= :start_id ORDER BY `sticky` ASC, `id` ASC LIMIT :limit', $uri));
				$query->bindValue(':ip', $ip);
				$query->bindValue(':start_id', $start_id, \PDO::PARAM_INT);
				$query->bindValue(':limit', $page_size + 2, \PDO::PARAM_INT); // Always fetch more.
				$query->execute();
				return \array_reverse($query->fetchAll(\PDO::FETCH_ASSOC));
			} else {
				throw new \RuntimeException("Unknown cursor type '$cursor_type'");
			}
		});
	}

	/**
	 * Fetch a page of user posts.
	 *
	 * @param array $board_uris The uris of the boards that should be included.
	 * @param string $password The password of the target user.
	 * @param integer $page_size The Number of posts that should be fetched.
	 * @param string|null $cursor The directional cursor to fetch the next or previous page. Null to start from the beginning.
	 * @return PageFetchResult
	 */
	public function fetchPaginateByPassword(array $board_uris, string $password, int $page_size, ?string $cursor = null): PageFetchResult {
		return $this->paginate($board_uris, $page_size, $cursor, function($uri, $cursor_type, $start_id, $page_size) use ($password) {
			if ($cursor_type === null) {
				$query = $this->pdo->prepare(sprintf('SELECT * FROM `posts_%s` WHERE `password` = :password ORDER BY `sticky` DESC, `id` DESC LIMIT :limit', $uri));
				$query->bindValue(':password', $password);
				$query->bindValue(':limit', $page_size + 1, \PDO::PARAM_INT); // Always fetch more.
				$query->execute();
				return $query->fetchAll(\PDO::FETCH_ASSOC);
			} elseif ($cursor_type === self::CURSOR_TYPE_NEXT) {
				$query = $this->pdo->prepare(sprintf('SELECT * FROM `posts_%s` WHERE `password` = :password AND `id` <= :start_id ORDER BY `sticky` DESC, `id` DESC LIMIT :limit', $uri));
				$query->bindValue(':password', $password);
				$query->bindValue(':start_id', $start_id, \PDO::PARAM_INT);
				$query->bindValue(':limit', $page_size + 2, \PDO::PARAM_INT); // Always fetch more.
				$query->execute();
				return $query->fetchAll(\PDO::FETCH_ASSOC);
			} elseif ($cursor_type === self::CURSOR_TYPE_PREV) {
				$query = $this->pdo->prepare(sprintf('SELECT * FROM `posts_%s` WHERE `password` = :password AND `id` >= :start_id ORDER BY `sticky` ASC, `id` ASC LIMIT :limit', $uri));
				$query->bindValue(':password', $password);
				$query->bindValue(':start_id', $start_id, \PDO::PARAM_INT);
				$query->bindValue(':limit', $page_size + 2, \PDO::PARAM_INT); // Always fetch more.
				$query->execute();
				return \array_reverse($query->fetchAll(\PDO::FETCH_ASSOC));
			} else {
				throw new \RuntimeException("Unknown cursor type '$cursor_type'");
			}
		});
	}
}
