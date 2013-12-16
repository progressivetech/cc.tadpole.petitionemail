--
-- Table structure for table `civicrm_petition_email`
--

DROP TABLE IF EXISTS `civicrm_petition_email`;
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
