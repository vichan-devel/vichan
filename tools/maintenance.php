<?php
/**
 * Performs maintenance tasks. Invoke this periodically if the auto_maintenance configuration option is turned off.
 */

require dirname(__FILE__) . '/inc/cli.php';

echo "Clearing expired bans...";
$start = microtime(true);
$deleted_count = Bans::purge($config['require_ban_view'], $config['purge_bans']);
$delta = microtime(true) - $start;
echo "Deleted $deleted_count expired bans in $delta seconds!";
modLog("Deleted expired bans in {$delta}s with tools/maintenance.php");

echo "Clearing old antispam...";
$start = microtime(true);
$deleted_count = purge_old_antispam();
$delta = microtime(true) - $start;
echo "Deleted $deleted_count expired antispam in $delta seconds!";
modLog("Deleted expired antispam in {$delta}s with tools/maintenance.php");
