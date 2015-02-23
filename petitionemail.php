<?php

require_once 'petitionemail.civix.php';

// You can define multiple pairs of target groups to
// matching field. This constant defines how many are 
// presented in the user interface.
define('PETITIONEMAIL_ALLOWED_GROUP_FIELD_COMBINATIONS_COUNT', 3);

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
  // Clear out our variables.
  petitionemail_remove_profiles();
  petitionemail_remove_custom_fields();
  petitionemail_remove_variables();
  return _petitionemail_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function petitionemail_civicrm_enable() {
  // Ensure the profile id is created.
  petitionemail_create_custom_fields();
  petitionemail_get_profile_id('petitionemail_profile_matching_fields');
  petitionemail_get_profile_id('petitionemail_profile_default_contact');
  petitionemail_get_profile_id('petitionemail_profile_default_activity');
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
                  subject, 
                  message_field, 
                  subject_field,
                  subject 
             FROM civicrm_petition_email 
             WHERE petition_id = %1";
      $params = array( 1 => array( $survey_id, 'Integer' ) );
      $dao = CRM_Core_DAO::executeQuery( $sql, $params );
      $defaults = array();
      $dao->fetch();
      if($dao->N == 0) {
        // Not a email enabled petition
        return;
      }
      $message_field = $dao->message_field;
      $subject_field = $dao->subject_field;
      $defaults[$message_field] = $dao->default_message;
      $defaults[$subject_field] = $dao->subject;
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
                subject_field,
                subject,
                recipients,
                location_type_id
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
        $defaults['message_field'] = $dao->message_field;
        $defaults['subject_field'] = $dao->subject_field;
        $defaults['subject'] = $dao->subject;
        $defaults['location_type_id'] = $dao->location_type_id;
        
        // Now get matching fields.
        $sql = "SELECT matching_field, matching_group_id FROM
          civicrm_petition_email_matching_field WHERE petition_id = %1";
        $dao = CRM_Core_DAO::executeQuery($sql, $params);
        $matching_fields = array();
        $i = 1;
        while($dao->fetch()) {
          $defaults['matching_field' . $i] = $dao->matching_field;
          $defaults['matching_group_id' . $i] = $dao->matching_group_id;
          $i++;
        }
        // We have to build this URL by hand to avoid having the curly 
        // braces get escaped.
        $base_url = preg_match('#/$#', CIVICRM_UF_BASEURL) ? CIVICRM_UF_BASEURL : CIVICRM_UF_BASEURL . '/';
        $base_url = $base_url . "civicrm/petition/sign?sid=$survey_id&reset=1";
        $personal_url = $base_url . '&{contact.checksum}&cid={contact.contact_id}';
        $defaults['links'] = ts("Personal link (use this link if you are sending it via CiviMail,
          it will auto fill with the user's address): ") . "\n" . 
          $personal_url . "\n\n" .  ts("General link: ") . $base_url;
        $form->setDefaults($defaults);
      }
    }
    else {
      $form->setDefaults(
        array(
          'links' => ts("Please save the petition first, then you can copy and
             paste the link to sign the petition.")
        )
      );
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
    $custom_fields = petitionemail_get_text_fields($profile_ids);
    
    $custom_field_options = array();
    if(count($custom_fields) == 0) {
      $custom_field_options = array(
        '' => t('- No Text or TextArea fields defined in your profiles -')
      );
    }
    else {
      $custom_field_options = array('' => t('- Select -'));
      $custom_field_options = $custom_field_options + $custom_fields;
    }
    $choose_one = array('0' => ts('Primary'));
    $group_options = $choose_one + CRM_Core_PseudoConstant::group('Mailing');
    $location_options = $choose_one + 
      CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');

    $field_options = petitionemail_get_matching_field_options();
    $field_options_count = count($field_options);
    if($field_options_count == 0) {
      // No matching fields!
      $field_options[''] = ts("No fields are configured");
    }
    else {
      array_unshift($field_options, ts("--Choose one--"));
    }
    $form->assign('petitionemail_matching_fields_count', $field_options_count);
    $url_params = array(
      'gid' => petitionemail_get_profile_id('petitionemail_profile_matching_fields'),
      'action' => 'browse'
    );
    $url = CRM_Utils_System::url("civicrm/admin/uf/group/field", $url_params);
    $form->assign('petitionemail_profile_edit_link', $url);

    $i = 1;
    while($i <= PETITIONEMAIL_ALLOWED_GROUP_FIELD_COMBINATIONS_COUNT) {
      $form->add('select', 'matching_group_id' . $i, ts('Matching Target Group'), $group_options);
      $form->add('select', 'matching_field' . $i, ts('Matching field(s)'), $field_options); 
      $i++;
    }
    // We can't make default message and default subject required,
    // otherwise users will get an error if they submit a petition
    // that doesn't want to send an email to the target. So we have
    // our own custom validation checking that only complains if 
    // the user wants to send an email and we have to insert the 
    // asterisk ourselves, manually.
    $required = ' <span title="This field is required." class="crm-marker">*</span>';
    $form->add('select', 'location_type_id', ts('Email'), $location_options);
    $form->add('textarea', 'recipients', ts("Send petitions to"), 'rows=20 cols=100');
    $form->add('select', 'message_field', ts('Custom Message Field'),
      $custom_field_options);
    $form->add('select', 'subject_field', ts('Custom Subject Field'),
      $custom_field_options);
    $form->add('textarea', 'default_message', ts('Default Message') . $required, 'rows=20 cols=100');
    $form->add('text', 'subject', ts('Default Email Subject Line') . $required, array('size' => 70));
    $form->add('textarea', 'links', ts('Links to sign the petition'), 'rows=5')->freeze();
  }
}

/**
 * Get fields from the special petition email profile.
 *
 * Filter out un-supported fields.
 */
