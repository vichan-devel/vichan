<?php

/*
 *  Copyright (c) 2010-2013 Tinyboard Development Group
 */

if (realpath($_SERVER['SCRIPT_FILENAME']) == str_replace('\\', '/', __FILE__)) {
	// You cannot request this file directly.
	exit;
}

require_once 'inc/display.php';
require_once 'inc/template.php';
require_once 'inc/database.php';
require_once 'inc/events.php';
require_once 'inc/api.php';
require_once 'inc/lib/gettext/gettext.inc';

// the user is not currently logged in as a moderator
$mod = false;

register_shutdown_function('fatal_error_handler');
mb_internal_encoding('UTF-8');
loadConfig();

function loadConfig() {
	global $board, $config, $__ip, $debug, $__version;

	$error = function_exists('error') ? 'error' : 'basic_error_function_because_the_other_isnt_loaded_yet';

	reset_events();

	if (!isset($_SERVER['REMOTE_ADDR']))
		$_SERVER['REMOTE_ADDR'] = '0.0.0.0';

	$arrays = array(
		'db',
		'cache',
		'cookies',
		'error',
		'dir',
		'mod',
		'spam',
		'filters',
		'wordfilters',
		'custom_capcode',
		'custom_tripcode',
		'dnsbl',
		'dnsbl_exceptions',
		'remote',
		'allowed_ext',
		'allowed_ext_files',
		'file_icons',
		'footer',
		'stylesheets',
		'additional_javascript',
		'markup',
		'custom_pages',
		'dashboard_links'
	);

	$config = array();
	foreach ($arrays as $key) {
		$config[$key] = array();
	}

	require 'inc/config.php';
	if (!file_exists('inc/instance-config.php'))
		$error('Tinyboard is not configured! Create inc/instance-config.php.');

	require 'inc/instance-config.php';

	if (isset($board['dir']) && file_exists($board['dir'] . '/config.php')) {
		require $board['dir'] . '/config.php';
	}

	if (!isset($__version))
		$__version = file_exists('.installed') ? trim(file_get_contents('.installed')) : false;
	$config['version'] = $__version;

	if ($config['debug']) {
		if (!isset($debug)) {
			$debug = array('sql' => array(), 'exec' => array(), 'purge' => array(), 'cached' => array(), 'write' => array());
			$debug['start'] = microtime(true);
		}
	}

	date_default_timezone_set($config['timezone']);

	if (!isset($config['global_message']))
		$config['global_message'] = false;

	if (!isset($config['post_url']))
		$config['post_url'] = $config['root'] . $config['file_post'];

	if (!isset($config['referer_match']))
		if (isset($_SERVER['HTTP_HOST'])) {
			$config['referer_match'] = '/^' .
				(preg_match($config['url_regex'], $config['root']) ? '' :
					'https?:\/\/' . $_SERVER['HTTP_HOST']) .
					preg_quote($config['root'], '/') .
				'(' .
						str_replace('%s', $config['board_regex'], preg_quote($config['board_path'], '/')) .
						'(' .
							preg_quote($config['file_index'], '/') . '|' .
							str_replace('%d', '\d+', preg_quote($config['file_page'])) .
						')?' .
					'|' .
						str_replace('%s', $config['board_regex'], preg_quote($config['board_path'], '/')) .
						preg_quote($config['dir']['res'], '/') .
						'(' .
							str_replace('%d', '\d+', preg_quote($config['file_page'], '/')) . '|' .
							str_replace('%d', '\d+', preg_quote($config['file_page50'], '/')) .
						')' .
					'|' .
						preg_quote($config['file_mod'], '/') . '\?\/.+' .
				')([#?](.+)?)?$/ui';
		} else {
			// CLI mode
			$config['referer_match'] = '//';
		}
	if (!isset($config['cookies']['path']))
		$config['cookies']['path'] = &$config['root'];

	if (!isset($config['dir']['static']))
		$config['dir']['static'] = $config['root'] . 'static/';

	if (!isset($config['image_sticky']))
		$config['image_sticky'] = $config['dir']['static'] . 'sticky.gif';
	if (!isset($config['image_locked']))
		$config['image_locked'] = $config['dir']['static'] . 'locked.gif';
	if (!isset($config['image_bumplocked']))
		$config['image_bumplocked'] = $config['dir']['static'] . 'sage.gif';
	if (!isset($config['image_deleted']))
		$config['image_deleted'] = $config['dir']['static'] . 'deleted.png';

	if (!isset($config['uri_thumb']))
		$config['uri_thumb'] = $config['root'] . $board['dir'] . $config['dir']['thumb'];
	elseif (isset($board['dir']))
		$config['uri_thumb'] = sprintf($config['uri_thumb'], $board['dir']);

	if (!isset($config['uri_img']))
		$config['uri_img'] = $config['root'] . $board['dir'] . $config['dir']['img'];
	elseif (isset($board['dir']))
		$config['uri_img'] = sprintf($config['uri_img'], $board['dir']);

	if (!isset($config['uri_stylesheets']))
		$config['uri_stylesheets'] = $config['root'] . 'stylesheets/';

	if (!isset($config['url_stylesheet']))
		$config['url_stylesheet'] = $config['uri_stylesheets'] . 'style.css';
	if (!isset($config['url_javascript']))
		$config['url_javascript'] = $config['root'] . $config['file_script'];
	if (!isset($config['additional_javascript_url']))
		$config['additional_javascript_url'] = $config['root'];
	if (!isset($config['uri_flags']))
		$config['uri_flags'] = $config['root'] . 'static/flags/%s.png';

	if ($config['root_file']) {
		chdir($config['root_file']);
	}

	if ($config['verbose_errors']) {
		set_error_handler('verbose_error_handler');
		error_reporting(E_ALL);
		ini_set('display_errors', true);
		ini_set('html_errors', false);
	}

	// Keep the original address to properly comply with other board configurations
	if (!isset($__ip))
		$__ip = $_SERVER['REMOTE_ADDR'];

	// ::ffff:0.0.0.0
	if (preg_match('/^\:\:(ffff\:)?(\d+\.\d+\.\d+\.\d+)$/', $__ip, $m))
		$_SERVER['REMOTE_ADDR'] = $m[2];

	if (_setlocale(LC_ALL, $config['locale']) === false) {
		$error('The specified locale (' . $config['locale'] . ') does not exist on your platform!');
	}

	if (extension_loaded('gettext')) {
		bindtextdomain('tinyboard', './inc/locale');
		bind_textdomain_codeset('tinyboard', 'UTF-8');
		textdomain('tinyboard');
	} else {
		_bindtextdomain('tinyboard', './inc/locale');
		_bind_textdomain_codeset('tinyboard', 'UTF-8');
		_textdomain('tinyboard');
	}


	if ($config['syslog'])
		openlog('tinyboard', LOG_ODELAY, LOG_SYSLOG); // open a connection to sysem logger

	if ($config['recaptcha'])
		require_once 'inc/lib/recaptcha/recaptchalib.php';
	if ($config['cache']['enabled'])
		require_once 'inc/cache.php';
	event('load-config');
}

function basic_error_function_because_the_other_isnt_loaded_yet($message, $priority = true) {
	global $config;

	if ($config['syslog'] && $priority !== false) {
		// Use LOG_NOTICE instead of LOG_ERR or LOG_WARNING because most error message are not significant.
		_syslog($priority !== true ? $priority : LOG_NOTICE, $message);
	}

	// Yes, this is horrible.
	die('<!DOCTYPE html><html><head><title>Error</title>' .
		'<style type="text/css">' .
			'body{text-align:center;font-family:arial, helvetica, sans-serif;font-size:10pt;}' .
			'p{padding:0;margin:20px 0;}' .
			'p.c{font-size:11px;}' .
		'</style></head>' .
		'<body><h2>Error</h2>' . $message . '<hr/>' .
		'<p class="c">This alternative error page is being displayed because the other couldn\'t be found or hasn\'t loaded yet.</p></body></html>');
}

function fatal_error_handler() { 
	if ($error = error_get_last()) {
		if ($error['type'] == E_ERROR) {
			if (function_exists('error')) {
				error('Caught fatal error: ' . $error['message'] . ' in <strong>' . $error['file'] . '</strong> on line ' . $error['line'], LOG_ERR);
			} else {
				basic_error_function_because_the_other_isnt_loaded_yet('Caught fatal error: ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line'], LOG_ERR);
			}
		}
	}
}

