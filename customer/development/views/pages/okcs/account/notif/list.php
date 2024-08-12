<rn:meta title="#rn:msg:NOTIFICATIONS_HDG#" template="okcs_standard.php" login_required="true" force_https="true"/>
<div class="rn_Hero">
    <div class="rn_Container">
        <h1>#rn:msg:NOTIFICATIONS_HDG#</h1>
    </div>
</div>
<div id="rn_LoadingIndicator" class="rn_Browse">
    <rn:widget path="okcs/LoadingIndicator"/>
</div>
<div class="rn_Container">
    <rn:widget path="okcs/OkcsSetNotificationFrequency"/>
</div>
<div class="rn_PageContent rn_AccountNotifications rn_Container">
    <h2>#rn:msg:ANS_NOTIFICATIONS_LBL#</h2>
    <rn:widget path="okcs/OkcsAnswerNotificationManager" view_type="list"/>
</div>
