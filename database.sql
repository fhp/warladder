-- phpMyAdmin SQL Dump
-- version 3.4.11.1deb2+deb7u1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 05, 2014 at 01:33 AM
-- Server version: 5.5.38
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
-- Table structure for table `gamePlayers`
--

CREATE TABLE IF NOT EXISTS `gamePlayers` (
  `gameID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  UNIQUE KEY `gameID` (`gameID`,`userID`),
  UNIQUE KEY `userID` (`userID`,`gameID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ladderAdmins`
--

CREATE TABLE IF NOT EXISTS `ladderAdmins` (
  `ladderID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  PRIMARY KEY (`ladderID`,`userID`),
  UNIQUE KEY `userID` (`userID`,`ladderID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ladderChat`
--

CREATE TABLE IF NOT EXISTS `ladderChat` (
  `chatLineID` int(11) NOT NULL AUTO_INCREMENT,
  `ladderID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `timestamp` int(11) NOT NULL,
  PRIMARY KEY (`chatLineID`),
  KEY `ladderID` (`ladderID`),
  KEY `userID` (`userID`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ladderGames`
--

CREATE TABLE IF NOT EXISTS `ladderGames` (
  `gameID` int(11) NOT NULL AUTO_INCREMENT,
  `ladderID` int(11) NOT NULL,
  `templateID` int(11) NOT NULL,
  `warlightGameID` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `htmlName` text,
  `status` enum('RUNNING','FINISHED') NOT NULL,
  `winningUserID` int(11) DEFAULT NULL,
  `startTime` int(11) NOT NULL,
  `endTime` int(11) DEFAULT NULL,
  PRIMARY KEY (`gameID`),
  UNIQUE KEY `warlightGameID` (`warlightGameID`),
  KEY `ladderID` (`ladderID`,`endTime`),
  KEY `endTime` (`endTime`),
  KEY `templateID` (`templateID`),
  KEY `winningUserID` (`winningUserID`),
  KEY `ladderID_2` (`ladderID`,`startTime`,`status`,`endTime`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ladderPlayers`
--

CREATE TABLE IF NOT EXISTS `ladderPlayers` (
  `userID` int(11) NOT NULL,
  `ladderID` int(11) NOT NULL,
  `mu` double NOT NULL,
  `sigma` double NOT NULL,
  `rating` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `joinStatus` enum('JOINED','SIGNEDUP','REJECTED','BOOTED') NOT NULL,
  `active` tinyint(1) NOT NULL,
  `simultaneousGames` int(11) NOT NULL,
  `joinTime` int(11) NOT NULL,
  `emailInterval` enum('NEVER','DAILY','WEEKLY','MONTHLY') NOT NULL,
  PRIMARY KEY (`userID`,`ladderID`),
  KEY `ladderID` (`ladderID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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

-- --------------------------------------------------------

--
-- Table structure for table `ladderTemplates`
--

CREATE TABLE IF NOT EXISTS `ladderTemplates` (
  `templateID` int(11) NOT NULL AUTO_INCREMENT,
  `ladderID` int(11) NOT NULL,
  `warlightTemplateID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`templateID`),
  UNIQUE KEY `ladderID` (`ladderID`,`templateID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `playerLadderTemplates`
--

CREATE TABLE IF NOT EXISTS `playerLadderTemplates` (
  `userID` int(11) NOT NULL,
  `ladderID` int(11) NOT NULL,
  `templateID` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `canPlay` tinyint(1) NOT NULL,
  PRIMARY KEY (`userID`,`ladderID`,`templateID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `userID` int(20) NOT NULL AUTO_INCREMENT,
  `warlightUserID` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `color` varchar(12) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `emailConfirmation` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`userID`),
  UNIQUE KEY `warlightUserID` (`warlightUserID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=102 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
