<?php

/*
 *  Copyright (c) 2010-2013 Tinyboard Development Group
 */

defined('TINYBOARD') or exit;

function mod_page($title, $template, $args, $subtitle = false) {
	global $config, $mod;

	echo Element($config['file_page_template'], array(
		'config' => $config,
		'mod' => $mod,
		'hide_dashboard_link' => $template == $config['file_mod_dashboard'],
		'title' => $title,
		'subtitle' => $subtitle,
		'boardlist' => createBoardlist($mod),
		'body' => Element($template,
				array_merge(
					array('config' => $config, 'mod' => $mod),
					$args
				)
			)
		)
	);
}

function mod_login($redirect = false) {
	global $config;

	$args = array();

	if (isset($_POST['login'])) {
		// Check if inputs are set and not empty
		if (!isset($_POST['username'], $_POST['password']) || $_POST['username'] == '' || $_POST['password'] == '') {
			$args['error'] = $config['error']['invalid'];
		} elseif (!login($_POST['username'], $_POST['password'])) {
			if ($config['syslog'])
				_syslog(LOG_WARNING, 'Unauthorized login attempt!');

			$args['error'] = $config['error']['invalid'];
		} else {
			modLog('Logged in');

			// Login successful
			// Set cookies
			setCookies();

			if ($redirect)
				header('Location: ?' . $redirect, true, $config['redirect_http']);
			else
				header('Location: ?/', true, $config['redirect_http']);
		}
	}

	if (isset($_POST['username']))
		$args['username'] = $_POST['username'];

	mod_page(_('Login'), $config['file_mod_login'], $args);
}

function mod_confirm($request) {
	global $config;
	mod_page(_('Confirm action'), $config['file_mod_confim'], array('request' => $request, 'token' => make_secure_link_token($request)));
}

function mod_logout() {
	global $config;
	destroyCookies();

	header('Location: ?/', true, $config['redirect_http']);
}

