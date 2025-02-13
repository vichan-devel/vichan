<?php
/*
 *  Copyright (c) 2010-2024 Tinyboard Development Group
 */

use Vichan\Context;

require_once 'inc/bootstrap.php';

if ($config['debug']) {
	$parse_start_time = microtime(true);
}

require_once 'inc/mod/pages.php';

/**
 * Class Router
 *
 * Handles HTTP request routing.
 */
class Router {
	/** @var array $pages Array of URL patterns and their corresponding handlers */
	private array $pages;

	/** @var string $query The query string from the HTTP request */
	private string $query;

	/** @var mixed $mod Mod information for the current user session */
	private mixed $mod;

	/** @var array $config Configuration settings for the application */
	private array $config;

	/** @var bool $securePostOnly Indicates if the current handler requires a secure POST request */
	private bool $securePostOnly = false;

	/**
	 * Router constructor.
	 *
	 * @param Context $ctx The application context object
	 * @param mixed|null $mod Mod information for the current user session
	 */
	public function __construct(Context $ctx, mixed $mod = null) {
		$this->config = $ctx->get('config');
		$this->mod = $mod;
		$this->query = isset($_SERVER['QUERY_STRING']) ? rawurldecode($_SERVER['QUERY_STRING']) : '';

		$this->initializePages();
	}