function _syslog($priority, $message) {
	if (isset($_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'])) {
		// CGI
		syslog($priority, $message . ' - client: ' . $_SERVER['REMOTE_ADDR'] . ', request: "' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'] . '"');
	} else {
		syslog($priority, $message);
	}
}

function verbose_error_handler($errno, $errstr, $errfile, $errline) {
	if (error_reporting() == 0)
		return false; // Looks like this warning was suppressed by the @ operator.
	error(utf8tohtml($errstr), true, array(
		'file' => $errfile . ':' . $errline,
		'errno' => $errno,
		'error' => $errstr,
		'backtrace' => array_slice(debug_backtrace(), 1)
	));
}

function create_antibot($board, $thread = null) {
	require_once dirname(__FILE__) . '/anti-bot.php';

	return _create_antibot($board, $thread);
}

function rebuildThemes($action, $board = false) {
	// List themes
	$query = query("SELECT `theme` FROM ``theme_settings`` WHERE `name` IS NULL AND `value` IS NULL") or error(db_error());

	while ($theme = $query->fetch(PDO::FETCH_ASSOC)) {
		rebuildTheme($theme['theme'], $action, $board);
	}
}


function loadThemeConfig($_theme) {
	global $config;

	if (!file_exists($config['dir']['themes'] . '/' . $_theme . '/info.php'))
		return false;

	// Load theme information into $theme
	include $config['dir']['themes'] . '/' . $_theme . '/info.php';

	return $theme;
}

function rebuildTheme($theme, $action, $board = false) {
	global $config, $_theme;
	$_theme = $theme;

	$theme = loadThemeConfig($_theme);

	if (file_exists($config['dir']['themes'] . '/' . $_theme . '/theme.php')) {
		require_once $config['dir']['themes'] . '/' . $_theme . '/theme.php';

		$theme['build_function']($action, themeSettings($_theme), $board);
	}
}


function themeSettings($theme) {
	$query = prepare("SELECT `name`, `value` FROM ``theme_settings`` WHERE `theme` = :theme AND `name` IS NOT NULL");
	$query->bindValue(':theme', $theme);
	$query->execute() or error(db_error($query));

	$settings = array();
	while ($s = $query->fetch(PDO::FETCH_ASSOC)) {
		$settings[$s['name']] = $s['value'];
	}

	return $settings;
}

function sprintf3($str, $vars, $delim = '%') {
	$replaces = array();
	foreach ($vars as $k => $v) {
		$replaces[$delim . $k . $delim] = $v;
	}
	return str_replace(array_keys($replaces),
					   array_values($replaces), $str);
}

function mb_substr_replace($string, $replacement, $start, $length) {
	return mb_substr($string, 0, $start) . $replacement . mb_substr($string, $start + $length);
}

function setupBoard($array) {
	global $board, $config;

	$board = array(
		'uri' => $array['uri'],
		'title' => $array['title'],
		'subtitle' => $array['subtitle']
	);

	// older versions
	$board['name'] = &$board['title'];

	$board['dir'] = sprintf($config['board_path'], $board['uri']);
	$board['url'] = sprintf($config['board_abbreviation'], $board['uri']);

	loadConfig();

	if (!file_exists($board['dir']))
		@mkdir($board['dir'], 0777) or error("Couldn't create " . $board['dir'] . ". Check permissions.", true);
	if (!file_exists($board['dir'] . $config['dir']['img']))
		@mkdir($board['dir'] . $config['dir']['img'], 0777)
			or error("Couldn't create " . $board['dir'] . $config['dir']['img'] . ". Check permissions.", true);
	if (!file_exists($board['dir'] . $config['dir']['thumb']))
		@mkdir($board['dir'] . $config['dir']['thumb'], 0777)
			or error("Couldn't create " . $board['dir'] . $config['dir']['img'] . ". Check permissions.", true);
	if (!file_exists($board['dir'] . $config['dir']['res']))
		@mkdir($board['dir'] . $config['dir']['res'], 0777)
			or error("Couldn't create " . $board['dir'] . $config['dir']['img'] . ". Check permissions.", true);
}

function openBoard($uri) {
	global $config, $build_pages;

	if ($config['try_smarter'])
		$build_pages = array();

	$board = getBoardInfo($uri);
	if ($board) {
		setupBoard($board);
		return true;
	}
	return false;
}

function getBoardInfo($uri) {
	global $config;

	if ($config['cache']['enabled'] && ($board = cache::get('board_' . $uri))) {
		return $board;
	}

	$query = prepare("SELECT * FROM ``boards`` WHERE `uri` = :uri LIMIT 1");
	$query->bindValue(':uri', $uri);
	$query->execute() or error(db_error($query));

	if ($board = $query->fetch(PDO::FETCH_ASSOC)) {
		if ($config['cache']['enabled'])
			cache::set('board_' . $uri, $board);
		return $board;
	}

	return false;
}

function boardTitle($uri) {
	$board = getBoardInfo($uri);
	if ($board)
		return $board['title'];
	return false;
}

function purge($uri) {
	global $config, $debug;

	// Fix for Unicode
	$uri = rawurlencode($uri); 

	$noescape = "/!~*()+:";
	$noescape = preg_split('//', $noescape);
	$noescape_url = array_map("rawurlencode", $noescape);
	$uri = str_replace($noescape_url, $noescape, $uri);

	if (preg_match($config['referer_match'], $config['root']) && isset($_SERVER['REQUEST_URI'])) {
		$uri = (str_replace('\\', '/', dirname($_SERVER['REQUEST_URI'])) == '/' ? '/' : str_replace('\\', '/', dirname($_SERVER['REQUEST_URI'])) . '/') . $uri;
	} else {
		$uri = $config['root'] . $uri;
	}

	if ($config['debug']) {
		$debug['purge'][] = $uri;
	}

	foreach ($config['purge'] as &$purge) {
		$host = &$purge[0];
		$port = &$purge[1];
		$http_host = isset($purge[2]) ? $purge[2] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
		$request = "PURGE {$uri} HTTP/1.1\r\nHost: {$http_host}\r\nUser-Agent: Tinyboard\r\nConnection: Close\r\n\r\n";
		if ($fp = fsockopen($host, $port, $errno, $errstr, $config['purge_timeout'])) {
			fwrite($fp, $request);
			fclose($fp);
		} else {
			// Cannot connect?
			error('Could not PURGE for ' . $host);
		}
	}
}

function file_write($path, $data, $simple = false, $skip_purge = false) {
	global $config, $debug;

	if (preg_match('/^remote:\/\/(.+)\:(.+)$/', $path, $m)) {
		if (isset($config['remote'][$m[1]])) {
			require_once 'inc/remote.php';

			$remote = new Remote($config['remote'][$m[1]]);
			$remote->write($data, $m[2]);
			return;
		} else {
			error('Invalid remote server: ' . $m[1]);
		}
	}

	if (!$fp = fopen($path, $simple ? 'w' : 'c'))
		error('Unable to open file for writing: ' . $path);

	// File locking
	if (!$simple && !flock($fp, LOCK_EX)) {
		error('Unable to lock file: ' . $path);
	}

	// Truncate file
	if (!$simple && !ftruncate($fp, 0))
		error('Unable to truncate file: ' . $path);

	// Write data
	if (($bytes = fwrite($fp, $data)) === false)
		error('Unable to write to file: ' . $path);

	// Unlock
	if (!$simple)
		flock($fp, LOCK_UN);

	// Close
	if (!fclose($fp))
		error('Unable to close file: ' . $path);

	if (!$skip_purge && isset($config['purge'])) {
		// Purge cache
		if (basename($path) == $config['file_index']) {
			// Index file (/index.html); purge "/" as well
			$uri = dirname($path);
			// root
			if ($uri == '.')
				$uri = '';
			else
				$uri .= '/';
			purge($uri);
		}
		purge($path);
	}

	if ($config['debug']) {
		$debug['write'][] = $path . ': ' . $bytes . ' bytes';
	}

	event('write', $path);
}

function file_unlink($path) {
	global $config, $debug;

	if ($config['debug']) {
		if (!isset($debug['unlink']))
			$debug['unlink'] = array();
		$debug['unlink'][] = $path;
	}

	$ret = @unlink($path);
	if (isset($config['purge']) && $path[0] != '/' && isset($_SERVER['HTTP_HOST'])) {
		// Purge cache
		if (basename($path) == $config['file_index']) {
			// Index file (/index.html); purge "/" as well
			$uri = dirname($path);
			// root
			if ($uri == '.')
				$uri = '';
			else
				$uri .= '/';
			purge($uri);
		}
		purge($path);
	}

	event('unlink', $path);

	return $ret;
}

function hasPermission($action = null, $board = null, $_mod = null) {
	global $config;

	if (isset($_mod))
		$mod = &$_mod;
	else
		global $mod;

	if (!is_array($mod))
		return false;

	if (isset($action) && $mod['type'] < $action)
		return false;

	if (!isset($board) || $config['mod']['skip_per_board'])
		return true;

	if (!isset($mod['boards']))
		return false;

	if (!in_array('*', $mod['boards']) && !in_array($board, $mod['boards']))
		return false;

	return true;
}

function listBoards() {
	global $config;

	if ($config['cache']['enabled'] && ($boards = cache::get('all_boards')))
		return $boards;

	$query = query("SELECT * FROM ``boards`` ORDER BY `uri`") or error(db_error());
	$boards = $query->fetchAll();

	if ($config['cache']['enabled'])
		cache::set('all_boards', $boards);

	return $boards;
}

function checkFlood($post) {
	global $board, $config;

	$query = prepare(sprintf("SELECT * FROM ``posts_%s`` WHERE (`ip` = :ip AND `time` >= :floodtime) OR (`ip` = :ip AND `body` != '' AND `body` = :body AND `time` >= :floodsameiptime) OR (`body` != ''  AND `body` = :body AND `time` >= :floodsametime) LIMIT 1", $board['uri']));
	$query->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
	$query->bindValue(':body', $post['body']);
	$query->bindValue(':floodtime', time()-$config['flood_time'], PDO::PARAM_INT);
	$query->bindValue(':floodsameiptime', time()-$config['flood_time_ip'], PDO::PARAM_INT);
	$query->bindValue(':floodsametime', time()-$config['flood_time_same'], PDO::PARAM_INT);
	$query->execute() or error(db_error($query));

	$flood = (bool) $query->fetch(PDO::FETCH_ASSOC);

	if (event('check-flood', $post))
		return true;

	return $flood;
}

function until($timestamp) {
	$difference = $timestamp - time();
	if ($difference < 60) {
		return $difference . ' ' . ngettext('second', 'seconds', $difference);
	} elseif ($difference < 60*60) {
		return ($num = round($difference/(60))) . ' ' . ngettext('minute', 'minutes', $num);
	} elseif ($difference < 60*60*24) {
		return ($num = round($difference/(60*60))) . ' ' . ngettext('hour', 'hours', $num);
	} elseif ($difference < 60*60*24*7) {
		return ($num = round($difference/(60*60*24))) . ' ' . ngettext('day', 'days', $num);
	} elseif ($difference < 60*60*24*365) {
		return ($num = round($difference/(60*60*24*7))) . ' ' . ngettext('week', 'weeks', $num);
	}

	return ($num = round($difference/(60*60*24*365))) . ' ' . ngettext('year', 'years', $num);
}

function ago($timestamp) {
	$difference = time() - $timestamp;
	if ($difference < 60) {
		return $difference . ' ' . ngettext('second', 'seconds', $difference);
	} elseif ($difference < 60*60) {
		return ($num = round($difference/(60))) . ' ' . ngettext('minute', 'minutes', $num);
	} elseif ($difference < 60*60*24) {
		return ($num = round($difference/(60*60))) . ' ' . ngettext('hour', 'hours', $num);
	} elseif ($difference < 60*60*24*7) {
		return ($num = round($difference/(60*60*24))) . ' ' . ngettext('day', 'days', $num);
	} elseif ($difference < 60*60*24*365) {
		return ($num = round($difference/(60*60*24*7))) . ' ' . ngettext('week', 'weeks', $num);
	}

	return ($num = round($difference/(60*60*24*365))) . ' ' . ngettext('year', 'years', $num);
}

function displayBan($ban) {
	global $config;

	if (!$ban['seen']) {
		$query = prepare("UPDATE ``bans`` SET `seen` = 1 WHERE `id` = :id");
		$query->bindValue(':id', $ban['id'], PDO::PARAM_INT);
		$query->execute() or error(db_error($query));
	}

	$ban['ip'] = $_SERVER['REMOTE_ADDR'];

	// Show banned page and exit
	die(
		Element('page.html', array(
			'title' => _('Banned!'),
			'config' => $config,
			'body' => Element('banned.html', array(
				'config' => $config,
				'ban' => $ban
			)
		))
	));
}

function checkBan($board = 0) {
	global $config;

	if (!isset($_SERVER['REMOTE_ADDR'])) {
		// Server misconfiguration
		return;
	}		

	if (event('check-ban', $board))
		return true;

	$query = prepare("SELECT `set`, `expires`, `reason`, `board`, `seen`, ``bans``.`id` FROM ``bans`` WHERE (`board` IS NULL OR `board` = :board) AND `ip` = :ip ORDER BY `expires` IS NULL DESC, `expires` DESC, `expires` DESC LIMIT 1");
	$query->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
	$query->bindValue(':board', $board);
	$query->execute() or error(db_error($query));
	if ($query->rowCount() < 1 && $config['ban_range']) {
		$query = prepare("SELECT `set`, `expires`, `reason`, `board`, `seen`, ``bans``.`id` FROM ``bans`` WHERE (`board` IS NULL OR `board` = :board) AND :ip LIKE REPLACE(REPLACE(`ip`, '%', '!%'), '*', '%') ESCAPE '!' ORDER BY `expires` IS NULL DESC, `expires` DESC LIMIT 1");
		$query->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
		$query->bindValue(':board', $board);
		$query->execute() or error(db_error($query));
	}

	if ($query->rowCount() < 1 && $config['ban_cidr'] && !isIPv6()) {
		// my most insane SQL query yet
		$query = prepare("SELECT `set`, `expires`, `reason`, `board`, `seen`, ``bans``.`id` FROM ``bans`` WHERE (`board` IS NULL OR `board` = :board)
			AND (					
				`ip` REGEXP '^(\[0-9]+\.\[0-9]+\.\[0-9]+\.\[0-9]+\)\/(\[0-9]+)$'
					AND
				:ip >= INET_ATON(SUBSTRING_INDEX(`ip`, '/', 1))
					AND
				:ip < INET_ATON(SUBSTRING_INDEX(`ip`, '/', 1)) + POW(2, 32 - SUBSTRING_INDEX(`ip`, '/', -1))
			)
			ORDER BY `expires` IS NULL DESC, `expires` DESC LIMIT 1");
		$query->bindValue(':ip', ip2long($_SERVER['REMOTE_ADDR']));
		$query->bindValue(':board', $board);
		$query->execute() or error(db_error($query));
	}

	if ($ban = $query->fetch(PDO::FETCH_ASSOC)) {
		if ($ban['expires'] && $ban['expires'] < time()) {
			// Ban expired
			$query = prepare("DELETE FROM ``bans`` WHERE `id` = :id");
			$query->bindValue(':id', $ban['id'], PDO::PARAM_INT);
			$query->execute() or error(db_error($query));

			if ($config['require_ban_view'] && !$ban['seen']) {
				displayBan($ban);
			}

			return;
		}

		displayBan($ban);
	}

	// I'm not sure where else to put this. It doesn't really matter where; it just needs to be called every now and then to keep the ban list tidy.
	purge_bans();
}

// No reason to keep expired bans in the database (except those that haven't been viewed yet)
function purge_bans() {
	global $config;
	
	if ($config['cache']['enabled'] && $last_time_purged = cache::get('purged_bans_last')) {
		if (time() - $last_time_purged < $config['purge_bans'] )
			return;
	}
	
	$query = prepare("DELETE FROM ``bans`` WHERE `expires` IS NOT NULL AND `expires` < :time AND `seen` = 1");
	$query->bindValue(':time', time());
	$query->execute() or error(db_error($query));
	
	if ($config['cache']['enabled'])
		cache::set('purged_bans_last', time());
}

function threadLocked($id) {
	global $board;

	if (event('check-locked', $id))
		return true;

	$query = prepare(sprintf("SELECT `locked` FROM ``posts_%s`` WHERE `id` = :id AND `thread` IS NULL LIMIT 1", $board['uri']));
	$query->bindValue(':id', $id, PDO::PARAM_INT);
	$query->execute() or error(db_error());

	if (($locked = $query->fetchColumn()) === false) {
		// Non-existant, so it can't be locked...
		return false;
	}

	return (bool)$locked;
}

function threadSageLocked($id) {
	global $board;

	if (event('check-sage-locked', $id))
		return true;

	$query = prepare(sprintf("SELECT `sage` FROM ``posts_%s`` WHERE `id` = :id AND `thread` IS NULL LIMIT 1", $board['uri']));
	$query->bindValue(':id', $id, PDO::PARAM_INT);
	$query->execute() or error(db_error());

	if (($sagelocked = $query->fetchColumn()) === false) {
		// Non-existant, so it can't be locked...
		return false;
	}

	return (bool)$sagelocked;
}

function threadExists($id) {
	global $board;

	$query = prepare(sprintf("SELECT 1 FROM ``posts_%s`` WHERE `id` = :id AND `thread` IS NULL LIMIT 1", $board['uri']));
	$query->bindValue(':id', $id, PDO::PARAM_INT);
	$query->execute() or error(db_error());

	if ($query->rowCount()) {
		return true;
	}

	return false;
}

function post(array $post) {
	global $pdo, $board;
	$query = prepare(sprintf("INSERT INTO ``posts_%s`` VALUES ( NULL, :thread, :subject, :email, :name, :trip, :capcode, :body, :body_nomarkup, :time, :time, :thumb, :thumbwidth, :thumbheight, :file, :width, :height, :filesize, :filename, :filehash, :password, :ip, :sticky, :locked, 0, :embed)", $board['uri']));

	// Basic stuff
	if (!empty($post['subject'])) {
		$query->bindValue(':subject', $post['subject']);
	} else {
		$query->bindValue(':subject', NULL, PDO::PARAM_NULL);
	}

	if (!empty($post['email'])) {
		$query->bindValue(':email', $post['email']);
	} else {
		$query->bindValue(':email', NULL, PDO::PARAM_NULL);
	}

	if (!empty($post['trip'])) {
		$query->bindValue(':trip', $post['trip']);
	} else {
		$query->bindValue(':trip', NULL, PDO::PARAM_NULL);
	}

	$query->bindValue(':name', $post['name']);
	$query->bindValue(':body', $post['body']);
	$query->bindValue(':body_nomarkup', $post['body_nomarkup']);
	$query->bindValue(':time', isset($post['time']) ? $post['time'] : time(), PDO::PARAM_INT);
	$query->bindValue(':password', $post['password']);		
	$query->bindValue(':ip', isset($post['ip']) ? $post['ip'] : $_SERVER['REMOTE_ADDR']);

	if ($post['op'] && $post['mod'] && isset($post['sticky']) && $post['sticky']) {
		$query->bindValue(':sticky', 1, PDO::PARAM_INT);
	} else {
		$query->bindValue(':sticky', 0, PDO::PARAM_INT);
	}

	if ($post['op'] && $post['mod'] && isset($post['locked']) && $post['locked']) {
		$query->bindValue(':locked', 1, PDO::PARAM_INT);
	} else {
		$query->bindValue(':locked', 0, PDO::PARAM_INT);
	}

	if ($post['mod'] && isset($post['capcode']) && $post['capcode']) {
		$query->bindValue(':capcode', $post['capcode'], PDO::PARAM_INT);
	} else {
		$query->bindValue(':capcode', NULL, PDO::PARAM_NULL);
	}

	if (!empty($post['embed'])) {
		$query->bindValue(':embed', $post['embed']);
	} else {
		$query->bindValue(':embed', NULL, PDO::PARAM_NULL);
	}

	if ($post['op']) {
		// No parent thread, image
		$query->bindValue(':thread', null, PDO::PARAM_NULL);
	} else {
		$query->bindValue(':thread', $post['thread'], PDO::PARAM_INT);
	}

	if ($post['has_file']) {
		$query->bindValue(':thumb', $post['thumb']);
		$query->bindValue(':thumbwidth', $post['thumbwidth'], PDO::PARAM_INT);
		$query->bindValue(':thumbheight', $post['thumbheight'], PDO::PARAM_INT);
		$query->bindValue(':file', $post['file']);

		if (isset($post['width'], $post['height'])) {
			$query->bindValue(':width', $post['width'], PDO::PARAM_INT);
			$query->bindValue(':height', $post['height'], PDO::PARAM_INT);
		} else {
			$query->bindValue(':width', null, PDO::PARAM_NULL);
			$query->bindValue(':height', null, PDO::PARAM_NULL);
		}

		$query->bindValue(':filesize', $post['filesize'], PDO::PARAM_INT);
		$query->bindValue(':filename', $post['filename']);
		$query->bindValue(':filehash', $post['filehash']);
	} else {
		$query->bindValue(':thumb', null, PDO::PARAM_NULL);
		$query->bindValue(':thumbwidth', null, PDO::PARAM_NULL);
		$query->bindValue(':thumbheight', null, PDO::PARAM_NULL);
		$query->bindValue(':file', null, PDO::PARAM_NULL);
		$query->bindValue(':width', null, PDO::PARAM_NULL);
		$query->bindValue(':height', null, PDO::PARAM_NULL);
		$query->bindValue(':filesize', null, PDO::PARAM_NULL);
		$query->bindValue(':filename', null, PDO::PARAM_NULL);
		$query->bindValue(':filehash', null, PDO::PARAM_NULL);
	}

	if (!$query->execute()) {
		undoImage($post);
		error(db_error($query));
	}

	return $pdo->lastInsertId();
}

function bumpThread($id) {
	global $config, $board, $build_pages;

	if (event('bump', $id))
		return true;

	if ($config['try_smarter'])
		$build_pages[] = thread_find_page($id);

	$query = prepare(sprintf("UPDATE ``posts_%s`` SET `bump` = :time WHERE `id` = :id AND `thread` IS NULL", $board['uri']));
	$query->bindValue(':time', time(), PDO::PARAM_INT);
	$query->bindValue(':id', $id, PDO::PARAM_INT);
	$query->execute() or error(db_error($query));
}

// Remove file from post
function deleteFile($id, $remove_entirely_if_already=true) {
	global $board, $config;

	$query = prepare(sprintf("SELECT `thread`,`thumb`,`file` FROM ``posts_%s`` WHERE `id` = :id LIMIT 1", $board['uri']));
	$query->bindValue(':id', $id, PDO::PARAM_INT);
	$query->execute() or error(db_error($query));
	if (!$post = $query->fetch(PDO::FETCH_ASSOC))
		error($config['error']['invalidpost']);

	if ($post['file'] == 'deleted' && !$post['thread'])
		return; // Can't delete OP's image completely.

	$query = prepare(sprintf("UPDATE ``posts_%s`` SET `thumb` = NULL, `thumbwidth` = NULL, `thumbheight` = NULL, `filewidth` = NULL, `fileheight` = NULL, `filesize` = NULL, `filename` = NULL, `filehash` = NULL, `file` = :file WHERE `id` = :id", $board['uri']));
	if ($post['file'] == 'deleted' && $remove_entirely_if_already) {
		// Already deleted; remove file fully
		$query->bindValue(':file', null, PDO::PARAM_NULL);
	} else {
		// Delete thumbnail
		file_unlink($board['dir'] . $config['dir']['thumb'] . $post['thumb']);

		// Delete file
		file_unlink($board['dir'] . $config['dir']['img'] . $post['file']);

		// Set file to 'deleted'
		$query->bindValue(':file', 'deleted', PDO::PARAM_INT);
	}

	$query->bindValue(':id', $id, PDO::PARAM_INT);
	$query->execute() or error(db_error($query));

	if ($post['thread'])
		buildThread($post['thread']);
	else
		buildThread($id);
}

// rebuild post (markup)
function rebuildPost($id) {
	global $board;

	$query = prepare(sprintf("SELECT `body_nomarkup`, `thread` FROM ``posts_%s`` WHERE `id` = :id", $board['uri']));
	$query->bindValue(':id', $id, PDO::PARAM_INT);
	$query->execute() or error(db_error($query));

	if ((!$post = $query->fetch(PDO::FETCH_ASSOC)) || !$post['body_nomarkup'])
		return false;

	markup($body = &$post['body_nomarkup']);

	$query = prepare(sprintf("UPDATE ``posts_%s`` SET `body` = :body WHERE `id` = :id", $board['uri']));
	$query->bindValue(':body', $body);
	$query->bindValue(':id', $id, PDO::PARAM_INT);
	$query->execute() or error(db_error($query));

	buildThread($post['thread'] ? $post['thread'] : $id);

	return true;
}

// Delete a post (reply or thread)
function deletePost($id, $error_if_doesnt_exist=true, $rebuild_after=true) {
	global $board, $config;

	// Select post and replies (if thread) in one query
	$query = prepare(sprintf("SELECT `id`,`thread`,`thumb`,`file` FROM ``posts_%s`` WHERE `id` = :id OR `thread` = :id", $board['uri']));
	$query->bindValue(':id', $id, PDO::PARAM_INT);
	$query->execute() or error(db_error($query));

	if ($query->rowCount() < 1) {
		if ($error_if_doesnt_exist)
			error($config['error']['invalidpost']);
		else return false;
	}

	$ids = array();

	// Delete posts and maybe replies
	while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
		event('delete', $post);
		
		if (!$post['thread']) {
			// Delete thread HTML page
			file_unlink($board['dir'] . $config['dir']['res'] . sprintf($config['file_page'], $post['id']));
			file_unlink($board['dir'] . $config['dir']['res'] . sprintf($config['file_page50'], $post['id']));
			file_unlink($board['dir'] . $config['dir']['res'] . sprintf('%d.json', $post['id']));

			$antispam_query = prepare('DELETE FROM ``antispam`` WHERE `board` = :board AND `thread` = :thread');
			$antispam_query->bindValue(':board', $board['uri']);
			$antispam_query->bindValue(':thread', $post['id']);
			$antispam_query->execute() or error(db_error($antispam_query));
		} elseif ($query->rowCount() == 1) {
			// Rebuild thread
			$rebuild = &$post['thread'];
		}
		if ($post['thumb']) {
			// Delete thumbnail
			file_unlink($board['dir'] . $config['dir']['thumb'] . $post['thumb']);
		}
		if ($post['file']) {
			// Delete file
			file_unlink($board['dir'] . $config['dir']['img'] . $post['file']);
		}

		$ids[] = (int)$post['id'];

	}

	$query = prepare(sprintf("DELETE FROM ``posts_%s`` WHERE `id` = :id OR `thread` = :id", $board['uri']));
	$query->bindValue(':id', $id, PDO::PARAM_INT);
	$query->execute() or error(db_error($query));

	$query = prepare("SELECT `board`, `post` FROM ``cites`` WHERE `target_board` = :board AND (`target` = " . implode(' OR `target` = ', $ids) . ")");
	$query->bindValue(':board', $board['uri']);
	$query->execute() or error(db_error($query));
	while ($cite = $query->fetch(PDO::FETCH_ASSOC)) {
		if ($board['uri'] != $cite['board']) {
			if (!isset($tmp_board))
				$tmp_board = $board['uri'];
			openBoard($cite['board']);
		}
		rebuildPost($cite['post']);
	}

	if (isset($tmp_board))
		openBoard($tmp_board);

	$query = prepare("DELETE FROM ``cites`` WHERE (`target_board` = :board AND `target` = :id) OR (`board` = :board AND `post` = :id)");
	$query->bindValue(':board', $board['uri']);
	$query->bindValue(':id', $id, PDO::PARAM_INT);
	$query->execute() or error(db_error($query));

	if (isset($rebuild) && $rebuild_after) {
		buildThread($rebuild);
	}

	return true;
}

function clean() {
	global $board, $config;
	$offset = round($config['max_pages']*$config['threads_per_page']);

	// I too wish there was an easier way of doing this...
	$query = prepare(sprintf("SELECT `id` FROM ``posts_%s`` WHERE `thread` IS NULL ORDER BY `sticky` DESC, `bump` DESC LIMIT :offset, 9001", $board['uri']));
	$query->bindValue(':offset', $offset, PDO::PARAM_INT);

	$query->execute() or error(db_error($query));
	while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
		deletePost($post['id']);
	}
}

function thread_find_page($thread) {
	global $config, $board;

	$query = query(sprintf("SELECT `id` FROM ``posts_%s`` WHERE `thread` IS NULL ORDER BY `sticky` DESC, `bump` DESC", $board['uri'])) or error(db_error($query));
	$threads = $query->fetchAll(PDO::FETCH_COLUMN);
	if (($index = array_search($thread, $threads)) === false)
		return false;
	return floor(($config['threads_per_page'] + $index) / $config['threads_per_page']);
}

function index($page, $mod=false) {
	global $board, $config, $debug;

	$body = '';
	$offset = round($page*$config['threads_per_page']-$config['threads_per_page']);

	$query = prepare(sprintf("SELECT * FROM ``posts_%s`` WHERE `thread` IS NULL ORDER BY `sticky` DESC, `bump` DESC LIMIT :offset,:threads_per_page", $board['uri']));
	$query->bindValue(':offset', $offset, PDO::PARAM_INT);
	$query->bindValue(':threads_per_page', $config['threads_per_page'], PDO::PARAM_INT);
	$query->execute() or error(db_error($query));

	if ($page == 1 && $query->rowCount() < $config['threads_per_page'])
		$board['thread_count'] = $query->rowCount();

	if ($query->rowCount() < 1 && $page > 1)
		return false;

	$threads = array();

	while ($th = $query->fetch(PDO::FETCH_ASSOC)) {
		$thread = new Thread($th, $mod ? '?/' : $config['root'], $mod);

		if ($config['cache']['enabled'] && $cached = cache::get("thread_index_{$board['uri']}_{$th['id']}")) {
			$replies = $cached['replies'];
			$omitted = $cached['omitted'];
		} else {
			$posts = prepare(sprintf("SELECT * FROM ``posts_%s`` WHERE `thread` = :id ORDER BY `id` DESC LIMIT :limit", $board['uri']));
			$posts->bindValue(':id', $th['id']);
			$posts->bindValue(':limit', ($th['sticky'] ? $config['threads_preview_sticky'] : $config['threads_preview']), PDO::PARAM_INT);
			$posts->execute() or error(db_error($posts));

			$replies = array_reverse($posts->fetchAll(PDO::FETCH_ASSOC));

			if (count($replies) == ($th['sticky'] ? $config['threads_preview_sticky'] : $config['threads_preview'])) {
				$count = numPosts($th['id']);
				$omitted = array('post_count' => $count['replies'], 'image_count' => $count['images']);
			} else {
				$omitted = false;
			}

			if ($config['cache']['enabled'])
				cache::set("thread_index_{$board['uri']}_{$th['id']}", array(
					'replies' => $replies,
					'omitted' => $omitted,
				));
		}

		$num_images = 0;
		foreach ($replies as $po) {
			if ($po['file'])
				$num_images++;

			$thread->add(new Post($po, $mod ? '?/' : $config['root'], $mod));
		}

		if ($omitted) {
			$thread->omitted = $omitted['post_count'] - ($th['sticky'] ? $config['threads_preview_sticky'] : $config['threads_preview']);
			$thread->omitted_images = $omitted['image_count'] - $num_images;
		}

		$threads[] = $thread;
		$body .= $thread->build(true);
	}

	return array(
		'board' => $board,
		'body' => $body,
		'post_url' => $config['post_url'],
		'config' => $config,
		'boardlist' => createBoardlist($mod),
		'threads' => $threads
	);
}

function getPageButtons($pages, $mod=false) {
	global $config, $board;

	$btn = array();
	$root = ($mod ? '?/' : $config['root']) . $board['dir'];

	foreach ($pages as $num => $page) {
		if (isset($page['selected'])) {
			// Previous button
			if ($num == 0) {
				// There is no previous page.
				$btn['prev'] = _('Previous');
			} else {
				$loc = ($mod ? '?/' . $board['uri'] . '/' : '') .
					($num == 1 ?
						$config['file_index']
					:
						sprintf($config['file_page'], $num)
					);

				$btn['prev'] = '<form action="' . ($mod ? '' : $root . $loc) . '" method="get">' .
					($mod ?
						'<input type="hidden" name="status" value="301" />' .
						'<input type="hidden" name="r" value="' . htmlentities($loc) . '" />'
					:'') .
				'<input type="submit" value="' . _('Previous') . '" /></form>';
			}

			if ($num == count($pages) - 1) {
				// There is no next page.
				$btn['next'] = _('Next');
			} else {
				$loc = ($mod ? '?/' . $board['uri'] . '/' : '') . sprintf($config['file_page'], $num + 2);

				$btn['next'] = '<form action="' . ($mod ? '' : $root . $loc) . '" method="get">' .
					($mod ?
						'<input type="hidden" name="status" value="301" />' .
						'<input type="hidden" name="r" value="' . htmlentities($loc) . '" />'
					:'') .
				'<input type="submit" value="' . _('Next') . '" /></form>';
			}
		}
	}

	return $btn;
}

function getPages($mod=false) {
	global $board, $config;

	if (isset($board['thread_count'])) {
		$count = $board['thread_count'];
	} else {
		// Count threads
		$query = query(sprintf("SELECT COUNT(*) FROM ``posts_%s`` WHERE `thread` IS NULL", $board['uri'])) or error(db_error());
		$count = $query->fetchColumn();
	}
	$count = floor(($config['threads_per_page'] + $count - 1) / $config['threads_per_page']);

	if ($count < 1) $count = 1;

	$pages = array();
	for ($x=0;$x<$count && $x<$config['max_pages'];$x++) {
		$pages[] = array(
			'num' => $x+1,
			'link' => $x==0 ? ($mod ? '?/' : $config['root']) . $board['dir'] . $config['file_index'] : ($mod ? '?/' : $config['root']) . $board['dir'] . sprintf($config['file_page'], $x+1)
		);
	}

	return $pages;
}

function makerobot($body) {
	global $config;
	$body = strtolower($body);

	// Leave only letters
	$body = preg_replace('/[^a-z]/i', '', $body);
	// Remove repeating characters
	if ($config['robot_strip_repeating'])
		$body = preg_replace('/(.)\\1+/', '$1', $body);

	return sha1($body);
}

function checkRobot($body) {
	if (empty($body) || event('check-robot', $body))
		return true;

	$body = makerobot($body);
	$query = prepare("SELECT 1 FROM ``robot`` WHERE `hash` = :hash LIMIT 1");
	$query->bindValue(':hash', $body);
	$query->execute() or error(db_error($query));

	if ($query->fetchColumn()) {
		return true;
	}

	// Insert new hash
	$query = prepare("INSERT INTO ``robot`` VALUES (:hash)");
	$query->bindValue(':hash', $body);
	$query->execute() or error(db_error($query));

	return false;
}

// Returns an associative array with 'replies' and 'images' keys
function numPosts($id) {
	global $board;
	$query = prepare(sprintf("SELECT COUNT(*) FROM ``posts_%s`` WHERE `thread` = :thread UNION ALL SELECT COUNT(*) FROM ``posts_%s`` WHERE `file` IS NOT NULL AND `thread` = :thread", $board['uri'], $board['uri']));
	$query->bindValue(':thread', $id, PDO::PARAM_INT);
	$query->execute() or error(db_error($query));

	$num_posts = $query->fetchColumn();
	$num_images = $query->fetchColumn();

	return array('replies' => $num_posts, 'images' => $num_images);
}

function muteTime() {
	global $config;

	if ($time = event('mute-time'))
		return $time;

	// Find number of mutes in the past X hours
	$query = prepare("SELECT COUNT(*) FROM ``mutes`` WHERE `time` >= :time AND `ip` = :ip");
	$query->bindValue(':time', time()-($config['robot_mute_hour']*3600), PDO::PARAM_INT);
	$query->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
	$query->execute() or error(db_error($query));

	if (!$result = $query->fetchColumn())
		return 0;
	return pow($config['robot_mute_multiplier'], $result);
}

function mute() {
	// Insert mute
	$query = prepare("INSERT INTO ``mutes`` VALUES (:ip, :time)");
	$query->bindValue(':time', time(), PDO::PARAM_INT);
	$query->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
	$query->execute() or error(db_error($query));

	return muteTime();
}

function checkMute() {
	global $config, $debug;

	if ($config['cache']['enabled']) {
		// Cached mute?
		if (($mute = cache::get("mute_${_SERVER['REMOTE_ADDR']}")) && ($mutetime = cache::get("mutetime_${_SERVER['REMOTE_ADDR']}"))) {
			error(sprintf($config['error']['youaremuted'], $mute['time'] + $mutetime - time()));
		}
	}

	$mutetime = muteTime();
	if ($mutetime > 0) {
		// Find last mute time
		$query = prepare("SELECT `time` FROM ``mutes`` WHERE `ip` = :ip ORDER BY `time` DESC LIMIT 1");
		$query->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
		$query->execute() or error(db_error($query));

		if (!$mute = $query->fetch(PDO::FETCH_ASSOC)) {
			// What!? He's muted but he's not muted...
			return;
		}

		if ($mute['time'] + $mutetime > time()) {
			if ($config['cache']['enabled']) {
				cache::set("mute_${_SERVER['REMOTE_ADDR']}", $mute, $mute['time'] + $mutetime - time());
				cache::set("mutetime_${_SERVER['REMOTE_ADDR']}", $mutetime, $mute['time'] + $mutetime - time());
			}
			// Not expired yet
			error(sprintf($config['error']['youaremuted'], $mute['time'] + $mutetime - time()));
		} else {
			// Already expired	
			return;
		}
	}
}

function buildIndex() {
	global $board, $config, $build_pages;

	$pages = getPages();
	if (!$config['try_smarter'])
		$antibot = create_antibot($board['uri']);

	if ($config['api']['enabled']) {
		$api = new Api();
		$catalog = array();
	}

	for ($page = 1; $page <= $config['max_pages']; $page++) {
		$filename = $board['dir'] . ($page == 1 ? $config['file_index'] : sprintf($config['file_page'], $page));

		if ($config['try_smarter'] && isset($build_pages) && count($build_pages) && !in_array($page, $build_pages) && is_file($filename))
			continue;
		$content = index($page);
		if (!$content)
			break;

		if ($config['try_smarter']) {
			$antibot = create_antibot($board['uri'], 0 - $page);
			$content['current_page'] = $page;
		}
		$antibot->reset();
		$content['pages'] = $pages;
		$content['pages'][$page-1]['selected'] = true;
		$content['btn'] = getPageButtons($content['pages']);
		$content['antibot'] = $antibot;

		file_write($filename, Element('index.html', $content));

		// json api
		if ($config['api']['enabled']) {
			$threads = $content['threads'];
			$json = json_encode($api->translatePage($threads));
			$jsonFilename = $board['dir'] . ($page-1) . ".json"; // pages should start from 0
			file_write($jsonFilename, $json);

			$catalog[$page-1] = $threads; 
		}
	}
	if ($page < $config['max_pages']) {
		for (;$page<=$config['max_pages'];$page++) {
			$filename = $board['dir'] . ($page==1 ? $config['file_index'] : sprintf($config['file_page'], $page));
			file_unlink($filename);
			
			if ($config['api']['enabled']) {
				$jsonFilename = $board['dir'] . ($page-1) . ".json";
				file_unlink($jsonFilename);
			}
		}
	}

	// json api catalog
	if ($config['api']['enabled']) {
		$json = json_encode($api->translateCatalog($catalog));
		$jsonFilename = $board['dir'] . "catalog.json";
		file_write($jsonFilename, $json);
	}
}

function buildJavascript() {
	global $config;

	$stylesheets = array();
	foreach ($config['stylesheets'] as $name => $uri) {
		$stylesheets[] = array(
			'name' => addslashes($name),
			'uri' => addslashes((!empty($uri) ? $config['uri_stylesheets'] : '') . $uri));
	}

	$script = Element('main.js', array(
		'config' => $config,
		'stylesheets' => $stylesheets
	));

	// Check if we have translation for the javascripts; if yes, we add it to additional javascripts
	list($pure_locale) = explode(".", $config['locale']);
	if (file_exists ($jsloc = "inc/locale/$pure_locale/LC_MESSAGES/javascript.js")) {
		$script = file_get_contents($jsloc) . "\n\n" . $script;
	}

	if ($config['additional_javascript_compile']) {
		foreach ($config['additional_javascript'] as $file) {
			$script .= file_get_contents($file);
		}
	}

	if ($config['minify_js']) {
		require_once 'inc/lib/minify/JSMin.php';		
		$script = JSMin::minify($script);
	}

	file_write($config['file_script'], $script);
}

function checkDNSBL() {
	global $config;


	if (isIPv6())
		return; // No IPv6 support yet.

	if (!isset($_SERVER['REMOTE_ADDR']))
		return; // Fix your web server configuration

	if (in_array($_SERVER['REMOTE_ADDR'], $config['dnsbl_exceptions']))
		return;

	$ipaddr = ReverseIPOctets($_SERVER['REMOTE_ADDR']);

	foreach ($config['dnsbl'] as $blacklist) {
		if (!is_array($blacklist))
			$blacklist = array($blacklist);

		if (($lookup = str_replace('%', $ipaddr, $blacklist[0])) == $blacklist[0])
			$lookup = $ipaddr . '.' . $blacklist[0];

		if (!$ip = DNS($lookup))
			continue; // not in list

		$blacklist_name = isset($blacklist[2]) ? $blacklist[2] : $blacklist[0];

		if (!isset($blacklist[1])) {
			// If you're listed at all, you're blocked.
			error(sprintf($config['error']['dnsbl'], $blacklist_name));
		} elseif (is_array($blacklist[1])) {
			foreach ($blacklist[1] as $octet) {
				if ($ip == $octet || $ip == '127.0.0.' . $octet)
					error(sprintf($config['error']['dnsbl'], $blacklist_name));
			}
		} elseif (is_callable($blacklist[1])) {
			if ($blacklist[1]($ip))
				error(sprintf($config['error']['dnsbl'], $blacklist_name));
		} else {
			if ($ip == $blacklist[1] || $ip == '127.0.0.' . $blacklist[1])
				error(sprintf($config['error']['dnsbl'], $blacklist_name));
		}
	}
}

function isIPv6() {
	return strstr($_SERVER['REMOTE_ADDR'], ':') !== false;
}

function ReverseIPOctets($ip) {
	return implode('.', array_reverse(explode('.', $ip)));
}

function wordfilters(&$body) {
	global $config;

	foreach ($config['wordfilters'] as $filter) {
		if (isset($filter[2]) && $filter[2]) {
			if (is_callable($filter[1]))
				$body = preg_replace_callback($filter[0], $filter[1], $body);
			else
				$body = preg_replace($filter[0], $filter[1], $body);
		} else {
			$body = str_ireplace($filter[0], $filter[1], $body);
		}
	}
}

function quote($body, $quote=true) {
	global $config;

	$body = str_replace('<br/>', "\n", $body);

	$body = strip_tags($body);

	$body = preg_replace("/(^|\n)/", '$1&gt;', $body);

	$body .= "\n";

	if ($config['minify_html'])
		$body = str_replace("\n", '&#010;', $body);

	return $body;
}

function markup_url($matches) {
	global $config, $markup_urls;

	$url = $matches[1];
	$after = $matches[2];

	$markup_urls[] = $url;

	return '<a target="_blank" rel="nofollow" href="'. $config['link_prefix'] . $url . '">' . $url . '</a>' . $after;
}

function unicodify($body) {
	$body = str_replace('...', '&hellip;', $body);
	$body = str_replace('&lt;--', '&larr;', $body);
	$body = str_replace('--&gt;', '&rarr;', $body);

	// En and em- dashes are rendered exactly the same in
	// most monospace fonts (they look the same in code
	// editors).
	$body = str_replace('---', '&mdash;', $body); // em dash
	$body = str_replace('--', '&ndash;', $body); // en dash

	return $body;
}

function extract_modifiers($body) {
	$modifiers = array();
	
	if (preg_match_all('@<tinyboard ([\w\s]+)>(.+?)</tinyboard>@us', $body, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $match) {
			if (preg_match('/^escape /', $match[1]))
				continue;
			$modifiers[$match[1]] = html_entity_decode($match[2]);
		}
	}
		
	return $modifiers;
}

function markup(&$body, $track_cites = false) {
	global $board, $config, $markup_urls;
	
	$modifiers = extract_modifiers($body);
	
	$body = preg_replace('@<tinyboard (?!escape )([\w\s]+)>(.+?)</tinyboard>@us', '', $body);
	$body = preg_replace('@<(tinyboard) escape ([\w\s]+)>@i', '<$1 $2>', $body);
	
	if (isset($modifiers['raw html']) && $modifiers['raw html'] == '1') {
		return array();
	}

	$body = str_replace("\r", '', $body);
	$body = utf8tohtml($body);

	if (mysql_version() < 50503)
		$body = mb_encode_numericentity($body, array(0x010000, 0xffffff, 0, 0xffffff), 'UTF-8');

	foreach ($config['markup'] as $markup) {
		if (is_string($markup[1])) {
			$body = preg_replace($markup[0], $markup[1], $body);
		} elseif (is_callable($markup[1])) {
			$body = preg_replace_callback($markup[0], $markup[1], $body);
		}
	}

	if ($config['markup_urls']) {
		$markup_urls = array();

		$body = preg_replace_callback(
				'/((?:https?:\/\/|ftp:\/\/|irc:\/\/)[^\s<>()"]+?(?:\([^\s<>()"]*?\)[^\s<>()"]*?)*)((?:\s|<|>|"|\.||\]|!|\?|,|&#44;|&quot;)*(?:[\s<>()"]|$))/',
				'markup_url',
				$body,
				-1,
				$num_links);

		if ($num_links > $config['max_links'])
			error($config['error']['toomanylinks']);
	}

	if ($config['auto_unicode']) {
		$body = unicodify($body);

		if ($config['markup_urls']) {
			foreach ($markup_urls as &$url) {
				$body = str_replace(unicodify($url), $url, $body);
			}
		}
	}

	// replace tabs with 8 spaces
	$body = str_replace("\t", '        ', $body);

	$tracked_cites = array();

	// Cites
	if (isset($board) && preg_match_all('/(^|\s)&gt;&gt;(\d+?)([\s,.)?]|$)/m', $body, $cites, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
		if (count($cites[0]) > $config['max_cites']) {
			error($config['error']['toomanycites']);
		}

		$skip_chars = 0;
		$body_tmp = $body;

		foreach ($cites as $matches) {
			$cite = $matches[2][0];
			$query = prepare(sprintf("SELECT `thread`,`id` FROM ``posts_%s`` WHERE `id` = :id LIMIT 1", $board['uri']));
			$query->bindValue(':id', $cite);
			$query->execute() or error(db_error($query));

			// preg_match_all is not multibyte-safe
			foreach ($matches as &$match) {
				$match[1] = mb_strlen(substr($body_tmp, 0, $match[1]));
			}

			if ($post = $query->fetch(PDO::FETCH_ASSOC)) {
				$replacement = '<a onclick="highlightReply(\''.$cite.'\');" href="' .
					$config['root'] . $board['dir'] . $config['dir']['res'] . ($post['thread']?$post['thread']:$post['id']) . '.html#' . $cite . '">' .
						'&gt;&gt;' . $cite .
						'</a>';

				$body = mb_substr_replace($body, $matches[1][0] . $replacement . $matches[3][0], $matches[0][1] + $skip_chars, mb_strlen($matches[0][0]));
				$skip_chars += mb_strlen($matches[1][0] . $replacement . $matches[3][0]) - mb_strlen($matches[0][0]);

				if ($track_cites && $config['track_cites'])
					$tracked_cites[] = array($board['uri'], $post['id']);
			}
		}
	}

	// Cross-board linking
	if (preg_match_all('/(^|\s)&gt;&gt;&gt;\/(' . $config['board_regex'] . 'f?)\/(\d+)?([\s,.)?]|$)/um', $body, $cites, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
		if (count($cites[0]) > $config['max_cites']) {
			error($config['error']['toomanycross']);
		}

		$skip_chars = 0;
		$body_tmp = $body;

		foreach ($cites as $matches) {
			$_board = $matches[2][0];
			$cite = @$matches[3][0];

			// preg_match_all is not multibyte-safe
			foreach ($matches as &$match) {
				$match[1] = mb_strlen(substr($body_tmp, 0, $match[1]));
			}

			// Temporarily store board information because it will be overwritten
			$tmp_board = $board['uri'];

			// Check if the board exists, and load settings
			if (openBoard($_board)) {
				if ($cite) {
					$query = prepare(sprintf("SELECT `thread`,`id` FROM ``posts_%s`` WHERE `id` = :id LIMIT 1", $board['uri']));
					$query->bindValue(':id', $cite);
					$query->execute() or error(db_error($query));

					if ($post = $query->fetch(PDO::FETCH_ASSOC)) {
						$replacement = '<a onclick="highlightReply(\''.$cite.'\');" href="' .
							$config['root'] . $board['dir'] . $config['dir']['res'] . ($post['thread']?$post['thread']:$post['id']) . '.html#' . $cite . '">' .
								'&gt;&gt;&gt;/' . $_board . '/' . $cite .
								'</a>';

						$body = mb_substr_replace($body, $matches[1][0] . $replacement . $matches[4][0], $matches[0][1] + $skip_chars, mb_strlen($matches[0][0]));
						$skip_chars += mb_strlen($matches[1][0] . $replacement . $matches[4][0]) - mb_strlen($matches[0][0]);

						if ($track_cites && $config['track_cites'])
							$tracked_cites[] = array($board['uri'], $post['id']);
					}
				} else {
					$replacement = '<a href="' .
						$config['root'] . $board['dir'] . $config['file_index'] . '">' .
							'&gt;&gt;&gt;/' . $_board . '/' .
							'</a>';
					$body = mb_substr_replace($body, $matches[1][0] . $replacement . $matches[4][0], $matches[0][1] + $skip_chars, mb_strlen($matches[0][0]));
					$skip_chars += mb_strlen($matches[1][0] . $replacement . $matches[4][0]) - mb_strlen($matches[0][0]);
				}
			}

			// Restore main board settings
			openBoard($tmp_board);
		}
	}

	$body = preg_replace("/^\s*&gt;.*$/m", '<span class="quote">$0</span>', $body);

	if ($config['strip_superfluous_returns'])
		$body = preg_replace('/\s+$/', '', $body);

	$body = preg_replace("/\n/", '<br/>', $body);

	return $tracked_cites;
}

function escape_markup_modifiers($string) {
	return preg_replace('@<(tinyboard) ([\w\s]+)>@mi', '<$1 escape $2>', $string);
}

function utf8tohtml($utf8) {
	return htmlspecialchars($utf8, ENT_NOQUOTES, 'UTF-8');
}

function ordutf8($string, &$offset) {
	$code = ord(substr($string, $offset,1)); 
	if ($code >= 128) { // otherwise 0xxxxxxx
		if ($code < 224)
			$bytesnumber = 2; // 110xxxxx
		else if ($code < 240)
			$bytesnumber = 3; // 1110xxxx
		else if ($code < 248)
			$bytesnumber = 4; // 11110xxx
		$codetemp = $code - 192 - ($bytesnumber > 2 ? 32 : 0) - ($bytesnumber > 3 ? 16 : 0);
		for ($i = 2; $i <= $bytesnumber; $i++) {
			$offset ++;
			$code2 = ord(substr($string, $offset, 1)) - 128; //10xxxxxx
			$codetemp = $codetemp*64 + $code2;
		}
		$code = $codetemp;
	}
	$offset += 1;
	if ($offset >= strlen($string))
		$offset = -1;
	return $code;
}

function strip_combining_chars($str) {
	$chars = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
	$str = '';
	foreach ($chars as $char) {
		$o = 0;
		$ord = ordutf8($char, $o);

		if ($ord >= 768 && $ord <= 879)
			continue;

		if ($ord >= 7616 && $ord <= 7679)
			continue;

		if ($ord >= 8400 && $ord <= 8447)
			continue;

		if ($ord >= 65056 && $ord <= 65071)
			continue;

		$str .= $char;
	}
	return $str;
}

function buildThread($id, $return = false, $mod = false) {
	global $board, $config, $build_pages;
	$id = round($id);

	if (event('build-thread', $id))
		return;

	if ($config['cache']['enabled'] && !$mod) {
		// Clear cache
		cache::delete("thread_index_{$board['uri']}_{$id}");
		cache::delete("thread_{$board['uri']}_{$id}");
	}

	$query = prepare(sprintf("SELECT * FROM ``posts_%s`` WHERE (`thread` IS NULL AND `id` = :id) OR `thread` = :id ORDER BY `thread`,`id`", $board['uri']));
	$query->bindValue(':id', $id, PDO::PARAM_INT);
	$query->execute() or error(db_error($query));

	while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
		if (!isset($thread)) {
			$thread = new Thread($post, $mod ? '?/' : $config['root'], $mod);
		} else {
			$thread->add(new Post($post, $mod ? '?/' : $config['root'], $mod));
		}
	}

	// Check if any posts were found
	if (!isset($thread))
		error($config['error']['nonexistant']);
	
	$hasnoko50 = $thread->postCount() >= $config['noko50_min'];		

	$body = Element('thread.html', array(
		'board' => $board,
		'thread' => $thread,
		'body' => $thread->build(),
		'config' => $config,
		'id' => $id,
		'mod' => $mod,
		'hasnoko50' => $hasnoko50,
		'isnoko50' => false,
		'antibot' => $mod || $return ? false : create_antibot($board['uri'], $id),
		'boardlist' => createBoardlist($mod),
		'return' => ($mod ? '?' . $board['url'] . $config['file_index'] : $config['root'] . $board['dir'] . $config['file_index'])
	));

	if ($config['try_smarter'] && !$mod)
		$build_pages[] = thread_find_page($id);

	// json api
	if ($config['api']['enabled']) {
		$api = new Api();
		$json = json_encode($api->translateThread($thread));
		$jsonFilename = $board['dir'] . $config['dir']['res'] . $id . ".json";
		file_write($jsonFilename, $json);
	}

	if ($return) {
		return $body;
	} else {
		$noko50fn = $board['dir'] . $config['dir']['res'] . sprintf($config['file_page50'], $id);
		if ($hasnoko50 || file_exists($noko50fn)) {
			buildThread50($id, $return, $mod, $thread);
		}

		file_write($board['dir'] . $config['dir']['res'] . sprintf($config['file_page'], $id), $body);
	}
}

function buildThread50($id, $return = false, $mod = false, $thread = null) {
	global $board, $config, $build_pages;
	$id = round($id);
	
	if (event('build-thread', $id))
		return;
		
	if (!$thread) {
		$query = prepare(sprintf("SELECT * FROM ``posts_%s`` WHERE (`thread` IS NULL AND `id` = :id) OR `thread` = :id ORDER BY `thread`,`id` DESC LIMIT :limit", $board['uri']));
		$query->bindValue(':id', $id, PDO::PARAM_INT);
		$query->bindValue(':limit', $config['noko50_count']+1, PDO::PARAM_INT);
		$query->execute() or error(db_error($query));
		
		$num_images = 0;
		while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
			if (!isset($thread)) {
				$thread = new Thread($post, $mod ? '?/' : $config['root'], $mod);
			} else {
				if ($post['file'])
					$num_images++;
					
				$thread->add(new Post($post, $mod ? '?/' : $config['root'], $mod));
			}
		}

		// Check if any posts were found
		if (!isset($thread))
			error($config['error']['nonexistant']);


		if ($query->rowCount() == $config['noko50_count']+1) {
			$count = prepare(sprintf("SELECT COUNT(`id`) as `num` FROM ``posts_%s`` WHERE `thread` = :thread UNION ALL SELECT COUNT(`id`) FROM ``posts_%s`` WHERE `file` IS NOT NULL AND `thread` = :thread", $board['uri'], $board['uri']));
			$count->bindValue(':thread', $id, PDO::PARAM_INT);
			$count->execute() or error(db_error($count));
			
			$c = $count->fetch();
			$thread->omitted = $c['num'] - $config['noko50_count'];
			
			$c = $count->fetch();
			$thread->omitted_images = $c['num'] - $num_images;
		}

		$thread->posts = array_reverse($thread->posts);
	} else {
		$allPosts = $thread->posts;

		$thread->posts = array_slice($allPosts, -$config['noko50_count']);
		$thread->omitted += count($allPosts) - count($thread->posts);
		foreach ($allPosts as $index => $post) {
			if ($index == count($allPosts)-count($thread->posts))
				break;  
			if ($post->file)
				$thread->omitted_images++;
		}
	}

	$hasnoko50 = $thread->postCount() >= $config['noko50_min'];		

	$body = Element('thread.html', array(
		'board' => $board,
		'thread' => $thread,
		'body' => $thread->build(false, true),
		'config' => $config,
		'id' => $id,
		'mod' => $mod,
		'hasnoko50' => $hasnoko50,
		'isnoko50' => true,
		'antibot' => $mod ? false : create_antibot($board['uri'], $id),
		'boardlist' => createBoardlist($mod),
		'return' => ($mod ? '?' . $board['url'] . $config['file_index'] : $config['root'] . $board['dir'] . $config['file_index'])
	));	

	if ($return) {
		return $body;
	} else {
		file_write($board['dir'] . $config['dir']['res'] . sprintf($config['file_page50'], $id), $body);
	}
}

 function rrmdir($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir."/".$object) == "dir")
					rrmdir($dir."/".$object);
				else
					file_unlink($dir."/".$object);
			}
		}
		reset($objects);
		rmdir($dir);
	}
}