function mod_dashboard() {
	global $config, $mod;

	$args = array();

	$args['boards'] = listBoards();

	if (hasPermission($config['mod']['noticeboard'])) {
		if (!$config['cache']['enabled'] || !$args['noticeboard'] = cache::get('noticeboard_preview')) {
			$query = prepare("SELECT ``noticeboard``.*, `username` FROM ``noticeboard`` LEFT JOIN ``mods`` ON ``mods``.`id` = `mod` ORDER BY `id` DESC LIMIT :limit");
			$query->bindValue(':limit', $config['mod']['noticeboard_dashboard'], PDO::PARAM_INT);
			$query->execute() or error(db_error($query));
			$args['noticeboard'] = $query->fetchAll(PDO::FETCH_ASSOC);

			if ($config['cache']['enabled'])
				cache::set('noticeboard_preview', $args['noticeboard']);
		}
	}

	if (!$config['cache']['enabled'] || ($args['unread_pms'] = cache::get('pm_unreadcount_' . $mod['id'])) === false) {
		$query = prepare('SELECT COUNT(*) FROM ``pms`` WHERE `to` = :id AND `unread` = 1');
		$query->bindValue(':id', $mod['id']);
		$query->execute() or error(db_error($query));
		$args['unread_pms'] = $query->fetchColumn();

		if ($config['cache']['enabled'])
			cache::set('pm_unreadcount_' . $mod['id'], $args['unread_pms']);
	}

	$query = query('SELECT COUNT(*) FROM ``reports``') or error(db_error($query));
	$args['reports'] = $query->fetchColumn();

	$query = query('SELECT COUNT(*) FROM ``ban_appeals``') or error(db_error($query));
	$args['appeals'] = $query->fetchColumn();

	if ($mod['type'] >= ADMIN && $config['check_updates']) {
		if (!$config['version'])
			error(_('Could not find current version! (Check .installed)'));

		if (isset($_COOKIE['update'])) {
			$latest = unserialize($_COOKIE['update']);
		} else {
			$ctx = stream_context_create(array('http' => array('timeout' => 5)));
			if ($code = @file_get_contents('http://engine.vichan.info/version.txt', 0, $ctx)) {
				$ver = strtok($code, "\n");

				if (preg_match('@^// v(\d+)\.(\d+)\.(\d+)\s*?$@', $ver, $matches)) {
					$latest = array(
						'massive' => $matches[1],
						'major' => $matches[2],
						'minor' => $matches[3]
					);
					if (preg_match('/(\d+)\.(\d)\.(\d+)(-dev.+)?$/', $config['version'], $matches)) {
						$current = array(
							'massive' => (int) $matches[1],
							'major' => (int) $matches[2],
							'minor' => (int) $matches[3]
						);
						if (isset($m[4])) {
							// Development versions are always ahead in the versioning numbers
							$current['minor'] --;
						}
						// Check if it's newer
						if (!(	$latest['massive'] > $current['massive'] ||
							$latest['major'] > $current['major'] ||
								($latest['massive'] == $current['massive'] &&
									$latest['major'] == $current['major'] &&
									$latest['minor'] > $current['minor']
								)))
							$latest = false;
					} else {
						$latest = false;
					}
				} else {
					// Couldn't get latest version
					$latest = false;
				}
			} else {
				// Couldn't get latest version
				$latest = false;
			}

			setcookie('update', serialize($latest), time() + $config['check_updates_time'], $config['cookies']['jail'] ? $config['cookies']['path'] : '/', null, !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off', true);
		}

		if ($latest)
			$args['newer_release'] = $latest;
	}

	$args['logout_token'] = make_secure_link_token('logout');

	mod_page(_('Dashboard'), $config['file_mod_dashboard'], $args);
}

function mod_search_redirect() {
	global $config;

	if (!hasPermission($config['mod']['search']))
		error($config['error']['noaccess']);

	if (isset($_POST['query'], $_POST['type']) && in_array($_POST['type'], array('posts', 'IP_notes', 'bans', 'log'))) {
		$query = $_POST['query'];
		$query = urlencode($query);
		$query = str_replace('_', '%5F', $query);
		$query = str_replace('+', '_', $query);

		if ($query === '') {
			header('Location: ?/', true, $config['redirect_http']);
			return;
		}

		header('Location: ?/search/' . $_POST['type'] . '/' . $query, true, $config['redirect_http']);
	} else {
		header('Location: ?/', true, $config['redirect_http']);
	}
}

function mod_search($type, $search_query_escaped, $page_no = 1) {
	global $pdo, $config;

	if (!hasPermission($config['mod']['search']))
		error($config['error']['noaccess']);

	// Unescape query
	$query = str_replace('_', ' ', $search_query_escaped);
	$query = urldecode($query);
	$search_query = $query;

	// Form a series of LIKE clauses for the query.
	// This gets a little complicated.

	// Escape "escape" character
	$query = str_replace('!', '!!', $query);

	// Escape SQL wildcard
	$query = str_replace('%', '!%', $query);

	// Use asterisk as wildcard instead
	$query = str_replace('*', '%', $query);

	$query = str_replace('`', '!`', $query);

	// Array of phrases to match
	$match = array();

	// Exact phrases ("like this")
	if (preg_match_all('/"(.+?)"/', $query, $exact_phrases)) {
		$exact_phrases = $exact_phrases[1];
		foreach ($exact_phrases as $phrase) {
			$query = str_replace("\"{$phrase}\"", '', $query);
			$match[] = $pdo->quote($phrase);
		}
	}

	// Non-exact phrases (ie. plain keywords)
	$keywords = explode(' ', $query);
	foreach ($keywords as $word) {
		if (empty($word))
			continue;
		$match[] = $pdo->quote($word);
	}

	// Which `field` to search?
	if ($type == 'posts')
		$sql_field = array('body_nomarkup', 'files', 'subject', 'filehash', 'ip', 'name', 'trip');
	if ($type == 'IP_notes')
		$sql_field = 'body';
	if ($type == 'bans')
		$sql_field = 'reason';
	if ($type == 'log')
		$sql_field = 'text';

	// Build the "LIKE 'this' AND LIKE 'that'" etc. part of the SQL query
	$sql_like = '';
	foreach ($match as $phrase) {
		if (!empty($sql_like))
			$sql_like .= ' AND ';
		$phrase = preg_replace('/^\'(.+)\'$/', '\'%$1%\'', $phrase);
		if (is_array($sql_field)) {
			foreach ($sql_field as $field) {
				$sql_like .= '`' . $field . '` LIKE ' . $phrase . ' ESCAPE \'!\' OR';
			}
			$sql_like = preg_replace('/ OR$/', '', $sql_like);
		} else {
			$sql_like .= '`' . $sql_field . '` LIKE ' . $phrase . ' ESCAPE \'!\'';
		}
	}

	// Compile SQL query

	if ($type == 'posts') {
		$query = '';
		$boards = listBoards();
		if (empty($boards))
			error(_('There are no boards to search!'));

		foreach ($boards as $board) {
			openBoard($board['uri']);
			if (!hasPermission($config['mod']['search_posts'], $board['uri']))
				continue;

			if (!empty($query))
				$query .= ' UNION ALL ';
			$query .= sprintf("SELECT *, '%s' AS `board` FROM ``posts_%s`` WHERE %s", $board['uri'], $board['uri'], $sql_like);
		}

		// You weren't allowed to search any boards
		if (empty($query))
				error($config['error']['noaccess']);

		$query .= ' ORDER BY `sticky` DESC, `id` DESC';
	}

	if ($type == 'IP_notes') {
		$query = 'SELECT * FROM ``ip_notes`` LEFT JOIN ``mods`` ON `mod` = ``mods``.`id` WHERE ' . $sql_like . ' ORDER BY `time` DESC';
		$sql_table = 'ip_notes';
		if (!hasPermission($config['mod']['view_notes']) || !hasPermission($config['mod']['show_ip']))
			error($config['error']['noaccess']);
	}

	if ($type == 'bans') {
		$query = 'SELECT ``bans``.*, `username` FROM ``bans`` LEFT JOIN ``mods`` ON `creator` = ``mods``.`id` WHERE ' . $sql_like . ' ORDER BY (`expires` IS NOT NULL AND `expires` < UNIX_TIMESTAMP()), `created` DESC';
		$sql_table = 'bans';
		if (!hasPermission($config['mod']['view_banlist']))
			error($config['error']['noaccess']);
	}

	if ($type == 'log') {
		$query = 'SELECT `username`, `mod`, `ip`, `board`, `time`, `text` FROM ``modlogs`` LEFT JOIN ``mods`` ON `mod` = ``mods``.`id` WHERE ' . $sql_like . ' ORDER BY `time` DESC';
		$sql_table = 'modlogs';
		if (!hasPermission($config['mod']['modlog']))
			error($config['error']['noaccess']);
	}

	// Execute SQL query (with pages)
	$q = query($query . ' LIMIT ' . (($page_no - 1) * $config['mod']['search_page']) . ', ' . $config['mod']['search_page']) or error(db_error());
	$results = $q->fetchAll(PDO::FETCH_ASSOC);

	// Get total result count
	if ($type == 'posts') {
		$q = query("SELECT COUNT(*) FROM ($query) AS `tmp_table`") or error(db_error());
		$result_count = $q->fetchColumn();
	} else {
		$q = query('SELECT COUNT(*) FROM `' . $sql_table . '` WHERE ' . $sql_like) or error(db_error());
		$result_count = $q->fetchColumn();
	}

	if ($type == 'bans') {
		foreach ($results as &$ban) {
			$ban['mask'] = Bans::range_to_string(array($ban['ipstart'], $ban['ipend']));
			if (filter_var($ban['mask'], FILTER_VALIDATE_IP) !== false)
				$ban['single_addr'] = true;
		}
	}

	if ($type == 'posts') {
		foreach ($results as &$post) {
			$post['snippet'] = pm_snippet($post['body']);
		}
	}

	// $results now contains the search results

	mod_page(_('Search results'), $config['file_mod_search_results'], array(
		'search_type' => $type,
		'search_query' => $search_query,
		'search_query_escaped' => $search_query_escaped,
		'result_count' => $result_count,
		'results' => $results
	));
}

function mod_edit_board($boardName) {
	global $board, $config;

	if (!openBoard($boardName))
		error($config['error']['noboard']);

	if (!hasPermission($config['mod']['manageboards'], $board['uri']))
			error($config['error']['noaccess']);

	if (isset($_POST['title'], $_POST['subtitle'])) {
		if (isset($_POST['delete'])) {
			if (!hasPermission($config['mod']['manageboards'], $board['uri']))
				error($config['error']['deleteboard']);

			$query = prepare('DELETE FROM ``boards`` WHERE `uri` = :uri');
			$query->bindValue(':uri', $board['uri']);
			$query->execute() or error(db_error($query));

			if ($config['cache']['enabled']) {
				cache::delete('board_' . $board['uri']);
				cache::delete('all_boards');
			}

			modLog('Deleted board: ' . sprintf($config['board_abbreviation'], $board['uri']), false);

			// Delete posting table
			$query = query(sprintf('DROP TABLE IF EXISTS ``posts_%s``', $board['uri'])) or error(db_error());

			// Clear reports
			$query = prepare('DELETE FROM ``reports`` WHERE `board` = :id');
			$query->bindValue(':id', $board['uri'], PDO::PARAM_STR);
			$query->execute() or error(db_error($query));

			// Delete from table
			$query = prepare('DELETE FROM ``boards`` WHERE `uri` = :uri');
			$query->bindValue(':uri', $board['uri'], PDO::PARAM_STR);
			$query->execute() or error(db_error($query));

			$query = prepare("SELECT `board`, `post` FROM ``cites`` WHERE `target_board` = :board ORDER BY `board`");
			$query->bindValue(':board', $board['uri']);
			$query->execute() or error(db_error($query));
			while ($cite = $query->fetch(PDO::FETCH_ASSOC)) {
				if ($board['uri'] != $cite['board']) {
					if (!isset($tmp_board))
						$tmp_board = $board;
					openBoard($cite['board']);
					rebuildPost($cite['post']);
				}
			}

			if (isset($tmp_board))
				$board = $tmp_board;

			$query = prepare('DELETE FROM ``cites`` WHERE `board` = :board OR `target_board` = :board');
			$query->bindValue(':board', $board['uri']);
			$query->execute() or error(db_error($query));

			$query = prepare('DELETE FROM ``antispam`` WHERE `board` = :board');
			$query->bindValue(':board', $board['uri']);
			$query->execute() or error(db_error($query));

			// Remove board from users/permissions table
			$query = query('SELECT `id`,`boards` FROM ``mods``') or error(db_error());
			while ($user = $query->fetch(PDO::FETCH_ASSOC)) {
				$user_boards = explode(',', $user['boards']);
				if (in_array($board['uri'], $user_boards)) {
					unset($user_boards[array_search($board['uri'], $user_boards)]);
					$_query = prepare('UPDATE ``mods`` SET `boards` = :boards WHERE `id` = :id');
					$_query->bindValue(':boards', implode(',', $user_boards));
					$_query->bindValue(':id', $user['id']);
					$_query->execute() or error(db_error($_query));
				}
			}

			// Delete entire board directory
			rrmdir($board['uri'] . '/');
		} else {
			$query = prepare('UPDATE ``boards`` SET `title` = :title, `subtitle` = :subtitle WHERE `uri` = :uri');
			$query->bindValue(':uri', $board['uri']);
			$query->bindValue(':title', $_POST['title']);
			$query->bindValue(':subtitle', $_POST['subtitle']);
			$query->execute() or error(db_error($query));

			modLog('Edited board information for ' . sprintf($config['board_abbreviation'], $board['uri']), false);
		}

		if ($config['cache']['enabled']) {
			cache::delete('board_' . $board['uri']);
			cache::delete('all_boards');
		}

		rebuildThemes('boards');

		header('Location: ?/', true, $config['redirect_http']);
	} else {
		mod_page(sprintf('%s: ' . $config['board_abbreviation'], _('Edit board'), $board['uri']), $config['file_mod_board'], array(
			'board' => $board,
			'token' => make_secure_link_token('edit/' . $board['uri'])
		));
	}
}

function mod_new_board() {
	global $config, $board;

	if (!hasPermission($config['mod']['newboard']))
		error($config['error']['noaccess']);

	if (isset($_POST['uri'], $_POST['title'], $_POST['subtitle'])) {
		if ($_POST['uri'] == '')
			error(sprintf($config['error']['required'], 'URI'));

		if ($_POST['title'] == '')
			error(sprintf($config['error']['required'], 'title'));

		if (!preg_match('/^' . $config['board_regex'] . '$/u', $_POST['uri']))
			error(sprintf($config['error']['invalidfield'], 'URI'));

		$bytes = 0;
		$chars = preg_split('//u', $_POST['uri'], -1, PREG_SPLIT_NO_EMPTY);
		foreach ($chars as $char) {
			$o = 0;
			$ord = ordutf8($char, $o);
			if ($ord > 0x0080)
				$bytes += 5; // @01ff
			else
				$bytes ++;
		}
		$bytes + strlen('posts_.frm');

		if ($bytes > 255) {
			error('Your filesystem cannot handle a board URI of that length (' . $bytes . '/255 bytes)');
			exit;
		}

		if (openBoard($_POST['uri'])) {
			error(sprintf($config['error']['boardexists'], $board['url']));
		}

		$query = prepare('INSERT INTO ``boards`` VALUES (:uri, :title, :subtitle)');
		$query->bindValue(':uri', $_POST['uri']);
		$query->bindValue(':title', $_POST['title']);
		$query->bindValue(':subtitle', $_POST['subtitle']);
		$query->execute() or error(db_error($query));

		modLog('Created a new board: ' . sprintf($config['board_abbreviation'], $_POST['uri']));

		if (!openBoard($_POST['uri']))
			error(_("Couldn't open board after creation."));

		$query = Element('posts.sql', array('board' => $board['uri']));

		if (mysql_version() < 50503)
			$query = preg_replace('/(CHARSET=|CHARACTER SET )utf8mb4/', '$1utf8', $query);

		query($query) or error(db_error());

		if ($config['cache']['enabled'])
			cache::delete('all_boards');

		// Build the board
		buildIndex();

		rebuildThemes('boards');

		header('Location: ?/' . $board['uri'] . '/' . $config['file_index'], true, $config['redirect_http']);
	}

	mod_page(_('New board'), $config['file_mod_board'], array('new' => true, 'token' => make_secure_link_token('new-board')));
}

function mod_noticeboard($page_no = 1) {
	global $config, $pdo, $mod;

	if ($page_no < 1)
		error($config['error']['404']);

	if (!hasPermission($config['mod']['noticeboard']))
		error($config['error']['noaccess']);

	if (isset($_POST['subject'], $_POST['body'])) {
		if (!hasPermission($config['mod']['noticeboard_post']))
			error($config['error']['noaccess']);

		$_POST['body'] = escape_markup_modifiers($_POST['body']);
		markup($_POST['body']);

		$query = prepare('INSERT INTO ``noticeboard`` VALUES (NULL, :mod, :time, :subject, :body)');
		$query->bindValue(':mod', $mod['id']);
		$query->bindvalue(':time', time());
		$query->bindValue(':subject', $_POST['subject']);
		$query->bindValue(':body', $_POST['body']);
		$query->execute() or error(db_error($query));

		if ($config['cache']['enabled'])
			cache::delete('noticeboard_preview');

		modLog('Posted a noticeboard entry');

		header('Location: ?/noticeboard#' . $pdo->lastInsertId(), true, $config['redirect_http']);
	}

	$query = prepare("SELECT ``noticeboard``.*, `username` FROM ``noticeboard`` LEFT JOIN ``mods`` ON ``mods``.`id` = `mod` ORDER BY `id` DESC LIMIT :offset, :limit");
	$query->bindValue(':limit', $config['mod']['noticeboard_page'], PDO::PARAM_INT);
	$query->bindValue(':offset', ($page_no - 1) * $config['mod']['noticeboard_page'], PDO::PARAM_INT);
	$query->execute() or error(db_error($query));
	$noticeboard = $query->fetchAll(PDO::FETCH_ASSOC);

	if (empty($noticeboard) && $page_no > 1)
		error($config['error']['404']);

	foreach ($noticeboard as &$entry) {
		$entry['delete_token'] = make_secure_link_token('noticeboard/delete/' . $entry['id']);
	}

	$query = prepare("SELECT COUNT(*) FROM ``noticeboard``");
	$query->execute() or error(db_error($query));
	$count = $query->fetchColumn();

	mod_page(_('Noticeboard'), $config['file_mod_noticeboard'], array(
		'noticeboard' => $noticeboard,
		'count' => $count,
		'token' => make_secure_link_token('noticeboard')
	));
}

function mod_noticeboard_delete($id) {
	global $config;

	if (!hasPermission($config['mod']['noticeboard_delete']))
			error($config['error']['noaccess']);

	$query = prepare('DELETE FROM ``noticeboard`` WHERE `id` = :id');
	$query->bindValue(':id', $id);
	$query->execute() or error(db_error($query));

	modLog('Deleted a noticeboard entry');

	if ($config['cache']['enabled'])
		cache::delete('noticeboard_preview');

	header('Location: ?/noticeboard', true, $config['redirect_http']);
}

function mod_news($page_no = 1) {
	global $config, $pdo, $mod;

	if ($page_no < 1)
		error($config['error']['404']);

	if (isset($_POST['subject'], $_POST['body'])) {
		if (!hasPermission($config['mod']['news']))
			error($config['error']['noaccess']);

		$_POST['body'] = escape_markup_modifiers($_POST['body']);
		markup($_POST['body']);

		$query = prepare('INSERT INTO ``news`` VALUES (NULL, :name, :time, :subject, :body)');
		$query->bindValue(':name', isset($_POST['name']) && hasPermission($config['mod']['news_custom']) ? $_POST['name'] : $mod['username']);
		$query->bindvalue(':time', time());
		$query->bindValue(':subject', $_POST['subject']);
		$query->bindValue(':body', $_POST['body']);
		$query->execute() or error(db_error($query));

		modLog('Posted a news entry');

		rebuildThemes('news');

		header('Location: ?/edit_news#' . $pdo->lastInsertId(), true, $config['redirect_http']);
	}

	$query = prepare("SELECT * FROM ``news`` ORDER BY `id` DESC LIMIT :offset, :limit");
	$query->bindValue(':limit', $config['mod']['news_page'], PDO::PARAM_INT);
	$query->bindValue(':offset', ($page_no - 1) * $config['mod']['news_page'], PDO::PARAM_INT);
	$query->execute() or error(db_error($query));
	$news = $query->fetchAll(PDO::FETCH_ASSOC);

	if (empty($news) && $page_no > 1)
		error($config['error']['404']);

	foreach ($news as &$entry) {
		$entry['delete_token'] = make_secure_link_token('edit_news/delete/' . $entry['id']);
	}

	$query = prepare("SELECT COUNT(*) FROM ``news``");
	$query->execute() or error(db_error($query));
	$count = $query->fetchColumn();

	mod_page(_('News'), $config['file_mod_news'], array('news' => $news, 'count' => $count, 'token' => make_secure_link_token('edit_news')));
}

function mod_news_delete($id) {
	global $config;

	if (!hasPermission($config['mod']['news_delete']))
			error($config['error']['noaccess']);

	$query = prepare('DELETE FROM ``news`` WHERE `id` = :id');
	$query->bindValue(':id', $id);
	$query->execute() or error(db_error($query));

	modLog('Deleted a news entry');

	header('Location: ?/edit_news', true, $config['redirect_http']);
}

function mod_log($page_no = 1) {
	global $config;

	if ($page_no < 1)
		error($config['error']['404']);

	if (!hasPermission($config['mod']['modlog']))
		error($config['error']['noaccess']);

	$query = prepare("SELECT `username`, `mod`, `ip`, `board`, `time`, `text` FROM ``modlogs`` LEFT JOIN ``mods`` ON `mod` = ``mods``.`id` ORDER BY `time` DESC LIMIT :offset, :limit");
	$query->bindValue(':limit', $config['mod']['modlog_page'], PDO::PARAM_INT);
	$query->bindValue(':offset', ($page_no - 1) * $config['mod']['modlog_page'], PDO::PARAM_INT);
	$query->execute() or error(db_error($query));
	$logs = $query->fetchAll(PDO::FETCH_ASSOC);

	if (empty($logs) && $page_no > 1)
		error($config['error']['404']);

	$query = prepare("SELECT COUNT(*) FROM ``modlogs``");
	$query->execute() or error(db_error($query));
	$count = $query->fetchColumn();

	mod_page(_('Moderation log'), $config['file_mod_log'], array('logs' => $logs, 'count' => $count));
}

function mod_user_log($username, $page_no = 1) {
	global $config;

	if ($page_no < 1)
		error($config['error']['404']);

	if (!hasPermission($config['mod']['modlog']))
		error($config['error']['noaccess']);

	$query = prepare("SELECT `username`, `mod`, `ip`, `board`, `time`, `text` FROM ``modlogs`` LEFT JOIN ``mods`` ON `mod` = ``mods``.`id` WHERE `username` = :username ORDER BY `time` DESC LIMIT :offset, :limit");
	$query->bindValue(':username', $username);
	$query->bindValue(':limit', $config['mod']['modlog_page'], PDO::PARAM_INT);
	$query->bindValue(':offset', ($page_no - 1) * $config['mod']['modlog_page'], PDO::PARAM_INT);
	$query->execute() or error(db_error($query));
	$logs = $query->fetchAll(PDO::FETCH_ASSOC);

	if (empty($logs) && $page_no > 1)
		error($config['error']['404']);

	$query = prepare("SELECT COUNT(*) FROM ``modlogs`` LEFT JOIN ``mods`` ON `mod` = ``mods``.`id` WHERE `username` = :username");
	$query->bindValue(':username', $username);
	$query->execute() or error(db_error($query));
	$count = $query->fetchColumn();

	mod_page(_('Moderation log'), $config['file_mod_log'], array('logs' => $logs, 'count' => $count, 'username' => $username));
}

function mod_board_log($board, $page_no = 1, $hide_names = false, $public = false) {
	global $config;

	if ($page_no < 1)
		error($config['error']['404']);

	if (!hasPermission($config['mod']['mod_board_log'], $board) && !$public)
		error($config['error']['noaccess']);

	$query = prepare("SELECT `username`, `mod`, `ip`, `board`, `time`, `text` FROM ``modlogs`` LEFT JOIN ``mods`` ON `mod` = ``mods``.`id` WHERE `board` = :board ORDER BY `time` DESC LIMIT :offset, :limit");
	$query->bindValue(':board', $board);
	$query->bindValue(':limit', $config['mod']['modlog_page'], PDO::PARAM_INT);
	$query->bindValue(':offset', ($page_no - 1) * $config['mod']['modlog_page'], PDO::PARAM_INT);
	$query->execute() or error(db_error($query));
	$logs = $query->fetchAll(PDO::FETCH_ASSOC);

	if (empty($logs) && $page_no > 1)
		error($config['error']['404']);

	if (!hasPermission($config['mod']['show_ip'])) {
		// Supports ipv4 only!
		foreach ($logs as $i => &$log) {
			$log['text'] = preg_replace_callback('/(?:<a href="\?\/IP\/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}">)?(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(?:<\/a>)?/', function($matches) {
				return "xxxx";//less_ip($matches[1]);
			}, $log['text']);
		}
	}

	$query = prepare("SELECT COUNT(*) FROM ``modlogs`` LEFT JOIN ``mods`` ON `mod` = ``mods``.`id` WHERE `board` = :board");
	$query->bindValue(':board', $board);
	$query->execute() or error(db_error($query));
	$count = $query->fetchColumn();

	mod_page(_('Board log'), $config['file_mod_log'], array('logs' => $logs, 'count' => $count, 'board' => $board, 'hide_names' => $hide_names, 'public' => $public));
}

function mod_view_catalog($boardName) {
	global $config, $mod;
	require_once($config['dir']['themes'].'/catalog/theme.php');
	$settings = array();
	$settings['boards'] = $boardName;
	$settings['update_on_posts'] = true;
	$settings['title'] = 'Catalog';
	$settings['use_tooltipster'] = true;
	$catalog = new Catalog();
	echo $catalog->build($settings, $boardName, true);
}

function mod_view_board($boardName, $page_no = 1) {
	global $config, $mod;

	if (!openBoard($boardName))
		error($config['error']['noboard']);

	if (!$page = index($page_no, $mod)) {
		error($config['error']['404']);
	}

	$page['pages'] = getPages(true);
	$page['pages'][$page_no-1]['selected'] = true;
	$page['btn'] = getPageButtons($page['pages'], true);
	$page['mod'] = true;
	$page['config'] = $config;

	echo Element($config['file_board_index'], $page);
}

function mod_view_thread($boardName, $thread) {
	global $config, $mod;

	if (!openBoard($boardName))
		error($config['error']['noboard']);

	$page = buildThread($thread, true, $mod);
	echo $page;
}

function mod_view_thread50($boardName, $thread) {
	global $config, $mod;

	if (!openBoard($boardName))
		error($config['error']['noboard']);

	$page = buildThread50($thread, true, $mod);
	echo $page;
}

function mod_ip_remove_note($cloaked_ip, $id) {
	$ip = uncloak_ip($cloaked_ip);
	global $config, $mod;

	if (!hasPermission($config['mod']['remove_notes']))
			error($config['error']['noaccess']);

	if (filter_var($ip, FILTER_VALIDATE_IP) === false)
		error("Invalid IP address.");

	$query = prepare('DELETE FROM ``ip_notes`` WHERE `ip` = :ip AND `id` = :id');
	$query->bindValue(':ip', $ip);
	$query->bindValue(':id', $id);
	$query->execute() or error(db_error($query));

	modLog("Removed a note for <a href=\"?/IP/{$cloaked_ip}\">{$cloaked_ip}</a>");

	header('Location: ?/IP/' . $cloaked_ip . '#notes', true, $config['redirect_http']);
}



function mod_page_ip($cip) {
	$ip = uncloak_ip($cip);
	global $config, $mod;

	if (filter_var($ip, FILTER_VALIDATE_IP) === false)
		error("Invalid IP address.");

	if (isset($_POST['ban_id'], $_POST['unban'])) {
		if (!hasPermission($config['mod']['unban']))
			error($config['error']['noaccess']);

		Bans::delete($_POST['ban_id'], true, $mod['boards']);

		header('Location: ?/IP/' . $cip . '#bans', true, $config['redirect_http']);
		return;
	}

	if (isset($_POST['ban_id'], $_POST['edit_ban'])) {
		if (!hasPermission($config['mod']['edit_ban']))
			error($config['error']['noaccess']);

		header('Location: ?/edit_ban/' . $_POST['ban_id'], true, $config['redirect_http']);
		return;
	}

	if (isset($_POST['note'])) {
		if (!hasPermission($config['mod']['create_notes']))
			error($config['error']['noaccess']);

		$_POST['note'] = escape_markup_modifiers($_POST['note']);
		markup($_POST['note']);
		$query = prepare('INSERT INTO ``ip_notes`` VALUES (NULL, :ip, :mod, :time, :body)');
		$query->bindValue(':ip', $ip);
		$query->bindValue(':mod', $mod['id']);
		$query->bindValue(':time', time());
		$query->bindValue(':body', $_POST['note']);
		$query->execute() or error(db_error($query));

		modLog("Added a note for <a href=\"?/IP/{$cip}\">{$cip}</a>");

		header('Location: ?/IP/' . $cip . '#notes', true, $config['redirect_http']);
		return;
	}


	$args = array();
	$args['ip'] = $ip;
	$args['posts'] = array();

	if ($config['mod']['dns_lookup'] && empty($config['ipcrypt_key']))
		$args['hostname'] = rDNS($ip);

	$boards = listBoards();
	foreach ($boards as $board) {
		openBoard($board['uri']);
		if (!hasPermission($config['mod']['show_ip'], $board['uri']))
			continue;
		$query = prepare(sprintf('SELECT * FROM ``posts_%s`` WHERE `ip` = :ip ORDER BY `sticky` DESC, `id` DESC LIMIT :limit', $board['uri']));
		$query->bindValue(':ip', $ip);
		$query->bindValue(':limit', $config['mod']['ip_recentposts'], PDO::PARAM_INT);
		$query->execute() or error(db_error($query));

		while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
			if (!$post['thread']) {
				$po = new Thread($post, '?/', $mod, false);
			} else {
				$po = new Post($post, '?/', $mod);
			}

			if (!isset($args['posts'][$board['uri']]))
				$args['posts'][$board['uri']] = array('board' => $board, 'posts' => array());
			$args['posts'][$board['uri']]['posts'][] = $po->build(true);
		}
	}

	$args['boards'] = $boards;
	$args['token'] = make_secure_link_token('ban');

	if (hasPermission($config['mod']['view_ban'])) {
		$args['bans'] = Bans::find($ip, false, true);
	}

	if (hasPermission($config['mod']['view_notes'])) {
		$query = prepare("SELECT ``ip_notes``.*, `username` FROM ``ip_notes`` LEFT JOIN ``mods`` ON `mod` = ``mods``.`id` WHERE `ip` = :ip ORDER BY `time` DESC");
		$query->bindValue(':ip', $ip);
		$query->execute() or error(db_error($query));
		$args['notes'] = $query->fetchAll(PDO::FETCH_ASSOC);
	}

	if (hasPermission($config['mod']['modlog_ip'])) {
		$query = prepare("SELECT `username`, `mod`, `ip`, `board`, `time`, `text` FROM ``modlogs`` LEFT JOIN ``mods`` ON `mod` = ``mods``.`id` WHERE `text` LIKE :search ORDER BY `time` DESC LIMIT 50");
		$query->bindValue(':search', '%' . $cip . '%');
		$query->execute() or error(db_error($query));
		$args['logs'] = $query->fetchAll(PDO::FETCH_ASSOC);
	} else {
		$args['logs'] = array();
	}

	$args['security_token'] = make_secure_link_token('IP/' . $cip);

	mod_page(sprintf('%s: %s', _('IP'), htmlspecialchars($cip)), $config['file_mod_view_ip'], $args, $args['hostname']);
}