	/**
	 * Initializes the array of pages and their handlers.
	 *
	 * This method sets up the routing table for various endpoints.
	 */
	private function initializePages(): void {
		$this->pages = [
			''	=> ':?/',	// redirect to dashboard
			'/'	=> 'dashboard',	// dashboard
			'/confirm/(.+)'	=> 'confirm',	// confirm action (if javascript didn't work)
			'/logout'	=> 'secure logout',	// logout

			'/users'	=> 'users',	// manage users
			'/users/(\d+)/(promote|demote)'	=> 'secure user_promote',	// prmote/demote user
			'/users/(\d+)'	=> 'secure_POST user',	// edit user
			'/users/new'	=> 'secure_POST user_new',	// create a new user

			'/new_PM/([^/]+)'	=> 'secure_POST new_pm',	// create a new pm
			'/PM/(\d+)(/reply)?'	=> 'pm',	// read a pm
			'/inbox'	=> 'inbox',	// pm inbox

			'/log'	=> 'log',	// modlog
			'/log/(\d+)'	=> 'log',	// modlog
			'/log:([^/:]+)'	=> 'user_log',	// modlog
			'/log:([^/:]+)/(\d+)'	=> 'user_log',	// modlog
			'/log:b:([^/]+)'	=> 'board_log',	// modlog
			'/log:b:([^/]+)/(\d+)'	=> 'board_log',	// modlog

			'/edit_news'	=> 'secure_POST news',	// view news
			'/edit_news/(\d+)'	=> 'secure_POST news',	// view news
			'/edit_news/delete/(\d+)'	=> 'secure news_delete',	// delete from news

			'/edit_pages(?:/?(\%b)?)'	=> 'secure_POST pages',	// edit static pages from board
			'/edit_page/(\d+)'	=> 'secure_POST edit_page',	// edit site-wide static pages
			'/edit_pages/delete/([a-z0-9]+)'	=> 'secure delete_page',	// delete site-wide static pages
			'/edit_pages/delete/([a-z0-9]+)/(\%b)'	=> 'secure delete_page_board',	// delete static pages from board

			'/noticeboard'	=> 'secure_POST noticeboard',	// view noticeboard
			'/noticeboard/(\d+)'	=> 'secure_POST noticeboard',	// view noticeboard
			'/noticeboard/delete/(\d+)'	=> 'secure noticeboard_delete',	// delete from noticeboard

			'/edit/(\%b)'	=> 'secure_POST edit_board',	// edit board details
			'/new-board'	=> 'secure_POST new_board',	// create a new board

			'/rebuild'	=> 'secure_POST rebuild',	// rebuild static files
			'/reports'	=> 'reports',	// report queue
			'/reports/(\d+)/dismiss(&all|&post)?'	=> 'secure report_dismiss',	// dismiss a report

			'/IP/([\w.:]+)'	=> 'secure_POST ip',	// view ip address
			'/IP/([\w.:]+)/remove_note/(\d+)'	=> 'secure ip_remove_note',	// remove note from ip address

			'/user_posts/ip/([\w.:]+)'				=> 'secure_POST user_posts_by_ip',		// view user posts by ip address
			'/user_posts/ip/([\w.:]+)/cursor/([\w|-|_]+)'	=> 'secure_POST user_posts_by_ip',	// remove note from ip address

			'/user_posts/passwd/(\w+)'				=> 'secure_POST user_posts_by_passwd',		// view user posts by ip address
			'/user_posts/passwd/(\w+)/cursor/([\w|-|_]+)'	=> 'secure_POST user_posts_by_passwd',	// remove note from ip address

			'/ban'	=> 'secure_POST ban',	// new ban
			'/bans'	=> 'secure_POST bans',	// ban list
			'/bans.json'	=> 'secure bans_json',	// ban list JSON
			'/edit_ban/(\d+)'	=> 'secure_POST edit_ban',	// edit ban
			'/ban-appeals'	=> 'secure_POST ban_appeals',	// view ban appeals

			'/recent/(\d+)'	=> 'recent_posts',	// view recent posts

			'/search'	=> 'search_redirect',	// search
			'/search/(posts|IP_notes|bans|log)/(.+)/(\d+)'	=> 'search',	// search
			'/search/(posts|IP_notes|bans|log)/(.+)'	=> 'search',	// search

			'/(\%b)/ban(&delete)?/(\d+)'	=> 'secure_POST ban_post',	// ban poster
			'/(\%b)/move/(\d+)'	=> 'secure_POST move',	// move thread
			'/(\%b)/move_reply/(\d+)'	=> 'secure_POST move_reply',	// move reply
			'/(\%b)/edit(_raw)?/(\d+)'	=> 'secure_POST edit_post',	// edit post
			'/(\%b)/delete/(\d+)'	=> 'secure delete',	// delete post
			'/(\%b)/deletefile/(\d+)/(\d+)'	=> 'secure deletefile',	// delete file from post
			'/(\%b+)/spoiler/(\d+)/(\d+)'	=> 'secure spoiler_image',	// spoiler file
			'/(\%b)/deletebyip/(\d+)(/global)?'	=> 'secure deletebyip',	// delete all posts by IP address
			'/(\%b)/(un)?lock/(\d+)'	=> 'secure lock',	// lock thread
			'/(\%b)/(un)?sticky/(\d+)'	=> 'secure sticky',	// sticky thread
			'/(\%b)/(un)?cycle/(\d+)'	=> 'secure cycle',	// cycle thread
			'/(\%b)/bump(un)?lock/(\d+)'	=> 'secure bumplock',	// "bumplock" thread

			'/themes'	=> 'themes_list',	// manage themes
			'/themes/(\w+)'	=> 'secure_POST theme_configure',	// configure/reconfigure theme
			'/themes/(\w+)/rebuild'	=> 'secure theme_rebuild',	// rebuild theme
			'/themes/(\w+)/uninstall'	=> 'secure theme_uninstall',	// uninstall theme

			'/config'	=> 'secure_POST config',	// config editor
			'/config/(\%b)'	=> 'secure_POST config',	// config editor

			// This should always be at the end:
			'/(\%b)/'	=> 'view_board',
			'/(\%b)/' . preg_quote($this->config['file_index'], '!')	=> 'view_board',
			'/(\%b)/' . str_replace('%d', '(\d+)',
						preg_quote($this->config['file_page'], '!'))	=> 'view_board',

			'/(\%b)/' . preg_quote($this->config['file_catalog'], '!')	=> 'view_catalog',

			'/(\%b)/' . preg_quote($this->config['dir']['res'], '!') .
						str_replace('%d', '(\d+)',
						preg_quote($this->config['file_page50'], '!'))	=> 'view_thread50',

			'/(\%b)/' . preg_quote($this->config['dir']['res'], '!') .
						str_replace('%d', '(\d+)',
						preg_quote($this->config['file_page'], '!'))	=> 'view_thread',

			// slug
			'/(\%b)/' . preg_quote($this->config['dir']['res'], '!') .
						str_replace([ '%d','%s' ], [ '(\d+)', '[a-z0-9-]+' ],
						preg_quote($this->config['file_page50_slug'], '!'))	=> 'view_thread50',

			'/(\%b)/' . preg_quote($this->config['dir']['res'], '!') .
						str_replace([ '%d','%s' ], [ '(\d+)', '[a-z0-9-]+' ],
						preg_quote($this->config['file_page_slug'], '!'))	=> 'view_thread',
		];

		if ($this->config['debug']) {
			$this->addDebugPages();
		}

		if (!$this->mod) {
			$this->pages = ['!^(.+)?$!' => 'login'];
		}

		if (isset($this->config['mod']['custom_pages'])) {
			$this->pages = array_merge($this->pages, $this->config['mod']['custom_pages']);
		}

		$this->prepareRoutes();
	}

	/**
	 * Adds debugging pages to the routing table if debugging is enabled.
	 */
	private function addDebugPages(): void {
		$this->pages = array_merge_recursive($this->pages, [
			'/debug/antispam' => 'debug_antispam',
			'/debug/recent' => 'debug_recent_posts',
			'/debug/sql' => 'secure_POST debug_sql',
		]);
	}

	/**
	 * Prepares routes by processing page patterns into regex patterns and updating the pages array.
	 */
	private function prepareRoutes(): void {
		$new_pages = [];
		foreach ($this->pages as $key => $callback) {
			if (is_string($callback) && preg_match('/^secure /', $callback)) {
				$key .= '(/(?P<token>[a-f0-9]{8}))?';
			}

			$key = str_replace(
				'\%b',
				'?P<board>' . sprintf(
					substr($this->config['board_path'], 0, -1),
					$this->config['board_regex']
				),
				$key
			);

			$new_pages[
				strpos($key, '!') === 0
					? $key
					: "!^{$key}(?:&[^&=]+=[^&]*)*$!u"
			] = $callback;
		}
		$this->pages = $new_pages;
	}

