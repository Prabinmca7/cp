<?php
namespace RightNow\Libraries;
use RightNow\Utils\Url,
    RightNow\Api;

/**
 * Reads and provides data about the current user's session. Also provides information about the currently logged in user. Methods within this class
 * can be accessed by using
 *
 *      get_instance()->session->{method}
 */
class Session
{
    private $CI;

    /**
     * Object of session information
     */
    private $sessionData;

    /**
     * Object of profile information
     */
    private $profileData;

    /**
     * Denotes if setting of session cookies is allowed
     */
    private $canSetSessionCookie;

    /**
     * The amount of time the user can be idle before session ID expires
     */
    private $sessionIdleLength;

    /**
     * The maximum value for a visitor session length
     */
    private $sessionLengthLimit;

    /**
     * The amount of time the user can be idle before profile cookie expires (i.e. length of time
     * set on profile cookie when sent to client)
     */
    private $profileCookieLength;

    /**
     * The max amount of time the user can be logged in for
     */
    private $profileCookieLimit;

    /**
     * Name to use when writing out session cookie name
     */
    private $sessionCookieName = 'cp_session';

    /**
     * Name to use when writing out profile cookie name
     */
    private $profileCookieName = 'cp_profile';

    /**
     * Name to use when writing out profile flag cookie name
     */
    private $profileFlagCookieName = 'cp_profile_flag';

    /**
     * Denotes if user is currently logged in
     */
    private $isLoggedIn;

    /**
     * Denotes if a new session ID has been generated
     */
    private $newSession = false;

    /**
     * Denotes if we should store URL parameters for the current request
     */
    private $cullUrlParameters = true;

    /**
     * The original profile cookie data that's checked later to determine if anything has changed
     */
    private $profileCookieData;
    /**
     * Temp data that's set for the next request.
     * @var FlashData
     */
    private $flashData;

    /**
     * Upper cookie length limit before we start trimming our cookie
     */
    const COOKIE_MAX_LENGTH = 4000;

    /**
     * Ensures that only a single instance of this class is instantiated
     * @param bool $haltSession Whether or not fully initialize the session class
     * @return RightNow\Libraries\Session Instance of Session class
     * @internal
     */
    public static function getInstance($haltSession = false)
    {
        static $instance = null;
        if (null === $instance)
            $instance = new Session($haltSession);
        return $instance;
    }

    /**
     * Creates session instance
     * @param bool $haltSession Whether or not fully initialize the session class
     * @internal
     */
    private function __construct($haltSession)
    {
        $this->CI = get_instance();
        if($this->CI->router->fetch_class() !== 'page')
            $this->cullUrlParameters = false;
        $this->ignoreOfficeRequests();
        if($haltSession)
        {
            $this->canSetSessionCookie = false;
            return;
        }
        $this->setSessionTimeouts();
        $this->setProfileCookieTimeouts();
        $this->canSetSessionCookie = \RightNow\Utils\Config::getConfig(CP_COOKIES_ENABLED);
        //Attempt to read cookies
        $this->readSessionCookie();
        $this->readProfileCookie();

        //Process information in cookies
        if(is_object($this->sessionData))
            $this->processSession();
        else
            $this->processNoCookie();

        $this->addUrlParmsToSession();

        //Write out cookie information
        $this->writeSession();
        $this->writeProfile();

        //If we have one, tell Connect what the users session ID is so they can use it for various things. Currently they're only
        //using it for populating the incidents.sessionid column when an incident is created, but they'll probably use it for more
        //things in the future so we want to set it as early as possible
        if($sessionID = $this->getSessionData('sessionID')){
            \RightNow\Connect\v1_4\CustomerPortal::setCustomerPortalSession($sessionID);
        }
    }

    /**
     * Decrypts and decodes the session cookie if it exists.
     * @return void
     */
    private function readSessionCookie()
    {
        $sessionCookie = $this->CI->input->cookie($this->sessionCookieName);
        if($sessionCookie && $this->canSetSessionCookie)
        {
            $sessionCookie = @json_decode($this->stripSlashes(Api::ver_ske_decrypt($sessionCookie)), true);
            if(!is_array($sessionCookie) || !$sessionData = SessionData::newFromCookie($sessionCookie['s'])) return;
            $this->sessionData = $sessionData;
            $this->sessionData->cookiesEnabled = true;
            $this->flashData = new FlashData($sessionCookie['f']);
        }
    }

    /**
     * Processes the logic needed to persist session information when either the user
     * has cookies turned off or the config value says to not set cookies.
     * @return void
     */
    private function processNoCookie()
    {
        $this->sessionData = $this->sessionData ?: new \stdClass();
        $this->sessionData->cookiesEnabled = false;
        $this->getSessionFromURL();
        if((!$this->sessionData->sessionID) || ($this->sessionData->lastActivity && (($this->sessionData->lastActivity + $this->sessionIdleLength) < time()))
            || ($this->sessionData->sessionGeneratedTime && (($this->sessionData->sessionGeneratedTime + $this->sessionLengthLimit) < time()))){
            $this->generateNewSession();
        }

        //Only log user in if a profile was returned and they have a cookie set
        if(is_object($this->profileData) && ($profile = $this->verifyContactLogin()))
        {
            if($this->getProfileData('ptaLoginUsed'))
                $this->sessionData->ptaUsed = true;

            // Preserve openLogin info from cookie
            if ((!isset($profile->openLoginUsed) || !$profile->openLoginUsed) && is_array($this->profileData->openLoginUsed))
                $profile->openLoginUsed = $this->profileData->openLoginUsed;
            
            // Preserve SAML Login info from cookie
            if ($this->getProfileData('samlLoginUsed'))
                $profile->samlLoginUsed = $this->profileData->samlLoginUsed;
            
            $this->profileData = $this->createMapping($profile);
            //Since we have a profile, we know cookies are enabled
            $this->sessionData->cookiesEnabled = true;
        }
        // If no profile, we need to invalidate the cookie profile to avoid an
        // elevation of privilege attack.
        else if(is_object($this->profileData))
        {
            $this->destroyProfile();
            $this->profileData = null;
        }

        //Reset last activity to current time
        $this->sessionData->lastActivity = time();

        $sessionString = '';
        if($this->sessionData->ptaUsed)
            $sessionString .= '/pta/1';
        if($this->sessionData->answersViewed)
            $sessionString .= '/av/' . $this->sessionData->answersViewed;
        if($this->sessionData->questionsViewed)
            $sessionString .= '/qv/' . $this->sessionData->questionsViewed;
        if($this->sessionData->numberOfSearches)
            $sessionString .= '/sno/' . $this->sessionData->numberOfSearches;
        $sessionString .= '/time/' . $this->sessionData->lastActivity;
        $sessionString .= '/gen/' . $this->sessionData->sessionGeneratedTime;

        $this->sessionData->sessionString = '/session/' . base64_encode("$sessionString/sid/" . $this->createUrlSafeSessionID($this->sessionData->sessionID));
    }

