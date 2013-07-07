CREATE DATABASE  IF NOT EXISTS `dune` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `dune`;
-- MySQL dump 10.13  Distrib 5.5.16, for Win32 (x86)
--
-- Host: BARNARD-1079    Database: dune
-- ------------------------------------------------------
-- Server version	5.1.36

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `Actors`
--

DROP TABLE IF EXISTS `Actors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Actors` (
  `Name` varchar(255) DEFAULT NULL,
  `thumb` text,
  `SortName` varchar(255) DEFAULT NULL,
  KEY `Name` (`Name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Alphabet`
--

DROP TABLE IF EXISTS `Alphabet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Alphabet` (
  `Index` int(11) NOT NULL,
  `Description` varchar(45) NOT NULL,
  PRIMARY KEY (`Index`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Movies`
--

DROP TABLE IF EXISTS `Movies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Movies` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `MoviePath` text,
  `Type` tinyint(1) DEFAULT NULL,
  `ListTitle` text,
  `HasPoster` tinyint(1) DEFAULT NULL,
  `HasFanart` tinyint(1) DEFAULT NULL,
  `HasNfo` tinyint(1) DEFAULT NULL,
  `HasTrailer` tinyint(1) DEFAULT NULL,
  `HasSub` tinyint(1) DEFAULT NULL,
  `HasExtra` tinyint(1) DEFAULT NULL,
  `New` tinyint(1) DEFAULT NULL,
  `Mark` tinyint(1) DEFAULT NULL,
  `Source` text,
  `Imdb` text,
  `Lock2` tinyint(1) DEFAULT NULL,
  `Title` text,
  `OriginalTitle` text,
  `Year` text,
  `Rating` text,
  `Votes` text,
  `MPAA` text,
  `Top250` text,
  `Country` text,
  `Outline` text,
  `Plot` text,
  `Tagline` text,
  `Certification` text,
  `Genre` text,
  `Studio` text,
  `Runtime` text,
  `ReleaseDate` text,
  `Director` text,
  `Credits` text,
  `Playcount` text,
  `HasWatched` tinyint(1) DEFAULT '0',
  `Trailer` text,
  `PosterPath` text,
  `FanartPath` text,
  `ExtraPath` text,
  `NfoPath` text,
  `TrailerPath` text,
  `SubPath` text,
  `FanartURL` text,
  `UseFolder` tinyint(1) DEFAULT NULL,
  `OutOfTolerance` tinyint(1) DEFAULT NULL,
  `FileSource` text,
  `NeedsSave` tinyint(1) DEFAULT NULL,
  `SortTitle` text,
  `DateAdd` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=1561 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `MoviesAStreams`
--

DROP TABLE IF EXISTS `MoviesAStreams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `MoviesAStreams` (
  `MovieID` int(11) NOT NULL,
  `StreamID` int(11) NOT NULL,
  `Audio_Language` text,
  `Audio_LongLanguage` text,
  `Audio_Codec` text,
  `Audio_Channel` text,
  `Audio_Bitrate` text,
  PRIMARY KEY (`MovieID`,`StreamID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `MoviesActors`
--

DROP TABLE IF EXISTS `MoviesActors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `MoviesActors` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `MovieID` int(11) NOT NULL,
  `ActorName` varchar(255) NOT NULL,
  `Role` text,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `IDACTOR` (`MovieID`,`ActorName`)
) ENGINE=MyISAM AUTO_INCREMENT=3306303 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `MoviesDirectors`
--

DROP TABLE IF EXISTS `MoviesDirectors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `MoviesDirectors` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `MovieID` int(11) NOT NULL,
  `Director` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `MoviesDirectors` (`MovieID`,`Director`)
) ENGINE=MyISAM AUTO_INCREMENT=1612 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `MoviesFanart`
--

DROP TABLE IF EXISTS `MoviesFanart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `MoviesFanart` (
  `ID` int(11) NOT NULL,
  `MovieID` int(11) NOT NULL,
  `preview` text,
  `thumbs` text,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `MoviesGenres`
--

DROP TABLE IF EXISTS `MoviesGenres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `MoviesGenres` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `MovieID` int(11) NOT NULL,
  `Genre` varchar(45) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `MoviesGenres` (`MovieID`,`Genre`)
) ENGINE=MyISAM AUTO_INCREMENT=4449 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `MoviesPosters`
--

DROP TABLE IF EXISTS `MoviesPosters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `MoviesPosters` (
  `ID` int(11) NOT NULL,
  `MovieID` int(11) NOT NULL,
  `thumbs` text,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `MoviesSubs`
--

DROP TABLE IF EXISTS `MoviesSubs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `MoviesSubs` (
  `MovieID` int(11) NOT NULL,
  `StreamID` int(11) NOT NULL,
  `Subs_Language` text,
  `Subs_LongLanguage` text,
  `Subs_Type` text,
  `Subs_Path` text,
  PRIMARY KEY (`MovieID`,`StreamID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `MoviesVStreams`
--

DROP TABLE IF EXISTS `MoviesVStreams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `MoviesVStreams` (
  `MovieID` int(11) NOT NULL,
  `StreamID` int(11) NOT NULL,
  `Video_Width` text,
  `Video_Height` text,
  `Video_Codec` text,
  `Video_Duration` text,
  `Video_ScanType` text,
  `Video_AspectDisplayRatio` text,
  `Video_Language` text,
  `Video_LongLanguage` text,
  `Video_EncodedSettings` text,
  `Video_Bitrate` text,
  `Video_MultiView` text,
  PRIMARY KEY (`MovieID`,`StreamID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `MoviesWatched`
--

DROP TABLE IF EXISTS `MoviesWatched`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `MoviesWatched` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `FileName` varchar(255) NOT NULL,
  `PlayCount` int(11) NOT NULL,
  `LastPlayed` int(11) NOT NULL,
  `LastPosition` int(11) DEFAULT '0',
  `MovieID` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `FILENAME` (`FileName`),
  KEY `MovieID` (`MovieID`)
) ENGINE=MyISAM AUTO_INCREMENT=960 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Sources`
--

DROP TABLE IF EXISTS `Sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Sources` (
  `ID` int(11) NOT NULL,
  `Name` text NOT NULL,
  `path` text NOT NULL,
  `Recursive` tinyint(1) NOT NULL DEFAULT '0',
  `Foldername` tinyint(1) NOT NULL DEFAULT '0',
  `Single` tinyint(1) NOT NULL DEFAULT '0',
  `LastScan` varchar(32) NOT NULL DEFAULT '1900/01/01',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `TVAStreams`
--

DROP TABLE IF EXISTS `TVAStreams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TVAStreams` (
  `TVEpID` int(11) NOT NULL,
  `StreamID` int(11) NOT NULL,
  `Audio_Language` text,
  `Audio_LongLanguage` text,
  `Audio_Codec` text,
  `Audio_Channel` text,
  `Audio_Bitrate` text,
  PRIMARY KEY (`TVEpID`,`StreamID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `TVEpPaths`
--

DROP TABLE IF EXISTS `TVEpPaths`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TVEpPaths` (
  `ID` int(11) NOT NULL,
  `TVEpPath` text NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `TVEps`
--

DROP TABLE IF EXISTS `TVEps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TVEps` (
  `ID` int(11) NOT NULL,
  `TVShowID` int(11) NOT NULL,
  `Episode` int(11) DEFAULT NULL,
  `Title` text,
  `HasPoster` tinyint(1) NOT NULL DEFAULT '0',
  `HasFanart` tinyint(1) NOT NULL DEFAULT '0',
  `HasNfo` tinyint(1) NOT NULL DEFAULT '0',
  `New` tinyint(1) DEFAULT '0',
  `Mark` tinyint(1) NOT NULL DEFAULT '0',
  `TVEpPathID` int(11) NOT NULL,
  `Source` text NOT NULL,
  `Lock2` tinyint(1) NOT NULL DEFAULT '0',
  `Season` int(11) DEFAULT NULL,
  `Rating` text,
  `Plot` text,
  `Aired` text,
  `Director` text,
  `Credits` text,
  `PosterPath` text,
  `FanartPath` text,
  `NfoPath` text,
  `NeedsSave` tinyint(1) NOT NULL DEFAULT '0',
  `Missing` tinyint(1) NOT NULL DEFAULT '0',
  `Playcount` text,
  `HasWatched` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `TVEpPathIDTVShowID` (`TVEpPathID`,`TVShowID`,`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `TVSeason`
--

DROP TABLE IF EXISTS `TVSeason`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TVSeason` (
  `TVShowID` int(11) NOT NULL,
  `SeasonText` text,
  `Season` int(11) NOT NULL,
  `HasPoster` tinyint(1) NOT NULL DEFAULT '0',
  `HasFanart` tinyint(1) NOT NULL DEFAULT '0',
  `PosterPath` text,
  `FanartPath` text,
  `Lock2` tinyint(1) NOT NULL DEFAULT '0',
  `Mark` tinyint(1) NOT NULL DEFAULT '0',
  `New` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`TVShowID`,`Season`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `TVShowActors`
--

DROP TABLE IF EXISTS `TVShowActors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TVShowActors` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `TVShowID` int(11) NOT NULL,
  `ActorName` varchar(255) NOT NULL,
  `Role` text,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `IDACTOR` (`TVShowID`,`ActorName`)
) ENGINE=MyISAM AUTO_INCREMENT=74928 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `TVShows`
--

DROP TABLE IF EXISTS `TVShows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TVShows` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Title` text,
  `HasPoster` tinyint(1) NOT NULL DEFAULT '0',
  `HasFanart` tinyint(1) NOT NULL DEFAULT '0',
  `HasNfo` tinyint(1) NOT NULL DEFAULT '0',
  `New` tinyint(1) DEFAULT '0',
  `Mark` tinyint(1) NOT NULL DEFAULT '0',
  `TVShowPath` text NOT NULL,
  `Source` text NOT NULL,
  `TVDB` text,
  `Lock2` tinyint(1) NOT NULL DEFAULT '0',
  `EpisodeGuide` text,
  `Plot` text,
  `Genre` text,
  `Premiered` text,
  `Studio` text,
  `MPAA` text,
  `Rating` text,
  `PosterPath` text,
  `FanartPath` text,
  `NfoPath` text,
  `NeedsSave` tinyint(1) NOT NULL DEFAULT '0',
  `Language` text,
  `Ordering` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=204 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `TVShowsGenres`
--

DROP TABLE IF EXISTS `TVShowsGenres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TVShowsGenres` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `TVShowID` int(11) NOT NULL,
  `Genre` varchar(45) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `TVShowsGenres` (`TVShowID`,`Genre`)
) ENGINE=MyISAM AUTO_INCREMENT=357 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `TVSources`
--

DROP TABLE IF EXISTS `TVSources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TVSources` (
  `ID` int(11) NOT NULL,
  `Name` text NOT NULL,
  `path` text NOT NULL,
  `LastScan` varchar(32) NOT NULL DEFAULT '1900/01/01',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `TVSubs`
--

DROP TABLE IF EXISTS `TVSubs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TVSubs` (
  `TVEpID` int(11) NOT NULL,
  `StreamID` int(11) NOT NULL,
  `Subs_Language` text,
  `Subs_LongLanguage` text,
  PRIMARY KEY (`TVEpID`,`StreamID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `TVVStreams`
--

DROP TABLE IF EXISTS `TVVStreams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TVVStreams` (
  `TVEpID` int(11) NOT NULL,
  `StreamID` int(11) NOT NULL,
  `Video_Width` text,
  `Video_Height` text,
  `Video_Codec` text,
  `Video_Duration` text,
  `Video_ScanType` text,
  `Video_AspectDisplayRatio` text,
  `Video_Language` text,
  `Video_LongLanguage` text,
  `Video_EncodedSettings` text,
  `Video_Bitrate` text,
  `Video_MultiView` text,
  PRIMARY KEY (`TVEpID`,`StreamID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `TVWatched`
--

DROP TABLE IF EXISTS `TVWatched`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TVWatched` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `FileName` varchar(255) NOT NULL,
  `PlayCount` int(11) NOT NULL,
  `LastPlayed` int(11) NOT NULL,
  `LastPosition` int(11) DEFAULT '0',
  `TVEpPathID` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `FILENAME` (`FileName`,`TVEpPathID`),
  KEY `TVEpPathID` (`TVEpPathID`)
) ENGINE=MyISAM AUTO_INCREMENT=10990 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `TVWatched2`
--

DROP TABLE IF EXISTS `TVWatched2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TVWatched2` (
  `ID` int(11) NOT NULL,
  `FileName` varchar(255) NOT NULL,
  `PlayCount` int(11) NOT NULL,
  `LastPlayed` int(11) NOT NULL,
  `LastPosition` int(11) DEFAULT '0',
  `TVEpPathID` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `FILENAME` (`FileName`),
  KEY `TVEpPathID` (`TVEpPathID`)
) ENGINE=MyISAM AUTO_INCREMENT=9970 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-07-07 10:55:15
