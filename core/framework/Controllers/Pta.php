<?php

namespace RightNow\Controllers;

use RightNow\Utils\Framework,
    RightNow\Utils\Config,
    RightNow\Utils\Text,
    RightNow\Utils\Url,
    RightNow\Libraries,
    RightNow\Api;

/**
 * Pass through authentication. Provides a mechanism for a third party site to send in contact
 * credentials via the URL to automatically create/update the contact and log them in.
 *
 * PTA sequence diagram: ({@link http://bit.ly/fT2IAv})
 */
final class Pta extends Base
{
    /**
     * Error Code 1: No p_li parameter was found in the request.
     */
    const ERROR_NO_PTA_PARAMETER_FOUND = 1;

    /**
     * Error Code 2: The data returned from the pre_pta_decode was not an array or string.
     */
    const ERROR_FAILED_DECODE_HOOK = 2;

    /**
     * Error Code 3: The contact data could not be successfully base64 decoded.
     */
    const ERROR_FAILED_DECODE = 3;

    /**
     * Error Code 4: The contact data sent was not in the format expected.
     */
    const ERROR_INVALID_DATA_FORMAT = 4;

    /**
     * Error Code 5: No p_userid parameter was found in the PTA contact data. Contacts must have a login value set.
     */
    const ERROR_NO_USERID_FOUND = 5;

    /**
     * Error Code 6: The value for the p_li_passwd key did not match the value of the PTA_SECRET_KEY config setting.
     */
    const ERROR_INCORRECT_PASSWORD = 6;

    /**
     * Error Code 7: The credentials for the user were invalid.
     */
    const ERROR_UNABLE_TO_LOGIN = 7;

    /**
     * Error Code 8: The PTA_ENABLED config setting is not enabled.
     */
    const ERROR_PTA_NOT_ENABLED = 8;

    /**
     * Error Code 9: The encrypted PTA data could not be successfully decrypted.
     */
    const ERROR_FAILED_DECRYPTION = 9;

    /**
     * Error Code 10: The provided encryption method is not one of the supported options.
     */
    const ERROR_UNSUPPORTED_ENCRYPTION_METHOD = 10;

    /**
     * Error Code 11: The provided encryption padding is not one of the supported options.
     */
    const ERROR_UNSUPPORTED_PADDING_METHOD = 11;

    /**
     * Error Code 12: The provided encryption keygen is not one of the supported options.
     */
    const ERROR_UNSUPPORTED_KEYGEN_METHOD = 12;

    /**
     * Error Code 13: This interface MUST use encryption but no encryption method was provided.
     */
    const ERROR_MUST_USE_ENCRYPTION = 13;

    /**
     * Error Code 14: The data provided after the pre_pta_convert hook was not an array.
     */
    const ERROR_FAILED_PRE_PTA_CONVERT_HOOK = 14;

    /**
     * Error Code 15: The provided password value exceeds the maximum allowed length.
     */
    const ERROR_PASSWORD_LENGTH_EXCEEDED = 15;

    /**
     * Error Code 16: The PTA token provided has expired.
     */
    const ERROR_TOKEN_EXPIRED = 16;

    /**
     * Error Code 17: The contact data provided contains duplicate emails.
     */
    const ERROR_DUP_EMAILS_WITHIN_CONTACT = 17;

    /**
     * Error Code 18: The salt value exceeds the maximum length for the specified encryption method.
     */
    const ERROR_SALT_VALUE_TOO_LONG = 18;

    /**
     * Error Code 19: The initialization vector value exceeds the maximum length for the specified encryption method.
     */
    const ERROR_IV_VALUE_TOO_LONG = 19;

    /**
     * Error Code 20: The Login User Id value exceeds the 80 character limit.
     */
    const ERROR_LOGIN_USER_ID_TOO_LONG = 20;

    private $redirectLocation = '';

    public function __construct()
    {
        parent::__construct();
        if(!Config::getConfig(PTA_ENABLED))
            $this->_loginRedirect(self::ERROR_PTA_NOT_ENABLED);
    }

