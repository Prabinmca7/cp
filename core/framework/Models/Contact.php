<?php

namespace RightNow\Models;

use RightNow\Utils\Url,
    RightNow\Utils\Framework,
    RightNow\Utils\Config,
    RightNow\Utils\Text,
    RightNow\Api,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Internal\Sql\Contact as Sql,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Libraries\Hooks,
    RightNow\ActionCapture,
    RightNow\Utils\Date as DateUtil;

require_once CORE_FILES . 'compatibility/Internal/Sql/Contact.php';

/**
 * Methods for handling the retrieval, creation, and updating of contacts.
 */
class Contact extends PrimaryObjectBase
{
    /**
     * Returns an empty contact structure. Used to be able to access contact
     * fields without having a contact ID.
     *
     * @return Connect\Contact An instance of the Connect contact object
     */
    public function getBlank()
    {
        $contact = parent::getBlank();
        ConnectUtil::setCustomFieldDefaults($contact);
        return $this->getResponseObject($contact);
    }

    /**
     * Returns a Connect Contact object for the specified contact ID.
     *
     * @param int|null $contactID The id of the contact to retrieve. If unspecified and user is logged in,
     * their record will be returned. Null will be returned if no contact could be found.
     * @return Connect\Contact An instance of the Connect contact object
     */
    public function get($contactID = null){
        if($contactID === null){
            if(Framework::isLoggedIn()){
                $contactID = $this->CI->session->getProfileData('contactID');
            }
            else{
                return $this->getResponseObject(null, null, Config::getMessage(NO_SPECIFIED_NO_LOGGED_IN_MSG));
            }
        }
        $contact = parent::get($contactID);
        if(!is_object($contact)){
            return $this->getResponseObject(null, null, $contact);
        }
        return $this->getResponseObject($contact);
    }

    /**
    * Converts the specified DateTime string value into numeric value.
    * @param string $value The value of the field
    * @param object $fieldMetaData The Connect/_metadata object for the field
    * @return int The Numeric value
    */
    public static function convertDateTimeStringToNumericValue($value, $fieldMetaData) {
        //Convert date in string format to numeric value if the field type is "DateTime".
        if($fieldMetaData->COM_type === 'DateTime'){
            if(!is_int($value)){
                $value = strtotime($value);
            }
            return $value;
        }
        return $value;
    }

    /**
     * Creates a new contact with the given form data. Form data is expected to be in the format:
     *
     *      -Keys are Field names (e.g. Contact.FirstName)
     *      -Values are objects with the following members:
     *          -value: string Value to save for the field
     *          -required: bool Whether the field is required
     *
     * @param array $formData Form fields to update the contact with.
     * @param boolean $loginContactAfterCreate Whether to log the newly-created contact in after the creation has occurred
     * @param boolean $isOpenLoginAction Whether this contact is being created via the openlogin process. Changes the source field appropriately.
     * @return Connect\Contact|null Created contact object or error messages if the contact wasn't created
     */
    public function create(array $formData, $loginContactAfterCreate = false, $isOpenLoginAction = false) {
        if(Framework::isContactCreateDisabled()) {
            return $this->getResponseObject(null, null, Config::getMessage(ERROR_REQUEST_ACTION_COMPLETED_MSG));
        }

        $contact = $this->getBlank()->result;
        $errors = $warnings = array();
        $newPassword = '';

        if($this->processOrganizationCredentials($formData) === false){
            return $this->getResponseObject(null, null, Config::getMessage(ORG_CREDENTIALS_ENTERED_VALID_MSG));
        }
        foreach ($formData as $name => $field) {
            if(!Text::beginsWith($name, 'Contact')){
                continue;
            }
            $fieldName = explode('.', $name);
            try {
                //Get the metadata about the field we're trying to set. In order to do that we have to
                //populate some of the sub-objects on the record. We don't want to touch the existing
                //record at all, so instead we'll just pass in a dummy instance.
                list(, $fieldMetaData) = ConnectUtil::getObjectField($fieldName, $this->getBlank()->result);
            }
            catch (Connect\ConnectAPIErrorBase $e) {
                $warnings []= $e->getMessage();
                continue;
            }
            // Added catch block for Exception. 
            catch (\Exception $e) {
                $errors[] = $e->getMessage();
                continue;
            }

            if (\RightNow\Utils\Validation::validate($field, $name, $fieldMetaData, $errors)) {
                $field->value = ConnectUtil::checkAndStripMask($name, $field->value, $fieldMetaData, isset($formData['Contact.Address.Country']) ? $formData['Contact.Address.Country'] : null);
                $field->value = ConnectUtil::castValue($field->value, $fieldMetaData);
                if (Text::getSubstringAfter($name, '.') === 'NewPassword') {
                    //For password, null is not the same as an empty string, like it is for everything else
                    if($field->value === null){
                        $field->value = '';
                    }
                    $newPassword = $field->value;
                }
                   //Convert date in string format to numeric value if the field type is "DateTime".
                   $field->value = DateUtil::convertDateTimeStringToNumericValue($field->value, $fieldMetaData);

                if($errorMessage = parent::setFieldValue($contact, $name, $field->value)){
                    $errors []= $errorMessage;
                }
            }
        }
        $hookData = array('data' => $contact);
        if (is_string($customHookError = Hooks::callHook('pre_contact_create', $hookData))) {
            return $this->getResponseObject(null, null, $customHookError);
        }
        if ($errors || ($errors = $this->validateUniqueFields($contact, $formData)) || ($errors = $this->validateStateAndCountry($contact))) {
            return $this->getResponseObject(null, null, $this->replaceErrorMessages($errors), $warnings);
        }

        try {
            $contact = parent::createObject($contact, $isOpenLoginAction ? SRC2_EU_OPENLOGIN : SRC2_EU_NEW_CONTACT, false);
        }
        catch (\Exception $e) {
            $contact = $this->getSaveErrors($e);
        }
        if(!is_object($contact)){
            return $this->getResponseObject(null, null, $contact);
        }

        if ($loginContactAfterCreate === true && $contact->Login !== null) {
            $profile = $this->getProfileSid($contact->Login, $newPassword ?: '', $this->CI->session->getSessionData('sessionID'))->result;
            if ($profile !== null && !is_string($profile)) {
                $this->CI->session->createProfileCookie($profile);
            }
            else {
                $warnings []= sprintf(Config::getMessage(ERROR_ATTEMPTING_LOG_CONTACT_PCT_S_LBL), $contact->Login);
            }
        }
        return $this->getResponseObject($contact, 'is_object', null, $warnings);
    }

