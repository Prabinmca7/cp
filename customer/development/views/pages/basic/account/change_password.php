<rn:meta title="#rn:msg:CHANGE_YOUR_PASSWORD_CMD#" template="basic.php" login_required="true" force_https="true"/>

<h1>#rn:msg:CHANGE_YOUR_PASSWORD_CMD#</h1>
#rn:url_param_value:msg#
<rn:widget path="input/BasicFormStatusDisplay"/>
<rn:form post_handler="postRequest/sendForm">
    <rn:widget path="input/BasicFormInput" name="Contact.NewPassword" require_validation="true" require_current_password="true" label_input="#rn:msg:PASSWORD_LBL#" label_validation="#rn:msg:VERIFY_PASSWD_LBL#"/>
    <rn:widget path="input/BasicFormSubmit" on_success_url="/app/utils/submit/password_changed"/>
</rn:form>