    /**
     * Attempts to perform a PTA login from the passed encoded contact data
     */
    public function login()
    {
        $ptaParameterFound = false;
        $ptaParameter = '';
        //parse out p_li variable and redirect location
        $segments = $this->uri->segment_array();
        for($i = 3; $i <= count($segments); $i++)
        {
            //Bypass the redirect parameter segment
            if($segments[$i] === 'redirect' || $segments[$i] === 'redirect_to')
            {
                continue;
            }
            if($segments[$i] !== 'p_li')
            {
                $this->redirectLocation .= $segments[$i] . '/';
            }
            else
            {
                $ptaParameter = urldecode($segments[++$i]);
                $ptaParameterFound = true;
            }
        }

        if ($ptaParameterFound === true && $_GET)
        {
            if (!($this->session->getSessionData('sessionID') === @json_decode(trim(Api::ver_ske_decrypt(urldecode($_GET["p_ptaid"]))))->p_ptaid))
                $this->_loginRedirect(self::ERROR_UNABLE_TO_LOGIN);
        }

        if($ptaParameterFound === false && !($ptaParameter = $_POST['p_li']))
            $this->_loginRedirect(self::ERROR_NO_PTA_PARAMETER_FOUND);

        //strip off trailing slash
        if($this->redirectLocation)
        {
            if(substr($this->redirectLocation, -1) === '/')
                $this->redirectLocation = substr($this->redirectLocation, 0, strlen($this->redirectLocation) - 1);
        }
        else
        {
            $this->redirectLocation = Config::getConfig(CP_HOME_URL);
        }

        //decode and retrieve login integration data
        $contactDataArray = $this->_liDataToPairs($this->_convertPtaStringToArray($ptaParameter));

        if(isset($contactDataArray['login']) && strlen($contactDataArray['login']) > 255) {
            $this->_loginRedirect(self::ERROR_LOGIN_USER_ID_TOO_LONG);
        }
        $profile = $this->_getProfileFromPairdata($contactDataArray);

        Framework::runSqlMailCommitHook();

        if($contactDataArray && $profile != null)
        {
            $this->session->setPTA($profile);
            if(!$this->session->getSessionData('cookiesEnabled'))
            {
                Framework::setLocationHeader('/ci/pta/ensureCookiesEnabled/' . urlencode(urlencode($this->redirectLocation)) . Url::sessionParameter());
                exit;
            }

            $this->_redirectToDestination(html_entity_decode($this->redirectLocation, ENT_QUOTES));
        }
        else //unable to login - e.g. bad pass or duplicate email
        {
            $this->_loginRedirect(self::ERROR_UNABLE_TO_LOGIN);
        }
    }

    /**
     * Performs a PTA logout of the logged in user. This allows the
     * parent site and the RightNow support site to be synced
     */
    public function logout()
    {
        Url::redirectToHttpsIfNecessary();

        $redirectInformation = $this->model('Contact')->doLogout('', Config::getConfig(PTA_EXTERNAL_POST_LOGOUT_URL))->result;
        Framework::destroyCookie('cp_session');
        if(isset($redirectInformation['socialLogout']) && $redirectInformation['socialLogout']){
            Framework::setLocationHeader($redirectInformation['socialLogout']);
        }
        else{
            Framework::setLocationHeader(Config::getConfig(PTA_EXTERNAL_POST_LOGOUT_URL));
        }
        exit;
    }

    /**
     * Redirect to ensure that the user has cookies enabled. If cookies are enabled (i.e. the user is logged in) then
     * we take them on their way. Otherwise we redirect them to an error page
     * @param string|null $redirect Location to take user to after cookies have been verified
     */
    public function ensureCookiesEnabled($redirect = null)
    {
        if($this->session->getSessionData('cookiesEnabled'))
        {
            $redirectLocation = html_entity_decode(urldecode(urldecode($redirect)), ENT_QUOTES) ?: Config::getConfig(CP_HOME_URL);
            $parsedURL = parse_url($redirectLocation);
            if(isset($parsedURL['scheme']) && $parsedURL['scheme'])
            {
                Framework::setLocationHeader($redirectLocation);
                exit;
            }
            $this->_redirectToDestination($redirectLocation);
        }
        else
        {
            $this->session->destroyProfile();
            Framework::setLocationHeader("/app/error/error_id/7" . Url::sessionParameter());
            exit;
        }
    }

