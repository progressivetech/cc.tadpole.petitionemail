--
-- Table structure for table `civicrm_petition_email`
--

CREATE TABLE IF NOT EXISTS `civicrm_petition_email` (
  `petition_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The SID of the petition.',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The ID of the CiviCRM Group representing the petition targets.',
  `location_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The location type that should be used when selecting the target email address.',
  `default_message` text COMMENT 'The default message for the petition',
  `message_field` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The ID of the custom field used for petition messages.',
  `subject` varchar(128) DEFAULT NULL COMMENT 'The subject line for outgoing emails.',
  `recipients` text COMMENT 'The name and email address of additional targets that should receive a copy of all petitions signed, separated by line breaks.',
  PRIMARY KEY (`petition_id`),
  KEY `petition_id` (`petition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores recipient and message information for petitions.';


