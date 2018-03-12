--
-- SQL statements to handle the 1002 upgrade

ALTER TABLE civicrm_petition_email ADD COLUMN `insert_address` int(1) DEFAULT 1 COMMENT "Whether or not to insert the sender address into the email." AFTER recipients;
