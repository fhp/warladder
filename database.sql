-- phpMyAdmin SQL Dump
-- version 3.4.10.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 14, 2014 at 09:00 PM
-- Server version: 5.5.40
-- PHP Version: 5.4.34-0+deb7u1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `warladder`
--

-- --------------------------------------------------------

--
-- Table structure for table `ladders`
--

CREATE TABLE IF NOT EXISTS `ladders` (
  `ladderID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `summary` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `accessibility` enum('PUBLIC','MODERATED') NOT NULL,
  `visibility` enum('PUBLIC','PRIVATE') NOT NULL,
  `active` tinyint(1) NOT NULL,
  `minSimultaneousGames` int(11) DEFAULT NULL,
  `maxSimultaneousGames` int(11) DEFAULT NULL,
  PRIMARY KEY (`ladderID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
