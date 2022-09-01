<?php

/*
 *  Copyright (c) 2010-2014 Tinyboard Development Group
 */

require_once 'inc/bootstrap.php';

if ($config['debug'])
	$parse_start_time = microtime(true);

require_once 'inc/mod/pages.php';

check_login(true);

$query = isset($_SERVER['QUERY_STRING']) ? rawurldecode($_SERVER['QUERY_STRING']) : '';

$pages = array(
	''					=> ':?/',			// redirect to dashboard
	'/'					=> 'dashboard',			// dashboard
	'/confirm/(.+)'				=> 'confirm',			// confirm action (if javascript didn't work)
	'/logout'				=> 'secure logout',		// logout
	
	'/users'				=> 'users',			// manage users
	'/users/(\d+)/(promote|demote)'		=> 'secure user_promote',	// prmote/demote user
	'/users/(\d+)'				=> 'secure_POST user',		// edit user
	'/users/new'				=> 'secure_POST user_new',	// create a new user
	
	'/new_PM/([^/]+)'			=> 'secure_POST new_pm',	// create a new pm
	'/PM/(\d+)(/reply)?'			=> 'pm',			// read a pm
	'/inbox'				=> 'inbox',			// pm inbox
	
	'/log'					=> 'log',			// modlog
	'/log/(\d+)'				=> 'log',			// modlog
	'/log:([^/:]+)'				=> 'user_log',			// modlog
	'/log:([^/:]+)/(\d+)'			=> 'user_log',			// modlog
	'/log:b:([^/]+)'			=> 'board_log',			// modlog
	'/log:b:([^/]+)/(\d+)'			=> 'board_log',			// modlog

	'/edit_news'				=> 'secure_POST news',		// view news
	'/edit_news/(\d+)'			=> 'secure_POST news',		// view news
	'/edit_news/delete/(\d+)'		=> 'secure news_delete',	// delete from news

	'/edit_pages(?:/?(\%b)?)'		=> 'secure_POST pages',
	'/edit_page/(\d+)'			=> 'secure_POST edit_page',
	'/edit_pages/delete/([a-z0-9]+)'	=> 'secure delete_page',
	'/edit_pages/delete/([a-z0-9]+)/(\%b)'	=> 'secure delete_page_board',
	
	'/noticeboard'				=> 'secure_POST noticeboard',	// view noticeboard
	'/noticeboard/(\d+)'			=> 'secure_POST noticeboard',	// view noticeboard
	'/noticeboard/delete/(\d+)'		=> 'secure noticeboard_delete',	// delete from noticeboard
	
	'/edit/(\%b)'				=> 'secure_POST edit_board',	// edit board details
	'/new-board'				=> 'secure_POST new_board',	// create a new board
	
	'/rebuild'				=> 'secure_POST rebuild',	// rebuild static files
	'/reports'				=> 'reports',			// report queue
	'/reports/(\d+)/dismiss(all)?'		=> 'secure report_dismiss',	// dismiss a report
	
	'/IP/([\w.:]+)'				=> 'secure_POST ip',		// view ip address
	'/IP/([\w.:]+)/remove_note/(\d+)'	=> 'secure ip_remove_note',	// remove note from ip address
	'/IP/([\w.:-]+)/remove_telegram/(\d+)'	=> 'secure ip_remove_telegram',	// remove telegram from ip address

	'/ban'					=> 'secure_POST ban',		// new ban
	'/bans'					=> 'secure_POST bans',		// ban list
	'/bans.json'				=> 'secure bans_json',		// ban list JSON
	'/ban-appeals'				=> 'secure_POST ban_appeals',	// view ban appeals
	
	'/recent/(\d+)'				=> 'recent_posts',		// view recent posts

	'/search'				=> 'search_redirect',		// search
	'/search/(posts|IP_notes|bans|log)/(.+)/(\d+)'	=> 'search',		// search
	'/search/(posts|IP_notes|bans|log)/(.+)'	=> 'search',		// search

	'/(\%b)/ban(&delete)?/(\d+)'		=> 'secure_POST ban_post', 	// ban poster
	'/(\%b)/move/(\d+)'			=> 'secure_POST move',		// move thread
	'/(\%b)/move_reply/(\d+)'			=> 'secure_POST move_reply',		// move reply
	'/(\%b)/edit(_raw)?/(\d+)'		=> 'secure_POST edit_post',	// edit post
	'/(\%b)/delete/(\d+)'			=> 'secure delete',		// delete post
	'/(\%b)/deletefile/(\d+)/(\d+)'		=> 'secure deletefile',		// delete file from post
	'/(\%b+)/spoiler/(\d+)/(\d+)'			=> 'secure spoiler_image',	// spoiler file
	'/(\%b)/deletebyip/(\d+)(/global)?'	=> 'secure deletebyip',		// delete all posts by IP address
	'/(\%b)/(un)?lock/(\d+)'		=> 'secure lock',		// lock thread
	'/(\%b)/(un)?sticky/(\d+)'		=> 'secure sticky',		// sticky thread
	'/(\%b)/(un)?cycle/(\d+)'                         => 'secure cycle',          // cycle thread
	'/(\%b)/bump(un)?lock/(\d+)'		=> 'secure bumplock',		// "bumplock" thread
	
	'/themes'				=> 'themes_list',		// manage themes
	'/themes/(\w+)'				=> 'secure_POST theme_configure',		// configure/reconfigure theme
	'/themes/(\w+)/rebuild'			=> 'secure theme_rebuild',		// rebuild theme
	'/themes/(\w+)/uninstall'		=> 'secure theme_uninstall',		// uninstall theme
	
	'/config'				=> 'secure_POST config',	// config editor
	'/config/(\%b)'				=> 'secure_POST config',	// config editor
	
	// these pages aren't listed in the dashboard without $config['debug']
	//'/debug/antispam'			=> 'debug_antispam',
	//'/debug/recent'				=> 'debug_recent_posts',
	//'/debug/apc'				=> 'debug_apc',
	//'/debug/sql'				=> 'secure_POST debug_sql',
	
	// This should always be at the end:
	'/(\%b)/'										=> 'view_board',
	'/(\%b)/' . preg_quote($config['file_index'], '!')					=> 'view_board',
	'/(\%b)/' . preg_quote($config['file_catalog'], '!')					=> 'view_catalog',
	'/(\%b)/' . str_replace('%d', '(\d+)', preg_quote($config['file_page'], '!'))		=> 'view_board',
	'/(\%b)/' . preg_quote($config['dir']['res'], '!') .
			str_replace('%d', '(\d+)', preg_quote($config['file_page50'], '!'))	=> 'view_thread50',
	'/(\%b)/' . preg_quote($config['dir']['res'], '!') .
			str_replace('%d', '(\d+)', preg_quote($config['file_page'], '!'))	=> 'view_thread',

	'/(\%b)/' . preg_quote($config['dir']['res'], '!') .
			str_replace(array('%d','%s'), array('(\d+)', '[a-z0-9-]+'), preg_quote($config['file_page50_slug'], '!'))	=> 'view_thread50',
	'/(\%b)/' . preg_quote($config['dir']['res'], '!') .
			str_replace(array('%d','%s'), array('(\d+)', '[a-z0-9-]+'), preg_quote($config['file_page_slug'], '!'))	=> 'view_thread',
);


