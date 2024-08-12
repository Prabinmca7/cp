<?php

namespace RightNow\Controllers;
use RightNow\Utils\Config,
    RightNow\Utils\Filesystem,
    RightNow\Utils\Framework,
    RightNow\Utils\Text,
    RightNow\Utils\Url,
    RightNow\Internal\Api;

/**
 * Base class for all controllers that contains logic to load up all bootstrap libraries and
 * also contains a number of utility methods for getting information about the current request.
 */
class Base extends \CI_Controller
{
    /**
     * Flag set within CodeIgniter to denote if they've called into the parent constructor
     * @internal
     */
    protected $hasCalledConstructor = false;

    /**
     * Set if the request is being made by an agent, which will have a valid session ID
     * @internal
     */
    protected $account;
    private $pageSetID;
    private $pageSetPath;
    private $pageSetOffset = 0;
    private $clickstreamActionMapping = array();
    private $methodsExemptFromContactLoginRequired;
    private $loadedModels = array();

    /**
     * Key used to pull agent session ID out of URL or POST
     * @internal
     */
    const agentSessionIdKey = 'session_id';

    public function __construct($loadRnow = true)
    {
        parent::__construct();
        $this->load->library('themes');
        $this->load->library('rnow', $loadRnow || CUSTOM_CONTROLLER_REQUEST);
        $this->hasCalledConstructor = true;
        //Check if we have a valid agent session
        $this->account = $this->_verifyAgentSessionId();
        $this->_onlyAllowAgentConsoleRequestsForLoggedInAgents();
        $this->_setAdminLangInterfaceCookie();
        if(!IS_ADMIN)
            $this->_setPageSet();

        //If a user hits the site with an incorrectly encrypted location cookie remove it and redirect
        list($mode, $modeToken) = \RightNow\Environment\retrieveModeAndModeTokenFromCookie();
        if ($mode && $mode !== 'test' && (!$modeToken || !Framework::testLocationToken($modeToken, $mode)))
        {
            $this->_setCookieRedirectAndExit('location', '', -1, $_SERVER['REQUEST_URI']);
        }
    }

    /**
     * Load a model instance. If the model is already loaded, it will return a reference to the current
     * version. If the model is not loaded, it will include the required file, create an instance and return it.
     * @param string $model Model name in the form (custom)/modelName e.g.(Answer for standard model or custom/test/Answer for a custom model)
     * @return object Instance of model to be loaded. If the model path provided cannot be found, the process will be terminated with an error message.
     * @throws \Exception If no model path was specified
     */
    public function model($model)
    {
        if (!$model)
            throw new \Exception(Config::getMessage(NO_MODEL_PATH_SPECIFIED_MSG));
        try{
            $modelInstance = \RightNow\Models\Base::loadModel($model);
        }
        catch(\Exception $e){
            show_error($e->getMessage() . '<pre>' . $e->getTraceAsString() . '</pre>');
        }
        return $modelInstance;
    }

    /**
     * Determines if the current request came from the CX console.
     * @return boolean True if the request came from the CX console.
     * @internal
     */
    public function _isAgentConsoleRequest() {
        static $isAgentRequest;
        if (!isset($isAgentRequest)) {
            // CX console requests store just the interface name and not the language code.
            $isAgentRequest = (($langData = $this->_getRequestedAdminLangData()) && !$langData[1]);
        }
        return $isAgentRequest;
    }

    /**
     * Returns the requested interface and optional language code to render CP in. Lets CP render in English on a non-English interface, for example.
     * @return string|null The interface name from CX requests, or "{interface_name}|{lang code}" for CP Admin requests.
     * @internal
     */
    public function _getRequestedInterfaceForStrings() {
        if ($interface = (isset($_SERVER['HTTP_X_RIGHTNOW_AGENT_CONSOLE_INTERFACE']) ? $_SERVER['HTTP_X_RIGHTNOW_AGENT_CONSOLE_INTERFACE'] ?: $_COOKIE['cp_admin_lang_intf'] : ''))
            return trim($interface);
    }