    /**
     * Verifies $sessionID matches the expected pattern.
     * @param string $sessionID The session ID
     * @return bool True if $sessionID matches the expected pattern.
     */
    private function isValidSessionID($sessionID) {
        return preg_match('@^([-0-9a-zA-Z_.*]{8}|[-0-9a-zA-Z_.*]{10})$@', $sessionID) ? true : false;
    }

    /**
     * Returns the results of API::contact_login_verify, either the Contact profile object or null if the session ID/auth token does not match a user.
     * A new session is generated if either the sessionID does not match the expected pattern,
     * or the API call nulls out the sessionID, signifying the session has expired.
     * @return Object|null The Contact profile object or null.
     */
    private function verifyContactLogin() {
        if (!$this->isValidSessionID($this->sessionData->sessionID)) {
            $this->generateNewSession();
        }

        $profile = Api::contact_login_verify($this->sessionData->sessionID, $this->getProfileData('authToken'));

        $this->setSocialUser($profile);

        if(!$this->sessionData->sessionID) {
            //The API has expired the session ID, generate a new one
            $this->generateNewSession();
        }

        return $profile;
    }

    /**
     * After reading the cookie, it will be processed to update values based on url
     * parameters and to replace expired session ID's
     * @return void
     */
    private function processSession()
    {
        if(($this->sessionData->lastActivity && (($this->sessionData->lastActivity + $this->sessionIdleLength) < time()))
            || ($this->sessionData->sessionGeneratedTime && (($this->sessionData->sessionGeneratedTime + $this->sessionLengthLimit) < time()))){
            $this->generateNewSession();
        }
        //Only log user in if a profile was returned and they have a cookie set
        if(is_object($this->profileData) && ($profile = $this->verifyContactLogin()))
        {
            if($this->getProfileData('ptaLoginUsed'))
                $this->sessionData->ptaUsed = true;
            $profile->forcefulLogoutTime = $this->profileData->forcefulLogoutTime;
            
            // Preserve SAML Login info from cookie
            if ($this->getProfileData('samlLoginUsed'))
                $profile->samlLoginUsed = $this->profileData->samlLoginUsed;
            
            $this->profileData = $this->createMapping($profile, false, true);
        }
        // If no profile, we need to invalidate the cookie profile to avoid an
        // elevation of privilege attack.
        else if(is_object($this->profileData))
        {
            $this->destroyProfile();
            $this->profileData = null;
        }

        //Reset last activity to current time
        $this->sessionData->lastActivity = time();
        //Add information about answers viewed and number of searches
        $urlParms = $this->CI->uri->segment_array();
        $searchNubmerParameter = 0;
        $searchParameter = 0;
        for($i = 1; $i < count($urlParms); $i++)
        {
            if($urlParms[$i] === 'a_id')
                $this->sessionData->answersViewed += 1;
            if($urlParms[$i] === 'qid')
                $this->sessionData->questionsViewed += 1;
            if($urlParms[$i] === 'sno')
                $searchNubmerParameter = $urlParms[$i + 1];
            if($urlParms[$i] === 'search')
                $searchParameter = $urlParms[$i + 1];
        }
        if($searchNubmerParameter)
            $this->sessionData->numberOfSearches = $searchNubmerParameter;
        else if($searchParameter)
            $this->sessionData->numberOfSearches += 1;
    }

    /**
     * Returns whether the cookie data is valid, based on whether the interface specified in the cookie matches
     * the ID of the current interface.
     *
     * @param array $cookieData Cookie data (either session or profile)
     * @return bool Whether the cookie data specifies the current interface
     */
    private function verifyCookieInterface(array $cookieData) {
        return array_key_exists('i', $cookieData) && (intval($cookieData['i']) === Api::intf_id());
    }

    /**
     * Returns the value of the property in the SessionData object specified by $index. If the value does not
     * exist, it will return false.
     *
     * @param string $index The name of the property to return
     * @return mixed Either the value of the session property, or false if it does not exist
     * @see SessionData For the list of valid indices to this method.
     */
    public function getSessionData($index)
    {
        if(is_object($this->sessionData) && (property_exists($this->sessionData, $index) || ($this->sessionData instanceof SessionData && $this->sessionData->dynamicPropertyExists($index))))
            return $this->sessionData->$index;
        return false;
    }

    /**
     * Updates the session object with the values passed in.
     *
     * @param array|null $data An associative array of session property=>value.
     * @return void
     * @see SessionData For the list of valid indices to this method.
     */
    public function setSessionData($data)
    {
        if(is_array($data) && count($data) > 0)
        {
            $this->sessionData = $this->sessionData ?: new \stdClass();
            foreach($data as $key => $value){
                if($key === 'previouslySeenEmail'){
                    $value = \RightNow\Utils\Text::escapeHtml($value);
                }
                $this->sessionData->$key = $value;
            }
        }
        $this->writeSession();
    }

    /**
     * Sets the Social User ID for the logged-in contact.
     * @param Object $profile The profile object
     */
    public function setSocialUser($profile) {
        if (is_object($profile) && (!isset($profile->socialUserID) || !$profile->socialUserID) && ($user = $this->CI->model('CommunityUser')->getForContact($profile->c_id)->result)) {
            $profile->socialUserID = $user->ID;
        }
    }

    /**
     * Passes the session information in the headers to be stored as a cookie
     * @return void
     */
    private function writeSession()
    {
        if($this->canSetSessionCookie)
        {
            $infoToStash = array(
                'f' => $this->flashData->convertToCookie(),
                's' => $this->sessionData->convertToCookie(),
            );

            $cookieData = Api::ver_ske_encrypt_fast_urlsafe(json_encode($infoToStash));

            // This is a soft limit, start resetting cookie parts after the max is reached. mb_strlen is
            // more accurate but expensive.
            if(strlen($cookieData) >= self::COOKIE_MAX_LENGTH)
            {
                Api::phpoutlog("Maximum cookie size limit reached: " . self::COOKIE_MAX_LENGTH);
                Api::phpoutlog("Reset session data's `urlParameters` to accomodate maximum cookie limit");
                $this->sessionData->urlParameters = array();
                $infoToStash['s'] = $this->sessionData->convertToCookie();

                if (strlen($cookieData) >= self::COOKIE_MAX_LENGTH) {
                    Api::phpoutlog("Reset session's flash data to accomodate maximum cookie limit");
                    $infoToStash['f'] = array();
                }
                $cookieData = Api::ver_ske_encrypt_fast_urlsafe(json_encode($infoToStash));
            }
            // in case CP_FORCE_PASSWORDS_OVER_HTTPS is enabled, just use SEC_END_USER_HTTPS to set secure option of cookie
            \RightNow\Utils\Framework::setCPCookie($this->sessionCookieName, $cookieData, 0, '/', '', true, \RightNow\Utils\Config::getConfig(SEC_END_USER_HTTPS, 'COMMON'));
        }
    }

