<rn:meta title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" template="basic.php" login_required="true" force_https="true" />

<div>
    <h1>#rn:msg:ACCOUNT_OVERVIEW_LBL#</h1>
</div>
<div>
    <h2>#rn:msg:QUESTIONS_HDG#</h2>
    <div>
        <rn:widget path="reports/BasicMultiline" report_id="196" per_page="4"/>
        <a href="/app/account/questions/list#rn:session#">#rn:msg:SEE_ALL_QUESTIONS_LBL#</a>
    </div>
    <h2>#rn:msg:SETTINGS_LBL#</h2>
    <div class="rn_LinksBlock">
        <a href="/app/account/profile#rn:session#">#rn:msg:UPDATE_YOUR_ACCOUNT_SETTINGS_CMD#</a>
        <rn:condition external_login_used="false">
            <rn:condition config_check="EU_CUST_PASSWD_ENABLED == true">
                <a href="/app/#rn:config:CP_CHANGE_PASSWORD_URL##rn:session#">#rn:msg:CHANGE_YOUR_PASSWORD_CMD#</a>
            </rn:condition>
        </rn:condition>
    </div>
</div>
