<?php
namespace Vichan;

use Vichan\Driver\{HttpDriver, HttpDrivers, Log, LogDrivers};

defined('TINYBOARD') or exit;


interface Context {
	public function getLog(): Log;
	public function getHttpDriver(): HttpDriver;
}

class AppContext implements Context {
	private array $config;
	private ?Log $log;
	private ?HttpDriver $http;


	private function initLogDriver(): Log {
		$name = $this->config['log_system']['name'];
		$level = $this->config['debug'] ? Log::DEBUG : Log::NOTICE;
		$backend = $this->config['log_system']['type'];

		// Check 'syslog' for backwards compatibility.
		if ((isset($this->config['syslog']) && $this->config['syslog']) || $backend === 'syslog') {
			return LogDrivers::syslog($name, $level, $this->config['log_system']['syslog_stderr']);
		} elseif ($backend === 'file') {
			return LogDrivers::file($name, $level, $this->config['log_system']['file_path']);
		} elseif ($backend === 'stderr') {
			return LogDrivers::stderr($name, $level);
		} elseif ($backend === 'none') {
			return LogDrivers::none();
		} else {
			return LogDrivers::error_log($name, $level);
		}
	}


	public function __construct(array $config) {
		$this->config = $config;
	}

	public function getLog(): Log {
		if (is_null($this->log)) {
			$this->log = $this->initLogDriver();
		}
		return $this->log;
	}

	public function getHttpDriver(): HttpDriver {
		if (is_null($this->http)) {
			$this->http = HttpDrivers::getHttpDriver($this->config['upload_by_url_timeout'], $this->config['max_filesize']);
		}
		return $this->http;
	}
}