    /**
     * Deletes all private information out of the session cookie except the
     * current session ID.
     * @return void
     */
    private function destroySession()
    {
        $this->sessionData->answersViewed = 0;
        $this->sessionData->questionsViewed = 0;
        $this->sessionData->numberOfSearches = 0;
        $this->sessionData->urlParameters = array();
        $this->sessionData->previouslySeenEmail = '';
        $this->sessionData->ptaUsed = false;
        $this->sessionData->lastActivity = 0;
        $this->sessionData->sessionGeneratedTime = 0;
        $this->sessionData->recentSearches = array();
        $this->sessionData->okcsAnswersViewed = array();
        $this->sessionData->discQuestionsViewed = array();
        $this->sessionData->userCacheKey = '';
        $this->sessionData->sessionString = '/session/' . base64_encode("sid/" . $this->createUrlSafeSessionID($this->sessionData->sessionID) . "/time/" . $this->sessionData->lastActivity);
        $this->writeSession();
    }

    /**
     * Decrypts and decodes the profile cookie if it exists.
     * @return void
     */
    private function readProfileCookie()
    {
        $profileCookie = $this->CI->input->cookie($this->profileCookieName);
        if($profileCookie)
        {
            $this->profileCookieData = $profileCookie = @json_decode(trim(Api::ver_ske_decrypt($profileCookie)), true);
            if (!$profile = ProfileData::newFromCookie($profileCookie)) return;

            //If we have a login limit, try and read the existing value out of the cookie, otherwise just default it to the current time
            if($this->profileCookieLimit !== 0 || $profile->forcefulLogoutTime)
            {
                $now = time();
                $profile->loginStartTime = $profile->loginStartTime ?: $now;

                // If the user has exceeded either,
                // - the max time allowed to be logged in (see #setProfileCookieTimeouts), or
                // - there was a hard-limit set in the cookie (see #createProfileCookieWithExpiration)
                // then log them out
                if (($this->profileCookieLimit && $profile->loginStartTime + $this->profileCookieLimit < $now) ||
                    ($profile->forcefulLogoutTime && $profile->loginStartTime + $profile->forcefulLogoutTime < $now))
                {
                    $this->destroyProfile();
                    return;
                }
            }
            $this->profileData = $profile;
            if($this->profileData->ptaLoginUsed && is_object($this->sessionData))
                $this->sessionData->ptaUsed = true;
        }
    }

    /**
     * Returns the value of the property in the profile specified by $index. If the value does not
     * exist, it will return false.
     *
     * @param string $index The name of the property to return
     * @return mixed Either the value of the profile property, or false if it does not exist
     * @see ProfileData For the list of valid indices to this method.
     */
    public function getProfileData($index)
    {
        if(is_object($this->profileData))
            return $this->profileData->{$index};
        return false;
    }

    /**
     * Returns the ProfileData object or null if it does not exist
     * @param boolean $getAsFlatObject Denotes if return should be a flat object instead of the backward-compatible object with a 'value' subkey. Recommended.
     * @return ProfileData Either the profile object, or null if it does not exist
     */
    public function getProfile($getAsFlatObject = false)
    {
        if(is_object($this->profileData)){
            if($getAsFlatObject){
                return $this->profileData;
            }
            return $this->profileData->getComplexMappedObject();
        }
        return null;
    }

    /**
     * Assigns the passed in profile to the profile property and attempts to write
     * out the profile cookie.
     * @param object $profile An instance of the profile object to store
     * @return void
     * @internal
     */
    public function createProfileCookie($profile)
    {
        $this->createCookieForProfile($profile);
    }

    /**
     * Assigns the passed in profile to the profile property and writes
     * the profile cookie. Sets the cookie to forcefully expire at
     * the specified interval.
     * @param object $profile An instance of the profile object to store
     * @param int $expireTime Seconds in which the cookie should expire
     * @return void
     * @internal
     */
    public function createProfileCookieWithExpiration($profile, $expireTime) {
        $this->createCookieForProfile($profile, $expireTime);
    }

    /**
     * Determines if the value is a valid login.
     * @param String $login Login value
     * @return Boolean True if the value is valid
     */
    private static function loginIsValid($login) {
        return $login !== '' && $login !== false && $login !== null;
    }

    /**
     * Creates the profile cookie for #createProfileCookieWithExpiration and #createProfileCookie
     * @param object $profile Profile object
     * @param int|null $expireTime Seconds in which the cookie should expire.
     * @return void
     */
    private function createCookieForProfile($profile, $expireTime = null) {
        $profile->loginStartTime = ($this->profileCookieLimit === 0) ? 0 : time();
        $this->profileData = $profile;
        $this->writeProfile($expireTime);
    }

    /**
     * Attempts to write out the information in the profile to a encrypted/encoded cookie. The information is
     * converted into an array because of size constraints
     * @param int|null $expireTime Seconds in which the cookie should forcefully expire (regardless of any other config)
     * @return void
     */
    private function writeProfile($expireTime = null)
    {
        if($this->profileData)
        {
            $loginValue = $this->getProfileData('login');
            if (!self::loginIsValid($loginValue)) {
                $this->destroyProfile();
                return;
            }

            $expireTime || ($expireTime = $this->profileData->forcefulLogoutTime);
            $profileCookieInformation = $this->profileData->convertToCookie(array(
                'forcefulLogoutTime' => $expireTime,
                // Write out login start time if there is a limit. Either use the existing value, or set it to the current time
                'loginStartTime'     => ($this->profileCookieLimit !== 0 || $expireTime) ? ($this->profileData->loginStartTime ?: time()) : null,
            ));
            // If any of the profile data has changed then re-encrypt the new cookie value;
            // otherwise, save some work and just persist the unchanged cookie value.
            $cookieData = ($this->profileCookieData === $profileCookieInformation)
                ? $this->CI->input->cookie($this->profileCookieName)
                : Api::ver_ske_encrypt_urlsafe(json_encode($profileCookieInformation));

            $cookieExpireTime = ($this->profileCookieLength === 0) ? 0 : time() + $this->profileCookieLength;
            
            // cp_profile cookie should be set regardless of mismatch on Request vs Configuration
            \RightNow\Utils\Framework::setCPCookie($this->profileCookieName, $cookieData, $cookieExpireTime, '/', '', true, Url::isRequestHttps());
            if(Url::isRequestHttps() && !\RightNow\Utils\Config::getConfig(SEC_END_USER_HTTPS, 'COMMON'))
                \RightNow\Utils\Framework::setCPCookie($this->profileFlagCookieName, '1', $cookieExpireTime, '/', '', true, false);

            //Update the time in the DB of when the users cookie will expire. If the
            //profile is set to expire at the end of the session, this value just gets
            //set to the CP_LOGIN_MAX_TIME. If that value is set to 0, there really isn't any
            //sort of hard deadline for when their cookie will expire, so we just set it to
            //a far future date (1 year in the future)
            if($cookieExpireTime === 0)
                $cookieExpireTime = ($this->profileCookieLimit !== 0) ? time() + $this->profileCookieLimit : time() + 60 * 60 * 24 * 365;
            Api::contact_login_update_cookie(array(
                'login' => $loginValue,
                'expire_time' => $cookieExpireTime,
            ));
        }
    }

