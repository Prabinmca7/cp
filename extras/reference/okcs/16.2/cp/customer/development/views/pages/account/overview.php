<rn:meta title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" template="standard.php" login_required="true" force_https="true" />

<div class="rn_Hero">
    <div class="rn_Container">
        <h1>#rn:msg:ACCOUNT_OVERVIEW_LBL#</h1>
    </div>
</div>

<div class="rn_PageContent rn_AccountOverview rn_Container">
    <h2><a class="rn_Questions" href="/app/account/questions/list#rn:session#">#rn:msg:QUESTIONS_HDG#</a></h2>
    <div class="rn_Questions">
        <rn:widget path="reports/Grid" report_id="196" per_page="4" label_caption="<span class='rn_ScreenReaderOnly'>#rn:msg:YOUR_RECENTLY_SUBMITTED_QUESTIONS_LBL#</span>"/>
        <a href="/app/account/questions/list#rn:session#">#rn:msg:SEE_ALL_QUESTIONS_LBL#</a>
    </div>
    <h2><a class="rn_Profile" href="/app/account/profile#rn:session#">#rn:msg:SETTINGS_LBL#</a></h2>
    <div class="rn_Profile">
        <a href="/app/account/profile#rn:session#">#rn:msg:UPDATE_YOUR_ACCOUNT_SETTINGS_CMD#</a><br/>
        <rn:condition external_login_used="false">
            <rn:condition config_check="EU_CUST_PASSWD_ENABLED == true">
                <a href="/app/#rn:config:CP_CHANGE_PASSWORD_URL##rn:session#">#rn:msg:CHANGE_YOUR_PASSWORD_CMD#</a>
            </rn:condition>
        </rn:condition>
    </div>
    <h2><a class="rn_Notifs" href="/app/account/notif/list#rn:session#">#rn:msg:NOTIFICATIONS_HDG#</a></h2>
    <div class="rn_Notifs">
        <rn:widget path="okcs/OkcsAnswerNotificationManager" view_type="table" label_caption="<span class='rn_ScreenReaderOnly'>#rn:msg:ANSWER_NOTIFICATIONS_SLASH_SPAN_LBL#</span>"/>
        <a href="/app/account/notif/list#rn:session#">#rn:msg:SEE_ALL_NOTIFICATIONS_LBL#</a>
    </div>
</div>
