<?php
namespace Vichan\Data\Driver;

defined('TINYBOARD') or exit;


/**
 * Log to a file.
 */
class FileLogDriver implements LogDriver {
	use LogTrait;

	private string $name;
	private int $level;
	private mixed $fd;

	public function __construct(string $name, int $level, string $file_path) {
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

		$this->fd = \fopen($file_path, 'a');
		if ($this->fd === false) {
			throw new \RuntimeException("Unable to open log file at $file_path");
		}

		$this->name = $name;
		$this->level = $level;

		// In some cases PHP does not run the destructor.
		\register_shutdown_function([$this, 'close']);
	}

	public function __destruct() {
		$this->close();
	}

	public function log(int $level, string $message): void {
		if ($level <= $this->level) {
			$lv = $this->levelToString($level);
			$line = "{$this->name} $lv: $message\n";
			\flock($this->fd, LOCK_EX);
			\fwrite($this->fd, $line);
			\fflush($this->fd);
			\flock($this->fd, LOCK_UN);
		}
	}

	public function close() {
		\flock($this->fd, LOCK_UN);
		\fclose($this->fd);
	}
}