    /**
     * Indicates if the profile flag cookie is set. This denotes that the user supports cookies in their browser.
     * @return bool Whether the profile flag cookie is set
     */
    public function isProfileFlagCookieSet()
    {
        return $this->CI->input->cookie($this->profileFlagCookieName) === '1';
    }

    /**
     * Destroys the profile cookie by setting its expiration date to the past.
     * @return void
     * @internal
     */
    public function destroyProfile()
    {
        $loginValue = $this->getProfileData('login');
        if(self::loginIsValid($loginValue)) {
            Api::contact_login_update_cookie(array(
                'login' => $loginValue,
                'expire_time' => -1,
            ));
        }
        \RightNow\Utils\Framework::destroyCookie($this->profileCookieName);
        \RightNow\Utils\Framework::destroyCookie($this->profileFlagCookieName);
        $this->profileData = null;
    }

    /**
     * Sets both the idle and max timeouts required for profile cookies
     * @return void
     */
    private function setProfileCookieTimeouts()
    {
        $loginLength = \RightNow\Utils\Config::getConfig(CP_LOGIN_COOKIE_EXP);
        $loginLengthMax = \RightNow\Utils\Config::getConfig(CP_LOGIN_MAX_TIME);

        //If the length is set to 0, we set the expire time to last 10 years (i.e. eternity)
        if($loginLength === 0)
            $this->profileCookieLength = 60 * 60 * 24 * 365 * 10;
        //If the length is set to -1, then we set the login cookie to expire when the browser is closed
        else if($loginLength === -1)
            $this->profileCookieLength = 0;
        //Otherwise, take the config value and convert it to seconds
        else
            $this->profileCookieLength = $loginLength * 60;

        //A setting of 0 means we dont have a limit
        if($loginLengthMax === 0)
        {
            $this->profileCookieLimit = 0;
        }
        //Otherwise the limit is the config setting converted to seconds
        else
        {
            $this->profileCookieLimit = $loginLengthMax * 60;
        }
    }

    /**
     * Sets the idle timeout for session cookies
     * @return void
     */
    private function setSessionTimeouts()
    {
        $billableSessionLength = \RightNow\Utils\Config::getConfig(BILLABLE_SESSION_LENGTH);
        //Force the maximum value of visitor session to be 12 hours and the minimum value to be the BILLABLE_SESSION_LENGTH config
        $sessionLengthLimit = min(max(\RightNow\Utils\Config::getConfig(VISIT_MAX_TIME), $billableSessionLength), 720);
        $sessionLength = \RightNow\Utils\Config::getConfig(VISIT_INACTIVITY_TIMEOUT);

        //Force visitor session length to be greater or equal to billable session length
        if($sessionLength < $billableSessionLength)
            $sessionLength = $billableSessionLength;

        //Force visitor session length to be less than or equal to max visitor session length
        if($sessionLength > $sessionLengthLimit)
            $sessionLength = $sessionLengthLimit;

        $this->sessionLengthLimit = $sessionLengthLimit * 60;
        $this->sessionIdleLength = $sessionLength * 60;
    }

    /**
     * Returns the amount of time that the current session ID will last if the user is idle
     * @return int The length of time in seconds
     */
    public function getSessionIdleLength()
    {
        return $this->sessionIdleLength;
    }

    /**
     * Returns the maximum amount of time that the current session ID will last irrespective of the user status (idle/active)
     * @return int The length of time in seconds
     */
    public function getSessionLengthLimit()
    {
        return $this->sessionLengthLimit;
    }

    /**
     * Returns the amount of time that the current profile cookie will last
     * @return int The length of time in seconds
     */
    public function getProfileCookieLength()
    {
        return $this->profileCookieLength;
    }

    /**
     * Checks if there is a profile cookie set, meaning the user is logged in
     *
     * @return bool True if user is logged in, otherwise returns false
     * @internal
     */
    public function isLoggedIn()
    {
        return is_object($this->profileData);
    }

    /**
     * Returns if cookies are required in order for users to log in. This is now the default behavior
     * so this function will always return true
     * @return bool True if cookies are required, otherwise returns false
     */
    public function isRequired()
    {
        return true;
    }

    /**
     * Returns if session cookies are not to be set
     * @return bool True if cookies cannot be set, false otherwise
     */
    public function isDisabled()
    {
        return !$this->canSetSessionCookie;
    }

    /**
     * Returns if session cookies are allowed to be set
     * @return bool True if cookies can be set, false otherwise
     */
    public function canSetSessionCookies()
    {
        return $this->canSetSessionCookie;
    }

    /**
     * Returns value if new session ID has been generated, either because this is the first
     * page hit, or their session has expired.
     *
     * @return bool True if new session, false otherwise
     */
    public function isNewSession()
    {
        return $this->newSession;
    }

    /**
     * Sets _Flashdata_: session data that's only available for the next server request
     * and then automatically deleted. Typically used to store an info / status
     * message to display on the next page load.
     * **After being set, flashdata is only available on the next page if the user's
     * browser cookies are enabled.** Because of this, your pages should not be entirely
     * reliant upon flashdata being present.
     *
     *      get_instance()->session->setFlashData( "info", "Your account has been created!" );
     *      get_instance()->session->setFlashData( array( "info" => "Please check your email", "alert" => "Verify your account" ) );
     *
     * @param string|array $data  String key of item to set or an array of
     * keys and values to set
     * @param string|null $value Value to set if $data is a string key
     */
    public function setFlashData($data, $value = null) {
        if (is_string($data) && !is_null($value)) {
            $data = array($data => $value);
        }

        if (is_array($data) && count($data)) {
            $this->flashData = $this->flashData ?: new \stdClass();
            foreach ($data as $key => $value) {
                $this->flashData->$key = $value;
            }

            $this->writeSession();
        }
    }

