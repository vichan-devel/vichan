
--
-- Table structure for table `votes_archive`
--

CREATE TABLE `votes_archive` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `board` varchar(58) CHARACTER SET utf8mb4 NOT NULL,
  `thread_id` int(10) NOT NULL,
  `ip` varchar(61) CHARACTER SET ascii NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `ip` (`ip`, `board`, `thread_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
