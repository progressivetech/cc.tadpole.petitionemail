--
-- Table structure for table `civicrm_petition_email_matching_field`
--

CREATE TABLE IF NOT EXISTS `civicrm_petition_email_matching_field` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'The unique ID of the email field.',
  `petition_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The SID of the petition.',
  `matching_field` text COMMENT 'The name of the field that should match between petition signer and target.',
  `matching_group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The group that should match against this field.', 
  PRIMARY KEY (`id`),
  KEY `petition_id` (`petition_id`),
  CONSTRAINT `FK_civicrm_petition_email_matching_field_petition_id` FOREIGN KEY (`petition_id`) REFERENCES `civicrm_survey` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores the fields used to match petition signers and targets.';