function poster_id($ip, $thread) {
	global $config;

	if ($id = event('poster-id', $ip, $thread))
		return $id;

	// Confusing, hard to brute-force, but simple algorithm
	return substr(sha1(sha1($ip . $config['secure_trip_salt'] . $thread) . $config['secure_trip_salt']), 0, $config['poster_id_length']);
}

function generate_tripcode($name) {
	global $config;

	if ($trip = event('tripcode', $name))
		return $trip;

	if (!preg_match('/^([^#]+)?(##|#)(.+)$/', $name, $match))
		return array($name);

	$name = $match[1];
	$secure = $match[2] == '##';
	$trip = $match[3];

	// convert to SHIT_JIS encoding
	$trip = mb_convert_encoding($trip, 'Shift_JIS', 'UTF-8');

	// generate salt
	$salt = substr($trip . 'H..', 1, 2);
	$salt = preg_replace('/[^.-z]/', '.', $salt);
	$salt = strtr($salt, ':;<=>?@[\]^_`', 'ABCDEFGabcdef');

	if ($secure) {
		if (isset($config['custom_tripcode']["##{$trip}"]))
			$trip = $config['custom_tripcode']["##{$trip}"];
		else
			$trip = '!!' . substr(crypt($trip, $config['secure_trip_salt']), -10);
	} else {
		if (isset($config['custom_tripcode']["#{$trip}"]))
			$trip = $config['custom_tripcode']["#{$trip}"];
		else
			$trip = '!' . substr(crypt($trip, $salt), -10);
	}

	return array($name, $trip);
}

