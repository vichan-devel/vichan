<?php
/**
 * Performs maintenance tasks. Invoke this periodically if the auto_maintenance configuration option is turned off.
 */

require dirname(__FILE__) . '/inc/cli.php';

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
$deleted_tot = $deleted_count;

$time_tot = number_format((float)$time_tot, 4, '.', '');
modLog("Deleted $deleted_tot expired entries in {$time_tot}s with maintenance tool");