    /**
     * Returns an array of ({interface name}, {language code|null}) as returned from '_getRequestedInterfaceForStrings'.
     * @return array
     */
    public function _getRequestedAdminLangData() {
        $results = array();
        if ($langData = $this->_getRequestedInterfaceForStrings()) {
            list($interface, $lang) = explode('|', $langData);
            $results = array($interface, $lang);
        }
        return $results;
    }

    /**
     * Returns account and profile information for the logged in agent.
     *
     * @return object|false Account object if request was made by an agent, or false if no agent is authenticated for this request
     */
    public function _getAgentAccount() {
        return $this->account;
    }

    /**
     * Checks if the logged in agent has the specified permissions
     * @param int $requiredPermissions Bit flags of required permissions
     * @param string $permissionCluster Name of the cluster of permissions to check for the required permissions.
     * @return boolean False if there's not a logged in agent or if he doesn't have the permission; true otherwise.
     */
    public function _doesAccountHavePermission($requiredPermissions, $permissionCluster) {
        $agentAccount = $this->_getAgentAccount();
        if ($agentAccount) {
            $permissionClusterName = "{$permissionCluster}_perms";
            if ($requiredPermissions === false || ($agentAccount->$permissionClusterName & $requiredPermissions)) {
                return $agentAccount;
            }
            if (!is_int($agentAccount->$permissionClusterName)) {
                exit(sprintf(Config::getMessage(PERMISSION_CLUSTER_NAMED_PCT_S_MSG), $permissionCluster));
            }
        }
        return false;
    }

    /**
     * Ensure that the Base constructor was run
     * @param object $instance Class instance
     * @return boolean True if it has been run, false otherwise
     * @internal
     */
    public static function checkConstructor($instance)
    {
        return($instance instanceof Base && $instance->hasCalledConstructor);
    }

    /**
     * Searches for a session_id parameter in the POST data, in the URL parameters, and finally within a cookie
     * @return string|boolean Agents session ID if set, or false if not present
     */
    public function _getAgentSessionId()
    {
        $sessionId = $this->input->post(self::agentSessionIdKey);
        if ($sessionId)
        {
            return $sessionId;
        }

        $requestUriSegments = explode('/', $_SERVER['REQUEST_URI']);
        for ($i = count($requestUriSegments) - 2; $i >= 0; $i--)
        {
            if ($requestUriSegments[$i] === self::agentSessionIdKey)
            {
                return $requestUriSegments[$i + 1];
            }
        }

        return $this->_getAgentSessionIdFromCookie();
    }

    /**
     * Returns the relative sub-folder path of the current page set. For example, on the
     * standard page set this is null, but for the default mobile page set, this would return 'mobile'.
     *
     * @return string|null Relative page set path or null if not set
     */
    public function getPageSetPath()
    {
        return $this->pageSetPath;
    }

    /**
     * Returns the sub folder depth of the current page set.
     *
     * @return int Offset value of current pageset
     */
    public function getPageSetOffset()
    {
        return $this->pageSetOffset;
    }

    /**
     * Returns the ID of the current page set.
     *
     * @return int|null Page set ID or null if not set
     */
    public function getPageSetID()
    {
        return $this->pageSetID;
    }

    /**
     * Returns the controller's clickstream action mappings.
     * @return array Curent controllers clickstream mappings
     */
    public function _getClickstreamMapping()
    {
        return $this->clickstreamActionMapping;
    }

    /**
     * Returns the controller's clickstream action mapping for a given function name. If the mapping is found, it
     * returns the clickstream tag. Otherwise, if `$defaultValue` is given, it will return it. If default value is not given,
     * it will return the function name.
     *
     * @param string $functionName Controller function name
     * @param mixed $defaultValue Default value to return if mapping is not found.
     * @return mixed Clickstream tag
     */
    public function _getClickstreamActionTag($functionName, $defaultValue=null)
    {
        if (array_key_exists($functionName, $this->clickstreamActionMapping))
            return $this->clickstreamActionMapping[$functionName];
        if($defaultValue)
            return $defaultValue;
        return $functionName;
    }