    /**
     * Do nothing. Can't require login to a controller that performs logins
     * @internal
     */
    public function _ensureContactIsAllowed() {
    }

    /**
     * Annoyingly enough, this method, despite its similarity to the equivalent
     * method in Base, needs to remain because it is just different
     * enough that the base class method shouldn't be required to handle all
     * the differences.
     * @param int $errorCode Error code to replace in redirect URL
     * @internal
     */
    public function _loginRedirect($errorCode=0)
    {
        if($errorCode > 0){
            \RightNow\ActionCapture::record('pta', 'error', $errorCode);
        }
        $externalLogin = Url::replaceExternalLoginVariables($errorCode, 0);
        if($externalLogin)
            Framework::setLocationHeader($externalLogin);
        else
            Framework::setLocationHeader(Url::getShortEufBaseUrl('sameAsRequest', '/app/' . Config::getConfig(CP_LOGIN_URL) . Url::sessionParameter()));
        exit;
    }

    /**
     * We return an empty string so that the login page will send the user to the default page after login.
     * @internal
     */
    protected function _getLoginReturnUrl() {
        return '';
    }

    /**
     * Converts array of PTA URL parameters into correct
     * pairdata to perform a contact update or create
     * @param array $contactDataArray PTA key/value pairs
     */
    private function _liDataToPairs(array $contactDataArray)
    {
        $addr = array();
        foreach($contactDataArray as $key => $val)
        {
            // substr returns false instead of an empty string, the API doesn't like that
            if(!is_string($val))
                $val = '';
            //Trim and XSS sanitize all keys except passwords
            if($key !== 'p_passwd' && $key !== 'p_li_passwd'){
                $val = \RightNow\Environment\xssSanitizeReplacer(trim($val), false);
            }
            switch ($key)
            {
                case 'p_userid':
                    if(!$val)
                    {
                        Api::phpoutlog("A 'p_userid' key was found but no value was supplied");
                        $this->_loginRedirect(self::ERROR_NO_USERID_FOUND);
                    }
                    $pairdata['login'] = $val;
                    break;

                case 'p_email':
                case 'p_email.addr':
                    $pairdata['email'] = array('addr' => ($val === "") ? null : $val);
                    break;

                case 'p_email_alt1':
                case 'p_email_alt1.addr':
                    $pairdata['email_alt1'] = array('addr' => ($val === "") ? null : $val);
                    break;

                case 'p_email_alt2':
                case 'p_email_alt2.addr':
                    $pairdata['email_alt2'] = array('addr' => ($val === "") ? null : $val);
                    break;

                case 'p_street':
                case 'p_addr.street':
                    $addr['street'] = ($val === "") ? null : $val;
                    $addressSet = true;
                    break;

                case 'p_city':
                case 'p_addr.city':
                    $addr['city'] = ($val === "") ? null : $val;
                    $addressSet = true;
                    break;

                case 'p_postal_code':
                case 'p_addr.postal_code':
                    $addr['postal_code'] = ($val === "") ? null : $val;
                    $addressSet = true;
                    break;

                case 'p_sa_state':
                case 'p_state.sa':
                    if(is_numeric($val))
                    {
                        $stateSet = true;
                        $state['sa'] = intval($val);
                    }
                    break;

                case 'p_ma_state':
                case 'p_state.ma':
                    if(is_numeric($val))
                    {
                        $stateSet = true;
                        $state['ma'] = intval($val);
                    }
                    break;

                case 'p_css_state':
                case 'p_state.css':
                    if(is_numeric($val))
                    {
                        $stateSet = true;
                        $state['css'] = intval($val);
                    }
                    break;

                case 'p_country_id':
                case 'p_addr.country_id':
                    $val = str_replace(' ', '', $val);
                    if ($val == '' || $val === '0')
                        $addr['country_id'] = INT_NULL;
                    else if (!is_numeric($val))
                        $addr['country_id'] = INT_NOT_SET;
                    else if(($countryID = intval($val)) > 0)
                        $addr['country_id'] = $countryID;
                    $addressSet = true;
                    break;

                case 'p_prov_id':
                case 'p_addr.prov_id':
                    $val = str_replace(' ', '', $val);
                    if ($val == '' || $val === '0')
                        $addr['prov_id'] = INT_NULL;
                    else if (!is_numeric($val))
                        $addr['prov_id'] = INT_NOT_SET;
                    else if(($provinceID = intval($val)) > 0)
                        $addr['prov_id'] = $provinceID;
                    $addressSet = true;
                    break;

                case 'p_ph_office':
                    $pairdata['ph_office'] = ($val === "") ? null : $val;
                    break;

                case 'p_ph_mobile':
                    $pairdata['ph_mobile'] = ($val === "") ? null : $val;
                    break;

                case 'p_ph_fax':
                    $pairdata['ph_fax'] = ($val === "") ? null : $val;
                    break;

                case 'p_ph_asst':
                    $pairdata['ph_asst'] = ($val === "") ? null : $val;
                    break;

                case 'p_ph_home':
                    $pairdata['ph_home'] = ($val === "") ? null : $val;
                    break;

                case 'p_org_id':
                    $val = str_replace(' ', '', $val);
                    if ($val == '' || $val === '0')
                        $pairdata['org_id'] = INT_NULL;
                    else if (!is_numeric($val))
                        $pairdata['org_id'] = INT_NOT_SET;
                    else
                        $pairdata['org_id'] = intval($val);
                    break;

                case 'p_passwd':
                    if(!Config::getConfig(PTA_IGNORE_CONTACT_PASSWORD))
                    {
                        if(Api::utf8_char_len($val) > 20)
                            $this->_loginRedirect(self::ERROR_PASSWORD_LENGTH_EXCEEDED);
                        $pairdata['password_text'] = $val;
                    }
                    break;

                case 'p_title':
                    $pairdata['title'] = ($val === "") ? null : $val;
                    break;

                case 'p_alt_last_name':
                case 'p_alt_name.last':
                    $altName['last'] = ($val === "") ? null : $val;
                    $altNameSet = true;
                    break;
                case 'p_alt_first_name':
                case 'p_alt_name.first':
                    $altName['first'] = ($val === "") ? null : $val;
                    $altNameSet = true;
                    break;
                case 'p_first_name':
                case 'p_name.first':
                    $name['first'] = ($val === "") ? null : $val;
                    $nameSet = true;
                    break;

                case 'p_last_name':
                case 'p_name.last':
                    $name['last'] = ($val === "") ? null : $val;
                    $nameSet = true;
                    break;

                case 'p_li_expiry':
                    if($val !== "" && intval($val) < time()){
                        $this->_loginRedirect(self::ERROR_TOKEN_EXPIRED);
                    }
                    break;

                case 'p_li_passwd':
                    $integrationPassword = $val;
                    break;

                default:
                    $data[$key] = ($val === "") ? null : $val;
                    break;
            }
        }

        if(!isset($pairdata['login']) || !$pairdata['login'])
            $this->_loginRedirect(self::ERROR_NO_USERID_FOUND);

        if(isset($addressSet) && $addressSet && count($addr) > 0){
            $pairdata['addr'] = $addr;
        }

        if(isset($nameSet) && $nameSet)
            $pairdata['name'] = $name;

        if(isset($altNameSet) && $altNameSet)
            $pairdata['alt_name'] = $altName;

        if(isset($stateSet) && $stateSet)
            $pairdata['state'] = $state;

        $pairdata['source_upd'] = array(
            'lvl_id1' => SRC1_EU,
            'lvl_id2' => SRC2_EU_PASSTHRU,
        );

        $setEmails = array_filter(array(strtolower(isset($pairdata['email']) ? $pairdata['email']['addr'] : ''), strtolower(isset($pairdata['email_alt1']) ? $pairdata['email_alt1']['addr'] : ''), strtolower(isset($pairdata['email_alt2']) ? $pairdata['email_alt2']['addr'] : '')));
        if(count($setEmails) !== count(array_unique($setEmails)))
            $this->_loginRedirect(self::ERROR_DUP_EMAILS_WITHIN_CONTACT);

        if(!isset($pairdata['password_text']) || !$pairdata['password_text'])
            $pairdata['password_text'] = '';

        if(Config::getConfig(PTA_ENCRYPTION_METHOD) === "" && $integrationPassword != Config::getConfig(PTA_SECRET_KEY))
            $this->_loginRedirect(self::ERROR_INCORRECT_PASSWORD);

        if(isset($data) && $data)
        {
            $customFieldPairdata = $this->_cfParmsToPairs($data);
            if(count($customFieldPairdata))
                $pairdata['custom_field'] = $customFieldPairdata;

            $channelFields = $this->channelFieldsToPairs($data, $pairdata['login']);
            if($channelFields !== null)
                $pairdata['channel_type'] = $channelFields;
        }
        return $pairdata;
    }

