<rn:meta title="#rn:msg:MODERATE_SOCIAL_QUESTION_LBL#" template="standard.php" login_required="true" social_moderator_required="true" clickstream="question_moderation_dashboard_view"/>

<rn:container report_id="15100" object_type="CommunityQuestion" per_page="10">
<div id="rn_PageTitle" class="rn_Hero rn_ModerateQuestion">
    <div class="rn_HeroInner">
        <div class="rn_Container">
            <h1>#rn:msg:QUESTION_MODERATION_DASHBOARD_LBL#</h1>
        </div>
        <br />
        <a href="/app/social/moderate/overview">#rn:msg:MODERATION_OVERVIEW_LBL#</a>
        <a href="/app/social/moderate/comment">#rn:msg:MODERATE_COMMENTS_LBL#</a>
        <rn:condition is_social_user_moderator="true">
            <a href="/app/social/moderate/user">#rn:msg:MODERATE_USERS_LBL#</a>
        </rn:condition>
    </div>
</div>
<div id="rn_PageContent" class="rn_MegaContainer rn_ModerationDashboard">
    <div id="rn_MessageLocation" role="alert" aria-label="#rn:msg:MESSAGE_BOX_LBL#"></div>
    <div>
        <rn:widget path="moderation/ModerationFilterDialog" sub:date:report_filter_name="questions.updated" sub:status:report_filter_name="questions.status" sub:prodcat:filter_type="products" sub:prodcat:label_input="#rn:msg:PRODUCT_LBL#" sub:flag:report_filter_name="question_content_flags.flag"/>
        <rn:widget path="moderation/ModerationFilterBreadCrumbs"/>
    </div>
    <rn:widget path="reports/ResultInfo"/>
    <div class="rn_QuestionModerationGrid_Container">
        <rn:widget path="moderation/ModerationGrid" exclude_from_sorting="1,2,3,5,6,7" icon_cols="4" label_caption="<span class='rn_ScreenReaderOnly'>#rn:msg:QUESTION_MODERATION_TABLE_LBL#</span>"/>
    </div>
    <rn:widget path="reports/Paginator"/>
    <rn:widget path="moderation/ModerationAction" message_location="rn_MessageLocation" label_action_suspend_user="#rn:msg:SUSPEND_AUTHOR_LBL#" label_action_approve_restore_user="#rn:msg:APPROVERESTORE_AUTHOR_LBL#"/>
</div>
</rn:container>
