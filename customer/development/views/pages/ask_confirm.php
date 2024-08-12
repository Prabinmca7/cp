<rn:meta title="#rn:msg:QUESTION_SUBMITTED_LBL#" template="standard.php" clickstream="incident_confirm"/>

<div class="rn_Hero">
    <div class="rn_Container">
        <h1>#rn:msg:YOUR_QUESTION_HAS_BEEN_SUBMITTED_LBL#</h1>
    </div>
</div>

<div class="rn_PageContent rn_AskQuestion rn_Container">
    <p>
        <rn:condition url_parameter_check="i_id == null">
            <rn:condition flashdata_value_for="newlySubmittedIncidentRefNum">
                #rn:msg:SUBMITTING_QUEST_REFERENCE_FOLLOW_LBL#
                <b>##rn:flashdata:newlySubmittedIncidentRefNum#</b>
            <rn:condition_else/>
                #rn:msg:THANKS_FOR_SUBMITTING_YOUR_QUESTION_MSG#
            </rn:condition>
        <rn:condition_else/>
            #rn:msg:SUBMITTING_QUEST_REFERENCE_FOLLOW_LBL#
            <b><a href="/app/#rn:config:CP_INCIDENT_RESPONSE_URL#/i_id/#rn:url_param_value:i_id##rn:session#">#<rn:field name="Incident.ReferenceNumber" /></a>.</b>
        </rn:condition>
    </p>
    <p>
        #rn:msg:SUPPORT_TEAM_SOON_MSG#
    </p>
    <rn:condition logged_in="true">
    <p>
        #rn:msg:NEED_UPD_EXP_OR_M_GO_TO_HIST_O_UPD_IT_MSG#
    </p>
    <rn:condition_else/>
    <p>
        #rn:msg:UPD_ADDR_LG_EXP_OR_M_HIST_C_T_O_UPD_IT_MSG#
    </p>
    <p>
        #rn:msg:DONT_ACCT_ACCOUNT_ASST_ENTER_EMAIL_MSG#
        <a href="/app/#rn:config:CP_ACCOUNT_ASSIST_URL##rn:session#">#rn:msg:ACCOUNT_ASSISTANCE_LBL#</a>
    </p>
    </rn:condition>
</div>