    /**
     * Takes the PTA string found in the URL in encoded query string format and decodes it based on config
     * settings. Also runs the two PTA hooks on the data prior to decoding/decrypting and after
     * decoding/decrypting.
     *
     * @param string $contactDataString PTA data extracted from the URL
     */
    private function _convertPtaStringToArray($contactDataString)
    {
        //PTA integrations used to allow two p_li data strings separated by a '.'. The first is the contact data
        //and the second would be page fields (i.e. p_question for the AAQ page). CP currently only supports contact data.
        $contactDataString = explode('.', $contactDataString);
        $contactDataString = $contactDataString[0];

        //Run the pre_pta_decode hook
        $preHookData = array('data' => array('p_li' => $contactDataString, 'redirect_to' => $this->redirectLocation));
        Libraries\Hooks::callHook('pre_pta_decode', $preHookData);

        //Update redirect location and either return data converted in hook, or throw error
        //if hook result is not a string
        $this->redirectLocation = $preHookData['data']['redirect_to'];
        if(is_array($preHookData['data']['p_li']))
        {
            return $preHookData['data']['p_li'];
        }
        else if(!is_string($preHookData['data']['p_li']))
        {
            $this->_loginRedirect(self::ERROR_FAILED_DECODE_HOOK);
        }

        require_once CPCORE . 'Internal/Libraries/Encryption.php';
        try {
            $convertedPtaString = \RightNow\Internal\Libraries\Encryption::decryptPtaString($preHookData['data']['p_li']);
        }
        catch (\Exception $e) {
            $error = $e->getMessage();
            $errorCode = defined("self::$error") ? constant("self::$error") : 0; // Should map to one of the ERROR_ constants for this class.
            if (!$errorCode) {
                Api::phpoutlog("An unexpected exception was encountered from decryptPtaString: '$error'");
            }
            $this->_loginRedirect($errorCode);
        }

        //Strings encrypted with RSSL_PAD_ZERO come back with null padding bytes untrimmed which
        //can make it possible to fail validation or possibly create other weird interactions
        if (Config::getConfig(PTA_ENCRYPTION_PADDING) === RSSL_PAD_ZERO)
            $convertedPtaString = trim($convertedPtaString);

        //Due to the possibility of an ampersand (&) in an input field, we will seperate tokens by
        //exploding &p_. We will re-append this to all keys other than the first, which retains its p_ prefix
        $ptaParameterItems = explode('&p_', $convertedPtaString);

        $ptaData = array();
        $firstElement = true;
        foreach($ptaParameterItems as $ptaItem)
        {
            $key = Text::getSubstringBefore($ptaItem, '=', isset($key) ? $key : '');
            //We need to re-prepend the p_ parameter on every element except the
            //first since they got stripped out by the explode above
            if($firstElement === false){
                $key = "p_$key";
            }
            $firstElement = false;
            $value = Text::getSubstringAfter($ptaItem, '=');
            if($key === '' || $key === 'p_' || (Text::stringContains($key, 'p_email') && !Text::isValidEmailAddress($value) && $value !== "" && $value !== false))
            {
                Api::phpoutlog("The key-value pair '{$key}={$value}' is invalid");
                $this->_loginRedirect(self::ERROR_INVALID_DATA_FORMAT);
            }

            $ptaData[$key] = $value;
        }

        //Run the pre_pta_decode hook
        $preHookData = array('data' => array('decodedData' => $ptaData));
        Libraries\Hooks::callHook('pre_pta_convert', $preHookData);

        if(!is_array($preHookData['data']['decodedData']))
        {
            $this->_loginRedirect(self::ERROR_FAILED_PRE_PTA_CONVERT_HOOK);
        }
        return $preHookData['data']['decodedData'];
    }

