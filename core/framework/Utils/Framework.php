<?php

namespace RightNow\Utils;
use RightNow\Api,
    RightNow\Connect\Crypto\v1_4 as Crypto,
    RightNow\Connect\v1_4 as Connect;

/**
 * Framework related utility functions that are specific to the CP framework.
 */
final class Framework extends \RightNow\Internal\Utils\Framework
{
    const ANSWER_UNAVAILABLE = 1;
    const DOCUMENT_SLA = 2;
    const DOWNLOAD_ERROR = 3;
    const DOCUMENT_PERMISSION = 4;
    const OPERATION_TIMEOUT = 5;
    const ILLEGAL_PARAMETER = 6;
    const COOKIES_DISABLED = 7;
    const CONTENT_PERMISSION = 8;
    const QUESTION_UNAVAILABLE = 9;

    /**
     * Cache array that stores all process cache variables
     */
    private static $processCache = array();
    /**
     * Get the concatenated framework version in the form 'major.minor.nano'
     * @return string
     */
    public static function getFrameworkVersion()
    {
        return CP_FRAMEWORK_VERSION . '.' . CP_FRAMEWORK_NANO_VERSION;
    }

    /**
     * Checks for the specified key in the process cache
     * @param string $key The variable to search for in the cache
     * @return mixed If found, returns the variable in cache, otherwise returns null
     */
    public static function checkCache($key)
    {
        if(!array_key_exists($key, self::$processCache))
            return null;
        list($value, $shouldSerialize) = self::$processCache[$key];
        if ($shouldSerialize)
            $value = unserialize($value);
        return $value;
    }

    /**
     * Sets an item in the process cache. This cache only lasts for the length of the PHP process
     * @param string $key The variable to set in the cache
     * @param string $value The value of the variable to set in the cache
     * @param bool $shouldSerialize Usually you don't need to pass a value for this, but if you're sending in an array which contains an object,
     * you need to pass true. If you really want to set this parameter, if $value is a value type, then pass false; if it's a reference type, which in PHP means an object, pass true.
     * @throws \Exception If value passed is an array that contains an object and $shouldSerialize was not set
     * @return void
     */
    public static function setCache($key, $value, $shouldSerialize=null)
    {
        if($shouldSerialize === null)
        {
            if(!IS_HOSTED && is_array($value) && self::doesArrayContainAnObject($value))
                throw new \Exception("To setCache() you passed an array which contains an object but didn't specify if the value should be serialized. Either specify whether the whole array should be serialized or serialize the objects within it.\n");
            $shouldSerialize = is_object($value);
        }

        if($shouldSerialize)
            $value = serialize($value);

        self::$processCache[$key] = array($value, $shouldSerialize);
    }

    /**
     * Removes an item from the process cache.
     * @param string $key The variable to remove from the cache
     */
    public static function removeCache($key)
    {
        if(array_key_exists($key, self::$processCache))
            unset(self::$processCache[$key]);
    }

    /**
     * Performs the PHP in_array() function but does so ignoring case
     *
     * @param array $array The array to search through
     * @param string $search The string to search for within the array
     * @return bool Whether or not the search was found in the array
     */
    public static function inArrayCaseInsensitive(array $array, $search)
    {
        for($i = 0; $i < count($array); $i++)
        {
            if(strcasecmp($array[$i], $search) === 0)
                return true;
        }
        return false;
    }

    /**
     * Checks if the current user is logged in
     * @return bool If logged in true else false.
     * @throws \Exception If the session class has not yet been initialized
     */
    public static function isLoggedIn()
    {
        $session = get_instance()->session;
        if (!is_object($session)) {
            throw new \Exception("No session has been created yet, so we cannot determine if the user has been logged in.  Either the controller you're using does not cause the clickstream hook to create a session or you need to call isLoggedIn after the Controller\\Base contructor has run.");
        }
        return $session->isLoggedIn();
    }
    
