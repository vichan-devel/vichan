<?php
namespace Vichan\Service;

use Vichan\Data\Model\{FiltersParseResult, SearchFilters};
use Vichan\Data\Driver\Log\LogDriver;
use Vichan\Data\Queries\{UserPostQueries, SearchQueries};


class SearchService {
	private const COMMON_WORDS = [
		'anon', 'thread', 'board', 'post', 'reply', 'image', 'topic', 'bump', 'sage', 'tripcode', 'groyper',
		'mod', 'admin', 'ban', 'rules', 'sticky', 'archive', 'catalog', 'report', 'captcha', 'proxy',  'the',
		'vpn', 'tor', 'doxx', 'spam', 'troll', 'bait', 'flame', 'greentext', 'copypasta', 'meme', 'this',
		'shitpost', 'shitposting', 'edgy', 'kek', 'lulz', 'rekt', 'smug', 'lewd', 'nsfw', 'anonymous', 'glowie',
		'cringe', 'normie', 'boomer', 'zoomer', 'incel', 'chad', 'stacy', 'simp', 'based', 'redpill', 'color',
		'blackpill', 'whitepill', 'bluepill', 'clownworld', 'coomer', 'doomer', 'wojak', 'soyjak', 'pepe',
		'style', 'weight', 'size', 'freedom', 'speech', 'censorship', 'moderation', 'community', 'anonymous',
		'reply', 'search', 'group', 'merge', 'flatten', 'lock', 'unlock', 'hide', 'uyghur', 'soyshit', 'glow',
		'also', 'only', 'just', 'even', 'very', 'than', 'then', 'that', 'this', 'with',
		'from', 'into', 'onto', 'over', 'under', 'about', 'after', 'before', 'since', 'while',
		'because', 'although', 'though', 'unless', 'until', 'where', 'which', 'whose', 'there', 'their',
		'these', 'those', 'being', 'having', 'doing', 'going', 'would', 'could', 'should', 'shall', 'everything',
		'might', 'must', 'will', 'have', 'been', 'were', 'wasn', 'aren', 'isn', 'does', 'isn’t', 'mustn’t',
		'didn', 'hadn', 'hasn', 'don’t', 'can’t', 'won’t', 'cannot', 'haven', 'weren', 'didnt', 'since',
		'mustn', 'mightn', 'shouldn', 'wouldn', 'might’ve', 'would’ve', 'should’ve', 'could’ve', 'must’ve',
		'wasn’t', 'weren’t', 'hasn’t', 'hadn’t', 'won’t', 'wouldn’t', 'shouldn’t', 'couldn’t', 'mightn’t',
		'each', 'such', 'some', 'most', 'many', 'more', 'much', 'less', 'few', 'none', 'although', 'because',
		'both', 'either', 'neither', 'every', 'anyone', 'someone', 'everyone', 'nobody', 'nothing', 'so',
		'above', 'below', 'along', 'across', 'among', 'until', 'and', 'but', 'or', 'nor', 'for', 'yet',
	];

	private const MAX_LENGTH_SUBJECT = 100; // posts.sql
	private const MAX_LENGTH_NAME = 35; // posts.sql

	private LogDriver $log;
	private UserPostQueries $user_queries;
	private SearchQueries $search_queries;
	private ?array $flag_map;
	private float $max_weight;
	private int $max_query_length;
	private int $post_limit;
	private array $searchable_board_uris;


	private static function truncateQuery(string $text, int $byteLimit): ?string {
		if (\strlen($text) <= $byteLimit) {
			return $text;
		}

		// Cut at byte length, trimming incomplete multibyte character at the end.
		$cut = \mb_convert_encoding(\substr($text, 0, $byteLimit), 'UTF-8', 'UTF-8');

		// Try the last space.
		$spacePos = \strrpos($cut, ' ');
		if ($spacePos !== false) {
			return \substr($cut, 0, $spacePos);
		}

		// Fallback to the last word boundary.
		if (\preg_match('/^(.+)\b/u', $cut, $m)) {
			return $m[1];
		}

		// Too long but could not cut.
		return null;
	}