function mod_edit_ban($ban_id) {
	global $mod, $config;

	if (!hasPermission($config['mod']['edit_ban']))
		error($config['error']['noaccess']);

	$args['bans'] = Bans::find(null, false, true, $ban_id);
	$args['ban_id'] = $ban_id;
	$args['boards'] = listBoards();
	$args['current_board'] = isset($args['bans'][0]['board']) ? $args['bans'][0]['board'] : false;

	if (!$args['bans'])
		error($config['error']['404']);

	if (isset($_POST['new_ban'])) {

		$new_ban['mask'] = $args['bans'][0]['mask'];
		$new_ban['post'] = isset($args['bans'][0]['post']) ? $args['bans'][0]['post'] : false;
		$new_ban['board'] = $args['current_board'];

		if (isset($_POST['reason']))
			$new_ban['reason'] = $_POST['reason'];
		else
			$new_ban['reason'] = $args['bans'][0]['reason'];

		if (isset($_POST['length']) && !empty($_POST['length']))
			$new_ban['length'] = $_POST['length'];
		else
			$new_ban['length'] = false;

		if (isset($_POST['board'])) {
			if ($_POST['board'] == '*')
				$new_ban['board'] = false;
			else
				$new_ban['board'] = $_POST['board'];
		}

		Bans::new_ban($new_ban['mask'], $new_ban['reason'], $new_ban['length'], $new_ban['board'], false, $new_ban['post']);
		Bans::delete($ban_id);

		header('Location: ?/', true, $config['redirect_http']);

	}

	$args['token'] = make_secure_link_token('edit_ban/' . $ban_id);

	mod_page(_('Edit ban'), 'mod/edit_ban.html', $args);

}


function mod_ban() {
	global $config;

	if (!hasPermission($config['mod']['ban']))
		error($config['error']['noaccess']);

	if (!isset($_POST['ip'], $_POST['reason'], $_POST['length'], $_POST['board'])) {
		mod_page(_('New ban'), $config['file_mod_ban_form'], array('token' => make_secure_link_token('ban')));
		return;
	}

	Bans::new_ban($_POST['ip'], $_POST['reason'], $_POST['length'], $_POST['board'] == '*' ? false : $_POST['board']);

	if (isset($_POST['redirect']))
		header('Location: ' . $_POST['redirect'], true, $config['redirect_http']);
	else
		header('Location: ?/', true, $config['redirect_http']);
}

function mod_bans() {
	global $config;
	global $mod;

	if (!hasPermission($config['mod']['view_banlist']))
		error($config['error']['noaccess']);

	if (isset($_POST['unban'])) {
		if (!hasPermission($config['mod']['unban']))
			error($config['error']['noaccess']);

		$unban = array();
		foreach ($_POST as $name => $unused) {
			if (preg_match('/^ban_(\d+)$/', $name, $match))
				$unban[] = $match[1];
		}
		if (isset($config['mod']['unban_limit']) && $config['mod']['unban_limit'] && count($unban) > $config['mod']['unban_limit'])
			error(sprintf($config['error']['toomanyunban'], $config['mod']['unban_limit'], count($unban)));

		foreach ($unban as $id) {
			Bans::delete($id, true, $mod['boards'], true);
		}
                rebuildThemes('bans');
		header('Location: ?/bans', true, $config['redirect_http']);
		return;
	}

	mod_page(_('Ban list'), $config['file_mod_ban_list'], array(
		'mod' => $mod,
		'boards' => json_encode($mod['boards']),
		'token' => make_secure_link_token('bans'),
		'token_json' => make_secure_link_token('bans.json')
	));
}

function mod_bans_json() {
        global $config, $mod;

        if (!hasPermission($config['mod']['ban']))
                error($config['error']['noaccess']);

	// Compress the json for faster loads
	if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) ob_start("ob_gzhandler");

	Bans::stream_json(false, false, !hasPermission($config['mod']['view_banstaff']), $mod['boards']);
}