    /**
     * Checks if the current user password is not expired
     * @return bool If password is not expired then true else false
     */
    public static function ensurePasswordIsNotExpired(){
        if(self::isLoggedIn() && !self::isPta() && !self::isSAMLLogin() && !self::isOpenLogin()){
            $contactID = get_instance()->session->getProfileData('contactID');
            if($contactID){
                $contact = get_instance()->model('Contact')->get($contactID)->result;
                if ($contact->PasswordExpirationTime) {
                    $now = time();
                    if ($contact->PasswordExpirationTime < $now) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Checks if the current user is a social moderator
     * @return bool True if user is a social moderator else False
     */
    public static function isSocialModerator () {
        return self::getSocialUser(STATUS_TYPE_SSS_USER_ACTIVE, PERM_VIEWSOCIALMODERATORDASHBOARD) !== null;
    }

    /**
     * Checks if the current user is logged in and has an active status.
     * @return bool True if user is logged in and has an active social user.
     */
    public static function isActiveSocialUser () {
        return self::getSocialUser(STATUS_TYPE_SSS_USER_ACTIVE) !== null;
    }

    /**
     * Checks if the current user is a social moderator and has permission to moderate users
     * @return bool True if user is a social moderator with the permission to moderate users else False
     */
    public static function isSocialUserModerator () {
        if (self::isSocialUser() && self::isSocialModerator()) {
            $mockSocialUser = get_instance()->model('CommunityUser')->getBlank()->result;
            return ($mockSocialUser && ($mockSocialUser->SocialPermissions->canUpdateStatus() || $mockSocialUser->SocialPermissions->canDelete()));
        }
        return false;
    }

    /**
     * Checks if the current user is logged in and has a social user tied to their profile
     * @return boolean True if the logged in user has a social profile
     * @throws \Exception If the session class has not yet been initialized
     */
    public static function isSocialUser(){
        if(!self::isLoggedIn()){
            return false;
        }
        return get_instance()->session->getProfileData('socialUserID') > 0;
    }

    /**
     * Checks if the current user is logged in through pass through authentication
     * @return bool If logged in via pta true else false.
     */
    public static function isPta()
    {
        return (bool)(get_instance()->session->getSessionData('ptaUsed'));
    }

    /**
     * Checks if the current user is logged in through open login
     * @return bool If logged in via open login true else false.
     */
    public static function isOpenLogin()
    {
        return (bool)(get_instance()->session->getProfileData('openLoginUsed'));
    }
    
    /**
     * Checks if the current user is logged in through SAML login
     * @return bool If logged in via SAML login true else false.
     */
    public static function isSAMLLogin()
    {
        return (bool)(get_instance()->session->getProfileData('samlLoginUsed'));
    }

    /**
     * Checks for existence of login cookie. It it exists, it deletes
     * the cookie and returns true. Otherwise, it returns false
     * @return bool Whether the temporary login cookie exists
     */
    public static function checkForTemporaryLoginCookie()
    {
        $CI = get_instance();
        if($CI->input->cookie('cp_login_start'))
        {
            //Temporary cookie exists, destroy it
            self::destroyCookie('cp_login_start');
            return true;
        }
        return false;
    }

    /**
     * Sets a session cookie with the name 'cp_login_start' that is used to determine if a contact
     * who doesn't have a CP session cookie set will accept cookies in order to log in.
     * @return void
     */
    public static function setTemporaryLoginCookie(){
        self::setCPCookie('cp_login_start', 1, 0);
    }

    /**
     * Creates an encrypted security token that can be used to verify form requests
     * @param int $id A token identifier that can be used for secondary protection or to pass data.
     * @return string A token with the id encrypted and encoded.
     */
    public static function createToken($id)
    {
        $token = self::checkCache("securityToken$id");
        if($token !== null)
            return $token;

        $contactID = 0;
        if(self::isLoggedIn())
        {
            $CI = get_instance();
            $contactID = $CI->session->getProfileData('contactID');
        }

        $token = parent::createCsrfToken($id, 0, $contactID);
        self::setCache("securityToken$id", $token);
        return $token;
    }

    /**
     * Create a token for protecting exchanges of data between the client and the server. The function differs
     * from createToken in that the created token has an expiration time based off the value of the SUBMIT_TOKEN_EXP config
     *
     * @param int $id A token identifier that can be used for secondary protection or to pass data.
     * @param bool $requireChallenge Indicates if a valid abuse challenge response must accompany the token when submitted.
     * @param bool $createCsrfToken Indicates its CSRF token used as part of f_tok parameter in forms.
     * @return string A token with an expiration time and the id encrypted and encoded.
     */
    public static function createTokenWithExpiration($id, $requireChallenge=false, $createCsrfToken = true)
    {
        // New custom config to enable Captcha challenge. Has 3 values.
        // Always(1), Default(0) and Unauthenticated(2)
        try {
            $customConfig = Connect\Configuration::fetch("CUSTOM_CFG_FORCE_CAPTCHA_CHALLENGE")->Value;
        }
        catch (\Exception $err) {
            $customConfig = 0;
        }
        if ($customConfig == 1){
            $requireChallenge = true;
        }
        $contactID = 0;
        if(self::isLoggedIn())
        {
            $CI = get_instance();
            $contactID = $CI->session->getProfileData('contactID');
        }
        else {
            if ($customConfig == 2) {
                $requireChallenge = true;
            }
        }
        return parent::createCsrfToken($id, 1, $contactID, $requireChallenge, $createCsrfToken);
    }

    /**
     * Generate a token used to validate the constraints of the submitted form data. This token enables server side validation
     * of widget attribute constraints. It's salted with the POST action (e.g. /app/ask), interface name and the post handler to prevent
     * the token from being used on other sites or forms or with different handler.
     * @param string $constraints A base64 encoded string of widget attribute constraints
     * @param string $action The POST action receiving the token
     * @param string $handler The PostRequest method that will handle the POST data
     * @return string A token combining the constraints, interface, and action
     */
    public static function createPostToken($constraints, $action, $handler) {
        $action = Url::deleteParameter($action, 'session');
        return Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe(implode('|', array(sha1($constraints), Api::intf_name(), $action, $handler))));
    }

    /**
     * Decrypt and validate a Post Token to ensure that the form constraints and handler haven't been tampered with. These
     * parameters are inserted at the end of the <rn:form> tag via the Widgets.php#addServerConstraints method.
     * @param string $token The form token
     * @param string $constraints The form constraints
     * @param string $action The POST action receiving the token
     * @param string $handler The path to the library and method processing the POST data
     * @return boolean true or false whether the token is valid or invalid
     */
    public static function isValidPostToken($token, $constraints, $action, $handler) {
        $decodedToken = Api::decode_base64_urlsafe($token);
        if(!$decryptedToken = Api::ver_ske_decrypt($decodedToken)) {
            return false;
        }
        $action = Url::deleteParameter($action, 'session');
        return ($decryptedToken === implode('|', array(sha1($constraints), Api::intf_name(), $action, $handler)));
    }

    /**
     * Checks if the given token matches the value we're checking against
     * @param string $token The security token
     * @param string $value The value to compare against
     * @param boolean $isStrict True for validating the token against the existing session else false
     * @return boolean True if token passed, false otherwise
     */
    public static function isValidSecurityToken($token, $value, $isStrict = true)
    {
        $contactID = 0;
        if(self::isLoggedIn())
        {
            $CI = get_instance();
            $contactID = $CI->session->getProfileData('contactID');
        }
        return parent::testCsrfToken($token, $value, $contactID, false, $isStrict);
    }

    /**
     * Gets the key value pairs from an optlist that are of type int. This ignores parent_id, parent_type, id_type, attrs.
     *
     * @param int $optlistID Optlist id
     * @return array Key value pairs of value and label, these may not be sequential
     */
    public static function getOptlist($optlistID)
    {
        $response = array();
        $optlist = Api::optl_get($optlistID);
        if ($optlist) {
            foreach($optlist as $key => $value){
                if (is_int($key)){
                    $response[$key] = $value;
                }
            }
        }
        return $response;
    }

    /**
     * Writes a message to the log file during page execution. This log can be viewed from the admin
     * section of your site. Log entries will only be written during Development mode.
     *
     * @param mixed $message The message to write to the outlog. Objects and arrays will be expanded using print_r.
     * @return void
     */
    public static function logMessage($message)
    {
        // log as if in hosted for testing
        $forceHostedLogging = func_num_args() > 1 ? func_get_arg(1) : false;
        if(!IS_HOSTED && !$forceHostedLogging)
        {
            if (!class_exists('\RightNow\Internal\Utils\DevelopmentLogger'))
                require_once CPCORE . 'Internal/Utils/DevelopmentLogger.php';

            \RightNow\Internal\Utils\DevelopmentLogger::log($message);
        }
        else if(!IS_OPTIMIZED && (!isset($fileSizeMet) || !$fileSizeMet))
        {
            static $fileSize = 0;
            static $fileSizeMet = false;

            if($fileSizeMet)
                return;

            $fileLocation = Api::cfg_path() . '/log/cp' . getmypid() . '.tr';
            if(!is_readable($fileLocation))
                $writeHeader = true;

            if(is_object($message) || is_array($message)){
                // @codingStandardsIgnoreStart
                $formattedMessage = '<pre>' . htmlspecialchars(print_r($message, true)) . '</pre>';
                // @codingStandardsIgnoreEnd
            }
            else{
                $formattedMessage = htmlspecialchars($message);
            }

            //Truncate entry to max allowed size
            $currentEntryLength = strlen($formattedMessage);
            if($fileSize + $currentEntryLength >= 50000)
            {
                $formattedMessage = substr($formattedMessage, 0, 50001 - $fileSize);
                $fileSizeMet = true;
            }

            $log = fopen($fileLocation, 'a');

            if(isset($writeHeader) && $writeHeader){
                fwrite($log,
                    '-------------------------------' .
                    "URI REQUEST: {$_SERVER['REQUEST_URI']}\n" .
                    'INTERFACE: ' . Api::intf_name() .
                    "\n-------------------------------\n");
            }
            $stackTrace = debug_backtrace();
            $caller = null;
            foreach ($stackTrace as &$c) {
                // Exclude the compatibility mapping from consideration as the calling line to avoid confusing users.
                if (!Text::endsWith($c['file'], "/scripts/cp/core/compatibility/Mappings/Functions.php")) {
                    $caller = $c;
                    break;
                }
            }
            $caller = $caller ?: $stackTrace[0]; // Give up and use whatever's on top of the stack.
            $file = $caller['file'];
            $file = Text::getSubstringAfter($file, "/scripts/cp/customer/development/", Text::getSubstringAfter($file, "/scripts/cp/", $file));

            $entry = sprintf("%s %s::%d  %s\n", date('G:i:s'), $file, $caller['line'], $formattedMessage);

            fwrite($log, $entry);
            if($fileSizeMet)
                 fwrite($log, Config::getMessage(MAXIMUM_SIZE_LOG_FILE_REACHED_LBL) . "\n");
            else
                 $fileSize += $currentEntryLength;
            fclose($log);
        }
    }

    /**
     * Adds an error message to the development header if in development mode
     *
     * @param string $errorMessage The error message to display
     * @return void
     */
    public static function addDevelopmentHeaderError($errorMessage)
    {
        if(!IS_OPTIMIZED && !defined('SUPPRESS_PAGE_ERRORS'))
        {
            $CI = get_instance();
            if(is_object($CI->developmentHeader))
                $CI->developmentHeader->addError($errorMessage);
        }
    }

    /**
     * Adds a warning message to the development header if in development mode
     *
     * @param string $warningMessage The warning message to display
     * @return void
     */
    public static function addDevelopmentHeaderWarning($warningMessage)
    {
        if(!IS_OPTIMIZED)
        {
            $CI = get_instance();
            if(is_object($CI->developmentHeader))
                $CI->developmentHeader->addWarning($warningMessage);
        }
    }

    /**
     * Adds an error message to the page and header if not in production mode
     * @param string $error The error message to display.
     * @param bool $return Denotes if the error message should be displayed or returned
     * @return string If $return is set to true, string will be returned
     */
    public static function addErrorToPageAndHeader($error, $return = false)
    {
        self::addDevelopmentHeaderError($error);
        if(!IS_OPTIMIZED && !defined('SUPPRESS_PAGE_ERRORS'))
        {
            if($return)
                return "<div><b>$error</b></div>";
            echo "<div><b>$error</b></div>";
        }
    }

    /**
     * Escapes string for DB querying
     * @param string $string The string you wish to escape
     * @return string The value escaped for the DB
     */
    public static function escapeForSql($string)
    {
        return strtr($string, get_instance()->rnow->getSqlEscapeCharacters());
    }

    /**
     * Runs the SqlMailCommit hook. This is often used to be sure that
     * commits occur before a redirect.
     * 
     * @param boolean $disconnectDatabase Flag to indicate whether the database connection to be closed or not
     * @return void
     */
    public static function runSqlMailCommitHook($disconnectDatabase = false)
    {
        $hooks =& load_class('Hooks');
        $hooks->_run_hook(array(
            'class' => 'RightNow\Hooks\SqlMailCommit',
            'function' => 'commit',
            'filename' => 'SqlMailCommit.php',
            'filepath' => 'Hooks',
            'params' => $disconnectDatabase
        ));
    }

    /**
     * Takes PHP code content, evals it, and returns the resulting content
     * @param string $code The code to execute
     * @param string $relativePathToContent Path to the content being executed, relative to the /cp/customer directory. Does not
     * affect how code is executed, but helps display paths in error messages correctly.
     * @return string The code after running through eval
     * @throws \Exception If the code being eval'd throws an exception
     */
    public static function evalCodeAndCaptureOutput($code, $relativePathToContent='') {
        ob_start();
        $code = "try{?>$code<?}catch(\Exception \$e){return \$e;}";
        $exception = Api::trusted_eval("/scripts/cp/customer/$relativePathToContent", $code);
        $fileContent = ob_get_clean();
        if($exception instanceof \Exception)
            throw $exception;
        return $fileContent;
    }

    /**
     * Determines if a) the current incident (based off the url) is closed and
     * b) if it has been closed passed the specified number of $hours.
     * @param int $hours The number of hours to check
     * @return bool True if incident has passed the deadline, false otherwise
     * @internal
     */
    public static function hasClosedIncidentReopenDeadlinePassed($hours)
    {
        if (($incident = get_instance()->model('Incident')->get(Url::getParameter('i_id'))->result) && $incident->ClosedTime && $incident->StatusWithType->StatusType->ID === STATUS_SOLVED)
            return (time() - ($hours * 60 * 60) > $incident->ClosedTime);
        return false;
    }

    /**
     * Increments the number of searches performed that is stored
     * within the users session cookie.
     * @return void
     */
    public static function incrementNumberOfSearchesPerformed()
    {
        $session = get_instance()->session;
        $session->setSessionData(array('numberOfSearches' => $session->getSessionData('numberOfSearches') + 1));
    }

    /**
     * Function used to get an icon path based on a file name
     *
     * @param string $path The path to the file
     * @return string The html to display the correct image
     */
    public static function getIcon($path)
    {
        if (Text::beginsWithCaseInsensitive($path, 'http')) {
            $fileExtensionClassName = " rn_url";
            $screenReaderText = "<span class='rn_ScreenReaderOnly'>" . Config::getMessage(LINK_TO_A_URL_CMD) . "</span>";
        }
        else {
            if (strtolower($path) === "rnklans") {
                $fileExtension = "rightnow";
            }
            else {
                $fileExtension = pathinfo($path, PATHINFO_EXTENSION);
            }
            $fileExtensionClassName = "";
            $screenReaderText = "";
            if ($fileExtension) {
                $fileExtension = htmlspecialchars($fileExtension, ENT_QUOTES, 'UTF-8');
                $fileExtensionClassName = " rn_$fileExtension";
                $screenReaderText = "<span class='rn_ScreenReaderOnly'>" . sprintf(Config::getMessage(FILE_TYPE_PCT_S_LBL), $fileExtension) . "</span>";
            }
        }
        return "<span class='rn_FileTypeIcon{$fileExtensionClassName}'>{$screenReaderText}</span>";
    }

    /**
     * Destroy a cookie given by $name by setting its expire
     * time to the past.
     * @param string $cookieName The name of the cookie to destroy
     * @param string $path The path of the cookie (default is '/')
     * @return void
     */
    public static function destroyCookie($cookieName, $path = '/')
    {
        $longEnoughToInvalidateCookie = 60 * 60 * 24 * 365;
        setcookie($cookieName, '', time() - $longEnoughToInvalidateCookie, $path);
    }

    /**
     * A wrapper for php's setcookie that provides
     * default optional values for path, domain, httponly, and secure
     *
     * @param string $name The name of the cookie (required)
     * @param string $value The value of the cookie (required)
     * @param int $expire The time the cookie expires (required)
     * @param string $path The path on the server in which the cookie will be available on (default is '/')
     * @param string $domain The domain that the cookie is available to (default is empty)
     * @param bool $httpOnly When true the cookie will not be available to javascript in modern browsers (default is true). This value should be true in most situations due to security concerns.
     * @param bool $secure Determines if the cookie is sent using HTTPS (default is SEC_END_USER_HTTPS)
     * @return void
     */
    public static function setCPCookie($name, $value, $expire, $path = '/', $domain = '', $httpOnly = true, $secure = -1)
    {
        $maxAge = $expire > time() ? $expire - time() : 0;
        $dateString = $expire > 0 ? "; expires=" . gmdate("D, d-M-Y H:i:s", $expire) . " GMT; Max-Age=$maxAge" : "";
        $domainString = strlen($domain) > 0 ? "; domain=$domain" : "";
        $httpOnlyString = $httpOnly ? "; httponly" : "";

        $secure = ($secure === -1) ? Config::getConfig(SEC_END_USER_HTTPS) : $secure;
        $secureString = $secure ? "; SameSite=None; Secure" : "; SameSite=Lax";

        // use header() since setcookie() doesn't support same-site until PHP 7
        header("Set-Cookie: $name=$value{$dateString}; path=$path{$domainString}{$httpOnlyString}{$secureString}\n", false);
    }

    /**
     * This sends an "Expires" header that expires in the future according to CACHED_CONTENT_EXPIRE_TIME.
     * It can only be used with GETs and, like all headers, must be called before any page content is sent.
     * @return void
     */
    public static function sendCachedContentExpiresHeader()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET')
        {
            $cacheTime = (($time = Config::getConfig(CACHED_CONTENT_EXPIRE_TIME)) && $time > 0) ? $time : 5;
            header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + $cacheTime * 60));
        }
    }

