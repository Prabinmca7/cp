<rn:meta title="#rn:msg:ACCOUNT_SETTINGS_LBL#" template="okcs_mobile.php" login_required="true" force_https="true" />

<div class="rn_Hero">
    <div class="rn_Container">
        <h1>#rn:msg:ACCOUNT_SETTINGS_LBL#</h1>
    </div>
</div>

<div class="rn_PageContent rn_Profile rn_Container">
    <rn:condition flashdata_value_for="info">
        <div class="rn_MessageBox rn_InfoMessage">
            #rn:flashdata:info#
        </div>
    </rn:condition>

    <rn:condition url_parameter_check="msg != null">
        <div class="rn_MessageBox rn_InfoMessage">#rn:url_param_value:msg#</div>
    </rn:condition>

    <form id="rn_CreateAccount" onsubmit="return false;">
        <div id="rn_ErrorLocation"></div>
        <h2>#rn:msg:ACCT_HDG#</h2>

        <rn:condition external_login_used="true">
        <rn:container read_only="true">
        </rn:condition>

        <fieldset>
            <legend>#rn:msg:ACCT_HDG#</legend>
            <rn:widget path="input/FormInput" name="Contact.Emails.PRIMARY.Address" required="true" validate_on_blur="true" initial_focus="true" label_input="#rn:msg:EMAIL_ADDR_LBL#"/>
            <rn:widget path="input/FormInput" name="Contact.Login" required="true" validate_on_blur="true" label_input="#rn:msg:USERNAME_LBL#" hint="#rn:msg:TH_PRIVATE_S_LOG_IN_SITE_JUST_WANT_MSG#"/>
        <rn:condition external_login_used="false">
            <rn:condition config_check="EU_CUST_PASSWD_ENABLED == true">
                <a href="/app/#rn:config:CP_CHANGE_PASSWORD_URL##rn:session#">#rn:msg:CHANGE_YOUR_PASSWORD_CMD#</a>
            </rn:condition>
        </rn:condition>
        </fieldset>

        <h2>#rn:msg:CONTACT_INFO_LBL#</h2>
        <fieldset>
            <legend>#rn:msg:CONTACT_INFO_LBL#</legend>
        <rn:condition config_check="intl_nameorder == 1">
            <rn:widget path="input/FormInput" name="Contact.Name.Last" label_input="#rn:msg:LAST_NAME_LBL#" required="true"/>
            <rn:widget path="input/FormInput" name="Contact.Name.First" label_input="#rn:msg:FIRST_NAME_LBL#" required="true"/>
        <rn:condition_else/>
            <rn:widget path="input/FormInput" name="Contact.Name.First" label_input="#rn:msg:FIRST_NAME_LBL#" required="true"/>
            <rn:widget path="input/FormInput" name="Contact.Name.Last" label_input="#rn:msg:LAST_NAME_LBL#" required="true"/>
        </rn:condition>
        <rn:condition language_in="ja-JP,ko-KR,zh-CN,zh-HK,zh-TW">
            <rn:widget path="input/FormInput" name="Contact.Address.PostalCode" label_input="#rn:msg:POSTAL_CODE_LBL#" />
            <rn:widget path="input/FormInput" name="Contact.Address.Country" label_input="#rn:msg:COUNTRY_LBL#"/>
            <rn:widget path="input/FormInput" name="Contact.Address.StateOrProvince" label_input="#rn:msg:STATE_PROV_LBL#"/>
            <rn:widget path="input/FormInput" name="Contact.Address.City" label_input="#rn:msg:CITY_LBL#"/>
            <rn:widget path="input/FormInput" name="Contact.Address.Street" label_input="#rn:msg:STREET_LBL#"/>
        <rn:condition_else />
            <rn:widget path="input/FormInput" name="Contact.Address.Street" label_input="#rn:msg:STREET_LBL#"/>
            <rn:widget path="input/FormInput" name="Contact.Address.City" label_input="#rn:msg:CITY_LBL#"/>
            <rn:widget path="input/FormInput" name="Contact.Address.Country" label_input="#rn:msg:COUNTRY_LBL#"/>
            <rn:widget path="input/FormInput" name="Contact.Address.StateOrProvince" label_input="#rn:msg:STATE_PROV_LBL#"/>
            <rn:widget path="input/FormInput" name="Contact.Address.PostalCode" label_input="#rn:msg:POSTAL_CODE_LBL#" />
        </rn:condition>
            <rn:widget path="input/FormInput" name="Contact.Phones.HOME.Number" label_input="#rn:msg:HOME_PHONE_LBL#"/>
        </fieldset>

        <rn:condition external_login_used="true">
        </rn:container>
        </rn:condition>

        <rn:condition external_login_used="false" is_social_user="false" is_active_social_user="true">
            <rn:widget path="input/FormSubmit" label_button="#rn:msg:SAVE_CHANGE_CMD#" on_success_url="none" label_on_success_banner="#rn:msg:PROFILE_UPDATED_SUCCESSFULLY_LBL#" error_location="rn_ErrorLocation"/>
        </rn:condition>
    </form>
</div>
