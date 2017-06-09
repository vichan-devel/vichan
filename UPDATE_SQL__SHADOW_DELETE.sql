




-- --------------------------------------------------------

--
-- Table structure for table `shadow_antispam`
--

CREATE TABLE IF NOT EXISTS `shadow_antispam` (
  `board` varchar(58) NOT NULL,
  `thread` int(11) DEFAULT NULL,
  `hash` char(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `created` int(11) NOT NULL,
  `expires` int(11) DEFAULT NULL,
  `passed` smallint(6) NOT NULL,
  PRIMARY KEY (`hash`),
  KEY `board` (`board`,`thread`),
  KEY `expires` (`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- --------------------------------------------------------

--
-- Table structure for table `shadow_cites`
--

CREATE TABLE IF NOT EXISTS `shadow_cites` (
  `board` varchar(58) NOT NULL,
  `post` int(11) NOT NULL,
  `target_board` varchar(58) NOT NULL,
  `target` int(11) NOT NULL,
  KEY `target` (`target_board`,`target`),
  KEY `post` (`board`,`post`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------------------

--
-- Table structure for table `shadow_deleted`
--

CREATE TABLE IF NOT EXISTS `shadow_deleted` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `board` varchar(58) NOT NULL,
  `post_id` int(10) NOT NULL,
  `del_time` int(11) NOT NULL,
  `files` text CHARACTER SET ascii NOT NULL,
  `cite_ids` text CHARACTER SET armscii8 NOT NULL,
  PRIMARY KEY (`id`),
  KEY `board` (`board`),
  KEY `post_id` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------------------

--
-- Table structure for table `shadow_filehashes`
--

CREATE TABLE IF NOT EXISTS `shadow_filehashes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `board` varchar(58) NOT NULL,
  `thread` int(11) NOT NULL,
  `post` int(11) NOT NULL,
  `filehash` text CHARACTER SET ascii NOT NULL,
  PRIMARY KEY (`id`),
  KEY `thread_id` (`thread`),
  KEY `post_id` (`post`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
