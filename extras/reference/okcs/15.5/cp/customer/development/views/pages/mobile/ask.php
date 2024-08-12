<rn:meta title="#rn:msg:ASK_QUESTION_HDG#" template="mobile.php" clickstream="incident_create"/>

<section id="rn_PageTitle" class="rn_AskQuestion">
    <h1>#rn:msg:ASK_OUR_SUPPORT_TEAM_A_QUESTION_LBL#</h1>
</section>
<section id="rn_PageContent" class="rn_AskQuestion">
    <div class="rn_Padding">
        <form id="rn_QuestionSubmit" onsubmit="return false;">
            <div id="rn_ErrorLocation"></div>
            <fieldset>
            <rn:condition logged_in="false">
                <rn:widget path="input/FormInput" name="Contact.Emails.PRIMARY.Address" required="true" label_input="#rn:msg:EMAIL_ADDR_LBL#"/>
                <rn:widget path="input/FormInput" name="Incident.Subject" required="true" label_input="#rn:msg:SUBJECT_LBL#"/>
            </rn:condition>
            <rn:condition logged_in="true">
                <rn:widget path="input/FormInput" name="Incident.Subject" required="true" label_input="#rn:msg:SUBJECT_LBL#"/>
            </rn:condition>
                <rn:widget path="input/FormInput" name="Incident.Threads" required="true" label_input="#rn:msg:ADD_ADDITIONAL_DETAILS_CMD#"/>
                <rn:widget path="input/FileAttachmentUpload"/>
                <rn:widget path="input/MobileProductCategoryInput" name="Incident.Product"/>
                <rn:widget path="input/MobileProductCategoryInput" name="Incident.Category" data_type="Category"/>
                <!--<rn:widget path="input/CustomAllInput" table="Incident"/>-->
                <br/><br/><br/>
                <rn:widget path="input/FormSubmit" label_button="#rn:msg:CONTINUE_ELLIPSIS_CMD#" on_success_url="/app/ask_confirm" error_location="rn_ErrorLocation"/>
                <rn:condition answers_viewed="2" searches_done="1">
                <rn:condition_else/>
                    <rn:widget path="okcs/OkcsSmartAssistant" accesskeys_enabled="false" display_button_as_link="" view_type="inline"/>
                </rn:condition>
            </fieldset>
        </form>
    </div>
</section>