    /**
     * Returns the flashdata stored for the key.
     * @param  string $key Key for flashdata item
     * @return mixed       Value for key
     */
    public function getFlashData($key) {
        return $this->flashData->$key;
    }

    /**
     * Mark a flashdata item to prevent it from
     * being deleted so that it's still present
     * on the next server request.
     *
     *      get_instance()->session->keepFlashData( "info" );
     *      get_instance()->session->keepFlashData( array( "info", "alert" ) );
     *
     * @param  string|array $key String key of item to preserve or
     * array consisting of keys of items to preserve
     */
    public function keepFlashData($key) {
        if (is_string($key)) {
            $key = array($key);
        }

        if (is_array($key) && count($key)) {
            foreach ($key as $keyToKeep) {
                $this->flashData->keep($keyToKeep);
            }
        }
    }

    /**
     * Converts the API profile object to a CP ProfileData object
     *
     * @param array|null $apiProfile Profile array returned from the contact_login API
     * @param bool $useApiSessionId Denotes if the session ID returned from the contact_login API should be used as the sessionID.
     * @param  bool $includePersistentProperties Whether to include properties in the current profile data that are only
     *                                           persisted within the profile cookie
     * @return ProfileData|null Generated ProfileData object or null if no profile is passed in
     * @internal
     */
    public function createMapping($apiProfile, $useApiSessionId = false, $includePersistentProperties = false)
    {
        if($apiProfile)
        {
            if (is_array($apiProfile))
            {
                $apiProfile = (object) $apiProfile;
            }
            $profile = new ProfileData();
            $profile->contactID      = $apiProfile->c_id;
            $profile->login          = $apiProfile->login;
            $profile->email          = $apiProfile->email;
            $profile->firstName      = isset($apiProfile->first_name) ? $apiProfile->first_name : '';
            $profile->lastName       = isset($apiProfile->last_name) ? $apiProfile->last_name : '';
            $profile->disabled       = $apiProfile->disabled ?: null;
            $profile->orgID          = isset($apiProfile->org_id) ? $apiProfile->org_id : null;
            $profile->slai           = $apiProfile->slai ?: null;
            $profile->slac           = $apiProfile->slac ?: null;
            $profile->webAccess      = $apiProfile->web_access ?: null;
            $profile->authToken      = $apiProfile->cookie;
            $profile->orgLevel = $apiProfile->o_lvlN;
            $profile->ptaLoginUsed = $this->sessionData->ptaUsed;
            $profile->openLoginUsed = isset($apiProfile->openLoginUsed) ? $apiProfile->openLoginUsed : null;
            $profile->samlLoginUsed  = isset($apiProfile->samlLoginUsed) ? $apiProfile->samlLoginUsed : null;
            $profile->forcefulLogoutTime = isset($apiProfile->forcefulLogoutTime) ? $apiProfile->forcefulLogoutTime : null;
            $profile->socialUserID  = isset($apiProfile->socialUserID) ? $apiProfile->socialUserID : null;
            //Either persist existing login start time, or reset it to current time
            if($this->profileData)
                $profile->loginStartTime = ($this->profileData->loginStartTime !== null) ? $this->profileData->loginStartTime : time();
            else
                $profile->loginStartTime = time();
            if ($useApiSessionId)
                $this->sessionData->sessionID  = $apiProfile->sessionid;

            if ($includePersistentProperties && $this->profileData)
            {
                foreach (ProfileData::getPersistentPropertyKeys() as $propertyName)
                {
                    if (!$profile->{$propertyName} && ($propertyValue = $this->profileData->{$propertyName}))
                    {
                        $profile->{$propertyName} = $propertyValue;
                    }
                }
            }

            return $profile;
        }
    }

    /**
     * Destroys the profile cookie and all personal information in the session cookie. Does not destroy the users current
     * session ID.
     * @return void
     */
    public function performLogout()
    {
        $this->destroyProfile();
        $this->destroySession();
    }

    /**
     * Adds information to the current profile to denote that the login was via PTA
     * @param int $profile Value to set in PTA session variable
     * @return void
     * @internal
     */
    public function setPTA($profile)
    {
        $currentSessionString = base64_decode(substr($this->sessionData->sessionString, 9));
        $currentSessionString = Url::addParameter($currentSessionString, 'pta', '1');
        // The new SID should be set in createMapping
        $currentSessionString = Url::addParameter($currentSessionString, 'sid', $this->createUrlSafeSessionID($this->sessionData->sessionID));
        $this->sessionData->sessionString = '/session/' . base64_encode($currentSessionString);
        $this->sessionData->ptaUsed = true;
        $this->profileData = $profile;
        $this->profileData->ptaLoginUsed = true;
        $this->writeSession();
        $this->writeProfile();
    }

    /**
     * Sets values on a specified ProfileData persistent property and writes the profile cookie.
     * @param  String|Array $key Property name or Associative array of key / vals to set
     * @param  String|Number $value Property value
     * @return  Boolean T/F if the operation succeeded
     * @internal
     */
    public function writePersistentProfileData($key, $value = null) {
        $write = false;
        $props = (is_array($key)) ? $key : array($key => $value);

        if ($this->profileData) {
            $writable = ProfileData::getPersistentPropertyKeys();
            foreach ($props as $key => $value) {
                if (in_array($key, $writable)) {
                    $this->profileData->{$key} = $value;
                    $write = true;
                }
            }
        }

        if ($write) {
            $this->writeProfile();
            return true;
        }

        return false;
    }

    /**
     * Creates an instance of the SessionData class and populates its values based
     * on information that is in the URL
     * @return void
     */
    private function getSessionFromURL()
    {
        $sessionInfo = new SessionData();
        $urlParms = $this->CI->uri->segment_array();
        for($i = 0; $i < count($urlParms); $i++)
        {
            if(isset($urlParms[$i])){
            if($urlParms[$i] === 'session')
                $urlSession = explode('/', base64_decode(urldecode($urlParms[$i + 1])));
            if($urlParms[$i] === 'a_id')
                $sessionInfo->answersViewed = 1;
            if($urlParms[$i] === 'qid')
                $sessionInfo->questionsViewed = 1;
            if($urlParms[$i] === 'sno')
                $sessionInfo->numberOfSearches = $urlParms[$i + 1];
            }
        }

        for($i = 0; $i < count(isset($urlSession) && $urlSession ? $urlSession : array()); $i++)
        {
            if($urlSession[$i] === 'sid')
                $sessionInfo->sessionID = $this->extractSessionID($urlSession[$i + 1]);
            if($urlSession[$i] === 'pta')
                $sessionInfo->ptaUsed = $urlSession[$i + 1];
            if($urlSession[$i] === 'av')
                $sessionInfo->answersViewed += $urlSession[$i + 1];
            if($urlSession[$i] === 'qv') {
                $sessionInfo->questionsViewed += $urlSession[$i + 1];
            }
            if($urlSession[$i] === 'sno' && !$sessionInfo->numberOfSearches)
                $sessionInfo->numberOfSearches = $urlSession[$i + 1];
            if($urlSession[$i] === 'time')
                $sessionInfo->lastActivity = $urlSession[$i + 1];
            if($urlSession[$i] === 'gen')
                $sessionInfo->sessionGeneratedTime = $urlSession[$i + 1];
        }

        $this->sessionData = $sessionInfo;
        $this->flashData = new FlashData();
    }

