<?php

class CRM_Petitionemail_Form_Report_PetitionEmail extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_customGroupGroupBy = FALSE; function __construct() {
    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'id' => array(
            'display' => FALSE,
            'required' => TRUE,
          ),
          'display_name' => array(
            'title' => ts('Contact Name'),
            'required' => TRUE,
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
          'contacts_matched' => array(
            'title' => ts('Contacts Matched'),
          ),
          'emails_sent' => array(
            'title' => ts('Emails Sent'),
          ),
        ),
      ),
      'civicrm_petition_email' => array(
        'dao' => 'CRM_Campaign_DAO_Survey',
        'filters' => array(
          'petition_id' => array(
            'title' => ts("Petition"),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $this->get_petition_options()
          ),
        ),
      ),
      'civicrm_group_contact' => array(
        'dao' => 'CRM_Contact_DAO_GroupContact',
        'filters' => array(
          'group_id' => array(
            'title' => ts("Group"),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $this->get_group_options(),
          ),
        ),
      )
    );
    parent::__construct();
  }

  function get_group_options() {
    $choose_one = array('' => ts("Choose one"));
    return $choose_one + CRM_Core_PseudoConstant::group('Mailing');
  }

  function get_petition_options() {
    $sql = "SELECT s.id, s.title FROM civicrm_survey s JOIN civicrm_petition_email e ".
      "ON s.id = e.petition_id";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $ret = array();
    while($dao->fetch()) {
      $ret[$dao->id] = $dao->title;
    }
    return $ret;
  }

  function preProcess() {
    $this->assign('reportTitle', ts('Petition Email Report'));
    parent::preProcess();
  }

  function select() {
    $select = array();
    // don't use our special fields which get populated in alterDisplay
    $special = array('contacts_matched', 'emails_sent');
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            $alias = "{$tableName}_{$fieldName}";
            if(in_array($fieldName, $special)) {
              $select[] = "1 AS $alias";
            }
            else {
              $select[] = "{$field['dbAlias']} as $alias";
            }
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_selectAliases[] = $alias;
          }
        }
      }
    }
    $this->_selectClauses = $select;
    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {
    $this->_from = "FROM civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}";
  }

  function where() {
    $petition_id = intval($this->_params['petition_id_value']);
    $group_id = NULL;
    if(array_key_exists('group_id_value', $this->_params)) {
      $group_id = intval($this->_params['group_id_value']);
    }
    $petition_activity_type_id = 
      intval(\CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Petition'));
    $activityContacts =
      CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $source_activity_record_type_id =
      intval(CRM_Utils_Array::key('Activity Source', $activityContacts));

    $this->_where = "WHERE ";
    $signed = '';
    $group = '';

    // Include people who have signed the petition OR people who are in the passed in group

    // First the signers.
    $signed = "{$this->_aliases['civicrm_contact']}.id IN (SELECT contact_id
      FROM civicrm_activity_contact ac JOIN civicrm_activity a ON
      ac.activity_id = a.id WHERE ac.record_type_id = $source_activity_record_type_id
      AND source_record_id = $petition_id AND a.activity_type_id = $petition_activity_type_id)";

    // Now the people in the specified group
    if($group_id) {
      // Check if we are a smart group or regular group
      $results = civicrm_api3('Group', 'getsingle', array('id' => $group_id));
      if(!empty($results['id'])) {
        $group = "{$this->_aliases['civicrm_contact']}.id IN (SELECT contact_id FROM ";
        if(!empty($results['saved_search_id'])) {
          // Populate the cache
          CRM_Contact_BAO_GroupContactCache::check($group_id);
          $group .= "civicrm_group_contact_cache cc WHERE cc.group_id = $group_id)";
        }
        else {
          $group .= "civicrm_group_contact gc WHERE gc.group_id = $group_id
            AND gc.status = 'Added')";
        }
      }
    }
    if(!empty($group)) {
      $this->_where .= " ($signed) OR ($group) ";
    }
    else {
      $this->_where .= "$signed";
    }
  }

  function alterDisplay(&$rows) {
    $petition_id = $this->_params['petition_id_value'];
    foreach ($rows as $rowNum => $row) {
      if(array_key_exists('civicrm_contact_contacts_matched', $row)) {
        $recipients = petitionemail_get_recipients($row['civicrm_contact_id'], $petition_id);
        $contacts_matched = array();
        while(list(,$recipient) = each($recipients)) {
          if(!empty($recipient['contact_id'])) {
            $contacts_matched[] = $this->convert_contact_to_link($recipient['name'], $recipient['contact_id']); 
          } 
   
        } 
        $rows[$rowNum]['civicrm_contact_contacts_matched'] = implode($contacts_matched, ',');
      }
      if(array_key_exists('civicrm_contact_emails_sent', $row)) {
        $emails_sent = $this->get_emails_sent_for_contact($row['civicrm_contact_id'], $petition_id);
        $rows[$rowNum]['civicrm_contact_emails_sent'] = implode($emails_sent, ',');
      }
      if(array_key_exists('civicrm_contact_display_name', $row)) {
        $rows[$rowNum]['civicrm_contact_display_name'] = 
          $this->convert_contact_to_link($row['civicrm_contact_display_name'], $row['civicrm_contact_id']);
      }
    }
  }

  function get_emails_sent_for_contact($contact_id, $petition_id) {
    $ret = array();
    $activityContacts =
      CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $source_activity_record_type_id =
      intval(CRM_Utils_Array::key('Activity Source', $activityContacts));
    $target_activity_record_type_id =
      intval(CRM_Utils_Array::key('Activity Targets', $activityContacts));
    $email_activity_type_id = 
      intval(\CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Email'));

    $sql = "SELECT DISTINCT c.id, display_name FROM civicrm_contact c JOIN
      civicrm_activity_contact ac ON c.id = ac.contact_id WHERE record_type_id = %0
      AND activity_id IN (SELECT a.id FROM civicrm_activity a JOIN
      civicrm_activity_contact ac ON a.id = ac.activity_id WHERE 
      record_type_id = %1 AND source_record_id = %2 AND activity_type_id = %3
      AND contact_id = %4)";
    $params = array(
      0 => array($target_activity_record_type_id, 'Integer'),
      1 => array($source_activity_record_type_id, 'Integer'),
      2 => array($petition_id, 'Integer'),
      3 => array($email_activity_type_id, 'Integer'),
      4 => array($contact_id, 'Integer')
    );
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while($dao->fetch()) {
      $ret[] = $this->convert_contact_to_link($dao->display_name, $dao->id);
    }
    return $ret;
  }

  function convert_contact_to_link($name, $contact_id) {
    $url = CRM_Utils_System::url('civicrm/contact/view', array('cid' => $contact_id));
    return '<a href="' . $url . '">' . $name . '</a>'; 
  }
}