    /**
     * Writes content type and length headers to stop page cacheing and then
     * outputs the content passed in
     * @param string $content The content to display
     * @param string $mimeType Mime type declaration
     * @return void
     */
    public static function writeContentWithLengthAndExit($content, $mimeType = null)
    {
        parent::killAllOutputBuffering();
        if ($mimeType)
        {
            header('Content-Type: ' . $mimeType);
        }
        header('Content-Length: ' . strlen($content));
        exit($content);
    }

    /**
     * Returns an error title and message based on the error code given
     * @param int $code The error code
     * @return array An array of the error title and message
     */
    public static function getErrorMessageFromCode($code)
    {
        if(is_int($code))
            $code = (int)$code;
        else if(is_string($code)){
            $code = strtolower($code);
        }
        switch($code)
        {
            case self::ANSWER_UNAVAILABLE:
                $errorTitle = Config::getMessage(NOT_AVAIL_LBL);
                $errorMessage = Config::getMessage(ANSWER_IS_NO_LONGER_AVAILABLE_MSG);
                break;
            case self::DOCUMENT_SLA:
                $errorTitle = Config::getMessage(NOT_AVAIL_LBL);
                $errorMessage = Config::getMessage(SORRY_ACCT_DOESNT_SERV_LVL_AGRMNT_MSG);
                break;
            case self::DOWNLOAD_ERROR:
                $errorTitle = Config::getMessage(FILE_DOWNLOAD_ERROR_LBL);
                $errorMessage = Config::getMessage(SORRY_ERROR_DOWNLOADING_FILE_MSG);
                break;
            case self::DOCUMENT_PERMISSION:
                $errorTitle = Config::getMessage(PERMISSION_DENIED_LBL);
                $errorMessage = Config::getMessage(NO_ACCESS_PERMISSION_MSG);
                break;
            case self::CONTENT_PERMISSION:
                $errorTitle = Config::getMessage(NOT_AVAIL_LBL);
                $errorMessage = Config::getMessage(THERE_IS_NOTHING_HERE_FOR_YOU_SORRY_LBL);
                break;
            case self::OPERATION_TIMEOUT:
                $errorTitle = Config::getMessage(OPERATION_FAILED_LBL);
                $errorMessage = Config::getMessage(OPERATION_TIMEOUT_BTN_REFRESH_PAGE_LBL);
                break;
            case self::ILLEGAL_PARAMETER:
                $errorTitle = Config::getMessage(PERMISSION_DENIED_LBL);
                if(IS_DEVELOPMENT && ($param = Url::getParameter('errorParameter'))) {
                    $errorMessage = sprintf(Config::getMessage(ILLEGAL_VALUE_RECEIVED_PARAM_PCT_S_LBL), $param);
                }
                else {
                    $errorMessage = Config::getMessage(AN_ILLEGAL_PARAMETER_WAS_RECEIVED_MSG);
                }
                break;
            case self::COOKIES_DISABLED:
                $loginPage = (Config::getConfig(PTA_EXTERNAL_LOGIN_URL)) ?: Url::getShortEufBaseUrl('sameAsRequest', '/app/' . Config::getConfig(CP_LOGIN_URL) . Url::sessionParameter());
                $errorTitle = Config::getMessage(COOKIES_ARE_REQUIRED_MSG);
                $errorMessage = Config::getMessage(YOULL_ENABLE_COOKIES_BROWSER_BEF_MSG) . "<br/><a href='$loginPage'>" . Config::getMessage(BACK_TO_LOGIN_CMD) . '</a>';
                break;
            case self::QUESTION_UNAVAILABLE:
                $errorTitle = Config::getMessage(NOT_AVAIL_LBL);
                $errorMessage = Config::getMessage(THIS_DISCUSSION_IS_NO_LONGER_AVAILABLE_MSG);
                break;
            case 'sso9':
                $errorTitle = Config::getMessage(INCOMPLETE_ACCOUNT_DATA_LBL);
                $errorMessage = sprintf(Config::getMessage(SRRY_CREATE_ACCT_COMMUNITY_SPEC_MSG), '<a href="/app/account/profile' . Url::sessionParameter() . '">', '</a>');
                break;
            case 'sso10':
                $errorTitle = Config::getMessage(INCOMPLETE_ACCOUNT_DATA_LBL);
                $errorMessage = sprintf(Config::getMessage(SORRY_CREATE_ACCT_COMMUNITY_SPEC_MSG), '<a href="/app/account/profile' . Url::sessionParameter() . '">', '</a>');
                break;
            case 'sso11':
                $errorTitle = Config::getMessage(DUPLICATE_EMAIL_LBL);
                $errorMessage = sprintf(Config::getMessage(SORRY_EMAIL_ADDR_EXS_COMMUNITY_MSG), '<a href="/app/utils/account_assistance' . Url::sessionParameter() . '">', '</a>');
                break;
            case 'sso13':
            case 'sso14':
            case 'sso15':
            case 'sso16':
            case 'sso17':
                $errorTitle = Config::getMessage(AUTHENTICATION_FAILED_LBL);
                $errorMessage = Config::getMessage(LINK_CLICKED_CONTAINED_CMD);
                break;
            case 'saml18':
                $errorTitle = Config::getMessage(AUTHENTICATION_FAILED_LBL);
                $errorMessage = Config::getMessage(ATTEMPT_LOG_SAML_SNGL_SIGN_FAIL_PLS_MSG);
                break;
            case 'saml19':
                $errorTitle = Config::getMessage(COOKIES_ARE_REQUIRED_MSG);
                $errorMessage = Config::getMessage(YOULL_ENABLE_COOKIES_BROWSER_BEF_MSG);
                break;
            case 404:
                $errorTitle = Config::getMessage(NOT_FOUND_UC_LBL);
                $errorMessage = sprintf(Config::getMessage(PAGE_PCT_S_NOT_FOUND_MSG), htmlspecialchars(urldecode(Url::getParameter('url'))));
                break;
            default:
                $errorTitle = Config::getMessage(UNKNOWN_ERR_MSG);
                $errorMessage = Config::getMessage(UNKNOWN_ERR_LBL);
                break;
        }
        return array($errorTitle, $errorMessage);
    }

