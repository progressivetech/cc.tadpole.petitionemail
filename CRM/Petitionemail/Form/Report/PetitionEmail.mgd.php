<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'CRM_Petitionemail_Form_Report_PetitionEmail',
    'entity' => 'ReportTemplate',
    'params' => 
    array (
      'version' => 3,
      'label' => 'PetitionEmail',
      'description' => 'PetitionEmail (cc.tadpole.petitionemail)',
      'class_name' => 'CRM_Petitionemail_Form_Report_PetitionEmail',
      'report_url' => 'petition-email',
      'component' => 'CiviCampaign',
    ),
  ),
);