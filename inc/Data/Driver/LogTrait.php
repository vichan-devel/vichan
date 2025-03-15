<?php
namespace Vichan\Data\Driver;

defined('TINYBOARD') or exit;


trait LogTrait {
	public static function levelToString(int $level): string {
		switch ($level) {
			case LogDriver::EMERG:
				return 'EMERG';
			case LogDriver::ERROR:
				return 'ERROR';
			case LogDriver::WARNING:
				return 'WARNING';
			case LogDriver::NOTICE:
				return 'NOTICE';
			case LogDriver::INFO:
				return 'INFO';
			case LogDriver::DEBUG:
				return 'DEBUG';
			default:
				throw new \InvalidArgumentException('Not a logging level');
		}
	}
}
