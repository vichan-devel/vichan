<?php

/*
 *  Copyright (c) 2010-2013 Tinyboard Development Group
 */

require 'inc/functions.php';
require 'inc/mod/pages.php';
require 'inc/mod/auth.php';

// Fix for magic quotes
if (get_magic_quotes_gpc()) {
	function strip_array($var) {
		return is_array($var) ? array_map('strip_array', $var) : stripslashes($var);
	}
	
	$_GET = strip_array($_GET);
	$_POST = strip_array($_POST);
}

$query = isset($_SERVER['QUERY_STRING']) ? rawurldecode($_SERVER['QUERY_STRING']) : '';

$pages = array(
	''					=> ':?/',		// redirect to dashboard
	'/'					=> 'dashboard',		// dashboard
	'/confirm/(.+)'				=> 'confirm',		// confirm action (if javascript didn't work)
	'/logout'				=> 'logout',		// logout
	
	'/users'				=> 'users',		// manage users
	'/users/(\d+)'				=> 'user',		// edit user
	'/users/(\d+)/(promote|demote)'		=> 'user_promote',		// prmote/demote user
	'/users/new'				=> 'user_new',		// create a new user
	'/new_PM/([^/]+)'			=> 'new_pm',		// create a new pm
	'/PM/(\d+)(/reply)?'			=> 'pm',		// read a pm
	'/inbox'				=> 'inbox',		// pm inbox
	
	'/noticeboard'				=> 'noticeboard',	// view noticeboard
	'/noticeboard/(\d+)'			=> 'noticeboard',	// view noticeboard
	'/noticeboard/delete/(\d+)'		=> 'noticeboard_delete',	// delete from noticeboard
	'/log'					=> 'log',		// modlog
	'/log/(\d+)'				=> 'log',		// modlog
	'/log:([^/]+)'				=> 'user_log',		// modlog
	'/log:([^/]+)/(\d+)'			=> 'user_log',		// modlog
	'/news'					=> 'news',		// view news
	'/news/(\d+)'				=> 'news',		// view news
	'/news/delete/(\d+)'			=> 'news_delete',	// delete from news
	
	'/edit/(\%b)'				=> 'edit_board',	// edit board details
	'/new-board'				=> 'new_board',		// create a new board
	
	'/rebuild'				=> 'rebuild',		// rebuild static files
	'/reports'				=> 'reports',		// report queue
	'/reports/(\d+)/dismiss(all)?'		=> 'report_dismiss',	// dismiss a report
	
	'/IP/([\w.:]+)'				=> 'ip',		// view ip address
	'/IP/([\w.:]+)/remove_note/(\d+)'	=> 'ip_remove_note',	// remove note from ip address
	'/bans'					=> 'bans',		// ban list
	'/bans/(\d+)'				=> 'bans',		// ban list
	
	'/search'				=> 'search_redirect',	// search
	'/search/(posts|IP_notes|bans|log)/(.+)/(\d+)'	=> 'search',	// search
	'/search/(posts|IP_notes|bans|log)/(.+)'	=> 'search',	// search

	// CSRF-protected moderator actions
	'/ban'					=> 'secure_POST ban',		// new ban
	'/(\%b)/ban(&delete)?/(\d+)'		=> 'secure_POST ban_post', 	// ban poster
	'/(\%b)/move/(\d+)'			=> 'secure_POST move',		// move thread
	'/(\%b)/move_reply/(\d+)'			=> 'secure_POST move_reply',		// move reply
	'/(\%b)/edit(_raw)?/(\d+)'		=> 'secure_POST edit_post',	// edit post
	'/(\%b)/delete/(\d+)'			=> 'secure delete',		// delete post
	'/(\%b)/deletefile/(\d+)'		=> 'secure deletefile',		// delete file from post
	'/(\%b+)/spoiler/(\d+)'			=> 'secure spoiler_image',	// spoiler file
	'/(\%b)/deletebyip/(\d+)(/global)?'	=> 'secure deletebyip',		// delete all posts by IP address
	'/(\%b)/(un)?lock/(\d+)'		=> 'secure lock',		// lock thread
	'/(\%b)/(un)?sticky/(\d+)'		=> 'secure sticky',		// sticky thread
	'/(\%b)/bump(un)?lock/(\d+)'		=> 'secure bumplock',		// "bumplock" thread
	
	'/themes'				=> 'themes_list',	// manage themes
	'/themes/(\w+)'				=> 'theme_configure',	// configure/reconfigure theme
	'/themes/(\w+)/rebuild'			=> 'theme_rebuild',	// rebuild theme
	'/themes/(\w+)/uninstall'		=> 'theme_uninstall',	// uninstall theme
	
	'/config'				=> 'config',		// config editor
	'/config/(\%b)'				=> 'config',		// config editor
	
	// these pages aren't listed in the dashboard without $config['debug']
	'/debug/antispam'			=> 'debug_antispam',
	'/debug/recent'				=> 'debug_recent_posts',
	'/debug/sql'				=> 'secure_POST debug_sql',
	
	// This should always be at the end:
	'/(\%b)/'										=> 'view_board',
	'/(\%b)/' . preg_quote($config['file_index'], '!')					=> 'view_board',
	'/(\%b)/' . str_replace('%d', '(\d+)', preg_quote($config['file_page'], '!'))		=> 'view_board',
	'/(\%b)/' . preg_quote($config['dir']['res'], '!') .
			str_replace('%d', '(\d+)', preg_quote($config['file_page'], '!'))	=> 'view_thread',
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
	$new_pages[@$key[0] == '!' ? $key : '!^' . $key . '(?:&[^&=]+=[^&]*)*$!u'] = $callback;
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
				'handler' => $handler
			);
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

