--
-- Table structure for table `civicrm_petition_email`
--

CREATE TABLE IF NOT EXISTS `civicrm_petition_email` (
  `petition_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The SID of the petition.',
  `location_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The location type that should be used when selecting the target email address.',
  `default_message` text COMMENT 'The default message for the petition',
  `message_field` varchar(128) COMMENT 'The name of the custom field used for petition messages.',
  `subject_field` varchar(255) DEFAULT NULL COMMENT "The custom field used to store the petition signer's personal subject.",
  `subject` varchar(128) DEFAULT NULL COMMENT 'The subject line for outgoing emails.',
  `recipients` text COMMENT 'The name and email address of additional targets that should receive a copy of all petitions signed, separated by line breaks.',
  `insert_address` int(1) DEFAULT 1 COMMENT 'Whether or not to insert the sender address into the email.',
  PRIMARY KEY (`petition_id`),
  KEY `petition_id` (`petition_id`),
  CONSTRAINT `FK_civicrm_petition_email_petition_id` FOREIGN KEY (`petition_id`) REFERENCES `civicrm_survey` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores recipient and message information for petitions.';


