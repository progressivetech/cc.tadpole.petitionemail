cj(document).ready( function() {
  showHideEmailPetition();
  populateUserMessageFieldOptions();
  checkProfileIncludesMessage();
  cj("input#email_petition").click( function() { showHideEmailPetition(); });
  cj("#profile_id").change( function() { populateUserMessageFieldOptions(); });
  cj("#user_message").change( function() { checkProfileIncludesMessage(); });
});

function populateUserMessageFieldOptions() {
  var actProfile = cj("#profile_id").val();
  var options = {};
  cj('#user_message').val('');
  cj('#user_message').empty();
  if(actProfile) {
    CRM.api("UFField","get",{ "uf_group_id" : actProfile },{ success:function (data) {
      if(data['is_error'] == 0) {
        cj.each(data["values"], function(key, value) {
          cj('#user_message').append('<option value="' + key + '">' + value['label']  + '</option>');
        });
      }
    }});
  }
  else {
    options[''] = "No activity profile selected.";
    cj('#user_message').append('<option value="">No activity profile selected.</option>');
 }
}
function checkProfileIncludesMessage() {
  cj("#profileMissingMessage").remove();
  var actProfile = cj("#profile_id").val();
  CRM.api("UFField","get",{ "sequential" :"1", "uf_group_id" : actProfile },{ success:function (data){
    var msgField = cj("#user_message").val();
    if (msgField) {
      var fieldinfo = cj.inArray(msgField, data["values"])
      var matchfield = false;
      cj.each(data["values"], function(key, value) {
        if (value["field_name"] == "custom_"+msgField) {
          matchfield = true;
          return true;
        }
      });
      if (!matchfield) {
        cj("#user_message").after("<div id='profileMissingMessage' style='background-color: #FF9999; border: 1px solid #CC3333; display: inline-block; font-size: 85%; margin-left: 1ex; padding: 0.5ex; vertical-align: top;'>" + ts("This field is not in the activity profile you selected") + "</div>");
      }
    }
  }
});
}
function showHideEmailPetition() {
  if( cj("input#email_petition").attr("checked") ) {
    cj("tr.crm-campaign-survey-form-block-location_type_id").show("fast");
    cj("tr.crm-campaign-survey-form-block-recipient_options").show("fast");
    cj("tr.crm-campaign-survey-form-block-user_message").show("fast");
    cj("tr.crm-campaign-survey-form-block-default_message").show("fast");
    cj("tr.crm-campaign-survey-form-block-subject").show("fast");
  } else {
    cj("tr.crm-campaign-survey-form-block-location_type_id").hide("fast");
    cj("tr.crm-campaign-survey-form-block-recipient_options").hide("fast");
    cj("tr.crm-campaign-survey-form-block-user_message").hide("fast");
    cj("tr.crm-campaign-survey-form-block-default_message").hide("fast");
    cj("tr.crm-campaign-survey-form-block-subject").hide("fast");
  }
}