    /**
     * Updates the specified contact with the given form data. Expected format:
     *
     *      -Keys are Field names (e.g. Contact.FirstName)
     *      -Values are objects with the following members:
     *          -value: string Value to save for the field
     *          -required: bool Whether the field is required
     *
     * @param int $contactID The ID of the contact to update
     * @param array $formData Form fields to update the contact with
     * @param boolean $isOpenLoginAction Whether this contact is being created via the openlogin process. Changes the source field appropriately.
     * @return Connect\Contact|null Updated contact record or null if the contact wasn't updated
     */
    public function update($contactID, array $formData, $isOpenLoginAction = false) {
        $contact = $this->get($contactID);
        if (!$contact->result) {
            // Error: return the ResponseObject
            return $contact;
        }
        $contact = $contact->result;

        if ($contact->Disabled && (!$isOpenLoginAction || !$formData['Contact.Disabled'] || $formData['Contact.Disabled']->value !== false)) {
            // OpenLogin is allowed to enable a contact, otherwise disabled contacts cannot be updated.
            return $this->getResponseObject(null, null, Config::getMessage(SORRY_THERES_ACCT_CREDENTIALS_INCOR_MSG));
        }

        $errors = $warnings = array();
        if($this->processOrganizationCredentials($formData) === false){
            return $this->getResponseObject(null, null, Config::getMessage(ORG_CREDENTIALS_ENTERED_VALID_MSG));
        }

        // Don't send emails to the API if they have same values
        $emailAddressAttrs = array('Email - Primary' => 'Contact.Emails.PRIMARY.Address', 'Alternate Email 1' => 'Contact.Emails.ALT1.Address', 'Alternate Email 2' => 'Contact.Emails.ALT2.Address');
        foreach($contact->Emails as $email){
            $emailArray[$emailAddressAttrs[$email->AddressType->LookupName]] = $email->Address;
        }

        foreach ($emailAddressAttrs as $emailKey => $emailVal) {
            if (isset($emailVal, $emailArray) &&
                array_key_exists($emailVal, $formData) &&
                $emailArray[$emailVal] === $formData[$emailVal]->value) {
                    unset($formData[$emailVal]);
            }
        }

        foreach ($formData as $name => $field) {
            if(!Text::beginsWith($name, 'Contact')){
                continue;
            }
            $fieldName = explode('.', $name);
            try {
                //Get the metadata about the field we're trying to set. In order to do that we have to
                //populate some of the sub-objects on the record. We don't want to touch the existing
                //record at all, so instead we'll just pass in a dummy instance.
                list(, $fieldMetaData) = ConnectUtil::getObjectField($fieldName, $this->getBlank()->result);
            }
            catch (\Exception $e) {
                $warnings []= $e->getMessage();
                continue;
            }

            if (\RightNow\Utils\Validation::validate($field, $name, $fieldMetaData, $errors)) {
                $field->value = ConnectUtil::checkAndStripMask($name, $field->value, $fieldMetaData, isset($formData['Contact.Address.Country']) ? $formData['Contact.Address.Country'] : null);
                $field->value = ConnectUtil::castValue($field->value, $fieldMetaData);

                //Hack for being unable to revert Connect object changes.
                if($name === 'Contact.Login') {
                    $hasLogin = true;
                    $existingLogin = $contact->Login;
                }
                   //Convert date in string format to numeric value if the field type is "DateTime".
                   $field->value = DateUtil::convertDateTimeStringToNumericValue($field->value, $fieldMetaData);

                if($errorMessage = parent::setFieldValue($contact, $name, $field->value)){
                    $errors []= $errorMessage;
                }
            }
        }

        if ((isset($formData['Contact.NewPassword']) && !is_null($formData['Contact.NewPassword'])) && 
            ($error = $this->verifyPasswordChange($formData['Contact.NewPassword'], $contact, $errors))) {
            $errors []= $error;
        }

        $hookData = array('data' => $contact);
        if (is_string($customHookError = Hooks::callHook('pre_contact_update', $hookData))) {
            return $this->getResponseObject(null, null, $customHookError);
        }
        if ($errors || ($errors = $this->validateUniqueFields($contact)) || ($errors = $this->validateStateAndCountry($contact))) {
            //Hack for being unable to revert Connect object changes.
            if(isset($hasLogin) && $hasLogin) {
                $contact->Login = $existingLogin;
            }

            return $this->getResponseObject(null, null, $errors, $warnings);
        }
        try {
            $contact = parent::updateObject($contact, $isOpenLoginAction ? SRC2_EU_OPENLOGIN : SRC2_EU_CONTACT_EDIT, false);
        }
        catch (\Exception $e) {
            $contact = $this->getSaveErrors($e);
        }
        if(!is_object($contact)){
            return $this->getResponseObject(null, null, $contact);
        }
        $changingPassword = isset($formData['Contact.NewPassword']) ? !is_null($formData['Contact.NewPassword']) : false;
        if($changingPassword){
            ActionCapture::record('contact', 'changePassword');
        }

        $this->updateProfile($contact, $changingPassword);
        return $this->getResponseObject($contact, 'is_object', null, $warnings);
    }

