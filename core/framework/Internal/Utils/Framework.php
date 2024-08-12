<?php
namespace RightNow\Internal\Utils;

use RightNow\ActionCapture,
    RightNow\Api,
    RightNow\Utils\Text,
    RightNow\Connect\v1_4 as ConnectExternal,
    RightNow\Utils\Config as ConfigExternal,
    RightNow\Utils\FileSystem as FileSystemExternal,
    RightNow\Utils\Framework as FrameworkExternal,
    RightNow\Internal\Utils\Version as Version,
    RightNow\Utils\Url as UrlExternal;

class Framework
{
    /**
     * Cache of extensions.yml contents.
     * @var array
     */
    private static $codeExtensions;

    /**
     * Iterates over all levels of output buffering and kills each one.
     */
    public static function killAllOutputBuffering()
    {
        while (ob_get_level() > 0)
        {
            ob_end_clean();
        }
    }

    /**
     * Takes PHP code content, evals it with the specified scope, and returns the resulting content
     * @param string $code The code to execute
     * @param string $relativePathToContent Path to the content being executed, relative to the /cp/customer directory. Does not
     * affect how code is executed, but helps display paths in error messages correctly.
     * @param object|null $scope Scope within which to execute the code. The object provided will become $this within the executed code. Access
     * to private and protected methods will still be disallowed.
     * @return string The code after running through eval
     * @throws \Exception If the code being eval'd throws an exception
     */
    public static function evalCodeAndCaptureOutputWithScope($code, $relativePathToContent='', $scope = null) {
        ob_start();
        $code = "try{?>$code<?}catch(\Exception \$e){return \$e;}";
        $exception = Api::trusted_eval("/scripts/cp/customer/$relativePathToContent", $code, $scope);
        $fileContent = ob_get_clean();
        if($exception instanceof \Exception)
            throw $exception;
        return $fileContent;
    }

    public static function doesTokenRequireChallenge($token)
    {
        // Check to see if token is legitimate
        if (!is_string($token) || strlen($token) === 0)
        {
            return false;
        }

        // Do the token contents pass the smell test.
        $decodedToken = Api::decode_base64_urlsafe($token);
        list($id, $hexTime, $doesTokenExpire, $interfaceName, $contactID, $isChallengeRequired) = explode('|', Api::ver_ske_decrypt($decodedToken));
        return (bool)$isChallengeRequired;
    }

