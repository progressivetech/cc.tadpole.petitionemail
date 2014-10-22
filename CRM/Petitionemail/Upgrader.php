<?php

/**
 * Install and uninstall functions
 *
 */
class CRM_Petitionemail_Upgrader extends CRM_Petitionemail_Upgrader_Base {

  /**
   * Create civicrm_petition_email table during install 
   *
  */
  public function install() {
    // Nothing to do. All *_install.sql files installed automatically.
  }

  /**
   * Delete civicrm_peition_email table during uninstall
   *
   */
  public function uninstall() {
    // Nothing to do. All *_uninstall.sql files installed automatically.
  }

  /**
   * Add new fields and table allowing for dynamic targets to be selected.
   *
  */
  function upgrade_1001() {
    // First create the two new tables
    if(!$this->executeSqlFile('sql/Petitionemail_target_install.sql')) return FALSE;
    if(!$this->executeSqlFile('sql/Petitionemail_matching_field_install.sql')) return FALSE;

    // Now transfer the data to the new tables
    $sql = "SELECT petition_id, recipient_email, recipient_name FROM civicrm_petition_email";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while($dao->fetch()) {
      $target = '"' . $dao->recipient_name . '" <' . $dao->recipient_email . '>';
      $sql = "INSERT INTO civicrm_petition_email_target SET petition_id = %0, target = %1";
      $params = array(
        0 => array($dao->petition_id, 'Integer'),
        1 => array($target, 'String')
      );
      CRM_Core_DAO::executeQuery($sql, $params);
    }
    if(!$this->executeSqlFile('sql/Petitionemail_1001_upgrade.sql')) return FALSE;
    return TRUE;
  }
}
