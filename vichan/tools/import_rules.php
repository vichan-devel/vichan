<?php
// This script imports rules.txt files from the old system into the new ``pages`` table.

require dirname(__FILE__) . '/inc/cli.php';

$boards = listBoards(TRUE);

foreach ($boards as $i => $b) {
	$rules = @file_get_contents($b.'/rules.txt');
	if ($rules && !empty(trim($rules))) {
		$query = prepare('INSERT INTO ``pages``(name, title, type, board, content) VALUES("rules", "Rules", "html", :board, :content)');
		$query->bindValue(':board', $b);
		$query->bindValue(':content', $rules);
		$query->execute() or error(db_error($query));
	} 
}