	/**
	 * Handles the incoming request by matching the query string to a route and executing the corresponding handler.
	 */
	public function handleRequest(Context $ctx): void {
		foreach ($this->pages as $uri => $handler) {
			if (preg_match($uri, $this->query, $matches)) {
				$matches[0] = $ctx;

				$this->processBoard($matches);

				if (is_string($handler) && preg_match('/^secure(_POST)? /', $handler, $m)) {
					$this->securePostOnly = isset($m[1]);
					$this->processSecureHandler($ctx, $matches);
					$handler = $this->processHandler($handler);
				}

				$this->logDebugInfo($uri, $handler);

				$matches = array_values($matches);

				$this->executeHandler($handler, $matches);
				exit;
			}
		}

		$this->error($this->config['error']['404']);
	}

	/**
	 * Processes the handler name, removing security prefixes.
	 *
	 * @param string $handler The handler name
	 * @return string The processed handler name
	 */
	private function processHandler(string $handler): string {
		return preg_replace('/^secure(_POST)? /', '', $handler);
	}

	/**
	 * Processes the board information from the route matches.
	 *
	 * @param array &$matches The array of route matches
	 */
	private function processBoard(array &$matches): void {
		if (isset($matches['board'])) {
			$board_match = $matches['board'];
			unset($matches['board']);
			$key = array_search($board_match, $matches);
			if (preg_match(
				'/^' . sprintf(
					substr($this->config['board_path'], 0, -1),
					"({$this->config['board_regex']})"
				) . '$/u',
				$matches[$key],
				$board_match
			)) {
				$matches[$key] = $board_match[1];
			}
		}
	}

	/**
	 * Processes POST secure handlers, validating CSRF tokens.
	 *
	 * @param array &$matches The array of route matches
	 */
	private function processSecureHandler(Context $ctx, array &$matches): void {
		if (!$this->securePostOnly || $_SERVER['REQUEST_METHOD'] === 'POST') {
			$token = $this->getToken($ctx, $matches);

			// CSRF-protected page; validate security token
			$actual_query = preg_replace('!/([a-f0-9]{8})$!', '', $this->query);
			if ($token !== make_secure_link_token(substr($actual_query, 1))) {
				$this->error($this->config['error']['csrf']);
			}
		}
	}

	/**
	 * Retrieves the CSRF token from the route matches or POST data.
	 *
	 * @param array &$matches The array of route matches
	 * @return string|null The CSRF token, or null if not found
	 */
	private function getToken(Context $ctx, array &$matches): ?string {
		if (isset($matches['token'])) {
			return $matches['token'];
		} elseif (isset($_POST['token'])) {
			return $_POST['token'];
		} else {
			if ($this->securePostOnly) {
				$this->error($this->config['error']['csrf']);
			} else {
				mod_confirm($ctx, substr($this->query, 1));
				exit;
			}
		}

		return null;
	}

	/**
	 * Logs debug information, if enabled, about the current request.
	 *
	 * @param string $uri The matched URI pattern
	 * @param string $handler The handler name
	 */
	private function logDebugInfo(string $uri, string $handler): void {
		global $debug, $parse_start_time;

		if ($this->config['debug']) {
			$debug['mod_page'] = [
				'req' => $this->query,
				'match' => $uri,
				'handler' => $handler,
				'type' => gettype($handler),
				'secure' => $this->securePostOnly ? 'true' : 'false'
			];
			$debug['time']['parse_mod_req'] = '~' . round((microtime(true) - $parse_start_time) * 1000, 2) . 'ms';
		}
	}

	/**
	 * Executes the matched handler with the provided matches.
	 *
	 * @param string $handler The handler to execute
	 * @param array $matches The route matches to pass to the handler
	 */
	private function executeHandler(string $handler, array $matches): void {
		if (is_string($handler)) {
			if ($handler[0] === ':') {
				$this->safeRedirect(substr($handler, 1));
			} elseif (is_callable("mod_{$handler}")) {
				call_user_func_array("mod_{$handler}", $matches);
			} else {
				$this->error("Mod page '{$handler}' not found!");
			}
		} elseif (is_callable($handler)) {
			call_user_func_array($handler, $matches);
		} else {
			$this->error("Mod page '{$handler}' not a string, and not callable!");
		}
	}

	/**
	 * Safely redirects to the specified location.
	 *
	 * @param string $location The URL to redirect to
	 */
	private function safeRedirect(string $location): void {
		header("Location: {$location}", true, $this->config['redirect_http']);
		exit;
	}

	/**
	 * Triggers an error with the specified message.
	 *
	 * @param string $message The error message
	 */
	private function error(string $message): void {
		error($message);
	}
}

$ctx = Vichan\build_context($config);

check_login($ctx, true);

$router = new Router($ctx, $mod);
$router->handleRequest($ctx);