if (!$mod) {
	$pages = array('!^(.+)?$!' => 'login');
} elseif (isset($_GET['status'], $_GET['r'])) {
	header('Location: ' . $_GET['r'], true, (int)$_GET['status']);
	exit;
}

if (isset($config['mod']['custom_pages'])) {
	$pages = array_merge($pages, $config['mod']['custom_pages']);
}

$new_pages = array();
foreach ($pages as $key => $callback) {
	if (is_string($callback) && preg_match('/^secure /', $callback))
		$key .= '(/(?P<token>[a-f0-9]{8}))?';
	$key = str_replace('\%b', '?P<board>' . sprintf(substr($config['board_path'], 0, -1), $config['board_regex']), $key);
	$new_pages[(!empty($key) and $key[0] == '!') ? $key : '!^' . $key . '(?:&[^&=]+=[^&]*)*$!u'] = $callback;
}
$pages = $new_pages;

foreach ($pages as $uri => $handler) {
	if (preg_match($uri, $query, $matches)) {
		$matches = array_slice($matches, 1);
		
		if (isset($matches['board'])) {
			$board_match = $matches['board'];
			unset($matches['board']);
			$key = array_search($board_match, $matches);
			if (preg_match('/^' . sprintf(substr($config['board_path'], 0, -1), '(' . $config['board_regex'] . ')') . '$/u', $matches[$key], $board_match)) {
				$matches[$key] = $board_match[1];
			}
		}
		
		if (is_string($handler) && preg_match('/^secure(_POST)? /', $handler, $m)) {
			$secure_post_only = isset($m[1]);
			if (!$secure_post_only || $_SERVER['REQUEST_METHOD'] == 'POST') {
				$token = isset($matches['token']) ? $matches['token'] : (isset($_POST['token']) ? $_POST['token'] : false);
				
				if ($token === false) {
					if ($secure_post_only)
						error($config['error']['csrf']);
					else {
						mod_confirm(substr($query, 1));
						exit;
					}
				}
			
				// CSRF-protected page; validate security token
				$actual_query = preg_replace('!/([a-f0-9]{8})$!', '', $query);
				if ($token != make_secure_link_token(substr($actual_query, 1))) {
					error($config['error']['csrf']);
				}
			}
			$handler = preg_replace('/^secure(_POST)? /', '', $handler);
		}
		
		if ($config['debug']) {
			$debug['mod_page'] = array(
				'req' => $query,
				'match' => $uri,
				'handler' => $handler,
			);
			$debug['time']['parse_mod_req'] = '~' . round((microtime(true) - $parse_start_time) * 1000, 2) . 'ms';
		}

		if (is_array($matches)) {
			// we don't want to call named parameters (PHP 8)
			$matches = array_values($matches);
		}

		if (is_string($handler)) {
			if ($handler[0] == ':') {
				header('Location: ' . substr($handler, 1),  true, $config['redirect_http']);
			} elseif (is_callable("mod_page_$handler")) {
				call_user_func_array("mod_page_$handler", $matches);
			} elseif (is_callable("mod_$handler")) {
				call_user_func_array("mod_$handler", $matches);
			} else {
				error("Mod page '$handler' not found!");
			}
		} elseif (is_callable($handler)) {
			call_user_func_array($handler, $matches);
		} else {
			error("Mod page '$handler' not a string, and not callable!");
		}
		
		exit;
	}
}

error($config['error']['404']);

