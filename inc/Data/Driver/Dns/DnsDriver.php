<?php
namespace Vichan\Data\Driver\Dns;


interface DnsDriver {
	/**
	 * Resolve a domain name to 1 or more ips.
	 *
	 * @param string $name Domain name.
	 * @return ?array Returns an array of IPv4 and IPv6 addresses or null on error.
	 */
	public function nameToIPs(string $name): ?array;

	/**
	 * Resolve an ip address to a domain name.
	 *
	 * @param string $ip Ip address.
	 * @return ?array Returns the domain names or null on error.
	 */
	public function IPToNames(string $ip): ?array;
}
