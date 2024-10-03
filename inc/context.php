<?php
namespace Vichan;

use RuntimeException;
use Vichan\Driver\{Log, LogDrivers};
use Vichan\Data\Driver\{HttpDriver, HttpDrivers};
use Vichan\Service\HCaptchaQuery;
use Vichan\Service\NativeCaptchaQuery;
use Vichan\Service\ReCaptchaQuery;
use Vichan\Service\RemoteCaptchaQuery;

defined('TINYBOARD') or exit;

class Context {
	private array $definitions;

	public function __construct(array $definitions) {
		$this->definitions = $definitions;
	}

	public function get(string $name): mixed {
		if (!isset($this->definitions[$name])) {
			throw new RuntimeException("Could not find a dependency named $name");
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
		Log::class => function($c) {
			$config = $c->get('config');

			$name = $config['log_system']['name'];
			$level = $config['debug'] ? Log::DEBUG : Log::NOTICE;
			$backend = $config['log_system']['type'];

			// Check 'syslog' for backwards compatibility.
			if ((isset($config['syslog']) && $config['syslog']) || $backend === 'syslog') {
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
		},
		HttpDriver::class => function($c) {
			$config = $c->get('config');
			return HttpDrivers::getHttpDriver($config['upload_by_url_timeout'], $config['max_filesize']);
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
				default:
					throw new RuntimeException('No remote captcha service available');
			}
		},
		NativeCaptchaQuery::class => function($c) {
			$config = $c->get('config');
			if ($config['captcha']['provider'] !== 'native') {
				throw new RuntimeException('No native captcha service available');
			}
			return new NativeCaptchaQuery(
				$c->get(HttpDriver::class),
				$config['domain'],
				$config['captcha']['native']['provider_check'],
				$config['captcha']['native']['extra']
			);
		}
	]);
}
