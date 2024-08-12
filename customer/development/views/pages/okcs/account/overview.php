<rn:meta title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" template="okcs_standard.php" login_required="true" force_https="true" />

<div class="rn_Hero">
    <div class="rn_Container">
        <h1>#rn:msg:ACCOUNT_OVERVIEW_LBL#</h1>
    </div>
</div>

<div class="rn_PageContent rn_AccountOverview rn_Container">
    <div class="rn_ContentDetail">
        <div class="rn_Questions">
            <rn:container report_id="196" per_page="4">
                <div class="rn_HeaderContainer">
                    <h2><a class="rn_Questions" href="/app/account/questions/list#rn:session#">#rn:msg:MY_SUPPORT_QUESTIONS_LBL#</a></h2>
                </div>
                <rn:widget path="reports/Grid" label_caption="<span class='rn_ScreenReaderOnly'>#rn:msg:YOUR_RECENTLY_SUBMITTED_QUESTIONS_LBL#</span>"/>
                <a href="/app/account/questions/list#rn:session#">#rn:msg:SEE_ALL_MY_SUPPORT_QUESTIONS_LBL#</a>
            </rn:container>
        </div>
    
        <div id="rn_LoadingIndicator" class="rn_Browse">
            <rn:widget path="okcs/LoadingIndicator"/>
        </div>
        <rn:container source_id="OKCSBrowse">
            <div class="rn_HeaderContainer">
                <h2><a class="rn_Profile" href="/app/account/recommendations/list#rn:session#">#rn:msg:MY_RECOMMENDATIONS_LBL#</a></h2>
            </div>
            <div id="rn_OkcsManageRecommendations">
                <rn:widget path="okcs/OkcsManageRecommendations"/>
                <a href="/app/account/recommendations/list#rn:session#">#rn:msg:SEE_ALL_RECOMMENDATIONS_LBL#</a>
            </div>
        <div class="rn_HeaderContainer">
            <h2><a class="rn_Profile" href="/app/account/favorites/list#rn:session#">#rn:msg:MY_FAVORITES_LBL#</a></h2>
        </div>
        <div id="rn_OkcsFavoritesList">
            <rn:widget path="okcs/OkcsFavoritesList"/>
            <a href="/app/account/favorites/list#rn:session#">#rn:msg:SEE_ALL_FAVORITES_LBL#</a>
        </div>
        </rn:container>
    </div>

    <div class="rn_SideRail">
        <div class="rn_Well">
            <h3>#rn:msg:LINKS_LBL#</h3>
            <ul>
                <li><a href="/app/account/profile#rn:session#">#rn:msg:UPDATE_YOUR_ACCOUNT_SETTINGS_CMD#</a></li>
                <rn:condition external_login_used="false">
                    <rn:condition config_check="EU_CUST_PASSWD_ENABLED == true">
                        <li><a href="/app/account/change_password#rn:session#">#rn:msg:CHANGE_YOUR_PASSWORD_CMD#</a></li>
                    </rn:condition>
                </rn:condition>
                <li><a href="/app/account/notif/list#rn:session#">#rn:msg:MANAGE_YOUR_NOTIFICATIONS_LBL#</a></li>
                <li><a href="/app/account/notif/content_type_list#rn:session#">#rn:msg:MANAGE_YOUR_CONTENT_TYPE_NOTIFICATIONS_LBL#</a></li>
                <rn:condition is_active_social_user="true">
                        <li><a href="/app/public_profile/user/#rn:profile:socialUserID#">#rn:msg:VIEW_YOUR_PUBLIC_PROFILE_LBL#</a></li>
                </rn:condition>
            </ul>
        </div>
    </div>
</div>
