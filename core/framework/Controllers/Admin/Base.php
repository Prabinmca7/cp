<?php

namespace RightNow\Controllers\Admin;

use RightNow\Utils\Text,
    RightNow\Utils\Framework,
    RightNow\Utils\Config;

/**
 * HOWTO add a new admin controller:
 *  1. Create a new file in core/framework/Controllers/Admin containing a class of the same name.
 *     The class' name ought to be in PascalCase.
 *  2. Make the class extend from Base.
 *  3. The controller should have a constructor which calls' the parent constructor.
 *  4. Insert the name of the controller in index.php in the switch block that contains tags, logs, admin, etc.
 *     The point is to set $isAdmin = true.
 *  5. In get_enduser_type() in common/libutil/util.c, add the controller to the regular expression alongside tags, logs, admin, etc.
 */
class Base extends \RightNow\Controllers\Base
{
    const HTTP_AUTH_REALM = "RightNow CX";
    const HTTP_AUTH_TYPE_BASIC = "Basic";

    function __construct($loadRnow, $defaultLoginFunction, $loginExceptions=false)
    {
        parent::__construct($loadRnow);
        //Deny access to controllers where the IS_ADMIN define is true. This will allow things such as
        //answer preview and any possible admin custom controllers to still work

        $methodName = get_instance()->router->fetch_method();
        if (!\RightNow\Utils\Config::getConfig(MOD_CP_DEVELOPMENT_ENABLED) && IS_ADMIN
            && !(strtolower(get_instance()->router->fetch_class()) === 'deploy'
            && in_array(strtolower($methodName), array('upgradedeploy', 'servicepackdeploy'))))
        {
            header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
            //Expand the error message by duplicating spaces so that we actually display this message
            //and the browser doesn't show its default 404 page
            exit(\RightNow\Utils\Config::getMessage(CUST_PORTAL_DEVELOPMENT_ENABLED_MSG) . str_repeat(" ", 512));
        }

        if (!headers_sent()) {
            //Do not allow admin pages to be accessed via iframes
            header("X-Frame-Options: DENY");
            header("Content-Security-Policy: frame-ancestors 'none'");
        }

        if (is_array($loginExceptions) && (($loginExceptionsKey = $this->_getArrayKeyCaseInsensitive($methodName, $loginExceptions)) !== false))
        {
            $loginFunction = $loginExceptions[$loginExceptionsKey];
            if ($loginFunction === false) {
                return;
            }
        }
        else
        {
            $loginFunction = $defaultLoginFunction;
        }
        if (!method_exists($this, $loginFunction))
        {
            exit("There is no $loginFunction method on Admin\Base or its derived class.");
        }

        $this->$loginFunction();
    }

    /**
     * To have gotten to this point, we have to have passed whatever authentication requirement the
     * derived admin controller asserted.  Usually that's going to mean that by the
     * time we're here, we know that we have a valid admin.  We really don't care
     * if we have a valid contact to access admin pages.
     * @internal
     */
    public function _ensureContactIsAllowed() {
        return true;
    }

    /**
     * Ensure that the Admin\Base constructor was run
     *
     * @param object $instance Class instance
     * @return Boolean True if it has been run, false otherwise
     */
    public static function checkConstructor($instance)
    {
        return($instance instanceof \RightNow\Controllers\Admin\Base && $instance->hasCalledConstructor);
    }
    

    /**
     * Check the request session_id/<agentSessionID> pair and check the request for an HTTP Auth variable for a username and password and attempt a login.
     * Also checks if the agent has the WebDAV permission.
     */
    protected function _verifyLogin() {
        if (!$this->account) {
            // Account will already be set if there was a valid session_id somewhere in the request,
            // So, we'll be here if there wasn't a valid session_id, in case we need to check for interactive log in.
            $this->account = self::_verifyLoginWithHttpAuthBasic();
        }
        
        // This should require ACCESS_WEBDAV, but we messed up long ago.
        if (!$this->_doesAccountHavePermission(false, 'global')) {
            self::exitWithHttpAuthError();
        }
        
        if(!$this->_verifyPostCsrfToken()){
            $this->renderErrorResponseObject(Config::getMessage(SORRY_BUT_ACTION_CANNOT_PCT_R_TRY_AGAIN_MSG));
        }
    }

