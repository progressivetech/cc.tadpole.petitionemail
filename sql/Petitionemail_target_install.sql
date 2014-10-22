--
-- Table structure for table `civicrm_petition_email_target`
--

CREATE TABLE IF NOT EXISTS `civicrm_petition_email_target` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'The unique ID of the target.',
  `petition_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The SID of the petition.',
  `target` varchar(255) COMMENT 'The name and email address of additional targets that should receive a copy of all petitions signed.',
  PRIMARY KEY (`id`),
  KEY `petition_id` (`petition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores the additional targets that should receive a copy of all petitions signed.';