function petitionemail_get_matching_field_options() {
  $session = CRM_Core_Session::singleton();
  $ret = array();
  $uf_group_id = petitionemail_get_profile_id('petitionemail_profile_matching_fields');
  $fields = CRM_Core_BAO_UFGroup::getFields($uf_group_id); 
  $allowed = petitionemail_get_allowed_matching_fields();
  if(is_array($fields)) {
    reset($fields);
    while(list($id, $value) = each($fields)) {
      $include = FALSE;
      // Check to see if it's a custom field
      if(preg_match('/^custom_/', $id)) {
        $ret[$id] = $value['title'];
        continue;
      }
      else {
        // Check to see if it's an address field
        $field_pieces = petitionemail_split_address_field($id);
        if($field_pieces) {
          if($field_pieces['location_name'] != 'Primary') {
            $session->setStatus(ts("Only primary address fields are support at this time."));
            continue;
          }
          if(array_key_exists($field_pieces['field_name'], $allowed)) {
            $ret[$id] = $value['title'];
            continue;
          }
        }
      }
      // Warn the user about a field that is not allowed
      $session->setStatus(ts("The field $id is not supported as a matching field at this time."));
    }
  }
  
  return $ret;
}

/**
 * Validate the petition form
 *
 * Ensure our values are consistent to avoid broken petitions.
 */
