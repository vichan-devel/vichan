<?php
/**
 * Performs maintenance tasks. Invoke this periodically if the auto_maintenance configuration option is turned off.
 */

require dirname(__FILE__) . '/inc/cli.php';

echo "Clearing expired bans...";
$start = microtime(true);
$deleted_count = Bans::purge($config['require_ban_view']);
$delta = microtime(true) - $start;
echo "Deleted $deleted_count expired bans in $delta seconds!";
modLog('Deleted expired bans using tools/maintenance.php');
