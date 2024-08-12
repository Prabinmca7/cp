<rn:meta title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" template="standard.php" login_required="true" force_https="true" />

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
        <div class="rn_Discussions">
            <rn:container report_id="15156" per_page="4">
                <div class="rn_HeaderContainer">
                    <h2><a class="rn_Discussions" href="/app/social/questions/list/author/#rn:profile:socialUserID#/kw/*#rn:session#">#rn:msg:MY_DISCUSSION_QUESTIONS_LBL#</a></h2>
                </div>
                <rn:widget path="reports/Grid" static_filter="created_by=#rn:profile:socialUserID#" label_caption="<span class='rn_ScreenReaderOnly'>#rn:msg:YOUR_RECENTLY_SUBMITTED_DISCUSSIONS_LBL#</span>"/>
                <a href="/app/social/questions/list/author/#rn:profile:socialUserID#/kw/*#rn:session#">#rn:msg:SEE_ALL_MY_DISCUSSION_QUESTIONS_LBL#</a>
            </rn:container>
        </div>
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
                <rn:condition is_active_social_user="true">
                        <li><a href="/app/public_profile/user/#rn:profile:socialUserID#">#rn:msg:VIEW_YOUR_PUBLIC_PROFILE_LBL#</a></li>
                </rn:condition>
            </ul>
        </div>
    </div>
</div>