	private static function trim(string $str): string {
		return \trim($str, "* \n\r\t\v\0");
	}

	private static function unescape(string $str): string {
		return \strtr($str, [
			'\\\\' => '\\',
			'\\*' => '*',
			'\\"' => '"'
		]);
	}

	/**
	 * Split the filter into fragments along the wildcards, handling escaping.
	 *
	 * @param string $str The full filter.
	 * @return array<string>
	 */
	private static function split(string $str): array {
		// Split the fragments
		return \preg_split('/(?:\\\\\\\\)*\\\\\*|(?:\\\\\\\\)*\*+/', $str);
	}

	private static function weightByContent(array $fragments): float {
		$w = 0;

		foreach ($fragments as $fragment) {
			$short = \strlen($fragment) < 4;
			if (\in_array($fragment, self::COMMON_WORDS)) {
				$w += $short ? 16 : 6;
			} elseif ($short) {
				$w += 6;
			}
		}

		return $w;
	}

	private static function filterAndWeight(string $filter): array {
		$fragments = self::split($filter);
		$acc = [];
		$total_len = 0;

		foreach ($fragments as $fragment) {
			$fragment = self::trim(self::unescape($fragment));

			if (!empty($fragment)) {
				$total_len += \strlen($fragment);
				$acc[] = $fragment;
			}
		}

		$wildcard_weight = 0;
		if (!empty($acc) && $total_len >= 0) {
			// Interword wildcards
			$interword = \min(\count($fragments) - 1, 0);
			// Wildcards over the total length of the word. Ergo the number of fragments minus 1.
			$perc = $interword / $total_len * 100;
			$wildcard_weight = $perc + \count($fragments) * 2;
		}

		return [ $acc, $total_len, $wildcard_weight ];
	}

	/**
	 * Gets a subset of the given strings which match every filter.
	 *
	 * @param array<string> $fragments User provided fragments to search in the flags.
	 * @param array<string> $strings An array of strings.
	 * @return array<string> An array of strings, subset of $strings.
	 */
	private static function matchStrings(array $strings, array $fragments): array {
		return \array_filter($strings, function ($str) use ($fragments) {
			// Saves the last position. We use this to ensure the fragments are one after the other.
			$last_ret = -1;
			foreach ($fragments as $fragment) {
				if ($last_ret + 1 > \strlen($fragment)) {
					// Cannot possibly match.
					return false;
				}

				$last_ret = \stripos($str, $fragment, $last_ret + 1);
				if ($last_ret === false) {
					// Exclude flags that don't match even a single fragment.
					return false;
				}
			}
			return true;
		});
	}

