cj(document).ready( function() {
  showHideEmailPetition();
  populateUserMessageFieldOptions();
  cj("input#email_petition").click( function() { showHideEmailPetition(); });
  cj("#profile_id").change( function() { populateUserMessageFieldOptions(); });
});

function populateUserMessageFieldOptions() {
  var actProfile = cj("#profile_id").val();
  var selected = cj('#message_field').val();
  var options = {};
  cj('#message_field').empty();
  if(actProfile) {
    CRM.api("UFField","get",{ "uf_group_id" : actProfile },{ success:function (data) {
      if(data['is_error'] == 0) {
        cj('#message_field').append('<option value="">--Choose one--</option>');
        cj.each(data["values"], function(key, value) {
          cj('#message_field').append('<option value="' + value['field_name'] + '">' + value['label']  + '</option>');
        });
        cj('#message_field').val(selected);
      }
    }});
  }
  else {
    options[''] = "No activity profile selected.";
    cj('#message_field').append('<option value="">No activity profile selected.</option>');
 }
}

function showHideEmailPetition() {
  if( cj("input#email_petition").attr("checked") ) {
    cj("tr.crm-campaign-survey-form-block-location_type_id").show("fast");
    cj("tr.crm-campaign-survey-form-block-recipient_options").show("fast");
    cj("tr.crm-campaign-survey-form-block-message_field").show("fast");
    cj("tr.crm-campaign-survey-form-block-default_message").show("fast");
    cj("tr.crm-campaign-survey-form-block-subject").show("fast");
  } else {
    cj("tr.crm-campaign-survey-form-block-location_type_id").hide("fast");
    cj("tr.crm-campaign-survey-form-block-recipient_options").hide("fast");
    cj("tr.crm-campaign-survey-form-block-message_field").hide("fast");
    cj("tr.crm-campaign-survey-form-block-default_message").hide("fast");
    cj("tr.crm-campaign-survey-form-block-subject").hide("fast");
  }
}
