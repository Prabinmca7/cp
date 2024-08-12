<?php
namespace RightNow\Api\Models;

use RightNow\Api,
    RightNow\Internal\Api\Response,
    RightNow\Internal\Utils\Version as Version,
    RightNow\Libraries\Hooks,
    RightNow\Utils\Config,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Utils\Framework,
    RightNow\Utils\Text,
    RightNow\Utils\Url;

require_once CORE_FILES . 'compatibility/Internal/Api/Models/Base.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Response.php';

class Contact extends Base {

    /**
     * Retrieves a contact with the given ID
     * @param int|string $objectID ID of the contact to retrieve
     * @return string|Connect\RNObject Error message or object instance
     */
    public function get($objectID){
        static $objectCache = array();
        $cacheKey = "Contact-{$objectID}";

        if($cachedObject = $objectCache[$cacheKey]){
            return $cachedObject;
        }
        try {
            $connectObject = call_user_func(CONNECT_NAMESPACE_PREFIX . '\\' . 'Contact' . '::fetch', $objectID);
        }
        catch (\RightNow\Connect\v1_3\ConnectAPIErrorBase $e) {
            return $e->getMessage();
        }
        catch (\RightNow\Connect\v1_4\ConnectAPIErrorBase $e) {
            return $e->getMessage();
        }
        if(Version::compareVersionNumbers(Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.6") > 0) {
            $connectObject = \RightNow\Libraries\Formatter::formatSafeObject($connectObject);
        }
        $objectCache[$cacheKey] = $connectObject;        
        return $connectObject;
    }

    /**
     * Returns an empty contact structure.
     * @return Connect\Contact An instance of the Connect contact object
     */
    public function getBlank() {
        $namespacedClass = $this->connectNamespace . '\\' . 'Contact';
        $contact = new $namespacedClass();
        $this->setCustomFieldDefaults($contact);
        return Response::getResponseObject($contact);
    }

    /**
     * Creates a new contact with the given form data. Form data is expected to be in the format:
     *
     *      -Keys are Field names (e.g. Contact.FirstName)
     *      -Values are objects with the following members:
     *          -value: string Value to save for the field
     *          -required: bool Whether the field is required
     *
     * @param array $formData Form fields to update the contact
     * @param boolean $loginContactAfterCreate Whether to log the newly-created contact in after the creation has occurred
     * @param boolean $isOpenLoginAction Whether this contact is being created via the openlogin process. Changes the source field appropriately.
     * @return Connect\Contact|null Created contact object or error messages if the contact wasn't created
     */
    public function create(array $formData, $loginContactAfterCreate = false, $isOpenLoginAction = false) {
        $loginContactAfterCreate = !!$loginContactAfterCreate;
        $contact = $this->getBlank()->result;
        $errors = $warnings = array();
        $newPassword = '';

        /* 
        Commenting this code as currently OIT API will not pass organisation details
        if($this->processOrganizationCredentials($formData) === false){
            return $this->getResponseObject(null, null, Config::getMessage(ORG_CREDENTIALS_ENTERED_VALID_MSG));
        }
        */
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
            catch (\RightNow\Connect\v1_3\ConnectAPIErrorBase $e) {
                $warnings []= $e->getMessage();
                continue;
            }
            catch (\RightNow\Connect\v1_4\ConnectAPIErrorBase $e) {
                $warnings []= $e->getMessage();
                continue;
            }
            // Added catch block for Exception. 
            catch (\Exception $e) {
                $errors[] = $e->getMessage();
                continue;
            }

            if (\RightNow\Utils\Validation::validate($field, $name, $fieldMetaData, $errors)) {
                $field->value = ConnectUtil::checkAndStripMask($name, $field->value, $fieldMetaData, $formData['Contact.Address.Country']);
                $field->value = ConnectUtil::castValue($field->value, $fieldMetaData);
                if (Text::getSubstringAfter($name, '.') === 'NewPassword') {
                    //For password, null is not the same as an empty string, like it is for everything else
                    if($field->value === null){
                        $field->value = '';
                    }
                    $newPassword = $field->value;
                }
                if($errorMessage = parent::setFieldValue($contact, $name, $field->value)){
                    $errors []= $errorMessage;
                }
            }
        }

        /*
        Commenting this code for now, hooks are currently not supported by OIT APIs
        $hookData = array('data' => $contact);
        if (is_string($customHookError = Hooks::callHook('pre_contact_create', $hookData))) {
            return $this->getResponseObject(null, null, $customHookError);
        }
        */

        if ($errors || ($errors = $this->validateUniqueFields($contact, $formData)) || ($errors = $this->prepareAndValidateStateAndCountry($contact))) {
            return Response::getErrorResponseObject('Data validation failed.', Response::HTTP_BAD_REQUEST);
        }
        try {
            $contact = parent::createObject($contact, $isOpenLoginAction ? SRC2_EU_OPENLOGIN : SRC2_EU_NEW_CONTACT);
        }
        catch (\Exception $e) {
            return Response::getErrorResponseObject("An unexpected error has occurred.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        if(!is_object($contact)){
            return Response::getErrorResponseObject("An unexpected error has occurred.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        /*
        Commenting this code as currently OIT API will not login
        if ($loginContactAfterCreate === true && $contact->Login !== null) {
            $profile = $this->getProfileSid($contact->Login, $newPassword ?: '', $this->CI->session->getSessionData('sessionID'))->result;
            if ($profile !== null && !is_string($profile)) {
                $this->CI->session->createProfileCookie($profile);
            }
            else {
                $warnings []= sprintf(Config::getMessage(ERROR_ATTEMPTING_LOG_CONTACT_PCT_S_LBL), $contact->Login);
            }
        }
        */
        return Response::getResponseObject($contact, 'is_object');
    }

    /**
     * Attempts to find a contact using the email, first and last name. Mainly useful when
     * email address sharing is enabled.
     *
     * @param string $email Contact email address
     * @param string|null $firstName Contact first name
     * @param string|null $lastName Contact last name
     * @return array|bool Details about the contact including their ID and org associations or false if no contact was found
     */
    protected function lookupContact($email, $firstName = null, $lastName = null) {
        if($email === null || $email === false) {
            return false;
        }
        $email = strtolower($email);
        $cacheKey = "existingContactEmail$email";
        $contactMatchPairData = array('email' => $email);
        if($firstName !== null) {
            $contactMatchPairData['first'] = $firstName;
            $cacheKey .= $firstName;
        }
        if($lastName !== null) {
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
            if(!$contact['c_id'])
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
     * Attempts to find a contact using email address and optionally using first and last name
     * @param string $email The email address to lookup
     * @param object $firstName The first name of the contact
     * @param object $lastName The last name of the contact
     * @return int|bool The contact ID if found or false if not found
     */
    public function lookupContactByEmail($email, $firstName = null, $lastName = null) {
        $contactDetails = $this->lookupContact($email, $firstName, $lastName);
        if($contactDetails === false){
            return Response::getResponseObject(false, 'is_bool');
        }
        return Response::getResponseObject($contactDetails['c_id'], 'is_int');
    }

    /**
     * Checks if email and login fields are unique for a contact.
     * @param Connect\Contact $contact Contact to validate
     * @return boolean|array False if no validation errors, Array of error messages otherwise
     */
    protected function checkUniqueFields($contact) {
        $emailList = $errors = array();
        $emailString = '';
        if ($emails = $contact->Emails) {
            foreach (range(0, 2) as $index) {
                if ($address = strtolower(ConnectUtil::fetchFromArray($emails, $index, 'Address'))) {
                    $emailList[] = $address;
                    if($this->connectVersion == 1.4) {
                        $emailString .= "'" . \RightNow\Connect\v1_4\ROQL::escapeString($address) . "', ";
                    }
                    else {
                        $emailString .= "'" . \RightNow\Connect\v1_3\ROQL::escapeString($address) . "', ";
                    }
                }
            }
        }
        $emailString = rtrim($emailString, ', ');

        $login = ($this->connectVersion == 1.4) ? \RightNow\Connect\v1_4\ROQL::escapeString($contact->Login) : \RightNow\Connect\v1_3\ROQL::escapeString($contact->Login);

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
            if($this->connectVersion == 1.4) {
                $queryResult = \RightNow\Connect\v1_4\ROQL::query($query);
            }
            else {
                $queryResult = \RightNow\Connect\v1_3\ROQL::query($query);
            }
        }
        catch (\RightNow\Connect\v1_3\ConnectAPIErrorBase $e) {
            return $errors ?: false;
        }
        catch (\RightNow\Connect\v1_4\ConnectAPIErrorBase $e) {
            return $errors ?: false;
        }
        if($login !== ''){
            $loginResult = $queryResult->next();
        }
        if($emailString !== ''){
            $emailsResult = $queryResult->next();
        }

        $accountAssistPage = '/app/' . Config::getConfig(CP_ACCOUNT_ASSIST_URL) . Url::sessionParameter();

        if($loginResult && $duplicateLogin = $loginResult->next()){
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
    protected function prepareAndValidateStateAndCountry($contact) {
        if($contact->Address && $contact->Address->Country) {
            $country = $contact->Address->Country->ID;
        }

        if($contact->Address && $contact->Address->StateOrProvince) {
            $state = $contact->Address->StateOrProvince->ID;
        }

        if($state && $country && !$this->validateStateAndCountry($state, $country)) {
            return Config::getMessage(COUNTRY_PROVINCE_VALID_COMBINATION_MSG);
        }
    }

    /**
     * Check if the given state and country pair is valid.
     * @param int $stateID The state ID
     * @param int $countryID The country ID
     * @return boolean True if the pair is valid, false otherwise
     */
    public function validateStateAndCountry($stateID, $countryID) {
        if(!Framework::isValidID($countryID)){
            return Response::getErrorResponseObject("Invalid Country ID: $countryID");
        }

        if($this->connectVersion == 1.4) {
            $stateProvinceList = \RightNow\Connect\v1_4\Country::fetch($countryID);
        }
        else {
            $stateProvinceList = \RightNow\Connect\v1_3\Country::fetch($countryID);
        }

        if($stateProvinceList) {
            foreach($stateProvinceList->Provinces as $stateOrProvince) {
                if($stateOrProvince->ID === intval($stateID)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Validates Contact fields that must be unique (login, emails). Handles specialized logic
     * dealing with whether duplicate emails are allowed for the interface.
     * @param Connect\Contact $contact Contact to validate
     * @param array $formData Supplied if the form data is relevant (namely, in the case of a #create)
     * @return boolean|array False if no validation errors otherwise Array of error messages
     */
    protected function validateUniqueFields($contact, array $formData = array()) {
        $errors = $this->checkUniqueFields($contact);
        if ($errors && !$errors['duplicates_within_email_fields']
            && Api::site_config_int_get(CFG_OPT_DUPLICATE_EMAIL)
            && (!$formData || is_object($formData['ResetPasswordProcess']))) {
                // Contact create for an existing email only if coming through 'finish account creation' process
                // and if the login is unique.
                // Contact update only cares if there's an existing contact w/ the specified login.
                return $errors['login'] ?: false;
        }
        return $errors;
    }
}
