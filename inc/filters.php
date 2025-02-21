<?php

/*
 *  Copyright (c) 2010-2013 Tinyboard Development Group
 */

use Vichan\Context;
use Vichan\Data\IpNoteQueries;

defined('TINYBOARD') or exit;

class Filter {
	public $flood_check;
	private $condition;
	private $post;


	public function __construct(array $arr) {
		foreach ($arr as $key => $value) {
			$this->$key = $value;
		}
	}

	public function match($condition, $match) {
		$condition = strtolower($condition);
		$post = &$this->post;

		switch($condition) {
			case 'custom':
				if (!is_callable($match)) {
					error('Custom condition for filter is not callable!');
				}
				return $match($post);
			case 'flood-match':
				if (!is_array($match)) {
					error('Filter condition "flood-match" must be an array.');
				}

				// Filter out "flood" table entries which do not match this filter.

				$flood_check_matched = array();

				foreach ($this->flood_check as $flood_post) {
					foreach ($match as $flood_match_arg) {
						switch ($flood_match_arg) {
							case 'ip':
								if ($flood_post['ip'] != $_SERVER['REMOTE_ADDR']) {
									continue 3;
								}
								break;
							case 'body':
								if ($flood_post['posthash'] != make_comment_hex($post['body_nomarkup'])) {
									continue 3;
								}
								break;
							case 'file':
								if (!isset($post['filehash'])) {
									return false;
								}
								if ($flood_post['filehash'] != $post['filehash']) {
									continue 3;
								}
								break;
							case 'board':
								if ($flood_post['board'] != $post['board']) {
									continue 3;
								}
								break;
							case 'isreply':
								if ($flood_post['isreply'] == $post['op']) {
									continue 3;
								}
								break;
							default:
								error('Invalid filter flood condition: ' . $flood_match_arg);
						}
					}
					$flood_check_matched[] = $flood_post;
				}

				$this->flood_check = $flood_check_matched;
				return !empty($this->flood_check);
			case 'flood-time':
				foreach ($this->flood_check as $flood_post) {
					if (time() - $flood_post['time'] <= $match) {
						return true;
					}
				}
				return false;
			case 'flood-count':
				$count = 0;
				foreach ($this->flood_check as $flood_post) {
					if (time() - $flood_post['time'] <= $this->condition['flood-time']) {
						++$count;
					}
				}
				return $count >= $match;
			case 'name':
				return preg_match($match, $post['name']);
			case 'trip':
				return $match === $post['trip'];
			case 'email':
				return preg_match($match, $post['email']);
			case 'subject':
				return preg_match($match, $post['subject']);
			case 'body':
				return preg_match($match, $post['body_nomarkup']);
			case 'filehash':
				return $match === $post['filehash'];
			case 'filename':
				if (!$post['files']) {
					return false;
				}

				foreach ($post['files'] as $file) {
					if (preg_match($match, $file['filename'])) {
						return true;
					}
				}
				return false;
			case 'extension':
				if (!$post['files']) {
					return false;
				}

				foreach ($post['files'] as $file) {
					if (preg_match($match, $file['extension'])) {
						return true;
					}
				}
				return false;
			case 'ip':
				return preg_match($match, $_SERVER['REMOTE_ADDR']);
			case 'op':
				return $post['op'] == $match;
			case 'has_file':
				return $post['has_file'] == $match;
			case 'board':
				return $post['board'] == $match;
			case 'password':
				return $post['password'] == $match;
			case 'unshorten':
				$extracted_urls = get_urls($post['body_nomarkup']);
				foreach ($extracted_urls as $url) {
					if (preg_match($match, trace_url($url))) {
						return true;
					}
				}
				return false;
			default:
				error('Unknown filter condition: ' . $condition);
		}
	}

	public function action(Context $ctx) {
		global $board;

		$this->add_note = isset($this->add_note) ? $this->add_note : false;
		if ($this->add_note) {
			$note_queries = $ctx->get(IpNoteQueries::class);
			$note_queries->add($_SERVER['REMOTE_ADDR'], -1, 'Autoban message: ' . $this->post['body']);
		}
		if (isset($this->action)) {
			switch($this->action) {
				case 'reject':
					error(isset($this->message) ? $this->message : 'Posting throttled by filter.');
				case 'ban':
					if (!isset($this->reason)) {
						error('The ban action requires a reason.');
					}

					$this->expires = isset($this->expires) ? $this->expires : false;
					$this->reject = isset($this->reject) ? $this->reject : true;
					$this->all_boards = isset($this->all_boards) ? $this->all_boards : false;

					Bans::new_ban($_SERVER['REMOTE_ADDR'], $this->reason, $this->expires, $this->all_boards ? false : $board['uri'], -1);

					if ($this->reject) {
						if (isset($this->message)) {
							error($message);
						}

						checkBan($board['uri']);
						exit;
					}

					break;
				default:
					error('Unknown filter action: ' . $this->action);
			}
		}
	}

	public function check(array $post) {
		$this->post = $post;
		foreach ($this->condition as $condition => $value) {
			if ($condition[0] == '!') {
				$NOT = true;
				$condition = substr($condition, 1);
			} else {
				$NOT = false;
			}

			if ($this->match($condition, $value) == $NOT) {
				return false;
			}
		}
		return true;
	}
}

function purge_flood_table() {
	global $config;

	// Determine how long we need to keep a cache of posts for flood prevention. Unfortunately, it is not
	// aware of flood filters in other board configurations. You can solve this problem by settings the
	// config variable $config['flood_cache'] (seconds).

	if ($config['flood_cache'] != -1) {
		$max_time = &$config['flood_cache'];
	} else {
		$max_time = 0;
		foreach ($config['filters'] as $filter) {
			if (isset($filter['condition']['flood-time'])) {
				$max_time = max($max_time, $filter['condition']['flood-time']);
			}
		}
	}

	$time = time() - $max_time;

	query("DELETE FROM ``flood`` WHERE `time` < $time") or error(db_error());
}

function do_filters(Context $ctx, array $post) {
	global $config;

	if (!isset($config['filters']) || empty($config['filters'])) {
		return;
	}

	foreach ($config['filters'] as $filter) {
		if (isset($filter['condition']['flood-match'])) {
			$has_flood = true;
			break;
		}
	}

	if (isset($has_flood)) {
		if ($post['has_file']) {
			$query = prepare("SELECT * FROM ``flood`` WHERE `ip` = :ip OR `posthash` = :posthash OR `filehash` = :filehash");
			$query->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
			$query->bindValue(':posthash', make_comment_hex($post['body_nomarkup']));
			$query->bindValue(':filehash', $post['filehash']);
		} else {
			$query = prepare("SELECT * FROM ``flood`` WHERE `ip` = :ip OR `posthash` = :posthash");
			$query->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
			$query->bindValue(':posthash', make_comment_hex($post['body_nomarkup']));
		}
		$query->execute() or error(db_error($query));
		$flood_check = $query->fetchAll(PDO::FETCH_ASSOC);
	} else {
		$flood_check = false;
	}

	foreach ($config['filters'] as $filter_array) {
		$filter = new Filter($filter_array);
		$filter->flood_check = $flood_check;
		if ($filter->check($post)) {
			$filter->action($ctx);
		}
	}

	purge_flood_table();
}