    /**
     * Creates an encrypted session ID that can be stored in the URL for the handful of
     * cases where we support sessions in the URL.
     * @param String $sessionID The session ID to encrypt
     * @return String The encrypted session ID
     */
    private function createUrlSafeSessionID($sessionID)
    {
        return urlencode(Api::ver_ske_encrypt_fast_urlsafe(json_encode(array('session_id' => $sessionID))));
    }

    /**
     * Extracts decrypted session ID from the encrypted value typically stored in the URL.
     * @param String $secureSessionID The encrypted session ID
     * @return String The decrypted session ID
     */
    private function extractSessionID($secureSessionID)
    {
        return @json_decode(trim(Api::ver_ske_decrypt(urldecode($secureSessionID))))->session_id;
    }

    /**
     * When downloading office files, Office sends a OPTIONS request and
     * a HEAD request. We don't want these running through the Session class
     * because they will generate new session ID's which will invalidate the
     * old one. Therefore, we head these requests off at the pass.
     * @return void
     */
    private function ignoreOfficeRequests()
    {
        if($_SERVER['REQUEST_METHOD'] === 'OPTIONS')
        {
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
            exit;
        }
        else if($_SERVER['REQUEST_METHOD'] === 'HEAD')
        {
            header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
            exit;
        }
    }

    /**
     * Strip slashes from either an array or a string.
     * Use with caution: if your string has a newline ("\n") then the slash will be
     * stripped, leaving the "n", which probably isn't what you're expecting...
     * @param array|string $vals The string or array of strings to strip
     * @return array|string Values with all slashes stripped
     */
    private function stripSlashes($vals)
    {
        if(is_array($vals))
        {
            foreach($vals as $key => $val){
                if($key !== 'login'){
                    $vals[$key] = $this->stripSlashes($val);
                }
            }
        }
        else
        {
            $vals = stripslashes($vals);
        }
        return $vals;
    }

    /**
     * Perform the operations necessary to generate a new session ID. The session ID is automatically set as part of
     * the current sessions SessionData object. WARNING - Generating a new session will cause a new billable action.
     * @return void
     */
    public function generateNewSession()
    {
        $this->sessionData->sessionID = Api::generate_session_id();
        $this->sessionData->sessionGeneratedTime = time();
        $this->newSession = true;
        $this->insertStats($this->sessionData->sessionID);
    }

    /**
     * Insert session stats: session counts and session source activities.
     * @param string $sessionID Session id
     * @return void
     */
    private function insertStats($sessionID)
    {
        if (!IS_PRODUCTION || $this->CI->rnow->isSpider() || Url::isCallFromTagGallery() || Url::isPtaLogout() || $this->CI->_getAgentAccount())
            return;

        $contactID = $this->getProfileData('contactID');
        $controllerClassName = $this->CI->router->fetch_class();

        // This means it is either a ma or feedback session. We will determine which.
        if ($controllerClassName === 'documents' && !CUSTOM_CONTROLLER_REQUEST)
        {
            $urlParms = $this->CI->uri->uri_to_assoc($this->CI->config->item('parm_segment'));
            $shortcut = $urlParms[MA_QS_ITEM_PARM] ?: $this->CI->input->post('p_shortcut');
            $app = $this->CI->model('Clickstream')->getMaAppType($shortcut, $urlParms[MA_QS_SURVEY_PARM], '')->result;
        }
        else
        {
            //NOTE: stats.session column is populated offline. Thus, we will not it increment it here.
            $app = CS_APP_EU;
        }

        $referringUrl = parse_url($this->CI->agent->referrer());
        $referringSite = (isset($referringUrl['host']) && $referringUrl['host']) ? $referringUrl['host'] : null;
        $referringPath = (isset($referringUrl['path']) && $referringUrl['path']) ? $referringUrl['path'] : null;

        $this->CI->model('Clickstream')->insertAction($sessionID, $contactID, $app, 'SOURCE', $this->CI->input->ip_address(), $referringSite, $referringPath);
        $this->CI->model('Clickstream')->insertAction($sessionID, $contactID, $app, 'SOURCE_CLIENT', $this->CI->agent->platform(), $this->CI->agent->browser(), $this->CI->agent->agent_string());
    }

    /**
     * Populates an array of all URL parameters seen during the session
     * @return void
     */
    private function addUrlParmsToSession()
    {
        if($this->cullUrlParameters)
        {
            //Set up URL parms array in session data, ignore the session parameter, as that is already stored
            $urlParms = $this->CI->uri->uri_to_assoc($this->CI->config->item('parm_segment'));
            unset($urlParms['session']);
            unset($urlParms['url']);

            foreach($urlParms as $key => $val) {
                $this->sessionData->urlParameters[] = array($key => $val);
            }
        }
    }
}

/**
 * Abstract class for subclasses to
 * hook into in order to set the interface
 * as part of cookie data.
 */
abstract class InterfaceCookieData {
    private static $cookieKey = 'i';

    /**
     * Verifies that the decrypted, json-decoded
     * cookie data is legit.
     * @param  Array $cookieData Cookie data
     * @return Boolean             Whether the given
     *                                     data is an array
     *                                     and has a legit
     *                                     interface id
     */
    public static function verifyCookieData($cookieData) {
        return is_array($cookieData) && $cookieData[self::$cookieKey] === self::getInterface();
    }

    /**
     * Cookie representation of the interface.
     * @return Array Interface key and value
     */
    public static function getCookieData() {
        return array(self::$cookieKey => self::getInterface());
    }

    /**
     * Returns the current interface id.
     * @return Integer interface id
     */
    private static function getInterface() {
        static $interfaceID;
        $interfaceID || ($interfaceID = Api::intf_id());

        return $interfaceID;
    }
}

/**
 * The object where data about the current session is kept. Data can be retrieved out of this object by using
 *
 *      get_instance()->session->getSessionData({property_name})
 */
class SessionData extends InterfaceCookieData {
    /**
     * Current users session ID
     */
    public $sessionID;

    /**
     * Number of answers the user has viewed
     */
    public $answersViewed = 0;

    /**
     * Number of social questions the user has viewed
     */
    public $questionsViewed = 0;