    /**
     * Gets the list of custom fields for the specified table with the given visibility.
     *
     * @param int $table One of the TBL_-style constants.
     * @param int $visibility One of the VIS_-style constants.
     * @return array List of custom fields
     */
    public static function getCustomFieldList($table, $visibility)
    {
        $key = "getCustomFieldList-$table-$visibility";
        $customFieldList = self::checkCache($key);
        if ($customFieldList === null)
        {
            $customFieldList = Api::cf_get_list($table, $visibility);
            self::setCache($key, $customFieldList);
        }
        return $customFieldList;
    }

    /**
     * Returns true if the specified custom field has End-user Display visibility.
     * @param string $table One of 'Contact', 'Incident' or 'Answer'.
     * @param string $fieldName The column name of the custom field (e.g. mktg_optin)
     * @return boolean True if the field has End-user Display visibility, False if the field wasn't
     *         found or doesn't have End-user Display visibility
     */
    public static function isCustomFieldEnduserVisible($table, $fieldName) {
        return in_array($table, array('Contact', 'Incident', 'Answer')) && ($customField = self::getCustomField($table, $fieldName)) && $customField['enduser_visible'];
    }

    /**
     * Returns true if the specified custom field has both End-user Display and Edit visibility.
     * @param string $table One of 'Contact', 'Incident' or 'Answer'.
     * @param string $fieldName The column name of the custom field (e.g. mktg_optin)
     * @return boolean True if the field has both End-user Display and Edit visibility, False if the field wasn't
     *         found or doesn't have both End-user Display and Edit visibility
     */
    public static function isCustomFieldEnduserWritable($table, $fieldName) {
        return in_array($table, array('Contact', 'Incident', 'Answer')) && ($customField = self::getCustomField($table, $fieldName)) && $customField['enduser_visible'] && $customField['enduser_writable'];
    }

    /**
     * Determines if the passed in ID is numeric and not negative or zero.
     * @param mixed $id ID to check
     * @return bool Whether the ID is a valid format
     */
    public static function isValidID($id)
    {
        return $id && is_numeric($id) && $id > 0 && (is_int($id) || $id === (string)intval($id));
    }

    /**
     * Return filename of $assetPath that includes unique hash value of JavaScript information.
     *
     * @param string $javaScriptPaths JSON of JavaScript included in a particular asset (page or template)
     * @param string $assetPath The path to a particular asset (page or template)
     * @param int $timestamp Timestamp to use to make unique hash
     * @return string Filename of $assetPath that includes unique hash value of JavaScript information.
     * @internal
     */
    public static function calculateJavaScriptHash($javaScriptPaths, $assetPath, $timestamp)
    {
        $includeVersionInPaths = function($item) {
            if(Text::beginsWith($item, 'standard/') || Text::beginsWith($item, 'custom/'))
                return $item . '/' . Widgets::getWidgetVersionDirectory($item);
            else
                return $item;
        };
        return str_replace('.js', '.' . md5($timestamp . '.' . CP_FRAMEWORK_VERSION . '.' . CP_FRAMEWORK_NANO_VERSION . '.' . json_encode(array_map($includeVersionInPaths, $javaScriptPaths))) . '.js', $assetPath);
    }

    /**
     * Sorts an array based on keys provided by a closure which extracts a value from the array's elements.
     * Keys are not preserved.  The original is not modified. Should work with Connect arrays. Inspired by Scala's Seq.sortBy()
     * ({@link http://www.scala-lang.org/api/current/index.html#scala.collection.Seq})
     *
     *
     * @param array|null $array Array to sort. (Really it just needs to be something iterable.)
     * @param bool $descendingOrder Whether the keys should be sorted in ascending or descending order.
     * @param \Closure $keyExtractor Function that will be passed each element of $array to produce a key for sorting. Must return an int or string.
     * @return array A new array sorted as you requested.
     * @throws \Exception If array provided is not iterable
     */
    public static function sortBy($array, $descendingOrder, \Closure $keyExtractor)
    {
        if (is_string($array) || is_numeric($array)) {
            throw new \Exception('The $array argument must be an iterable value.');
        }
        // Another reason for this copy other than not modifying the original is that it allows this function to support anything iterable, like a Connect array.
        $newArray = array();
        foreach ($array as $value) {
            $newArray[] = $value;
        }
        $descendingMultiplier = $descendingOrder ? -1 : 1;
        $usortSucceeded = usort($newArray, function($a, $b) use ($descendingMultiplier, $keyExtractor) {
            $aKey = $keyExtractor($a);
            $bKey = $keyExtractor($b);
            if (is_int($aKey) && is_int($bKey)) {
                return $descendingMultiplier * (($aKey < $bKey) ? (-1) : (($aKey === $bKey) ? (0) : (1)));
            }
            if (is_string($aKey) && is_string($bKey)) {
                return $descendingMultiplier * strcmp($aKey, $bKey);
            }
            throw new \Exception("keyExtractor must return an int or string.");
        });
        if (!$usortSucceeded) {
            throw new \Exception("Unable to sort array.");
        }
        return $newArray;
    }

