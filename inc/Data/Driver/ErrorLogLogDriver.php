<?php
namespace Vichan\Data\Driver;

defined('TINYBOARD') or exit;


/**
 * Log via the php function error_log.
 */
class ErrorLogLogDriver implements LogDriver {
	use LogTrait;

	private string $name;
	private int $level;

	public function __construct(string $name, int $level) {
		$this->name = $name;
		$this->level = $level;
	}

	public function log(int $level, string $message): void {
		if ($level <= $this->level) {
			$lv = $this->levelToString($level);
			$line = "{$this->name} $lv: $message";
			\error_log($line, 0, null, null);
		}
	}
}
