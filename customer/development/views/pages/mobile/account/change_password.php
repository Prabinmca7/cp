<rn:meta title="#rn:msg:CHANGE_YOUR_PASSWORD_CMD#" template="mobile.php" login_required="true" force_https="true"/>

<div class="rn_Hero">
    <div class="rn_Container">
        <h1>#rn:msg:CHANGE_YOUR_PASSWORD_CMD#</h1>
    </div>
</div>

<div class="rn_PageContent rn_Account rn_Container">
    <div class="rn_Required rn_Message">#rn:url_param_value:msg#</div>
    <div id="rn_ErrorLocation"></div>
    <form id="rn_ChangePassword" onsubmit="return false;">
        <rn:widget path="input/PasswordInput" name="Contact.NewPassword" require_validation="true" require_current_password="true" label_input="#rn:msg:PASSWORD_LBL#" label_validation="#rn:msg:VERIFY_PASSWD_LBL#" initial_focus="true"/>
        <rn:widget path="input/FormSubmit" on_success_url="#rn:php:'/app/account/profile/msg/' . urlencode(\RightNow\Utils\Config::getMessage(YOUR_PASSWORD_HAS_BEEN_CHANGED_MSG))#" error_location="rn_ErrorLocation" />
    </form>
</div>