function mod_ban_appeals() {
	global $config, $board;

	if (!hasPermission($config['mod']['view_ban_appeals']))
		error($config['error']['noaccess']);

	// Remove stale ban appeals
	query("DELETE FROM ``ban_appeals`` WHERE NOT EXISTS (SELECT 1 FROM ``bans`` WHERE `ban_id` = ``bans``.`id`)")
		or error(db_error());

	if (isset($_POST['appeal_id']) && (isset($_POST['unban']) || isset($_POST['deny']))) {
		if (!hasPermission($config['mod']['ban_appeals']))
			error($config['error']['noaccess']);

		$query = query("SELECT *, ``ban_appeals``.`id` AS `id` FROM ``ban_appeals``
			LEFT JOIN ``bans`` ON `ban_id` = ``bans``.`id`
			WHERE ``ban_appeals``.`id` = " . (int)$_POST['appeal_id']) or error(db_error());
		if (!$ban = $query->fetch(PDO::FETCH_ASSOC)) {
			error(_('Ban appeal not found!'));
		}

		$ban['mask'] = cloak_mask(Bans::range_to_string(array($ban['ipstart'], $ban['ipend'])));

		if (isset($_POST['unban'])) {
			modLog('Accepted ban appeal #' . $ban['id'] . ' for ' . $ban['mask']);
			Bans::delete($ban['ban_id'], true);
			query("DELETE FROM ``ban_appeals`` WHERE `id` = " . $ban['id']) or error(db_error());
		} else {
			modLog('Denied ban appeal #' . $ban['id'] . ' for ' . $ban['mask']);
			query("UPDATE ``ban_appeals`` SET `denied` = 1 WHERE `id` = " . $ban['id']) or error(db_error());
		}

		header('Location: ?/ban-appeals', true, $config['redirect_http']);
		return;
	}

	$query = query("SELECT *, ``ban_appeals``.`id` AS `id` FROM ``ban_appeals``
		LEFT JOIN ``bans`` ON `ban_id` = ``bans``.`id`
		LEFT JOIN ``mods`` ON ``bans``.`creator` = ``mods``.`id`
		WHERE `denied` != 1 ORDER BY `time`") or error(db_error());
	$ban_appeals = $query->fetchAll(PDO::FETCH_ASSOC);
	foreach ($ban_appeals as &$ban) {
		if ($ban['post'])
			$ban['post'] = json_decode($ban['post'], true);
		$ban['mask'] = Bans::range_to_string(array($ban['ipstart'], $ban['ipend']));

		if ($ban['post'] && isset($ban['post']['board'], $ban['post']['id'])) {
			if (openBoard($ban['post']['board'])) {
				$query = query(sprintf("SELECT `num_files`, `files` FROM ``posts_%s`` WHERE `id` = " .
					(int)$ban['post']['id'], $board['uri']));
				if ($_post = $query->fetch(PDO::FETCH_ASSOC)) {
					$_post['files'] = $_post['files'] ? json_decode($_post['files']) : array();
					$ban['post'] = array_merge($ban['post'], $_post);
				} else {
					$ban['post']['files'] = array(array());
					$ban['post']['files'][0]['file'] = 'deleted';
					$ban['post']['files'][0]['thumb'] = false;
					$ban['post']['num_files'] = 1;
				}
			} else {
				$ban['post']['files'] = array(array());
				$ban['post']['files'][0]['file'] = 'deleted';
				$ban['post']['files'][0]['thumb'] = false;
				$ban['post']['num_files'] = 1;
			}

			if ($ban['post']['thread']) {
				$ban['post'] = new Post($ban['post']);
			} else {
				$ban['post'] = new Thread($ban['post'], null, false, false);
			}
		}
	}

	mod_page(_('Ban appeals'), $config['file_mod_ban_appeals'], array(
		'ban_appeals' => $ban_appeals,
		'token' => make_secure_link_token('ban-appeals')
	));
}

function mod_lock($board, $unlock, $post) {
	global $config;

	if (!openBoard($board))
		error($config['error']['noboard']);

	if (!hasPermission($config['mod']['lock'], $board))
		error($config['error']['noaccess']);

	$query = prepare(sprintf('UPDATE ``posts_%s`` SET `locked` = :locked WHERE `id` = :id AND `thread` IS NULL', $board));
	$query->bindValue(':id', $post);
	$query->bindValue(':locked', $unlock ? 0 : 1);
	$query->execute() or error(db_error($query));
	if ($query->rowCount()) {
		modLog(($unlock ? 'Unlocked' : 'Locked') . " thread #{$post}");
		buildThread($post);
		buildIndex();
	}

	if ($config['mod']['dismiss_reports_on_lock']) {
		$query = prepare('DELETE FROM ``reports`` WHERE `board` = :board AND `post` = :id');
		$query->bindValue(':board', $board);
		$query->bindValue(':id', $post);
		$query->execute() or error(db_error($query));
	}

	header('Location: ?/' . sprintf($config['board_path'], $board) . $config['file_index'], true, $config['redirect_http']);

	if ($unlock)
		event('unlock', $post);
	else
		event('lock', $post);
}

function mod_sticky($board, $unsticky, $post) {
	global $config;

	if (!openBoard($board))
		error($config['error']['noboard']);

	if (!hasPermission($config['mod']['sticky'], $board))
		error($config['error']['noaccess']);

	$query = prepare(sprintf('UPDATE ``posts_%s`` SET `sticky` = :sticky WHERE `id` = :id AND `thread` IS NULL', $board));
	$query->bindValue(':id', $post);
	$query->bindValue(':sticky', $unsticky ? 0 : 1);
	$query->execute() or error(db_error($query));
	if ($query->rowCount()) {
		modLog(($unsticky ? 'Unstickied' : 'Stickied') . " thread #{$post}");
		buildThread($post);
		buildIndex();
	}

	header('Location: ?/' . sprintf($config['board_path'], $board) . $config['file_index'], true, $config['redirect_http']);
}

function mod_cycle($board, $uncycle, $post) {
	global $config;

	if (!openBoard($board))
		error($config['error']['noboard']);

	if (!hasPermission($config['mod']['cycle'], $board))
		error($config['error']['noaccess']);

	$query = prepare(sprintf('UPDATE ``posts_%s`` SET `cycle` = :cycle WHERE `id` = :id AND `thread` IS NULL', $board));
	$query->bindValue(':id', $post);
	$query->bindValue(':cycle', $uncycle ? 0 : 1);
	$query->execute() or error(db_error($query));
	if ($query->rowCount()) {
		modLog(($uncycle ? 'Made not cyclical' : 'Made cyclical') . " thread #{$post}");
		buildThread($post);
		buildIndex();
	}

	header('Location: ?/' . sprintf($config['board_path'], $board) . $config['file_index'], true, $config['redirect_http']);
}

function mod_bumplock($board, $unbumplock, $post) {
	global $config;

	if (!openBoard($board))
		error($config['error']['noboard']);

	if (!hasPermission($config['mod']['bumplock'], $board))
		error($config['error']['noaccess']);

	$query = prepare(sprintf('UPDATE ``posts_%s`` SET `sage` = :bumplock WHERE `id` = :id AND `thread` IS NULL', $board));
	$query->bindValue(':id', $post);
	$query->bindValue(':bumplock', $unbumplock ? 0 : 1);
	$query->execute() or error(db_error($query));
	if ($query->rowCount()) {
		modLog(($unbumplock ? 'Unbumplocked' : 'Bumplocked') . " thread #{$post}");
		buildThread($post);
		buildIndex();
	}

	header('Location: ?/' . sprintf($config['board_path'], $board) . $config['file_index'], true, $config['redirect_http']);
}

function mod_move_reply($originBoard, $postID) {
	global $board, $config, $mod;

	if (!openBoard($originBoard))
		error($config['error']['noboard']);

	if (!hasPermission($config['mod']['move'], $originBoard))
		error($config['error']['noaccess']);

	$query = prepare(sprintf('SELECT * FROM ``posts_%s`` WHERE `id` = :id', $originBoard));
	$query->bindValue(':id', $postID);
	$query->execute() or error(db_error($query));
	if (!$post = $query->fetch(PDO::FETCH_ASSOC))
		error($config['error']['404']);

	if (isset($_POST['board'])) {
		$targetBoard = $_POST['board'];

		if ($_POST['target_thread']) {
			$query = prepare(sprintf('SELECT * FROM ``posts_%s`` WHERE `id` = :id', $targetBoard));
			$query->bindValue(':id', $_POST['target_thread']);
			$query->execute() or error(db_error($query)); // If it fails, thread probably does not exist
			$post['op'] = false;
			$post['thread'] = $_POST['target_thread'];
		}
		else {
			$post['op'] = true;
		}

		if ($post['files']) {
			$post['files'] = json_decode($post['files'], TRUE);
			$post['has_file'] = true;
			foreach ($post['files'] as $i => &$file) {
				$file['file_path'] = sprintf($config['board_path'], $board['uri']) . $config['dir']['img'] . $file['file'];
				if (isset($file['thumb']))
				$file['thumb_path'] = sprintf($config['board_path'], $board['uri']) . $config['dir']['thumb'] . $file['thumb'];
			}
		} else {
			$post['has_file'] = false;
		}

		// allow thread to keep its same traits (stickied, locked, etc.)
		$post['mod'] = true;

		if (!openBoard($targetBoard))
			error($config['error']['noboard']);

		// create the new post
		$newID = post($post);

		if ($post['has_file']) {
			foreach ($post['files'] as $i => &$file) {
				// move the image
				if (isset($file['thumb']))
				if ($file['thumb'] != 'spoiler' || $file['thumb'] != 'deleted') { //trying to move/copy the spoiler thumb raises an error
				rename($file['file_path'], sprintf($config['board_path'], $board['uri']) . $config['dir']['img'] . $file['file']);
				rename($file['thumb_path'], sprintf($config['board_path'], $board['uri']) . $config['dir']['thumb'] . $file['thumb']);
				}
			}
		}

		// build index
		buildIndex();
		// build new thread
		buildThread($newID);

		// trigger themes
		rebuildThemes('post', $targetBoard);
		// mod log
		modLog("Moved post #${postID} to " . sprintf($config['board_abbreviation'], $targetBoard) . " (#${newID})", $originBoard);

		// return to original board
		openBoard($originBoard);

		// delete original post
		deletePost($postID);
		buildIndex();

		// open target board for redirect
		openBoard($targetBoard);

		// Find new thread on our target board
		$query = prepare(sprintf('SELECT thread FROM ``posts_%s`` WHERE `id` = :id', $targetBoard));
		$query->bindValue(':id', $newID);
		$query->execute() or error(db_error($query));
		$post = $query->fetch(PDO::FETCH_ASSOC);

		// redirect
		header('Location: ?/' . sprintf($config['board_path'], $board['uri']) . $config['dir']['res'] . link_for($post) . '#' . $newID, true, $config['redirect_http']);
	}

	else {
		$boards = listBoards();

		$security_token = make_secure_link_token($originBoard . '/move_reply/' . $postID);

		mod_page(_('Move reply'), $config['file_mod_move_reply'], array('post' => $postID, 'board' => $originBoard, 'boards' => $boards, 'token' => $security_token));

	}

}

function mod_move($originBoard, $postID) {
	global $board, $config, $mod, $pdo;

	if (!openBoard($originBoard))
		error($config['error']['noboard']);

	if (!hasPermission($config['mod']['move'], $originBoard))
		error($config['error']['noaccess']);

	$query = prepare(sprintf('SELECT * FROM ``posts_%s`` WHERE `id` = :id AND `thread` IS NULL', $originBoard));
	$query->bindValue(':id', $postID);
	$query->execute() or error(db_error($query));
	if (!$post = $query->fetch(PDO::FETCH_ASSOC))
		error($config['error']['404']);

	if (isset($_POST['board'])) {
		$targetBoard = $_POST['board'];
		$shadow = isset($_POST['shadow']);

		if ($targetBoard === $originBoard)
			error(_('Target and source board are the same.'));

		// copy() if leaving a shadow thread behind; else, rename().
		$clone = $shadow ? 'copy' : 'rename';

		// indicate that the post is a thread
		$post['op'] = true;

		if ($post['files']) {
			$post['files'] = json_decode($post['files'], TRUE);
			$post['has_file'] = true;
			foreach ($post['files'] as $i => &$file) {
				if ($file['file'] === 'deleted')
					continue;
				$file['file_path'] = sprintf($config['board_path'], $board['uri']) . $config['dir']['img'] . $file['file'];
				$file['thumb_path'] = sprintf($config['board_path'], $board['uri']) . $config['dir']['thumb'] . $file['thumb'];
			}
		} else {
			$post['has_file'] = false;
		}

		// allow thread to keep its same traits (stickied, locked, etc.)
		$post['mod'] = true;

		if (!openBoard($targetBoard))
			error($config['error']['noboard']);

		// create the new thread
		$newID = post($post);

		$op = $post;
		$op['id'] = $newID;

		if ($post['has_file']) {
			// copy image
			foreach ($post['files'] as $i => &$file) {
				if ($file['file'] !== 'deleted')
					$clone($file['file_path'], sprintf($config['board_path'], $board['uri']) . $config['dir']['img'] . $file['file']);
				if (isset($file['thumb']) && !in_array($file['thumb'], array('spoiler', 'deleted', 'file')))
					$clone($file['thumb_path'], sprintf($config['board_path'], $board['uri']) . $config['dir']['thumb'] . $file['thumb']);
			}
		}

		// go back to the original board to fetch replies
		openBoard($originBoard);

		$query = prepare(sprintf('SELECT * FROM ``posts_%s`` WHERE `thread` = :id ORDER BY `id`', $originBoard));
		$query->bindValue(':id', $postID, PDO::PARAM_INT);
		$query->execute() or error(db_error($query));

		$replies = array();

		while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
			$post['mod'] = true;
			$post['thread'] = $newID;

			if ($post['files']) {
				$post['files'] = json_decode($post['files'], TRUE);
				$post['has_file'] = true;
				foreach ($post['files'] as $i => &$file) {
					$file['file_path'] = sprintf($config['board_path'], $board['uri']) . $config['dir']['img'] . $file['file'];

					if (isset($file['thumb']))
						$file['thumb_path'] = sprintf($config['board_path'], $board['uri']) . $config['dir']['thumb'] . $file['thumb'];
				}
			} else {
				$post['has_file'] = false;
			}

			$replies[] = $post;
		}

		$newIDs = array($postID => $newID);

		openBoard($targetBoard);

		foreach ($replies as &$post) {
			$query = prepare('SELECT `target` FROM ``cites`` WHERE `target_board` = :board AND `board` = :board AND `post` = :post');
			$query->bindValue(':board', $originBoard);
			$query->bindValue(':post', $post['id'], PDO::PARAM_INT);
			$query->execute() or error(db_error($query));

			// correct >>X links
			while ($cite = $query->fetch(PDO::FETCH_ASSOC)) {
				if (isset($newIDs[$cite['target']])) {
					$post['body_nomarkup'] = preg_replace(
							'/(>>(>\/' . preg_quote($originBoard, '/') . '\/)?)' . preg_quote($cite['target'], '/') . '/',
							'>>' . $newIDs[$cite['target']],
							$post['body_nomarkup']);

					$post['body'] = $post['body_nomarkup'];
				}
			}

			$post['body'] = $post['body_nomarkup'];

			$post['op'] = false;
			$post['tracked_cites'] = markup($post['body'], true);

			if ($post['has_file']) {
				// copy image
				foreach ($post['files'] as $i => &$file) {
					if (isset($file['thumb']))
					if ($file['thumb'] != 'spoiler' || $file['thumb'] != 'deleted') { //trying to move/copy the spoiler thumb raises an error
						$clone($file['file_path'], sprintf($config['board_path'], $board['uri']) . $config['dir']['img'] . $file['file']);
						$clone($file['thumb_path'], sprintf($config['board_path'], $board['uri']) . $config['dir']['thumb'] . $file['thumb']);
					}
				}
			}
			// insert reply
			$newIDs[$post['id']] = $newPostID = post($post);


			if (!empty($post['tracked_cites'])) {
				$insert_rows = array();
				foreach ($post['tracked_cites'] as $cite) {
					$insert_rows[] = '(' .
						$pdo->quote($board['uri']) . ', ' . $newPostID . ', ' .
						$pdo->quote($cite[0]) . ', ' . (int)$cite[1] . ')';
				}
				query('INSERT INTO ``cites`` VALUES ' . implode(', ', $insert_rows)) or error(db_error());
			}
		}

		modLog("Moved thread #${postID} to " . sprintf($config['board_abbreviation'], $targetBoard) . " (#${newID})", $originBoard);

		// build new thread
		buildThread($newID);

		clean();
		buildIndex();

		// trigger themes
		rebuildThemes('post', $targetBoard);

		$newboard = $board;

		// return to original board
		openBoard($originBoard);

		if ($shadow) {
			// lock old thread
			$query = prepare(sprintf('UPDATE ``posts_%s`` SET `locked` = 1 WHERE `id` = :id', $originBoard));
			$query->bindValue(':id', $postID, PDO::PARAM_INT);
			$query->execute() or error(db_error($query));

			// leave a reply, linking to the new thread
			$spost = array(
				'mod' => true,
				'subject' => '',
				'email' => '',
				'name' => (!$config['mod']['shadow_name'] ? $config['anonymous'] : $config['mod']['shadow_name']),
				'capcode' => $config['mod']['shadow_capcode'],
				'trip' => '',
				'password' => '',
				'has_file' => false,
				// attach to original thread
				'thread' => $postID,
				'op' => false
			);

			$spost['body'] = $spost['body_nomarkup'] =  sprintf($config['mod']['shadow_mesage'], '>>>/' . $targetBoard . '/' . $newID);

			markup($spost['body']);

			$botID = post($spost);
			buildThread($postID);

			buildIndex();

			header('Location: ?/' . sprintf($config['board_path'], $newboard['uri']) . $config['dir']['res'] . link_for($op, false, $newboard) .
				'#' . $botID, true, $config['redirect_http']);
		} else {
			deletePost($postID);
			buildIndex();

			openBoard($targetBoard);
			header('Location: ?/' . sprintf($config['board_path'], $newboard['uri']) . $config['dir']['res'] . link_for($op, false, $newboard), true, $config['redirect_http']);
		}
	}

	$boards = listBoards();
	if (count($boards) <= 1)
		error(_('Impossible to move thread; there is only one board.'));

	$security_token = make_secure_link_token($originBoard . '/move/' . $postID);

	mod_page(_('Move thread'), $config['file_mod_move'], array('post' => $postID, 'board' => $originBoard, 'boards' => $boards, 'token' => $security_token));
}

function mod_ban_post($board, $delete, $post, $token = false) {
	global $config, $mod;

	if (!openBoard($board))
		error($config['error']['noboard']);

	if (!hasPermission($config['mod']['delete'], $board))
		error($config['error']['noaccess']);

	$security_token = make_secure_link_token($board . '/ban/' . $post);

	$query = prepare(sprintf('SELECT ' . ($config['ban_show_post'] ? '*' : '`ip`, `thread`') .
		' FROM ``posts_%s`` WHERE `id` = :id', $board));
	$query->bindValue(':id', $post);
	$query->execute() or error(db_error($query));
	if (!$_post = $query->fetch(PDO::FETCH_ASSOC))
		error($config['error']['404']);

	$thread = $_post['thread'];
	$ip = $_post['ip'];

	if (isset($_POST['new_ban'], $_POST['reason'], $_POST['length'], $_POST['board'])) {
		if (isset($_POST['ip']))
			$ip = $_POST['ip'];

		Bans::new_ban($ip, $_POST['reason'], $_POST['length'], $_POST['board'] == '*' ? false : $_POST['board'],
			false, $config['ban_show_post'] ? $_post : false);

		if (isset($_POST['public_message'], $_POST['message'])) {
			// public ban message
			$length_english = Bans::parse_time($_POST['length']) ? 'for ' . until(Bans::parse_time($_POST['length'])) : 'permanently';
			$_POST['message'] = preg_replace('/[\r\n]/', '', $_POST['message']);
			$_POST['message'] = str_replace('%length%', $length_english, $_POST['message']);
			$_POST['message'] = str_replace('%LENGTH%', strtoupper($length_english), $_POST['message']);
			$query = prepare(sprintf('UPDATE ``posts_%s`` SET `body_nomarkup` = CONCAT(`body_nomarkup`, :body_nomarkup) WHERE `id` = :id', $board));
			$query->bindValue(':id', $post);
			$query->bindValue(':body_nomarkup', sprintf("\n<tinyboard ban message>%s</tinyboard>", utf8tohtml($_POST['message'])));
			$query->execute() or error(db_error($query));
			rebuildPost($post);

			modLog("Attached a public ban message to post #{$post}: " . utf8tohtml($_POST['message']));
			buildThread($thread ? $thread : $post);
			buildIndex();
		} elseif (isset($_POST['delete']) && (int) $_POST['delete']) {
			// Delete post
			deletePost($post);
			modLog("Deleted post #{$post}");
			// Rebuild board
			buildIndex();
			// Rebuild themes
			rebuildThemes('post-delete', $board);
		}

		header('Location: ?/' . sprintf($config['board_path'], $board) . $config['file_index'], true, $config['redirect_http']);
	}

	$args = array(
		'ip' => $ip,
		'hide_ip' => !hasPermission($config['mod']['show_ip'], $board),
		'post' => $post,
		'board' => $board,
		'delete' => (bool)$delete,
		'boards' => listBoards(),
		'reasons' => $config['premade_ban_reasons'],
		'token' => $security_token
	);

	mod_page(_('New ban'), $config['file_mod_ban_form'], $args);
}

function mod_edit_post($board, $edit_raw_html, $postID) {
	global $config, $mod;

	if (!openBoard($board))
		error($config['error']['noboard']);

	if (!hasPermission($config['mod']['editpost'], $board))
		error($config['error']['noaccess']);

	if ($edit_raw_html && !hasPermission($config['mod']['rawhtml'], $board))
		error($config['error']['noaccess']);

	$security_token = make_secure_link_token($board . '/edit' . ($edit_raw_html ? '_raw' : '') . '/' . $postID);

	$query = prepare(sprintf('SELECT * FROM ``posts_%s`` WHERE `id` = :id', $board));
	$query->bindValue(':id', $postID);
	$query->execute() or error(db_error($query));

	if (!$post = $query->fetch(PDO::FETCH_ASSOC))
		error($config['error']['404']);

	if (isset($_POST['name'], $_POST['email'], $_POST['subject'], $_POST['body'])) {
		// Remove any modifiers they may have put in
		$_POST['body'] = remove_modifiers($_POST['body']);

		// Add back modifiers in the original post
		$modifiers = extract_modifiers($post['body_nomarkup']);
		foreach ($modifiers as $key => $value) {
			$_POST['body'] .= "<tinyboard $key>$value</tinyboard>";
		}

		if ($edit_raw_html)
			$query = prepare(sprintf('UPDATE ``posts_%s`` SET `name` = :name, `email` = :email, `subject` = :subject, `body` = :body, `body_nomarkup` = :body_nomarkup WHERE `id` = :id', $board));
		else
			$query = prepare(sprintf('UPDATE ``posts_%s`` SET `name` = :name, `email` = :email, `subject` = :subject, `body_nomarkup` = :body WHERE `id` = :id', $board));
		$query->bindValue(':id', $postID);
		$query->bindValue(':name', $_POST['name']);
		$query->bindValue(':email', $_POST['email']);
		$query->bindValue(':subject', $_POST['subject']);
		$query->bindValue(':body', $_POST['body']);
		if ($edit_raw_html) {
			$body_nomarkup = $_POST['body'] . "\n<tinyboard raw html>1</tinyboard>";
			$query->bindValue(':body_nomarkup', $body_nomarkup);
		}
		$query->execute() or error(db_error($query));

		if ($edit_raw_html) {
			modLog("Edited raw HTML of post #{$postID}");
		} else {
			modLog("Edited post #{$postID}");
			rebuildPost($postID);
		}

		buildIndex();

		rebuildThemes('post', $board);

		header('Location: ?/' . sprintf($config['board_path'], $board) . $config['dir']['res'] . link_for($post) . '#' . $postID, true, $config['redirect_http']);
	} else {
		// Remove modifiers
		$post['body_nomarkup'] = remove_modifiers($post['body_nomarkup']);

		$post['body_nomarkup'] = utf8tohtml($post['body_nomarkup']);
		$post['body'] = utf8tohtml($post['body']);
		if ($config['minify_html']) {
			$post['body_nomarkup'] = str_replace("\n", '&#010;', $post['body_nomarkup']);
			$post['body'] = str_replace("\n", '&#010;', $post['body']);
			$post['body_nomarkup'] = str_replace("\r", '', $post['body_nomarkup']);
			$post['body'] = str_replace("\r", '', $post['body']);
			$post['body_nomarkup'] = str_replace("\t", '&#09;', $post['body_nomarkup']);
			$post['body'] = str_replace("\t", '&#09;', $post['body']);
		}

		mod_page(_('Edit post'), $config['file_mod_edit_post_form'], array('token' => $security_token, 'board' => $board, 'raw' => $edit_raw_html, 'post' => $post));
	}
}

function mod_delete($board, $post) {
	global $config, $mod;

	if (!openBoard($board))
		error($config['error']['noboard']);

	if (!hasPermission($config['mod']['delete'], $board))
		error($config['error']['noaccess']);

	// Delete post
	deletePost($post);
	// Record the action
	modLog("Deleted post #{$post}");
	// Rebuild board
	buildIndex();
	// Rebuild themes
	rebuildThemes('post-delete', $board);
	// Redirect
	header('Location: ?/' . sprintf($config['board_path'], $board) . $config['file_index'], true, $config['redirect_http']);
}

function mod_deletefile($board, $post, $file) {
	global $config, $mod;

	if (!openBoard($board))
		error($config['error']['noboard']);

	if (!hasPermission($config['mod']['deletefile'], $board))
		error($config['error']['noaccess']);

	// Delete file
	deleteFile($post, TRUE, $file);
	// Record the action
	modLog("Deleted file from post #{$post}");

	// Rebuild board
	buildIndex();
	// Rebuild themes
	rebuildThemes('post-delete', $board);

	// Redirect
	header('Location: ?/' . sprintf($config['board_path'], $board) . $config['file_index'], true, $config['redirect_http']);
}

function mod_spoiler_image($board, $post, $file) {
	global $config, $mod;

	if (!openBoard($board))
		error($config['error']['noboard']);

	if (!hasPermission($config['mod']['spoilerimage'], $board))
		error($config['error']['noaccess']);

	// Delete file thumbnail
	$query = prepare(sprintf("SELECT `files`, `thread` FROM ``posts_%s`` WHERE id = :id", $board));
	$query->bindValue(':id', $post, PDO::PARAM_INT);
	$query->execute() or error(db_error($query));
	$result = $query->fetch(PDO::FETCH_ASSOC);
	$files = json_decode($result['files']);


	$size_spoiler_image = @getimagesize($config['spoiler_image']);
	file_unlink($board . '/' . $config['dir']['thumb'] . $files[$file]->thumb);
	$files[$file]->thumb = 'spoiler';
	$files[$file]->thumbwidth = $size_spoiler_image[0];
	$files[$file]->thumbheight = $size_spoiler_image[1];

	// Make thumbnail spoiler
	$query = prepare(sprintf("UPDATE ``posts_%s`` SET `files` = :files WHERE `id` = :id", $board));
	$query->bindValue(':files', json_encode($files));
	$query->bindValue(':id', $post, PDO::PARAM_INT);
	$query->execute() or error(db_error($query));

	// Record the action
	modLog("Spoilered file from post #{$post}");

	// Rebuild thread
	buildThread($result['thread'] ? $result['thread'] : $post);

	// Rebuild board
	buildIndex();

	// Rebuild themes
	rebuildThemes('post-delete', $board);

	// Redirect
	header('Location: ?/' . sprintf($config['board_path'], $board) . $config['file_index'], true, $config['redirect_http']);
}

function mod_deletebyip($boardName, $post, $global = false) {
	global $config, $mod, $board;

	$global = (bool)$global;

	if (!openBoard($boardName))
		error($config['error']['noboard']);

	if (!$global && !hasPermission($config['mod']['deletebyip'], $boardName))
		error($config['error']['noaccess']);

	if ($global && !hasPermission($config['mod']['deletebyip_global'], $boardName))
		error($config['error']['noaccess']);

	// Find IP address
	$query = prepare(sprintf('SELECT `ip` FROM ``posts_%s`` WHERE `id` = :id', $boardName));
	$query->bindValue(':id', $post);
	$query->execute() or error(db_error($query));
	if (!$ip = $query->fetchColumn())
		error($config['error']['invalidpost']);

	$boards = $global ? listBoards() : array(array('uri' => $boardName));

	$query = '';
	foreach ($boards as $_board) {
		$query .= sprintf("SELECT `thread`, `id`, '%s' AS `board` FROM ``posts_%s`` WHERE `ip` = :ip UNION ALL ", $_board['uri'], $_board['uri']);
	}
	$query = preg_replace('/UNION ALL $/', '', $query);

	$query = prepare($query);
	$query->bindValue(':ip', $ip);
	$query->execute() or error(db_error($query));

	if ($query->rowCount() < 1)
		error($config['error']['invalidpost']);

	@set_time_limit($config['mod']['rebuild_timelimit']);

	$threads_to_rebuild = array();
	$threads_deleted = array();
	while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
		openBoard($post['board']);

		deletePost($post['id'], false, false);

		rebuildThemes('post-delete', $board['uri']);

		buildIndex();

		if ($post['thread'])
			$threads_to_rebuild[$post['board']][$post['thread']] = true;
		else
			$threads_deleted[$post['board']][$post['id']] = true;
	}

	foreach ($threads_to_rebuild as $_board => $_threads) {
		openBoard($_board);
		foreach ($_threads as $_thread => $_dummy) {
			if ($_dummy && !isset($threads_deleted[$_board][$_thread]))
				buildThread($_thread);
		}
		buildIndex();
	}

	if ($global) {
		$board = false;
	}

	// Record the action
	$cip = cloak_ip($ip);
	modLog("Deleted all posts by IP address: <a href=\"?/IP/$cip\">$cip</a>");

	// Redirect
	header('Location: ?/' . sprintf($config['board_path'], $boardName) . $config['file_index'], true, $config['redirect_http']);
}

