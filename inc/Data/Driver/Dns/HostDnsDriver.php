<?php
namespace Vichan\Data\Driver\Dns;


/**
 * Relies on the `host` command line executable.
 */
class HostDnsDriver implements DnsDriver {
	private int $timeout;

	private static function matchOrEmpty(string $pattern, string $subject): array {
		$ret = \preg_match_all($pattern, $subject, $out);
		if ($ret === false || $ret === 0) {
			return [];
		}
		return $out[1];
	}

	public function __construct(int $timeout) {
		$this->timeout = $timeout;
	}

	public function nameToIPs(string $name): ?array {
		$ret = shell_exec_error("host -W {$this->timeout} {$name}");
		if ($ret === false) {
			return null;
		}

		$ipv4 = self::matchOrEmpty('/has address ([^\s]+)/', $ret);
		$ipv6 = self::matchOrEmpty('/has IPv6 address ([^\s]+)/', $ret);
		return \array_merge($ipv4, $ipv6);
	}

	public function IPToNames(string $ip): ?array {
		$ret = shell_exec_error("host -W {$this->timeout} {$ip}");
		if ($ret === false) {
			return null;
		}

		$names = self::matchOrEmpty('/domain name pointer ([^\s]+)\./', $ret);
		return \array_map(fn($n) => \strtolower(\rtrim($n, '.')), $names);
	}
}
