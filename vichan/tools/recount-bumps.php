<?php
// A script to recount bumps to recover from a last-page-bump attack
// or to be run after the KusabaX Migration.

require dirname(__FILE__) . '/inc/cli.php';

if (!isset ($argv[1])) {
	die("Usage: tools/recount-bumps.php board_uri\n");
}
$board = $argv[1];

$q = query(sprintf("SELECT `id`, `bump`, `time` FROM ``posts_%s``
                    WHERE `thread` IS NULL", $board));
while ($val = $q->fetch()) {
        $lc = prepare(sprintf('SELECT MAX(`time`) AS `aq` FROM ``posts_%s``
                               WHERE ((`thread` = :thread and
			       `email` != "sage" ) OR `id` = :thread', $board));
		
	$lc->bindValue(":thread", $val['id']);
	$lc->execute();

	$f = $lc->fetch();
        if ($val['bump'] != $f['aq']) {
                $query = prepare(sprintf("UPDATE ``posts_%s`` SET `bump`=:bump
	                                  WHERE `id`=:id", $board));
		$query->bindValue(":bump", $f['aq']);
		$query->bindValue(":id", $val['id']);
                echo("Thread $val[id] - to be $val[bump] -> $f[aq]\n");
        }
	else {
		echo("Thread $val[id] ok\n");
	}
}

echo("done\n");
