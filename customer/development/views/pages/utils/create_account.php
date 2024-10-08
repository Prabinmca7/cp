<rn:meta title="#rn:msg:CREATE_NEW_ACCT_HDG#" template="standard.php" login_required="false" redirect_if_logged_in="account/overview" force_https="true" />
<div class="rn_Hero">
    <div class="rn_Container">
        <h1>#rn:msg:CREATE_AN_ACCOUNT_CMD#</h1>
    </div>
</div>

<div class="rn_PageContent rn_CreateAccount rn_Container">
    <div class="rn_ThirdPartyLogin">
        <h2 class="rn_ServicesMessage">#rn:msg:SERVICES_MSG#</h2>
        <p class="rn_LoginUsingMessage">#rn:msg:LOG_IN_OR_REGISTER_USING_ELLIPSIS_MSG#</p>

        <div class="rn_OpenLogins">
            <rn:widget path="login/OpenLogin"/>
            <rn:widget path="login/OpenLogin" controller_endpoint="/ci/openlogin/oauth/authorize/twitter" label_service_button="Twitter" label_process_explanation="#rn:msg:CLICK_BTN_TWITTER_LOG_TWITTER_MSG#" label_login_button="#rn:msg:LOG_IN_USING_TWITTER_LBL#"/>
            <rn:widget path="login/OpenLogin" controller_endpoint="/ci/openlogin/openid/authorize/google" label_service_button="Google" label_process_explanation="#rn:msg:CLICK_BTN_GOOGLE_LOG_GOOGLE_VERIFY_MSG#" label_login_button="#rn:msg:LOG_IN_USING_GOOGLE_LBL#"/>
            <rn:widget path="login/OpenLogin" controller_endpoint="/ci/openlogin/openid/authorize/yahoo" label_service_button="Yahoo" label_process_explanation="#rn:msg:CLICK_BTN_YAHOO_LOG_YAHOO_VERIFY_MSG#" label_login_button="#rn:msg:LOG_IN_USING_YAHOO_LBL#"/>
        </div>
    </div>
    <h2 class="rn_CreateAccountMessage">#rn:msg:CONTINUE_CREATING_ACCOUNT_ELLIPSIS_CMD#</h2>
    <form id="rn_CreateAccount" onsubmit="return false;">
        <div id="rn_ErrorLocation"></div>
        <rn:widget path="input/FormInput" name="Contact.Emails.PRIMARY.Address" required="true" validate_on_blur="false" initial_focus="true" label_input="#rn:msg:EMAIL_ADDR_LBL#"/>
        <rn:widget path="input/FormInput" name="Contact.Login" required="true" validate_on_blur="false" label_input="#rn:msg:USERNAME_LBL#" hint="#rn:msg:TH_PRIVATE_S_LOG_IN_SITE_JUST_WANT_MSG#"/>
        <rn:widget path="input/DisplayNameInput" label_input="#rn:msg:DISPLAY_NAME_LBL#" hint="#rn:msg:TH_PUBLIC_THATS_DISP_ALONG_COMMENTS_MSG#"/>
        <rn:condition site_config_check="CP.EmailConfirmationLoop.Enable == 0">
            <rn:condition config_check="EU_CUST_PASSWD_ENABLED == true" >
                <rn:widget path="input/FormInput" name="Contact.NewPassword" require_validation="true" label_input="#rn:msg:PASSWORD_LBL#" label_validation="#rn:msg:VERIFY_PASSWD_LBL#"/>
            </rn:condition>
        </rn:condition>
        <rn:condition config_check="intl_nameorder == 1">
            <rn:widget path="input/FormInput" name="Contact.Name.Last" label_input="#rn:msg:LAST_NAME_LBL#" required="true"/>
            <rn:widget path="input/FormInput" name="Contact.Name.First" label_input="#rn:msg:FIRST_NAME_LBL#" required="true"/>
        <rn:condition_else/>
            <rn:widget path="input/FormInput" name="Contact.Name.First" label_input="#rn:msg:FIRST_NAME_LBL#" required="true"/>
            <rn:widget path="input/FormInput" name="Contact.Name.Last" label_input="#rn:msg:LAST_NAME_LBL#" required="true"/>
        </rn:condition>
        <rn:widget path="input/CustomAllInput" table="Contact"/>
        <rn:widget path="input/FormSubmit" label_button="#rn:msg:CREATE_ACCT_CMD#" on_success_url="/app/account/overview" error_location="rn_ErrorLocation"/>
    </form>
</div>
