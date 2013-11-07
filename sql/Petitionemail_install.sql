-- MySQL dump 10.13  Distrib 5.5.33, for debian-linux-gnu (i686)
--
-- Host: localhost    Database: civicrm_drup
-- ------------------------------------------------------
-- Server version	5.5.33-1

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
-- Table structure for table `civicrm_petition_email`
--

DROP TABLE IF EXISTS `civicrm_petition_email`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `civicrm_petition_email` (
  `petition_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The SID of the petition.',
  `recipient_email` varchar(128) DEFAULT NULL COMMENT 'The email of the petition target.',
  `recipient_name` varchar(128) DEFAULT NULL COMMENT 'The display name of the petition target.',
  `default_message` text COMMENT 'The default message for the petition',
  `message_field` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The ID of the custom field used for petition messages.',
  `subject` varchar(128) DEFAULT NULL COMMENT 'The subject line for outgoing emails.',
  PRIMARY KEY (`petition_id`),
  KEY `petition_id` (`petition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores recipient and message information for petitions...';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-11-06 21:30:20
