<rn:meta title="#rn:msg:PUBLIC_PROFILE_UPDATE_LBL#" template="standard.php" login_required="true" force_https="true" />

<div class="rn_Hero">
    <div class="rn_Container">
        <h1>#rn:msg:UPDATE_PUBLIC_PROFILE_LBL#</h1>
    </div>
</div>

<div class="rn_PageContent rn_Profile rn_Container">
    <? $userIDFromURL = \RightNow\Utils\Url::getParameter('user'); ?>
    <? if(!$userIDFromURL || !(\RightNow\Utils\Permissions\Social::userCanEdit('AvatarOrDisplayName', $userIDFromURL))): ?>
        <div class="rn_MessageBox rn_ErrorMessage">#rn:msg:YOU_ARE_NOT_AUTHORIZED_ACCESS_PAGE_LBL#</div>
    <? else: ?>
        <rn:condition flashdata_value_for="info">
            <div class="rn_MessageBox rn_InfoMessage">
                #rn:flashdata:info#
            </div>
        </rn:condition>

        <rn:condition url_parameter_check="msg != null">
            <div class="rn_MessageBox rn_InfoMessage">#rn:url_param_value:msg#</div>
        </rn:condition>

        <form id="rn_PublicProfileUpdate" onsubmit="return false;">
            <div id="rn_ErrorLocation"></div>
            <fieldset>
                <div class="rn_PublicProfileUpdateAvatarDisplay">
                    <? if (\RightNow\Utils\Permissions\Social::userCanEdit('Avatar', $userIDFromURL)): ?>
                        <? if(get_instance()->session->getProfileData('socialUserID') === intval($userIDFromURL, 10)): ?>
                            <a href="/app/account/profile_picture/user/#rn:profile:socialUserID#">
                            <rn:widget path="user/AvatarDisplay" user_id="#rn:profile:socialUserID#"/>
                            #rn:msg:CHANGE_YOUR_PROFILE_PICTURE_LBL#
                        <? else: ?>
                            <a href="/app/account/profile_picture/#rn:url_param:user#/redirect/public_profile_update%252Fuser%252F<?=$userIDFromURL?>">
                            <rn:widget path="user/AvatarDisplay" user_id="#rn:url_param_value:user#"/>
                            #rn:msg:CHANGE_THE_USERS_PROFILE_PICTURE_LBL#
                        <? endif ?>
                        </a>
                    <? endif ?>
                    </div>
                    <div class="rn_UserDetail">
                        <h1><rn:field name="CommunityUser.DisplayName"/></h1>
                            #rn:msg:MEMBER_SINCE_LBL# <span itemprop="dateCreated"><rn:field name="CommunityUser.CreatedTime"/></span>
                    </div>
                
                <br/><br/>
                <div class="rn_PublicProfileUpdateDisplayName">
                    <? if (\RightNow\Utils\Permissions\Social::userCanEdit('DisplayName', $userIDFromURL)): ?>
                        <rn:widget path="input/DisplayNameInput"/>
                    <? endif ?>
                </div>
            </fieldset>
            <rn:condition external_login_used="false">
                <rn:widget path="input/FormSubmit" label_button="#rn:msg:SAVE_CHANGE_CMD#" on_success_url="/app/#rn:config:CP_PUBLIC_PROFILE_URL#/#rn:url_param:user#" error_location="rn_ErrorLocation"/>
            </rn:condition>
        </form>
    <? endif ?>
</div>
