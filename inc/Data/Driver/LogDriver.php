<?php
namespace Vichan\Data\Driver;

defined('TINYBOARD') or exit;


interface LogDriver {
	public const EMERG = \LOG_EMERG;
	public const ERROR = \LOG_ERR;
	public const WARNING = \LOG_WARNING;
	public const NOTICE = \LOG_NOTICE;
	public const INFO = \LOG_INFO;
	public const DEBUG = \LOG_DEBUG;

	/**
	 * Log a message if the level of relevancy is at least the minimum.
	 *
	 * @param int $level Message level. Use Log interface constants.
	 * @param string $message The message to log.
	 */
	public function log(int $level, string $message): void;
}