function mod_user($uid) {
	global $config, $mod;

	if (!hasPermission($config['mod']['editusers']) && !(hasPermission($config['mod']['change_password']) && $uid == $mod['id']))
		error($config['error']['noaccess']);

	$query = prepare('SELECT * FROM ``mods`` WHERE `id` = :id');
	$query->bindValue(':id', $uid);
	$query->execute() or error(db_error($query));
	if (!$user = $query->fetch(PDO::FETCH_ASSOC))
		error($config['error']['404']);

	if (hasPermission($config['mod']['editusers']) && isset($_POST['username'], $_POST['password'])) {
		if (isset($_POST['allboards'])) {
			$boards = array('*');
		} else {
			$_boards = listBoards();
			foreach ($_boards as &$board) {
				$board = $board['uri'];
			}

			$boards = array();
			foreach ($_POST as $name => $value) {
				if (preg_match('/^board_(' . $config['board_regex'] . ')$/u', $name, $matches) && in_array($matches[1], $_boards))
					$boards[] = $matches[1];
			}
		}

		if (isset($_POST['delete'])) {
			if (!hasPermission($config['mod']['deleteusers']))
				error($config['error']['noaccess']);

			$query = prepare('DELETE FROM ``mods`` WHERE `id` = :id');
			$query->bindValue(':id', $uid);
			$query->execute() or error(db_error($query));

			modLog('Deleted user ' . utf8tohtml($user['username']) . ' <small>(#' . $user['id'] . ')</small>');

			header('Location: ?/users', true, $config['redirect_http']);

			return;
		}

		if ($_POST['username'] == '')
			error(sprintf($config['error']['required'], 'username'));

		$query = prepare('UPDATE ``mods`` SET `username` = :username, `boards` = :boards WHERE `id` = :id');
		$query->bindValue(':id', $uid);
		$query->bindValue(':username', $_POST['username']);
		$query->bindValue(':boards', implode(',', $boards));
		$query->execute() or error(db_error($query));

		if ($user['username'] !== $_POST['username']) {
			// account was renamed
			modLog('Renamed user "' . utf8tohtml($user['username']) . '" <small>(#' . $user['id'] . ')</small> to "' . utf8tohtml($_POST['username']) . '"');
		}

		if ($_POST['password'] != '') {
			list($version, $password) = crypt_password($_POST['password']);

			$query = prepare('UPDATE ``mods`` SET `password` = :password, `version` = :version WHERE `id` = :id');
			$query->bindValue(':id', $uid);
			$query->bindValue(':password', $password);
			$query->bindValue(':version', $version);
			$query->execute() or error(db_error($query));

			modLog('Changed password for ' . utf8tohtml($_POST['username']) . ' <small>(#' . $user['id'] . ')</small>');

			if ($uid == $mod['id']) {
				login($_POST['username'], $_POST['password']);
				setCookies();
			}
		}

		if (hasPermission($config['mod']['manageusers']))
			header('Location: ?/users', true, $config['redirect_http']);
		else
			header('Location: ?/', true, $config['redirect_http']);

		return;
	}

	if (hasPermission($config['mod']['change_password']) && $uid == $mod['id'] && isset($_POST['password'])) {
		if ($_POST['password'] != '') {
			list($version, $password) = crypt_password($_POST['password']);

			$query = prepare('UPDATE ``mods`` SET `password` = :password, `version` = :version WHERE `id` = :id');
			$query->bindValue(':id', $uid);
			$query->bindValue(':password', $password);
			$query->bindValue(':version', $version);
			$query->execute() or error(db_error($query));

			modLog('Changed own password');

			login($user['username'], $_POST['password']);
			setCookies();
		}

		if (hasPermission($config['mod']['manageusers']))
			header('Location: ?/users', true, $config['redirect_http']);
		else
			header('Location: ?/', true, $config['redirect_http']);

		return;
	}

	if (hasPermission($config['mod']['modlog'])) {
		$query = prepare('SELECT * FROM ``modlogs`` WHERE `mod` = :id ORDER BY `time` DESC LIMIT 5');
		$query->bindValue(':id', $uid);
		$query->execute() or error(db_error($query));
		$log = $query->fetchAll(PDO::FETCH_ASSOC);
	} else {
		$log = array();
	}

	$user['boards'] = explode(',', $user['boards']);

	mod_page(_('Edit user'), $config['file_mod_user'], array(
		'user' => $user,
		'logs' => $log,
		'boards' => listBoards(),
		'token' => make_secure_link_token('users/' . $user['id'])
	));
}

