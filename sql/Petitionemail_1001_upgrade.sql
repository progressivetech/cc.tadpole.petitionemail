--
-- SQL statements to handle the 1001 upgrade

ALTER TABLE civicrm_petition_email DROP COLUMN `recipient_email`;
ALTER TABLE civicrm_petition_email DROP COLUMN `recipient_name`;
ALTER TABLE civicrm_petition_email CHANGE COLUMN message_field message_field varchar(128);
ALTER TABLE civicrm_petition_email ADD COLUMN `location_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The location type that should be used when selecting the target email address.';
ALTER TABLE civicrm_petition_email ADD CONSTRAINT `FK_civicrm_petition_email_petition_id` FOREIGN KEY (`petition_id`) REFERENCES `civicrm_survey` (`id`) ON DELETE CASCADE;
