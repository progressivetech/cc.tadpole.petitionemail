   <tr class="crm-campaign-survey-form-block-email_petition">
       <td class="label">{$form.email_petition.label}</td>
       <td class="view-value">{$form.email_petition.html}
      <div class="description">{ts}Should signatures generate an email to the petition's  target?.{/ts}</div></td>
   </tr>
   <tr class="crm-campaign-survey-form-block-subject">
       <td class="label">{$form.subject.label}</td>
       <td class="view-value">{$form.subject.html}
      <div class="description">{ts}Enter the subject line that should appear in the target email.{/ts}</div></td>
   </tr>
   <tr class="crm-campaign-survey-form-block-default_message">
       <td class="label">{$form.default_message.label}</td>
       <td class="view-value">{$form.default_message.html}
      <div class="description">{ts}Enter the default message to be included in the email.{/ts}</div></td>
   </tr>
  <tr class="crm-campaign-survey-form-block-message_field">
       <td class="label">{$form.message_field.label}</td>
       <td class="view-value">{$form.message_field.html}
      <div class="description">{ts}Select a field that will have the signer's custom message.  Make sure it is included in the Activity Profile you selected.{/ts}</div></td>
   </tr>

   <tr class="crm-campaign-survey-form-block-recipient_options">
       <td class="label">Target/Recipient Options</td>
       <td class="view-value">
         <div class="petitionemail-recipient-options">
           <div class="petition-email-recipient-option-description">{ts}You must specify who will receive a copy of the petition using at least one of the methods below. You may also use both methods if you would like one set of recipients to receive all petitions signed and another set of recipients to be chosen dynamically.{/ts}</div>

           <h3 class="petition-email-header">Dynamic Method</h3>

           <div class="petition-email-recipient-option-description">The dynamic method allows you to choose different petition recipients depending on who is filling out the petition. To use the dynamic method, you must first put your targets into a group. Then specify the group below, and the field that must match between the person filling out the petition and the target contact. You can choose up to three group/field combinations.</div>
           <table id="petition-email-dynamic-method">
             <tr>
               <td>{ts}Target Group{/ts}</td>
               <td></td>
               <td>{ts}Matching field{/ts}</td>
             </tr>
             <tr>
               <td class="view-value">
                 <div class="petitionemail-matching-group_id">{$form.matching_group_id1.html}</div>
                 <div class="petitionemail-matching-group_id">{$form.matching_group_id2.html}</div>
                 <div class="petitionemail-matching-group_id">{$form.matching_group_id3.html}</div>
               </td>
               <td>
                 <div class="petitionemail-use-with">{ts}match using:{/ts}</div>
                 <div class="petitionemail-use-with">{ts}match using:{/ts}</div>
                 <div class="petitionemail-use-with">{ts}match using:{/ts}</div>
               </td> 
               <td class="view-value">
                 <div class="petitionemail-matching-fields">{$form.matching_field1.html}</div>
                 <div class="petitionemail-matching-fields">{$form.matching_field2.html}</div>
                 <div class="petitionemail-matching-fields">{$form.matching_field3.html}</div>
               </td>
             </tr>
             <tr>
              <td colspan="3" class="description">{ts}Select the group containing the contacts that you want to receive the petition along with the field that should match between the petition signer and the target in this group. If the user and the target have the same value for this field, then the user's petition will be sent to the matching target.{/ts}
               {if $petitionemail_matching_fields_count eq 0}
                  {ts}No fields are configured to be used as matching fields. Please <a href={$petitionemail_profile_edit_link}>add fields to the petitionemail profile</a>.{/ts}
               {else}
                  {ts}Don't see the field you want to use? You can <a href={$petitionemail_profile_edit_link}>add more fields to the petitionemail profile</a> and they will show up here.{/ts}
               {/if}
              </td>
             </tr>
           </table>
           <div class="label">{$form.location_type_id.label}</div>
           <div class="view-value">{$form.location_type_id.html}</div>
           <div class="description">{ts}A target contact can have more than one email address. Choose the email location that should be preferred when sending the petition. If blank, or the preferred location is not available for the target, the primary email address will be used.{/ts}</div>

           <h3 class="petition-email-header">Static Method</h3>

           <div class="label">{$form.recipients.label}</div>
           <div class="view-value">{$form.recipients.html}</div>
           <div class="description">{ts}Enter targets that receive copies of all petitions in the form: 'First name Last name' &lt;email@example.org&gt;. Separate multiple recipients with line breaks.{/ts}</div></td>

         </div>
       </td>
   </tr>
  
      