    /**
     * Return a date/time string formatted per $dateFormat and optionally $timeFormat.
     *
     * @param integer|null $seconds Seconds since the epoch. If null, the current local time will be used.
     * @param string|null $dateFormat The format string specifying the date (e.g. %m/%d/%Y).
     *     If specified as 'default' or null, the format from the DTF_SHORT_DATE config will be used.
     * @param string|null $timeFormat The optional format string specifying the time (e.g. %I:%M %p).
     *     If specified as 'default', the format from the DTF_TIME config will be used.
     *     If specified as null, only the date portion of the timestamp will be returned.
     * @param boolean $includeTimeZone True to include the interface's timezone in the date computation, false to return UTC time
     * @return string The formatted date/time string (e.g. 06/12/2013 09:10 AM).
     */
    public static function formatDate($seconds = null, $dateFormat = 'default', $timeFormat = 'default', $includeTimeZone = true) {
        $CI = get_instance();
        static $defaultTimezoneSet;
        if (!isset($defaultTimezoneSet)) {
            $defaultTimezoneSet = date_default_timezone_set(Config::getConfig(TZ_INTERFACE));
        }
        $format = ($dateFormat === null || $dateFormat === 'default') ? $CI->cpwrapper->cpPhpWrapper->getDtfShortDate() : "$dateFormat";
        if ($timeFormat) {
            $format .= ' ' . ($timeFormat === 'default' ? $CI->cpwrapper->cpPhpWrapper->getDtfTime() : "$timeFormat");
        }
        $seconds = ($seconds === null) ? time() : (int) $seconds;
        return ($includeTimeZone) ? $CI->cpwrapper->cpPhpWrapper->strftime($format, $seconds) : $CI->cpwrapper->cpPhpWrapper->gmstrftime($format, $seconds);
    }

    /**
     * Populate the array with all dates for a given date range
     * @param string $startDate Initial date to start with
     * @param string $endDate End data for date range
     * @param string $interval Interval to generate possible dates - defaults to one day.
     * @param string $format Format for date output
     * @return array all possible date as key and 0 as their value
     */
    public static function createDateRangeArray($startDate, $endDate, $interval = '+1 day', $format = 'Y-m-d') {
        $dates = array();
        $startDate = strtotime($startDate);
        $endDate = strtotime($endDate);
        while($startDate && $startDate <= $endDate) {
            $dates[date($format, $startDate)] = 0;
            $startDate = strtotime($interval, $startDate);
        }
        return $dates;
    }

    /**
     * Checks if the current user is allowed to update an Asset
     * @param String $assetID ID of Asset
     * @return bool If current user owns the asset true else false.
     */
    public static function isContactAllowedToUpdateAsset($assetID = null) {
        $assetID = is_null($assetID) ? Url::getParameter('asset_id') : $assetID;
        return get_instance()->model('Asset')->isContactAllowedToUpdateAsset($assetID);
    }

    /**
     * Wrapper for setting the Location header; filters out unwanted,
     * potentially unsafe characters with a high degree of paranoia. If
     * updating, also update equivalent function in init.php. Currently
     * filters out newline (CRLF) characters (and some encoding
     * variations).
     *
     * @param string $headerValue The value for which to set the Location
     *  header.
     * @param bool $permanent When true, do a permanent (301) redirect.
     */
    public static function setLocationHeader($headerValue, $permanent = false) {
        $location = str_replace(array("\r\n", "\n", "\r", '%0D%0A', '%0D', '%0A', '%5Cr%5Cn', '%5Cr', '%5Cn'), '', $headerValue);
        if($permanent) {
            header("Location: {$location}", true, 301);
        }
        else {
            header("Location: {$location}");
        }
    }

    /**
     * Create SHA-256 hash of a string
     * @param string $message The string to be hashed
     * @return string SHA-256 hash of the string passed
     */
    public static function getSHA2Hash($message) {
        try {
            $md = new Crypto\MessageDigest();
            $md->Algorithm->ID = 3; //SHA256 
            $md->Text = $message;
            $md->Encoding->ID = 1;
            $md->hash();
            $hashedText = $md->HashText;
            return bin2hex($hashedText);
        }
        catch(Exception $err) {
            echo $err->getMessage();
        }
    }

    /**
     * Create security token used to access community file attachments
     * @param integer $fileID The file ID of the community file attachment
     * @param integer $createdTime Created time of the file we need the token for
     * @return string The file token
     */
    public static function createCommunityAttachmentToken($fileID, $createdTime) {
        $sessionID = get_instance()->session->getSessionData('sessionID');
        $attachment = get_instance()->model('FileAttachment')->get($fileID, $createdTime)->result;
        $localFileName = get_instance()->model('FileAttachment')->getRawLocalFileName($fileID, $createdTime);
        $secretKey = self::getSHA2Hash($localFileName . $fileID . $attachment->id . $attachment->table);
        $token = self::getSHA2Hash($sessionID . $secretKey);
        return $token;
    }

    /**
     * Validate security token used to access community file attachments
     * @param integer $fileID The file ID of the community file attachment
     * @param integer $createdTime Created time of the file that the token is being verified for
     * @param string $localfname Local filename of the file that the token is to be verified
     * @return boolean True if the token is valid, false otherwise
     */
    public static function isValidCommunityAttachmentToken($fileID, $createdTime, $localfname = null) {
        if(!($urlToken = $_GET['token'])) {
            return false;
        }
        if($objectIDfromUrl = Url::getParameter('cq')) {
            $objectTable = TBL_SSS_QUESTIONS;
        }
        else {
            $objectIDfromUrl = Url::getParameter('cc');
            $objectTable = VTBL_SSS_QUESTION_COMMENTS;
        }
        $localFileName = $localfname ?: get_instance()->model('FileAttachment')->getRawLocalFileName($fileID, $createdTime);
        $recreatedSecretKey = self::getSHA2Hash($localFileName . $fileID . $objectIDfromUrl . $objectTable);
        $sessionID = get_instance()->session->getSessionData('sessionID');
        $recreatedToken = self::getSHA2Hash($sessionID . $recreatedSecretKey);
        return $urlToken === $recreatedToken;
    }

    /**
     * Redirects HTTP GET requests to /cpgtk/cpgatekeeper.php to retry after some time if there is resource contention
     * @param array $cpConfigs Array of CP configs defined in cpConfig.json
     * @return void
     */
    public static function sendRetryHeadersAndRedirect($cpConfigs) {
        parent::killAllOutputBuffering();
        $retryQueryString = '';
        if(get_instance()->input->get('rc')){
            $retryQueryString = '&rc=' . get_instance()->input->get('rc');
        }
        $nextRequestAfter = (int) $cpConfigs['CP.ServiceRetryable.NextRequestAfter'];
        header('OSVCSTATUS: 503');
        if (!get_instance()->isAjaxRequest()) {
            $originalUrl = URL::getOriginalUrl(false);
            self::setLocationHeader($originalUrl.'/cpgtk/redirect?ru=' . urlencode("$originalUrl{$_SERVER['REQUEST_URI']}?{$_SERVER['argv'][0]}") . '&ra=' . $nextRequestAfter . $retryQueryString);
        }
        exit;
    }

    /**
     * Reads configs defined in cpConfig.json file
     * @return array List of configs
     */
    public static function readCPConfigsFromFile() {
        $cpConfigFilePath = CUSTOMER_FILES . "cpConfig.json";
        return json_decode(FileSystem::isReadableFile($cpConfigFilePath) ? trim(file_get_contents($cpConfigFilePath)) : null, true);
    }

    /**
     * Read config defined in siteConfig.json file.
     * @param string $siteConfig A config defined in siteConfig.json.
     * @return string Value assigned to config.
     */
    public static function getSiteConfigValue($siteConfig) {
        $cache = new \RightNow\Libraries\Cache\Memcache(1800); //expiry time set to 30 mins.
        $siteConfigValue = $cache->get($siteConfig);
        if ($siteConfigValue === false) {
            $siteConfigFilePath = CUSTOMER_CONFIG_FILES . "siteConfig.json";
            $siteConfigValue = FileSystem::isReadableFile($siteConfigFilePath) ? json_decode(trim(file_get_contents($siteConfigFilePath)), true)[$siteConfig] : null;
            //Adding try catch around memcache set calls to handle exception thrown while calling memcache_value_set.
            try {
                $cache->set($siteConfig, $siteConfigValue);
            }
            catch (\Exception $e) {
                //self::logMessage("Exception thrown while calling memcache_value_set: " . $e->getMessage());
                // do nothing
            }
        }
        return $siteConfigValue;
    }

