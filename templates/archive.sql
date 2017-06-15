CREATE TABLE IF NOT EXISTS ``archive_{{ board }}`` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `snippet` text NOT NULL,
  `lifetime` int(11) NOT NULL,
  `files` mediumtext NOT NULL,
  `featured` int(1) NOT NULL,
  `mod_archived` int(1) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `lifetime` (`lifetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
