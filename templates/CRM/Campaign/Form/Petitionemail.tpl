   <tr class="crm-campaign-survey-form-block-email_petition">
       <td class="label">{$form.email_petition.label}</td>
       <td class="view-value">{$form.email_petition.html}
      <div class="description">{ts}Should signatures generate an email to the petition's  target?.{/ts}</div></td>
   </tr>
   <tr class="crm-campaign-survey-form-block-recipient_options">
       <td class="label">Recipient Options</td>
       <td class="view-value">
         <div class="petitionemail-recipient-options">
           <div class="petition-email-recipient-option-description">{ts}You must specify who will receive a copy of the petition using at least one of the methods below. You may also use both methods if you would like one set of recipients to receive all petitions signed and another set of recipients to be chosen dynamically.{/ts}</div>

           <h3 class="petition-email-header">Dynamic Method</h3>

           <div class="petition-email-recipient-option-description">The dynamic method allows you to choose different petition recipients depending on who is filling out the petition. To use the dynamic method, you must first put your targets into a group. Then specify the group below, and the fields that must match between the person filling out the petition and the target contact.</div>
           <div class="label">{$form.group_id.label}</div>
           <div class="view-value">{$form.group_id.html}</div>
           <div class="description">{ts}Select the group containing the contacts that you want to receive the petition.{/ts}</div>

           <div class="label">{$form.matching_fields.label}</div>
           <div class="view-value">{$form.matching_fields.html}</div>
           <div class="description">{ts}If the user and the target have the same value for this field, then the user's petition will be sent to the matching target.{/ts}</div>

           <div class="label">{$form.location_type_id.label}</div>
           <div class="view-value">{$form.location_type_id.html}</div>
           <div class="description">{ts}A target contact can have more than one email address. Choose the email location that should be used when sending the petition{/ts}</div>

           <h3 class="petition-email-header">Static Method</h3>

           <div class="label">{$form.recipients.label}</div>
           <div class="view-value">{$form.recipients.html}</div>
           <div class="description">{ts}Enter additional targets that receive copies of all petitions in the form: 'First name Last name' &lt;email@example.org&gt;. Separate multiple recipients with line breaks.{/ts}</div></td>

         </div>
       </td>
   </tr>
  
   <tr class="crm-campaign-survey-form-block-user_message">
       <td class="label">{$form.user_message.label}</td>
       <td class="view-value">{$form.user_message.html}
      <div class="description">{ts}Select a field that will have the signer's custom message.  Make sure it is included in the Activity Profile you selected.{/ts}</div></td>
   </tr>
   <tr class="crm-campaign-survey-form-block-default_message">
       <td class="label">{$form.default_message.label}</td>
       <td class="view-value">{$form.default_message.html}
      <div class="description">{ts}Enter the default message to be included in the email.{/ts}</div></td>
   </tr>
   <tr class="crm-campaign-survey-form-block-subject">
       <td class="label">{$form.subject.label}</td>
       <td class="view-value">{$form.subject.html}
      <div class="description">{ts}Enter the subject line that should appear in the target email.{/ts}</div></td>
   </tr>