function mod_user_new() {
	global $pdo, $config;

	if (!hasPermission($config['mod']['createusers']))
		error($config['error']['noaccess']);

	if (isset($_POST['username'], $_POST['password'], $_POST['type'])) {
		if ($_POST['username'] == '')
			error(sprintf($config['error']['required'], 'username'));
		if ($_POST['password'] == '')
			error(sprintf($config['error']['required'], 'password'));

		if (isset($_POST['allboards'])) {
			$boards = array('*');
		} else {
			$_boards = listBoards();
			foreach ($_boards as &$board) {
				$board = $board['uri'];
			}

			$boards = array();
			foreach ($_POST as $name => $value) {
				if (preg_match('/^board_(' . $config['board_regex'] . ')$/u', $name, $matches) && in_array($matches[1], $_boards))
					$boards[] = $matches[1];
			}
		}

		$type = (int)$_POST['type'];
		if (!isset($config['mod']['groups'][$type]) || $type == DISABLED)
			error(sprintf($config['error']['invalidfield'], 'type'));

		list($version, $password) = crypt_password($_POST['password']);

		$query = prepare('INSERT INTO ``mods`` VALUES (NULL, :username, :password, :version, :type, :boards)');
		$query->bindValue(':username', $_POST['username']);
		$query->bindValue(':password', $password);
		$query->bindValue(':version', $version);
		$query->bindValue(':type', $type);
		$query->bindValue(':boards', implode(',', $boards));
		$query->execute() or error(db_error($query));

		$userID = $pdo->lastInsertId();

		modLog('Created a new user: ' . utf8tohtml($_POST['username']) . ' <small>(#' . $userID . ')</small>');

		header('Location: ?/users', true, $config['redirect_http']);
		return;
	}

	mod_page(_('New user'), $config['file_mod_user'], array('new' => true, 'boards' => listBoards(), 'token' => make_secure_link_token('users/new')));
}


function mod_users() {
	global $config;

	if (!hasPermission($config['mod']['manageusers']))
		error($config['error']['noaccess']);

	$query = query("SELECT
		*,
		(SELECT `time` FROM ``modlogs`` WHERE `mod` = `id` ORDER BY `time` DESC LIMIT 1) AS `last`,
		(SELECT `text` FROM ``modlogs`` WHERE `mod` = `id` ORDER BY `time` DESC LIMIT 1) AS `action`
		FROM ``mods`` ORDER BY `type` DESC,`id`") or error(db_error());
	$users = $query->fetchAll(PDO::FETCH_ASSOC);

	foreach ($users as &$user) {
		$user['promote_token'] = make_secure_link_token("users/{$user['id']}/promote");
		$user['demote_token'] = make_secure_link_token("users/{$user['id']}/demote");
	}

	mod_page(sprintf('%s (%d)', _('Manage users'), count($users)), $config['file_mod_users'], array('users' => $users));
}

function mod_user_promote($uid, $action) {
	global $config;

	if (!hasPermission($config['mod']['promoteusers']))
		error($config['error']['noaccess']);

	$query = prepare("SELECT `type`, `username` FROM ``mods`` WHERE `id` = :id");
	$query->bindValue(':id', $uid);
	$query->execute() or error(db_error($query));

	if (!$mod = $query->fetch(PDO::FETCH_ASSOC))
		error($config['error']['404']);

	$new_group = false;

	$groups = $config['mod']['groups'];
	if ($action == 'demote')
		$groups = array_reverse($groups, true);

	foreach ($groups as $group_value => $group_name) {
		if ($action == 'promote' && $group_value > $mod['type']) {
			$new_group = $group_value;
			break;
		} elseif ($action == 'demote' && $group_value < $mod['type']) {
			$new_group = $group_value;
			break;
		}
	}

	if ($new_group === false || $new_group == DISABLED)
		error(_('Impossible to promote/demote user.'));

	$query = prepare("UPDATE ``mods`` SET `type` = :group_value WHERE `id` = :id");
	$query->bindValue(':id', $uid);
	$query->bindValue(':group_value', $new_group);
	$query->execute() or error(db_error($query));

	modLog(($action == 'promote' ? 'Promoted' : 'Demoted') . ' user "' .
		utf8tohtml($mod['username']) . '" to ' . $config['mod']['groups'][$new_group]);

	header('Location: ?/users', true, $config['redirect_http']);
}

function mod_pm($id, $reply = false) {
	global $mod, $config;

	if ($reply && !hasPermission($config['mod']['create_pm']))
		error($config['error']['noaccess']);

	$query = prepare("SELECT ``mods``.`username`, `mods_to`.`username` AS `to_username`, ``pms``.* FROM ``pms`` LEFT JOIN ``mods`` ON ``mods``.`id` = `sender` LEFT JOIN ``mods`` AS `mods_to` ON `mods_to`.`id` = `to` WHERE ``pms``.`id` = :id");
	$query->bindValue(':id', $id);
	$query->execute() or error(db_error($query));

	if ((!$pm = $query->fetch(PDO::FETCH_ASSOC)) || ($pm['to'] != $mod['id'] && !hasPermission($config['mod']['master_pm'])))
		error($config['error']['404']);

	if (isset($_POST['delete'])) {
		$query = prepare("DELETE FROM ``pms`` WHERE `id` = :id");
		$query->bindValue(':id', $id);
		$query->execute() or error(db_error($query));

		if ($config['cache']['enabled']) {
			cache::delete('pm_unread_' . $mod['id']);
			cache::delete('pm_unreadcount_' . $mod['id']);
		}

		header('Location: ?/', true, $config['redirect_http']);
		return;
	}

	if ($pm['unread'] && $pm['to'] == $mod['id']) {
		$query = prepare("UPDATE ``pms`` SET `unread` = 0 WHERE `id` = :id");
		$query->bindValue(':id', $id);
		$query->execute() or error(db_error($query));

		if ($config['cache']['enabled']) {
			cache::delete('pm_unread_' . $mod['id']);
			cache::delete('pm_unreadcount_' . $mod['id']);
		}

		modLog('Read a PM');
	}

	if ($reply) {
		if (!$pm['to_username'])
			error($config['error']['404']); // deleted?

		mod_page(sprintf('%s %s', _('New PM for'), $pm['to_username']), $config['file_mod_new_pm'], array(
			'username' => $pm['username'],
			'id' => $pm['sender'],
			'message' => quote($pm['message']),
			'token' => make_secure_link_token('new_PM/' . $pm['username'])
		));
	} else {
		mod_page(sprintf('%s &ndash; #%d', _('Private message'), $id), $config['file_mod_pm'], $pm);
	}
}

function mod_inbox() {
	global $config, $mod;

	$query = prepare('SELECT `unread`,``pms``.`id`, `time`, `sender`, `to`, `message`, `username` FROM ``pms`` LEFT JOIN ``mods`` ON ``mods``.`id` = `sender` WHERE `to` = :mod ORDER BY `unread` DESC, `time` DESC');
	$query->bindValue(':mod', $mod['id']);
	$query->execute() or error(db_error($query));
	$messages = $query->fetchAll(PDO::FETCH_ASSOC);

	$query = prepare('SELECT COUNT(*) FROM ``pms`` WHERE `to` = :mod AND `unread` = 1');
	$query->bindValue(':mod', $mod['id']);
	$query->execute() or error(db_error($query));
	$unread = $query->fetchColumn();

	foreach ($messages as &$message) {
		$message['snippet'] = pm_snippet($message['message']);
	}

	mod_page(sprintf('%s (%s)', _('PM inbox'), count($messages) > 0 ? $unread . ' unread' : 'empty'), $config['file_mod_inbox'], array(
		'messages' => $messages,
		'unread' => $unread
	));
}


function mod_new_pm($username) {
	global $config, $mod;

	if (!hasPermission($config['mod']['create_pm']))
		error($config['error']['noaccess']);

	$query = prepare("SELECT `id` FROM ``mods`` WHERE `username` = :username");
	$query->bindValue(':username', $username);
	$query->execute() or error(db_error($query));
	if (!$id = $query->fetchColumn()) {
		// Old style ?/PM: by user ID
		$query = prepare("SELECT `username` FROM ``mods`` WHERE `id` = :username");
		$query->bindValue(':username', $username);
		$query->execute() or error(db_error($query));
		if ($username = $query->fetchColumn())
			header('Location: ?/new_PM/' . $username, true, $config['redirect_http']);
		else
			error($config['error']['404']);
	}

	if (isset($_POST['message'])) {
		$_POST['message'] = escape_markup_modifiers($_POST['message']);
		markup($_POST['message']);

		$query = prepare("INSERT INTO ``pms`` VALUES (NULL, :me, :id, :message, :time, 1)");
		$query->bindValue(':me', $mod['id']);
		$query->bindValue(':id', $id);
		$query->bindValue(':message', $_POST['message']);
		$query->bindValue(':time', time());
		$query->execute() or error(db_error($query));

		if ($config['cache']['enabled']) {
			cache::delete('pm_unread_' . $id);
			cache::delete('pm_unreadcount_' . $id);
		}

		modLog('Sent a PM to ' . utf8tohtml($username));

		header('Location: ?/', true, $config['redirect_http']);
	}

	mod_page(sprintf('%s %s', _('New PM for'), $username), $config['file_mod_new_pm'], array(
		'username' => $username,
		'id' => $id,
		'token' => make_secure_link_token('new_PM/' . $username)
	));
}

function mod_rebuild() {
	global $config, $twig;

	if (!hasPermission($config['mod']['rebuild']))
		error($config['error']['noaccess']);

	if (isset($_POST['rebuild'])) {
		@set_time_limit($config['mod']['rebuild_timelimit']);

		$log = array();
		$boards = listBoards();
		$rebuilt_scripts = array();

		if (isset($_POST['rebuild_cache'])) {
			if ($config['cache']['enabled']) {
				$log[] = 'Flushing cache';
				Cache::flush();
			}

			$log[] = 'Clearing template cache';
			load_twig();
			$twig->getCache()->clear();
		}

		if (isset($_POST['rebuild_themes'])) {
			$log[] = 'Regenerating theme files';
			rebuildThemes('all');
		}

		if (isset($_POST['rebuild_javascript'])) {
			$log[] = 'Rebuilding <strong>' . $config['file_script'] . '</strong>';
			buildJavascript();
			$rebuilt_scripts[] = $config['file_script'];
		}

		foreach ($boards as $board) {
			if (!(isset($_POST['boards_all']) || isset($_POST['board_' . $board['uri']])))
				continue;

			openBoard($board['uri']);
			$config['try_smarter'] = false;

			if (isset($_POST['rebuild_index'])) {
				buildIndex();
				$log[] = '<strong>' . sprintf($config['board_abbreviation'], $board['uri']) . '</strong>: Creating index pages';
			}

			if (isset($_POST['rebuild_javascript']) && !in_array($config['file_script'], $rebuilt_scripts)) {
				$log[] = '<strong>' . sprintf($config['board_abbreviation'], $board['uri']) . '</strong>: Rebuilding <strong>' . $config['file_script'] . '</strong>';
				buildJavascript();
				$rebuilt_scripts[] = $config['file_script'];
			}

			if (isset($_POST['rebuild_thread'])) {
				$query = query(sprintf("SELECT `id` FROM ``posts_%s`` WHERE `thread` IS NULL", $board['uri'])) or error(db_error());
				while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
					$log[] = '<strong>' . sprintf($config['board_abbreviation'], $board['uri']) . '</strong>: Rebuilding thread #' . $post['id'];
					buildThread($post['id']);
				}
			}
		}

		mod_page(_('Rebuild'), $config['file_mod_rebuilt'], array('logs' => $log));
		return;
	}

	mod_page(_('Rebuild'), $config['file_mod_rebuild'], array(
		'boards' => listBoards(),
		'token' => make_secure_link_token('rebuild')
	));
}