function petitionemail_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if ($formName == 'CRM_Campaign_Form_Petition_Signature') {  
    // Do some basic sanity checking to prevent spammers
    if(empty($form->_surveyId)) {
      // Can't do much without the survey_id
      return;
    }
    $survey_id = $form->_surveyId;

    // Check to see if it's an email petition
    $sql = "SELECT message_field FROM civicrm_petition_email WHERE
      petition_id = %0";
    $dao = CRM_Core_DAO::executeQuery($sql, array(0 => array($survey_id, 'Integer')));
    $dao->fetch();
    if($dao->N == 0) {
      // Nothing to do
      return;
    }

    if(!empty($dao->message_field)) {
      $field_name = 'custom_' . $dao->message_field;
      // If we are allowing a user-supplied message field, ensure it doesn't
      // have any URLs or HTML in it.
      if(array_key_exists($field_name, $fields)) {
        if(preg_match('#https?://#i', $fields[$field_name])) {
          $errors[$field_name] = ts("To avoid spammers, you are not allowed to put web addresses in your message. Please revise your message and try again.");
        }
        // Now ensure we have no html tag
        if (preg_match('/([\<])([^\>]{1,})*([\>])/i', $fields[$field_name] )) {
          $errors[$field_name] = ts("To avoid spammers, you are not allowed to put HTML code in your message. Please revise your message and try again.");
        }
      }
    }
  }

  if($formName == 'CRM_Campaign_Form_Petition') {
    if(CRM_Utils_Array::value('email_petition', $fields)) {
      // Make sure we have a subject field and a default message.
      if(!CRM_Utils_Array::value('subject', $fields)) {
        $msg = ts("You must enter an email subject line.");
        $errors['subject'] = $msg;
      }
      if(!CRM_Utils_Array::value('default_message', $fields)) {
        $msg = ts("You must enter a default message.");
        $errors['default_message'] = $msg;
      }
      // For each matching_group_id, make sure we have a corresponding
      // matching field. 
      $i = 1;
      $using_dynamic_method = FALSE;
      while($i <= PETITIONEMAIL_ALLOWED_GROUP_FIELD_COMBINATIONS_COUNT) {
        $matching_group_id = CRM_Utils_Array::value('matching_group_id' . $i, $fields);
        $matching_field = CRM_Utils_Array::value('matching_field' . $i, $fields);

        if(!empty($matching_group_id) && empty($matching_field)) {
          $msg = ts("If you select a matching target group you must select
            a corresponding matching field.");
          $errors['matching_field' . $i] = $msg; 
        }
        if(empty($matching_group_id) && !empty($matching_field)) {
          $msg = ts("If you select a matching field you must select a 
            corresponding matching target group.");
          $errors['matching_group_id' . $i] = $msg; 
        }

        // Keep track to see if there are using the dynamic method
        if(!empty($matching_group_id)) {
          $using_dynamic_method = TRUE;
        }
        $i++;
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

      if(!$using_dynamic_method && empty($recipients)) {
        $msg = ts("You must select either one target matching group/field or list
          at least one address to send all petitions to.");
        $errors['recipients'] = $msg;
      }
    }
  }
}

/**
 * Given an array of profile ids, list all text area fields
 */
function petitionemail_get_text_fields($profile_ids) {
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
            $custom_fields['custom_' . $id] = $label;
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
  $email_petition = CRM_Utils_Array::value('email_petition', $form->_submitValues);
  if($email_petition && $email_petition  == 1 ) {
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
    $message_field = $form->_submitValues['message_field'];
    $subject_field = $form->_submitValues['subject_field'];
    $subject = $form->_submitValues['subject'];
    $recipients = $form->_submitValues['recipients'];
    $location_type_id = $form->_submitValues['location_type_id'];

    $sql = "REPLACE INTO civicrm_petition_email (
             petition_id,
             default_message, 
             message_field, 
             subject_field, 
             subject,
             recipients,
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
      3 => array( $message_field, 'String' ),
      4 => array( $subject_field, 'String' ),
      5 => array( $subject, 'String' ),
      6 => array( $recipients, 'String' ),
      7 => array( $location_type_id, 'Integer' ),
    );
    $petitionemail = CRM_Core_DAO::executeQuery( $sql, $params );
    
    // delete any existing ones
    $sql = "DELETE FROM civicrm_petition_email_matching_field WHERE
      petition_id = %0";
    $params = array(0 => array($survey_id, 'Integer'));
    CRM_Core_DAO::executeQuery($sql, $params);

    $i = 1;
    while($i <= PETITIONEMAIL_ALLOWED_GROUP_FIELD_COMBINATIONS_COUNT) {
      $matching_group_id = CRM_Utils_Array::value('matching_group_id' . $i, $form->_submitValues);
      $matching_field = CRM_Utils_Array::value('matching_field' . $i, $form->_submitValues);
      if(!empty($matching_group_id) && !empty($matching_field)) {
        $sql = "INSERT INTO civicrm_petition_email_matching_field SET
          petition_id = %0, matching_field = %1, matching_group_id = %2";
        $params = array(
          0 => array($survey_id, 'Integer'),
          1 => array($matching_field, 'String'),
          2 => array($matching_group_id, 'Integer')
        );
        CRM_Core_DAO::executeQuery($sql, $params);
      }
      $i++;
    }
  }
}

/**
 * Implementation of hook_civicrm_post
 *
 * Run everytime a post is made to see if it's a new profile/activity
 * that should trigger a petition email to be sent. Also clean up 
 * our tables if a petition is deleted.
 */
function petitionemail_civicrm_post( $op, $objectName, $objectId, &$objectRef ) {
  static $profile_fields = NULL;
  if($objectName == 'Profile' && is_array($objectRef)) {
    // This is hacky but seems to be unavoidable. We really want to run 
    // on the activity post create hook. However, the activity post create
    // hook is called *before* the custom fields are saved for the activity
    // record. That means that none of the custom fields are available when
    // it is called, so we can't provide a custom subject or custom message
    // field to the petitionemail_process_signature function.
    //
    // However, the profile post hook is called before the activity post
    // hook is called. So, we set a static variable when the profile post
    // hook is called to save all the fields being submitted and then make
    // that available when the activity post hook is called.
    $profile_fields = $objectRef;
  }
  if ($objectName == 'Activity') {
    $activity_id = $objectId;

    // Only run on creation. For petitions that require a confirmation,
    // after the petition has been created, see petitionemail_civicrm_pageRun().
    if($op == 'create') {
      if(petitionemail_is_actionable_activity($activity_id)) {
        petitionemail_process_signature($activity_id, $profile_fields);
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

function petitionemail_get_petition_details($petition_id) {
  $ret = array();
  $sql = "SELECT default_message, 
               message_field, 
               subject_field,
               subject,
               location_type_id,
               recipients
         FROM civicrm_petition_email
         WHERE petition_id = %1 GROUP BY petition_id";
  $params = array( 1 => array( $petition_id, 'Integer' ) );
  $petition_email = CRM_Core_DAO::executeQuery( $sql, $params );
  $petition_email->fetch();
  if($petition_email->N == 0) {
    // Must not be a petition with a target.
    return FALSE;;
  }

  // Store variables we need
  $ret['default_message'] = $petition_email->default_message;
  $ret['subject'] = $petition_email->subject;
  $ret['location_type_id'] = $petition_email->location_type_id;
  $ret['message_field'] = $petition_email->message_field;
  $ret['subject_field'] = $petition_email->subject_field;
  $ret['recipients'] = $petition_email->recipients;

  // Now retrieve the matching fields, if any
  $sql = "SELECT matching_field, matching_group_id FROM
    civicrm_petition_email_matching_field WHERE petition_id = %1";
  $params = array( 1 => array( $petition_id, 'Integer' ) );
  $dao = CRM_Core_DAO::executeQuery($sql, $params);
  $ret['matching'] = array();
  while($dao->fetch()) {
    $ret['matching'][$dao->matching_field] = $dao->matching_group_id;
  }
  return $ret;
}

/**
 * This function handles all petition signature processing.
 *
 * @activity_id integer The activity id of the signature activity
 * @profile_fields array An array of fields submitted by the user, which
 *   may include the custom subject and custom message values.
 */
function petitionemail_process_signature($activity_id, $profile_fields = NULL) {
  $petition_id = petitionemail_get_petition_id_for_activity($activity_id);
  if(empty($petition_id)) {
    $log = "Failed to find petition id for activity id: $activity_id";
    CRM_Core_Error::debug_log_message($log);
    return FALSE;
  }
  $petition_vars = petitionemail_get_petition_details($petition_id);
  if(!$petition_vars) {
    // Nothing to process, this isn't an email target enabled petition
    return;
  }
  $default_message = $petition_vars['default_message'];
  $default_subject = $petition_vars['subject'];
  $message_field = $petition_vars['message_field'];
  $subject_field = $petition_vars['subject_field'];

  $activity = civicrm_api3("Activity", "getsingle", array ('id' => $activity_id));
  $contact_id = $activity['source_contact_id'];
  $contact = civicrm_api3("Contact", "getsingle", array ('id' => $contact_id));

  // Figure out whether to use the user-supplied message/subject or the default
  // message/subject.
  $petition_message = NULL;
  $subject = NULL;
  // If the petition has specified a message field
  if(!empty($message_field)) {
    // Check for a custom message field value in the passed in profile fields.
    // This field will be populated if we are operating on a new activity via
    // the post hook.
    if(is_array($profile_fields) && !empty($profile_fields[$message_field])) {
      $petition_message = $profile_fields[$message_field];
    }
    else {
      // Retrieve the value of the field for this activity (this may happen
      // if we are operating on a confirmation click from pageRun hook).
      $params = array(
        'id' => $activity_id, 
        'return' => $message_field
      );
      $result = civicrm_api3('Activity', 'getsingle', $params);
      if(!empty($result[$message_field])) {
        $petition_message = $result[$message_field];
      }
    }
  } 
  if(is_null($petition_message)) {
    $petition_message = $default_message;
  }
  // CiviCRM seems to htmlentitize everything submitted, but we are
  // preventing any html tags in our validation and we want to avoid
  // weird htmlentites being added to text messages. 
  $petition_message = html_entity_decode($petition_message);

  // Add the sending contacts address info
  $address_block = petitionemail_get_address_block($contact_id);
  if($address_block) {
    $petition_message = strip_tags($address_block) . "\n\n" . $petition_message;
  }

  // If the petition has specified a subject field
  if(!empty($subject_field)) {
    // Check for a custom subject field value in the passed in profile fields.
    // This field will be populated if we are operating on a new activity via
    // the post hook.
    if(is_array($profile_fields) && !empty($profile_fields[$subject_field])) {
      $subject = $profile_fields[$subject_field];
    }
    else {
      // Retrieve the value of the field for this activity (this may happen
      // if we are operating on a confirmation click from pageRun hook).
      $params = array(
        'id' => $activity_id, 
        'return' => $subject_field
      );
      $result = civicrm_api3('Activity', 'getsingle', $params);
      if(!empty($result[$subject_field])) {
        $subject = $result[$subject_field];
      }
    }
  }
  // No user supplied message/subject, use the default
  
  if(is_null($subject)) {
    $subject = $default_subject;
  }

  // CiviCRM seems to htmlentitize everything submitted, but we don't
  // want 3 > 1 in a subject line get converted to 3 &gt; 1 
  $subject = html_entity_decode($subject);

  $from = NULL;
  if (empty($contact['email'])) {
    $domain = civicrm_api3("Domain", "get", array ());
    if ($domain['is_error'] != 0 || !is_array($domain['values'])) { 
      // Can't send email without a from address.
      $msg = "petition_email: Failed to send petition email because from
        address not sent.";
      CRM_Core_Error::debug_log_message($msg);
      return; 
    }
    $contact['email'] = $domain['values']['from_email'];
  }
  $from = $contact['display_name'] . ' <' . $contact['email'] . '>';

  // Setup email message (except to address)
  $email_params = array( 
    'from'    => $from,
    'toName'  => NULL,
    'toEmail' => NULL,
    'subject' => $subject,
    'text'    => $petition_message, 
    'html'    => NULL, 
  );

  // Get array of recipients
  $recipients = petitionemail_get_recipients($contact_id, $petition_id);
  // Keep track of the targets we actually send the message to so we can 
  // email the petition signer to let them now.
  $message_sent_to = array();
  while(list(, $recipient) = each($recipients)) {
    if(!empty($recipient['email'])) {
      $log = "petition email: contact id ($contact_id) sending to email (" .
        $recipient['email'] . ")";
      CRM_Core_Error::debug_log_message($log);
      if(!empty($recipient['contact_id'])) {
        // Since we're sending to a recipient in the database, create an
        // email activity. Note: we are not using the built-in
        // function to create (and send) and email activity because it won't
        // send if the contact has DoNotEmail they won't get it. However, it's
        // normal to have DoNotEmail for your targets, but you still want them
        // to get the petition.

        $log = "petition email: recording email as activity against ".
          "target contact id: " . $recipient['contact_id'];
        CRM_Core_Error::debug_log_message($log);

        $contactDetails = array(0 => $recipient);
        // We are sending a text message, so ensure it's the preferred one
        $contactDetails[0]['preferred_mail_format'] = 'Text';
        $subject = $email_params['subject'];
        $text = $email_params['text'];
        $html = $email_params['html'];
        $emailAddress = $recipient['email'];
        $userID = $contact_id;
        $from = NULL; // This will be pulled from $contact_id,
        $attachments = NULL;
        $cc = NULL;
        $bcc = NULL;
        $contactIds = array($recipient['contact_id']);

        // Create the activity first, then we will send the email.
        $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'name');
        $activity_type_id = array_search('Email', $activityTypes);
        $activity_status = CRM_Core_PseudoConstant::activityStatus();
        $status_id = array_search('Completed', $activity_status);
        $params = array(
          'activity_type_id' => $activity_type_id,
          'subject' => $subject,
          'details' => $text,
          'source_contact_id' => $contact_id,
          'target_contact_id' => $recipient['contact_id'],
          'status_id' => $status_id
        );
        $activity_id = NULL;
        try {
          $ret = civicrm_api3('Activity', 'create', $params);
          $value = array_pop($ret['values']);
          $activity_id = $value['id']; 
        }
        catch (CiviCRM_API3_Exception $e) {
          $log = "petition email: email activity not created";
          $log .= $e->getMessage();
          CRM_Core_Error::debug_log_message($log);
          return FALSE;
        }
        if($activity_id) {
          // Update the activity with the petition id so we can properly
          // report on the email messages sent as a result of this petition.
          $params = array(
            'activity_id' => $activity_id,
            'source_record_id' => $petition_id
          );
          $result = civicrm_api3('Activity', 'update', $params);
          if($result['is_error'] != 0) {
            $log = "civicrm petition: failed to update activity with ".
              "source_record_id";
            CRM_Core_Error::debug_log_message($log);
          }
        }
      }
      // Now send all email.
      // Handle targets not in the database.
      $email_params['toName'] = $recipient['name'];
      $email_params['toEmail'] = $recipient['email'];
      $to = $email_params['toName'] . ' <' . $email_params['toEmail'] . '>';

      $log = "petition_email: sending petition to '$to' via mail function.";
      CRM_Core_Error::debug_log_message($log);

      $success = CRM_Utils_Mail::send($email_params);

      if($success == 1) {
        CRM_Core_Session::setStatus( ts('Message sent successfully to: ') . htmlentities($to), '', 'success' );
        $log = "petition_email: message sent.";
        $message_sent_to[] = $to;
      } else {
        $log = "petition_email: message was not sent.";
      }
      CRM_Core_Error::debug_log_message($log);
    }
  }
}

function petitionemail_get_address_block($contact_id) {
  $sql = "SELECT display_name, street_address, city, ".
    "s.abbreviation AS state_province, postal_code FROM civicrm_contact c JOIN ".
    "civicrm_address a ON c.id = a.contact_id JOIN civicrm_state_province s ".  
    "on a.state_province_id = s.id WHERE is_primary = 1 ".
    "AND c.id = %0";
  $params = array(0 => array($contact_id, 'Integer'));
  $dao = CRM_Core_DAO::executeQuery($sql, $params);
  $dao->fetch();
  if($dao->N == 0) {
    return NULL;
  }
  $block = $dao->display_name . "\n" .
    $dao->street_address . "\n" .
    $dao->city . ", " .
    $dao->state_province .
    " " .
    $dao->postal_code;
  return $block;
}

/**
 * Non custom data fields allowed to be a matching field.
 *
 * All custom fields can be used as matching fields, but only
 * a subset of non-custom fields (so we can be sure to build
 * a working query to retrieve them).
 */
function petitionemail_get_allowed_matching_fields() {
  $ret = array(
    'street_name' => 'civicrm_address',
    'street_number' => 'civicrm_address',
    'street_name' => 'civicrm_address',
    'city' => 'civicrm_address',
    'county_id' => 'civicrm_address',
    'state_province' => 'civicrm_address',
    'postal_code' => 'civicrm_address',
    'postal_code_suffix' => 'civicrm_address',
    'country_id' => 'civicrm_address',
  );
  return $ret;
}

function petitionemail_get_recipients($contact_id, $petition_id) {
  $petition_vars = petitionemail_get_petition_details($petition_id);
  if(!$petition_vars) {
    // Not an email target enabled petition
    return;
  }
  $ret = array();
  // First, parse the additional recipients, if any. These get the email
  // regarldess of who signs it.
  if(!empty($petition_vars['recipients'])) {
    $recipients = explode("\n", $petition_vars['recipients']);
    while(list(,$recipient) = each($recipients)) {
      $email_parts = petitionemail_parse_email_line($recipient); 
      if(FALSE !== $email_parts) {
        $ret[] = array(
          'contact_id' => NULL,
          'name' => $email_parts['name'],
          'email' => $email_parts['email']
        );
      }
    }
  }
  // If there are any matching criteria (for a dynamic lookup) we do a
  // complex query to figure out which members of the group should be
  // included as recipients.
  if(count($petition_vars['matching']) > 0) {
    // This comes as an array with the key being the matching field and
    // the value being the matching_group_id.
    $matching_fields = $petition_vars['matching'];

    // Get the values of the matching fields for the contact. These values
    // are used to match the contact who signed the petition with the 
    // contact or contacts in the target group.

    // Given the matching fields, we're going to do an API call against
    // the contact to get the values that we will be matching on.

    // Build a return_fields array that we will pass to the api call to 
    // specify the fields we want returned with this query.
    $field_names = array_keys($matching_fields);
    $return_fields = array();
    reset($field_names);
    while(list(, $field_name) = each($field_names)) {
      // If the field_name starts with custom_ we can add it straight 
      // away.
      if(preg_match('/^custom_/', $field_name)) {
        $return_fields[] = $field_name;
        continue;
      }

      // Look for field names with a - in them - that's an indication 
      // that it's an address field which will have the location part
      // stuck into the name.
      $field_pieces = petitionemail_split_address_field($field_name);
      if($field_pieces) {
        if($field_pieces['location_name'] == 'Primary') {
          // Primary will be included via the api call, so we just need
          // the field name. If it's not primary, we'll have to do a 
          // manual SQL call below to get the value.
          $return_fields[] = $field_pieces['field_name'];
          continue;
        }
      }
      // FIXME If we get here, this is an error
    }
    $contact_params = array('return' => $return_fields, 'id' => $contact_id);
    $contact = civicrm_api3('Contact', 'getsingle', $contact_params);
    while(list($matching_field) = each($matching_fields)) {
      // Check if the field was returned. If not, it's probably an address field
      if(array_key_exists($matching_field, $contact)) {
        $matching_fields[$matching_field] = $contact[$matching_field];
        continue;
      }
      // This means it's probably an address field.
      $field_pieces = petitionemail_split_address_field($matching_field);
      if(!$field_pieces) {
        // FIXME This is an error
        continue;
      }
      $location_name = $field_pieces['location_name'];
      $field_name = $field_pieces['field_name'];
      // NOTE: we only work with primary fields.
      if($location_name == 'Primary' && array_key_exists($field_name, $contact)) {
        // The field name returned by the API won't have the -location part.
        $matching_fields[$matching_field] = $contact[$field_name];
        continue;
      }
      else {
        // FIXME This is an error
        continue;
      }
    } 

    // Initialize variables to build the SQL statement
    $from = array();
    // The master $where clause will be put together using AND
    $where = array();
    $params = array();
    $added_tables = array();

    // Initialize the from clause and where clause
    $from[] = 'civicrm_contact c';
    $where[] = 'c.is_deleted = 0';

    // We build a sub where clause that limits results based on the 
    // matching group and matching field that will be put together using
    // OR since we match any any of the matching field => group
    // combinations.
    $sub_where = array();
    reset($matching_fields);
    $id = 0;
    while(list($matching_field, $value) = each($matching_fields)) {
      // The $where_fragment will be put together using AND because
      // you have to match both the group and the field.
      $where_fragment = array();

      // Gather information about the group that is paired with this
      // matching field.
      $group_id = $petition_vars['matching'][$matching_field];
      // Retrieve details (specifically, find out if it's a smart group)
      $results = civicrm_api3('Group', 'getsingle', array('id' => $group_id));
      if(!empty($results['id'])) {
        if(!empty($results['saved_search_id'])) {
          // Populate the cache
          CRM_Contact_BAO_GroupContactCache::check($group_id);
          if(!in_array('civicrm_group_contact_cache', $added_tables)) {
            $from[] = 'LEFT JOIN civicrm_group_contact_cache cc ON
              c.id = cc.contact_id';
            $added_tables[] = 'civicrm_group_contact_cache';
          }
          $where_fragment[] = 'cc.group_id = %' . $id;
          $params[$id] = array($group_id, 'Integer');
          $id++;
        }
        else {
          if(!in_array('civicrm_group_contact', $added_tables)) {
            $from[] = 'LEFT JOIN civicrm_group_contact gc ON
              c.id = gc.contact_id';
            $added_tables[] = 'civicrm_group_contact';
          }
          $where_fragment[] = 'gc.group_id = %' . $id;
          $where_fragment[] = 'gc.status = "Added"';
          $params[$id] = array($group_id, 'Integer');
          $id++;
        }
      
        // Now add in the matching field
        if(empty($value)) {
          // We should never match in this case
          $where_fragment[] = "(0)";
        }
        else {
          if(preg_match('/^custom_/', $matching_field)) {
            $sql = "SELECT column_name, table_name FROM civicrm_custom_group g 
              JOIN civicrm_custom_field f ON g.id = f.custom_group_id WHERE 
              f.id = %0";
            $custom_field_id = str_replace('custom_', '', $matching_field);
            $dao = CRM_Core_DAO::executeQuery($sql, array(0 => array($custom_field_id, 'Integer')));
            $dao->fetch();
            if(!in_array($dao->table_name, $added_tables)) {
              $from[] = "LEFT JOIN " . $dao->table_name . " ON " . $dao->table_name . ".entity_id = 
                c.id";
              $added_tables[] = $dao->table_name;
            }
            $where_fragment[] = $dao->column_name . ' = %' . $id;
            // Fixme - we should use the proper data type for each custom field
            $params[$id] = array($value, 'String');
            $id++;
          }
          else {
            // Handle non-custom fields (address fields)
            // We only support primary address.
            $field_pieces = petitionemail_split_address_field($matching_field);
            $field_name = $field_pieces['field_name'];
            if(!in_array('civicrm_address', $added_tables)) {
              $from[] = "LEFT JOIN civicrm_address a ON a.contact_id = c.id";
              $added_tables[] = 'civicrm_address';
            }

            // We have to make a special case for states, since the value we get
            // from the user is the abbreviation rather than the state_province_id
            // that is in the civicrm_address table.
            if($field_name == 'state_province') {
              if(!in_array('civicrm_state_province', $added_tables)) {
                $from[] = "LEFT JOIN civicrm_state_province sp ON a.state_province_id = sp.id";
                $added_tables[] = 'civicrm_state_province';
              }
              $field_name = 'sp.abbreviation';
            }
            $where_fragment[] = $field_name . ' = %' . $id;
            $where_fragment[] = 'a.is_primary = 1';
            $params[$id] = array($value, 'String');
            $id++;
          }
        }
        $sub_where[] = '(' . implode(' AND ', $where_fragment) . ')';
      }
      else {
        // This is an error
      }
    }

    if(count($sub_where) > 0) {
      $where[] = '(' . implode(' OR ', $sub_where) . ')';
    }

    // put it all together
    $sql = "SELECT DISTINCT c.id, c.display_name ";
    $sql .= "FROM " . implode("\n", $from) . " ";
    $sql .= "WHERE " . implode(" AND\n", $where);
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $location_type_id = $petition_vars['location_type_id'];
    while($dao->fetch()) {
      // Lookup the best email address. 
      // ORDER BY FIELD allows us to arbitrarily set the location type id
      // we want to be set the highest.
      $sql = "SELECT e.email FROM civicrm_email e WHERE contact_id = %0 ".
        "AND (location_type_id = %1 OR is_primary = 1) ".
        "ORDER BY FIELD(e.location_type_id, %2) DESC, e.is_primary LIMIT 1";
      
      $email_params = array(
        0 => array($dao->id, 'Integer'),
        1 => array($petition_vars['location_type_id'], 'Integer'),
        2 => array($petition_vars['location_type_id'], 'Integer')
      );
      
      $email_dao = CRM_Core_DAO::executeQuery($sql, $email_params);
      $email_dao->fetch();
      $ret[] = array(
        'contact_id' => $dao->id,
        'name' => $dao->display_name,
        'email' => $email_dao->email
      );
    }
  }
  return $ret; 
}

/**
 * Split address field name
 * 
 * Field names in profiles are stored in the format
 * fieldname-locationame (e.g. postal_code-Primary).
 * This function breaks that string into the field name
 * and location name or returns FALSE if it's not a
 * location field.
 */
function petitionemail_split_address_field($field_name) {
  $ret = FALSE;
  if(preg_match('/([a-zA-Z0-9_]+)-([a-zA-Z0-9_]+)/', $field_name, $matches)) {
    if(!empty($matches[1]) && !empty($matches[2])) {
      $ret = array(
        'field_name' => $matches[1],
        'location_name' => $matches[2],
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

/**
 *  Provide profile parameters based on the keys passed. 
 *
 * This function returns the parameters used to create profiles
 * in the petitionemail_get_profile_id function.
 * 
 **/
function petitionemail_get_profile_params($key) {
  $params = array();
  if($key == 'petitionemail_profile_matching_fields') {
    $description = ts('This profile controls which fields are available as
      matching fields when using the petition email extension. Please do
      not delete this profile.');
    $params = array(
      'name' => $key,
      'title' => ts('Petition Email Available Matching fields'),
      'description' => $description,
    );
  }
  elseif($key == 'petitionemail_profile_default_contact') {
    $description = ts('This profile was created by the petition email extension for use in petitions.');
    $params = array (
        'version' => 3,
        'name' => 'Petition_Email_Contact_Info',
        'title' => 'Petition Email Contact Info',
        'description' => $description,
        'is_active' => 1,
        'api.uf_field.create' => array(
          array(
            'uf_group_id' => '$value.id',
            'field_name' => 'postal_code',
            'is_active' => 1,
            'is_required' => 1,
            'label' => 'Zip Code',
            'field_type' => 'Contact',
          ),
          array(
            'uf_group_id' => '$value.id',
            'field_name' => 'state_province',
            'is_active' => 1,
            'is_required' => 1,
            'label' => 'State',
            'field_type' => 'Contact',
          ),
          array(
            'uf_group_id' => '$value.id',
            'field_name' => 'city',
            'is_active' => 1,
            'is_required' => 1,
            'label' => 'City',
            'field_type' => 'Contact',
          ),
          array(
            'uf_group_id' => '$value.id',
            'field_name' => 'street_address',
            'is_active' => 1,
            'is_required' => 1,
            'label' => 'Street Address',
            'field_type' => 'Contact',
          ),
          array(
            'uf_group_id' => '$value.id',
            'field_name' => 'email',
            'is_active' => 1,
            'is_required' => 1,
            'label' => 'Email',
            'field_type' => 'Contact',
          ),
          array(
            'uf_group_id' => '$value.id',
            'field_name' => 'last_name',
            'is_active' => 1,
            'is_required' => 1,
            'label' => 'Last Name',
            'field_type' => 'Individual',
          ),
           array(
            'uf_group_id' => '$value.id',
            'field_name' => 'first_name',
            'is_active' => 1,
            'is_required' => 1,
            'label' => 'First Name',
            'field_type' => 'Individual',
          ),
        )
      );
  }
  elseif($key == 'petitionemail_profile_default_activity') {
   // Lookup the custom field names we are supposed to use.
   $sql = "SELECT id FROM civicrm_custom_field WHERE name = 'Petition_Email_Custom_Subject'";
   $dao = CRM_Core_DAO::executeQuery($sql);
   $dao->fetch();
   if($dao->N == 0) {
     // The custom fields have not yet been created.
     return NULL;
   }
   $custom_subject_field = 'custom_' . $dao->id;  
   $sql = "SELECT id FROM civicrm_custom_field WHERE name = 'Petition_Email_Custom_Message'";
   $dao = CRM_Core_DAO::executeQuery($sql);
   $dao->fetch();
   if($dao->N == 0) {
     // The custom fields have not yet been created.
     return NULL;
   }
   $custom_message_field = 'custom_' . $dao->id;  

   $description = ts('This profile was created by the petition email extension for use in petitions.');
   $params = array (
     'version' => 3,
     'name' => 'Petition_Email_Activity_Fields',
     'title' => 'Petition Email Activity Fields',
     'description' => $description,
     'is_active' => 1,
     'api.uf_field.create' => array(
       array(
         'uf_group_id' => '$value.id',
         'field_name' => $custom_message_field,
         'is_active' => 1,
         'is_required' => 1,
         'label' => ts('Customize the message'),
         'help_post' => ts('Your postal address will be automatically added to your message when it is sent.'),
         'field_type' => 'Activity',
       ),
       array(
         'uf_group_id' => '$value.id',
         'field_name' => $custom_subject_field,
         'is_active' => 1,
         'is_required' => 1,
         'label' => ts('Customize the email subject'),
         'field_type' => 'Activity',
       ),
      )
    );
  }
  return $params;
}

/**
 * Helper function to ensure a profile is created.
 *
 * This function ensures that the profile is created and if not, it
 * creates it. 
 *
 * @return integer profile id 
 */
function petitionemail_get_profile_id($key) {
  $group = 'petitionemail';
  $ret = CRM_Core_BAO_Setting::getItem($group, $key);
  if(!empty($ret)) {
    // Ensure it exists
    $sql = "SELECT id FROM civicrm_uf_group WHERE id = %0";
    $dao = CRM_Core_DAO::executeQuery($sql, array(0 => array($ret, 'Integer')));
    $dao->fetch();
    if($dao->N == 1) {
      return $ret;
    }
    // Delete this variable - probably the user deleted the profile not knowing
    // what it was used for.
    $sql = "DELETE FROM civicrm_setting WHERE group_name = %0 AND name = %1";
    $params = array(
      0 => array($group, 'String'),
      1 => array($key, 'String')
    );
    CRM_Core_DAO::executeQuery($sql, $params);
  }

  // Create the profile
  // We have to manually set created_id if the current user is not set
  $session = CRM_Core_Session::singleton();
  $contact_id = $session->get('userID');
  if(empty($contact_id)) {
    // Maybe we are running via drush?
    // Try the contact associated with uid 1
    $contact_id = CRM_Core_BAO_UFMatch::getContactId(1);
    if(empty($contact_id)) {
      // Last ditch effort
      $sql = "SELECT MIN(id) FROM civicrm_contact WHERE is_active = 1 AND is_deleted = 0";
      $dao = CRM_Core_DAO::executeQuery($sql);
      $dao->fetch();
      $contact_id = $dao->id;
    }
  }

  $params = petitionemail_get_profile_params($key);
  if(is_null($params)) return NULL;

  $params['created_id'] = $contact_id;
  
  $results = civicrm_api3('UFGroup', 'create', $params);
  if($results['is_error'] != 0) {
    $session->setStatus(ts("Error creating the petition email profile group."));
    return FALSE;
  }
  $value = array_pop($results['values']);
  $id = $value['id'];

  CRM_Core_BAO_Setting::setItem($id, $group, $key);
  return $id;
}

/**
 * Remove any profiles we automatically created.
 */
function petitionemail_remove_profiles() {
  $profiles_to_remove = array(
    'petitionemail_profile_matching_fields', 
    'petitionemail_profile_default_contact', 
    'petitionemail_profile_default_activity'
  );
  while(list(,$key) = each($profiles_to_remove)) {
    $group = 'petitionemail';
    $ret = CRM_Core_BAO_Setting::getItem($group, $key);
    if($ret) {
      // Get a list of existing profile fields and remove those 
      // first.
      $params = array(
        'uf_group_id' => $ret,
        'return' => array('id')
      );
      $results = civicrm_api3('UFField', 'get', $params);
      if(is_array($results['values'])) {
        while(list($id) = each($results['values'])) {
          $params = array('id' => $id);
          civicrm_api3('UFField', 'delete', $params);
        }
      }
      $params = array('id' => $ret);
      civicrm_api3('UFGroup', 'delete', $params);
    }
  }
}

/**
 * Remove any custom fields we automatically created.
 */
function petitionemail_remove_custom_fields() {
  $groups_to_remove = array(
    'petitionemail_custom_message_fields'
  );
  while(list(,$key) = each($groups_to_remove)) {
    $group = 'petitionemail';
    $ret = CRM_Core_BAO_Setting::getItem($group, $key);
    if($ret) {
      // Get a list of existing profile fields and remove those 
      // first.
      $params = array(
        'custom_group_id' => $ret,
        'return' => array('id')
      );
      $results = civicrm_api3('CustomField', 'get', $params);
      if(is_array($results['values'])) {
        while(list($id) = each($results['values'])) {
          $params = array('id' => $id);
          civicrm_api3('CustomField', 'delete', $params);
        }
      }
      $params = array('id' => $ret);
      civicrm_api3('CustomGroup', 'delete', $params);
    }
  }
}
/**
 * Create the custom fields used to record subject and body
 *
 */
function petitionemail_create_custom_fields() {
  $group = 'petitionemail';
  $key = 'petitionemail_custom_message_fields';
  $ret = CRM_Core_BAO_Setting::getItem($group, $key);
  if(!empty($ret)) {
    // Ensure it exists
    $sql = "SELECT id FROM civicrm_custom_group WHERE id = %0";
    $dao = CRM_Core_DAO::executeQuery($sql, array(0 => array($ret, 'Integer')));
    $dao->fetch();
    if($dao->N == 1) {
      return $ret;
    }
    // Delete this variable - probably the user deleted the profile not knowing
    // what it was used for.
    $sql = "DELETE FROM civicrm_setting WHERE group_name = %0 AND name = %1";
    $params = array(
      0 => array($group, 'String'),
      1 => array($key, 'String')
    );
    CRM_Core_DAO::executeQuery($sql, $params);
  }
  // Get the value of the petition activity id so our custom group
  // will only extend Activities of type Petition.
  $sql = "SELECT v.value FROM civicrm_option_group g JOIN 
    civicrm_option_value v ON g.id = v.option_group_id WHERE g.name = 'activity_type'
    AND v.name = 'petition'";
  $dao = CRM_Core_DAO::executeQuery($sql);
  $dao->fetch();
  if($dao->N > 0) {
    $activity_type_id = $dao->value;
    $params = array (
      'version' => 3,
      'name' => 'PetitionEmailMessageFields',
      'title' => 'Petition Email Message Fields',
      'extends' => 'Activity',
      'extends_entity_column_value' => array($activity_type_id),
      'style' => 'Inline',
      'collapse_display' => 1,
      'is_active' => 1,
      'api.custom_field.create' => array(
        array(
          'custom_group_id' => '$value.id',
          'label' => 'Custom Message',
          'name' => 'Petition_Email_Custom_Message',
          'data_type' => 'Memo',
          'html_type' => 'TextArea',
          'is_required' => 0,
          'is_searchable' => 0,
          'is_active' => 1,
        ),
        array(
          'custom_group_id' => '$value.id',
          'label' => 'Custom Subject',
          'name' => 'Petition_Email_Custom_Subject',
          'data_type' => 'String',
          'html_type' => 'Text',
          'is_required' => 0,
          'is_searchable' => 0,
          'is_active' => 1,
        ),
        
      ),
    );
    try {
      $results = civicrm_api3('CustomGroup', 'create', $params);
    }
    catch (CiviCRM_API3_Exception $e) {
      $session = CRM_Core_Session::singleton();
      $session->setStatus(ts("Error creating the petition custom fields."));
      $session->setStatus($e->getMessage());
      return FALSE;
    }
    $values = array_pop($results['values']);
    $id = $values['id'];
    CRM_Core_BAO_Setting::setItem($id, $group, $key);

    // Start complex process for clearing the cache of available fields. 
    // We need to clear the cache so that when we create a profile that
    // depends on these fields, we won't get an error that it's an invalid field.

    // First clear the static array of exportableFields which is used to determine
    // if a field is valid when being used in a profile.
    CRM_Activity_BAO_Activity::$_exportableFields = NULL;

    // Next clear the cache so we don't pull from an already populated cache.
    CRM_Utils_System::flushCache();

    // Lastly, we have to call the function that is called to validate fields,
    // but specifying that we want to force the re-fecthing of fields to unset
    // yet another static variable.
    CRM_Core_BAO_UFField::getAvailableFieldsFlat(TRUE);

    return $id;
  }
  return FALSE;
}

/**
 * Helper to remove any extension created variables
 */
function petitionemail_remove_variables() {
  $group = 'petitionemail';
  $sql = "DELETE FROM civicrm_setting WHERE group_name = %0";
  $params = array(
    0 => array($group, 'String')
  );
  CRM_Core_DAO::executeQuery($sql, $params);
}
