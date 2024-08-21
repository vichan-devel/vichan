<?php // Logging
namespace Vichan\Driver;

use InvalidArgumentException;
use RuntimeException;

defined('TINYBOARD') or exit;


class LogDrivers {
	public static function levelToString(int $level): string {
		switch ($level) {
			case Log::EMERG:
				return 'EMERG';
			case Log::ERROR:
				return 'ERROR';
			case Log::WARNING:
				return 'WARNING';
			case Log::NOTICE:
				return 'NOTICE';
			case Log::INFO:
				return 'INFO';
			case Log::DEBUG:
				return 'DEBUG';
			default:
				throw new InvalidArgumentException('Not a logging level');
		}
	}

	/**
	 * Log to syslog.
	 */
	public static function syslog(string $name, int $level, bool $print_stderr): Log {
		$flags = LOG_ODELAY;
		if ($print_stderr) {
			$flags |= LOG_PERROR;
		}

		if (!openlog($name, $flags, LOG_USER)) {
			throw new RuntimeException('Unable to open syslog');
		}

		return new class($level) implements Log {
			private $level;

			public function __construct(int $level) {
				$this->level = $level;
			}

			public function log(int $level, string $message): void {
				if ($level <= $this->level) {
					if (isset($_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'])) {
						// CGI
						syslog($level, "$message - client: {$_SERVER['REMOTE_ADDR']}, request: \"{$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}\"");
					} else {
						syslog($level, $message);
					}
				}
			}
		};
	}

	/**
	 * Log via the php function error_log.
	 */
	public static function error_log(string $name, int $level): Log {
		return new class($name, $level) implements Log {
			private string $name;
			private int $level;

			public function __construct(string $name, int $level) {
				$this->name = $name;
				$this->level = $level;
			}

			public function log(int $level, string $message): void {
				if ($level <= $this->level) {
					$lv = LogDrivers::levelToString($level);
					$line = "{$this->name} $lv: $message";
					error_log($line, 0, null, null);
				}
			}
		};
	}

	/**
	 * Log to a file.
	 */
	public static function file(string $name, int $level, string $file_path): Log {
		/*
		 * error_log is slow as hell in it's 3rd mode, so use fopen + file locking instead.
		 * https://grobmeier.solutions/performance-ofnonblocking-write-to-files-via-php-21082009.html
		 *
		 * Whatever file appending is atomic is contentious:
		 *  - There are no POSIX guarantees: https://stackoverflow.com/a/7237901
		 *  - But linus suggested they are on linux, on some filesystems: https://web.archive.org/web/20151201111541/http://article.gmane.org/gmane.linux.kernel/43445
		 *  - But it doesn't seem to be always the case: https://www.notthewizard.com/2014/06/17/are-files-appends-really-atomic/
		 *
		 * So we just use file locking to be sure.
		 */

		$fd = fopen($file_path, 'a');
		if ($fd === false) {
			throw new RuntimeException("Unable to open log file at $file_path");
		}

		$logger = new class($name, $level, $fd) implements Log {
			private string $name;
			private int $level;
			private mixed $fd;

			public function __construct(string $name, int $level, mixed $fd) {
				$this->name = $name;
				$this->level = $level;
				$this->fd = $fd;
			}

			public function log(int $level, string $message): void {
				if ($level <= $this->level) {
					$lv = LogDrivers::levelToString($level);
					$line = "{$this->name} $lv: $message\n";
					flock($this->fd, LOCK_EX);
					fwrite($this->fd, $line);
					flock($this->fd, LOCK_UN);
				}
			}

			public function close() {
				fclose($this->fd);
			}
		};

		// Close the file on shutdown.
		register_shutdown_function([$logger, 'close']);

		return $logger;
	}

	/**
	 * Log to php's standard error file stream.
	 */
	public static function stderr(string $name, int $level): Log {
		return new class($name, $level) implements Log {
			private $name;
			private $level;

			public function __construct(string $name, int $level) {
				$this->name = $name;
				$this->level = $level;
			}

			public function log(int $level, string $message): void {
				if ($level <= $this->level) {
					$lv = LogDrivers::levelToString($level);
					fwrite(STDERR, "{$this->name} $lv: $message\n");
				}
			}
		};
	}

	/**
	 * No-op logging system.
	 */
	public static function none(): Log {
		return new class() implements Log {
			public function log($level, $message): void {
				// No-op.
			}
		};
	}
}

interface Log {
	public const EMERG = LOG_EMERG;
	public const ERROR = LOG_ERR;
	public const WARNING = LOG_WARNING;
	public const NOTICE = LOG_NOTICE;
	public const INFO = LOG_INFO;
	public const DEBUG = LOG_DEBUG;


	/**
	 * Log a message if the level of relevancy is at least the minimum.
	 *
	 * @param int $level Message level. Use Log interface constants.
	 * @param string $message The message to log.
	 */
	public function log(int $level, string $message): void;
}
