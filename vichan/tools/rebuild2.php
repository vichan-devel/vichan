#!/usr/bin/php
<?php

/*
 *  rebuild.php - rebuilds all static files
 *  This is much different than the one in vichan because we have way more static files. It will not work without pcntl_fork.
 *  You must specify the things you want to rebuild. By default the script does nothing.
 *  Example of how to use:
 *	php rebuild.php --cache --js --indexes --processes 5
 *  That will clear the cache, rebuild all JS files and all indexes, and fork 5 processes to do it faster.
 *  I removed the quiet option, it's useless. Just use output redirection.
 */

require dirname(__FILE__) . '/inc/cli.php';

$start = microtime(true);

// parse command line
$opts = getopt('', Array('board:', 'themes', 'js', 'indexes', 'threads', 'processes:', 'cache', 'postmarkup', 'api'));
$options = Array();
$global_locale = $config['locale'];

// Do only one board?
$options['board'] = isset($opts['board']) ? $opts['board'] : (isset($opts['b']) ? $opts['b'] : false);
// Clear the cache?
$options['cache'] = isset($opts['cache']);
// Rebuild themes (catalogs)?
$options['themes'] = isset($opts['themes']);
// Rebuild JS?
$options['js'] = isset($opts['js']);
// Rebuild indexes? (e.g. /b/index.html)
$options['indexes'] = isset($opts['indexes']);
// Rebuild threads? (e.g. /b/res/1.html)
$options['threads'] = isset($opts['threads']);
// Rebuild all post markup? (e.g. /b/res/1.html#2)
$options['postmarkup'] = isset($opts['postmarkup']);
// Rebuild API pages? (e.g. /b/res/1.json')
$options['api'] = isset($opts['api']);
// How many processes?
$options['processes'] = isset($opts['processes']) ? $opts['processes'] : 1;

echo "== Tinyboard + vichan {$config['version']} ==\n";	

if ($options['cache']) {
	echo "Clearing template cache...\n";
	load_twig();
	$twig->clearCacheFiles();
}

if($options['themes']) {
	echo "Regenerating theme files...\n";
	rebuildThemes('all');
}

if($options['js']) {
	echo "Generating Javascript file...\n";
	buildJavascript();
}

$main_js = $config['file_script'];

$boards = listBoards();
//$boards = array(array('uri'=>'test'), array('uri'=>'tester'), array('uri'=>'testing'));
$boards_m = array_chunk($boards, floor(sizeof($boards)/$options['processes']));

function doboard($board) {
	global $global_locale, $config, $main_js, $options;
	$config['mask_db_error'] = false;
	if (!$options['api']) $config['api']['enabled'] = false;

	echo "Opening board /{$board['uri']}/...\n";
	// Reset locale to global locale
	$config['locale'] = $global_locale;
	init_locale($config['locale'], 'error');
	openBoard($board['uri']);
	$config['try_smarter'] = false;
	
	if($config['file_script'] != $main_js && $options['js']) {
		// different javascript file
		echo "(/{$board['uri']}/) Generating Javascript file...\n";
		buildJavascript();
	}
	
	
	if ($options['indexes']) {
		echo "(/{$board['uri']}/) Creating index pages...\n";
		buildIndex();
	}
	
	if($options['postmarkup']) {
		$query = query(sprintf("SELECT `id` FROM ``posts_%s``", $board['uri'])) or error(db_error());
		while($post = $query->fetch()) {
			echo "(/{$board['uri']}/) Rebuilding #{$post['id']}...\n";
			rebuildPost($post['id']);
		}
	}
	
	if ($options['threads']) {
		$query = query(sprintf("SELECT `id` FROM ``posts_%s`` WHERE `thread` IS NULL", $board['uri'])) or error(db_error());
		while($post = $query->fetch()) {
			echo "(/{$board['uri']}/) Rebuilding #{$post['id']}...\n";
			@buildThread($post['id']);
		}
	}
}

$children = array();
foreach ($boards_m as $i => $bb) {
	$pid = pcntl_fork();

	if ($pid == -1) {
		die('Fork failed?');
	} else if ($pid) {
		echo "Started PID #$pid...\n";
		$children[] = $pid;
	} else {
		unset($pdo);
		$i = 0;
		$total = sizeof($bb);
		sql_open();
		foreach ($bb as $i => $b) {
			$i++;
			doboard($b);
			echo "I'm on board $i/$total\n";
		}	
		break;
	}
}


printf("Complete! Took %g seconds\n", microtime(true) - $start);

unset($board);

foreach ($children as $child) {
	pcntl_waitpid($child, $status);
	unset($children[$child]);
}

//modLog('Rebuilt everything using tools/rebuild.php');
