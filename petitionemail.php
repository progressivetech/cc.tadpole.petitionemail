<?php

require_once 'petitionemail.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function petitionemail_civicrm_config(&$config) {
  // FIXME adding the javascript only on the alterForm or alterContent
  // functions doesn't seem to work.
  CRM_Core_Resources::singleton()->addScriptFile('cc.tadpole.petitionemail', 'petitionemail.js');
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

function petitionemail_civicrm_buildForm( $formName, &$form ) {
  if ($formName == 'CRM_Campaign_Form_Petition_Signature') {  
    $survey_id = $form->getVar('_surveyId');
    if ($survey_id) {
      $petitionemailval_sql = "SELECT petition_id, 
                                      default_message, 
                                      message_field, 
                                      subject 
                                 FROM civicrm_petition_email 
                                WHERE petition_id = %1";
      $petitionemailval_params = array( 1 => array( $survey_id, 'Integer' ) );
      $petitionemailval = CRM_Core_DAO::executeQuery( $petitionemailval_sql, $petitionemailval_params );
      while ($petitionemailval->fetch()) {
 
        $defaults = $form->getVar('_defaults');
        $messagefield = 'custom_' . $petitionemailval->message_field;
        foreach ($form->_elements as $element) {
          if ($element->_attributes['name'] == $messagefield) { 
            $element->_value = $petitionemailval->default_message; 
          }
        }
        $defaults[$messagefield] = $form->_defaultValues[$messagefield] = $petitionemailval->default_message;
        $form->setVar('_defaults',$defaults);
      }
    }
  }

  if ($formName == 'CRM_Campaign_Form_Petition') {
    $survey_id = $form->getVar('_surveyId');
    if ($survey_id) {
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
      while ($dao->fetch()) {
        $form->_defaultValues['email_petition'] = 1;
        $form->_defaultValues['recipients'] = $dao->recipients;
        $form->_defaultValues['default_message'] = $dao->default_message;
        $form->_defaultValues['user_message'] = $dao->message_field;
        $form->_defaultValues['subject'] = $dao->subject;
        $form->_defaultValues['location_type_id'] = $dao->location_type_id;
        $form->_defaultValues['group_id'] = $dao->group_id;
      }
      // Now get matching fields
      $sql = "SELECT matching_field FROM civicrm_petition_email_matching_field
        WHERE petition_id = %1";
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      $matching_fields = array();
      while($dao->fetch()) {
        $matching_fields[] = $dao->matching_field;
      }
      $form->_defaultValues['matching_fields'] = $matching_fields;
    }
    $form->add('checkbox', 'email_petition', ts('Send an email to a target'));
    $validcustomgroups = array();

    // Get the Profiles in use by this petition so we can find out
    // if there are any potential fields for an extra message to the
    // petition target.
    $params = array('version' => '3', 
                    'module' => 'CiviCampaign', 
                    'entity_table' => 'civicrm_survey', 
                    'entity_id' => $survey_id );
    $join_results = civicrm_api('UFJoin','get', $params);
    $custom_fields = array();
    if ($join_results['is_error'] == 0) {
      foreach ($join_results['values'] as $join_value) {
        $uf_group_id = $join_value['uf_group_id'];

        // Now get all fields in this profile
        $params = array('version' => 3, 'uf_group_id' => $uf_group_id);
        $field_results = civicrm_api('UFField', 'get', $params);
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
            $params = array('version' => 3, 'id' => $id);
            $custom_results = civicrm_api('CustomField', 'get', $params);
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
    }
    $custom_message_field_options = array();
    if(count($custom_fields) == 0) {
      $custom_message_field_options = array('' => t('- No Text or TextArea fields defined in your profiles -'));
    }
    else {
      $custom_message_field_options = array('' => t('- Select -'));
      $custom_message_field_options = $custom_message_field_options + $custom_fields;
    }
    $choose_one = array('0' => ts('--choose one--'));
    $group_options = $choose_one + CRM_Core_PseudoConstant::group('Mailing');
    $location_options = $choose_one + CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    $sql = "SELECT f.id, g.title, f.label FROM civicrm_custom_group g JOIN civicrm_custom_field f ON g.id = f.custom_group_id
      WHERE g.is_active = 1 AND f.is_active = 1 ORDER BY g.title, f.label";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $field_options = array();
    while($dao->fetch()) {
      $field_options[$dao->id] = $dao->title . '::' . $dao->label;
    }

    $form->add('select', 'group_id', ts('Matching Target Group'), $group_options);
    $form->addElement('advmultiselect', 'matching_fields', ts('Matching field(s)'), $field_options,
      array('style' => 'width:400px;', 'class' => 'advmultiselect'));
    $form->add('select', 'location_type_id', ts('Email'), $location_options);
    $form->add('textarea', 'recipients', ts("Send petitions to"));
    $form->add('select', 'user_message', ts('Custom Message Field'), $custom_message_field_options);
    $form->add('textarea', 'default_message', ts('Default Message'));
    $form->add('text', 'subject', ts('Email Subject Line'));
  }
}

function petitionemail_civicrm_alterContent(  &$content, $context, $tplName, &$object ) {
  if ($tplName == 'CRM/Campaign/Form/Petition.tpl') {
    $ret = '';
    $action = $object->getVar('_action');
    if ($action == 8) { return; }

    //insert the field before is_active
    $insertpoint = strpos($content, '<tr class="crm-campaign-survey-form-block-is_active">');

    $ret .= substr($content, 0, $insertpoint);
    $ret .= petitionemail_get_template($object);
    $ret .= substr($content, $insertpoint);
    $content = $ret;
  }
}

function petitionemail_get_template($object) {
  $rendererval = $object->getVar('_renderer');
  // Identify the fields to display (keys) with the descriptions that 
  // should display with them (values)
  $fields = array(
    'group_id' => ts("Select the group containing the contacst that you want to receive the petition."),
    'matching_fields' => ts("If the user and the target have the same value for this field, then the user's petition will be sent to the matching target."),
    'location_type_id' => ts("A target contact can have more than one email address. Choose the email location that should be used when sending the petition."),
    'email_petition' => ts("Should signatures generate an email to the petition's  target?"),
    'recipients' => ts("Enter additional targets that receive copies of all petitions in the form: 'First name Last name' &lt;email@example.org&gt;. Separate multiple recipients with line breaks."),
    'user_message' => ts("Select a field that will have the signer's custom message.  Make sure it is included in the Activity Profile you selected."),
    'default_message' => ts("Enter the default message to be included in the email."),
    'subject' => ts("Enter the subject line that should appear in the target email.")
  );

  $ret = '';
  reset($rendererval->_tpl->_tpl_vars['form']);
  while(list($k, $v) = each($rendererval->_tpl->_tpl_vars['form'])) {
    if(array_key_exists($k, $fields)) {
      $label = $v['label'];
      $html = $v['html'];
      $description = $fields[$k];
      $ret .= ' 
        <tr class="crm-campaign-survey-form-block-' . $k . '">
          <td class="label">' . $label . '</td>
          <td>' .  $html . '<div class="description">' . $description . '</div>
          </td>
        </tr>';
    }
  }
  return $ret;
} 

function petitionemail_civicrm_postProcess( $formName, &$form ) {
  if ($formName != 'CRM_Campaign_Form_Petition') { 
    return; 
  }
  if ( $form->_submitValues['email_petition'] == 1 ) {
    $survey_id = $form->getVar('_surveyId');
    $lastmoddate = 0;
    if (!$survey_id) {  // Ugly hack because the form doesn't return the id
      $params = array('version' => '3', 'sequential' =>'1', 'title' =>$form->_submitValues['title']);
      $surveys = civicrm_api("Survey", "get", $params);
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
      CRM_Core_Session::setStatus( ts('Cannot find the petition for saving email delivery fields.') );
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
    $sql = "DELETE FROM civicrm_petition_email_matching_field WHERE petition_id = %0";
    $params = array(0 => array($survey_id, 'Integer'));
    CRM_Core_DAO::executeQuery($sql, $params);
    $sql = "INSERT INTO civicrm_petition_email_matching_field SET petition_id = %0, matching_field = %1";
    $params = array(0 => array($survey_id, 'Integer'));
    while(list(,$matching_field) = each($matching_fields)) {
      $params[1] = array($matching_field, 'String');
      CRM_Core_DAO::executeQuery($sql, $params);
    }
  }
}

function petitionemail_civicrm_post( $op, $objectName, $objectId, &$objectRef ) {
  static $profile_fields = NULL;
  if($objectName == 'Profile' && is_array($objectRef)) {
    // This seems like broad criteria to be hanging on to a static array, however,
    // not sure how else to capture the input to be used in case this is a petition
    // being signed that has a target. If you are anonymous, you have a source field in the
    // array, but that is not there if you are logged in. Sigh.
      $profile_fields = $objectRef;
  }

  if ($op == 'create' && $objectName == 'Activity') {
    require_once 'api/api.php';

    //Check what the Petition Activity id is
    $petitiontype = petitionemail_get_petition_type();

    //Only proceed if the Petition Activity is being created
    if ($objectRef->activity_type_id == $petitiontype) {
      $survey_id = $objectRef->source_record_id;
      $activity_id = $objectRef->id;
      $petitionemail_get_sql = "SELECT petition_id, 
                                       default_message, 
                                       message_field, 
                                       subject 
                                  FROM civicrm_petition_email 
                                 WHERE petition_id = %1";
      $petitionemail_get_params = array( 1 => array( $survey_id, 'Integer' ) );
      $petitionemail_get = CRM_Core_DAO::executeQuery( $petitionemail_get_sql, $petitionemail_get_params );
      while ($petitionemail_get->fetch() ) {
        if($petitionemail_get->petition_id == NULL) {
          // Must not be a petition with a target.
          return;
        }

        // Set up variables for the email message
        // Figure out whether to use the user-supplied message or the default message
        $petition_message = NULL;
        // If the petition has specified a message field, and we've encountered the profile post action....
        if(!empty($petitionemail_get->message_field) && !is_null($profile_fields)) {
          if(is_numeric($petitionemail_get->message_field)) {
            $message_field = 'custom_' . $petitionemail_get->message_field;
          }
          else {
            $message_field = $petitionemail_get->message_field;
          }
          // If the field is in the profile
          if(array_key_exists($message_field, $profile_fields)) {
            // If it's not empty...
            if(!empty($profile_fields[$message_field])) {
              $petition_message = $profile_fields[$message_field];
            }
          }
        } 

        // No user supplied message, use the default
        if(is_null($petition_message)) {
          $petition_message = $petitionemail_get->default_message;
        }
        $to = $petitionemail_get->recipient_name . ' <' . $petitionemail_get->recipient_email . '>';
        $activity = civicrm_api("Activity",
                                "getsingle", 
                                array ('version' => '3',
                                       'sequential' =>'1', 
                                       'id' =>$objectId)
                               );
        $from = civicrm_api("Contact",
                            "getsingle", 
                            array ('version' => '3',
                                   'sequential' =>'1', 
                                   'id' =>$activity['source_contact_id'])
                           );
        if (array_key_exists('email', $from) && !empty($from['email'])) {
          $from = $from['display_name'] . ' <' . $from['email'] . '>';
        } else {
          $domain = civicrm_api("Domain",
                                "get", 
                                array ('version' => '3',
                                       'sequential' =>'1')
                                );
          if ($domain['is_error'] != 0 || !is_array($domain['values'])) { 
            // Can't send email without a from address.
            return; 
          }
          $from = '"' . $from['display_name'] . '"' . ' <' . $domain['values']['from_email'] . '>';
        }

        // Setup email message
        $email_params = array( 
          'from'    => $from,
          'toName'  => $petitionemail_get->recipient_name,
          'toEmail' => $petitionemail_get->recipient_email,
          'subject' => $petitionemail_get->subject,
          'text'    => $petition_message, 
          'html'    => $petition_message
        );
        $success = CRM_Utils_Mail::send($email_params);

        if($success == 1) {
          CRM_Core_Session::setStatus( ts('Message sent successfully to') . " $to" );
        } else {
          CRM_Core_Session::setStatus( ts('Error sending message to') . " $to" );
        }
      }
    }
  }
}
 
function petitionemail_get_petition_type() {
  require_once 'api/api.php';
  $acttypegroup = civicrm_api("OptionGroup",
                              "getsingle", 
                              array('version' => '3',
                                    'sequential' =>'1', 
                                    'name' =>'activity_type')
                             );
  if ( $acttypegroup['id'] && !isset($acttypegroup['is_error']) ) {
    $acttype = civicrm_api("OptionValue",
                           "getsingle", 
                           array ('version' => '3',
                                  'sequential' => '1', 
                                  'option_group_id' => $acttypegroup['id'], 
                                  'name' =>'Petition')
                          );
    $petitiontype = $acttype['value'];
  }
    
  return $petitiontype;
}