    /**
     * Called just before a controller method is executed to determine if the contact making the request should be allowed.
     * If you override this method, you should either exit to deny access or return to allow. See the CP_CONTACT_LOGIN_REQUIRED config
     * setting and the pre_allow_contact hook.
     */
    public function _ensureContactIsAllowed()
    {
        $controllerClass = $this->router->fetch_class();
        $actionWhenNotAllowed = 'redirectToLogin';
        //If it's a POST request and the user isn't posting to the page controller (basic page set), just exit
        //instead of redirecting.
        if($_SERVER['REQUEST_METHOD'] === 'POST' && (CUSTOM_CONTROLLER_REQUEST || $controllerClass !== 'page')){
            $actionWhenNotAllowed = 'exit';
        }
        $preHookData = array(
            'isContactAllowed' => $this->_isContactAllowed(),
            'ifNotAllowed' => $actionWhenNotAllowed,
            'controller' => $controllerClass,
            'method' => $this->router->fetch_method(),
            'uri' => $_SERVER['REQUEST_URI'],
        );
        \RightNow\Libraries\Hooks::callHook('pre_allow_contact', $preHookData);
        $this->_handleContactAllowedResult($preHookData);
    }

    /**
     * Indicates if the request is an Ajax request by checking the 'HTTP_X_REQUESTED_WITH' header
     * @return bool If the request is an Ajax request
     */
    public function isAjaxRequest() {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }

    /**
     * Extracts the CP page path from the referring URL within an AJAX request.
     * Dies if the current request is not an AJAX request or the referrer is invalid (empty or an external site).
     * @return string Page path without any preceeding /app (e.g. "answers/list", "home")
     */
    public function getPageFromReferrer() {
        // Default to checking super special header for referrer first (IE + base tag + AJAX = FTL)
        $referrer = $_SERVER['HTTP_RNT_REFERRER'] ?: $_SERVER['HTTP_REFERER'];
        if ($this->isAjaxRequest() && Text::stringContains($referrer, Url::getShortEufBaseUrl('sameAsRequest'))) {
            $referrer = Text::getSubstringAfter($referrer, '/app/');
            if ($referrer === false) {
                $referrer = 'home';
            }
            $segments = explode('/', $referrer);
            if ($pageSet = $this->getPageSetPath()) {
                $segments = array_merge(explode('/', $pageSet), $segments);
            }
            $resolved = $this->getPageFromSegments($segments);
            if ($resolved['found']) {
                return $resolved['page'];
            }
        }
        exit("The HTTP Referrer is invalid.");
    }

    /**
     * Returns an agent session ID from a cooke if present
     * @return string|boolean Agents session ID if set, or false if not present
     */
    protected function _getAgentSessionIdFromCookie() {
        return isset($_COOKIE[self::agentSessionIdKey]) ? $_COOKIE[self::agentSessionIdKey] : false;
    }

    /**
     * Ensures that the passed in agent session ID belongs to a valid, logged in, agent account.
     * @return array|bool Details about the agent or false if session wasn't valid
     * @internal
     */
    protected function _verifyAgentSessionId()
    {
        $sessionId = self::_getAgentSessionId();
        if ($sessionId)
        {
            $account = \RightNow\Api::acct_login_verify($sessionId);
            if ($account)
            {
                // We're only settting the cookie if the session ID was passed.  That should only happen
                // if the request came from the CRM client in most cases.
                $this->_setAgentSessionIdCookie($sessionId);
                return $account;
            }
        }
        return false;
    }

    /**
     * We set the agent's session ID into a cookie so that we don't have to update URLs throughout pages to continue passing the session ID around.
     * @param string $sessionID Session ID of logged in agent account.
     * @return void
     * @internal
     */
    protected function _setAgentSessionIdCookie($sessionID) {
        // Avoid setting the cookie if it's already set as a slight optimization.
        if ($sessionID !== $this->_getAgentSessionIdFromCookie()) {
            // We set the expire time to 0 so that it will be a session cookie.
            // Otherwise IE might get clever and share the cookie with the browser
            // embedded in the CRM client and in a regular browser.
            setcookie(self::agentSessionIdKey, $sessionID, 0, '/', '', Config::getConfig(SEC_ADMIN_HTTPS), true);
        }
    }

