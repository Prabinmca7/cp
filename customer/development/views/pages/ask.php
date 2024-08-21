<rn:meta title="#rn:msg:ASK_QUESTION_HDG#" template="standard.php" clickstream="incident_create"/>

<div class="rn_Hero">
    <div class="rn_HeroInner">
        <div class="rn_HeroCopy">
            <h1>#rn:msg:SUBMIT_QUESTION_OUR_SUPPORT_TEAM_CMD#</h1>
            <p>#rn:msg:OUR_DEDICATED_RESPOND_WITHIN_48_HOURS_MSG#</p>
        </div>
        <div class="translucent">
            <strong>#rn:msg:TIPS_LBL#:</strong>
            <ul>
                <li><i class="fa fa-thumbs-up"></i> #rn:msg:INCLUDE_AS_MANY_DETAILS_AS_POSSIBLE_LBL#</li>
            </ul>
        </div>
        <br>
        <p>#rn:msg:NEED_A_QUICKER_RESPONSE_LBL# <a href="/app/social/ask#rn:session#">#rn:msg:ASK_OUR_COMMUNITY_LBL#</a></p>
    </div>
</div>

<div class="rn_PageContent rn_AskQuestion rn_Container">
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
        <rn:widget path="input/ProductCategoryInput" name="Incident.Product"/>
        <rn:widget path="input/ProductCategoryInput" name="Incident.Category"/>
        <rn:widget path="input/FileAttachmentUpload"/>
        <rn:widget path="input/FormSubmit" label_button="#rn:msg:SUBMIT_YOUR_QUESTION_CMD#" on_success_url="/app/ask_confirm" error_location="rn_ErrorLocation"/>
        <rn:condition content_viewed="2" searches_done="1">
        <rn:condition_else/>
        <rn:widget path="input/SmartAssistantDialog" label_prompt="#rn:msg:OFFICIAL_SSS_MIGHT_L_IMMEDIATELY_MSG#"/>
        </rn:condition>
    </form>
</div>
