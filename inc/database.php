<?php

/*
 *  Copyright (c) 2010-2013 Tinyboard Development Group
 */

defined('TINYBOARD') or exit;

class PreparedQueryDebug {
	protected $query, $explain_query = false;
	
	public function __construct($query) {
		global $pdo, $config;
		$query = preg_replace("/[\n\t]+/", ' ', $query);
		
		$this->query = $pdo->prepare($query);
		if ($config['debug'] && $config['debug_explain'] && preg_match('/^(SELECT|INSERT|UPDATE|DELETE) /i', $query))
			$this->explain_query = $pdo->prepare("EXPLAIN $query");
	}
	public function __call($function, $args) {
		global $config, $debug;
		
		if ($config['debug'] && $function == 'execute') {
			if ($this->explain_query) {
				$this->explain_query->execute() or error(db_error($this->explain_query));
			}
			$start = microtime(true);
		}
		
		if ($this->explain_query && $function == 'bindValue')
			call_user_func_array(array($this->explain_query, $function), $args);
		
		$return = call_user_func_array(array($this->query, $function), $args);
		
		if ($config['debug'] && $function == 'execute') {
			$time = microtime(true) - $start;
			$debug['sql'][] = array(
				'query' => $this->query->queryString,
				'rows' => $this->query->rowCount(),
				'explain' => $this->explain_query ? $this->explain_query->fetchAll(PDO::FETCH_ASSOC) : null,
				'time' => '~' . round($time * 1000, 2) . 'ms'
			);
			$debug['time']['db_queries'] += $time;
		}
		
		return $return;
	}
}

function sql_open() {
	global $pdo, $config, $debug;
	if ($pdo)
		return true;
	
	
	if ($config['debug'])
		$start = microtime(true);
	
	if (isset($config['db']['server'][0]) && $config['db']['server'][0] == ':')
		$unix_socket = substr($config['db']['server'], 1);
	else
		$unix_socket = false;
	
	$dsn = $config['db']['type'] . ':' .
		($unix_socket ? 'unix_socket=' . $unix_socket : 'host=' . $config['db']['server']) .
		';dbname=' . $config['db']['database'];
	if (!empty($config['db']['dsn']))
		$dsn .= ';' . $config['db']['dsn'];
	try {
		$options = array(
			PDO::ATTR_TIMEOUT => $config['db']['timeout'],
			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
		);
		if ($config['db']['persistent'])
			$options[PDO::ATTR_PERSISTENT] = true;
		$pdo = new PDO($dsn, $config['db']['user'], $config['db']['password'], $options);
		
		if ($config['debug'])
			$debug['time']['db_connect'] = '~' . round((microtime(true) - $start) * 1000, 2) . 'ms';
		
		if (mysql_version() >= 50503)
			query('SET NAMES utf8mb4') or error(db_error());
		else
			query('SET NAMES utf8') or error(db_error());
		return $pdo;
	} catch(PDOException $e) {
		$message = $e->getMessage();
		
		// Remove any sensitive information
		$message = str_replace($config['db']['user'], '<em>hidden</em>', $message);
		$message = str_replace($config['db']['password'], '<em>hidden</em>', $message);
		
		// Print error
		error(_('Database error: ') . $message);
	}
}

// 5.6.10 becomes 50610
function mysql_version() {
	global $pdo;
	
	$version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
	$v = explode('.', $version);
	if (count($v) != 3)
		return false;
	return (int) sprintf("%02d%02d%02d", $v[0], $v[1], is_int($v[2]) ? (int)$v[2] : 0);
}

function prepare($query) {
	global $pdo, $debug, $config;
	
	$query = preg_replace('/``('.$config['board_regex'].')``/u', '`' . $config['db']['prefix'] . '$1`', $query);
	
	sql_open();
	
	if ($config['debug'])
		return new PreparedQueryDebug($query);

	return $pdo->prepare($query);
}

function query($query) {
	global $pdo, $debug, $config;
	
	$query = preg_replace('/``('.$config['board_regex'].')``/u', '`' . $config['db']['prefix'] . '$1`', $query);
	
	sql_open();
	
	if ($config['debug']) {
		if ($config['debug_explain'] && preg_match('/^(SELECT|INSERT|UPDATE|DELETE) /i', $query)) {
			$explain = $pdo->query("EXPLAIN $query") or error(db_error());
		}
		$start = microtime(true);
		$query = $pdo->query($query);
		if (!$query)
			return false;
		$time = microtime(true) - $start;
		$debug['sql'][] = array(
			'query' => $query->queryString,
			'rows' => $query->rowCount(),
			'explain' => isset($explain) ? $explain->fetchAll(PDO::FETCH_ASSOC) : null,
			'time' => '~' . round($time * 1000, 2) . 'ms'
		);
		$debug['time']['db_queries'] += $time;
		return $query;
	}

	return $pdo->query($query);
}

function db_error($PDOStatement = null) {
	global $pdo, $db_error;

	if (isset($PDOStatement)) {
		$db_error = $PDOStatement->errorInfo();
		return $db_error[2];
	}

	$db_error = $pdo->errorInfo();
	return $db_error[2];
}
