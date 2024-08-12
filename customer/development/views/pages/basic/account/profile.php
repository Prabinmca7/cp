<rn:meta title="#rn:msg:ACCOUNT_SETTINGS_LBL#" template="basic.php" login_required="true" force_https="true" />

<h1>#rn:msg:ACCOUNT_SETTINGS_LBL#</h1>

<rn:widget path="input/BasicFormStatusDisplay"/>
<rn:form post_handler="postRequest/sendForm">
    <div>#rn:url_param_value:msg#</div>
    <h2>#rn:msg:ACCT_HDG#</h2>

    <rn:condition external_login_used="true">
    <rn:container read_only="true">
    </rn:condition>

    <rn:widget path="input/BasicFormInput" name="Contact.Login" required="true" check_account_exists="true" label_input="#rn:msg:USERNAME_LBL#"/>
    <rn:condition external_login_used="false">
        <rn:condition config_check="EU_CUST_PASSWD_ENABLED == true">
            <div><a href="/app/#rn:config:CP_CHANGE_PASSWORD_URL##rn:session#">#rn:msg:CHG_YOUR_PASSWORD_CMD#</a><br/><br/></div>
        </rn:condition>
    </rn:condition>
    <h2>#rn:msg:CONTACT_INFO_LBL#</h2>
    <rn:condition config_check="intl_nameorder == 1">
        <rn:widget path="input/BasicFormInput" name="Contact.Name.Last" label_input="#rn:msg:LAST_NAME_LBL#" required="true"/>
        <rn:widget path="input/BasicFormInput" name="Contact.Name.First" label_input="#rn:msg:FIRST_NAME_LBL#" required="true"/>
    <rn:condition_else/>
        <rn:widget path="input/BasicFormInput" name="Contact.Name.First" label_input="#rn:msg:FIRST_NAME_LBL#" required="true"/>
        <rn:widget path="input/BasicFormInput" name="Contact.Name.Last" label_input="#rn:msg:LAST_NAME_LBL#" required="true"/>
    </rn:condition>
    <rn:widget path="input/BasicFormInput" name="Contact.Emails.PRIMARY.Address" required="true" label_input="#rn:msg:EMAIL_ADDR_LBL#"/>
    <rn:widget path="input/BasicFormInput" name="Contact.Phones.HOME.Number" label_input="#rn:msg:HOME_PHONE_LBL#">
    <rn:widget path="input/BasicFormInput" name="Contact.Phones.OFFICE.Number" label_input="#rn:msg:OFFICE_PHONE_LBL#"/>
    <rn:widget path="input/BasicFormInput" name="Contact.Phones.MOBILE.Number" label_input="#rn:msg:MOBILE_PHONE_LBL#"/>
    <rn:widget path="input/BasicCustomAllInput" table="Contact"/>

    <rn:condition external_login_used="true">
    </rn:container>
    </rn:condition>

    <rn:condition external_login_used="false">
        <rn:widget path="input/BasicFormSubmit" label_button="#rn:msg:SAVE_CHANGE_CMD#" on_success_url="/app/account/profile"/>
    </rn:condition>
</rn:form>
