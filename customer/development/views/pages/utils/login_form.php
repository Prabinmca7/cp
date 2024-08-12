<rn:meta title="#rn:msg:SUPPORT_LOGIN_HDG#" template="standard.php" login_required="false" redirect_if_logged_in="account/overview" force_https="true" />

<div class="rn_Hero">
    <div class="rn_Container">
        <h1>#rn:msg:LOG_IN_UC_LBL#</h1>
    </div>
</div>

<div class="rn_PageContent rn_LoginForm rn_Container">
    <div class="rn_ThirdPartyLogin">
        <h2>#rn:msg:SERVICES_MSG#</h2>
        <p class="rn_LoginUsingMessage">#rn:msg:LOG_IN_OR_REGISTER_USING_ELLIPSIS_MSG#</p>

        <div class="rn_OpenLogins">
            <rn:widget path="login/OpenLogin"/> <? /* Attributes Default to Facebook */ ?>
            <rn:widget path="login/OpenLogin" controller_endpoint="/ci/openlogin/oauth/authorize/twitter" label_service_button="Twitter" label_process_explanation="#rn:msg:CLICK_BTN_TWITTER_LOG_TWITTER_MSG#" label_login_button="#rn:msg:LOG_IN_USING_TWITTER_LBL#"/>
            <rn:widget path="login/OpenLogin" controller_endpoint="/ci/openlogin/openid/authorize/google" label_service_button="Google" label_process_explanation="#rn:msg:CLICK_BTN_GOOGLE_LOG_GOOGLE_VERIFY_MSG#" label_login_button="#rn:msg:LOG_IN_USING_GOOGLE_LBL#"/>
            <rn:widget path="login/OpenLogin" controller_endpoint="/ci/openlogin/openid/authorize/yahoo" label_service_button="Yahoo" label_process_explanation="#rn:msg:CLICK_BTN_YAHOO_LOG_YAHOO_VERIFY_MSG#" label_login_button="#rn:msg:LOG_IN_USING_YAHOO_LBL#"/>
        </div>
    </div>
    <div class="rn_StandardLogin">
        <h2>#rn:msg:LOG_IN_WITH_AN_EXISTING_ACCOUNT_LBL#</h2><br/>
        <rn:widget path="login/LoginForm" redirect_url="/app/account/overview" initial_focus="true"/><br/>

        <p><a href="/app/#rn:config:CP_ACCOUNT_ASSIST_URL##rn:session#">#rn:msg:FORGOT_YOUR_USERNAME_OR_PASSWORD_MSG#</a></p>

        <p>#rn:msg:NOT_REGISTERED_YET_MSG# <a href="/app/utils/create_account/redirect/<?= !empty(\RightNow\Utils\Url::getParameter('redirect')) ? urlencode(\RightNow\Utils\Url::getParameter('redirect')) : ''; ?>#rn:session#">#rn:msg:SIGN_UP_LBL#</a></p>
    </div>
</div>
