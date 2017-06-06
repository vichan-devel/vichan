<?php

// Run in Cron by using  "cd /var/www/html/tools/ && /usr/bin/php ./archive_cli.php"

require dirname(__FILE__) . '/inc/cli.php';

// Make sure cript is run from commandline interface
if(php_sapi_name() !== 'cli')
    exit();

// Set config variables so we aren't hindered in archiving or purging.
$config['archive']['cron_job']['archiving'] = false;
$config['archive']['cron_job']['purge'] = false;

// Get list of all boards
$boards = listBoards();

// Go through all boards cleaning the catalog and pruning archive
foreach($boards as &$board) {
    // Set Dir Value
	$board['dir'] = sprintf($config['board_path'], $board['uri']);
	
    // Open board "config"
	openBoard($board['uri']);

    // Archive Threads that are pushed off Catalog
    clean();
    // Clean Archive Purge old entries off it
    Archive::RebuildArchiveIndexes();
}



?>