    /**
     * Sets the current requests page set correctly either based on matching user agent strings or dynamically based
     * on the pre_page_set_selection hook.
     * @return void
     * @internal
     */
    protected function _setPageSet()
    {
        $pageSetArray = $this->_selectPageSet();
        \RightNow\Libraries\Hooks::callHook('pre_page_set_selection', $pageSetArray);
        $selected = isset($pageSetArray['selected']) ? $pageSetArray['selected'] : null;
        if ($selected !== null)
        {
            $this->pageSetPath = $pageSetArray[$selected]->getValue();
            $this->pageSetID = $pageSetArray[$selected]->getId();
        }
        else
        {
            $this->pageSetPath = null;
            $this->pageSetID = null;
        }
        $this->_setPageSetOffset();

        $this->_processForShiftJisTranscoding();
    }

    /**
     * Checks if a cookie is set to render a specific page set. If not, then the user agent string is checked against each
     * page set mapping to see if it matches.
     * @return array|null List of page sets with selected one keyed by 'selected' or null if no page set was matched
     * @internal
     */
    protected function _selectPageSet()
    {
        $matchedID = null;
        $pageSets = (IS_REFERENCE) ? $this->model('Pageset')->getPageSetDefaultArray() : $this->model('Pageset')->getPageSetMappingMergedArray();
        if (isset($_COOKIE['agent']) && $cookie = $_COOKIE['agent'])
        {
            if ($cookie !== '/')
            {
                foreach($pageSets as $id => $mapping)
                {
                    if ($mapping->enabled && $cookie === $mapping->value)
                    {
                        $matchedID = $id;
                        break;
                    }
                }
            }
        }
        else
        {
            foreach($pageSets as $id => $mapping)
            {
                if ($mapping->enabled && $mapping->item !== '' && preg_match($mapping->item, $_SERVER['HTTP_USER_AGENT']))
                {
                    $matchedID = $id;
                    break;
                }
            }
        }
        if ($matchedID !== null)
        {
            $pageSets['selected'] = $matchedID;
            return $pageSets;
        }
    }

    /**
     * A custom controller can use this function to set its clickstreams mappings.
     * NOTE: It must be called within the constructor because Clickstreams hook runs during the post-controller constructor phase.
     * Example:
     *
     *      parent::_setClickstreamMapping(array("myAskQuestions" => "incident_submit", "myattachmentGet" => "attachment_view"));
     *
     * @param array $clickstreamActionMapping An array of clickstream mappings
     * @return void
     */
    protected function _setClickstreamMapping(array $clickstreamActionMapping)
    {
        $this->clickstreamActionMapping = $clickstreamActionMapping;
    }

    /**
     * Returns the fully qualified path to the folder where pages are being served from. Handles
     * differences between various modes of where pages are located.
     *
     * @return string Fully qualified path to top level folder where pages are located.
     */
    protected function _getPagesPath()
    {
        return $this->_getViewsPath() . ((IS_OPTIMIZED) ? 'headers/' : (IS_OKCS_REFERENCE ? 'pages/okcs/' : 'pages/'));
    }

    /**
     * Returns the fully qualified path to the views folder, accounting for differences depending on
     * which mode the site is in.
     *
     * @return string Fully qualified path to top level folder where both pages and templates are located.
     */
    protected function _getViewsPath()
    {
        if(IS_REFERENCE)
        {
            $referenceImplementationViewPath = (IS_HOSTED ? CPCORESRC : CUSTOMER_FILES) . 'views/';
            if (is_dir($referenceImplementationViewPath))
                return $referenceImplementationViewPath;
        }
        return APPPATH . 'views/';
    }

