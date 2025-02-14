<?php
namespace Vichan;

use Vichan\Data\Driver\{CacheDriver, HttpDriver, ErrorLogLogDriver, FileLogDriver, LogDriver, StderrLogDriver, SyslogLogDriver};
use Vichan\Data\ReportQueries;
use Vichan\Service\HCaptchaQuery;
use Vichan\Service\SecureImageCaptchaQuery;
use Vichan\Service\ReCaptchaQuery;
use Vichan\Service\YandexCaptchaQuery;
use Vichan\Service\RemoteCaptchaQuery;

defined('TINYBOARD') or exit;

class Context {
	private array $definitions;

	public function __construct(array $definitions) {
		$this->definitions = $definitions;
	}

	public function get(string $name): mixed {
		if (!isset($this->definitions[$name])) {
			throw new \RuntimeException("Could not find a dependency named $name");
		}

		$ret = $this->definitions[$name];
		if (is_callable($ret) && !is_string($ret) && !is_array($ret)) {
			$ret = $ret($this);
			$this->definitions[$name] = $ret;
		}
		return $ret;
	}
}

function build_context(array $config): Context {
	return new Context([
		'config' => $config,
		LogDriver::class => function($c) {
			$config = $c->get('config');

			$name = $config['log_system']['name'];
			$level = $config['debug'] ? LogDriver::DEBUG : LogDriver::NOTICE;
			$backend = $config['log_system']['type'];

			$legacy_syslog = isset($config['syslog']) && $config['syslog'];

			// Check 'syslog' for backwards compatibility.
			if ($legacy_syslog || $backend === 'syslog') {
				$log_driver = new SyslogLogDriver($name, $level, $config['log_system']['syslog_stderr']);
				if ($legacy_syslog) {
					$log_driver->log(LogDriver::NOTICE, 'The configuration setting \'syslog\' is deprecated. Please use \'log_system\' instead');
				}
				return $log_driver;
			} elseif ($backend === 'file') {
				return new FileLogDriver($name, $level, $config['log_system']['file_path']);
			} elseif ($backend === 'stderr') {
				return new StderrLogDriver($name, $level);
			} else {
				return new ErrorLogLogDriver($name, $level);
			}
		},
		HttpDriver::class => function($c) {
			$config = $c->get('config');
			return new HttpDriver($config['upload_by_url_timeout'], $config['max_filesize']);
		},
		RemoteCaptchaQuery::class => function($c) {
			$config = $c->get('config');
			$http = $c->get(HttpDriver::class);
			switch ($config['captcha']['provider']) {
				case 'recaptcha':
					return new ReCaptchaQuery($http, $config['captcha']['recaptcha']['secret']);
				case 'hcaptcha':
					return new HCaptchaQuery(
						$http,
						$config['captcha']['hcaptcha']['secret'],
						$config['captcha']['hcaptcha']['sitekey']
					);
				case 'yandexcaptcha':
					return new YandexCaptchaQuery($http, $config['captcha']['yandexcaptcha']['secret']);
				default:
					throw new \RuntimeException('No remote captcha service available');
			}
		},
		SecureImageCaptchaQuery::class => function($c) {
			$config = $c->get('config');
			if ($config['captcha']['provider'] !== 'native') {
				throw new \RuntimeException('No native captcha service available');
			}
			return new SecureImageCaptchaQuery(
				$c->get(HttpDriver::class),
				$config['domain'],
				$config['captcha']['native']['provider_check']
			);
		},
		CacheDriver::class => function($c) {
			// Use the global for backwards compatibility.
			return \cache::getCache();
		},
		\PDO::class => function($c) {
			global $pdo;
			// Ensure the PDO is initialized.
			sql_open();
			return $pdo;
		},
		ReportQueries::class => function($c) {
			$auto_maintenance = (bool)$c->get('config')['auto_maintenance'];
			$pdo = $c->get(\PDO::class);
			return new ReportQueries($pdo, $auto_maintenance);
		}
	]);
}