    /**
     * Generates a random password based on the constraints specified.
     * @param array $constraints Constraints or conditions to be fulfilled for password
     * @return string random password which satisfies all constraints specified.
     */
    public static function getRandomPassword($constraints) {
        $password = '';

        // If constraints are present.
        if ($constraints['length']['count'] > 0) {
            $chars = '';
            $sets = array();
            $sets['lowercase'] = 'abcdefghijklmnopqrstuvwxyz';
            $sets['uppercase'] = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $sets['special'] = '&*@%+/!#$^\?';
            $sets['specialAndDigits'] = ':.(){}[]~\'\"0123456789';

            foreach ($sets as $key => $set) {
                $set = str_shuffle($set);
                // Password is appended with substring of length equal to $constraints[$key]['count'] of each shuffled set.
                $password .= substr($set, 0, $constraints[$key]['count']);
                // Remaining characters of each set are appended to $chars, to avoid character repetitions.
                $chars .= substr($set, $constraints[$key]['count']);
            }
            // Remaining length of password required.
            $length = $constraints['length']['count'] - strlen($password);
            $password .= substr(str_shuffle($chars), 0, $length);
        }
        // If no constraints are specified.
        else {
            $length = 10;
            $chars = "0123456789abcdefghijklmnopqrstuvwxyz&*@%+/!#$^\?ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $password .= substr(str_shuffle($chars), 0, $length);
        }
        $password = str_shuffle($password);
        return $password;
    }

    /**
     * Matches input from CUSTOM_CFG_INTERNAL_EMAIL_DOMAIN or CUSTOM_CFG_SEC_INTERNAL_EU_HOSTS
     * to the currently logged in Contact to mark them for Billing Validation
     * @return boolean based on Validation
     */
    public static function validateCustomerPortalAgent() {
        // Matching email domains for Contacts that should have "Customer Portal - Agent" Applied
        try{
            $emailDomainPattern = Connect\Configuration::fetch('CUSTOM_CFG_INTERNAL_EMAIL_DOMAIN');
        }
        catch (\Exception $err ){
            $emailDomainPattern = 0;
        }
        try{
            $contactIpAddressPattern = Connect\Configuration::fetch('CUSTOM_CFG_SEC_INTERNAL_EU_HOSTS');
        }
        catch (\Exception $err ){
            $contactIpAddressPattern = 0;
        }

        if (isset($emailDomainPattern)
            && $emailDomainPattern !== 0) {
            // Check email from value present in Profile Cookie
            if(self::isLoggedIn()) {
                $CI = get_instance();
                $contactEmail = $CI->session->getProfileData("email");
                $patternArr = explode(",", $emailDomainPattern->Value);

                foreach($patternArr as $pattern) {
                    if(Text::beginsWith($pattern, '*')) {
                        $pattern = substr($pattern, 1);
                    }
                    if(stripos($contactEmail, $pattern) !== false) {
                        return true;
                    }
                }
            }
        }

        // Matching custom domains/ip addresses for Contacts that should have "Customer Portal - Agent" Applied
        if (isset($contactIpAddressPattern)
            && $contactIpAddressPattern !== 0) {
            // Grab IP Address of Contact and validate that it fits the regex for IP from CodeIgniter
            $CI = get_instance();
            $ipAddr = $CI->input->ip_address();

            // Will always fail for all IPv6 addresses
            // TODO Add within CI->Input IPv6 validation
            if($CI->input->valid_ip($ipAddr) === true) {
                $contactPatternArr = explode(",", $contactIpAddressPattern->Value);

                foreach ($contactPatternArr as $contactPattern) {
                    // Use ip validation present in Server for validating list
                    if(Api::ipaddr_validate($contactPattern, $ipAddr)) {
                        return true;
                    }
                }
            }
            else {
                return false;
            }
        }
        return false;
    }

    /**
     * Checks whether the contact create is disabled or not
     * @return boolean True if disabled otherwise false
     */
    public static function isContactCreateDisabled() {
        try {
            return Connect\Configuration::fetch('CUSTOM_CFG_DISABLE_CONTACT_CREATE')->Value;
        }
        catch (\Exception $err ) {
            return false;
        }
    }

    /**
     * Checks whether the unauthenticated/anonymous incident create is disabled or not
     * @return boolean True if disabled otherwise false
     */
    public static function isUnauthenticatedIncidentCreateDisabled() {
        try {
            return Connect\Configuration::fetch('CUSTOM_CFG_DISABLE_UNAUTHENTICATED_INCIDENT_CREATE')->Value;
        }
        catch (\Exception $err ) {
            return false;
        }
    }
    
    /**
     * Checks whether f_tok is required or not
     * @return boolean True, by default f_tok is required
     */
    public static function isFormTokenRequired() {
        try {
            return Connect\Configuration::fetch('CUSTOM_CFG_FORM_TOKEN_REQUIRED')->Value;
        }
        catch (\Exception $err ) {
            return true;
        }
    }

     /**
     * Checks the attachment MIME type to validate aganist list of valid file types allowed 
     * @param string $filePath Temporary File Path where the file is avaliable before save
     * @return string which will be the uploaded file MIME type
     */
    public static function getFileMimeType ($filePath = '') {
        $regexp = '/^([a-z\-]+\/[a-z0-9\-\.\+]+)(;\s.+)?$/';
        $cmd = 'file --brief --mime ' . escapeshellarg($filePath) . ' 2>&1';
        if (function_exists('exec'))
        {
            $mime = @exec($cmd, $mime, $returnStatus);
            if ($returnStatus === 0 && is_string($mime) && preg_match($regexp, $mime, $matches))
            {
                $fileType = $matches[1];
            }
            else if($mime == "CDF V2 Document, No summary info; charset=binary")
            {
                $fileType = "application/vnd.ms-outlook";
            }
        }
        return trim($fileType);
    }

    /**
     * Checks whether the valid ExtAndMimeType are found for the File or not
     * @param string $fileName Name of the file to be uploaded
     * @param string $filePath Temporary File Path where the file is avaliable before save
     * @return boolean True if valid MIME type & extension otherwise false
     */
    public static function validateExtAndMimeType($fileName, $filePath) {
        $validFileExtensions = Config::getConfig(VALID_FILE_EXTENSIONS);
        if($validFileExtensions == '*')
        return true;
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $foundValidExt = false;
        $foundValidMimeType = false;
        $mimeType = self::getFileMimeType($filePath);
        $validFileExtensions = explode(',', str_replace(' ', '', strtolower(trim($validFileExtensions))));
        $size = count($validFileExtensions);
        for ($i = 0; $i < $size; $i++) {
            if ($validFileExtensions[$i] === $ext) {
                $foundValidExt = true;
                break;
            }
        }
        $mimeTypeLib = self::getMimes();       
        if(array_key_exists($ext, $mimeTypeLib) && $mimeType != 'text/plain')
        {
            return ($foundValidExt && in_array($mimeType, $mimeTypeLib[$ext]));
        }
        else if($mimeType == 'text/plain')
        {
            return true;
        }
    }

