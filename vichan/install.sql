-- phpMyAdmin SQL Dump
-- version 4.0.4.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 30, 2013 at 09:45 PM
-- Server version: 5.6.10
-- PHP Version: 5.3.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

-- --------------------------------------------------------

--
-- Table structure for table `antispam`
--

CREATE TABLE IF NOT EXISTS `antispam` (
  `board` varchar(58) CHARACTER SET utf8 NOT NULL,
  `thread` int(11) DEFAULT NULL,
  `hash` char(40) COLLATE ascii_bin NOT NULL,
  `created` int(11) NOT NULL,
  `expires` int(11) DEFAULT NULL,
  `passed` smallint(6) NOT NULL,
  PRIMARY KEY (`hash`),
  KEY `board` (`board`,`thread`),
  KEY `expires` (`expires`)
) ENGINE=MyISAM DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- --------------------------------------------------------

--
-- Table structure for table `bans`
--

CREATE TABLE IF NOT EXISTS `bans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ipstart` varbinary(16) NOT NULL,
  `ipend` varbinary(16) DEFAULT NULL,
  `created` int(10) unsigned NOT NULL,
  `expires` int(10) unsigned DEFAULT NULL,
  `board` varchar(58) DEFAULT NULL,
  `creator` int(10) NOT NULL,
  `reason` text,
  `seen` tinyint(1) NOT NULL,
  `post` blob,
  PRIMARY KEY (`id`),
  KEY `expires` (`expires`),
  KEY `ipstart` (`ipstart`,`ipend`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `boards`
--

CREATE TABLE IF NOT EXISTS `boards` (
  `uri` varchar(58) CHARACTER SET utf8 NOT NULL,
  `title` tinytext NOT NULL,
  `subtitle` tinytext,
  -- `indexed` boolean default true,
  PRIMARY KEY (`uri`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `boards`
--

INSERT INTO `boards` VALUES
('b', 'Random', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cites`
--

CREATE TABLE IF NOT EXISTS `cites` (
  `board` varchar(58) NOT NULL,
  `post` int(11) NOT NULL,
  `target_board` varchar(58) NOT NULL,
  `target` int(11) NOT NULL,
  KEY `target` (`target_board`,`target`),
  KEY `post` (`board`,`post`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ip_notes`
--

CREATE TABLE IF NOT EXISTS `ip_notes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(39) CHARACTER SET ascii NOT NULL,
  `mod` int(11) DEFAULT NULL,
  `time` int(11) NOT NULL,
  `body` text NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `ip_lookup` (`ip`, `time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `modlogs`
--

CREATE TABLE IF NOT EXISTS `modlogs` (
  `mod` int(11) NOT NULL,
  `ip` varchar(39) CHARACTER SET ascii NOT NULL,
  `board` varchar(58) CHARACTER SET utf8 DEFAULT NULL,
  `time` int(11) NOT NULL,
  `text` text NOT NULL,
  KEY `time` (`time`),
  KEY `mod`(`mod`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `mods`
--

CREATE TABLE IF NOT EXISTS `mods` (
  `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `password` varchar(256) CHARACTER SET ascii NOT NULL COMMENT 'SHA256',
  `version` varchar(64) CHARACTER SET ascii NOT NULL,
  `type` smallint(2) NOT NULL,
  `boards` text CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`,`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `mods`
--

INSERT INTO `mods` VALUES
(1, 'admin', 'cedad442efeef7112fed0f50b011b2b9bf83f6898082f995f69dd7865ca19fb7', '4a44c6c55df862ae901b413feecb0d49', 30, '*');

-- --------------------------------------------------------

--
-- Table structure for table `mutes`
--

CREATE TABLE IF NOT EXISTS `mutes` (
  `ip` varchar(39) NOT NULL,
  `time` int(11) NOT NULL,
  KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=ascii;

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE IF NOT EXISTS `news` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `time` int(11) NOT NULL,
  `subject` text NOT NULL,
  `body` text NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `noticeboard`
--

CREATE TABLE IF NOT EXISTS `noticeboard` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `mod` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `subject` text NOT NULL,
  `body` text NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pms`
--

CREATE TABLE IF NOT EXISTS `pms` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sender` int(11) NOT NULL,
  `to` int(11) NOT NULL,
  `message` text NOT NULL,
  `time` int(11) NOT NULL,
  `unread` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `to` (`to`, `unread`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE IF NOT EXISTS `reports` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `time` int(11) NOT NULL,
  `ip` varchar(39) CHARACTER SET ascii NOT NULL,
  `board` varchar(58) CHARACTER SET utf8 DEFAULT NULL,
  `post` int(11) NOT NULL,
  `reason` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `robot`
--

CREATE TABLE IF NOT EXISTS `robot` (
  `hash` varchar(40) COLLATE ascii_bin NOT NULL COMMENT 'SHA1',
  PRIMARY KEY (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- --------------------------------------------------------

--
-- Table structure for table `search_queries`
--

CREATE TABLE IF NOT EXISTS `search_queries` (
  `ip` varchar(39) NOT NULL,
  `time` int(11) NOT NULL,
  `query` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `theme_settings`
--

CREATE TABLE IF NOT EXISTS `theme_settings` (
  `theme` varchar(40) NOT NULL,
  `name` varchar(40) DEFAULT NULL,
  `value` text,
  KEY `theme` (`theme`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `flood`
--

CREATE TABLE IF NOT EXISTS `flood` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(39) NOT NULL,
  `board` varchar(58) CHARACTER SET utf8 NOT NULL,
  `time` int(11) NOT NULL,
  `posthash` char(32) NOT NULL,
  `filehash` char(32) DEFAULT NULL,
  `isreply` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`),
  KEY `posthash` (`posthash`),
  KEY `filehash` (`filehash`),
  KEY `time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=ascii COLLATE=ascii_bin AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ban_appeals`
--

CREATE TABLE IF NOT EXISTS `ban_appeals` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ban_id` int(10) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL,
  `message` text NOT NULL,
  `denied` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ban_id` (`ban_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE IF NOT EXISTS `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `board` varchar(58) CHARACTER SET utf8 DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `content` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_pages` (`name`,`board`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `nntp_references`
--

CREATE TABLE IF NOT EXISTS `nntp_references` (
  `board` varchar(30) NOT NULL,
  `id` int(11) unsigned NOT NULL,
  `message_id` varchar(255) CHARACTER SET ascii NOT NULL,
  `message_id_digest` varchar(40) CHARACTER SET ascii NOT NULL,
  `own` tinyint(1) NOT NULL,
  `headers` text,
  PRIMARY KEY (`message_id_digest`),
  UNIQUE KEY `message_id` (`message_id`),
  UNIQUE KEY `u_board_id` (`board`, `id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `captchas`
--

CREATE TABLE IF NOT EXISTS `captchas` (
  `cookie` VARCHAR(50),
  `extra` VARCHAR(200),
  `text` VARCHAR(255),
  `created_at` INT(11),
  PRIMARY KEY (`cookie`,`extra`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `telegrams`
--

CREATE TABLE IF NOT EXISTS `telegrams` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `mod_id` int(11) unsigned NOT NULL,
    `ip` varchar(39) CHARACTER SET ascii NOT NULL,
    `message` text NOT NULL,
    `seen` tinyint(1) NOT NULL DEFAULT FALSE,
    `created_at` INT(11),
    PRIMARY KEY(`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