    /**
     * Iterates over all custom field parameters found in the PTA string and
     * builds up the appropriate pairdata array
     * @param array $parms Custom field parameters found in the URL
     * @return array Pairdata for custom fields
     */
    private function _cfParmsToPairs(array $parms)
    {
        //First, iterate over custom fields and create a new array
        //where the cf_id is the key. This will save us work later
        $keyedCustomFields = array();
        foreach(Framework::getCustomFieldList(VTBL_CONTACTS, VIS_ENDUSER_EDIT_RW) as $value){
            $keyedCustomFields[$value['cf_id']] = $value;
        }
        ksort($parms);
        $count = 0;
        $customFieldPairdata = array();
        foreach($parms as $key => $customFieldValue)
        {
            if(Text::beginsWith($key, 'p_ccf_'))
            {
                $customFieldId = intval(Text::getSubstringAfter($key, 'p_ccf_'));
                if($customFieldId < 1)
                    continue;
                if($keyedCustomFields[$customFieldId])
                {
                    $result = $this->_convertCustomFieldToPair($keyedCustomFields[$customFieldId], $customFieldValue);
                    if(is_array($result))
                    {
                        $customFieldPairdata["cf_item{$count}"] = $result;
                        $count++;
                    }
                }
            }
        }
        return $customFieldPairdata;
    }