    /**
     * Processes the given segments and extracts the page and other info.
     * @param array $segments Segments to look at
     * @param bool $ignorePageSetPath If true, treats the $segments array literally and does not modify the search for the currently selected pageset
     * @return array Returns the following keys:
     *  -found => [boolean] Whether a page was found from the segments
     *  -page => [string] The page path (relative to views/pages/)
     *  -path => [string] Absolute path to the page php file
     *  -segment => [string] The final page segment
     *  -currentPath => [string] Path from views/pages/ to segment; identical to segment if there's only one segment
     *  -segmentIndex => [int] Index in the segments where URL parameters begin
     * @internal
     */
    protected function getPageFromSegments(array $segments = array(), $ignorePageSetPath = false) {
        $pagePath = $this->_getPagesPath();
        $currentPath = $ignorePageSetPath ? null : $this->getPageSetPath();
        $currentPath .= ($currentPath !== null) ? '/' : '';
        $requestedPath = '';
        $found = false;

        $pageSetOffset = $ignorePageSetPath ? 0 : $this->getPageSetOffset();
        for ($i = $pageSetOffset; $i < count($segments); $i++) {
            $tempSegment = urldecode($segments[$i]);
            if (\RightNow\Utils\Text::stringContains($tempSegment, '..')) {
                // The request looks malicious - Prevent an attempt to read outside of the page directory.
                break;
            }
            $segment = $tempSegment;
            if (is_file($pagePath . $currentPath . $segment . '.php')) {
                $found = true;
                $page = $requestedPath . $segment;
                $pagePath .= $currentPath . $segment . '.php';
                $segmentIndex = $i + 4;
                break;
            }
            else if (is_dir($pagePath . $currentPath . $segment)) {
                $currentPath .= "$segment/";
                $requestedPath .= "$segment/";
            }
            else {
                //Current path up to this point is invalid (folder or page do
                //not exist) don't continue, show 404
                $currentPath .= $segment;
                $requestedPath .= $segment;
                break;
            }
        }
        return array(
            'page' => (isset($page)) ? $page : '',
            'path' => $pagePath,
            'found' => $found,
            'segment' => isset($segment) ? $segment : '',
            'currentPath' => $currentPath,
            'segmentIndex' => (isset($segmentIndex)) ? $segmentIndex : 0,
        );
    }