// Highest common factor
function hcf($a, $b){
	$gcd = 1;
	if ($a>$b) {
		$a = $a+$b;
		$b = $a-$b;
		$a = $a-$b;
	}
	if ($b==(round($b/$a))*$a) 
		$gcd=$a;
	else {
		for ($i=round($a/2);$i;$i--) {
			if ($a == round($a/$i)*$i && $b == round($b/$i)*$i) {
				$gcd = $i;
				$i = false;
			}
		}
	}
	return $gcd;
}

function fraction($numerator, $denominator, $sep) {
	$gcf = hcf($numerator, $denominator);
	$numerator = $numerator / $gcf;
	$denominator = $denominator / $gcf;

	return "{$numerator}{$sep}{$denominator}";
}

function getPostByHash($hash) {
	global $board;
	$query = prepare(sprintf("SELECT `id`,`thread` FROM ``posts_%s`` WHERE `filehash` = :hash", $board['uri']));
	$query->bindValue(':hash', $hash, PDO::PARAM_STR);
	$query->execute() or error(db_error($query));

	if ($post = $query->fetch(PDO::FETCH_ASSOC)) {
		return $post;
	}

	return false;
}

function getPostByHashInThread($hash, $thread) {
	global $board;
	$query = prepare(sprintf("SELECT `id`,`thread` FROM ``posts_%s`` WHERE `filehash` = :hash AND ( `thread` = :thread OR `id` = :thread )", $board['uri']));
	$query->bindValue(':hash', $hash, PDO::PARAM_STR);
	$query->bindValue(':thread', $thread, PDO::PARAM_INT);
	$query->execute() or error(db_error($query));

	if ($post = $query->fetch(PDO::FETCH_ASSOC)) {
		return $post;
	}

	return false;
}

