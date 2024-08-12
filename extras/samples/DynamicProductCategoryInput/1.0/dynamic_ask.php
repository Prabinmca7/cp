<?php
/**
 * File: dynamic_ask.php
 * Abstract: PHP view providing an example dynamic form using the DynamicProductCategoryInput widget
 * Version: 1.0
 */
?>

<rn:meta title="#rn:msg:ASK_QUESTION_HDG#" template="standard.php" clickstream="incident_create"/>
<div id="rn_PageTitle" class="rn_AskQuestion">
    <h1>#rn:msg:SUBMIT_QUESTION_OUR_SUPPORT_TEAM_CMD#</h1>
</div>
<div id="rn_PageContent" class="rn_PageContent rn_AskQuestion rn_Container">
    <form id="rn_QuestionSubmit" method="post" action="/ci/ajaxRequest/sendForm">

        <div id="rn_ErrorLocation"></div>

<?php
/**
* NOTE: The attribute 'show_fields_for_ids' should be modified to reflect the Product IDs configured
* in your site. The ones provided as just as an example and may or may not work depending on
* the configuration of your site.
*/
?>
        <rn:widget path="input/DynamicProductCategoryInput" name="Incident.Product" show_fields_for_ids="1:Incident.Category|2:Incident.Category" />

<?php
/**
* NOTE: The attribute 'show_fields_for_ids' and 'fields_required_for_ids' should be modified to
* reflect the Product IDs configured in your site. The ones provided as just as an example
* and may or may not work depending on the configuration of your site.
*/
?>
        <rn:widget path="input/DynamicProductCategoryInput"
            name="Incident.Category"
            show_fields_for_ids="68:Incident.CustomFields.CP.SerialNumber|78:Incident.CustomFields.CP.SerialNumber,Incident.CustomFields.CP.PurchaseDate,Incident.CustomFields.CP.RequestCallback|*:Contact.Emails.PRIMARY.Address,Incident.Subject,Incident.Threads,Incident.FileAttachments"
            fields_required_for_ids="77:Incident.FileAttachments|78:Incident.CustomFields.CP.SerialNumber,Incident.FileAttachments|79:Incident.FileAttachments"
            hide_on_load="true"/>

<?php
/* The rest of the form fields */
?>
        <rn:widget path="input/FormInput" name="Incident.CustomFields.CP.SerialNumber" label_input="Serial Number" hide_on_load="true" />
        <rn:widget path="input/FormInput" name="Incident.CustomFields.CP.PurchaseDate" label_input="Date Purchased" hide_on_load="true" />
        <rn:widget path="input/FormInput" name="Incident.CustomFields.CP.RequestCallback" label_input="Request Callback From an Agent?" hide_on_load="true" />

        <rn:condition logged_in="false">
            <rn:widget path="input/FormInput" name="Contact.Emails.PRIMARY.Address" hide_on_load="true" required="true" initial_focus="true" label_input="#rn:msg:EMAIL_ADDR_LBL#"/>
            <rn:widget path="input/FormInput" name="Incident.Subject" hide_on_load="true" required="true" label_input="#rn:msg:SUBJECT_LBL#"/>
        </rn:condition>
        <rn:condition logged_in="true">
            <rn:widget path="input/FormInput" name="Incident.Subject" hide_on_load="true" required="true" initial_focus="true" label_input="#rn:msg:SUBJECT_LBL#"/>
        </rn:condition>
        <rn:widget path="input/FormInput" name="Incident.Threads" hide_on_load="true" required="true" label_input="#rn:msg:QUESTION_LBL#"/>
        <rn:widget path="input/FileAttachmentUpload" hide_on_load="true"/>

        <rn:widget path="input/FormSubmit" label_button="#rn:msg:CONTINUE_ELLIPSIS_CMD#" on_success_url="/app/ask_confirm" error_location="rn_ErrorLocation"/>
    </form>
</div>
