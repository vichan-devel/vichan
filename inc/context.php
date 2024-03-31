<?php
namespace Vichan;

defined('TINYBOARD') or exit;

use Vichan\Driver\{HttpDriver, HttpDrivers};


interface Context {
	public function getHttpDriver(): HttpDriver;
}

class AppContext implements Context {
	private array $config;
	private ?HttpDriver $http;


	public function __construct(array $config) {
		$this->config = $config;
	}

	public function getHttpDriver(): HttpDriver {
		if (is_null($this->http)) {
			$this->http = HttpDrivers::getHttpDriver($this->config['upload_by_url_timeout'], $this->config['max_filesize']);
		}
		return $this->http;
	}
}
