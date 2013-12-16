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
    $this->executeSqlFile('sql/Petitionemail_install.sql');
  }

  /**
   * Delete civicrm_peition_email table during uninstall
   *
   */
  public function uninstall() {
    $this->executeSqlFile('sql/Petitionemail_uninstall.sql');
  }

}
