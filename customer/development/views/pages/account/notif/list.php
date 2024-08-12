<rn:meta title="#rn:msg:NOTIFICATIONS_HDG#" template="standard.php" login_required="true" force_https="true"/>
<div class="rn_Hero">
    <div class="rn_Container">
        <h1>#rn:msg:NOTIFICATIONS_HDG#</h1>
    </div>
</div>

<div class="rn_PageContent rn_AccountNotifications rn_Container">
    <h2>#rn:msg:ANS_NOTIFICATIONS_LBL#</h2>
    <rn:widget path="notifications/AnswerNotificationManager" />
    <h2>#rn:msg:PRODUCTCATEGORY_ANSWER_NOTIFICATIONS_LBL#</h2>
    <rn:widget path="notifications/ProdCatNotificationManager"/>
    <rn:condition is_social_user="true">
        <h2>#rn:msg:DISCUSSION_NOTIFICATIONS_LBL#</h2>
        <rn:container report_id="15104">
            <rn:widget path="reports/ResultInfo" static_filter="user_id=#rn:profile:socialUserID#"/>
            <rn:widget path="notifications/DiscussionSubscriptionManager" static_filter="user_id=#rn:profile:socialUserID#"/>
        </rn:container>
        <h2>#rn:msg:PRODUCT_DISCUSSION_NOTIFICATIONS_LBL#</h2>
        <rn:container report_id="15105">
            <rn:widget path="reports/ResultInfo" static_filter="user_id=#rn:profile:socialUserID#"/>
            <rn:widget path="notifications/DiscussionSubscriptionManager" static_filter="user_id=#rn:profile:socialUserID#" subscription_type="Product" label_no_notification="#rn:msg:CURRENTLY_DONT_ANY_DISC_NOTIF_MSG#"/>
        </rn:container>
    </rn:condition>
</div>
