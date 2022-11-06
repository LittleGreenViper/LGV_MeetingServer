-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Nov 06, 2022 at 11:22 PM
-- Server version: 5.7.34
-- PHP Version: 8.0.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `LGV_MeetingServerTest`
--

-- --------------------------------------------------------

--
-- Table structure for table `lgv_ms_meetings`
--

DROP TABLE IF EXISTS `lgv_ms_meetings`;
CREATE TABLE `lgv_ms_meetings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `server_id` int(10) UNSIGNED NOT NULL,
  `meeting_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `duration` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `longitude` float DEFAULT NULL,
  `latitude` float DEFAULT NULL,
  `weekday` tinyint(3) UNSIGNED DEFAULT NULL,
  `organization_key` varchar(32) COLLATE utf8_bin DEFAULT NULL,
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `comments` text COLLATE utf8_bin,
  `formats` text COLLATE utf8_bin,
  `physical_address` text COLLATE utf8_bin,
  `virtual_information` text COLLATE utf8_bin
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `lgv_ms_meetings`
--
ALTER TABLE `lgv_ms_meetings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `server_id` (`server_id`),
  ADD KEY `meeting_id` (`meeting_id`),
  ADD KEY `name` (`name`),
  ADD KEY `start_time` (`start_time`),
  ADD KEY `duration` (`duration`),
  ADD KEY `longitude` (`longitude`),
  ADD KEY `latitude` (`latitude`),
  ADD KEY `weekday` (`weekday`),
  ADD KEY `organization_key` (`organization_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `lgv_ms_meetings`
--
ALTER TABLE `lgv_ms_meetings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