function undoImage(array $post) {
	if (!$post['has_file'])
		return;

	if (isset($post['file_path']))
		file_unlink($post['file_path']);
	if (isset($post['thumb_path']))
		file_unlink($post['thumb_path']);
}

function rDNS($ip_addr) {
	global $config;

	if ($config['cache']['enabled'] && ($host = cache::get('rdns_' . $ip_addr))) {
		return $host;
	}

	if (!$config['dns_system']) {
		$host = gethostbyaddr($ip_addr);
	} else {
		$resp = shell_exec_error('host -W 1 ' . $ip_addr);
		if (preg_match('/domain name pointer ([^\s]+)$/', $resp, $m))
			$host = $m[1];
		else
			$host = $ip_addr;
	}

	if ($config['cache']['enabled'])
		cache::set('rdns_' . $ip_addr, $host, 3600);

	return $host;
}

function DNS($host) {
	global $config;

	if ($config['cache']['enabled'] && ($ip_addr = cache::get('dns_' . $host))) {
		return $ip_addr;
	}

	if (!$config['dns_system']) {
		$ip_addr = gethostbyname($host);
		if ($ip_addr == $host)
			$ip_addr = false;
	} else {
		$resp = shell_exec_error('host -W 1 ' . $host);
		if (preg_match('/has address ([^\s]+)$/', $resp, $m))
			$ip_addr = $m[1];
		else
			$ip_addr = false;
	}

	if ($config['cache']['enabled'])
		cache::set('dns_' . $host, $ip_addr, 3600);

	return $ip_addr;
}

function shell_exec_error($command, $suppress_stdout = false) {
	global $config, $debug;

	if ($config['debug'])
		$start = microtime(true);

	$return = trim(shell_exec('PATH="' . escapeshellcmd($config['shell_path']) . ':$PATH";' .
		$command . ' 2>&1 ' . ($suppress_stdout ? '> /dev/null ' : '') . '&& echo "TB_SUCCESS"'));
	$return = preg_replace('/TB_SUCCESS$/', '', $return);

	if ($config['debug']) {
		$time = round((microtime(true) - $start) * 1000, 2) . 'ms';
		$debug['exec'][] = array(
			'command' => $command,
			'time' => '~' . $time,
			'response' => $return ? $return : null
		);
	}

	return $return === 'TB_SUCCESS' ? false : $return;
}