    /**
     * Converts custom field content into appropriate pairdata if data passed in
     * is valid for the custom field type.
     * @param array $customField Custom field as defined within the database
     * @param string $urlValue Value of custom field specified in PTA string
     * @return array|boolean Custom field pairdata if value is valid, false otherwise
     */
    private function _convertCustomFieldToPair(array $customField, $urlValue)
    {
        switch($customField['data_type'])
        {
            case CDT_VARCHAR:
            case CDT_MEMO:
                if($urlValue === null)
                    $urlValue = '';
                $valueType = 'val_str';
                break;
            case CDT_INT:
            case CDT_BOOL:
            case CDT_MENU:
            case CDT_OPT_IN:
                if($urlValue === '' || $urlValue === null)
                {
                    $urlValue = INT_NULL;
                }
                else
                {
                    if(!is_numeric($urlValue))
                        return false;
                    $urlValue = intval($urlValue);
                }
                $valueType = 'val_int';
                break;
            case CDT_DATE:
            case CDT_DATETIME:
                if($urlValue === '' || $urlValue === null)
                {
                    $urlValue = $customField['data_type'] === CDT_DATETIME ? TIME_NULL : DATE_NULL;
                }
                else
                {
                    if(!is_numeric($urlValue))
                        return false;
                    $urlValue = intval($urlValue);
                }
                $valueType = $customField['data_type'] === CDT_DATETIME ? 'val_dttm' : 'val_date';
                break;
        }
        return array('cf_id' => $customField['cf_id'], $valueType => $urlValue);
    }