    /**
     * Number of searches the user has performed
     */
    public $numberOfSearches = 0;

    /**
     * Array of URL parameters seen during this users visit
     */
    public $urlParameters = array();

    /**
     * Whether the current user logged in via PTA
     */
    public $ptaUsed = false;

    /**
     * Whether the user has cookies enabled
     */
    public $cookiesEnabled;

    /**
     * Session URL parameter to append to the URL when user has cookies diabled
     */
    public $sessionString = null;

    /**
     * Email a non-logged in user has previously entered into an existing web form
     */
    public $previouslySeenEmail = null;

    /**
     * Timestamp of the users last activity
     */
    public $lastActivity;

    /**
     * Session generated time
     */
    public $sessionGeneratedTime;

    /**
     * Array of recent searches
     */
    public $recentSearches = array();

    /**
     * Array of recently viewed okcs answers
     */
    public $okcsAnswersViewed = array();

    /**
     * Array of recently viewed discussions
     */
    public $discQuestionsViewed = array();

    /**
     * Array of user cache keys
     */
    public $userCacheKey = null;
    private $dynamicVariables = array();
    private $mapping = array(
        's' => 'sessionID',
        'a' => 'answersViewed',
        'q' => 'questionsViewed',
        'n' => 'numberOfSearches',
        'u' => 'urlParameters',
        'p' => 'ptaUsed',
        'e' => 'sessionString',
        'r' => 'previouslySeenEmail',
        'd' => 'dynamicVariables',
        'l' => 'lastActivity',
        'g' => 'sessionGeneratedTime',
        'c' => 'recentSearches',
        'k' => 'userCacheKey',
        'v' => 'okcsAnswersViewed',
        't' => 'discQuestionsViewed'
    );

    /**
     * Constructs a new SessionData object populating values from the provided array
     * @param array|null $cookieArray Initial data to populate
     * @internal
     */
    function __construct($cookieArray = null)
    {
        if($cookieArray !== null && is_array($cookieArray))
        {
            foreach ($cookieArray as $name => $value)
            {
                if(array_key_exists($name, $this->mapping))
                    $this->{$this->mapping[$name]} = $value;
            }
        }
    }

    /**
     * Checks if a given property exists in dynamicVariables
     * @param string $index The property/sub property name
     * @return bool
     */
    function dynamicPropertyExists($index)
    {
        return array_key_exists($index, $this->dynamicVariables);
    }

    /**
     * Compresses data down into it's smallest form so it can be written out to a cookie.
     * @return array Object converted down to minified cookie
     * @internal
     */
    function convertToCookie()
    {
        $cookieArray = self::getCookieData();
        foreach ($this->mapping as $cookieName => $memberName)
        {
            if ($this->$memberName)
                $cookieArray[$cookieName] = $this->$memberName;
        }
        return $cookieArray;
    }

    /**
     * Sets a value in the dynamic variables section
     * @param string $name The property name
     * @param mixed $value The value to set
     * @return void
     * @internal
     */
    function __set($name, $value)
    {
        $this->dynamicVariables[$name] = $value;
    }

    /**
     * Gets a value from the dynamic variables section
     * @param string $name The property name
     * @return mixed Value of property
     * @internal
     */
    function __get($name)
    {
        return (array_key_exists($name, $this->dynamicVariables)) ? $this->dynamicVariables[$name] : false;
    }

    /**
     * Creates an populates a new SessionData
     * instance with the given cookie data properties.
     * @param  Array $cookieData Cookie data to populate
     *                           the SessionData object
     *                           with
     * @return Object|Boolean             SessionData instance or
     *                                    false if validation
     *                                    fails
     */
    public static function newFromCookie($cookieData) {
        return self::verifyCookieData($cookieData)
            ? new SessionData($cookieData)
            : false;
    }
}

/**
 * The object where data about the currently logged in user is kept. Data can be retrieved out of this object by using
 *
 *      get_instance()->session->getProfileData({property_name})
 *
 * Or alternatively, the entire object can be returned by using
 *
 *      get_instance()->session->getProfile(true)
 */
class ProfileData extends InterfaceCookieData {
    /**
     * Contact ID for the currently logged in user
     */
    public $contactID;

    /**
     *  Username for the currently logged in user
     */
    public $login;


    /**
     * Primary email for the currently logged in user
     */
    public $email;

    /**
     * First name of the currently logged in user
     */
    public $firstName;

    /**
     * Last name of the currently logged in user
     */
    public $lastName;

    /**
     * Whether the currently logged in users account is disabled
     */
    public $disabled;

    /**
     * Org ID the currently logged in user is associated to
     */
    public $orgID;

    /**
     * Level of organization for the currently logged in user
     */
    public $orgLevel;

    /**
     * Whether the currently logged in user has an SLA with valid web incidents left
     */
    public $slai;

    /**
     * Whether the currently logged in user has an SLA with valid chat incidents left
     */
    public $slac;

    /**
     * Whether the currently logged in user has an SLA with self service enabled
     */
    public $webAccess;

    /**
     * Authorization token for the currently logged in user
     */
    public $authToken;

    /**
     * Whether the currently logged in user logged in via PTA
     */
    public $ptaLoginUsed;

    /**
     * Whether the currently logged in user logged in via OpenLogin
     */
    public $openLoginUsed;
    
    /**
     * Whether the currently logged in user logged in via SAML
     */
    public $samlLoginUsed;

    /**
     * Timestamp for when the user logged in
     */
    public $loginStartTime;

    /**
     * Time in which the user will be forcefully logged out
     */
    public $forcefulLogoutTime;
    /**
     * ID of the CommunityUser that the currently-logged-in user is associated to
     */
    public $socialUserID;

    /**
     * Mapping array to store data in reduced size, but keep interface names useful
     */
    private $mapping = array(
        'c_id' => 'contactID',
        'first_name' => 'firstName',
        'last_name' => 'lastName',
        'org_id' => 'orgID',
        'web_access' => 'webAccess',
        'cookie' => 'authToken',
        'pta_login_used' => 'ptaLoginUsed',
        'o_lvlN' => 'orgLevel',
        'slai' => 'slai',
        'slac' => 'slac',
        'login' => 'login',
        'email' => 'email',
        'disabled' => 'disabled',
        'openLoginUsed' => 'openLoginUsed',
        'samlLoginUsed' => 'samlLoginUsed',
        'forcefulLogoutTime' => 'forcefulLogoutTime',
        'loginStartTime' => 'loginStartTime',
        // No BC concerns, but due to the way Session#getProfile
        // calls ProfileData#getComplexMappedObject, an entry still
        // needs to exist.
        'socialUserID'      => 'socialUserID',
    );