    /**
     * Gets a list of the channel usernames for the given contact login or id.
     * @param int|string $loginOrID Contact ID or username from which to populate values
     * @return array Associative array of channel types having the channel ID as key.
     */
    public function getChannelFields($loginOrID) {
        if(is_int($loginOrID)) {
            $whereClause = sprintf('ID = %d', $loginOrID);
        }
        else if(is_string($loginOrID)) {
            $whereClause = sprintf("Login = '%s'", Connect\ROQL::escapeString($loginOrID));
        }
        else {
            return $this->getResponseObject(null, null, "Invalid Login or ID. The value must be either an integer or a string.");
        }

        try {
            $query = Connect\ROQL::query("SELECT ChannelUsernames.ChannelType.ID, ChannelUsernames.UserNumber, ChannelUsernames.Username
                                                  FROM Contact
                                                  WHERE $whereClause")->next();
        }
        catch(Connect\ConnectAPIErrorBase $e){
            return $this->getResponseObject(null, null, $e->getMessage());
        }

        $channelUsernames = array();
        while($row = $query->next()) {
            $channelUsernames[$row['ID']] = array(
                'UserNumber' => $row['UserNumber'],
                'Username' => $row['Username']
            );
        }
        return $this->getResponseObject($channelUsernames, 'is_array');
    }

    /**
     * Get a list of all contact visible channel types and their associated labels and IDs.
     * @return array List of channel types keyed by ID
     */
    public function getChannelTypes() {
        try {
            $query = Connect\ROQL::queryObject(sprintf('SELECT ChannelType FROM ChannelType WHERE ContactVisibility = 1 AND ID IN (%d, %d, %d) ORDER BY DisplayOrder', CHAN_TWITTER, CHAN_YOUTUBE, CHAN_FACEBOOK))->next();
        }
        catch(Connect\ConnectAPIErrorBase $e){
            return $this->getResponseObject(null, null, $e->getMessage());
        }

        $channelTypes = array();
        while($object = $query->next()) {
            $channelTypes[$object->ID] = array(
                'LookupName' => $object->LookupName
            );
        }
        return $this->getResponseObject($channelTypes, 'is_array');
    }


    /**
     * Logs the user in given their username, password and session ID
     *
     * @param string $username The username of the contact
     * @param string $password The password of the contact
     * @param string $sessionID The session Id of the contact
     * @param int|string $widgetID The widget ID of the widget that submitted the request
     * @param string $url The url where to redirect to after login has completed
     * @return array An array containing the status of the request and other data to be interpreted by the callee
     */
    public function doLogin($username, $password, $sessionID, $widgetID, $url)
    {
        $result = array('w_id' => $widgetID);
        if((!$this->CI->session->canSetSessionCookies() || !$this->CI->session->getSessionData('cookiesEnabled')) && !Framework::checkForTemporaryLoginCookie())
        {
            //Temporary cookie does not exist, return an error
            $result['message'] = Config::getMessage(PLEASE_ENABLE_COOKIES_BROWSER_LOG_MSG);
            $result['success'] = 0;
            $result['showLink'] = false;
            $result['newFormToken'] = Framework::createTokenWithExpiration(0, Framework::doesTokenRequireChallenge($this->CI->input->post('f_tok')));
            return $this->getResponseObject($result, 'is_array');
        }
        if(Api::utf8_char_len($password) > 20){
            $result['message'] = sprintf(Config::getMessage(PASSWD_ENTERED_EXCEEDS_MAX_CHARS_MSG), 20);
            $result['success'] = 0;
            $result['newFormToken'] = Framework::createTokenWithExpiration(0, Framework::doesTokenRequireChallenge($this->CI->input->post('f_tok')));
            return $this->getResponseObject($result, 'is_array');
        }

        //We need to check if they are on just ...com, ...com/, /app, or /app/ so what when we
        //redirect we go to the home page
        if (in_array($url, array('', '/', '/app', '/app/'), true))
            $url = Url::getHomePage();

        $result['addSession'] = in_array('session', explode('/', $url), true);
        $result['sessionParm'] = Url::sessionParameter();
        $result['url'] = $url;
        $profile = $this->getProfileSid($username, $password, $sessionID)->result;

        if(is_string($profile))
        {
            // Login error triggered via a custom hook
            $result['message'] = $profile;
            $result['success'] = 0;
        }
        else if($profile)
        {
            // Login successful, create the cookie and redirect the user
            $result['message'] = Config::getMessage(REDIRECTING_ELLIPSIS_MSG);
            $result['success'] = 1;

            if (!$this->enforcePasswordInterval($profile, $result))
            {
                $this->CI->session->createProfileCookie($profile);
            }
        }
        else
        {
            // Failed to login
            $result['message'] = (Config::getConfig(CP_MAX_LOGINS) || Config::getConfig(CP_MAX_LOGINS_PER_CONTACT))
                ? Config::getMessage(USRNAME_PASSWD_ENTERED_INCOR_ACCT_MSG)
                : Config::getMessage(USERNAME_PASSWD_ENTERED_INCOR_ACCT_MSG);
            $result['success'] = 0;
            $result['newFormToken'] = Framework::createTokenWithExpiration(0, Framework::doesTokenRequireChallenge($this->CI->input->post('f_tok')));
        }
        return $this->getResponseObject($result, 'is_array');
    }

    /**
     * Logs out the currently logged in user
     *
     * @param string $currentUrl The URL that the user was on when they clicked the logout link
     * @param string $redirectUrl The URL that the user is going to after they successfully logout\
     * @return array An array that has the new session id and the url to redirect to
     */
    public function doLogout($currentUrl, $redirectUrl = null)
    {
        //Delete out the number of searches since it will be reset
        $currentUrl = Url::deleteParameter($currentUrl, 'sno');
        if(Url::sessionParameter() !== '')
            $currentUrl = Url::addParameter($currentUrl, 'session', Text::getSubstringAfter(Url::sessionParameter(), "session/"));
        //Contact isn't logged in, just spoof a success
        if(!Framework::isLoggedIn())
            return $this->getResponseObject(array('url' => $currentUrl, 'session' => Url::sessionParameter(), 'success' => 1), 'is_array');

        if(Config::getConfig(COMMUNITY_ENABLED))
        {
            $socialLogoutUrl = Config::getConfig(COMMUNITY_BASE_URL) . '/scripts/signout';
            if($redirectUrl)
            {
                //Check if redirect is fully qualified and on the same domain
                $redirectComponents = parse_url($redirectUrl);
                if($redirectComponents['host'])
                {
                    if(Url::sessionParameter() !== '' && $redirectComponents['host'] === Config::getConfig(OE_WEB_SERVER))
                        $redirectUrl = Url::addParameter($redirectUrl, 'session', Text::getSubstringAfter(Url::sessionParameter(), "session/"));
                    $socialLogoutUrl .= '?redirectUrl=' . urlencode($redirectUrl);
                }
                else
                {
                    if(Url::sessionParameter() !== '')
                        $redirectUrl = Url::addParameter($redirectUrl, 'session', Text::getSubstringAfter(Url::sessionParameter(), "session/"));
                    $socialLogoutUrl .= '?redirectUrl=' . urlencode(Url::getShortEufBaseUrl('sameAsCurrentPage', $redirectUrl));
                }
            }
        }

        $preHookData = array();
        Hooks::callHook('pre_logout', $preHookData);
        $logoutResult = Api::contact_logout(array(
            'cookie' => $this->CI->session->getProfileData('authToken'),
            'sessionid' => $this->CI->session->getSessionData('sessionID'),
        ));

        //We don't record PTA logouts via ACS, so we need to be sure the library is loaded before we record the logout action
        if(ActionCapture::isInitialized()){
            ActionCapture::record('contact', 'logout');
        }

        $postHookData = array('returnValue' => $logoutResult);
        Hooks::callHook('post_logout', $postHookData);

        $this->CI->session->performLogout();

        if(Url::sessionParameter() !== '')
            $currentUrl = Url::addParameter($currentUrl, 'session', substr(Url::sessionParameter(), 9));

        $result = array('url' => $currentUrl,
                        'session' => Url::sessionParameter(),
                        'success' => 1);
        if(isset($socialLogoutUrl))
            $result['socialLogout'] = $socialLogoutUrl;
        return $this->getResponseObject($result, 'is_array');
    }

    /**
     * Creates an instance of the CP Profile object given a user's username and password
     *
     * @param string $username The username of the contact
     * @param string $password The password of the contact: plaintext, non-encrypted
     * @param string $sessionID The current session id
     * @return \RightNow\Libraries\ProfileData Instance of the Profile object, or null if login failed
     */
    public function getProfileSid($username, $password, $sessionID)
    {
        $username = trim($username);
        $preHookData = array('data' => array('source' => 'LOCAL'));
        $customHookError = Hooks::callHook('pre_login', $preHookData);
        if(is_string($customHookError))
            return $this->getResponseObject($customHookError, 'is_string');

        if($username === false || $username === null){
            return $this->getResponseObject(null, null, "Invalid username format provided. Value must be a string but received either null or false.");
        }

        if ($abuseMessage = $this->isAbuse()) {
            return $this->getResponseObject(null, null, $abuseMessage);
        }

        $pairData = array(
            'login' => $username,
            'sessionid' => $sessionID,
            'cookie_set' => 1,
            'login_method' => CP_LOGIN_METHOD_LOCAL,
        );
        if (is_string($password) && $password !== '') {
            $pairData['password_text'] = $password;
        }

        if ($profile = Api::contact_login($pairData)) {
            // Login succeeded. Attach the Contact's associated CommunityUser onto the session profile.
            $profile = (object) $profile;
            $this->CI->session->setSocialUser($profile);
        }
        $profile = $this->CI->session->createMapping($profile);

        ActionCapture::record('contact', 'login', 'local');

        $postHookData = array('returnValue' => $profile, 'data' => array('source' => 'LOCAL'));
        Hooks::callHook('post_login', $postHookData);

        return $this->getResponseObject($profile);
    }

    /**
     * Attempts to find a contact record in the database by an email address and optionally their first and last name
     * @param string $email The email address to lookup
     * @param object $firstName The first name of the contact
     * @param object $lastName The last name of the contact
     * @return int|bool The contact ID if found or false if not found
     */
    public function lookupContactByEmail($email, $firstName = null, $lastName = null)
    {
        $contactDetails = $this->lookupContact($email, $firstName, $lastName);
        if($contactDetails === false){
            return $this->getResponseObject(false, 'is_bool');
        }
        return $this->getResponseObject($contactDetails['c_id'], 'is_int');
    }

    /**
     * Attempts to find a contact ID in the database given their login
     * @param string $login The login of the contact to retrieve
     * @return int|null The contact ID if found or false if not found
     */
    public function getIDFromLogin($login)
    {
        try{
            $query = Connect\ROQL::query(sprintf("SELECT ID FROM Contact WHERE Login='%s'", Connect\ROQL::escapeString($login)))->next();
        }
        catch(Connect\ConnectAPIErrorBase $e){
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        $row = $query->next();
        if($row){
            return $this->getResponseObject(intval($row['ID']), 'is_int');
        }
        return $this->getResponseObject(null, 'is_null');
    }

    /**
     * Finds a contact ID and org ID of a contact given their email and optionally their first and last names
     * @param string $email The email address to look up
     * @param string $firstName The first name of the contact
     * @param string $lastName The last name of the contact
     * @return array|bool False if the contact doesn't exist or an array containing the contactID and orgID found
     */
    public function lookupContactAndOrgIdByEmail($email, $firstName=null, $lastName=null)
    {
        $contactDetails = $this->lookupContact($email, $firstName, $lastName);
        if($contactDetails === false)
            return $this->getResponseObject(false, 'is_bool');
        return $this->getResponseObject(array($contactDetails['c_id'], isset($contactDetails['org_id']) ? $contactDetails['org_id'] : null), 'is_array');
    }

    /**
     * Finds a contact ID and email address of a user given their federated account user ID and provider name.
     * @param string $providerName The name of the third-party provider (Either twitter, facebook, or openid)
     * @param string $openLoginAccountID The user's id on the third-party service (the openid_identity / openid_claimed_id URL for OpenID)
     * @param string $openLoginAccountUsername The user's username on the third-party service
     * @return Connect\Contact|bool False if the contact doesn't exist or a Connect/Contact object if contact is found
     */
    public function lookupContactByOpenLoginAccount($providerName, $openLoginAccountID, $openLoginAccountUsername = '') {
        $openLoginAccountID = Connect\ROQL::escapeString($openLoginAccountID);
        if ($providerName === 'openid') {
            $query = "SELECT Contact FROM Contact C WHERE C.OpenIDAccounts.URL = '" . $openLoginAccountID . "'";
        }
        else if ($providerID = $this->getOpenLoginChannel($providerName)) {
            $optionalCriteria = ($openLoginAccountUsername) ? "OR C.ChannelUsernames.Username = '" . Connect\ROQL::escapeString($openLoginAccountUsername) . "'" : '';
            $query = "SELECT Contact FROM Contact C WHERE C.ChannelUsernames.ChannelType.ID = $providerID AND (C.ChannelUsernames.UserNumber = '$openLoginAccountID' $optionalCriteria)";
        }

        if (isset($query)) {
            try {
                $query = Connect\ROQL::queryObject($query)->next();
                if ($result = $query->next()) {
                    return $this->getResponseObject($result);
                }
            }
            catch (Connect\ConnectAPIErrorBase $e) {
                //If the query failed, we'll return false
            }
        }

        return $this->getResponseObject(false, 'is_bool');
    }

    /**
     * Returns the channels.chan_type_id for the equivalent string name of the channel type for channels used as Open Login providers.
     * @param string $providerName The name of the channel type / open login provider
     * @return int ID of the channel type or 0 if the specified provider doesn't exist
     */
    public function getOpenLoginChannel($providerName)
    {
        $result = 0;
        switch(strtolower($providerName))
        {
            case 'facebook':
                $result = CHAN_FACEBOOK;
                break;
            case 'twitter':
                $result = CHAN_TWITTER;
                break;
        }
        return $this->getResponseObject($result, 'is_int');
    }

    /**
     * Emails contact username if the contact isn't disabled and the email isn't invalid.
     *
     * @param string $email The email of the contact
     * @return array Array containing a message that specifies the result of the operation
     */
    public function sendLoginEmail($email)
    {
        $this->CI->session->setSessionData(array('previouslySeenEmail' => $email));
        $result = $this->getResponseObject(array('message' => '<b>'
            . Config::getMessage(EMAIL_CONTAINING_ACCT_INFORMATION_MSG) . '</b><p></p>'
            . Config::getMessage(DNT_RCV_MLLLY_DSBLDLLY_NTF_NTRDLL_NT_CCN_MSG)), 'is_array');

        if(!is_string($email) || !strlen($email))
            return $result;

        $email = strtolower(Connect\ROQL::escapeString($email));

        try{
            $query = Connect\ROQL::query(sprintf("SELECT ID FROM Contact WHERE Emails.Address='%s' AND Emails.AddressType=%d AND Disabled = 0", $email, CONNECT_EMAIL_PRIMARY))->next();
        }
        catch(Connect\ConnectAPIErrorBase $e){
            return $result;
        }
        if($query = $query->next()){
            Api::contact_login_recover(array(
                'c_id' => intval($query['ID']),
                'email' => $email,
            ));
            ActionCapture::record('contact', 'loginRecovery');
        }
        return $result;
    }

    /**
     * Sends a password reset email to the contact specified with $login. If the contact is disabled then an email is not sent.
     * The contact's primary email address invalid flag is turned off if it had previously been enabled.
     * Note: For security considerations a success message is always returned as the result; to view errors, inspect the errors member of the ResponseObject
     * @param string $login Contact's username
     * @return array|false An associative array with a message key that contains a message that specifies the result of the operation, or false on error.
     */
    public function sendResetPasswordEmail($login) {
        $result = $this->getResponseObject(array('message' => '<b>'
            . Config::getMessage(EMAIL_RESET_PASSWORD_MSG) . '</b><p></p>'
            . Config::getMessage(DNT_RCV_MLLLY_DSBLDLL_VLDLL_CHCK_NTF_NTR_MSG)), 'is_array');

        if (!is_string($login) || !strlen($login)) {
            $result->error = "Contact login was not properly specified";
            return $result;
        }

        try {
            $contact = Connect\ROQL::queryObject(sprintf("SELECT Contact FROM Contact C WHERE C.Login = '%s'", Connect\ROQL::escapeString($login)))->next()->next();
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            $result->error = $e->getMessage();
            return $result;
        }
        if (!$contact) {
            $result->error = "Contact with Login $login wasn't found";
            return $result;
        }

        if ($contact->Disabled)
            return $result;

        if (time() < $contact->PasswordEmailExpirationTime)
            return $result;

        if ($contact->Emails[0]->Invalid === true) {
            $contact->Emails[0]->Invalid = false;
            try {
                ConnectUtil::save($contact, SRC2_EU_CONTACT_EDIT);
            }
            catch (Connect\ConnectAPIErrorBase $e) {
                $result->error = $e->getMessage();
                return $result;
            }
        }

        if ($abuseMessage = $this->isAbuse()) {
            return $this->getResponseObject(false, 'is_bool', $abuseMessage);
        }

        Connect\ConnectAPI::setSource(SRC2_EU_CONTACT_EDIT);
        $contact->ResetPassword();
        Connect\ConnectAPI::releaseSource(SRC2_EU_CONTACT_EDIT);
        ActionCapture::record('contact', 'passwordRecovery');

        return $result;
    }

    /**
     * Checks if a contact already exists with the given login or email.
     *
     * @param string $idType The identifier used to check contact uniqueness; either 'login' or 'email'
     * @param string $idValue The actual username or email value entered
     * @param string $accountSetup If the user is creating a username via setup_password: don't direct them back to acct_assist
     * page in the error message if they chose a pre-existing username.
     * @return string|bool Error message or false if the idValue is unique.
     */
    public function contactAlreadyExists($idType, $idValue, $accountSetup = false)
    {
        $idValue = Connect\ROQL::escapeString($idValue);
        $result = '';
        if($idType === 'email')
        {
            $idValue = strtolower($idValue);
            try{
                $query = Connect\ROQL::query(sprintf("SELECT ID FROM Contact WHERE Emails.Address='%s'", $idValue))->next();
            }
            catch(Connect\ConnectAPIErrorBase $e){
                return $this->getResponseObject(null, null, $e->getMessage());
            }
            if($query->next()){
                $result = sprintf(Config::getMessage(EXING_ACCT_EMAIL_ADDR_PCT_S_PLS_MSG), $idValue);
                if(!Framework::isLoggedIn()){
                    $result .= '<br/><br/>';
                    if(Api::site_config_int_get(CFG_OPT_DUPLICATE_EMAIL))
                        $result .= sprintf(Config::getMessage(EMAIL_ADDR_YOU_OBTAIN_CREDS_MSG), '/app/' . Config::getConfig(CP_ACCOUNT_ASSIST_URL) . Url::sessionParameter(), Config::getMessage(GET_ACCOUNT_ASSISTANCE_HERE_LBL));
                    else
                        $result .= sprintf(Config::getMessage(EMAIL_ADDR_SEND_USERNAME_RESET_MSG), '/app/' . Config::getConfig(CP_ACCOUNT_ASSIST_URL) . Url::sessionParameter(), Config::getMessage(GET_ACCOUNT_ASSISTANCE_HERE_LBL));
                }
            }
        }
        else if($idType === 'login' && Connect\ROQL::escapeString($this->CI->session->getProfileData('login')) !== $idValue)
        {
            try{
                $query = Connect\ROQL::query(sprintf("SELECT ID FROM Contact WHERE Login='%s'", $idValue))->next();
            }
            catch(Connect\ConnectAPIErrorBase $e){
                return $this->getResponseObject(null, null, $e->getMessage());
            }

            if($query->next())
            {
                $result = Config::getMessage(EXISTING_ACCT_USERNAME_PLS_ENTER_MSG) . '<br/><br/>';
                if($accountSetup !== 'true' && !Framework::isLoggedIn()){
                    $result .= sprintf(Config::getMessage(UNSURE_EXING_ACCT_FORGOTTEN_MSG), '/app/' . Config::getConfig(CP_ACCOUNT_ASSIST_URL) . Url::sessionParameter(), Config::getMessage(GET_ACCOUNT_ASSISTANCE_HERE_LBL));
                }
            }
        }
        if($result === '')
            return $this->getResponseObject(false, 'is_bool');
        return $this->getResponseObject(array('message' => $result), 'is_array');
    }

    /**
    * Checks if '$currentPassword' matches the current password value for '$contactID'
    * Note: repeatedly failing to validate a contact's current password can result in disabling that contact, unless the contact is already logged in
    * @param int $contactID The id of the contact to check for
    * @param string $currentPassword The current password to verify
    * @return bool True If $currentPassword matches in the database
    */
    public function validateCurrentPassword($contactID, $currentPassword) {
        return Sql::checkOldPassword($contactID, $currentPassword);
    }

    /**
     * Gets the Contact attached to the specified CommunityUser ID.
     * @param  int $socialUserID CommunityUser id
     * @return Connect\Contact|null Contact or null if there are error messages and the contact wasn't retrieved
     */
    public function getForSocialUser($socialUserID) {
        if (!Framework::isValidID($socialUserID)) {
            return $this->getResponseObject(null, null, "Invalid ID: $socialUserID");
        }
        // get the user, then attempt to fetch the Contact (will be null if nonexistent)
        try {
            if ($socialUser = Connect\CommunityUser::fetch($socialUserID)) {
                $contact = $socialUser->Contact;
            }
        }
        catch (\Exception $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }

        return $this->getResponseObject($contact, null, ($contact) ? null : sprintf(Config::getMessage(CONTACT_NOT_FOUND_SOCIAL_USER_ID_S_LBL), $socialUserID));
    }

    /**
     * Updates the contact's profile after a contact#update has occurred.
     * @param Connect\Contact $contact The newly-saved Connect/Contact object
     * @param boolean $newPassword True if the update included setting a new password
     * @return void
     */
    protected function updateProfile(Connect\Contact $contact, $newPassword) {
        if($newPassword) {
            $newProfile = array(
                'login'         => $contact->Login,
                'sessionid'     => $this->CI->session->getSessionData('sessionID'),
                'cookie_set'    => (int) $this->CI->session->getSessionData('cookiesEnabled'),
                'login_method'  => CP_LOGIN_METHOD_LOCAL,
            );
            if($contact->NewPassword !== '') {
                $newProfile['password_text'] = $contact->NewPassword;
            }
            $newProfile = (object) Api::contact_login($newProfile);
        }
        else {
            $newProfile = (object) Api::contact_login_reverify(array(
                'login'             => $contact->Login,
                // TK: connect doesn't expose existing password hash...
                'password_hash'  => Sql::getPasswordHash($contact->ID),
                'sessionid'         => $this->CI->session->getSessionData('sessionID'),
            ));            $sessionID = $this->CI->session->getSessionData('sessionID');
        }

        if ($newProfile && $newProfile->login !== null && $newProfile->login !== '' && $newProfile->login !== false) {
            Api::contact_login_update_cookie(array(
                'login'         => $newProfile->login,
                'expire_time'   => time() + $this->CI->session->getProfileCookieLength(),
            ));
        }
        $newProfile = $this->CI->session->createMapping($newProfile, false, true);

        $hookData = array('returnValue' => $newProfile, 'data' => array('source' => 'LOCAL'));
        Hooks::callHook('post_login', $hookData);

        if (!is_null($newProfile)) {
            $this->CI->session->createProfileCookie($newProfile);
        }
    }

    /**
     * Attempts to find a contact in the DB from their email, first and last names. Mainly useful for when
     * email address sharing is enabled.
     *
     * @param string $email Contacts email address
     * @param string|null $firstName Contacts first name
     * @param string|null $lastName Contacts last name
     * @return array|bool Details about the contact including their ID and org associations or false if no contact was found
     */
    protected function lookupContact($email, $firstName = null, $lastName = null)
    {
        if($email === null || $email === false){
            return false;
        }
        $email = strtolower($email);
        $cacheKey = "existingContactEmail$email";
        $contactMatchPairData = array('email' => $email);
        if($firstName !== null)
        {
            $contactMatchPairData['first'] = $firstName;
            $cacheKey .= $firstName;
        }
        if($lastName !== null)
        {
            $contactMatchPairData['last'] = $lastName;
            $cacheKey .= $lastName;
        }
        $contact = Framework::checkCache($cacheKey);
        if($contact !== null)
            return $contact;

        if (Text::isValidEmailAddress($email))
        {
            // This API behaves erratically if it's not given
            // something that it expects will be an email address.
            $contact = Api::contact_match($contactMatchPairData);
            if(!isset($contact['c_id']) || !$contact['c_id'])
                $contact = false;
        }
        else
        {
            $contact = false;
        }

        Framework::setCache($cacheKey, $contact, true);
        return $contact;
    }

    /**
     * Validates Contact fields that must be unique (login, emails). Handles specialized logic
     * dealing with whether duplicate emails are allowed for the interface.
     * @param Connect\Contact $contact Contact to validate
     * @param array $formData Supplied if the form data is relevant (namely, in the case of a #create)
     * @return boolean|array False if no validation errors, Array of error messages otherwise
     */
    protected function validateUniqueFields(Connect\Contact $contact, array $formData = array()) {
        $errors = $this->checkUniqueFields($contact);
        if ($errors && (!isset($errors['duplicates_within_email_fields']) || !$errors['duplicates_within_email_fields'])
            && Api::site_config_int_get(CFG_OPT_DUPLICATE_EMAIL)
            && (!$formData || is_object($formData['ResetPasswordProcess']))) {
                // Contact create for an existing email only if coming through 'finish account creation' process
                // and if the login is unique.
                // Contact update only cares if there's an existing contact w/ the specified login.
                return $errors['login'] ?: false;
        }
        return $errors;
    }

    /**
     * Checks if email and login fields are unique for a contact.
     * @param Connect\Contact $contact Contact to validate
     * @return boolean|array False if no validation errors, Array of error messages otherwise
     */
    protected function checkUniqueFields(Connect\Contact $contact) {
        $emailList = $errors = array();
        $emailString = '';
        if ($emails = $contact->Emails) {
            foreach (range(0, 2) as $index) {
                if ($address = strtolower(ConnectUtil::fetchFromArray($emails, $index, 'Address') ? ConnectUtil::fetchFromArray($emails, $index, 'Address') : '')) {
                    $emailList[] = $address;
                    $emailString .= "'" . Connect\ROQL::escapeString($address) . "', ";
                }
            }
        }
        $emailString = rtrim($emailString, ', ');

        $login = Connect\ROQL::escapeString($contact->Login ? $contact->Login : '');
        if($login === '' && count($emailList) === 0){
            return false;
        }

        //Validate that all email values provided are unique
        if(count($emailList) !== count(array_unique($emailList))){
            $errors['duplicates_within_email_fields'] = Config::getMessage(EMAIL_ADDRESSES_MUST_BE_UNIQUE_MSG);
        }
        $query = '';
        $existingContactID = $contact->ID;
        if($login !== ''){
            $query = "SELECT ID FROM Contact WHERE Login = '$login'" . (($existingContactID) ? " AND ID != $existingContactID;" : ";");
        }
        if($emailString !== ''){
            $query .= "SELECT ID, Emails.Address, Emails.AddressType FROM Contact WHERE Emails.Address IN ($emailString)" . (($existingContactID) ? " AND ID != $existingContactID" : "");
        }
        try{
            $queryResult = Connect\ROQL::query($query);
        }
        catch(Connect\ConnectAPIErrorBase $e){
            return $errors ?: false;
        }
        if($login !== ''){
            $loginResult = $queryResult->next();
        }
        if($emailString !== ''){
            $emailsResult = $queryResult->next();
        }

        $accountAssistPage = '/app/' . Config::getConfig(CP_ACCOUNT_ASSIST_URL) . Url::sessionParameter();

        if((isset($loginResult) && $loginResult) && $duplicateLogin = $loginResult->next()){
            $errors['login'] = Config::getMessage(EXISTING_ACCT_USERNAME_PLS_ENTER_MSG);
            if(!Framework::isLoggedIn()){
                $errors['login'] .= '<br/>' . sprintf(Config::getMessage(EMAIL_ADDR_SEUNAME_RESET_MSG), $accountAssistPage, Config::getMessage(GET_ACCOUNT_ASSISTANCE_HERE_LBL));
            }
        }

        while($emailsResult && $duplicateEmails = $emailsResult->next()){
            $isEmailSharingEnabled = Api::site_config_int_get(CFG_OPT_DUPLICATE_EMAIL);
            $errorMessage  = sprintf(Config::getMessage(EXING_ACCT_EMAIL_ADDR_PCT_S_PLS_MSG), $duplicateEmails['Address']);
            if(!Framework::isLoggedIn()){
                $errorMessage .= '<br/>' . (($isEmailSharingEnabled)
                                 ? sprintf(Config::getMessage(EMAIL_ADDR_OBTAIN_CRDENTIALS_MSG), $accountAssistPage, Config::getMessage(GET_ACCOUNT_ASSISTANCE_HERE_LBL))
                                 : sprintf(Config::getMessage(EMAIL_ADDR_SEUNAME_RESET_MSG), $accountAssistPage, Config::getMessage(GET_ACCOUNT_ASSISTANCE_HERE_LBL)));
            }

            if(intval($duplicateEmails['AddressType']) === CONNECT_EMAIL_PRIMARY){
                $errors['email'] = $errorMessage;
            }
            else if(intval($duplicateEmails['AddressType']) === CONNECT_EMAIL_ALT1){
                $errors['email_alt1'] = $errorMessage;
            }
            else{
                $errors['email_alt2'] = $errorMessage;
            }
        }
        return count($errors) ? $errors : false;
    }

    /**
     * Ensure that the state and country on the given Contact are a valid combination
     * @param Connect\Contact $contact Contact to validate
     * @return string|null An error message or null if the values are valid
     */
    protected function validateStateAndCountry(Connect\Contact $contact) {
        if($contact->Address && $contact->Address->Country) {
            $country = $contact->Address->Country->ID;
        }

        if($contact->Address && $contact->Address->StateOrProvince) {
            $state = $contact->Address->StateOrProvince->ID;
        }

        if(isset($state) && isset($country) && !$this->CI->model('Country')->validateStateAndCountry($state, $country)) {
            return Config::getMessage(COUNTRY_PROVINCE_VALID_COMBINATION_MSG);
        }
    }

    /**
     * Enforces the various password interval configurations placed on the contact's password.
     * @param \RightNow\Libraries\ProfileData|object $profile Profile object
     * @param array &$loginResults Pass-by-reference Array containing various login results; may be populated with a `forceRedirect` entry
     * @return boolean True if any enforcing happened - meaning the caller shouldn't create a profile cookie; False if this was a no-op -
     * meaning the caller should create a profile cookie as normal
     */
    protected function enforcePasswordInterval($profile, array &$loginResults) {
        $contact = $this->get($profile->contactID)->result;
        if ($contact->PasswordExpirationTime) {
            $configs = \RightNow\Utils\Validation::getPasswordRequirements($contact::getMetadata()->NewPassword, 'expiration');
            $now = time();
            if ($contact->PasswordExpirationTime < $now) {
                // Password has expired
                $this->CI->session->createProfileCookieWithExpiration($profile, 120 /* 2 minutes */);
                $message = Config::getMessage(PASSWORD_EXPIRED_PLEASE_SET_MSG);
            }
            else if ($configs['warningPeriod'] && $now > strtotime("-{$configs['warningPeriod']} days", $contact->PasswordExpirationTime)) {
                // Hasn't expired yet and there's a warning period
                $this->CI->session->createProfileCookie($profile);
                $message = Config::getMessage(PASSWORD_EXPIRE_PLEASE_SET_MSG);
            }

            if ($message) {
                $loginResults['forceRedirect'] = '/app/' . Config::getConfig(CP_CHANGE_PASSWORD_URL) . "/msg/$message";
                return true;
            }
        }
        return false;
    }

    /**
     * Extracts the organization login and password from the form data submitted and attempts to find the ID of the
     * organization. If successful, it will modify the form data to set the ID value correctly so that the form processing
     * that follows associates the contact with the organization.
     * @param array &$formData List of fields submitted with form
     * @return int|bool ID of organization matched with credentials, false if credentials are invalid, and null if no organization login was provided
     */
    protected function processOrganizationCredentials(array &$formData){
        $organizationID = null;

        //If a organization login was submitted, lookup the ID of that organization and set it
        //as the value. Even if no org password was submitted, send in an empty string for validation
        $login = isset($formData['Contact.Organization.Login']) ? $formData['Contact.Organization.Login'] : null;
        if($login && $login->value){
            if($organizationID = Sql::getOrganizationIDFromCredentials($login->value, $formData['Contact.Organization.NewPassword'] ? $formData['Contact.Organization.NewPassword']->value : '')){
                $formData['Contact.Organization'] = (object)array('value' => $organizationID, 'required' => false);
            }
            else{
                $organizationID = false;
            }
        }

        //If the organization login is not provided and a login is required, don't unset the value. Let validation catch the error.
        if($login && ($login->value || !$login->required)) {
            unset($formData['Contact.Organization.Login']);
        }
        unset($formData['Contact.Organization.NewPassword']);
        return $organizationID;
    }

    /**
     * Checks the `Contact.NewPassword` field for a password change.
     * Verifies that the `currentValue` property doesn't exceed the
     * max allowable length and that its hashed value matches the
     * hashed value saved in the database.
     * @param object $passwordField Contact.NewPassword field object
     * @param Connect\Contact $contact Contact whose password is being examined
     * @return null|string error message if verification fails
     */
    protected function verifyPasswordChange($passwordField, Connect\Contact $contact) {
        if ($passwordField && (isset($passwordField->currentValue) || isset($passwordField->requireCurrent))) {
            $metaData = $contact::getMetadata();
            $passwordConstraints = $metaData->NewPassword->constraints;

            foreach ($passwordConstraints as $constraint) {
                if ($constraint->kind == Connect\Constraint::MaxLength) {
                    $maxPasswordLength = $constraint->value;
                    break;
                }
            }

            if ($maxPasswordLength && ($error = \RightNow\Utils\Validation::maxLength($passwordField->currentValue, $maxPasswordLength, Config::getMessage(CURRENT_PASSWORD_LBL)))) {
                return $error;
            }

            if (!$this->validateCurrentPassword($contact->ID, $passwordField->currentValue)) {
                return ($this->CI->session->getProfileData('openLoginUsed') && Sql::getPasswordHash($contact->ID) === null)
                    ? Config::getMessage(PASSWORD_SET_LOGOUT_RESET_LOGGED_MSG)
                    : Config::getMessage(PASSWD_DOESNT_MATCH_PLS_RE_TYPE_MSG);
            }
        }
        else if (Framework::isLoggedIn() && !isset($passwordField->currentValue)) {
            return Config::getMessage(PASSWD_DOESNT_MATCH_PLS_RE_TYPE_MSG);
        }
    }

    /**
     * Updates error messages, which have presumably been generated server side, with error messages
     * matching the default equivalent client-side validation message.
     * @param array $errors An array containing error messages (strings) to potentially be updated
     * @return array Array of error message strings
     */
    protected function replaceErrorMessages(array $errors) {
        foreach ($errors as $index => $error) {
            // For now, only update messages given for emails marked as invalid server side, giving a more human-readable error
            if (Text::stringContains($error, "Pattern does not match")
                && Text::stringContains($error, "does not match pattern")
                && preg_match("/\bContact.Emails\[\d+\].Address\b/", $error)) {
                $errors[$index] = sprintf(Config::getMessage(PCT_S_IS_INVALID_MSG), Config::getMessage(EMAIL_ADDR_LBL));
            }
        }
        return $errors;
    }
}
