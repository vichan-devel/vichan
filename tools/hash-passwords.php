<?php

require_once dirname(__FILE__) . '/inc/cli.php';

foreach (listBoards(true) as $uri) {
	query(\sprintf('ALTER TABLE ``posts_%s`` MODIFY `password` varchar(64) DEFAULT NULL;', $uri)) or error(db_error());
	$query = prepare(\sprintf("SELECT DISTINCT `password` FROM ``posts_%s``", $uri));
	$query->execute() or error(db_error($query));

	while($entry = $query->fetch(\PDO::FETCH_ASSOC)) {
		$update_query = prepare(\sprintf("UPDATE ``posts_%s`` SET `password` = :password WHERE `password` = :password_org", $uri));
		$update_query->bindValue(':password', hashPassword($entry['password']));
		$update_query->bindValue(':password_org', $entry['password']);
		$update_query->execute() or error(db_error());
	}
}
