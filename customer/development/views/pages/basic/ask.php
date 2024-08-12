<rn:meta title="#rn:msg:ASK_QUESTION_HDG#" template="basic.php" clickstream="incident_create"/>

<h1>#rn:msg:SUBMIT_QUESTION_OUR_SUPPORT_TEAM_CMD#</h1>

<rn:widget path="input/BasicFormStatusDisplay"/>
<rn:form post_handler="postRequest/sendForm">
    <rn:condition answers_viewed="2" searches_done="1">
    <rn:condition_else/>
        <rn:widget path="input/BasicSmartAssistant"/>
    </rn:condition>
    <rn:condition logged_in="false">
        <rn:widget path="input/BasicFormInput" name="Contact.Emails.PRIMARY.Address" required="true" label_input="#rn:msg:EMAIL_ADDR_LBL#"/>
        <rn:widget path="input/BasicFormInput" name="Incident.Subject" required="true" label_input="#rn:msg:SUBJECT_LBL#"/>
    </rn:condition>
    <rn:condition logged_in="true">
        <rn:widget path="input/BasicFormInput" name="Incident.Subject" required="true" label_input="#rn:msg:SUBJECT_LBL#"/>
    </rn:condition>
    <rn:widget path="input/BasicFormInput" name="Incident.Threads" required="true" label_input="#rn:msg:QUESTION_LBL#"/>
    <rn:widget path="input/BasicProductCategoryInput" name="Incident.Product"/>
    <rn:widget path="input/BasicProductCategoryInput" name="Incident.Category"/>
    <rn:widget path="input/BasicCustomAllInput" table="Incident"/>
    <rn:widget path="input/BasicFormSubmit" on_success_url="/app/ask_confirm" clickstream_action="incident_submit"/>
</rn:form>