    protected function _verifyLoginWithCPPromotePermission() {
        $this->_verifyLoginWithCPPermission(ACCESS_CP_PROMOTE);
    }

    protected function _verifyLoginWithCPStagePermission() {
        $this->_verifyLoginWithCPPermission(ACCESS_CP_STAGE);
    }

    protected function _verifyLoginWithCPEditPermission() {
        $this->_verifyLoginWithCPPermission(ACCESS_CP_EDIT);
    }

    private function _verifyLoginWithCPPermission($permission) {
        $this->_verifyLogin();
        if (!$this->_doesAccountHavePermission($permission, 'global')) {
            self::exitWithHttpAuthError();
        }
    }

    /**
     * Like _verifyLogin, but only uses HTTP Auth.  Requires ACCESS_WEBDAV
     */
    protected function _verifyLoginWithHttpAuth() {
        $this->account = self::_verifyLoginWithHttpAuthBasic();
        // This should require ACCESS_WEBDAV, but we messed up long ago.
        if (!$this->_doesAccountHavePermission(false, 'global')) {
            self::exitWithHttpAuthError();
        }
        if (!$this->_verifyPostCsrfToken()) {
            $this->renderErrorResponseObject(Config::getMessage(SORRY_BUT_ACTION_CANNOT_PCT_R_TRY_AGAIN_MSG));
        }
    }
    
    /**
     * Like _verifyLogin, but only uses HTTP Auth.  Requires ACCESS_CP_EDIT.
     * This should require ACCESS_WEBDAV, but we messed up long ago.
     */
    protected function _verifyLoginWithHttpAuthWithCPEdit() {
        $this->account = self::_verifyLoginWithHttpAuthBasic();
        if (!$this->_doesAccountHavePermission(ACCESS_CP_EDIT, 'global')) {
            self::exitWithHttpAuthError();
        }
    }

    private static function exitWithHttpAuthError() {
        header('WWW-Authenticate: ' . self::HTTP_AUTH_TYPE_BASIC . ' realm="' . self::HTTP_AUTH_REALM . '"');
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit('401 Unauthorized');
    }