function mod_reports() {
	global $config, $mod;

	if (!hasPermission($config['mod']['reports']))
		error($config['error']['noaccess']);

	$query = prepare("SELECT * FROM ``reports`` ORDER BY `time` DESC LIMIT :limit");
	$query->bindValue(':limit', $config['mod']['recent_reports'], PDO::PARAM_INT);
	$query->execute() or error(db_error($query));
	$reports = $query->fetchAll(PDO::FETCH_ASSOC);

	$report_queries = array();
	foreach ($reports as $report) {
		if (!isset($report_queries[$report['board']]))
			$report_queries[$report['board']] = array();
		$report_queries[$report['board']][] = $report['post'];
	}

	$report_posts = array();
	foreach ($report_queries as $board => $posts) {
		$report_posts[$board] = array();

		$query = query(sprintf('SELECT * FROM ``posts_%s`` WHERE `id` = ' . implode(' OR `id` = ', $posts), $board)) or error(db_error());
		while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
			$report_posts[$board][$post['id']] = $post;
		}
	}

	$count = 0;
	$body = '';
	foreach ($reports as $report) {
		if (!isset($report_posts[$report['board']][$report['post']])) {
			// // Invalid report (post has since been deleted)
			$query = prepare("DELETE FROM ``reports`` WHERE `post` = :id AND `board` = :board");
			$query->bindValue(':id', $report['post'], PDO::PARAM_INT);
			$query->bindValue(':board', $report['board']);
			$query->execute() or error(db_error($query));
			continue;
		}

		openBoard($report['board']);

		$post = &$report_posts[$report['board']][$report['post']];

		if (!$post['thread']) {
			// Still need to fix this:
			$po = new Thread($post, '?/', $mod, false);
		} else {
			$po = new Post($post, '?/', $mod);
		}

		// a little messy and inefficient
		$append_html = Element($config['file_mod_report'], array(
			'report' => $report,
			'config' => $config,
			'mod' => $mod,
			'token' => make_secure_link_token('reports/' . $report['id'] . '/dismiss'),
			'token_all' => make_secure_link_token('reports/' . $report['id'] . '/dismiss&all'),
			'token_post' => make_secure_link_token('reports/'. $report['id'] . '/dismiss&post'),
		));

		// Bug fix for https://github.com/savetheinternet/Tinyboard/issues/21
		$po->body = truncate($po->body, $po->link(), $config['body_truncate'] - substr_count($append_html, '<br>'));

		if (mb_strlen($po->body) + mb_strlen($append_html) > $config['body_truncate_char']) {
			// still too long; temporarily increase limit in the config
			$__old_body_truncate_char = $config['body_truncate_char'];
			$config['body_truncate_char'] = mb_strlen($po->body) + mb_strlen($append_html);
		}

		$po->body .= $append_html;

		$body .= $po->build(true) . '<hr>';

		if (isset($__old_body_truncate_char))
			$config['body_truncate_char'] = $__old_body_truncate_char;

		$count++;
	}

	mod_page(sprintf('%s (%d)', _('Report queue'), $count), $config['file_mod_reports'], array('reports' => $body, 'count' => $count));
}

function mod_report_dismiss($id, $action) {
	global $config;

	$query = prepare("SELECT `post`, `board`, `ip` FROM ``reports`` WHERE `id` = :id");
	$query->bindValue(':id', $id);
	$query->execute() or error(db_error($query));
	if ($report = $query->fetch(PDO::FETCH_ASSOC)) {
		$ip = $report['ip'];
		$board = $report['board'];
		$post = $report['post'];
	} else
		error($config['error']['404']);

	switch($action){
		case '&post':
			if (!hasPermission($config['mod']['report_dismiss_post'], $board))
				error($config['error']['noaccess']);

			$query = prepare("DELETE FROM ``reports`` WHERE `post` = :post");
			$query->bindValue(':post', $post);
			modLog("Dismissed all reports for post #{$id}", $board);
			break;
		case '&all':
			if (!hasPermission($config['mod']['report_dismiss_ip'], $board))
				error($config['error']['noaccess']);

			$query = prepare("DELETE FROM ``reports`` WHERE `ip` = :ip");
			$query->bindValue(':ip', $ip);
			$cip = cloak_ip($ip);
			modLog("Dismissed all reports by <a href=\"?/IP/$cip\">$cip</a>");
			break;
		case '':
		default:
			if (!hasPermission($config['mod']['report_dismiss'], $board))
				error($config['error']['noaccess']);

			$query = prepare("DELETE FROM ``reports`` WHERE `id` = :id");
			$query->bindValue(':id', $id);
			modLog("Dismissed a report for post #{$id}", $board);
			break;
	}
	$query->execute() or error(db_error($query));

	header('Location: ?/reports', true, $config['redirect_http']);
}

function mod_recent_posts($lim) {
	global $config, $mod, $pdo;

	if (!hasPermission($config['mod']['recent']))
		error($config['error']['noaccess']);

	$limit = (is_numeric($lim))? $lim : 25;
	$last_time = (isset($_GET['last']) && is_numeric($_GET['last'])) ? $_GET['last'] : 0;

	$mod_boards = array();
	$boards = listBoards();

	//if not all boards
	if ($mod['boards'][0]!='*') {
		foreach ($boards as $board) {
			if (in_array($board['uri'], $mod['boards']))
				$mod_boards[] = $board;
		}
	} else {
		$mod_boards = $boards;
	}

	// Manually build an SQL query
	$query = 'SELECT * FROM (';
	foreach ($mod_boards as $board) {
		$query .= sprintf('SELECT *, %s AS `board` FROM ``posts_%s`` UNION ALL ', $pdo->quote($board['uri']), $board['uri']);
	}
	// Remove the last "UNION ALL" seperator and complete the query
	$query = preg_replace('/UNION ALL $/', ') AS `all_posts` WHERE (`time` < :last_time OR NOT :last_time) ORDER BY `time` DESC LIMIT ' . $limit, $query);
	$query = prepare($query);
	$query->bindValue(':last_time', $last_time);
	$query->execute() or error(db_error($query));
	$posts = $query->fetchAll(PDO::FETCH_ASSOC);

	foreach ($posts as &$post) {
		openBoard($post['board']);
		if (!$post['thread']) {
			// Still need to fix this:
			$po = new Thread($post, '?/', $mod, false);
			$post['built'] = $po->build(true);
		} else {
			$po = new Post($post, '?/', $mod);
			$post['built'] = $po->build(true);
		}
		$last_time = $post['time'];
	}

	echo mod_page(_('Recent posts'), $config['file_mod_recent_posts'],  array(
			'posts' => $posts,
			'limit' => $limit,
			'last_time' => $last_time
		)
	);

}

function mod_config($board_config = false) {
	global $config, $mod, $board;

	if ($board_config && !openBoard($board_config))
		error($config['error']['noboard']);

	if (!hasPermission($config['mod']['edit_config'], $board_config))
		error($config['error']['noaccess']);

	$config_file = $board_config ? $board['dir'] . 'config.php' : 'inc/secrets.php';

	if ($config['mod']['config_editor_php']) {
		$readonly = !(is_file($config_file) ? is_writable($config_file) : is_writable(dirname($config_file)));

		if (!$readonly && isset($_POST['code'])) {
			$code = $_POST['code'];
			// Save previous instance_config if php_check_syntax fails
			$old_code = file_get_contents($config_file);
			file_put_contents($config_file, $code);
			$resp = shell_exec_error('php -l ' . $config_file);
			if (preg_match('/No syntax errors detected/', $resp)) {
				header('Location: ?/config' . ($board_config ? '/' . $board_config : ''), true, $config['redirect_http']);
				return;
			}
			else {
				file_put_contents($config_file, $old_code);
				error($config['error']['badsyntax'] . $resp);
			}
		}

		$instance_config = @file_get_contents($config_file);
		if ($instance_config === false) {
			$instance_config = "<?php\n\n// This file does not exist yet. You are creating it.";
		}
		$instance_config = str_replace("\n", '&#010;', utf8tohtml($instance_config));

		mod_page(_('Config editor'), $config['file_mod_config_editor_php'], array(
			'php' => $instance_config,
			'readonly' => $readonly,
			'boards' => listBoards(),
			'board' => $board_config,
			'file' => $config_file,
			'token' => make_secure_link_token('config' . ($board_config ? '/' . $board_config : ''))
		));
		return;
	}

	require_once 'inc/mod/config-editor.php';

	$conf = config_vars();

	foreach ($conf as &$var) {
		if (is_array($var['name'])) {
			$c = &$config;
			foreach ($var['name'] as $n)
				$c = &$c[$n];
		} else {
			$c = @$config[$var['name']];
		}

		$var['value'] = $c;
	}
	unset($var);

	if (isset($_POST['save'])) {
		$config_append = '';

		foreach ($conf as $var) {
			$field_name = 'cf_' . (is_array($var['name']) ? implode('/', $var['name']) : $var['name']);

			if ($var['type'] == 'boolean')
				$value = isset($_POST[$field_name]);
			elseif (isset($_POST[$field_name]))
				$value = $_POST[$field_name];
			else
				continue; // ???

			if (!settype($value, $var['type']))
				continue; // invalid

			if ($value != $var['value']) {
				// This value has been changed.

				$config_append .= '$config';

				if (is_array($var['name'])) {
					foreach ($var['name'] as $name)
						$config_append .= '[' . var_export($name, true) . ']';
				} else {
					$config_append .= '[' . var_export($var['name'], true) . ']';
				}


				$config_append .= ' = ';
				if (@$var['permissions'] && isset($config['mod']['groups'][$value])) {
					$config_append .= $config['mod']['groups'][$value];
				} else {
					$config_append .= var_export($value, true);
				}
				$config_append .= ";\n";
			}
		}

		if (!empty($config_append)) {
			$config_append = "\n// Changes made via web editor by \"" . $mod['username'] . "\" @ " . date('r') . ":\n" . $config_append . "\n";
			if (!is_file($config_file))
				$config_append = "<?php\n\n$config_append";
			if (!@file_put_contents($config_file, $config_append, FILE_APPEND)) {
				$config_append = htmlentities($config_append);

				if ($config['minify_html'])
					$config_append = str_replace("\n", '&#010;', $config_append);
				$page = array();
				$page['title'] = 'Cannot write to file!';
				$page['config'] = $config;
				$page['body'] = '
					<p style="text-align:center">Tinyboard could not write to <strong>' . $config_file . '</strong> with the ammended configuration, probably due to a permissions error.</p>
					<p style="text-align:center">You may proceed with these changes manually by copying and pasting the following code to the end of <strong>' . $config_file . '</strong>:</p>
					<textarea style="width:700px;height:370px;margin:auto;display:block;background:white;color:black" readonly>' . $config_append . '</textarea>
				';
				echo Element($config['file_page_template'], $page);
				exit;
			}
		}

		header('Location: ?/config' . ($board_config ? '/' . $board_config : ''), true, $config['redirect_http']);

		exit;
	}

	mod_page(_('Config editor') . ($board_config ? ': ' . sprintf($config['board_abbreviation'], $board_config) : ''),
		$config['file_mod_config_editor'], array(
			'boards' => listBoards(),
			'board' => $board_config,
			'conf' => $conf,
			'file' => $config_file,
			'token' => make_secure_link_token('config' . ($board_config ? '/' . $board_config : ''))
	));
}

function mod_themes_list() {
	global $config;

	if (!hasPermission($config['mod']['themes']))
		error($config['error']['noaccess']);

	if (!is_dir($config['dir']['themes']))
		error(_('Themes directory doesn\'t exist!'));
	if (!$dir = opendir($config['dir']['themes']))
		error(_('Cannot open themes directory; check permissions.'));

	$query = query('SELECT `theme` FROM ``theme_settings`` WHERE `name` IS NULL AND `value` IS NULL') or error(db_error());
	$themes_in_use = $query->fetchAll(PDO::FETCH_COLUMN);

	// Scan directory for themes
	$themes = array();
	while ($file = readdir($dir)) {
		if ($file[0] != '.' && is_dir($config['dir']['themes'] . '/' . $file)) {
			$themes[$file] = loadThemeConfig($file);
		}
	}
	closedir($dir);

	foreach ($themes as $theme_name => &$theme) {
		$theme['rebuild_token'] = make_secure_link_token('themes/' . $theme_name . '/rebuild');
		$theme['uninstall_token'] = make_secure_link_token('themes/' . $theme_name . '/uninstall');
	}

	mod_page(_('Manage themes'), $config['file_mod_themes'], array(
		'themes' => $themes,
		'themes_in_use' => $themes_in_use,
	));
}

