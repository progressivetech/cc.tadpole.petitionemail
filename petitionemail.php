<?php

require_once 'petitionemail.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function petitionemail_civicrm_config(&$config) {
  _petitionemail_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function petitionemail_civicrm_xmlMenu(&$files) {
  _petitionemail_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function petitionemail_civicrm_install() {
  return _petitionemail_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function petitionemail_civicrm_uninstall() {
  return _petitionemail_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function petitionemail_civicrm_enable() {
  return _petitionemail_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function petitionemail_civicrm_disable() {
  return _petitionemail_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function petitionemail_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _petitionemail_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function petitionemail_civicrm_managed(&$entities) {
  return _petitionemail_civix_civicrm_managed($entities);
}

/**
 * Implemention of hook_civicrm_buildForm
 */
function petitionemail_civicrm_buildForm( $formName, &$form ) {
  if ($formName == 'CRM_Campaign_Form_Petition_Signature') {  
    $survey_id = $form->getVar('_surveyId');
    if ($survey_id) {
      $sql = "SELECT petition_id, 
                  default_message, 
                  message_field, 
                  subject 
             FROM civicrm_petition_email 
             WHERE petition_id = %1";
      $params = array( 1 => array( $survey_id, 'Integer' ) );
      $dao = CRM_Core_DAO::executeQuery( $sql, $params );
      $defaults = array();
      $dao->fetch();
      $message_field = 'custom_' . $dao->message_field;
      $defaults[$message_field] = $dao->default_message;
      $form->setDefaults($defaults);
    }
  }

  if ($formName == 'CRM_Campaign_Form_Petition') {
    CRM_Core_Resources::singleton()->addScriptFile('cc.tadpole.petitionemail', 'petitionemail.js');
    $survey_id = $form->getVar('_surveyId');
    if ($survey_id) {
      // Set default values for saved petitions.
      $sql = "SELECT petition_id, 
                default_message, 
                message_field, 
                subject,
                recipients,
                location_type_id,
                group_id
              FROM civicrm_petition_email 
              WHERE petition_id = %1";
      $params = array( 1 => array( $survey_id, 'Integer' ) );
      $dao = CRM_Core_DAO::executeQuery( $sql, $params );
      $dao->fetch();
      if($dao->N > 0) {
        // Base table values.
        $defaults['email_petition'] = 1;
        $defaults['recipients'] = $dao->recipients;
        $defaults['default_message'] = $dao->default_message;
        $defaults['user_message'] = $dao->message_field;
        $defaults['subject'] = $dao->subject;
        $defaults['location_type_id'] = $dao->location_type_id;
        $defaults['group_id'] = $dao->group_id;
        
        // Now get matching fields.
        $sql = "SELECT matching_field FROM civicrm_petition_email_matching_field
          WHERE petition_id = %1";
        $dao = CRM_Core_DAO::executeQuery($sql, $params);
        $matching_fields = array();
        while($dao->fetch()) {
          $matching_fields[] = $dao->matching_field;
        }
        $defaults['matching_fields'] = $matching_fields;

        $form->setDefaults($defaults);
      }
    }

    // Now add our extra fields to the form.
    $form->add('checkbox', 'email_petition', ts('Send an email to a target'));

    // Get the Profiles in use by this petition so we can find out
    // if there are any potential fields for an extra message to the
    // petition target.
    $params = array('module' => 'CiviCampaign', 
                    'entity_table' => 'civicrm_survey', 
                    'entity_id' => $survey_id,
                    'rowCount' => 0);
    $join_results = civicrm_api3('UFJoin','get', $params);
    $custom_fields = array();
    $profile_ids = array();
    if ($join_results['is_error'] == 0) {
      foreach ($join_results['values'] as $join_value) {
        $profile_ids[] = $join_value['uf_group_id'];
      }
    }
    $custom_fields = petitionemail_get_textarea_fields($profile_ids);
    
    $custom_message_field_options = array();
    if(count($custom_fields) == 0) {
      $custom_message_field_options = array(
        '' => t('- No Text or TextArea fields defined in your profiles -')
      );
    }
    else {
      $custom_message_field_options = array('' => t('- Select -'));
      $custom_message_field_options = $custom_message_field_options + $custom_fields;
    }
    $choose_one = array('0' => ts('--choose one--'));
    $group_options = $choose_one + CRM_Core_PseudoConstant::group('Mailing');
    $location_options = $choose_one + 
      CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    $sql = "SELECT f.id, g.title, f.label FROM civicrm_custom_group g JOIN
      civicrm_custom_field f ON g.id = f.custom_group_id
      WHERE g.is_active = 1 AND f.is_active = 1 ORDER BY g.title, f.label";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $field_options = array();
    while($dao->fetch()) {
      $field_options[$dao->id] = $dao->title . '::' . $dao->label;
    }

    $form->add('select', 'group_id', ts('Matching Target Group'), $group_options);
    $form->addElement('advmultiselect', 'matching_fields', ts('Matching field(s)'), 
      $field_options, array('style' => 'width:400px;', 'class' => 'advmultiselect'));
    $form->add('select', 'location_type_id', ts('Email'), $location_options);
    $form->add('textarea', 'recipients', ts("Send petitions to"));
    $form->add('select', 'user_message', ts('Custom Message Field'),
      $custom_message_field_options);
    $form->add('textarea', 'default_message', ts('Default Message'));
    $form->add('text', 'subject', ts('Email Subject Line'));
  }
}

/**
 * Validate the petition form
 *
 * Ensure our values are consistent to avoid broken petitions.
 */
function petitionemail_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if($formName == 'CRM_Campaign_Form_Petition') {
    if(CRM_Utils_Array::value('email_petition', $fields)) {
      // If group_id is provided, make sure we also have location_type_id and at least one
      // matching field.
      $group_id = CRM_Utils_Array::value('group_id', $fields);
      $location_type_id = CRM_Utils_Array::value('location_type_id', $fields);
      $matching_fields = CRM_Utils_Array::value('matching_fields', $fields);

      if(!empty($group_id)) {
        if(empty($location_type_id) || empty($matching_fields)) {
          $msg = ts("If you select a matching target group you must select
            both the email type and at least one matching field.");
          $errors['group_id'] = $msg; 
        }
      }
      // If additional email targets have been provided, make sure they are
      // all syntactically correct.
      $recipients = CRM_Utils_Array::value('recipients', $fields);
      if(!empty($recipients)) {
        $recipient_array = explode("\n", $recipients);
        while(list(,$line) = each($recipient_array)) {
          if(FALSE === petitionemail_parse_email_line($line)) {
            $errors['recipients'] = ts("Invalid email address listed: %1.", array(1 => $line));
          }
        }
      }

      if(empty($group_id) && empty($recipients)) {
        $msg = ts("You must select either a target matching group or list
          at least one address to send all petitions to.");
        $errors['recipients'] = $msg;
      }
    }
  }
}

/**
 * Given an array of profile ids, list all text area fields
 */
function petitionemail_get_textarea_fields($profile_ids) {
  // Now get all fields in this profile
  $custom_fields = array();
  while(list(,$uf_group_id) = each($profile_ids)) {
    $params = array('uf_group_id' => $uf_group_id, 'rowCount' => 0);
    $field_results = civicrm_api3('UFField', 'get', $params);
    if ($field_results['is_error'] == 0) {
      foreach ($field_results['values'] as $field_value) {
        $field_name = $field_value['field_name'];
        if(!preg_match('/^custom_[0-9]+/', $field_name)) {
          // We only know how to lookup field types for custom
          // fields. Skip core fields.
          continue;
        }

        $id = substr(strrchr($field_name, '_'), 1);
        // Finally, see if this is a text or textarea field.
        $params = array('id' => $id);
        $custom_results = civicrm_api3('CustomField', 'get', $params);
        if ($custom_results['is_error'] == 0) {
          $field_value = array_pop($custom_results['values']);
          $html_type = $field_value['html_type'];
          $label = $field_value['label'];
          $id = $field_value['id'];
          if($html_type == 'Text' || $html_type == 'TextArea') {
            $custom_fields[$id] = $label;
          }
        }
      }
    }
  }
  return $custom_fields;
}


function petitionemail_civicrm_postProcess( $formName, &$form ) {
  if ($formName != 'CRM_Campaign_Form_Petition') { 
    return; 
  }
  if ( $form->_submitValues['email_petition'] == 1 ) {
    $survey_id = $form->getVar('_surveyId');
    $lastmoddate = 0;
    if (!$survey_id) {  // Ugly hack because the form doesn't return the id
      $params = array('title' =>$form->_submitValues['title']);
      $surveys = civicrm_api3("Survey", "get", $params);
      if (is_array($surveys['values'])) {
        foreach($surveys['values'] as $survey) {
          if ($lastmoddate > strtotime($survey['last_modified_date'])) { 
            continue; 
          }
          $lastmoddate = strtotime($survey['last_modified_date']);
          $survey_id = $survey['id'];
        }
      }
    }
    if (!$survey_id) {
      $msg = ts('Cannot find the petition for saving email delivery fields.');
      CRM_Core_Session::setStatus($msg);
      return;
    }

    $default_message =  $form->_submitValues['default_message'];
    $user_message = intval($form->_submitValues['user_message']);
    $subject = $form->_submitValues['subject'];
    $recipients = $form->_submitValues['recipients'];
    $group_id = $form->_submitValues['group_id'];
    $location_type_id = $form->_submitValues['location_type_id'];
    $matching_fields = $form->_submitValues['matching_fields'];

    $sql = "REPLACE INTO civicrm_petition_email (
             petition_id,
             default_message, 
             message_field, 
             subject,
             recipients,
             group_id,
             location_type_id
           ) VALUES ( 
             %1, 
             %2, 
             %3, 
             %4,
             %5,
             %6,
             %7
    )";
    $params = array( 
      1 => array( $survey_id, 'Integer' ),
      2 => array( $default_message, 'String' ),
      3 => array( $user_message, 'String' ),
      4 => array( $subject, 'String' ),
      5 => array( $recipients, 'String' ),
      6 => array( $group_id, 'Integer' ),
      7 => array( $location_type_id, 'Integer' ),
    );
    $petitionemail = CRM_Core_DAO::executeQuery( $sql, $params );

    // Now insert fields into fields table
    reset($matching_fields);
    // delete any existing ones
    $sql = "DELETE FROM civicrm_petition_email_matching_field WHERE
      petition_id = %0";
    $params = array(0 => array($survey_id, 'Integer'));
    CRM_Core_DAO::executeQuery($sql, $params);
    $sql = "INSERT INTO civicrm_petition_email_matching_field SET
      petition_id = %0, matching_field = %1";
    $params = array(0 => array($survey_id, 'Integer'));
    while(list(,$matching_field) = each($matching_fields)) {
      $params[1] = array($matching_field, 'String');
      CRM_Core_DAO::executeQuery($sql, $params);
    }
  }
}

/**
 * Implementation of hook_civicrm_post
 *
 * Run everytime a post is made to see if it's a new profile/activity
 * that should trigger a petition email to be sent.
 */
function petitionemail_civicrm_post( $op, $objectName, $objectId, &$objectRef ) {
  if ($objectName == 'Activity') {
    $activity_id = $objectId;

    // Only run on creation. For petition that require a confirmation,
    // after the petition has been created, see petitionemail_civicrm_pageRun().
    if($op == 'create') {
      if(petitionemail_is_actionable_activity($activity_id)) {
        petitionemail_process_signature($activity_id);
      }
    }
  }
}

/**
 * Implementation of hook_civicrm_pageRun
 */
function petitionemail_civicrm_pageRun(&$page) {
  // This should be fired after most of the parent run()
  // code is done, which means the activity status should
  // be converted to "complete" if it has been properly
  // verified.
  $pageName = $page->getVar('_name');
  if ($pageName == 'CRM_Campaign_Page_Petition_Confirm') { 
    // Get the activity id from the URL
    $activity_id  = CRM_Utils_Request::retrieve('a', 'String', CRM_Core_DAO::$_nullObject);
    if(petitionemail_is_actionable_activity($activity_id)) {
      petitionemail_process_signature($activity_id);
    }
  }
}

function petitionemail_process_signature($activity_id) {
  $petition_id = petitionemail_get_petition_id_for_activity($activity_id);
  $sql = "SELECT default_message, 
               message_field, 
               subject,
               group_id,
               location_type_id,
               recipients
         FROM civicrm_petition_email
         WHERE petition_id = %1 GROUP BY petition_id";
  $params = array( 1 => array( $petition_id, 'Integer' ) );
  $petition_email = CRM_Core_DAO::executeQuery( $sql, $params );
  $petition_email->fetch();
  if($petition_email->N == 0) {
    // Must not be a petition with a target.
    return;
  }

  // Store variables we need
  $default_message = $petition_email->default_message;
  $subject = $petition_email->subject;
  $group_id = $petition_email->group_id;
  $location_type_id = $petition_email->location_type_id;
  $message_field = $petition_email->message_field;
  $recipients = $petition_email->recipients;

  // Now retrieve the matching fields, if any
  $sql = "SELECT matching_field FROM civicrm_petition_email_matching_field
    WHERE petition_id = %1";
  $params = array( 1 => array( $petition_id, 'Integer' ) );
  $dao = CRM_Core_DAO::executeQuery($sql, $params);
  $matching_fields = array();
  while($dao->fetch()) {
    // Key the array to the custom id number and leave the value blank.
    // The value will be populated below with the value from the petition
    // signer.
    $key = 'custom_' . $dao->matching_field;
    $matching_fields[$key] = NULL;
  }

  // Figure out whether to use the user-supplied message or the default
  // message.
  $petition_message = NULL;
  // If the petition has specified a message field
  if(!empty($message_field)) {
    if(is_numeric($message_field)) {
      $message_field = 'custom_' . $message_field;
    }
    
    // Retrieve the value of the field for this activity
    $params = array('id' => $activity_id, 
      'return' => array($message_field, 'activity_type_id'));
    $result = civicrm_api3('Activity', 'getsingle', $params);
    if(!empty($result[$message_field])) {
      $petition_message = $result[$message_field];
    }
  } 

  // No user supplied message, use the default
  if(is_null($petition_message)) {
    $petition_message = $default_message;
  }
  $activity = civicrm_api3("Activity", "getsingle", array ('id' => $activity_id));
  $contact_id = $activity['source_contact_id'];
  $from = civicrm_api3("Contact", "getsingle", array ('id' => $contact_id));

  if (array_key_exists('email', $from) && !empty($from['email'])) {
    $from = $from['display_name'] . ' <' . $from['email'] . '>';
  } else {
    $domain = civicrm_api3("Domain", "get", array ());
    if ($domain['is_error'] != 0 || !is_array($domain['values'])) { 
      // Can't send email without a from address.
      $msg = ts("Failed to send petition email because from address not sent.");
      CRM_Core_Error::debug_log_message($msg);
      return; 
    }
    $from = '"' . $from['display_name'] . '"' . ' <' .
      $domain['values']['from_email'] . '>';
  }

  // Setup email message (except to address)
  $email_params = array( 
    'from'    => $from,
    'toName'  => NULL,
    'toEmail' => NULL,
    'subject' => $petition_email->subject,
    'text'    => $petition_message, 
    'html'    => $petition_message
  );

  // Get array of recipients
  $petition_vars = array(
    'recipients' => $recipients,
    'group_id' => $group_id,
    'matching_fields' => $matching_fields,
    'location_type_id' => $location_type_id
  );
  $recipients = petitionemail_get_recipients($contact_id, $petition_vars);
  while(list(, $recipient) = each($recipients)) {
    if(!empty($recipient['email'])) {
      $email_params['toName'] = $recipient['name'];
      $email_params['toEmail'] = $recipient['email'];
      $to = $email_params['toName'] . ' ' . $email_params['toEmail'];
      $success = CRM_Utils_Mail::send($email_params);

      if($success == 1) {
        CRM_Core_Session::setStatus( ts('Message sent successfully to') . " $to", '', 'success' );
      } else {
        CRM_Core_Session::setStatus( ts('Error sending message to') . " $to" );
      }
    }
  }
}
 
function petitionemail_get_recipients($contact_id, $petition_vars) {
  $ret = array();
  // First, parse the additional recipients, if any. These get the email
  // regarldess of who signs it.
  if(!empty($petition_vars['recipients'])) {
    $recipients = explode("\n", $petition_vars['recipients']);
    while(list(,$recipient) = each($recipients)) {
      $email_parts = petitionemail_parse_email_line($recipient); 
      if(FALSE !== $email_parts) {
        $ret[] = array(
          'name' => $email_parts['name'],
          'email' => $email_parts['email']
        );
      }
    }
  }
  // If there is a contact group, we do a complex query to figure out
  // which members of the group should be included as recipients.
  if(!empty($petition_vars['group_id'])) {
    // Get the values of the matching fields for the contact. These values
    // are used to match the contact who signed the petition with the 
    // contact or contacts in the target group.
    $matching_fields = $petition_vars['matching_fields'];
    $field_names = array_keys($matching_fields);
    $contact_params = array('return' => $field_names, 'id' => $contact_id);
    $contact = civicrm_api3('Contact', 'getsingle', $contact_params);
    while(list($matching_field) = each($matching_fields)) {
      $matching_fields[$matching_field] = $contact[$matching_field];
    } 

    $from = array();
    $where = array();
    $params = array();

    $group_id = $petition_vars['group_id'];
    // Retrieve details (specifically, find out if it's a smart group)
    $results = civicrm_api3('Group', 'getsingle', array('id' => $group_id));
    if(!empty($results['id'])) {
      if(!empty($results['saved_search_id'])) {
        // Populate the cache
        CRM_Contact_BAO_GroupContactCache::check($group_id);
        $from [] = 'civicrm_contact c JOIN civicrm_group_contact_cache cc ON
          c.id = cc.contact_id';
        $where[] = 'cc.group_id = %0';
        $params[0] = array($group_id, 'Integer');
      }
      else {
        $from[] = 'civicrm_contact c JOIN civicrm_group_contact gc ON
          c.id = gc.contact_id';
        $where[] = 'gc.group_id = %0';
        $where[] = 'gc.status = "Added"';
        $params[0] = array($group_id, 'Integer');
      }
    }

    // Now we gather information on the custom fields at play
    reset($matching_fields);
    $id = 1;
    while(list($matching_field, $value) = each($matching_fields)) {
      $sql = "SELECT column_name, table_name FROM civicrm_custom_group g 
        JOIN civicrm_custom_field f ON g.id = f.custom_group_id WHERE 
        f.id = %0";
      $custom_field_id = str_replace('custom_', '', $matching_field);
      $dao = CRM_Core_DAO::executeQuery($sql, array(0 => array($custom_field_id, 'Integer')));
      $dao->fetch();
      $from[] = "JOIN " . $dao->table_name . " ON " . $dao->table_name . ".entity_id = 
        c.id";
      $where[] = $dao->column_name . ' = %' . $id;
      // Fixme - we should use the proper data type for each custom field
      $params[$id] = array($value, 'String');
      $id++;
    }

    // Now add the right email lookup info
    $from[] = "JOIN civicrm_email e ON c.id = e.contact_id";
    $where[] = 'e.location_type_id = %' . $id;
    $params[$id] = array($petition_vars['location_type_id'], 'Integer');

    // put it all together
    $sql = "SELECT c.display_name, e.email FROM ";
    $sql .= implode("\n", $from);
    $sql .= " WHERE " . implode(" AND\n", $where);

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    while($dao->fetch()) {
      $ret[] = array(
        'name' => $dao->display_name,
        'email' => $dao->email
      );
    }
  }
  return $ret; 
}

/**
 * Convert name + email line into name and email parts
 *
 * Thanks: http://www.regular-expressions.info/email.html
 */
function petitionemail_parse_email_line($line) {
  $ret = array();
  $recipient = trim($line);
  // First attempt to extract a valid email address
  if(preg_match('/([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6})/i', $recipient, $matches)) {
    $email = $matches[1];
    // Now remove the matching email from the string, along with any <> characters
    $remainder = trim(str_replace(array($email, '>', '<'), '', $recipient)); 
    // Trim off any opening/closing quotes
    $name = trim($remainder, '"');
    $ret['name'] = $name;
    $ret['email'] = $email;
  }
  else {
    // Could not find an email address in there any where.
    $ret = FALSE;
  }
  return $ret;
}

/**
 * Given an activity id, return the related petition id 
 *
 * Return FALSE if this is not an activity that is a petition
 * signature. 
 */
function petitionemail_get_petition_id_for_activity($activity_id) {
  // If there is a related civicrm_petition_email record, we are good to go.
  // NOTE: source_record_id stores the survey_id which is the same thing
  // as the petition_id for our purposes.
  $sql = "SELECT a.source_record_id FROM civicrm_activity a JOIN
    civicrm_petition_email pe ON a.source_record_id = pe.petition_id
    WHERE a.id = %0";
  $params = array(0 => array($activity_id, 'Integer'));
  $dao = CRM_Core_DAO::executeQuery($sql, $params);
  $dao->fetch();
  if($dao->N == 0) return FALSE;
  return $dao->source_record_id; 
}

/**
 * Ensure activity_id should generate an email 
 *
 * Should have a related petition_email record and should have
 * a status of complete and should have a date.
 */
function petitionemail_is_actionable_activity($activity_id) {
  if(!petitionemail_get_petition_id_for_activity($activity_id)) {
    return FALSE;
  }
  $completed = CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name');
  $sql = "SELECT id FROM civicrm_activity WHERE id = %0 AND status_id = %1";
  $params = array(0 => array($activity_id, 'Integer'), 1 => array($completed, 'Integer'));
  $dao = CRM_Core_DAO::executeQuery($sql, $params);
  $dao->fetch();
  if($dao->N == 0) return FALSE;
  return TRUE;
}
