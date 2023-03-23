#!/usr/bin/php
<?php

/*
 *  rebuild.php - rebuilds all static files
 * 
 *  Command line arguments:
 *     -q, --quiet
 *          Suppress output. 
 *
 *     --quick
 *          Do not rebuild posts.
 *
 *     -b, --board <string>
 *          Rebuild only the specified board.
 *
 *     -f, --full
 *          Rebuild replies as well as threads (re-markup).
 *
 */

require dirname(__FILE__) . '/inc/cli.php';

require_once("inc/bans.php");

$start = microtime(true);

// parse command line
$opts = getopt('qfb:', Array('board:', 'quick', 'full', 'quiet'));
$options = Array();
$global_locale = $config['locale'];

$options['board'] = isset($opts['board']) ? $opts['board'] : (isset($opts['b']) ? $opts['b'] : false);
$options['quiet'] = isset($opts['q']) || isset($opts['quiet']);
$options['quick'] = isset($opts['quick']);
$options['full'] = isset($opts['full']) || isset($opts['f']);

if(!$options['quiet'])
	echo "== Tinyboard + vichan {$config['version']} ==\n";	

if(!$options['quiet'])
	echo "Clearing template cache...\n";

load_twig();
$twig->clearCacheFiles();

if(!$options['quiet'])
	echo "Regenerating theme files...\n";
rebuildThemes('all');

if(!$options['quiet'])
	echo "Generating Javascript file...\n";
buildJavascript();

$main_js = $config['file_script'];

$boards = listBoards();

foreach($boards as &$board) {
	if($options['board'] && $board['uri'] != $options['board'])
		continue;
	
	if(!$options['quiet'])
		echo "Opening board /{$board['uri']}/...\n";
	// Reset locale to global locale
	$config['locale'] = $global_locale;
	openBoard($board['uri']);
	$config['try_smarter'] = false;
	
	if($config['file_script'] != $main_js) {
		// different javascript file
		if(!$options['quiet'])
			echo "Generating Javascript file...\n";
		buildJavascript();
	}
	
	
	if(!$options['quiet'])
		echo "Creating index pages...\n";
	buildIndex();
	
	if($options['quick'])
		continue; // do no more
	
	if($options['full']) {
		$query = query(sprintf("SELECT `id` FROM ``posts_%s``", $board['uri'])) or error(db_error());
		while($post = $query->fetch()) {
			if(!$options['quiet'])
				echo "Rebuilding #{$post['id']}...\n";
			rebuildPost($post['id']);
		}
	}
	
	$query = query(sprintf("SELECT `id` FROM ``posts_%s`` WHERE `thread` IS NULL", $board['uri'])) or error(db_error());
	while($post = $query->fetch()) {
		if(!$options['quiet'])
			echo "Rebuilding #{$post['id']}...\n";
		buildThread($post['id']);
	}
}

if(!$options['quiet'])
	printf("Complete! Took %g seconds\n", microtime(true) - $start);

unset($board);
modLog('Rebuilt everything using tools/rebuild.php');