    /**
     * Convert channel field URL parameters into pairdata.
     * @param array $data List of parameters found in PTA string keyed by parameter name
     * @param string $username Username passed in with PTA string. Used to lookup current channel values
     * @return array Pairdata to submit channel fields
     */
    private function channelFieldsToPairs(array $data, $username)
    {
        //Get all end user visible channels and the currently specified channels
        $allChannels = $this->model('Contact')->getChannelTypes()->result;
        $currentUsernames = $this->model('Contact')->getChannelFields($username)->result ?: array();

        //Find all the new usernames so they can be added to the database
        $hasModifiedChannel = false;
        foreach($data as $urlKey => $urlValue) {
            if(Text::beginsWithCaseInsensitive($urlKey, 'p_chan_')) {
                if(trim($urlValue) === '') {
                    $urlValue = null;
                }

                //If the channel is visible and the username isn't set, or it is different from the current value. Add it.
                $channelID = intval(Text::getSubstringAfter($urlKey, 'p_chan_'));
                if($allChannels[$channelID] && (!isset($currentUsernames[$channelID]) || !$currentUsernames[$channelID] || $currentUsernames[$channelID]['Username'] !== $urlValue)) {
                    $hasModifiedChannel = true;
                    $currentUsernames[$channelID]['Username'] = $urlValue;
                }
            }
        }

        //Rewrite all of the usernames if any of them have been edited
        if($hasModifiedChannel) {
            $channelPairData = array();
            $channelCount = 1;
            foreach($currentUsernames as $ID => $channel) {
                if($channel['Username'] !== null) {
                    $channelPairData['contact2chan_type_item' . $channelCount++] = array('chan_type_id' => $ID, 'username' => $channel['Username'], 'userid' => $channel['UserNumber']);
                }
            }
            return $channelPairData;
        }
        return null;
    }

    /**
     * Takes the PTA pair data and attempts to log in the user as well as create/update the record
     *
     * @param array $ptaPairData Contact pairdata
     * @return object|null Profile object or null if user could not be verified
     */
    private function _getProfileFromPairdata(array $ptaPairData)
    {
        $preHookData = array('data' => array('pta' => true, 'source' => 'PTA'));
        Libraries\Hooks::callHook('pre_login', $preHookData);

        //If a user is already logged in during the request, check if it's the same user that is
        //making another request. If so, we can reuse the session ID. If it's a new user, we need
        //to generate a new session ID for them
        if((Framework::isLoggedIn() || $this->session->isProfileFlagCookieSet()) && $this->session->getProfileData('login') !== $ptaPairData['login']){
            $this->session->generateNewSession();
            $sessionID = $this->session->getSessionData('sessionID');
            $apiProfile = Api::contact_login_verify($sessionID, null, $ptaPairData);
        }
        else{
            $sessionID = $this->session->getSessionData('sessionID');
            $apiProfile = Api::contact_login_verify($sessionID, null, $ptaPairData);
            //Check if the profile returned has a different username that the one we're trying to login. If
            //so, that means an existing user is already logged in via this session ID so we need to get a new one.
            if($apiProfile && $apiProfile->login !== $ptaPairData['login']){
                $this->session->generateNewSession();
                $sessionID = $this->session->getSessionData('sessionID');
                $apiProfile = Api::contact_login_verify($sessionID, null, $ptaPairData);
            }
        }

        $postHookData = array('returnValue' => $apiProfile, 'data' => array('source' => 'PTA'));
        Libraries\Hooks::callHook('post_login', $postHookData);
        if($apiProfile){
            $this->session->setSocialUser($apiProfile);
            return $this->session->createMapping($apiProfile, true);
        }
        //Invalid login, destroy current cookie information because it might already
        //contain informaion from a previous login.
        if($this->session->getProfileData('login'))
            $this->session->destroyProfile();
        \RightNow\ActionCapture::record('contact', 'login', 'pta');
        return null;
    }

    /**
    * Redirects to the specified page.
    * If the specified location begins with ci/, cc/, or app/ then the redirect goes
    * to the location with a '/' prepended; otherwise the redirect location is assumed
    * to be a page relative to /app/ so the redirect goes to the location with '/app/' prepended.
    * @param string $redirectLocation The page to go to
    */
    private function _redirectToDestination($redirectLocation)
    {
        $prepend = '/app/';
        if(Text::beginsWith($redirectLocation, 'ci/') || Text::beginsWith($redirectLocation, 'cc/') || Text::beginsWith($redirectLocation, 'app/'))
            $prepend = '/';

        Framework::setLocationHeader("{$prepend}{$redirectLocation}" . Url::sessionParameter());
        exit;
    }
}
