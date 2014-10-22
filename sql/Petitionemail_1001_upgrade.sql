--
-- SQL statements to handle the 1001 upgrade

ALTER TABLE civicrm_petition_email DROP COLUMN `recipient_email`;
ALTER TABLE civicrm_petition_email DROP COLUMN `recipient_name`;
ALTER TABLE civicrm_petition_email ADD COLUMN `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The ID of the CiviCRM Group representing the petition targets.' AFTER petition_id;
ALTER TABLE civicrm_petition_email ADD COLUMN `location_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The location type that should be used when selecting the target email address.' AFTER group_id;