    /**
     * Redirects the user to the login_url with previous page and parameters
     * so that upon successful login, the user is returned to this page
     * @return void
     * @internal
     */
    protected function _loginRedirect()
    {
        //Generate the login page URL. For an internal login, we have to double encode
        //the redirect parameter because our servers don't have single slash encoding
        //enabled (i.e. %2F is not allowed in the URL)
        if(Config::getConfig(PTA_ENABLED) && Config::getConfig(PTA_EXTERNAL_LOGIN_URL))
        {
            $loginPage = Url::replaceExternalLoginVariables(0, $this->_getLoginReturnUrl());
        }
        else if($internalLogin = Config::getConfig(CP_LOGIN_URL))
        {
            $loginPage = "/app/$internalLogin/redirect/" . rawurlencode(rawurlencode($this->_getLoginReturnUrl())) . Url::sessionParameter();

            //Append encrypted session cookie in URL, which will be validated during PTA login
            if (is_object($this->session)) {
                $ptaSessionId = urlencode(Api::ver_ske_encrypt_fast_urlsafe(json_encode(array('p_ptaid' => $this->session->getSessionData('sessionID')))));
                $loginPage .= ((strrchr($loginPage, '?')) ? '&' : '?') . "p_ptaid=$ptaSessionId";
            }

            //Log a message to the standard tracing mechanism (cphp tracing) when redirect happens 
            \RightNow\Api::phpoutlog("Anonymous user failed to visit page and redirected to the login_url.");
        }
        else
        {
            show_404();
        }

        $preHookData = array('data' => $loginPage);
        \RightNow\Libraries\Hooks::callHook('pre_login_redirect', $preHookData);
        $url = $preHookData['data'];
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['host']) || !$parsedUrl['host']) {
            $url = Url::getShortEufBaseUrl('sameAsRequest', $url);
        }
        $this->_redirectAndExit($url);
    }

    /**
     * Called by _ensureContactIsAllowed to determine the default value of the 'isContactAllowed' member
     * of the hook data passed to pre_allow_contact.
     *
     * @return boolean Indicates whether the contact should be allowed
     * @see Base::_setMethodsExemptFromContactLoginRequired to one way of getting complex behavior without overriding this method
     */
    protected function _isContactAllowed()
    {
        return (!Config::contactLoginRequiredEnabled() ||
            is_array($this->methodsExemptFromContactLoginRequired) && in_array($this->router->fetch_method(), $this->methodsExemptFromContactLoginRequired, true) ||
            Framework::isLoggedIn()
        );
    }

    /**
     * Called by _ensureContactIsAllowed after firing pre_allow_contact to take the appropriate action.
     * @param array|null $preHookData The array of hook data passed to pre_allow_contact
     * @return mixed Result from checking if contact is allowed, or might cause an exit.
     * @internal
     */
    protected function _handleContactAllowedResult($preHookData) {
        if (!is_array($preHookData) || !array_key_exists('isContactAllowed', $preHookData) || $preHookData['isContactAllowed']) {
            return;
        }
        if (array_key_exists('ifNotAllowed', $preHookData)) {
            $ifNotAllowed = $preHookData['ifNotAllowed'];
            if ($ifNotAllowed === 'exit') {
                header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
                if ($this->isAjaxRequest()) {
                    Framework::writeContentWithLengthAndExit(Config::getMessage(PAGE_HAS_EXPIRED_LBL));
                }
                else {
                    Framework::writeContentWithLengthAndExit(Config::getMessage(FORBIDDEN_LBL));
                }
            }
            else if ($ifNotAllowed instanceof \Closure) {
                return $ifNotAllowed();
            }
            else if (is_array($ifNotAllowed)) {
                return call_user_func($ifNotAllowed);
            }
            // The implicit else here is that $ifNotAllowed === 'redirectToLogin' or any other value, we'll redirect to login.
        }

        //I should have a function which redirects to login.  It should probably be copied from the page controller's version.
        //I kind of think that any POSTs should be rejected 403 style.
        $this->_loginRedirect();
    }

    /**
     * If a contact would otherwise have to be logged in to access a given controller method,
     * this offers a mechanism to provide a whitelist of methods which can be accessed without
     * being logged in. Only the name of the method needs to be specified.
     *
     * @param array|null $methods List of method names to whitelist.
     * @return void
     */
    protected function _setMethodsExemptFromContactLoginRequired($methods) {
        $this->methodsExemptFromContactLoginRequired = $methods;
    }

    /**
     * Called by _loginRedirect to determine where to send the contact after successfully logging in.
     *
     * @return string URL to eventually redirect to
     * @internal
     */
    protected function _getLoginReturnUrl() {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * Set a cookie.
     *
     * @param string $name Name of the cookie
     * @param string $value Value to store in the cookie
     * @param int $time Expiration interval in seconds (a negative number will remove the cookie and a value of 0 will make the cookie only last for the session)
     * @return void
     * @internal
     */
    protected function _setCookie($name, $value, $time)
    {
        if ($time < 0)
            $time = time() - 31500000;
        else if ($time > 0)
            $time += time();

        setcookie($name,
            $value,
            $time,
            $this->config->item('cookie_path'),
            $this->config->item('cookie_domain'),
            Config::getConfig(SEC_END_USER_HTTPS, 'COMMON'),
            true);
    }

    /**
     * Set the redirect and exit.
     *
     * @param string $url URL to redirect to
     * @param string|null $expires Value for optional 'Expires' header.
     * @return void
     */
    protected function _redirectAndExit($url, $expires = null)
    {
        if ($expires !== null) {
            header("Expires: $expires");
        }
        Framework::setLocationHeader($url);
        exit();
    }

    /**
     * Set a cookie, set a redirect, and exit.
     *
     * @param string $cookieName Name of the cookie
     * @param string $cookieValue Value to store in the cookie
     * @param int $cookieTime Expiration interval in seconds (a negative number will remove the cookie and a value of 0 will make the cookie only last for the session)
     * @param string $url URL to redirect to
     * @return void
     */
    protected function _setCookieRedirectAndExit($cookieName, $cookieValue, $cookieTime, $url)
    {
        $this->_setCookie($cookieName, $cookieValue, $cookieTime);
        $this->_redirectAndExit($url);
    }

    /**
     * Echoes out the JSON encoded value of $toRender along with the appropriate content type header.
     * @param (Object|Array) $toRender The data to encode
     */
    protected function _renderJSON($toRender) {
        echo Framework::jsonResponse($toRender);
    }

    /**
     * Echoes out the specified JSON value along with the appropriate content type header.
     * @param String $toEcho JSON data to echo
     */
    protected function _echoJSON($toEcho) {
        echo Framework::jsonResponse($toEcho, false);
    }

    /**
     * Checks that if the request came from the console, that a valid agent session ID was passed in.
     */
    private function _onlyAllowAgentConsoleRequestsForLoggedInAgents() {
        if ($this->_isAgentConsoleRequest() && !$this->_getAgentAccount()) {
            exit(Config::getMessage(LOOKS_REQ_CLAIMS_RN_AGT_CONSOLE_MSG));
        }
    }

    /**
     * Sets a cookie so that all future requests from this agent session are displayed in the requested interfaces language.
     */
    private function _setAdminLangInterfaceCookie() {
        if (($cookieValue = $this->_getRequestedInterfaceForStrings()) && $cookieValue !== trim($_COOKIE['cp_admin_lang_intf'])) {
            setcookie('cp_admin_lang_intf', $cookieValue, 0, '/ci/', $_SERVER['HTTP_HOST'], Config::getConfig(SEC_ADMIN_HTTPS), true);
        }
    }

    /**
     * Sets the URL offset for the current page set. The offset is the number of directories under /views/pages that the current page set is within.
     * @return void
     * @internal
     */
    private function _setPageSetOffset()
    {
        $pageSetArray = (!empty($this->pageSetPath)) ? explode('/', $this->pageSetPath) : array(); 
        if (isset($pageSetArray[0]) && $pageSetArray[0] === "") // if $this->pageSetPath is an empty string the array will contain an empty string.
            unset($pageSetArray[0]);
        $this->pageSetOffset = count($pageSetArray);
    }

    /**
     * If the current page set is configured to output and accept content in SHIFT_JIS,
     * perform the necessary setup and processing.
     * @return void
     */
    private function _processForShiftJisTranscoding(){
        // verify that we want to transcode this page set
        if ($this->_useShiftJis())
        {
            // CP always sends a UTF-8 content type, so we override it here
            header("Content-Type: text/html; charset=Shift_JIS");

            // be sure content gets transcoded to SHIFT_JIS before being sent to the browser
            ob_start(function ($pageBody) {
                if(!strlen($pageBody)){
                    return;
                }
                $pageBody = Api::lang_transcode($pageBody, 'UTF-8', "SHIFT_JIS");
                header('Content-Length: ' . strlen($pageBody));
                return $pageBody;
            });

            // transcode specific server globals
            $serverGlobalsToEscape = array('QUERY_STRING', 'REQUEST_URI', 'HTTP_REFERER', 'REDIRECT_URL');
            foreach($serverGlobalsToEscape as $value){
                $_SERVER[$value] = Api::lang_transcode($_SERVER[$value], 'SHIFT_JIS', 'UTF-8');
            }

            // transcode GET and POST array data
            $_GET = self::_transcodeArrayOfShiftJisData($_GET);
            $_POST = self::_transcodeArrayOfShiftJisData($_POST);

            // update values in REQUEST with transcoded data
            $_REQUEST = $_GET + $_POST + $_COOKIE;
        }
    }

    /**
     * Determines if the current request should use Shift JIS, based on the CP_SHIFT_JIS_PAGE_SETS config.
     * @return bool Whether or not to use Shift JIS transcoding with the current request
     */
    private function _useShiftJis(){
        $shiftJisPageSets = trim(Config::getConfig(CP_SHIFT_JIS_PAGE_SETS));
        if (!$shiftJisPageSets)
            return false;
        $shiftJisPageSets = explode(',', $shiftJisPageSets);
        foreach ($shiftJisPageSets as $shiftJisPageSet){
            $shiftJisPageSet = trim($shiftJisPageSet);
            if (($shiftJisPageSet === 'DEFAULT' && $this->pageSetPath === null) || ($shiftJisPageSet === $this->pageSetPath))
                return true;
        }
        return false;
    }

    /**
     * Transcodes data from SHIFT_JIS to UTF-8
     * @param array $data Array of data to transcode to UTF-8 from SHIFT_JIS
     * @return array Array of transcoded data
     */
    private static function _transcodeArrayOfShiftJisData(array $data){
        $safeData = array();
        foreach($data as $key => $value){
            $safeData[Api::lang_transcode($key, 'SHIFT_JIS', 'UTF-8')] = is_array($value) ? self::_transcodeArrayOfShiftJisData($value) : Api::lang_transcode($value, 'SHIFT_JIS', 'UTF-8');
        }
        return $safeData;
    }
}