    /**
     * The properties that are saved in a cookie.
     * Values are the keys used in the json-encoded array
     * written to the cookie.
     *
     * The secondary elements are fallback keys to use when
     * reading the cookie data (for BC concerns that I'm not
     * sure are valid anymore).
     *
     * All of the properties not in this list are populated via
     * Session#createMapping with data supplied by one of the
     * contact_login_* internal APIs.
     */
    private static $cookieMapping = array(
        'authToken'             => array('c', 'cookie'),
        'ptaLoginUsed'          => array('p', 'ptaLoginUsed'),
        'openLoginUsed'         => array('o'),
        'samlLoginUsed'         => array('m'),
        'forcefulLogoutTime'    => array('f'),
        'loginStartTime'        => array('l'),
        // It'd be real nice if the contact_login internal
        // APIs would become common_user aware so that a simple join
        // could be added to the query that supplies all of the
        // contact-specific stuff.
        'socialUserID'          => array('s'),
    );

    /**
     * Returns the backward compatible interface for the profile object.
     * @return object Returns backward compatible interface object
     * @internal
     */
    public function getComplexMappedObject(){
        $mappedObject = array();
        foreach($this->mapping as $oldValue => $newValue){
            $mappedObject[$oldValue] = (object)array('value' => $this->$newValue);
        }
        return (object)$mappedObject;
    }

    /**
     * Sets a value in the object
     * @param string $name The property name
     * @param mixed $value The value to set
     * @return void
     * @internal
     */
    public function __set($name, $value)
    {
        if(array_key_exists($name, $this->mapping))
            $this->{$this->mapping[$name]} = $value;
    }

    /**
     * Gets a value from the object
     * @param string $name The property name
     * @return mixed Value of property
     * @internal
     */
    public function __get($name)
    {
        return (array_key_exists($name, $this->mapping)) ? $this->{$this->mapping[$name]} : false;
    }

    /**
     * Returns a representation that's intended to be stored in the profile cookie.
     * @param Array $overrideData Associative array of properties and values to use for
     *                            the cookie representation rather than current property state
     * @return  Array Cookie data
     */
    public function convertToCookie($overrideData = array()) {
        $cookieData = self::getCookieData();

        foreach (self::$cookieMapping as $propertyName => $cookieKeys) {
            // Don't bother creating entries for falsy values.
            if ((isset($overrideData[$propertyName]) && $propertyValue = $overrideData[$propertyName]) || ($propertyValue = $this->{$propertyName})) {
                $cookieData[$cookieKeys[0]] = $propertyValue;
            }
        }

        return $cookieData;
    }

    /**
     * Creates and builds a new ProfileData instance from
     * the given array of cookie data.
     * @param  array $cookieData Associative array of cookie data.
     * @return ProfileData|Boolean New ProfileData instance or False
     *                                 if $cookieData doesn't pass
     *                                 validation
     */
    public static function newFromCookie($cookieData) {
        if (!self::verifyCookieData($cookieData)) return false;

        $profile = new ProfileData();

        foreach (self::$cookieMapping as $propertyName => $cookieKeys) {
            foreach ($cookieKeys as $key) {
                // Don't bother populating properties with any kind of falsy values.
                if (isset($cookieData[$key]) && $valueFromCookie = $cookieData[$key]) {
                    $profile->{$propertyName} = $valueFromCookie;
                    break 1;
                }
            }
        }

        return $profile;
    }

    /**
     * Returns a list of properties that aren't re-supplied by
     * the login internal APIs and so have to be supplied by
     * an external caller and persisted thru the profile cookie
     * written on each request.
     * @return array Array of keys to persist.
     */
    public static function getPersistentPropertyKeys() {
        // The other cookie-only properties are either recomputed or
        // should be overwritten each time.
        return array('openLoginUsed', 'socialUserID');
    }
}

/**
 * FlashData class used to handle temp data.
 *
 * @internal
 */
class FlashData {
    /**
     * Markers for what stays and what goes
     * when returning data used for storing
     * in the session cookie.
     */
    const NEW_DATA = ':new:';
    const OLD_DATA = ':old:';

    /**
     * Shortcuts for common data member names.
     * @var array
     */
    private static $shortNames = array(
        'a' => 'alert',
        'i' => 'info',
    );

    /**
     * Data to save.
     * @var array
     */
    private $data = array();

    /**
     * Populated with the 'old' flashdata values
     * from the last request. Should be instantiated
     * during Session's constructor.
     * @param array $properties Old flashdata properties
     */
    function __construct($properties = array()) {
        if (is_array($properties)) {
            foreach ($properties as $key => $value) {
                if (isset(self::$shortNames[$key]) && $longName = self::$shortNames[$key]) {
                    $key = $longName;
                }

                $this->data[self::OLD_DATA . $key] = urldecode($value);
            }
        }
    }

    /**
     * Sets 'new' flashdata values.
     *
     *      $flashData->name = 'value';
     *
     * @param string $key   Name
     * @param string|number|array|object $value Value to stash
     */
    function __set($key, $value) {
        $this->data[self::NEW_DATA . $key] = $value;
    }

    /**
     * Returns 'old' flashdata value, and, if that
     * doesn't exist, the 'new' flashdata value.
     * @param  string $key Name used to set the data
     * @return string|number|array|object|null Value
     * or, if not found, null
     */
    function __get($key) {
        return isset($this->data[self::OLD_DATA . $key]) && $this->data[self::OLD_DATA . $key] ? $this->data[self::OLD_DATA . $key] : (isset($this->data[self::NEW_DATA . $key]) && $this->data[self::NEW_DATA . $key] ? $this->data[self::NEW_DATA . $key] : null);
    }

    /**
     * Mark that an 'old' item should be kept around
     * for another request.
     * @param  string $key Name used to set the data
     */
    function keep($key) {
        $oldKey = self::OLD_DATA . $key;

        if (array_key_exists($oldKey, $this->data)) {
            $value = $this->data[$oldKey];
            $this->data[self::NEW_DATA . $key] = $value;
            unset($this->data[$oldKey]);
        }
    }

    /**
     * Returns an array representing the flashdata that
     * should be serialized for writing out to a cookie.
     * @return array Contains all 'new' items; empty if
     * no new items exist
     */
    function convertToCookie() {
        $cookieData = array();
        $longNames = array_flip(self::$shortNames);

        foreach ($this->data as $key => $value) {
            if (is_null($value) || !($key = \RightNow\Utils\Text::getSubstringAfter($key, self::NEW_DATA))) continue;

            if (isset($longNames[$key]) && $shortName = $longNames[$key]) {
                $key = $shortName;
            }

            // using urlencode/decode because json_encode doesn't support JSON_UNESCAPED_UNICODE until PHP v5.4
            $cookieData[$key] = urlencode($value);
        }

        return $cookieData;
    }
}