	/**
	 * Parses a raw search query.
	 *
	 * @param string $raw_query Raw user query. Phrases are searched in the post bodies. The user can specify also
	 *                          additional filters in the <key>:<value> format.
	 *                          Available filters:
	 *                          - board: the board, value can be quoted
	 *                          - subject: post subject, value can be quoted, supports wildcards
	 *                          - name: post name, value can be quoted, supports wildcards
	 *                          - flag: post flag, value can be quoted, supports wildcards
	 *                          - id: post id, must be numeric
	 *                          - thread: thread id, must be numeric
	 *                          The remaining text is split into chunks and searched in the post body.
	 * @return FiltersParseResult
	 */
	public function parse(string $raw_query): FiltersParseResult{
		$tres = self::truncateQuery($raw_query, $this->max_query_length);
		if ($tres === null) {
			throw new \RuntimeException('Could not truncate query');
		}

		$pres = \preg_match_all(
			'/(?:
				\b(board):
					(?:
						"([^"]+)"                                                       # [2] board: "quoted"
						|
						([^\s"]+)                                                       # [3] board: unquoted
					)
				|
				\b(subject|name|flag):
					(?:
						"((?:\\\\\\\\|\\\\\"|\\\\\*|[^"\\\\])*)"                        # [5] quoted with wildcards
						|
						((?:\\\\\\\\|\\\\\*|[^\s\\\\])++)                               # [6] unquoted with wildcards
					)
				|
				\b(id|thread):
					(\d+)                                                               # [8] numeric only
				|
				"((?:\\\\\\\\|\\\\\"|\\\\\*|[^"\\\\])*)"                                # [9] quoted free text
				|
				([^"\s]++)                                                              # [10] unquoted free text block
			)/iux',
			$tres,
			$matches,
			\PREG_SET_ORDER
		);
		if ($pres === false) {
			throw new \RuntimeException('Could not decode the query');
		}

		$filters = new FiltersParseResult();

		foreach ($matches as $m) {
			if (!empty($m[1])) {
				// board (no wildcards).
				$value = \trim(!empty($m[2]) ? $m[2] : $m[3], '/');

				$filters->board = $value;
			} elseif (!empty($m[4])) {
				// subject, name, flag (with wildcards).
				$key = \strtolower($m[4]);
				$value = !empty($m[5]) ? $m[5] : $m[6];

				if ($key === 'name') {
					$filters->name = $value;
				} elseif ($key === 'subject') {
					$filters->subject = $value;
				} else {
					$filters->flag = $value;
				}
			} elseif (!empty($m[7])) {
				$key = \strtolower($m[7]);
				$value = (int)$m[8];

				if ($key === 'id') {
					$filters->id = $value;
				} else {
					$filters->thread = $value;
				}
			} elseif (!empty($m[9]) || !empty($m[10])) {
				$value = !empty($m[9]) ? $m[9] : $m[10];

				$filters->body[] = $value;
			}
		}

		return $filters;
	}

	/**
	 * @param LogDriver $log Log river.
	 * @param UserPostQueries $user_queries User posts queries.
	 * @param SearchQueries $search_queries Search queries for flood detection.
	 * @param ?array $flag_map The key-value map of user flags, or null to disable flag search.
	 * @param float $max_weight The maximum weight of the parsed user query. Body filters that go beyond this limit are discarded.
	 * @param int $max_query_length Maximum length of the raw input query before it's truncated.
	 * @param int $post_limit Maximum number of results.
	 * @param ?array $searchable_board_uris The uris of the board that can be searched. Null to search all the boards.
	 */
	public function __construct(
		LogDriver $log,
		UserPostQueries $user_queries,
		SearchQueries $search_queries,
		?array $flag_map,
		float $max_weight,
		int $max_query_length,
		int $post_limit,
		?array $searchable_board_uris
	) {
		$this->log = $log;
		$this->user_queries = $user_queries;
		$this->search_queries = $search_queries;
		$this->flag_map = $flag_map;
		$this->max_weight = $max_weight;
		$this->max_query_length = $max_query_length;
		$this->post_limit = $post_limit;
		$this->searchable_board_uris = $searchable_board_uris ?? listBoards(true);
	}

	/**
	 * Reduces the user provided filters and assigns them a total weight.
	 *
	 * @param FiltersParseResult $filters The filters to sanitize, reduce and weight.
	 * @return SearchFilters
	 */
	public function reduceAndWeight(FiltersParseResult $filters): SearchFilters {
		$weighted = new SearchFilters();

		if ($filters->subject !== null) {
			list($fragments, $total_len, $wildcard_weight) = self::filterAndWeight($filters->subject);

			if (!empty($fragments) && $total_len >= 0) {
				if ($total_len <= self::MAX_LENGTH_SUBJECT) {
					$weighted->subject = $fragments;
					$weighted->weight += $wildcard_weight;
				}
			}
		}
		if ($filters->name !== null) {
			list($fragments, $total_len, $wildcard_weight) = self::filterAndWeight($filters->name);

			if (!empty($fragments) && $total_len >= 0) {
				if ($total_len <= self::MAX_LENGTH_NAME) {
					$weighted->name = $fragments;
					$weighted->weight += $wildcard_weight;
				}
			}
		}
		// No wildcard support, and obligatory anyway so it weights 0.
		$weighted->board = $filters->board;
		if ($filters->flag !== null) {
			$weighted->flag = [];

			if (!empty($this->flag_map)) {
				$max_flag_length = \array_reduce($this->flag_map, fn($max, $str) => \max($max, \strlen($str)), 0);

				list($fragments, $total_len, $wildcard_weight) = self::filterAndWeight($filters->flag);

				if (!empty($fragments) && $total_len >= 0) {
					// Add 2 to account for possible wildcards on the ends.
					if ($total_len <= $max_flag_length + 2) {
						$weighted->flag = $fragments;
						$weighted->weight += $wildcard_weight;
					}
				}
			}
		}
		$weighted->id = $filters->id;
		$weighted->thread = $filters->thread;
		if (!empty($filters->body)) {
			foreach ($filters->body as $keyword) {
				list($fragments, $total_len, $wildcard_weight) = self::filterAndWeight($keyword);

				if (!empty($fragments) && $total_len >= 0) {
					$content_weight = self::weightByContent($fragments);
					$str_weight = $content_weight + $wildcard_weight;

					if ($str_weight + $weighted->weight <= $this->max_weight) {
						$weighted->weight += $str_weight;
						$weighted->body[] = $fragments;
					}
				}
			}
		}

		return $weighted;
	}

	/**
	 * Run a search on user posts with the given filters.
	 *
	 * @param SearchFilters $filters An array of filters made by {@see self::parse()}.
	 * @param ?string $fallback_board Fallback board if there isn't a board filter.
	 * @return ?array Data array straight from the PDO, with all the fields in posts.sql, or null if the query was too broad.
	 */
	public function search(string $ip, string $raw_query, SearchFilters $filters, ?string $fallback_board): ?array {
		$board = !empty($filters->board) ? $filters->board : $fallback_board;
		if ($board === null) {
			return [];
		}

		// Only board is specified.
		if (empty($filters->subject) &&
			empty($filters->name) &&
			empty($filters->flag) &&
			$filters->id === null &&
			$filters->thread === null &&
			empty($filters->body)
		) {
			return null;
		}

		if (!\in_array($board, $this->searchable_board_uris)) {
			return [];
		}

		$weight_perc = ($filters->weight / $this->max_weight) * 100;
		if ($weight_perc > 85) {
			/// Over 85 of the weight.
			$this->log->log(LogDriver::NOTICE, "$ip search: weight {$weight_perc}% ({$filters->weight}) query '$raw_query'");
		} else {
			$this->log->log(LogDriver::INFO, "$ip search: weight {$weight_perc}% ({$filters->weight}) query '$raw_query'");
		}

		$flags = [];
		if (!empty($filters->flag) && !empty($this->flag_map)) {
			// A double array_values is necessary in order to re-index the array, otherwise it's left with random indexes.
			$reverse_flags = \array_values($this->flag_map);
			$flags = \array_values($this->matchStrings($reverse_flags, $filters->flag));
			if (empty($flags)) {
				// The query doesn't match any flags so it will always fail anyway.
				return [];
			}
		}

		return $this->user_queries->searchPosts(
			$board,
			$filters->subject,
			$filters->name,
			$flags,
			$filters->id,
			$filters->thread,
			$filters->body,
			$this->post_limit
		);
	}

	/**
	 * Check if the IP-query pair passes the limit.
	 *
	 * @param string $ip Source IP.
	 * @param string $phrase The search query.
	 * @return bool True if the request goes over the limit.
	 */
	public function checkFlood(string $ip, string $raw_query) {
		return $this->search_queries->checkFlood($ip, $raw_query);
	}

	/**
	 * Returns the uris of the boards that may be searched.
	 */
	public function getSearchableBoards(): array {
		return $this->searchable_board_uris;
	}

	/**
	 * @return bool True if the flag filter is enabled.
	 */
	public function isFlagFilterEnabled(): bool {
		return !empty($this->flag_map);
	}
}
