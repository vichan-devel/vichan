<?php
namespace Vichan\Data\Driver;

defined('TINYBOARD') or exit;

/**
 * Log to syslog.
 */
class SyslogLogDriver implements LogDriver {
	private int $level;

	public function __construct(string $name, int $level, bool $print_stderr) {
		$flags = \LOG_ODELAY;
		if ($print_stderr) {
			$flags |= \LOG_PERROR;
		}

		if (!\openlog($name, $flags, \LOG_USER)) {
			throw new \RuntimeException('Unable to open syslog');
		}

		$this->level = $level;
	}

	public function log(int $level, string $message): void {
		if ($level <= $this->level) {
			if (isset($_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'])) {
				// CGI
				\syslog($level, "$message - client: {$_SERVER['REMOTE_ADDR']}, request: \"{$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}\"");
			} else {
				\syslog($level, $message);
			}
		}
	}
}