    private static function _verifyLoginWithHttpAuthBasic() {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
        if ($auth && strlen($auth) > strlen(self::HTTP_AUTH_TYPE_BASIC) && substr($auth, 0, strlen(self::HTTP_AUTH_TYPE_BASIC)) == self::HTTP_AUTH_TYPE_BASIC)
        {
            $auth = base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], strlen(self::HTTP_AUTH_TYPE_BASIC) + 1));
            if ($auth && ($colonIndex = strpos($auth, ':')) > 0)
            {
                $username = substr($auth, 0, $colonIndex);
                $password = substr($auth, $colonIndex + 1);

                // Some versions of the Windows WebDAV client are broken (no, really!) and send the
                // authentication realm or host name as the "domain" in the username.  For example,
                // the user name might look like "RightNow CRM\user" or "full.host.name\user".
                // c.f., http://www.greenbytes.de/tech/webdav/webdav-redirector-list.html#issue-basic-authentication
                // c.f., http://support.microsoft.com/?kbid=315621
                $slashedRealm = self::HTTP_AUTH_REALM . "\\";
                if (0 === strncasecmp($slashedRealm, $username, strlen($slashedRealm))) {
                    $username = substr($username, strlen($slashedRealm));
                }
                else {
                    $slashedHost = "{$_SERVER['HTTP_HOST']}\\";
                    if (0 === strncasecmp($slashedHost, $username, strlen($slashedHost))) {
                        $username = substr($username, strlen($slashedHost));
                    }
                }

                //The API can't handle empty-string passwords
                //or newlines, tabs, etc. in the username, password
                $password = (is_string($password) && strlen($password) > 0) ? trim($password) : null;
                $pairdata = array(
                    'login'    => trim($username),
                    'opt'      => AL_OPT_TRANSIENT,
                    'password_text' => $password
                );
                if (is_array(\RightNow\Api::account_login($pairdata))) {
                    return \RightNow\Api::account_data_get();
                }
            }
        }
        return false;
    }

    /**
     * Searches for a session_id parameter in the POST data and in the URL parameters.
     * If found, attempts to get the account associated with the session ID.
     * If an account is found, makes sure that the account has the specified permissions.
     * If any of those tests fail, an HTTP header and body are output to indicate denied
     * access and PHP is exited.
     *
     * @param mixed $requiredPermissions A permission bit to require of the account.  If false, no bit is required; the user only needs an account.
     * @param string $permissionCluster Which of the account's permission clusters to search for $requiredPermissions on.  Values such as 'global', 'css', and 'ma' are valid.
     * @return The account account found. PHP will exit if no acceptable account is found.
     * @internal
     */
    public static function verifyAccountLoginBySessionId($requiredPermissions = false, $permissionCluster = 'global') {
        $CI = get_instance();
        if (!$CI->_doesAccountHavePermission($requiredPermissions, $permissionCluster)) {
            // very specific code to bypass agent security in non-hosted only for unit tests
            if (!IS_HOSTED
                && Text::beginsWith($_SERVER['REQUEST_URI'], '/ci/unitTest/wgetRecipient/invokeTestMethod')
                && Text::endsWith($_SERVER['REQUEST_URI'], 'AnswerPreview.test.php/AnswerPreviewTest/callFull')) {
                return true;
            }
            header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
            exit(\RightNow\Utils\Config::getMessage(ACCESS_DENIED_HDG));
        }
        return $CI->_getAgentAccount();
    }

    protected function _verifyLoginBySessionId($requiredPermissions = false, $permissionCluster = 'global') {
        self::verifyAccountLoginBySessionId($requiredPermissions, $permissionCluster);
    }

    protected function _verifyLoginBySessionIdAndRequireCPPromote() {
        $this->_verifyLoginBySessionId(ACCESS_CP_PROMOTE, 'global');
    }

    protected function _verifyLoginBySessionIdAndRequireCPStage() {
        $this->_verifyLoginBySessionId(ACCESS_CP_STAGE, 'global');
    }

    protected function _verifyLoginBySessionIdAndRequireCPEdit() {
        $this->_verifyLoginBySessionId(ACCESS_CP_EDIT, 'global');
    }

    /**
     * Makes sure that an ajax post is the origin of the call.
     */
    protected function _verifyAjaxPost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->isAjaxRequest()) {
            $caller = debug_backtrace();
            show_404($caller[1]['function']);
            exit;
        }
    }
    
    /**
     * Validates the form token in the POST request
     * @return boolean True if token is valid else False
     */
    protected function _verifyPostCsrfToken() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return ($formToken = $this->input->post("formToken"))
                    && Framework::testCsrfToken($formToken, $this->account->acct_id, 0, true);
        }
        return true;
    }

    /**
     * Renders an admin view.
     * @param string $pagePath String path to the view; relative to cp/core/framework/Views/Admin
     * @param array|null $data Array Data to pass to the view
     * @param string $pageTitle String page title
     * @param array $options Array options; (optional) keys:
     *  'js': String|Array path to a JS file to include on the page
     *      -The file can be absolute
     *      -If relative, should be relative to core/admin/js and the file extension should be omitted
     *  'css': String|Array path to a CSS file to include on the page
     *      -The file can be absolute
     *      -If relative, should be relative to core/admin/css and the file extension should be omitted
     *   Any other key-values specified in this array are passed onto the template as data
     * @param string $template String Template to use; should be relative to cp/core/framework/Views/Admin
     */
    protected function _render($pagePath, $data, $pageTitle, array $options = array(), $template = 'template') {
        $data["submitTokenExp"] = \RightNow\Utils\Config::getConfig(SUBMIT_TOKEN_EXP);
        $data["labels"] = array(
            "ok" => \RightNow\Utils\Config::getMessage(OK_CMD),
            "warning" => \RightNow\Utils\Config::getMessage(WARNING_LBL),
            "parserError" => config::getMessage(ERR_WERENT_EXPECTING_HAPPENED_SORRY_MSG),
            "genericError" => config::getMessage(SORRY_BUT_ACTION_CANNOT_PCT_R_TRY_AGAIN_MSG)
        );
        $data["formToken"] = \RightNow\Internal\Utils\Framework::createAdminPageCsrfToken(1, $this->account->acct_id);
        if (\Rnow::getTestCookieData()) return $this->_renderIsolatedView($pagePath, $data);

        require_once CPCORE . 'Internal/Utils/Admin.php';
        require_once CPCORE . 'Internal/Libraries/Staging.php';
        require_once CPCORE . 'Internal/Utils/Deployment.php';

        list($jsTags, $cssTags) = $this->_garnerResources($options);

        // Admin pages should not be cached due to dynamic elements
        // that change as a result of an action the current user has taken. Send this
        // header to tell ALL browsers not to cache.
        header("Expires: -1");

        $this->load->view('Admin/' . (($this->_isAgentConsoleRequest()) ? 'plainTemplate' : $template), array(
            'js'               => $jsTags,
            'css'              => $cssTags,
            'content'          => $this->load->view("Admin/$pagePath", $data, true),
            'pageTitle'        => $pageTitle,
            'userName'         => $this->_getAgentAccount()->fname,
            'controller'       => $this->router->fetch_class(),
            'method'           => $this->router->fetch_method(),
            'siteMode'         => $this->_getSiteMode(),
            'deployMenuItems'  => \RightNow\Internal\Utils\Deployment::getDeployMenuItems(true),
            'folderSearchList' => json_encode(\RightNow\Internal\Utils\Admin::getAllWidgetDirectories()),
        ) + $options);
    }

    /**
     * JSON-encodes the specified value and echoes it out,
     * along with the appropriate content type header, and
     * terminates execution.
     * @param (Object|Array) $toRender Complex data to encode
     */
    protected function _renderJSONAndExit($toRender) {
        $this->_echoJSONAndExit(json_encode($toRender));
    }

    /**
     * Echoes out the specified JSON value,
     * along with the appropriate content type header, and
     * terminates execution.
     * @param String $toEcho JSON data to echo
     */
    protected function _echoJSONAndExit($toEcho) {
        parent::_echoJSON($toEcho);
        // assume that any admin controller that calls this function has done any explicit rolling back, if necessary;
        // otherwise, we'll commit anything that has changed, since the exit below will prevent the post_controller
        // hook from running
        \RightNow\Utils\Framework::runSqlMailCommitHook();
        exit;
    }

    /**
     * JSON-encodes the specified value and echoes it out,
     * along with the appropriate content type header
     * with a flush at the end
     * @param (Object|Array) $toRender Complex data to encode
     */
    protected function _renderJSONWithFlush($toRender) {
        $this->_echoJSONWithFlush(json_encode($toRender));
    }

    /**
     * Echoes out the specified JSON value,
     * along with the appropriate content type header
     * with a flush at the end
     * @param String $toEcho JSON data to echo
     */
    protected function _echoJSONWithFlush($toEcho) {
        parent::_echoJSON($toEcho);
        flush();
    }

    /**
     * Processes JS and CSS specified by the caller to #_render.
     * Does different things based on the current environment.
     * If this is a dev site then we're basically in "admin development mode".
     * The resources are parsed for required file declarations and those are
     * included in the page as well as the specified resources.
     * If this is a hosted site then assets and their dependencies have already been combined,
     * so the specified resources are just included.
     * @param Array $options What was passed to #_render; the keys that are looked at are 'js' and 'css'.
     * @param mixed $isHosted Specify true for unit testing hosted mode.
     * @return Array The first element is a string of script tags (null if none); the second element is a string of CSS links (null if none)
     */
    private function _garnerResources(array $options, $isHosted = null) {
        if ($isHosted ?: IS_HOSTED) {
            $buildTags = function($resources, $type) {
                if ($resources = $resources[$type]) {
                    $tag = ($type === 'js') ? "<script src='%s'></script>" : "<link rel='stylesheet' href='%s'/>";
                    $includes = array();

                    if (!is_array($resources)) {
                        $resources = array($resources);
                    }

                    foreach ($resources as $file) {
                        if (!Text::beginsWith($file, '/') && !Text::beginsWith($file, 'thirdParty')) {
                            // Assumed relative to core/admin.
                            $file = "admin/{$type}/{$file}";
                        }
                        if (!Text::endsWith($file, ".{$type}")) {
                            // File extension may be omitted.
                            $file .= ".{$type}";
                        }
                        $includes []= sprintf($tag, $file);
                    }
                    return implode('', $includes);
                }
            };
        }
        else {
            $buildTags = function($resources, $type) {
                if (isset($resources[$type]) && $resources = $resources[$type]) {
                    if (!is_array($resources)) {
                        $resources = array($resources);
                    }
                    $tag = ($type === 'js') ? "<script src='%s'></script>" : "<link rel='stylesheet' href='%s'/>";
                    $includes = $absolutePaths = array();

                    // Build up tags for the given resources, create absolute paths for #processAssetDirectives.
                    foreach ($resources as $file) {
                        if (Text::beginsWith($file, '/') || Text::beginsWith($file, 'thirdParty')) {
                            // Absolute path given.
                            $absolutePaths []= $file;
                        }
                        else {
                            // Assumed relative to core/admin.
                            $file = "admin/{$type}/{$file}";
                            if (!Text::endsWith($file, ".{$type}")) {
                                // File extension may be omitted.
                                $file .= ".{$type}";
                            }
                            $absolutePaths []= HTMLROOT . \RightNow\Utils\Url::getCoreAssetPath($file);
                        }
                        $includes []= sprintf($tag, $file);
                    }
                    $additional = \RightNow\Internal\Utils\Admin::processAssetDirectives($absolutePaths);
                    if ($additional['all']) {
                        foreach ($additional['all'] as $file) {
                            $includes []= sprintf($tag, $file);
                        }
                    }

                    return implode("\n", $includes);
                }
            };
        }

        return array(
            $buildTags($options, 'js'),
            $buildTags($options, 'css'),
        );
    }

    private function _getSiteMode() {
        list($location) = \RightNow\Environment\retrieveModeAndModeTokenFromCookie();
        if ($location === 'development')
            $mode = \RightNow\Utils\Config::getMessage(DEVELOPMENT_LBL);
        else if ($location === 'reference')
            $mode = \RightNow\Utils\Config::getMessage(REFERENCE_LBL);
        else if ($location === 'okcs_reference')
            $mode = \RightNow\Utils\Config::getMessage(OKCS_REFERENCE_LBL);
        else if (Text::beginsWith($location, STAGING_PREFIX))
            $mode = \RightNow\Utils\Config::getMessage(STAGING_LBL);
        else
            $mode = \RightNow\Utils\Config::getMessage(PRODUCTION_LBL);

        $agent = isset($_COOKIE['agent']) ? $_COOKIE['agent'] : 'default';
        if ($agent === 'default')
            $agent = \RightNow\Utils\Config::getMessage(BROWSER_USER_AGENT_LBL);
        else if ($agent === '/')
            $agent = \RightNow\Utils\Config::getMessage(STANDARD_LBL);
        return compact('agent', 'mode');
    }

    /**
     * Return key from $array in a case-insensitive manner.
     * Based on an example from http://us2.php.net/manual/en/function.array-key-exists.php
     * We may consider moving this somewhere more generic if needed elsewhere.
     *
     * @param string $key Key to check for
     * @param mixed $array Array to look in
     * @return string|false
     */
    private function _getArrayKeyCaseInsensitive($key, $array) {
        if (array_key_exists($key, $array)) {
            return $key;
        }
        if (!(is_string($key) && is_array($array) && count($array))) {
            return false;
        }
        $key = strtolower($key);
        foreach (array_keys($array) as $arrayKey) {
            if (strtolower($arrayKey) === $key) {
                return $arrayKey;
            }
        }
        return false;
    }

    /**
     * Returns the rendered view. Used for JS testing where we
     * simply want the view--without a template and added js / css.
     * @param string $pagePath Path to admin view
     * @param array|null $pageData Data to pass to the view
     * @return string Rendered view
     */
    private function _renderIsolatedView($pagePath, $pageData) {
        return $this->load->view("Admin/$pagePath", $pageData, true);
    }
    
    /**
     * Writes error message to resposne
     * @param string $errorMessage Error Message
     */
    private function renderErrorResponseObject($errorMessage) {
        if($this->isAjaxRequest()) {
            $responseObject = new \RightNow\Libraries\ResponseObject('is_string');
            $responseObject->error = array('externalMessage' => $errorMessage, 'displayToUser' => true);
            header('Content-Type: application/json');
            echo $responseObject->toJson(array(), true);
            exit();
        }
        else {
            Framework::writeContentWithLengthAndExit($errorMessage);
        }
    }
}
