-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 16, 2017 at 05:04 PM
-- Server version: 5.7.18-0ubuntu0.16.04.1
-- PHP Version: 7.0.15-0ubuntu0.16.04.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gitvichan`
--

-- --------------------------------------------------------

--
-- Table structure for table `warnings`
--
CREATE TABLE `nicenotices` (
  `id` int(10) UNSIGNED NOT NULL,
  `ip` varchar(61) NOT NULL,
  `created` int(10) UNSIGNED NOT NULL,
  `board` varchar(58) DEFAULT NULL,
  `creator` int(10) NOT NULL,
  `reason` text,
  `seen` tinyint(1) NOT NULL,
  `post` blob
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `warnings`
--
ALTER TABLE `nicenotices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ipstart` (`ip`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `warnings`
--
ALTER TABLE `nicenotices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
