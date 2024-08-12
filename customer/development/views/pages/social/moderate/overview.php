<rn:meta title="#rn:msg:MODERATION_OVERVIEW_LBL#" template="standard.php" login_required="true" social_moderator_required="true" clickstream="moderation_overview_view"/>
<div id="rn_PageTitle" class="rn_Hero rn_ModerationOverview">
    <div class="rn_HeroInner">
        <div class="rn_Container">
            <h1>#rn:msg:MODERATION_OVERVIEW_LBL#</h1>
        </div>
        <br />
        <a href="/app/social/moderate/comment">#rn:msg:MODERATE_COMMENTS_LBL#</a>
        <a href="/app/social/moderate/question">#rn:msg:MODERATE_QUESTIONS_LBL#</a>
        <rn:condition is_social_user_moderator="true">
            <a href="/app/social/moderate/user">#rn:msg:MODERATE_USERS_LBL#</a>
        </rn:condition>
    </div>
</div> 
<div class="rn_PageContent rn_Container rn_ModerationOverview">
    <h2>#rn:msg:ANNOUNCEMENTS_LBL#</h2>
    <div class="rn_ModerationAnnouncement">
        <rn:widget path="standard/utils/AnnouncementText" file_path="/euf/assets/others/moderator-announcement.txt" label_heading=""/>
    </div>
    <h2>#rn:msg:SUMMARY_LBL#</h2>
    <rn:widget path="moderation/ModerationSummaryTable" label_caption="<span class='rn_ScreenReaderOnly'>#rn:msg:SOCIAL_OBJECT_COUNTS_BY_STATUS_LBL#</span>" />
    <h2>#rn:msg:ACTIVITY_CHARTS_LBL#</h2>
    <div class="rn_ActivityChartsContainer">
        <rn:widget path="moderation/ActivityCharts"/>
        <rn:widget path="moderation/ActivityCharts" object_types="CommunityUser" label_chart_title="#rn:msg:NEW_SOCIAL_USERS_PER_DAY_PAST_7_DAYS_LBL#" />
    </div>
</div>

