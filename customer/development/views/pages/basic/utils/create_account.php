<rn:meta title="#rn:msg:CREATE_NEW_ACCT_HDG#" template="basic.php" login_required="false" redirect_if_logged_in="account/overview" force_https="true"/>

<h1>#rn:msg:CREATE_AN_ACCOUNT_CMD#</h1>
<rn:widget path="input/BasicFormStatusDisplay"/>
<rn:form post_handler="postRequest/sendForm">
    <rn:widget path="input/BasicFormInput" name="Contact.Emails.PRIMARY.Address" required="true" label_input="#rn:msg:EMAIL_ADDR_LBL#"/>
    <rn:widget path="input/BasicFormInput" name="Contact.Login" required="true" label_input="#rn:msg:USERNAME_LBL#"/>
    <rn:condition config_check="EU_CUST_PASSWD_ENABLED == true">
        <rn:widget path="input/BasicFormInput" name="Contact.NewPassword" require_validation="true" label_input="#rn:msg:PASSWORD_LBL#" label_validation="#rn:msg:VERIFY_PASSWD_LBL#"/>
    </rn:condition>
    <rn:condition config_check="intl_nameorder == 1">
        <rn:widget path="input/BasicFormInput" name="Contact.Name.Last" label_input="#rn:msg:LAST_NAME_LBL#" required="true"/>
        <rn:widget path="input/BasicFormInput" name="Contact.Name.First" label_input="#rn:msg:FIRST_NAME_LBL#" required="true"/>
    <rn:condition_else/>
        <rn:widget path="input/BasicFormInput" name="Contact.Name.First" label_input="#rn:msg:FIRST_NAME_LBL#" required="true"/>
        <rn:widget path="input/BasicFormInput" name="Contact.Name.Last" label_input="#rn:msg:LAST_NAME_LBL#" required="true"/>
    </rn:condition>
    <rn:widget path="input/BasicCustomAllInput" table="Contact"/>
    <rn:widget path="input/BasicFormSubmit" label_button="#rn:msg:CREATE_ACCT_CMD#" on_success_url="/app/account/overview"/>
</rn:form>