function mod_theme_configure($theme_name) {
	global $config;

	if (!hasPermission($config['mod']['themes']))
		error($config['error']['noaccess']);

	if (!$theme = loadThemeConfig($theme_name)) {
		error($config['error']['invalidtheme']);
	}

	if (isset($_POST['install'])) {
		// Check if everything is submitted
		foreach ($theme['config'] as &$conf) {
			if (!isset($_POST[$conf['name']]) && $conf['type'] != 'checkbox')
				error(sprintf($config['error']['required'], $c['title']));
		}

		// Clear previous settings
		$query = prepare("DELETE FROM ``theme_settings`` WHERE `theme` = :theme");
		$query->bindValue(':theme', $theme_name);
		$query->execute() or error(db_error($query));

		foreach ($theme['config'] as &$conf) {
			$query = prepare("INSERT INTO ``theme_settings`` VALUES(:theme, :name, :value)");
			$query->bindValue(':theme', $theme_name);
			$query->bindValue(':name', $conf['name']);
			if ($conf['type'] == 'checkbox')
				$query->bindValue(':value', isset($_POST[$conf['name']]) ? 1 : 0);
			else
				$query->bindValue(':value', $_POST[$conf['name']]);
			$query->execute() or error(db_error($query));
		}

		$query = prepare("INSERT INTO ``theme_settings`` VALUES(:theme, NULL, NULL)");
		$query->bindValue(':theme', $theme_name);
		$query->execute() or error(db_error($query));

		// Clean cache
		Cache::delete("themes");
		Cache::delete("theme_settings_".$theme_name);

		$result = true;
		$message = false;
		if (isset($theme['install_callback'])) {
			$ret = $theme['install_callback'](themeSettings($theme_name));
			if ($ret && !empty($ret)) {
				if (is_array($ret) && count($ret) == 2) {
					$result = $ret[0];
					$message = $ret[1];
				}
			}
		}

		if (!$result) {
			// Install failed
			$query = prepare("DELETE FROM ``theme_settings`` WHERE `theme` = :theme");
			$query->bindValue(':theme', $theme_name);
			$query->execute() or error(db_error($query));
		}

		// Build themes
		rebuildThemes('all');

		mod_page(sprintf(_($result ? 'Installed theme: %s' : 'Installation failed: %s'), $theme['name']), $config['file_mod_theme_installed'], array(
			'theme_name' => $theme_name,
			'theme' => $theme,
			'result' => $result,
			'message' => $message
		));
		return;
	}

	$settings = themeSettings($theme_name);

	mod_page(sprintf(_('Configuring theme: %s'), $theme['name']), $config['file_mod_theme_config'], array(
		'theme_name' => $theme_name,
		'theme' => $theme,
		'settings' => $settings,
		'token' => make_secure_link_token('themes/' . $theme_name)
	));
}

function mod_theme_uninstall($theme_name) {
	global $config;

	if (!hasPermission($config['mod']['themes']))
		error($config['error']['noaccess']);

	$query = prepare("DELETE FROM ``theme_settings`` WHERE `theme` = :theme");
	$query->bindValue(':theme', $theme_name);
	$query->execute() or error(db_error($query));

	// Clean cache
	Cache::delete("themes");
	Cache::delete("theme_settings_".$theme_name);

	header('Location: ?/themes', true, $config['redirect_http']);
}

function mod_theme_rebuild($theme_name) {
	global $config;

	if (!hasPermission($config['mod']['themes']))
		error($config['error']['noaccess']);

	rebuildTheme($theme_name, 'all');

	mod_page(sprintf(_('Rebuilt theme: %s'), $theme_name), $config['file_mod_theme_rebuilt'], array(
		'theme_name' => $theme_name,
	));
}

// This needs to be done for `secure` CSRF prevention compatibility, otherwise the $board will be read in as the token if editing global pages.
function delete_page_base($page = '', $board = false) {
	global $config, $mod;

	if (empty($board))
		$board = false;

	if (!$board && $mod['boards'][0] !== '*')
		error($config['error']['noaccess']);

	if (!hasPermission($config['mod']['edit_pages'], $board))
		error($config['error']['noaccess']);

	if ($board !== FALSE && !openBoard($board))
		error($config['error']['noboard']);

	if ($board) {
		$query = prepare('DELETE FROM ``pages`` WHERE `board` = :board AND `name` = :name');
		$query->bindValue(':board', ($board ? $board : NULL));
	} else {
		$query = prepare('DELETE FROM ``pages`` WHERE `board` IS NULL AND `name` = :name');
	}
	$query->bindValue(':name', $page);
	$query->execute() or error(db_error($query));

	header('Location: ?/edit_pages' . ($board ? ('/' . $board) : ''), true, $config['redirect_http']);
}

function mod_delete_page($page = '') {
	delete_page_base($page);
}

function mod_delete_page_board($page = '', $board = false) {
	delete_page_base($page, $board);
}

function mod_edit_page($id) {
	global $config, $mod, $board;

	$query = prepare('SELECT * FROM ``pages`` WHERE `id` = :id');
	$query->bindValue(':id', $id);
	$query->execute() or error(db_error($query));
	$page = $query->fetch();

	if (!$page)
		error(_('Could not find the page you are trying to edit.'));

	if (!$page['board'] && $mod['boards'][0] !== '*')
		error($config['error']['noaccess']);

	if (!hasPermission($config['mod']['edit_pages'], $page['board']))
		error($config['error']['noaccess']);

	if ($page['board'] && !openBoard($page['board']))
		error($config['error']['noboard']);

	if (isset($_POST['method'], $_POST['content'])) {
		$content = $_POST['content'];
		$method = $_POST['method'];
		$page['type'] = $method;

		if (!in_array($method, array('markdown', 'html', 'infinity')))
			error(_('Unrecognized page markup method.'));

		switch ($method) {
			case 'markdown':
				$write = markdown($content);
				break;
			case 'html':
				if (hasPermission($config['mod']['rawhtml'])) {
					$write = $content;
				} else {
					$write = purify_html($content);
				}
				break;
			case 'infinity':
				$c = $content;
				markup($content);
				$write = $content;
				$content = $c;
		}

		if (!isset($write) or !$write)
			error(_('Failed to mark up your input for some reason...'));

		$query = prepare('UPDATE ``pages`` SET `type` = :method, `content` = :content WHERE `id` = :id');
		$query->bindValue(':method', $method);
		$query->bindValue(':content', $content);
		$query->bindValue(':id', $id);
		$query->execute() or error(db_error($query));

		$fn = (isset($board['uri']) ? ($board['uri'] . '/') : '') . $page['name'] . '.html';
		$body = "<div class='ban'>$write</div>";
		$html = Element($config['file_page_template'], array('config' => $config, 'boardlist' => createBoardlist(), 'body' => $body, 'title' => utf8tohtml($page['title'])));
		file_write($fn, $html);
	}

	if (!isset($content)) {
		$query = prepare('SELECT `content` FROM ``pages`` WHERE `id` = :id');
		$query->bindValue(':id', $id);
		$query->execute() or error(db_error($query));
		$content = $query->fetchColumn();
	}

	mod_page(sprintf(_('Editing static page: %s'), $page['name']), $config['file_mod_edit_page'], array('page' => $page, 'token' => make_secure_link_token("edit_page/$id"), 'content' => prettify_textarea($content), 'board' => $board));
}

function mod_pages($board = false) {
	global $config, $mod, $pdo;

	if (empty($board))
		$board = false;

	if (!$board && $mod['boards'][0] !== '*')
		error($config['error']['noaccess']);

	if (!hasPermission($config['mod']['edit_pages'], $board))
		error($config['error']['noaccess']);

	if ($board !== FALSE && !openBoard($board))
		error($config['error']['noboard']);

	if ($board) {
		$query = prepare('SELECT * FROM ``pages`` WHERE `board` = :board');
		$query->bindValue(':board', $board);
	} else {
		$query = query('SELECT * FROM ``pages`` WHERE `board` IS NULL');
	}
	$query->execute() or error(db_error($query));
	$pages = $query->fetchAll(PDO::FETCH_ASSOC);

	if (isset($_POST['page'])) {
		if ($board and sizeof($pages) > $config['pages_max'])
			error(sprintf(_('Sorry, this site only allows %d pages per board.'), $config['pages_max']));

		if (!preg_match('/^[a-z0-9]{1,255}$/', $_POST['page']))
			error(_('Page names must be < 255 chars and may only contain lowercase letters A-Z and digits 1-9.'));

		foreach ($pages as $i => $p) {
			if ($_POST['page'] === $p['name'])
				error(_('Refusing to create a new page with the same name as an existing one.'));
		}

		$title = ($_POST['title'] ? $_POST['title'] : NULL);

		$query = prepare('INSERT INTO ``pages``(board, title, name) VALUES(:board, :title, :name)');
		$query->bindValue(':board', ($board ? $board : NULL));
		$query->bindValue(':title', $title);
		$query->bindValue(':name', $_POST['page']);
		$query->execute() or error(db_error($query));

		$pages[] = array('id' => $pdo->lastInsertId(), 'name' => $_POST['page'], 'board' => $board, 'title' => $title);
	}

	foreach ($pages as $i => &$p) {
		$p['delete_token'] = make_secure_link_token('edit_pages/delete/' . $p['name'] . ($board ? ('/' . $board) : ''));
	}

	mod_page(_('Pages'), $config['file_mod_pages'], array('pages' => $pages, 'token' => make_secure_link_token('edit_pages' . ($board ? ('/' . $board) : '')), 'board' => $board));
}

function mod_debug_antispam() {
	global $pdo, $config;

	$args = array();

	if (isset($_POST['board'], $_POST['thread'])) {
		$where = '`board` = ' . $pdo->quote($_POST['board']);
		if ($_POST['thread'] != '')
			$where .= ' AND `thread` = ' . $pdo->quote($_POST['thread']);

		if (isset($_POST['purge'])) {
			$query = prepare(', DATE ``antispam`` SET `expires` = UNIX_TIMESTAMP() + :expires WHERE' . $where);
			$query->bindValue(':expires', $config['spam']['hidden_inputs_expire']);
			$query->execute() or error(db_error());
		}

		$args['board'] = $_POST['board'];
		$args['thread'] = $_POST['thread'];
	} else {
		$where = '';
	}

	$query = query('SELECT COUNT(*) FROM ``antispam``' . ($where ? " WHERE $where" : '')) or error(db_error());
	$args['total'] = number_format($query->fetchColumn());

	$query = query('SELECT COUNT(*) FROM ``antispam`` WHERE `expires` IS NOT NULL' . ($where ? " AND $where" : '')) or error(db_error());
	$args['expiring'] = number_format($query->fetchColumn());

	$query = query('SELECT * FROM ``antispam`` ' . ($where ? "WHERE $where" : '') . ' ORDER BY `passed` DESC LIMIT 40') or error(db_error());
	$args['top'] = $query->fetchAll(PDO::FETCH_ASSOC);

	$query = query('SELECT * FROM ``antispam`` ' . ($where ? "WHERE $where" : '') . ' ORDER BY `created` DESC LIMIT 20') or error(db_error());
	$args['recent'] = $query->fetchAll(PDO::FETCH_ASSOC);

	mod_page(_('Debug: Anti-spam'), $config['file_mod_debug_antispam'], $args);
}

function mod_debug_recent_posts() {
	global $pdo, $config;

	$limit = 500;

	$boards = listBoards();

	// Manually build an SQL query
	$query = 'SELECT * FROM (';
	foreach ($boards as $board) {
		$query .= sprintf('SELECT *, %s AS `board` FROM ``posts_%s`` UNION ALL ', $pdo->quote($board['uri']), $board['uri']);
	}
	// Remove the last "UNION ALL" seperator and complete the query
	$query = preg_replace('/UNION ALL $/', ') AS `all_posts` ORDER BY `time` DESC LIMIT ' . $limit, $query);
	$query = query($query) or error(db_error());
	$posts = $query->fetchAll(PDO::FETCH_ASSOC);

	// Fetch recent posts from flood prevention cache
	$query = query("SELECT * FROM ``flood`` ORDER BY `time` DESC") or error(db_error());
	$flood_posts = $query->fetchAll(PDO::FETCH_ASSOC);

	foreach ($posts as &$post) {
		$post['snippet'] = pm_snippet($post['body']);
		foreach ($flood_posts as $flood_post) {
			if ($flood_post['time'] == $post['time'] &&
				$flood_post['posthash'] == make_comment_hex($post['body_nomarkup']) &&
				$flood_post['filehash'] == $post['filehash'])
				$post['in_flood_table'] = true;
		}
	}

	mod_page(_('Debug: Recent posts'), $config['file_mod_debug_recent_posts'], array('posts' => $posts, 'flood_posts' => $flood_posts));
}

function mod_debug_sql() {
	global $config;

	if (!hasPermission($config['mod']['debug_sql']))
		error($config['error']['noaccess']);

	$args['security_token'] = make_secure_link_token('debug/sql');

	if (isset($_POST['query'])) {
		$args['query'] = $_POST['query'];
		if ($query = query($_POST['query'])) {
			$args['result'] = $query->fetchAll(PDO::FETCH_ASSOC);
			if (!empty($args['result']))
				$args['keys'] = array_keys($args['result'][0]);
			else
				$args['result'] = 'empty';
		} else {
			$args['error'] = db_error();
		}
	}

	mod_page(_('Debug: SQL'), $config['file_mod_debug_sql'], $args);
}
