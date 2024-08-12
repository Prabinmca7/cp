<rn:meta title="#rn:msg:MODERATE_SOCIAL_COMMENT_LBL#" template="standard.php" login_required="false" social_moderator_required="true" clickstream="comment_moderation_dashboard_view"/>

<rn:container report_id="15101" object_type="CommunityComment" per_page="10">
<div id="rn_PageTitle" class="rn_Hero rn_ModerateComment">
    <div class="rn_HeroInner">
        <div class="rn_Container">
            <h1>#rn:msg:COMMENT_MODERATION_DASHBOARD_LBL#</h1>
        </div>
        <br />
        <a href="/app/social/moderate/overview">#rn:msg:MODERATION_OVERVIEW_LBL#</a>
        <a href="/app/social/moderate/question">#rn:msg:MODERATE_QUESTIONS_LBL#</a>
        <rn:condition is_social_user_moderator="true">
            <a href="/app/social/moderate/user">#rn:msg:MODERATE_USERS_LBL#</a>
        </rn:condition>
    </div>
</div>
<div id="rn_PageContent" class="rn_MegaContainer rn_ModerationDashboard">
    <div id="rn_MessageLocation" role="alert" aria-label="#rn:msg:MESSAGE_BOX_LBL#"></div>
    <div>
        <rn:widget path="moderation/ModerationFilterDialog" sub:flag:report_filter_name="comment_cnt_flgs.flag" sub:date:report_filter_name="comments.updated" sub:status:report_filter_name="comments.status" sub:prodcat:filter_type="products" sub:prodcat:label_input="#rn:msg:PRODUCT_LBL#"/>
        <rn:widget path="moderation/ModerationFilterBreadCrumbs"/>
    </div>
    <rn:widget path="reports/ResultInfo"/>
    <div class="rn_CommentModerationGrid_Container">
        <rn:widget path="moderation/ModerationGrid" exclude_from_sorting="1,2,3,4,6,7,8" icon_cols="5" sanitize_data="2|16" truncate_size="75" label_caption="<span class='rn_ScreenReaderOnly'>#rn:msg:COMMENT_MODERATION_TABLE_LBL#</span>" product_column_index="4" category_column_index="10"/>
    </div>
    <rn:widget path="reports/Paginator"/>
    <rn:widget path="moderation/ModerationAction" message_location="rn_MessageLocation" label_action_suspend_user="#rn:msg:SUSPEND_AUTHOR_LBL#" label_action_approve_restore_user="#rn:msg:APPROVERESTORE_AUTHOR_LBL#"/>
</div>
</rn:container>
