<rn:meta title="#rn:msg:MODERATE_SOCIAL_USER_LBL#" template="standard.php" login_required="true" social_user_moderator_required="true" clickstream="user_moderation dashboard_view"/>

<rn:container report_id="15102" object_type="CommunityUser" per_page="10">
<div id="rn_PageTitle" class="rn_Hero rn_ModerateUser">
    <div class="rn_HeroInner">
        <div class="rn_Container">
            <h1>#rn:msg:USER_MODERATION_DASHBOARD_LBL#</h1>
        </div>
        <br />
        <a href="/app/social/moderate/overview">#rn:msg:MODERATION_OVERVIEW_LBL#</a> <a href="/app/social/moderate/comment">#rn:msg:MODERATE_COMMENTS_LBL#</a>  <a href="/app/social/moderate/question">#rn:msg:MODERATE_QUESTIONS_LBL#</a>
    </div>
</div>
<div id="rn_PageContent" class="rn_MegaContainer rn_ModerationDashboard">
    <div id="rn_MessageLocation" role="alert" aria-label="#rn:msg:MESSAGE_BOX_LBL#"></div>
    <div>
        <rn:widget path="moderation/ModerationFilterDialog" sub:status:report_filter_name="users.status"/>
        <rn:widget path="moderation/ModerationFilterBreadCrumbs"/>
    </div>
    <rn:widget path="reports/ResultInfo"/>
    <div class="rn_UserModerationGrid_Container">
        <rn:widget path="moderation/ModerationGrid" avatar_column_index="1" exclude_from_sorting="1,2,3,4,5" icon_cols="6,7,8,9,10,11" primary_info_column_index="2" label_caption="<span class='rn_ScreenReaderOnly'>#rn:msg:USER_MODERATION_TABLE_LBL#</span>"/>
    </div>
    <rn:widget path="reports/Paginator"/>
    <rn:widget path="moderation/ModerationAction" message_location="rn_MessageLocation"/>
</div>
</rn:container>
