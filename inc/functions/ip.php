<?php
namespace Vichan\Functions\IP;

use Exception;

function fetch_maxmind($ip) {
    global $config;

    try {
        $reader = new \GeoIp2\Database\Reader($config['maxmind']['db_path'], $config['maxmind']['locale']);
        $record = $reader->city($ip);
        $countryCode = strtolower($record->country->isoCode);
    } catch (Exception $e) {
        return [
            $config['maxmind']['code_fallback'],
            $config['maxmind']['country_fallback'],
        ];
    }

    $countryName = $record->country->name;

    if (empty($countryName)) {
        $countryName = $config['maxmind']['country_fallback'];
        $countryCode = $config['maxmind']['code_fallback'];
    }

    return [$countryCode, $countryName];
}