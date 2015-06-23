cj(document).ready( function() {
  showHideEmailPetition();
  populateUserFieldOptions();
  cj("input#email_petition").click( function() { showHideEmailPetition(); });
  cj("#profile_id").change( function() { populateUserFieldOptions(); });

  cj('.petitionemail-matching-group_id :input').each( function () { 
    if (cj(this).val() > 0) {
      cj("input.target-group").prop("checked", true);
    }
  });
  if (cj('#recipients').val() != '') {
    cj("input.target-individuals").prop("checked", true);
  }
  showHideTargetGroup();
  showHideTargetIndividuals();
  cj("input.target-group").click( function() { showHideTargetGroup(); });
  cj("input.target-individuals").click( function() { showHideTargetIndividuals(); });

});

function populateUserFieldOptions() {
  var actProfile = cj("#profile_id").val();
  var selected_message = cj('#message_field').val();
  var selected_subject = cj('#subject_field').val();
  var options = {};
  cj('#message_field').empty();
  cj('#subject_field').empty();
  if(actProfile) {
    CRM.api("UFField","get",{ "uf_group_id" : actProfile },{ success:function (data) {
      if(data['is_error'] == 0) {
        cj('#message_field').append('<option value="">--Choose one--</option>');
        cj('#subject_field').append('<option value="">--Choose one--</option>');
        cj.each(data["values"], function(key, value) {
          cj('#message_field').append('<option value="' + value['field_name'] + '">' + value['label']  + '</option>');
          cj('#subject_field').append('<option value="' + value['field_name'] + '">' + value['label']  + '</option>');
        });
        cj('#message_field').val(selected_message);
        cj('#subject_field').val(selected_subject);
      }
    }});
  }
  else {
    options[''] = "No activity profile selected.";
    cj('#message_field').append('<option value="">No activity profile selected.</option>');
    cj('#subject_field').append('<option value="">No activity profile selected.</option>');

 }
}

function showHideEmailPetition() {
  if( cj("input#email_petition:checked").length == 1 ) {
    cj("tr.crm-campaign-survey-form-block-subject").show("fast");
    cj("tr.crm-campaign-survey-form-block-subject_field").show("fast");
    cj("tr.crm-campaign-survey-form-block-default_message").show("fast");
    cj("tr.crm-campaign-survey-form-block-message_field").show("fast");
    cj("tr.crm-campaign-survey-form-block-recipient_options").show("fast");
  } else {
    cj("tr.crm-campaign-survey-form-block-subject").hide("fast");
    cj("tr.crm-campaign-survey-form-block-subject_field").hide("fast");
    cj("tr.crm-campaign-survey-form-block-default_message").hide("fast");
    cj("tr.crm-campaign-survey-form-block-message_field").hide("fast");
    cj("tr.crm-campaign-survey-form-block-recipient_options").hide("fast");
  }
}

function showHideTargetGroup() {
  if (cj("input.target-group").prop("checked")) {
    cj(".petition-email-target-group").show();
  } else {
    cj(".petition-email-target-group").hide();
  }
}
function showHideTargetIndividuals() {
  if (cj("input.target-individuals").prop("checked")) {
    cj(".petition-email-target-individuals").show();
  } else {
    cj(".petition-email-target-individuals").hide();
  }
}
