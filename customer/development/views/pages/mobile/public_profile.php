<rn:meta title="#rn:php:\RightNow\Libraries\SEO::getDynamicTitle('PublicProfile', \RightNow\Utils\Url::getParameter('user'), array('suffix' => ' - ' . \RightNow\Utils\Config::getMessage(PROFILE_LC_LBL)))#" template="mobile.php" clickstream="public_profile"/>

<div class="rn_Hero">
    <div class="rn_Container">
        <div class="rn_UserInfo">
            <div class="rn_AvatarAlignment">
                <rn:widget path="user/AvatarDisplay" avatar_size="medium"/>
            </div>

            <div class="rn_UserDetails">
                <h1><rn:field name="CommunityUser.DisplayName"/></h1>
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
            <rn:widget path="moderation/ModerationInlineAction" label_menu_accessibility="#rn:msg:DOUBLE_TAP_TO_OPEN_OR_CLOSE_THE_S_MENU_LBL#" object_type="CommunityUser" object_id="#rn:php:\RightNow\Utils\Url::getParameter('user')#" refresh_page_on_moderator_action="true"/>
        </div>

        <div class="rn_UserContributions">
            <rn:widget path="user/UserContributions"/>
        </div>

        <rn:widget path="user/UserActivity" avatar_size="small"/>
    </div>
</div>