    /**
     * This was copied out of rnt_security.php.
     * It has been modified from the original for the Abuse Detection System.
     * @param int $id ID of object
     * @param int $expires Timestamp of expiration time
     * @param int $contactID ID of contact
     * @param bool $isChallengeRequired Whether captcha is required on current form
     * @param bool $csrfToken When true its CSRF token to be used in form submits via f_tok parameter
     * @return string Generated CSRF token
     */
    public static function createCsrfToken($id, $expires, $contactID = 0, $isChallengeRequired = false, $csrfToken = false)
    {
        $sessionID = get_instance()->session->getSessionData('sessionID');
        $tokenSessionID = $contactID === 0 ? $sessionID : $sessionID . $contactID;
        $token = implode('|', array($id, bin2hex(time()), $expires, Api::intf_name(), $tokenSessionID, (int)$isChallengeRequired, $csrfToken));
        return Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe($token));
    }

    /**
     * This was copied out of rnt_security.php.
     * It has been modified from the original for the Abuse Detection System.
     * @param string $token Token to test
     * @param int $expectedID Expected object ID for non admin requests and agent ID for admin requests encoded in token
     * @param int $expectedContactID Expected contact ID
     * @param boolean $isAdminRequest True for admin requests else false
     * @param boolean $isStrict True for validating the token against the existing session else false
     * @return bool Whether the token is valid
     */
    public static function testCsrfToken($token, $expectedID, $expectedContactID = 0, $isAdminRequest = false, $isStrict = true)
    {
        // Check to see if token is legitimate
        if (!is_string($token) || strlen($token) === 0)
        {
            return false;
        }
        // Do the token contents pass the smell test.
        $decodedToken = Api::decode_base64_urlsafe($token);
        if($isAdminRequest)
        {
            list($hexTime, $doesTokenExpire, $interfaceName, $agentID, $isChallengeRequired, $csrfToken) = explode('|', Api::ver_ske_decrypt($decodedToken));
            if (($interfaceName !== Api::intf_name()) || (((int)$agentID) !== $expectedID))
            {
                return false;
            }
        }
        else {
            list($id, $hexTime, $doesTokenExpire, $interfaceName, $tokenSessionID, $isChallengeRequired, $csrfToken) = explode('|', Api::ver_ske_decrypt($decodedToken));
            $sessionID = get_instance()->session->getSessionData('sessionID');
            // if current sessionID is false, then we're intentionally not tracking sessions (happens with /ci/cache/rss)
            if($sessionID === false)
            {
                $sessionID = '';
            }
            $expectedSessionID = ($expectedContactID === 0) ? $sessionID : $sessionID . $expectedContactID;
            if ((((int)$expectedID) !== ((int)$id)) || ($interfaceName !== Api::intf_name()) || ($isStrict && ($tokenSessionID !== $expectedSessionID)))
            {
                // allow widget JS unit tests to fail the check
                $oeWebServer = ConfigExternal::getConfig(OE_WEB_SERVER);
                if (IS_HOSTED ||
                    ((!Text::beginsWith($_SERVER['HTTP_RNT_REFERRER'], "http://{$oeWebServer}/ci/unitTest/rendering/getTestPage/widgets/")) &&
                    (!Text::beginsWith($_SERVER['HTTP_RNT_REFERRER'], "{$oeWebServer}/ci/unitTest/rendering/getTestPage/widgets/"))))
                {
                    return false;
                }
            }
        }

        // Check the time stamp and compute the expired time and compare
        // to the configured limit if the token has the expires flag set.
        // $maxTime is stored in minutes, checked in seconds.
        if ($doesTokenExpire !== '1')
        {
            return true;
        }

        $maxTime = 60 * ConfigExternal::getConfig(SUBMIT_TOKEN_EXP);
        // Convert the time, which was stored as a hex number, back into an string containing the binary equivalent.
        $time = pack('H*', $hexTime);
        $tokenExpired = (time() - $time) >= $maxTime;
        if($tokenExpired === true){
            return false;
        }

        if((bool)$csrfToken === true) {
            //check in mamcached if token already exists.. if so its already consumed token
            $cache = new \RightNow\Libraries\Cache\Memcache($maxTime);
            try {
                $tokenInfo = $cache->get($token);
                if ($tokenInfo === false) {
                    $cache->set($token, $token);
                    return true;
                }
                return false;
            }
            catch(Exception $e) {
                //error communicating to memcached or with saving
                return true;
            }
        }
        //if its not CSRF token and its not expired it will be coming here.. its true because it passed all validation checks above
        return true;
    }

    /**
     * Creates the token for admin requests to prevent the CSRF attacks
     *
     * @param type $expires Timestamp of expiration time
     * @param type $agentID ID of an Agent
     * @param type $isChallengeRequired Whether captcha is required on current form
     * @return string Generated CSRF token
     */
    public static function createAdminPageCsrfToken($expires, $agentID, $isChallengeRequired = false)
    {
        if (!$agentID)
        {
            return null;
        }
        $token = implode('|', array(bin2hex(time()), $expires, Api::intf_name(), $agentID, (int) $isChallengeRequired, 0));
        return Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe($token));
    }

    /**
     * Creates location cookie token
     * @param string $mode Location mode
     * @param bool $useOldEncryption Whether to use pw_rev_encrypt for backwards compatibility or ver_ske_encrypt_fast_urlsafe
     * as the new encryption mechanism. The older encryption function will only be used if we are creating a token for
     * an older (pre-3.4) framework to consume.
     * @return string Generated token for location cookie
     */
    public static function createLocationToken($mode, $useOldEncryption = false)
    {
        $id = crc32($mode);
        // need to pack in id, time, doesTokenExpire, interfaceName, contactID, and isChallengeRequired
        // for backwards compatibility reasons
        $token = implode('|', array($id, bin2hex(time()), 0, Api::intf_name(), 0, (int)false));
        if ($useOldEncryption)
            $token = Api::pw_rev_encrypt($token);
        else
            $token = Api::ver_ske_encrypt_fast_urlsafe($token);
        return Api::encode_base64_urlsafe($token);
    }

    /**
     * Tests location cookie token
     * @param string $token Token to test from location cookie
     * @param string $mode Location mode
     * @return bool Whether the token is valid
     */
    public static function testLocationToken($token, $mode)
    {
        // Check to see if token and mode are legitimate
        if (!is_string($token) || strlen($token) === 0 || !is_string($mode) || strlen($mode) === 0)
        {
            return false;
        }

        $expectedID = crc32($mode);

        // If Dev mode is on framework < 3.4 then use old decryption method
        $frameworkVersions = Version::getVersionsInEnvironments('framework');
        $versionToCheck = isset($frameworkVersions['Development']) ? $frameworkVersions['Development'] : null;
        if ($versionToCheck)
        {
            $useOldDecryption = (Version::compareVersionNumbers($versionToCheck, "3.4") === -1);
        }
        else
        {
            $useOldDecryption = false;
        }

        // hexTime, doesTokenExpire, contactID, and isChallengeRequired are ignored, but necessary for backwards compatibility
        $decodedToken = Api::decode_base64_urlsafe($token);
        list($id, $hexTime, $doesTokenExpire, $interfaceName, $contactID, $isChallengeRequired) = explode('|', $useOldDecryption ? Api::pw_rev_decrypt($decodedToken) : Api::ver_ske_decrypt($decodedToken));

        if ((((int)$expectedID) !== ((int)$id)) ||
            ($interfaceName !== Api::intf_name()))
        {
            return false;
        }

        return true;
    }

    public static function installPathRestrictions()
    {
        if(IS_ADMIN || (!CUSTOM_CONTROLLER_REQUEST && get_instance()->router->fetch_class() === 'openlogin'))
            return;
        static $haveRestrictionsBeenAdded = false;
        if ($haveRestrictionsBeenAdded)
        {
            return;
        }
        $haveRestrictionsBeenAdded = true;

        $allowedBaseDirectories = array(
            CORE_FILES,
            CUSTOMER_FILES,
            CPCORESRC,
            OPTIMIZED_FILES,
            DOCROOT . '/ma/util.phph',
            DOCROOT . '/ma/cci/head.phph',
            DOCROOT . '/ma/cci/top.phph',
            DOCROOT . '/ma/cci/bottom.phph',
            DOCROOT . '/views/view_utils.phph',
            HTMLROOT . '/euf/',
            HTMLROOT . '/rnt/rnw/yui_2.7',
            HTMLROOT . YUI_SOURCE_DIR,
            DOCROOT . '/../log/',
            DOCROOT . '/include/ConnectPHP/',
            DOCROOT . '/ConnectPHP/',  //Allow old ConnectPHP path as well
            DOCROOT . '/cp/mod_info.phph',
            '/tmp/', //Allow customer access to tmp
            '/prod_tmp/', //Allow file attachment uploads
            dirname(DOCROOT) . '/webindex/',
            DOCROOT . '/../configs', // allow custom interface config/msgbases
            '/configs', // allow custom site config/msgbases
            // We'd need to allow access to RightNow.UI.AbuseDetection.Provider.js because it's included dynamically in the JSON response when abuse is detected.
        );

        if (!IS_OPTIMIZED)
        {
            if (IS_HOSTED)
            {
                $allowedUnoptimizedDirectories = array(
                    HTMLROOT,
                    HTMLROOT . '/rnt/rnw/',
                    DOCROOT . '/include/src/',
                    DOCROOT . '/cp/src/mod_info.phph',
                );
            }
            else
            {
                $allowedUnoptimizedDirectories = array(
                    // These have to be enumerated separately because of the symlinking things we do inside our per_site_html_root
                    HTMLROOT,
                    HTMLROOT . '/rnt/rnw/yui_2.3/',
                    HTMLROOT . '/rnt/rnw/yui_2.4/',
                    // If there are other things under /rnt/rnw/ that we need access to, we'll have to list them all separately too because of symlinking.

                    // I have to do this funny dance with dirname and realpath because of the way we symlink phph files into our cfg directories.
                    // The stuff under here is intended to be equivalent to DOCROOT/include/src.
                    dirname(realpath(DOCROOT . '/include/config/common.phph')),
                    dirname(realpath(DOCROOT . '/include/config/rnw_common.phph')),
                    dirname(realpath(DOCROOT . '/include/msgbase/common.phph')),
                    dirname(realpath(DOCROOT . '/include/msgbase/rnw.phph')),
                    dirname(realpath(DOCROOT . '/include/htdig.phph')),

                    // This list is intended to be functionally equivalent to the path DOCROOT . '/include/src/' in hosting.
                    dirname(realpath(DOCROOT . '/include/rnwintf.phph')),

                    // For setting up test databases
                    get_cfg_var('rnt.cgi_root'),
                );
            }
            if (IS_ADMIN)
            {
                // For some reason the admin template needs to scan the log directory to set up the menu items.
                $allowedUnoptimizedDirectories[]= realpath(DOCROOT . '/../log/');
            }
            $allowedBaseDirectories = array_merge($allowedBaseDirectories, $allowedUnoptimizedDirectories);
        }
        if (!IS_HOSTED)
        {
            // We always have to allow this in non-hosted because CP deploy doesn't do a script compile on files
            // outside of rnw/euf, such as view_utils which slurps this file in.  In a hosted land, that's not a
            // problem because define replacements have been done everywhere.
            $allowedBaseDirectories[]= DOCROOT . '/include/views/view_defines.phph';

            // custom code can access intf.cfg/tmp/
            $allowedBaseDirectories[]= DOCROOT . '/../tmp/';
            // fattach is done locally from intf.cfg/prod_tmp/
            $allowedBaseDirectories[]= DOCROOT . '/../prod_tmp/';

            // allow cp-versions to run /ci/UnitTest/ValidateCPHistory
            $allowedBaseDirectories[]= DOCROOT . '/cp/versions/';

            // when a custom controller cannot be found on a dev site, the controller is changed to the page controller
            // to show the 404 page, but the path restrictions are still put in place since CUSTOM_CONTROLLER_REQUEST is still
            // defined as true, so adding these additional locations only on non-hosted to get the expected 404 response
            $allowedBaseDirectories[]= dirname(realpath(DOCROOT . '/include/config/config.phph'));
            $allowedBaseDirectories[]= dirname(realpath(DOCROOT . '/include/msgbase/msgbase.phph'));
            $allowedBaseDirectories[]= dirname(realpath(DOCROOT . '/include/rnwintf.phph'));
        }
        ini_set('open_basedir', implode(':', $allowedBaseDirectories));
    }

    /**
     * Move contents of core/framework into a core/framework/{version} directory.
     * Called by tarball deploy.
     *
     * @param string|null $frameworkPath Path to core/framework. Defaults to CPCORE.
     * @param string|null $version Version in the form '{major}.{minor}.{nano}'
     * @throws \Exception If directory could not be inserted
     */
    public static function insertFrameworkVersionDirectory($frameworkPath = null, $version = null) {
        self::insertVersionDirectory($frameworkPath ?: CPCORE, $version ?: FrameworkExternal::getFrameworkVersion());
    }

    /**
     * Run #insertVersionDirectory on the widget paths specified or on ALL widgets if $widgetPaths isn't specified.
     * @param array $widgetPaths List of absolute widget paths
     * @throws \Exception If an error is encountered
     */
    public static function insertWidgetVersionDirectories(array $widgetPaths = array()) {
        \RightNow\Internal\Libraries\Widget\Registry::setSourceBasePath(APPPATH);
        if (!$widgetPaths) {
            $widgetPaths = array_keys(\RightNow\Internal\Libraries\Widget\Registry::getStandardWidgets(true));
        }

        foreach ($widgetPaths as $widgetPath) {
            try{
                $version = \RightNow\Utils\Widgets::getFullVersionFromManifest($widgetPath);
                self::insertVersionDirectory($widgetPath, $version);
            }
            catch(\Exception $e){
                //This can throw an exception since we've deprecated widgets which still claim support for the current major.minor
                //framework version and are still returned from the Registery::getStandardWidgets() method, but don't actually
                //exist on disk so we can't get their current version.
            }
        }
    }

    /**
     * Given a post handler, attempt to load the file and validate that the method and class in the path exist.
     * @param string $handlerPath The path to the library and method being validated (e.g. postRequest/sendForm)
     * @param boolean $instantiateClass A flag indicating whether or not the class should be instantiated and returned
     * @return mixed Either an instance of the class or boolean true or false indicating whether the class was found
     */
    public static function validatePostHandler($handlerPath, $instantiateClass = false) {
        $handlerPath = explode('/', $handlerPath);
        if(!$handlerPath || count($handlerPath) < 2) {
            return false;
        }

        $method = array_pop($handlerPath);
        $testedPaths = array(
            implode('/', $handlerPath),
            implode('/', array_slice($handlerPath, 0, -1)) . (count($handlerPath) > 1 ? '/' : '') . ucfirst($handlerPath[count($handlerPath) - 1])
        );

        require_once CPCORE . 'Libraries/PostRequest.php';
        foreach(array(APPPATH . "libraries", CPCORE . "Libraries") as $absolutePath) {
            foreach($testedPaths as $path) {
                if(FileSystemExternal::isReadableFile("$absolutePath/$path" . EXT)) {
                    require_once "$absolutePath/$path" . EXT;

                    //If the chosen library inherits from the post request handler create the instance and return it
                    $splitPath = explode('/', $path);
                    $class = (Text::stringContains($absolutePath, APPPATH) ? '\Custom\Libraries\\' : '\RightNow\Libraries\\') . array_pop($splitPath);
                    if(class_exists($class) && (is_subclass_of($class, '\RightNow\Libraries\PostRequest') || $class === '\RightNow\Libraries\PostRequest') && in_array($method, get_class_methods($class))) {
                        return ($instantiateClass) ? new $class() : true;
                    }

                    //If a file is loaded, but did not contain a valid class, bomb out to avoid loading additional files. The method and class should always appear in the first file loaded.
                    return false;
                }
            }
        }
        return false;
    }

    /**
     * Return an array of custom field attributes from Framework::getCustomFieldList for specified $table and $fieldName.
     * The array will also contain keys 'enduser_writable' and 'enduser_visible'
     *
     * @param string $table One of 'Contact', 'Incident' or 'Answer'.
     * @param string $fieldName The column name of the custom field (e.g. mktg_optin)
     * @return array|null Details about the custom field or null if it doesn't exist
     * @throws \Exception If not a valid table.
    */
    public static function getCustomField($table, $fieldName) {
        $key = "getCustomField-$table-$fieldName";
        if ($field = FrameworkExternal::checkCache($key)) {
            return $field;
        }

        if (!Text::beginsWith($fieldName, 'c$')) {
            $fieldName = "c\${$fieldName}";
        }

        $tables = array('Contact' => VTBL_CONTACTS, 'Incident' => VTBL_INCIDENTS, 'Answer' => TBL_ANSWERS);
        if (!$tableDefine = $tables[$table]) {
            throw new \Exception("Invalid table '$table'. Only 'Contact', 'Incident' or 'Answer' allowed.");
        }

        foreach (FrameworkExternal::getCustomFieldList($tableDefine, VIS_CF_ALL) as $field) {
            if ($field['col_name'] === $fieldName) {
                $enduserWritable = ($field['visibility'] & VIS_ENDUSER_EDIT_RW);
                if(($field['visibility'] & VIS_LIVE_CHAT) && (get_instance()->page === ConfigExternal::getConfig(CP_CHAT_URL) || Text::beginsWithCaseInsensitive(ORIGINAL_REQUEST_URI, '/ci/admin'))) {
                    //Any chat visible field is both end user writable and visible on the chat and admin pages.
                    $enduserWritable = true;
                }
                $result = array(
                    'enduser_writable' => (bool)$enduserWritable,
                    'required' => ($field['required'] === 1),
                );
                $result['enduser_visible'] = $result['enduser_writable'] || (($field['visibility'] & (VIS_ENDUSER_DISPLAY | VIS_ENDUSER_EDIT_RO)) == true);
                FrameworkExternal::setCache($key, $result);
                return $result;
            }
        }
    }

    /**
     * Determines if the given array contains an object (according to is_object).
     * @param array $array The array
     * @return bool Whether the array contains an object
     */
    public static function doesArrayContainAnObject(array $array)
    {
        foreach ($array as $value)
        {
            if (is_object($value))
                return true;
            if (is_array($value) && self::doesArrayContainAnObject($value))
                return true;
        }
        return false;
    }

    /**
     * Create $directoryPath/$version directory and move all files and directories into the new directory.
     * Called by tarball deploy.
     * @param string $directoryPath Absolute directory path
     * @param string $version Version to create as a sub-directory of $directoryPath must be in the form {major}.{minor}.{nano}
     * @throws \Exception If the specified version is invalid or if there's a problem creating directories
     */
    public static function insertVersionDirectory($directoryPath, $version) {
        if (!preg_match('/^\d+\.\d+(\.\d+)?$/', $version)) {
            throw new \Exception("Not a valid version: '$version'");
        }
        $directoryContents = FileSystemExternal::listDirectory($directoryPath, false, false, null, array('getType'));
        FileSystemExternal::mkdirOrThrowExceptionOnFailure("$directoryPath/$version", true);
        foreach($directoryContents as $pair) {
            list($basename, $type) = $pair;
            if ($basename === 'changelog.yml' || !in_array($type, array('dir', 'file'))) {
                echo "Skipping [$type] $source\n";
                continue;
            }
            $source = "$directoryPath/$basename";
            $target = "$directoryPath/$version/$basename";
            if (!rename($source, $target)) {
                throw new \Exception("Could not rename $source");
            }
        }
    }

    /**
     * Return the list of special URL parameters to preserve during a login redirect
     * @return array List of special URL parameters
     */
    public static function getPreservedParameters() {
        return array('a_id', 'asset_id', 'i_id', 'kw', 'p', 'c', 'email', 'cid', 'unsub', 'request_source', 'chat_data', 'step', 'product_id', 'serial_no', 'qid', 'user', 'comment');
    }

    /**
     * Function to determine if a page request is allowed
     * @param string $pagePath Fully qualified path to the currently executing page
     * @param object $CI Page controller instance
     * @return array|null Null if page access is allowed, otherwise an array of what action the page controller
     *   should take (redirect to login, redirect to url, redirect to error page)
     */
    public static function pageAllowed($pagePath, $CI) {
        static $authorizationChecks = array('pageAllowedContact', 'pageAllowedAnswer', 'pageAllowedSocialQuestion', 'pageAllowedSocialUser', 'pageAllowedAsset', 'pageAllowedIncident', 'pageAllowedSocialModerator', 'pageAllowedProductCategory');

        foreach ($authorizationChecks as $methodName) {
            if ($result = self::$methodName($CI, $pagePath)) {
                return $result;
            }
        }
        if (ConfigExternal::getConfig(OKCS_ENABLED) && !(ConfigExternal::getConfig(MOD_RNANS_ENABLED) && IS_PRODUCTION) && !\RightNow\Utils\Url::getParameter('s') && ($result = self::okcsPageAllowedAnswer($CI))) {
            return $result;
        }
    }

    /**
     * Returns the list of extensions configured in the extensions.yml file.
     * @param string $topLevelKey Top level key in the expected YAML list to
     *                            return the value for
     * @return boolean|array False if the file doesn't exist or is invalid YAML
     *                             Array the specified key's value
     */
    public static function getCodeExtensions($topLevelKey = null) {
        if (is_null(self::$codeExtensions)) {
            self::$codeExtensions = @yaml_parse_file(APPPATH . "config/extensions.yml");
        }
        if($topLevelKey && is_array(self::$codeExtensions)) { 
            return isset(self::$codeExtensions[$topLevelKey]) ? self::$codeExtensions[$topLevelKey] : null; 
        }else{ 
           return  self::$codeExtensions;
        }
    }

    /**
     * Sets the Content Length and Type headers and returns a JSON encoded string.
     * @param mixed $responseData The data to be JSON encoded if $encoded is true or an already encoded string.
     * @param bool $encode If true $responseData is JSON encoded.
     * @return string The JSON encoded string
     */
    public static function jsonResponse($responseData, $encode = true) {
        $content = $encode ? json_encode($responseData) : $responseData;
        header('Content-Length: ' . strlen("$content"));
        header('Content-Type: application/json');
        return $content;
    }

    /**
     * Returns the Social User of the currently logged-in contact, providing the statusType and optional permissions are met.
     * @param integer|null  $statusType  The statusType, as defined in one of the STATUS_TYPE_SSS_USER_* defines
     * @param integer|array $permissions The permission to verify, as defined by one of the PERM_* defines, or an array of permissions.
     * @return object|null  The Social User object if exists and conditions met, else null.
     */
    public static function getSocialUser($statusType = null, $permissions = null) {
        if (($socialUser = get_instance()->model('CommunityUser')->get()->result) &&
            (!$statusType || $socialUser->StatusWithType->StatusType->ID === $statusType)) {
            if ($permissions && ($permissions = (is_array($permissions) ? $permissions : array($permissions)))) {
                $currentContext = ConnectExternal\ConnectAPI::getCurrentContext();
                foreach($permissions as $permission) {
                    if (!$currentContext->hasPermission($permission)) {
                        return;
                    }
                }
            }
            return $socialUser;
        }
    }

    /**
     * Returns an assocative array array(50600=> 5.6, 80100=>8.1) containing the list of supported PHP versions for the current CP framework version
     * @return array If mapping is not defined in cpHistory empty array will be returned
     */
    public static function getSupportedPhpVersions()
    {
        $key = "supportedPhpVersions-" . CP_FRAMEWORK_VERSION;
        if ($data = FrameworkExternal::checkCache($key)) {
            return $data;
        }

        $allVersions = Version::getVersionHistory();
        $cpPhpVersions = isset($allVersions['rnPhpVersions'][CP_FRAMEWORK_VERSION]) ? $allVersions['rnPhpVersions'][CP_FRAMEWORK_VERSION] : array(CP_DEFAULT_PHP_VERSION);
        $cxPhpVersions = optl_get(OPTL_SUPPORTED_PHP_VERSIONS);
        $phpVersionList = array();

        foreach ($cpPhpVersions as $phpVersion) {
            if (array_key_exists($phpVersion, $cxPhpVersions)) {
                $phpVersionList[$phpVersion] = $cxPhpVersions[$phpVersion];
            }
        }

        FrameworkExternal::setCache($key, $phpVersionList);
        return $phpVersionList;
    }

    /**
     * Method with return the lable to be displayed for a PHP Version
     * @param string $version Contains internal representation of PHP version 
     * @param Boolean $sanitise Boolean variable when true extract only the version xx.xx from label
     * @return string Containg the PHP version label
     */
    public static function getPhpVersionLabel($version = null, $sanitise=false)
    {
        $version = (!empty($version)) ? $version : CP_PHP_VERSION;
        $key = "supportedPhpVersions-" . CP_FRAMEWORK_VERSION;
        if (!$phpVersionList = FrameworkExternal::checkCache($key)) {
            $phpVersionList = self::getSupportedPhpVersions();
        }
        if (!empty($phpVersionList) && isset($phpVersionList[$version])) {
            if ($sanitise === true) {
                preg_match('/^[0-9]{1,3}\.[0-9]{1,3}(\.[0-9]{1,3})?/', $phpVersionList[$version], $match);
                $versionLabel = (isset($match[0]) && !empty($match[0])) ? $match[0] : trim(str_ireplace('Deprecated', '', $phpVersionList[$version]));
                return $versionLabel;
            }
            return $phpVersionList[$version];
        }
        return CP_DEFAULT_PHP_VERSION_LABEL;
    }

    /**
     * Function to determine if a page request is valid for a contact
     * @param object $CI Page controller instance
     * @param string $pagePath Fully qualified path to the currently executing page
     * @return array|null Null if page access is allowed, otherwise an array of what action the page controller
     *   should take (redirect to login, redirect to url, redirect to error page)
     */
    private static function pageAllowedContact($CI, $pagePath) {
        if ($result = self::pageAllowedSla($CI))
            return $result;
        return self::pageAllowedPta($pagePath);
    }

    /**
     * Function to determine if a page request is valid for a contact based on SLA settings
     * @param object $CI Page controller instance
     * @return array|null Null if page access is allowed, otherwise an array of what action the page controller
     *   should take (redirect to login, redirect to url, redirect to error page)
     */
    private static function pageAllowedSla($CI) {
        $meta = $CI->meta;
        $session = $CI->session;

        //Check if sla requirement has been met
        if(isset($meta['sla_required_type']) && $meta['sla_required_type'])
        {
            if(FrameworkExternal::isLoggedIn())
            {
                if($meta['sla_required_type'] == 'chat')
                    $slaType = 'slac';
                else if($meta['sla_required_type'] == 'incident')
                    $slaType = 'slai';
                else if($meta['sla_required_type'] == 'selfservice')
                    $slaType = 'webAccess';

                if(!$session->getProfileData($slaType))
                {
                    if($meta['sla_failed_page'])
                    {
                        return array('type' => 'location', 'url' => $meta['sla_failed_page'] . UrlExternal::sessionParameter());
                    }
                    return array('type' => 'error', 'code' => FrameworkExternal::DOCUMENT_PERMISSION);
                }
            }
            else
            {
                return array('type' => 'login');
            }
        }
        else if(isset($meta['sla_failed_page']) && $meta['sla_failed_page'])
        {
            if(FrameworkExternal::isLoggedIn())
            {
                if(!$session->getProfileData('webAccess'))
                {
                    return array('type' => 'location', 'url' => $meta['sla_failed_page'] . UrlExternal::sessionParameter());
                }
            }
            else
            {
                return array('type' => 'login');
            }
        }
    }

    /**
     * Function to determine if a page request is valid for a contact based on PTA settings
     * @param string $pagePath Fully qualified path to the currently executing page
     * @return array|null Null if page access is allowed, otherwise an array of what action the page controller
     *   should take (redirect to login, redirect to url, redirect to error page)
     */
    private static function pageAllowedPta($pagePath) {
        //If pta is being used don't allow access to login, account_assistance pages
        if(ConfigExternal::getConfig(PTA_ENABLED) && !ConfigExternal::getConfig(PTA_IGNORE_CONTACT_PASSWORD)
            && (Text::stringContains($pagePath, ConfigExternal::getConfig(CP_ACCOUNT_ASSIST_URL)) || Text::stringContains($pagePath, ConfigExternal::getConfig(CP_LOGIN_URL)))) {
            return array('type' => 'error', 'code' => FrameworkExternal::DOCUMENT_PERMISSION);
        }
    }

    /**
     * Function to determine if a page request for an answer
     * @param object $CI Page controller instance
     * @return array|null Null if page access is allowed, otherwise an array of what action the page controller
     *   should take (redirect to login, redirect to url, redirect to error page)
     */
    private static function pageAllowedAnswer($CI) {
        $meta = $CI->meta;

        //Check if page is some sort of answer listing
        if(!isset($meta['answer_details']) || $meta['answer_details'] !== 'true')
            return;

        $errorResponse = array('type' => 'error', 'code' => FrameworkExternal::ANSWER_UNAVAILABLE);

        if(!($answerID = UrlExternal::getParameter('a_id')) || !FrameworkExternal::isValidID($answerID)) {
            return $errorResponse;
        }

        $answer = $CI->model('Answer')->get($answerID);
        //A warning should imply that the answer exists, but the user doesn't have permission to see it
        if($answer->warnings) {
            if($CI->model('Answer')->isPrivate($answerID)) {
                return $errorResponse;
            }

            return array('type' => 'login');
        }
        if($answer->error) {
            if(!$CI->model('Answer')->exists($answerID))
                $errorResponse['permanentRedirect'] = true;
            return $errorResponse;
        }
        $answer = $answer->result;

        ActionCapture::record('answer', 'view', $answerID);

        if($answer->AnswerType->ID === ANSWER_TYPE_URL) {
            return array('type' => 'location', 'url' => $answer->URL);
        }
        if($answer->AnswerType->ID === ANSWER_TYPE_ATTACHMENT) {
            if (!$attachment = $answer->FileAttachments[0]) {
                return $errorResponse;
            }
            $attachmentUrl = $attachment->URL . $attachment->CreatedTime . "/redirect/1" . UrlExternal::sessionParameter() . "/filename/" . urlencode($attachment->FileName);
            return array('type' => 'location', 'url' => $attachmentUrl);
        }
    }

    /**
     * Function specific to OKCS to determine if a page request for an answer
     * @param object $CI Page controller instance
     * @return array|null Null if page access is allowed, otherwise an array of what action the page controller
     *   should take (redirect to login, redirect to url, redirect to error page)
     */
    private static function okcsPageAllowedAnswer($CI) {
        $answer = $CI->model('Okcs')->getArticleDetails(UrlExternal::getParameter('a_id'), 'v1');
        if(isset($answer) && !isset($answer->errors) && isset($answer['data'][0]['content']['URL_ANSWER/URL']) && $answer['data'][0]['content']['URL_ANSWER/URL']) {
            $url = $answer['data'][0]['content']['URL_ANSWER/URL']['value'];
            if($url) {
                preg_match('/^<a.*?href=(["\'])(.*?)\1.*$/', $url, $urlData);
                if(!empty($urlData)) {
                    $url = $urlData[2];
                }
                return array('type' => 'location', 'url' => $url);
            }
        }
    }

    /**
     * Determines if the current page contains a social question URL parameter and checks if the current user has access
     * @param object $CI Page controller instance
     * @return array|null Null if page access is allowed, otherwise an array of what action the page controller
     *   should take (redirect to login, redirect to url, redirect to error page)
     */
    private static function pageAllowedSocialQuestion($CI){
        $socialQuestionID = UrlExternal::getParameter('qid');

        if(is_null($socialQuestionID)) {
            return;
        }

        if(empty($socialQuestionID)) {
            return array('type' => 'error', 'code' => FrameworkExternal::ILLEGAL_PARAMETER, "permanentRedirect" => true);
        }

        if(!FrameworkExternal::isValidID($socialQuestionID)) {
            return array('type' => 'error', 'code' => FrameworkExternal::CONTENT_PERMISSION);
        }

        if(!$CI->model('CommunityQuestion')->get($socialQuestionID)->result) {
            //No social question was returned, so we redirect the user to the login page
            //if they are not logged in
            if(!FrameworkExternal::isLoggedIn()) {
                return array('type' => 'login');
            }

            if(!$CI->model('CommunityQuestion')->exists($socialQuestionID)) {
                return array('type' => 'error', 'code' => FrameworkExternal::QUESTION_UNAVAILABLE, 'permanentRedirect' => true);
            }

            return array('type' => 'error', 'code' => FrameworkExternal::CONTENT_PERMISSION);
        }
        ActionCapture::record('socialquestion', 'view', $socialQuestionID);
    }

    /**
     * Determines of the current page contains a social user URL parameter and checks if the current user
     * has access to see the profile page of the user specified by that parameter.
     * Allowed:
     *     - Existing, active or archived user
     * Not allowed:
     *     - User param is invalid
     *     - User doesn't exist
     *     - User is neither active nor archived _and_ the currently-logged-in user doesn't have access to edit that user.
     * @param  object $CI Page controller instance
     * @return array|null Null if page access is allowed, otherwise an error array
     */
    private static function pageAllowedSocialUser($CI) {
        $socialUser = UrlExternal::getParameter('user');

        $errorStatus = array('type' => 'error', 'code' => FrameworkExternal::CONTENT_PERMISSION);
        if(is_null($socialUser)) {
            return;
        }

        if(empty($socialUser)) {
            $errorStatusPermanentRedirect = $errorStatus;
            $errorStatusPermanentRedirect['permanentRedirect'] = true;
            return $errorStatusPermanentRedirect;
        }
         
        if (!FrameworkExternal::isValidID($socialUser)) return $errorStatus;

        $model = $CI->model('CommunityUser');

        if (!$socialUser = $model->get($socialUser)->result) return $errorStatus;

        if (!in_array($socialUser->StatusWithType->StatusType->ID, array(STATUS_TYPE_SSS_USER_ACTIVE, STATUS_TYPE_SSS_USER_ARCHIVE)) &&
            !$socialUser->SocialPermissions->canUpdateStatus()) {
            return $errorStatus;
        }
    }

    /**
     * Determines if the page request is for moderation dashboard and check if the current user has access to see the page.
     * Allowed:
     *     - Active social moderator
     * Not allowed:
     *     - User not logged in
     *     - Normal social user
     *     - Content moderator if request is for user moderation dashboard
     * @param  object $CI Page controller instance
     * @return array|null Null if page access is allowed, otherwise an error array
     */
    private static function pageAllowedSocialModerator($CI) {
        //Check if page has one of the page meta tags - social_moderator_required or social_user_moderator_required
        if ((!isset($CI->meta['social_moderator_required']) || $CI->meta['social_moderator_required'] !== 'true') && (!isset($CI->meta['social_user_moderator_required']) || $CI->meta['social_user_moderator_required'] !== 'true')) {
                        return;
        }
        if (!FrameworkExternal::isLoggedIn()) {
            return array('type' => 'login');
        }
        if (!FrameworkExternal::isSocialUser() || (isset($CI->meta['social_moderator_required']) && $CI->meta['social_moderator_required'] === "true" && !FrameworkExternal::isSocialModerator()) || (isset($CI->meta['social_user_moderator_required']) && $CI->meta['social_user_moderator_required'] === "true" && !FrameworkExternal::isSocialUserModerator())) {
            return array('type' => 'error', 'code' => FrameworkExternal::DOCUMENT_PERMISSION);;
        }
    }

    /**
     * Function to determine if a page request for an asset
     * @param object $CI Page controller instance
     * @return array|null Null if page access is allowed, otherwise an array of what action the page controller
     *   should take (redirect to login, redirect to url, redirect to error page)
     */
    private static function pageAllowedAsset($CI) {
        // when assets are actually available, this should take the $CI parameter like pageAllowedAnswer and pageAllowedIncident
        if(!($assetID = UrlExternal::getParameter('asset_id')))
            return;

        if(!FrameworkExternal::isValidID($assetID)) {
            return array('type' => 'error', 'code' => FrameworkExternal::DOCUMENT_PERMISSION);
        }

        if(!$CI->model('Asset')->get($assetID)->result) {
            //No asset was returned, so we redirect the user to the login page
            //if they are not logged in
            if(!FrameworkExternal::isLoggedIn()) {
                return array('type' => 'login');
            }

            return array('type' => 'error', 'code' => FrameworkExternal::DOCUMENT_PERMISSION);
        }
        ActionCapture::record('asset', 'view');
    }

    /**
     * Function to determine if a page request for an incident
     * @param object $CI Page controller instance
     * @return array|null Null if page access is allowed, otherwise an array of what action the page controller
     *   should take (redirect to login, redirect to url, redirect to error page)
     */
    private static function pageAllowedIncident($CI) {
        if(!($incidentID = UrlExternal::getParameter('i_id')))
            return;

        if(!FrameworkExternal::isValidID($incidentID)) {
            return array('type' => 'error', 'code' => FrameworkExternal::DOCUMENT_PERMISSION);
        }

        if(!$CI->model('Incident')->get($incidentID)->result) {
            //No incident was returned, so we redirect the user to the login page
            //if they are not logged in
            if(!FrameworkExternal::isLoggedIn()) {
                return array('type' => 'login');
            }

            return array('type' => 'error', 'code' => FrameworkExternal::DOCUMENT_PERMISSION);
        }
        ActionCapture::record('incident', 'view');
    }

    /**
     * Function to determine page request is for an enduser visible product/category.
     * @param object $CI Page controller instance
     * @return array|null Null if page access is allowed, otherwise an array of what action the page controller
     *   should take (redirect to enduser visible url, redirect to empty product detail page)
     */
    private static function pageAllowedProductCategory($CI) {
        $prodCatID = $redirectProdCatID = null;
        $prodCatArray = array('p' => 'Product', 'c' => 'Category');
        $redirectUrl = $_SERVER['REQUEST_URI'];
        $visibleIDs = array();
        foreach ($prodCatArray as $key => $value) {
            $visibleProdCatIDs = array();

            if($prodCatID = UrlExternal::getParameter($key)) {
                // for url.com/p/1,3,4;6,7;30
                $productIDs = explode(';', $prodCatID);

                foreach ($productIDs as $productID) {
                    if (strpos($productID, ',') !== false) {
                        $flatProdHeirChain = explode(',', $productID);

                        while($productID = array_pop($flatProdHeirChain)) {
                            if ($CI->model('Prodcat')->isEnduserVisible($productID)) {
                                $visibleProdCatIDs[$productID] = true;
                                break;
                            }
                        }
                    }
                    else {
                        $visibleProductHeir = $CI->model('Prodcat')->getFormattedChain($value, $productID, true)->result;
                        $redirectProdCatID = array_pop($visibleProductHeir);

                        if ($redirectProdCatID !== null) {
                            // parent level product/category is visible. Hence add it to visible products/category list
                            if ($redirectProdCatID != $productID) {
                                $visibleProdCatIDs[$redirectProdCatID] = true;
                            }
                            else {
                                $visibleProdCatIDs[$productID] = true;
                            }
                        }
                    }
                }

                // to prevent going to infinite loop
                // if requested productIDs matches with visibleProdCatIDs then return null
                $visibleProdCatIDs = array_keys($visibleProdCatIDs);
                $visibleIDs = array_merge($visibleIDs, $visibleProdCatIDs);
                
                if (array_diff($productIDs, $visibleProdCatIDs) && ($prodUrl = implode(';', $visibleProdCatIDs))) {
                    // replace product url
                    $redirectUrl = UrlExternal::deleteParameter($redirectUrl, $key);
                    $redirectUrl = UrlExternal::addParameter($redirectUrl, $key, $prodUrl);
                }
            }
        }

        $prodCatUrl = UrlExternal::getParameter('p') === null ? ConfigExternal::getConfig(CP_CATEGORIES_DETAIL_URL) : ConfigExternal::getConfig(CP_PRODUCTS_DETAIL_URL);
        if(empty($visibleIDs) && preg_match('#/app/' . $prodCatUrl . '(\/.*)*$#', $redirectUrl)){
            return array('type' => 'error', 'code' => 404);
        }

        if ($redirectUrl !== $_SERVER['REQUEST_URI']) {
            return array('type' => 'location', 'url' => $redirectUrl);
        }
        
        return null;
    }
}
