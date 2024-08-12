<rn:meta title="#rn:php:\RightNow\Libraries\SEO::getDynamicTitle('PublicProfile', \RightNow\Utils\Url::getParameter('user'), array('suffix' => ' - ' . \RightNow\Utils\Config::getMessage(PROFILE_LC_LBL)))#" template="standard.php" clickstream="public_profile"/>

<div class="rn_Hero">
    <div class="rn_Container">
        <div class="rn_UserInfo">
            <div class="rn_AvatarAlignment">
                <rn:widget path="user/AvatarDisplay"/>
                <? if (\RightNow\Utils\Permissions\Social::userCanEdit('AvatarOrDisplayName')): ?>
                    <div class="rn_EditSocialFields"><i class="fa fa-pencil-square-o"></i> <a href="/app/public_profile_update/#rn:url_param:user#">#rn:msg:UPDATE_CMD#</a></div>
                <? endif; ?>
            </div>

            <div class="rn_UserDetails">
                <h1><rn:field name="CommunityUser.DisplayName"/></h1>
                #rn:msg:MEMBER_SINCE_LBL# <span itemprop="dateCreated"><rn:field name="CommunityUser.CreatedTime" /></span>
            </div>
            <rn:widget path="user/UserStatus"/>
        </div>
    </div>
</div>

<div class="rn_PublicProfile rn_Container">
    <div class="rn_ContentDetail">
        <rn:condition flashdata_value_for="info">
            <div class="rn_MessageBox rn_InfoMessage" role="alert">
                #rn:flashdata:info#
            </div>
        </rn:condition>

        <div class="rn_ModerationToolbar">
            <div>
                <rn:condition is_social_user_moderator="true">
                    <rn:widget path="moderation/ModerationInlineAction" object_type="CommunityUser" object_id="#rn:php:\RightNow\Utils\Url::getParameter('user')#" refresh_page_on_moderator_action="true"/>
                </rn:condition>
            </div>
        </div>
        <rn:widget path="user/UserActivity"/>
    </div>

    <div class="rn_SideRail">
        <rn:widget path="moderation/ModerationProfile"/>

        <div class="rn_ProfileSearch">
            <div class="rn_WellDark">
                <h3>#rn:msg:SEARCH_USERS_CONTRIBUTIONS_LBL#</h3>
                <rn:widget path="search/SimpleSearch" report_page_url="/app/social/questions/list" add_params_to_url="author:userFromUrl"/>
            </div>
        </div>

        <rn:widget path="user/UserContributions"/>

        <div class="rn_DetailTools rn_AdditionalInfo">
            <rn:widget path="utils/PrintPageLink"/>
        </div>
    </div>
</div>
