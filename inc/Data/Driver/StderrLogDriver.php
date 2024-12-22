<?php
namespace Vichan\Data\Driver;

defined('TINYBOARD') or exit;


/**
 * Log to php's standard error file stream.
 */
class StderrLogDriver implements LogDriver {
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
			\fwrite(\STDERR, "{$this->name} $lv: $message\n");
		}
	}
}
