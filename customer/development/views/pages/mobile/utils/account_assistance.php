<rn:meta title="#rn:msg:ACCOUNT_ASSISTANCE_LBL#" template="mobile.php" login_required="false" redirect_if_logged_in="account/questions/list"/>

<div class="rn_Hero">
    <div class="rn_Container">
        <h1>#rn:msg:ACCOUNT_ASSISTANCE_LBL#</h1>
    </div>
</div>

<div class="rn_PageContent rn_Account rn_Container">
    <rn:widget path="login/EmailCredentials" credential_type="username" label_heading="#rn:msg:REQUEST_YOUR_USERNAME_LBL#" label_description="#rn:msg:EMAIL_ADDR_ENTER_SYS_SEND_USERNAME_MSG#" label_button="#rn:msg:EMAIL_MY_USERNAME_LBL#" label_input="#rn:msg:EMAIL_ADDR_LBL#" initial_focus="true"/>
    <rn:widget path="login/EmailCredentials"/>
</div>
