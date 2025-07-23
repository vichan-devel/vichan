<?php
namespace Vichan\Data\Queries;

use Vichan\Data\PageFetchResult;
use Vichan\Functions\Net;


/**
 * Browse user posts
 */
class UserPostQueries {
	private const CURSOR_TYPE_PREV = 'p';
	private const CURSOR_TYPE_NEXT = 'n';

	private \PDO $pdo;


	/**
	 * Escapes wildcards from LIKE operators using the default escape character.
	 */
	private static function escapeLike(string $str): string {
		// Escape any existing escape characters.
		$str = \str_replace('\\', '\\\\', $str);
		// Escape wildcard characters.
		$str = \str_replace('%', '\\%', $str);
		$str = \str_replace('_', '\\_', $str);
		return $str;
	}

	/**
	 * Joins the fragments of filter into a list of bindable parameters for the CONCAT sql function.
	 * Given prefix = cat and fragments_count = 3, we get  [ "'%'", ":cat0%", "'%', ":cat1", "'%'" ":cat2%", "'%'" ];
	 *
	 * @param string $prefix The prefix for the parameter binding
	 * @param int $fragments_count MUST BE >= 1.
	 * @return array
	 */
	private static function arrayOfFragments(string $prefix, int $fragments_count): array {
		$args = [ "'%'" ];
		for ($i = 0; $i < $fragments_count; $i++) {
			$args[] = ":$prefix$i";
			$args[] = "'%'";
		}
		return $args;
	}

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

	/**
	 * Search among the user posts with the given filters.
	 * The subject, name and elements of the bodies filters are fragments which are joined together with wildcards, to
	 * allow for more flexible filtering.
	 *
	 * @param string $board The board where to search in.
	 * @param array<string> $subject Fragments of the subject filter.
	 * @param array<string> $name Fragments of the name filter.
	 * @param array<string> $flags An array of the flag names to search among the HTML.
	 * @param ?int $id Post id filter.
	 * @param ?int $thread Thread id filter.
	 * @param array<array<string>> $bodies An array whose element are arrays containing the fragments of multiple body filters, each
	 *                                     searched independently from the others
	 * @param integer $limit The maximum number of results.
	 * @throws PDOException On error.
	 * @return array<array>
	 */
	public function searchPosts(string $board, array $subject, array $name, array $flags, ?int $id, ?int $thread, array $bodies, int $limit): array {
		$where_acc = [];

		if (!empty($subject)) {
			$like_arg = self::arrayOfFragments('subj', \count($subject));
			$where_acc[] = 'subject LIKE CONCAT(' . \implode(', ', $like_arg) . ')';
		}
		if (!empty($name)) {
			$like_arg = self::arrayOfFragments('name', \count($name));
			$where_acc[] = 'name LIKE CONCAT(' . \implode(', ', $like_arg) . ')';
		}
		if (!empty($flags)) {
			$flag_acc = [];
			for ($i = 0; $i < \count($flags); $i++) {
				// Yes, vichan stores the flag inside the generated HTML. Now you know why it's slow as shit.
				// English lacks the words to express my feelings about it in a satisfying manner.
				$flag_acc[] = "CONCAT('%<tinyboard flag alt>', :flag$i, '</tinyboard>%')";
			}
			$where_acc[] = 'body_nomarkup LIKE (' . \implode(' OR ', $flag_acc) . ')';
		}
		if ($id !== null) {
			$where_acc[] = 'id = :id';
		}
		if ($thread !== null) {
			$where_acc[] = 'thread = :thread';
		}
		for ($i = 0; $i < \count($bodies); $i++) {
			$body = $bodies[$i];
			$like_arg = self::arrayOfFragments("body_{$i}_", \count($body));
			$where_acc[] = 'body_nomarkup LIKE CONCAT(' . \implode(', ', $like_arg) . ')';
		}

		if (empty($where_acc)) {
			return [];
		}

		$sql = "SELECT * FROM `posts_$board` WHERE " . \implode(' AND ', $where_acc) . ' ORDER BY `time` DESC LIMIT :limit';
		$query = $this->pdo->prepare($sql);

		for ($i = 0; $i < \count($subject); $i++) {
			$query->bindValue(":subj$i", self::escapeLike($subject[$i]));
		}
		for ($i = 0; $i < \count($name); $i++) {
			$query->bindValue(":name$i", self::escapeLike($name[$i]));
		}
		for ($i = 0; $i < \count($flags); $i++) {
			$query->bindValue(":flag$i", self::escapeLike($flags[$i]));
		}
		if ($id !== null) {
			$query->bindValue(':id', $id, \PDO::PARAM_INT);
		}
		if ($thread !== null) {
			$query->bindValue(':thread', $thread, \PDO::PARAM_INT);
		}
		for ($body_i = 0; $body_i < \count($bodies); $body_i++) {
			$body = $bodies[$body_i];

			for ($i = 0; $i < \count($body); $i++) {
				$query->bindValue(":body_{$body_i}_{$i}", self::escapeLike($body[$i]));
			}
		}

		$query->bindValue(':limit', $limit, \PDO::PARAM_INT);

		$query->execute();
		return $query->fetchAll(\PDO::FETCH_ASSOC);
	}
}
