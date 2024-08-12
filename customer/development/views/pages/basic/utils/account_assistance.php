<rn:meta title="#rn:msg:ACCOUNT_ASSISTANCE_LBL#" template="basic.php" login_required="false" redirect_if_logged_in="account/overview"/>

<h1>#rn:msg:ACCOUNT_ASSISTANCE_LBL#</h1>
<div>
<rn:widget path="input/BasicFormStatusDisplay"/>
<rn:widget path="login/BasicEmailCredentials" credential_type="username" label_heading="#rn:msg:REQUEST_YOUR_USERNAME_LBL#" label_description="#rn:msg:EMAIL_ADDR_ENTER_SYS_WE_LL_SEND_MSG#" label_button="#rn:msg:EMAIL_MY_USERNAME_LBL#" label_input="#rn:msg:EMAIL_ADDR_LBL#"/>
<br/><br/>
<rn:widget path="login/BasicEmailCredentials"/>
</div>
