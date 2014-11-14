--
-- SQL statements to handle the 1002 upgrade

ALTER TABLE civicrm_petition_email ADD COLUMN `subject_field` varchar(255) DEFAULT NULL COMMENT "The custom field used to store the petition signer's personal subject." AFTER default_message;
