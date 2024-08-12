<rn:meta title="#rn:msg:SUBMIT_FEEDBACK_CMD#" template="basic.php"/>

<rn:condition url_parameter_check="a_id != null">
    <div>
        <a href="/app/#rn:config:CP_ANSWERS_DETAIL_URL#/a_id/#rn:url_param_value:a_id##rn:session#">#rn:msg:BACK_TO_ANSWER_CMD#</a>
        <br/>
    </div>

    <rn:widget path="input/BasicFormStatusDisplay"/>
    <p>#rn:msg:RATING_SUBMITTED_PLS_TELL_ANS_MSG#</p>
    <rn:form post_handler="postRequest/submitFeedback">
        <input type="hidden" name="answerFeedback[OptionsCount]" value="#rn:url_param_value:options_count#" />
        <input type="hidden" name="answerFeedback[Threshold]" value="#rn:url_param_value:threshold#" />
        <input type="hidden" name="answerFeedback[AnswerId]" value="#rn:url_param_value:a_id#" />
        <input type="hidden" name="answerFeedback[Rating]" value="#rn:url_param_value:rating#" />
        
        <rn:condition logged_in="false">
            <rn:widget path="input/BasicFormInput" name="Contact.Emails.PRIMARY.Address" required="true" label_input="#rn:msg:EMAIL_ADDR_LBL#"/>
        </rn:condition>

        <rn:widget path="input/BasicFormInput" name="Incident.Threads" required="true" label_input="#rn:msg:FEEDBACK_LBL#"/>
        <rn:widget path="input/BasicFormSubmit" on_success_url="/app/#rn:config:CP_ANSWERS_DETAIL_URL#"/>
    </rn:form>
<rn:condition_else>
    <p>#rn:msg:INVALID_ANSWER_ID_LBL#</p>
    <p><a href="/app/#rn:config:CP_HOME_URL##rn:session#">#rn:msg:BACK_TO_SUPPORT_HOME_CMD#</a></p>
    <br />
</rn:condition>
