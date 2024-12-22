<?php

require_once dirname(__FILE__) . '/inc/cli.php';

$boards = listBoards();
		foreach ($boards as &$_board) {
			query(sprintf('ALTER TABLE ``posts_%s`` MODIFY `password` varchar(64) DEFAULT NULL;', $_board['uri'])) or error(db_error());
		    $query = prepare(sprintf("SELECT DISTINCT `password` FROM ``posts_%s``", $_board['uri']));
            $query->execute() or error(db_error($query));

		    while($entry = $query->fetch(PDO::FETCH_ASSOC)) {
		        $update_query = prepare(sprintf("UPDATE ``posts_%s`` SET `password` = :password WHERE `password` = :password_org", $_board['uri']));
		        $update_query->bindValue(':password', hashPassword($entry['password']));
		        $update_query->bindValue(':password_org', $entry['password']);
		        $update_query->execute() or  error(db_error());
		    }
		}
