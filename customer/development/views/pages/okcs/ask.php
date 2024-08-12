<rn:meta title="#rn:msg:ASK_QUESTION_HDG#" template="okcs_standard.php" clickstream="incident_create"/>
<div class="rn_Container">
    <div id="rn_PageTitle" class="rn_AskQuestion">
        <h1>#rn:msg:SUBMIT_QUESTION_OUR_SUPPORT_TEAM_CMD#</h1>
    </div>
</div>
<div id="rn_PageContent" class="rn_AskQuestion rn_Container">
    <div class="rn_Padding">
        <form id="rn_QuestionSubmit" method="post" action="/ci/ajaxRequest/sendForm">
            <div id="rn_ErrorLocation"></div>
            <rn:condition logged_in="false">
                <rn:widget path="input/FormInput" name="Contact.Emails.PRIMARY.Address" required="true" initial_focus="true" label_input="#rn:msg:EMAIL_ADDR_LBL#"/>
                <rn:widget path="input/FormInput" name="Incident.Subject" required="true" label_input="#rn:msg:SUBJECT_LBL#"/>
            </rn:condition>
            <rn:condition logged_in="true">
                <rn:widget path="input/FormInput" name="Incident.Subject" required="true" initial_focus="true" label_input="#rn:msg:SUBJECT_LBL#"/>
            </rn:condition>
                <rn:widget path="input/FormInput" name="Incident.Threads" required="true" label_input="#rn:msg:QUESTION_LBL#"/>
                <rn:widget path="input/FileAttachmentUpload"/>
                <rn:widget path="input/ProductCategoryInput" name="Incident.Product"/>
                <rn:widget path="input/ProductCategoryInput" name="Incident.Category" data_type="Category"/>
                <rn:widget path="input/FormSubmit" label_button="#rn:msg:CONTINUE_ELLIPSIS_CMD#" on_success_url="/app/ask_confirm" error_location="rn_ErrorLocation"/>
                <rn:widget path="okcs/OkcsSmartAssistant" view_type="explorer"/>
        </form>
    </div>
</div>
