<?php
namespace Vichan\Service;

use Vichan\Data\Driver\CacheDriver;
use Vichan\Data\Driver\Dns\DnsDriver;
use Lifo\IP\IP;


class IpBlacklistService {
	private const DNS_CACHE_TIMEOUT = 3600; // 1 hour.

	private DnsDriver $resolver;
	private CacheDriver $cache;
	private array $blacklist_providers;
	private array $exceptions;
	private bool $rdns_validate;


	private static function buildDnsCacheKey(string $host) {
		return 'dns_queries_dns_' . \strtolower($host);
	}

	private static function buildRDnsCacheKey(string $ip) {
		return "dns_queries_rdns_$ip";
	}

	private static function reverseIpv4Octets(string $ip): ?string {
		$ret = \filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4);
		if ($ret === false) {
			return null;
		}
		return \implode('.', \array_reverse(\explode('.', $ip)));
	}

	private static function reverseIpv6Octets(string $ip): ?string {
		$ret = \filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6);
		if ($ret === false) {
			return null;
		}
		return \strrev(\implode(".", \str_split(\str_replace(':', '', IP::inet_expand($ip)))));
	}

	/**
	 * Builds the name/host to resolve to discover if an ip is the host via DNS blacklists.
	 */
	private static function buildEndpoint(string $host, string $ip) {
		$replaced = 0;
		// See inc/config.php for the meaning of '%'.
		$lookup = \str_replace('%', $ip, $host, $replaced);
		if ($replaced !== 0) {
			return $lookup;
		}
		return "$ip.$host";
	}

	private static function filterIp(string $str): string|false {
		return \filter_var($str, \FILTER_VALIDATE_IP);
	}

	private function isIpWhitelisted(string $ip): bool {
		if (\in_array($ip, $this->exceptions)) {
			return true;
		}

		if (\filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE) !== false) {
			return true;
		}

		return false;
	}

	private function isIpBlacklistedImpl(string $ip, string $rip): ?string {
		foreach ($this->blacklist_providers as $blacklist) {
			$blacklist_host = $blacklist;
			if (\is_array($blacklist)) {
				$blacklist_host = $blacklist[0];
			}

			// The name that will be looked up.
			$name = self::buildEndpoint($blacklist_host, $rip);

			// Do the actual check.
			$is_blacklisted = $this->checkNameResolves($name);

			if ($is_blacklisted) {
				// Pick the strategy to deal with this blacklisted host.

				if (!isset($blacklist[1])) {
					// Just block them.
					return $blacklist_host;
				} elseif (\is_array($blacklist[1])) {
					// Check if the blacklist applies only to some IPs.
					foreach ($blacklist[1] as $octet_or_ip) {
						if ($ip == $octet_or_ip || $ip == "127.0.0.$octet_or_ip") {
							return $blacklist_host;
						}
					}
				} elseif (\is_callable($blacklist[1])) {
					// Custom user provided function.
					if ($blacklist[1]($ip)) {
						return $blacklist_host;
					}
				} else {
					// Check if the blacklist only applies to a specific IP.
					if ($ip == $blacklist[1] || $ip == "127.0.0.{$blacklist[1]}") {
						return $blacklist_host;
					}
				}
			}
		}
		return null;
	}

	private function checkNameResolves(string $name): bool {
		$value = $this->cache->get(self::buildDnsCacheKey($name));
		if ($value === null) {
			$value = !empty($this->resolver->nameToIps(self::buildDnsCacheKey($name)));
			$this->cache->set(self::buildDnsCacheKey($name), $value, self::DNS_CACHE_TIMEOUT);
		}
		return $value;
	}


	/**
	 * Build a DNS accessor.
	 *
	 * @param DnsDriver $resolver DNS driver.
	 * @param CacheDriver $cache Cache driver.
	 * @param array $blacklists Array of DNS blacklist providers.
	 * @param array $exceptions Exceptions to the blacklists.
	 * @param bool $rdns_validate If to validate the Reverse DNS queries results.
	 */
	public function __construct(DnsDriver $resolver, CacheDriver $cache, array $blacklist_providers, array $exceptions, bool $rdns_validate) {
		$this->resolver = $resolver;
		$this->cache = $cache;
		$this->blacklist_providers = $blacklist_providers;
		$this->exceptions = $exceptions;
		$this->rdns_validate = $rdns_validate;
	}

	/**
	 * Is the given IP known to a blacklist and not whitelisted?
	 * Documentation: https://github.com/vichan-devel/vichan/wiki/dnsbl
	 *
	 * @param string $ip The ip to lookup.
	 * @return ?string Returns the hit blacklist if the IP is a in known blacklist. Null if the IP is not blacklisted.
	 * @throws InvalidArgumentException Throws if $ip is not a valid IPv4 or IPv6 address.
	 */
	public function isIpBlacklisted(string $ip): ?string {
		$rev_ip = false;
		$ret = self::reverseIpv4Octets($ip);
		if ($ret !== null) {
			$rev_ip = $ret;
		}
		$ret = self::reverseIpv6Octets($ip);
		if ($ret !== null) {
			$rev_ip = $ret;
		}

		if ($rev_ip === false) {
			throw new \InvalidArgumentException("'$ip' is not a valid ip address");
		}

		if ($this->isIpWhitelisted($ip)) {
			return null;
		}

		return $this->isIpBlacklistedImpl($ip, $rev_ip);
	}

	/**
	 * Performs the Reverse DNS lookup (rDNS) of the given IP.
	 * This function can be slow since may validate the response.
	 *
	 * @param string $ip The ip to lookup.
	 * @return array The hostnames of the given ip.
	 * @throws InvalidArgumentException Throws if $ip is not a valid IPv4 or IPv6 address.
	 */
	public function ipToNames(string $ip): ?array {
		$ret = self::filterIp($ip);
		if ($ret === false) {
			throw new \InvalidArgumentException("'$ip' is not a valid ip address");
		}

		$names = $this->cache->get(self::buildRDnsCacheKey($ret));
		if ($names !== null) {
			return $names;
		}

		$names = $this->resolver->IpToNames($ret);
		if ($names === false) {
			$this->cache->set(self::buildRDnsCacheKey($ret), [], self::DNS_CACHE_TIMEOUT);
			return [];
		}

		// Do we bother with validating the result?
		if (!$this->rdns_validate) {
			$this->cache->set(self::buildRDnsCacheKey($ret), $names, self::DNS_CACHE_TIMEOUT);
			return $names;
		}

		// Filter out the names that do not resolve to the given ip.
		$acc = [];
		foreach ($names as $name) {
			// Validate the response.
			$resolved_ips = $this->resolver->nameToIps($name);
			if ($resolved_ips !== null && \is_array($ret, $resolved_ips)) {
				$acc[] = $name;
			}
		}

		$this->cache->set(self::buildRDnsCacheKey($ret), $acc, self::DNS_CACHE_TIMEOUT);
		return $acc;
	}
}
