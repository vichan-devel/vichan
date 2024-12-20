<?php
/**
 * Performs maintenance tasks. Invoke this periodically if the auto_maintenance configuration option is turned off.
 */

use Vichan\Data\ReportQueries;

require dirname(__FILE__) . '/inc/cli.php';

$ctx = Vichan\build_context($config);

echo "Clearing expired bans...\n";
$start = microtime(true);
$deleted_count = Bans::purge($config['require_ban_view'], $config['purge_bans']);
$delta = microtime(true) - $start;
echo "Deleted $deleted_count expired bans in $delta seconds!\n";
$time_tot = $delta;
$deleted_tot = $deleted_count;

echo "Clearing old antispam...\n";
$start = microtime(true);
$deleted_count = purge_old_antispam();
$delta = microtime(true) - $start;
echo "Deleted $deleted_count expired antispam in $delta seconds!\n";
$time_tot = $delta;
$deleted_tot += $deleted_count;

echo "Clearing invalid reports...\n";
$report_queries = $ctx->get(ReportQueries::class);
$start = microtime(true);
$deleted_count = $report_queries->purge();
$delta = microtime(true) - $start;
echo "Deleted $deleted_count invalid reports in $delta seconds!\n";
$time_tot += $delta;
$deleted_tot += $deleted_count;

if ($config['cache']['enabled'] === 'fs') {
	$fs_cache = new Vichan\Data\Driver\FsCacheDriver(
		$config['cache']['prefix'],
		"tmp/cache/{$config['cache']['prefix']}",
		'.lock',
		false
	);
	$start = microtime(true);
	$fs_cache->collect();
	$delta = microtime(true) - $start;
	echo "Deleted $deleted_count expired filesystem cache items in $delta seconds!\n";
	$time_tot = $delta;
	$deleted_tot = $deleted_count;
}

$time_tot = number_format((float)$time_tot, 4, '.', '');
modLog("Deleted $deleted_tot expired entries in {$time_tot}s with maintenance tool");
