<?php
namespace Vichan\Functions\IP;

use Vichan\Data\Driver\Log\LogDriver;
use GeoIp2\Database\Reader;


function fetch_maxmind(LogDriver $log, string $ip, string $db_path, string $locale, string $code_fallback, string $country_fallback) {
	try {
		$reader = new Reader($db_path, $locale);
		$record = $reader->city($ip);
		$countryCode = \strtolower($record->country->isoCode);
	} catch (\Exception $e) {
		$log->log(LogDriver::ERROR, "Could not fetch country: {$e->getMessage()}");
		return [ $code_fallback, $country_fallback ];
	}

	$countryName = $record->country->name;

	if (empty($countryName)) {
		return [ $code_fallback, $country_fallback ];
	}

	return [ $countryCode, $countryName ];
}
