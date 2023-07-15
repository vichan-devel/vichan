<?php
require dirname(__FILE__) . '/inc/cli.php';

echo "Adding column 'flg_warning' into bans table \n";
$query = query('ALTER TABLE ``bans`` ADD COLUMN `flg_warning` tinyint(1) DEFAULT 0 AFTER `post`;') or error(db_error($query));
echo "Done \n";