    /**
     * MIME TYPES Master List Array 
     * This function contains an array of mime types. It is used by to validate aganist the returned Mime type from shell file command
     * If match found for that extension then upload is honoured else rejected.
     * If a particular extension is missing than add that to this master list
     * @return array of all the valid Mime types for an uploaded extension from user if exist in this list
     */
    public static function getMimes() {
         return array(
        'hqx'	=>	array('application/mac-binhex40', 'application/mac-binhex', 'application/x-binhex40', 'application/x-mac-binhex40'),
        'cpt'	=>	array('application/mac-compactpro'),
        'csv'	=>	array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain'),
        'bin'	=>	array('application/macbinary', 'application/mac-binary', 'application/octet-stream', 'application/x-binary', 'application/x-macbinary'),
        'dms'	=>	array('application/octet-stream'),
        'lha'	=>	array('application/octet-stream'),
        'lzh'	=>	array('application/octet-stream'),
        'exe'	=>	array('application/octet-stream', 'application/x-msdownload'),
        'class'	=>	array('application/octet-stream'),
        'psd'	=>	array('application/x-photoshop', 'image/vnd.adobe.photoshop'),
        'so'	=>	array('application/octet-stream'),
        'sea'	=>	array('application/octet-stream'),
        'dll'	=>	array('application/octet-stream'),
        'oda'	=>	array('application/oda'),
        'pdf'	=>	array('application/pdf', 'application/force-download', 'application/x-download', 'binary/octet-stream'),
        'ai'	=>	array('application/pdf', 'application/postscript', 'text/plain'),
        'eps'	=>	array('application/postscript', 'text/plain'),
        'ps'	=>	array('application/postscript', 'text/plain'),
        'smi'	=>	array('application/smil', 'text/plain'),
        'smil'	=>	array('application/smil', 'text/plain'),
        'mif'	=>	array('application/vnd.mif', 'text/plain'),
        'xls'	=>	array('application/vnd.ms-excel', 'application/msexcel', 'application/x-msexcel', 'application/x-ms-excel', 'application/x-excel', 'application/x-dos_ms_excel', 'application/xls', 'application/x-xls', 'application/excel', 'application/download', 'application/vnd.ms-office', 'application/msword'),
        'ppt'	=>	array('application/powerpoint', 'application/vnd.ms-powerpoint', 'application/vnd.ms-office', 'application/msword'),
        'pptx'	=> array('application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/x-zip', 'application/zip', 'application/vnd.ms-powerpoint'),
        'wbxml'	=>	array('application/wbxml'),
        'wmlc'	=>	array('application/wmlc'),
        'dcr'	=>	array('application/x-director'),
        'dir'	=>	array('application/x-director'),
        'dxr'	=>	array('application/x-director'),
        'dvi'	=>	array('application/x-dvi'),
        'gtar'	=>	array('application/x-gtar'),
        'gz'	=>	array('application/x-gzip', 'application/gzip'),
        'cpgz'	=>	array('application/x-gzip'),
        'gzip'  =>	array('application/x-gzip'),
        'php'	=>	array('application/x-httpd-php', 'application/php', 'application/x-php', 'text/php', 'text/x-php', 'application/x-httpd-php-source'),
        'php4'	=>	array('application/x-httpd-php'),
        'php3'	=>	array('application/x-httpd-php'),
        'phtml'	=>	array('application/x-httpd-php'),
        'phps'	=>	array('application/x-httpd-php-source'),
        'js'	=>	array('application/x-javascript', 'text/plain'),
        'swf'	=>	array('application/x-shockwave-flash'),
        'sit'	=>	array('application/x-stuffit'),
        'tar'	=>	array('application/x-tar'),
        'tgz'	=>	array('application/x-tar', 'application/x-gzip-compressed'),
        'z'	=>	array('application/x-compress'),
        'xhtml'	=>	array('application/xhtml+xml'),
        'xht'	=>	array('application/xhtml+xml'),
        'zip'	=>	array('application/x-zip', 'application/zip', 'application/x-zip-compressed', 'application/s-compressed', 'multipart/x-zip'),
        'rar'	=>	array('application/x-rar', 'application/rar', 'application/x-rar-compressed'),
        'mid'	=>	array('audio/midi'),
        'midi'	=>	array('audio/midi'),
        'mpga'	=>	array('audio/mpeg'),
        'mp2'	=>	array('audio/mpeg'),
        'mp3'	=>	array('audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'),
        'aif'	=>	array('audio/x-aiff', 'audio/aiff'),
        'aiff'	=>	array('audio/x-aiff', 'audio/aiff'),
        'aifc'	=>	array('audio/x-aiff'),
        'ram'	=>	array('audio/x-pn-realaudio'),
        'rm'	=>	array('audio/x-pn-realaudio'),
        'rpm'	=>	array('audio/x-pn-realaudio-plugin'),
        'ra'	=>	array('audio/x-realaudio'),
        'rv'	=>	array('video/vnd.rn-realvideo'),
        'wav'	=>	array('audio/x-wav', 'audio/wave', 'audio/wav'),
        'bmp'	=>	array('image/bmp', 'image/x-bmp', 'image/x-bitmap', 'image/x-xbitmap', 'image/x-win-bitmap', 'image/x-windows-bmp', 'image/ms-bmp', 'image/x-ms-bmp', 'application/bmp', 'application/x-bmp', 'application/x-win-bitmap', 'application/octet-stream'),
        'gif'	=>	array('image/gif'),
        'jpeg'	=>	array('image/jpeg', 'image/pjpeg'),
        'jpg'	=>	array('image/jpeg', 'image/pjpeg'),
        'jpe'	=>	array('image/jpeg', 'image/pjpeg'),
        'jp2'	=>	array('image/jp2', 'video/mj2', 'image/jpx', 'image/jpm', 'video/3gpp', 'video/mp4'),
        'j2k'	=>	array('image/jp2', 'video/mj2', 'image/jpx', 'image/jpm', 'video/3gpp', 'video/mp4'),
        'jpf'	=>	array('image/jp2', 'video/mj2', 'image/jpx', 'image/jpm', 'video/3gpp', 'video/mp4'),
        'jpg2'	=>	array('image/jp2', 'video/mj2', 'image/jpx', 'image/jpm', 'video/3gpp', 'video/mp4'),
        'jpx'	=>	array('image/jp2', 'video/mj2', 'image/jpx', 'image/jpm', 'video/3gpp', 'video/mp4'),
        'jpm'	=>	array('image/jp2', 'video/mj2', 'image/jpx', 'image/jpm', 'video/3gpp', 'video/mp4'),
        'mj2'	=>	array('image/jp2', 'video/mj2', 'image/jpx', 'image/jpm', 'video/3gpp', 'video/mp4'),
        'mjp2'	=>	array('image/jp2', 'video/mj2', 'image/jpx', 'image/jpm', 'video/3gpp', 'video/mp4'),
        'png'	=>	array('image/png', 'image/x-png'),
        'tiff'	=>	array('image/tiff', 'text/plain'),
        'tif'	=>	array('image/tiff', 'text/plain'),
        'heic' 	=>	array('image/heic', 'text/plain', 'application/octet-stream', 'image/jpeg'),
        'har' 	=>	array('text/html', 'text/plain'),
        'mht' 	=>	array('text/html', 'text/plain'),
        'css'	=>	array('text/css', 'text/plain', 'text/html'),
        'html'	=>	array('text/html', 'text/plain'),
        'htm'	=>	array('text/html', 'text/plain'),
        'shtml'	=>	array('text/html', 'text/plain'),
        'txt'	=>	array('text/plain'),
        'text'	=>	array('text/plain'),
        'log'	=>	array('text/plain', 'text/x-log'),
        'rtx'	=>	array('text/richtext' , 'text/plain'),
        'rtf'	=>	array('text/rtf', 'text/plain'),
        'xml'	=>	array('application/xml', 'text/xml', 'text/plain'),
        'xsl'	=>	array('application/xml', 'text/xsl', 'text/xml' , 'text/plain'),
        'mpeg'	=>	array('video/mpeg', 'video/3gpp', 'video/mp4'),
        'mpg'	=>	array('video/mpeg', 'video/3gpp', 'video/mp4'),
        'mpe'	=>	array('video/mpeg', 'video/mp4'),
        'qt'	=>	array('video/quicktime', 'video/mp4'),
        'mov'	=>	array('video/quicktime', 'video/mp4'),
        'avi'	=>	array('video/x-msvideo', 'video/msvideo', 'video/avi', 'application/x-troff-msvideo', 'video/mp4'),
        'movie'	=>	array('video/x-sgi-movie'),
        'doc'	=>	array('application/msword', 'application/vnd.ms-office', 'text/html', 'text/plain'),
        'docx'	=>	array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/msword', 'application/x-zip', 'text/html', 'text/plain'),
        'dot'	=>	array('application/msword', 'application/vnd.ms-office', 'text/html', 'text/plain'),
        'dotx'	=>	array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/msword', 'text/html', 'text/plain'),
        'xlsx'	=>	array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/vnd.ms-excel', 'application/msword', 'application/x-zip', 'text/html', 'text/plain'),
        'word'	=>	array('application/msword', 'application/octet-stream', 'text/html', 'text/plain'),
        'xl'	=>	array('application/excel', 'text/html', 'text/plain'),
        'eml'	=>	array('message/rfc822', 'application/vnd.ms-outlook' , 'text/plain'),
        'json'  =>	array('application/json', 'text/json', 'text/plain', 'text/html'),
        'pem'   =>	array('application/x-x509-user-cert', 'application/x-pem-file', 'application/octet-stream', 'text/plain'),
        'p10'   =>	array('application/x-pkcs10', 'application/pkcs10' , 'text/plain'),
        'p12'   =>	array('application/x-pkcs12' , 'text/plain'),
        'p7a'   =>	array('application/x-pkcs7-signature', 'text/plain'),
        'p7c'   =>	array('application/pkcs7-mime', 'application/x-pkcs7-mime', 'text/plain'),
        'p7m'   =>	array('application/pkcs7-mime', 'application/x-pkcs7-mime', 'text/plain'),
        'p7r'   =>	array('application/x-pkcs7-certreqresp', 'text/plain'),
        'p7s'   =>	array('application/pkcs7-signature', 'text/plain'),
        'crt'   =>	array('application/x-x509-ca-cert', 'application/x-x509-user-cert', 'application/pkix-cert', 'text/plain'),
        'crl'   =>	array('application/pkix-crl', 'application/pkcs-crl', 'text/plain'),
        'der'   =>	array('application/x-x509-ca-cert', 'text/plain'),
        'kdb'   =>	array('application/octet-stream', 'text/plain'),
        'pgp'   =>	array('application/pgp', 'text/plain'),
        'gpg'   =>	array('application/gpg-keys', 'text/plain'),
        'sst'   =>	array('application/octet-stream', 'text/plain'),
        'csr'   =>	array('application/octet-stream', 'text/plain'),
        'rsa'   =>	array('application/x-pkcs7', 'text/plain'),
        'cer'   =>	array('application/pkix-cert', 'application/x-x509-ca-cert', 'text/plain'),
        '3g2'   =>	array('video/3gpp2', 'video/3gpp', 'video/mp4'),
        '3gp'   =>	array('video/3gp', 'video/3gpp', 'video/mp4'),
        'mp4'   =>	array('video/mp4', 'video/3gpp'),
        'm4a'   =>	array('audio/x-m4a', 'video/3gpp', 'video/mp4'),
        'f4v'   =>	array('video/mp4', 'video/x-f4v', 'video/3gpp'),
        'flv'	=>	array('video/x-flv', 'video/3gpp', 'video/mp4'),
        'webm'	=>	array('video/webm', 'video/3gpp', 'video/mp4', 'video/3gpp'),
        'aac'   =>	array('audio/x-aac', 'audio/aac'),
        'm4u'   =>	array('application/vnd.mpegurl'),
        'm3u'   =>	array('text/plain'),
        'xspf'  =>	array('application/xspf+xml'),
        'vlc'   =>	array('application/videolan', 'video/mp4'),
        'wmv'   =>	array('video/x-ms-wmv', 'video/x-ms-asf'),
        'au'    =>	array('audio/x-au'),
        'ac3'   =>	array('audio/ac3'),
        'flac'  =>	array('audio/x-flac'),
        'ogg'   =>	array('audio/ogg', 'video/ogg', 'application/ogg'),
        'kmz'	=>	array('application/vnd.google-earth.kmz', 'application/zip', 'application/x-zip'),
        'kml'	=>	array('application/vnd.google-earth.kml+xml', 'application/xml', 'text/xml'),
        'ics'	=>	array('text/calendar', 'text/plain'),
        'ical'	=>	array('text/calendar', 'text/plain'),
        'zsh'	=>	array('text/x-scriptzsh', 'text/plain'),
        '7z'	=>	array('application/x-7z-compressed', 'application/x-compressed', 'application/x-zip-compressed', 'application/zip', 'multipart/x-zip', 'application/octet-stream'),
        '7zip'	=>	array('application/x-7z-compressed', 'application/x-compressed', 'application/x-zip-compressed', 'application/zip', 'multipart/x-zip', 'application/octet-stream'),
        'cdr'	=>	array('application/cdr', 'application/coreldraw', 'application/x-cdr', 'application/x-coreldraw', 'image/cdr', 'image/x-cdr', 'zz-application/zz-winassoc-cdr'),
        'wma'	=>	array('audio/x-ms-wma', 'video/x-ms-asf'),
        'jar'	=>	array('application/java-archive', 'application/x-java-application', 'application/x-jar', 'application/x-compressed', 'application/octet-stream'),
        'svg'	=>	array('image/svg+xml', 'image/svg', 'application/xml', 'text/xml', 'text/plain'),
        'vcf'	=>	array('text/x-vcard'),
        'srt'	=>	array('text/srt', 'text/plain'),
        'tr'	=>	array('text/x-tr', 'text/plain'),
        'vtt'	=>	array('text/vtt', 'text/plain'),
        'ico'	=>	array('image/x-icon', 'image/x-ico', 'image/vnd.microsoft.icon'),
        'odc'	=>	array('application/vnd.oasis.opendocument.chart', 'text/plain'),
        'otc'	=>	array('application/vnd.oasis.opendocument.chart-template', 'text/plain'),
        'odf'	=>	array('application/vnd.oasis.opendocument.formula', 'text/plain'),
        'otf'	=>	array('application/vnd.oasis.opendocument.formula-template', 'text/plain'),
        'odg'	=>	array('application/vnd.oasis.opendocument.graphics', 'text/plain'),
        'otg'	=>	array('application/vnd.oasis.opendocument.graphics-template', 'text/plain'),
        'odi'	=>	array('application/vnd.oasis.opendocument.image', 'text/plain'),
        'oti'	=>	array('application/vnd.oasis.opendocument.image-template', 'text/plain'),
        'odp'	=>	array('application/vnd.oasis.opendocument.presentation', 'text/plain'),
        'otp'	=>	array('application/vnd.oasis.opendocument.presentation-template', 'text/plain'),
        'ods'	=>	array('application/vnd.oasis.opendocument.spreadsheet', 'text/plain'),
        'ots'	=>	array('application/vnd.oasis.opendocument.spreadsheet-template', 'text/plain'),
        'odt'	=>	array('application/vnd.oasis.opendocument.text', 'text/plain'),
        'odm'	=>	array('application/vnd.oasis.opendocument.text-master', 'text/plain'),
        'ott'	=>	array('application/vnd.oasis.opendocument.text-template', 'text/plain'),
        'oth'	=>	array('application/vnd.oasis.opendocument.text-web', 'text/plain'),
        'vsd'   =>	array('application/vnd.ms-office', 'text/plain'),
        'dwg'   =>	array('image/vnd','image/x-dwg','image/vnd.dwg','application/octet-stream'),
        'mpp'   =>	array('application/vnd.ms-office', 'text/plain'),
        'pjpeg' =>	array('image/jpeg'),
        'pkg'   =>	array('application/x-newton-compatible-pkg'),
        'key'   =>	array('application/zip'),
        'dat'   =>	array('application/octet-stream', 'text/plain'),
        'xps'   => array('application/octet-stream', 'text/plain'),
        'nfo'   => array('text/xml', 'text/plain', 'text/x-nfo', 'application/xml'),
        'sql'   =>	array('text/plain','application/octet-stream'),
        'ini'   =>	array('text/plain','application/textedit','zz-application/zz-winassoc-ini'),
        'cap'   =>	array('text/plain','application/octet-stream'),
        'ntar'   =>	array('text/plain','application/octet-stream'),
        'pcap'   =>	array('text/plain','application/octet-stream'),
        'sms'   =>	array('text/plain', 'text/x-vcard', 'text/richtext', 'text/rtf', 'application/vnd.3gpp2.sms'),
        'msg'   => array('application/vnd.ms-outlook', 'text/plain'),
        'xlsb'  =>	array('application/vnd.ms-excel', 'application/vnd.ms-excel', 'application/msexcel', 'application/x-msexcel', 'application/x-ms-excel', 'application/x-excel', 'application/x-dos_ms_excel', 'application/xls', 'application/x-xls', 'application/excel', 'application/download', 'application/vnd.ms-office', 'application/msword', 'text/plain'),
        'xlsm'  =>	array('application/vnd.ms-excel', 'application/vnd.ms-excel', 'application/msexcel', 'application/x-msexcel', 'application/x-ms-excel', 'application/x-excel', 'application/x-dos_ms_excel', 'application/xls', 'application/x-xls', 'application/excel', 'application/download', 'application/vnd.ms-office', 'application/msword', 'text/plain'),
        'xltx'  => array('application/vnd.ms-excel', 'application/vnd.ms-excel', 'application/msexcel', 'application/x-msexcel', 'application/x-ms-excel', 'application/x-excel', 'application/x-dos_ms_excel', 'application/xls', 'application/x-xls', 'application/excel', 'application/download', 'application/vnd.ms-office', 'application/msword', 'text/plain'),
        'net'   => array('application/zip'),
        'saz'   => array('application/zip'),
        'evtx'  => array('application/zip'),
        'c14'   => array('application/octet-stream'),
        'mergex' => array('application/xml'),
        'etc'   => array('application/xml'),
        'checkx' => array('application/xml'),
        'p1d'   => array('application/octet-stream'),
        'bplt'  => array('application/octet-stream'),
        'sopas' => array('application/octet-stream'),
        'eif'   => array('application/zip'),
        'sgva'  => array('application/zip'),
        'eax'   => array('application/xml'),
        'hyz'   => array('application/zip'),
        'crdownload' => array('application/pdf')
        );
    }
}
