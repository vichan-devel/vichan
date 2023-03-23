#!/usr/bin/php
<?php
/*
 *  delete-stray-images.php - there was a period when undoImage() was not working at all. This meant that
 *  if an error occured while uploading an image, the uploaded images might not have been deleted.
 *
 *  This script iterates through every board and deletes any stray files in src/ or thumb/ that don't
 *  exist in the database.
 *
 */

require dirname(__FILE__) . '/inc/cli.php';

$boards = listBoards();

foreach ($boards as $board) {
	echo "/{$board['uri']}/... ";
	
	openBoard($board['uri']);
	
	$query = query(sprintf("SELECT `file`, `thumb` FROM ``posts_%s`` WHERE `file` IS NOT NULL", $board['uri']));
	$valid_src = array();
	$valid_thumb = array();
	
	while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
		$valid_src[] = $post['file'];
		$valid_thumb[] = $post['thumb'];
	}
	
	$files_src = array_map('basename', glob($board['dir'] . $config['dir']['img'] . '*'));
	$files_thumb = array_map('basename', glob($board['dir'] . $config['dir']['thumb'] . '*'));
	
	$stray_src = array_diff($files_src, $valid_src);
	$stray_thumb = array_diff($files_thumb, $valid_thumb);
	
	$stats = array(
		'deleted' => 0,
		'size' => 0
	);
	
	foreach ($stray_src as $src) {
		$stats['deleted']++;
		$stats['size'] = filesize($board['dir'] . $config['dir']['img'] . $src);
		if (!file_unlink($board['dir'] . $config['dir']['img'] . $src)) {
			$er = error_get_last();
			die("error: " . $er['message'] . "\n");
		}
	}
		
	foreach ($stray_thumb as $thumb) {
		$stats['deleted']++;
		$stats['size'] = filesize($board['dir'] . $config['dir']['thumb'] . $thumb);
		if (!file_unlink($board['dir'] . $config['dir']['thumb'] . $thumb)) {
			$er = error_get_last();
			die("error: " . $er['message'] . "\n");
		}
	}
	
	echo sprintf("deleted %s files (%s)\n", $stats['deleted'], format_bytes($stats['size']));
}
