<?php
namespace Vichan\Data\Driver\Dns;


/**
 * For the love of god never use this implementation if you can.
 */
class LibcDnsDriver implements DnsDriver {
	public function __construct(int $timeout) {
		// Try to impose a very frail timeout https://www.php.net/manual/en/function.gethostbyname.php#118841
		\putenv("RES_OPTIONS=retrans:1 retry:1 timeout:{$timeout} attempts:1");
	}

	public function nameToIPs(string $name): ?array {
		$ret = \dns_get_record($name, DNS_A | DNS_AAAA);
		if ($ret === false) {
			return null;
		}

		$ips = [];
		foreach ($ret as $dns_record) {
			if ($dns_record['type'] == 'A') {
				$ips[] = $dns_record['ip'];
			} elseif ($dns_record['type'] == 'AAAA') {
				$ips[] = $dns_record['ipv6'];
			}
		}

		if (empty($ips)) {
			return [];
		} else {
			// Stable return order.
			\sort($ips, \SORT_STRING);
			return $ips;
		}
	}

	/**
	 * For the love of god never use this.
	 * https://www.php.net/manual/en/function.gethostbyaddr.php#57553
	 */
	public function IPToNames(string $ip): ?array {
		$ret = \gethostbyaddr($ip);
		if ($ret === $ip || $ret === false) {
			return null;
		}
		// Case extravaganza: https://www.php.net/manual/en/function.gethostbyaddr.php#123563
		return [ \strtolower($ret) ];
	}
}
