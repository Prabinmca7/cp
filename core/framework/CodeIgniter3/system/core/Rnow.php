<?php

use RightNow\Utils\Text,
    RightNow\Utils\Url,
    RightNow\Api,
    RightNow\Connect\v1_4 as Connect;

class Rnow
{
    //Misc Variables
    private $isSpider;
    private static $cgiRoot;
    private $protocol = '//';
    private static $updatedConfigs = array();

    function __construct($fullInitialization = true)
    {
        // init.phph include starts here

        self::$cgiRoot = get_cfg_var('rnt.cgi_root');
        putenv(sprintf("CGI_ROOT=%s", self::$cgiRoot));
        define('LANG_DIR', get_cfg_var('rnt.language'));
        putenv(sprintf("LANG_DIR=%s", LANG_DIR));

        // ---------------------------------------------------------------
        // This nasty bit pulls in the copy of mod_info.phph which has all of the defines
        // (i.e., the non-script-compiled copy) if the request is for a non-production CP
        // page.  Otherwise we get the normal one.
        if (IS_HOSTED && !IS_OPTIMIZED){
            require_once(DOCROOT . '/cp/src/mod_info.phph');
        }
        else {
            require_once(DOCROOT . '/cp/mod_info.phph');
        }

        // In production, the defines in mod_info.phph are hard coded into CP, and mod_info.phph
        // is not included. Thus MOD_ACCESS must be defined here so its value can change.
        if (USES_ADMIN_IP_ACCESS_RULES) {
            define("MOD_ACCESS", MOD_ADMIN);
        }
        else {
            define("MOD_ACCESS", MOD_PUBLIC);
        }

        //CP always sends a UTF-8 content type
        header("Content-Type: text/html; charset=UTF-8");
        dl('libcmnapi-'.CP_PHP_VERSION . sprintf(DLLVERSFX, MOD_CMN_BUILD_VER));

        //We need to include each file separately since in order to track things correctly, we want the initConnectAPI call to happen
        //from core CP code. The kf_init file will attempt to include Connect_init, but it uses require_once so there isn't much impact.
        //It also has an additional call to initConnectAPI, but that is also very fast.
        require_once(DOCROOT . '/include/ConnectPHP/Connect_init.phph');
        initConnectAPI();
        $context = Connect\ConnectAPI::getCurrentContext();
        $context->ApplicationContext = "Calling Connect from CP";
        $context->DateAsString = true;
        if(RightNow\Connect\v1_4\ConnectAPI::isErrorRetryable()) {
            //In case of resource contention, redirection happens only for HTTP GET requests starting with /app and domain root
            $cpConfigs = \RightNow\Utils\Framework::readCPConfigsFromFile();
            if($cpConfigs['CP.ServiceRetryable.Enable'] === "true" && !get_instance()->isAjaxRequest() && ($_SERVER['REQUEST_URI'] ==='/' || strcasecmp(substr($_SERVER['REQUEST_URI'], 0, 4), '/app') === 0)) {
                \RightNow\Utils\Framework::sendRetryHeadersAndRedirect($cpConfigs);
            }
            else {
                self::loadMessagebaseDefines();
                \RightNow\Utils\Framework::writeContentWithLengthAndExit(self::getMessage(UNABLE_COMPLETE_REQUEST_TRY_AGAIN_MSG));
            }
        }
        require_once(DOCROOT . '/include/ConnectPHP/Connect_kf_init.phph');

        // Connect turns off error reporting; turn it back on.
        if (IS_HOSTED && IS_DEVELOPMENT)
            error_reporting(E_ALL & ~E_NOTICE); // PHP's default: All errors except E_STRICT and E_NOTICE
        else if (!IS_HOSTED)
            error_reporting(~E_NOTICE); // All errors except E_NOTICE

        //Tell Connect which mode we're running in so that they can bill things accordingly
        $cpMode = Connect\CustomerPortalMode::Production;
        if(IS_DEVELOPMENT || IS_STAGING || IS_REFERENCE){
            $cpMode = Connect\CustomerPortalMode::Development;
        }
        else if(IS_ADMIN){
            $cpMode = Connect\CustomerPortalMode::Admin;
        }
        Connect\CustomerPortal::setCustomerPortalMode($cpMode);

        self::postCommonApiInit($fullInitialization);

        //IE doesn't allow 3rd party cookies (e.g. when CP is used within an iFrame) unless a P3P
        //header is sent. Because of that, we're conditionaly going to send the header for IE only.
        if (MOD_ACCESS == MOD_PUBLIC && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false){
            header('P3P: CP="CAO CURa ADMa DEVa OUR BUS IND UNI COM NAV"');
        }

        if ($fullInitialization) {
            \RightNow\Libraries\AbuseDetection::sendSpeculativeRequests();
        }
        dl('librnwapi-'.CP_PHP_VERSION . sprintf(DLLVERSFX, MOD_BUILD_VER));
        self::validateRemoteAddress();

        if (!Api::sql_open_db())
            exit;

        // init.phph include ends here

        $this->isSpider = $this->isSpiderInit();

        if (!IS_ADMIN && Url::isRequestHttps() && $this->getConfig(SEC_END_USER_HTTPS)) {
            header("Strict-Transport-Security: max-age=15724800"); // 6 months
        }

        $this->redirectToHttpsIfNeeded();
        $this->validateRedirectHost();
        $this->ensureOptimizedDirectoryExists();
    }

    /**
     * Throws an exception if the function calling ensureCallerIsInternal was not
     * called by standard RightNow code.
     *
     * @private
     */
    public static function ensureCallerIsInternal($stack = null) {
        if (!$stack) {
            $stack = debug_backtrace(false);
        }
        // Internal/Base uses the __call and __callStatic functions for method overloading,
        //  so we have to look back one more to find the calling file
        $stackIndex = (($function = $stack[1]['function']) && ($function === '__call' || $function === '__callStatic')) ? 2 : 1;
        $errorReportingStackIndex = $stackIndex;

        //In some cases, the file isn't reported. This should happen because the function is being invoked from a callback (usually
        //through the use of a preg_replace_callback). In that case, go back into the stack one level further and find the file
        //where the callback was invoked from since it has to be core code.
        if($stack[$stackIndex]['file'] === null){
            $stackIndex++;
        }
        $callingFile = $stack[$stackIndex]['file'];
        $className = $stack[$stackIndex]['class'];
        $functionName = $stack[$stackIndex]['function'];

        $coreFrameworkPrefix = IS_HOSTED ? ".cfg/scripts/cp/core/framework/" : "/rnw/scripts/cp/core/framework/";
        $callingFileIndex = strpos($callingFile, $coreFrameworkPrefix);
        if ($callingFileIndex === false && (
            stripos($callingFile, ".cfg/scripts/cp/core/framework/") !== false || // CruiseControl isn't IS_HOSTED but its file structure is the same as hosted sites.
            stripos($callingFile, "/rnw/scripts/cp/core/util/tarball/") !== false  ||  // Tarball deploy tasks and tests are in core/util (non-HOSTED).
            stripos($callingFile, ".cfg/scripts/cp/core/util/tarball/") !== false      // Tarball deploy tasks are in core/util (HOSTED, however IS_HOSTED is false during tarball creation).
            )) {
            return;
        }
        if ($callingFileIndex !== false) {
            $pathAfterCore = substr($callingFile, $callingFileIndex + strlen($coreFrameworkPrefix));
        }
        //Disallow calls from the following locations:
        //  - Code not under /core/framework
        //  - Code executed during an eval()
        if(!$pathAfterCore || Text::stringContains($pathAfterCore, "eval()'d code")){
            throw new Exception("{$stack[$errorReportingStackIndex]['class']}::{$stack[$errorReportingStackIndex]['function']} may only be called by standard RightNow code. PATH - " . var_export($stack[$errorReportingStackIndex], true));
        }
    }

    private static function ensureOptimizedDirectoryExists() {
        // I use hooks.php as the means to determine if the inteface has been
        // successfully deployed because we require it to be present in order
        // to deploy.
        if ((IS_OPTIMIZED) && !is_file(APPPATH . '/config/hooks.php')) {
            exit(self::getMessage(INTERFACE_SUCCESSFULLY_DEPLOYED_MSG));
        }
    }

    private static function validateRemoteAddress() {
        $forceModPublic = func_num_args() > 0 ? func_get_arg(0) : false;
        if ((MOD_ACCESS === MOD_ADMIN) ||
            (MOD_ACCESS === MOD_PUBLIC) || $forceModPublic) {
            $avi['ip_addr'] = $_SERVER['REMOTE_ADDR'];
            $avi['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : '';
            $avi['source'] = (MOD_ACCESS === MOD_ADMIN && !$forceModPublic) ?
                              intval(ACCESS_VALIDATE_SRC_PHP_ADMIN) :
                              intval(ACCESS_VALIDATE_SRC_PHP_PUBLIC);
            $rv = Api::access_validate($avi);
        }
        if (isset($rv) && $rv !== RET_ACCESS_VALIDATE_SUCCESS &&
            $rv !== RET_USER_AGENT_NOT_AUTHORIZED && $rv !== RET_CLIENT_ADDR_NOT_AUTH) {
            if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $avi['ip_addr'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
                if ($avi['source'] === ACCESS_VALIDATE_SRC_PHP_PUBLIC) {
                    $avi['source'] = ACCESS_VALIDATE_SRC_PHP_PUBLIC_FORWARD;
                }
            }
            $rv = Api::access_validate($avi);
        }
        if (isset($rv) && $rv !== RET_ACCESS_VALIDATE_SUCCESS) {
            if ($rv === RET_NO_CLIENT_ADDR_SPEC) {
                $errorMessage = self::getMessage(NO_CLIENT_ADDR_SPEC_MSG);
            }
            elseif ($rv === RET_CLIENT_ADDR_NOT_AUTH) {
                $errorMessage = self::getMessage(CLIENT_ADDR_NOT_AUTH_MSG);
            }
            else {
                $errorMessage = self::getMessage(USER_AGENT_NOT_AUTHORIZED_MSG);
            }
            $errorTemplate = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>%s</title>
    <style>body { font-family: sans-serif } div { font-size: larger }</style>
</head>
<body>
    <h1>%s</h1>
    <br><hr size=1><br>
    <div>
        %s
        <p>
            <b>%s: </b>%s (%s)
        </p>
    </div>
</body>
</html>
HTML;
            $errorMessage = sprintf(
                $errorTemplate,
                self::getMessage(RNT_FATAL_ERROR_LBL),
                self::getMessage(FATAL_ERROR_LBL),
                self::getMessage(ACCESS_DENIED_LBL),
                self::getMessage(REASON_LBL),
                $errorMessage,
                $_SERVER['REMOTE_ADDR'] ?: ''
            );
            header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");
            \RightNow\Utils\Framework::writeContentWithLengthAndExit($errorMessage, 'text/html');
        }
    }

    /**
     * Inspect URI for 'redirect' parameter and validate associated host against local domain, community domain, and CP_REDIRECT_HOSTS.
     * If a disallowed host found, redirect to 403 page in dev mode, else strip out bad host in production mode.
     * If CP_REDIRECT_HOSTS contains a '*', allow all hosts.
     * If CP_REDIRECT_HOSTS is empty, allow no hosts (other than local and community)
     *
     * $return [null]
     */
    private function validateRedirectHost() {
        //Adding ternary check to verify first character of $fragment is / and to strip
        //if there are two // we leave it alone
        if (!($uri = strtolower(urldecode(ORIGINAL_REQUEST_URI))) ||
            !($fragment = Text::getSubstringAfter($uri, '/redirect/')) ||
            !($fragment = $fragment[0] === '/' && $fragment[1] !== '/' ? substr($fragment, 1) : $fragment) || 
             (!Text::beginsWith($fragment, 'http') && !Text::beginsWith($fragment, '//')))
        {
            return;
        }

        if (!Url::isRedirectAllowedForHost($fragment)) {
            if (IS_PRODUCTION) {
                header("Location: " . $this->protocol . $_SERVER['HTTP_HOST'] . str_replace("/redirect/$fragment", '', $uri));
                exit;
            }
            else {
                header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
                \RightNow\Utils\Framework::writeContentWithLengthAndExit('Host not allowed');
            }
        }
    }

    private function redirectToHttpsIfNeeded() {
        $secHttpConfig = (IS_ADMIN || IS_DEPLOYABLE_ADMIN) ? SEC_ADMIN_HTTPS : SEC_END_USER_HTTPS;
        if (!((isset($_SERVER['HTTP_RNT_SSL']) && $_SERVER['HTTP_RNT_SSL'] === 'yes') || (!IS_HOSTED && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')) && $this->getConfig($secHttpConfig)) {
            $this->protocol = 'https://';
            header($_SERVER['SERVER_PROTOCOL'] . ' 301 Moved Permanently');
            header("Location: {$this->protocol}" . $_SERVER['HTTP_HOST'] . ORIGINAL_REQUEST_URI);
            exit;
        }
    }

    private static function postCommonApiInit($fullInitialization) {
        $currentInterfaceName = substr(self::getCfgDir(), 0, -4);
        Api::set_intf_name($currentInterfaceName);

        //Conditionally swap out the messagebases being used for requests from the CX console or CP Admin pages.
        if (($langData = get_instance()->_getRequestedAdminLangData()) && $langData[0] !== $currentInterfaceName) {
            Api::msgbase_switch($langData[0]);
        }

        if ($fullInitialization) {
            self::loadConfigDefines();
            self::loadMessagebaseDefines();
        }
        else {
            self::loadConfigDefines();
        }

        $controllerClassName = strtolower(get_instance()->router->fetch_class());
        if (self::cpDisabledAndShouldExit($controllerClassName)) {
            header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
            //Expand the error message by duplicating spaces so that we actually display this message
            //and the browser doesn't show its default 404 page
            exit(self::getMessage(CUSTOMER_PORTAL_ENABLED_INTERFACE_MSG) . str_repeat(" ", 512));
        }
        if(self::getConfig(CP_MAINTENANCE_MODE_ENABLED) && IS_PRODUCTION && !CUSTOM_CONTROLLER_REQUEST && in_array($controllerClassName, array('page', 'facebook', 'widgetservice'))) {
             //Only display the splash page for page requests
             if($controllerClassName !== 'widgetservice'){
                 echo file_get_contents(OPTIMIZED_FILES . "/production/optimized/errors/error500.html");
             }
             exit;
        }

        if (!IS_HOSTED) {
            require_once(DOCROOT . '/include/rnwintf.phph');
        }
        else if (!IS_OPTIMIZED) {
            require_once(DOCROOT . '/include/src/rnwintf.phph');
        }
    }

    /*
     * Indicates if Customer Portal is not enabled, and the request and general state of configs
     * warrants an exit with the 'Customer Portal is not enabled for this interface' message.
     *
     * @param string $className The controller's class name.
     * @param null|string $methodName The name of the method being run. If null, defaults to router->fetch_method()
     * @param null|boolean $isCustomController If null, defaults to CUSTOM_CONTROLLER_REQUEST
     * @return boolean True if an exit is warranted.
     */
    private static function cpDisabledAndShouldExit($className, $methodName = null, $isCustomController = null) {
        if (!self::getConfig(MOD_CP_ENABLED) &&
            // Allow when MOD_CP_DEVELOPEMENT_ENABLED and coming from a production/optimized type request
            !(self::getConfig(MOD_CP_DEVELOPMENT_ENABLED) && (IS_ADMIN || IS_DEVELOPMENT || IS_STAGING || IS_REFERENCE)) &&
            // class and/or method name exceptions, when not coming from a custom controller
            !(!($isCustomController === null ? CUSTOM_CONTROLLER_REQUEST : $isCustomController) && (
                // Allow inlineImage and answerPreview requests
                ($className === 'inlineimage' || $className === 'answerpreview' || $className === 'inlineimg') ||
                // Allow marketing requests if either of the MOD_*_ENABLED configs enabled.
                (($className === 'documents' || $className === 'friend') && (self::getConfig(MOD_FEEDBACK_ENABLED) || self::getConfig(MOD_MA_ENABLED))) ||
                // Allow service pack deploys
                ($className === 'deploy' && ($methodName ?: strtolower(get_instance()->router->fetch_method())) === 'servicepackdeploy')
            ))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Allows us to get the necessary information to load the config bases from the
     * test directory if current request is a test request.
     */
    private static function getConfigBaseInterfaceDirectory() {
        $cfgdir = self::getCfgDir();
        if (!self::isTestRequest())
            return $cfgdir;

        preg_match_all("/([^,:]+):([^,:]+)/", self::getTestOptions(), $results);
        $opts = array_combine($results[1], $results[2]);
        if (array_key_exists("suffix", $opts))
            return str_replace(".cfg", $opts["suffix"] . ".cfg", $cfgdir);

        return $cfgdir;
    }

    public static function getCfgDir() {
        static $cfgDir = null;
        if ($cfgDir === null) {
            // The "4" tells explode() to stop exploding after the first 3 strings have been seperated.
            // This is done because we only want the third string.
            $scriptNameSegments = explode('/', $_SERVER['SCRIPT_NAME'], 4);
            $cfgDir = $scriptNameSegments[2];
        }
        return $cfgDir;
    }

    private static function isTestRequest()
    {
        return isset($_ENV['rn_test_valid']) && ($_ENV['rn_test_valid'] === '1');
        //return $_COOKIE['rn_test_valid'] === '1';
    }

    private static function getTestOptions()
    {
        //return "suffix:_test2,db:jvswgit_test2,foo:bar";
        return $_ENV['rn_test_opts'];
        //return $_COOKIE['rn_test_opts'];
    }

    public static function getTestCookieData()
    {
        if(!self::isTestRequest())
            return "";

        return "location=" . str_replace("~", "%7E", $_COOKIE['location']) . ";rn_test_opts=" . $_COOKIE['rn_test_opts'] . ";";
        //return "rn_test_valid=" . $_COOKIE['rn_test_valid'] . ";rn_test_opts=" . $_COOKIE['rn_test_opts'] . ";";
    }

    /**
     * Gets a messageBase value given the slot name and the base's ID
     *
     * @return string The value of the messageBase slot
     * @param $slotID int The slot in the message base
     */
    static function getMessage($slotID)
    {
        static $canCallMessageGetApiMethod = null;
        if($canCallMessageGetApiMethod === null){
            $canCallMessageGetApiMethod = function_exists('msg_get');
        }
        if(!$canCallMessageGetApiMethod){
            return "msg #$slotID";
        }
        return Api::msg_get_compat($slotID);
    }

    /**
     * Specialized function to compare passed in value to admin password config setting since
     * we cannot access that config directly.
     * @param string $password Encrypted password to check
     * @return boolean
     */
    static function isValidAdminPassword($password) {
        if ($password === '')
            $password = Api::pw_encrypt($password, ENCRYPT_IP);
        return ($password === Api::pw_encrypt(Api::cfg_get_compat(SEC_CONFIG_PASSWD), ENCRYPT_IP));
    }

    /**
     * Gets a configbase value given the slot name and the
     * base ID's
     * @return mixed The value of the config in the correct form
     * @param $slotID int The config base slot ID
     */
    static function getConfig($slotID)
    {
        static $canCallConfigGetApiMethod = null;
        if($canCallConfigGetApiMethod === null){
            $canCallConfigGetApiMethod = function_exists('cfg_get_casted');
        }
        if(!$canCallConfigGetApiMethod){
            if($slotID === CP_DEPRECATION_BASELINE || $slotID === CP_CONTACT_LOGIN_REQUIRED)
                return 0;
            throw new Exception("Cannot retrieve config $slotID, $configBase during tarballDeploy. You probably need to add a case for it in Rnow.php.");
        }

        //Block all access to these configs for security reasons
        if(in_array($slotID, array(SEC_CONFIG_PASSWD, DB_PASSWD, PROD_DB_PASSWD, rep_db_passwd)))
            return null;

        // return default config values for url-related configs when in reference mode
        if(IS_REFERENCE && ($overrideValue = self::getReferenceModeConfigValue($slotID)) !== null)
            return $overrideValue;

            if (!IS_HOSTED && isset(self::$updatedConfigs[$slotID]) && ($unsavedValue = self::$updatedConfigs[$slotID]) !== null) {
            return $unsavedValue;
        }

        return Api::cfg_get_compat($slotID);
    }

    /**
     * Updates a configbase value
     *
     * @param string $slotName The config base slot name
     * @param string|bool|int $newValue The value to set the slot
     * @param bool $doNotSave Whether the config value is actually updated or just
     *        persisted for the rest of the process
     * @return string|bool|int The old value of the config in the correct form
     */
    static function updateConfig($slotName, $newValue, $doNotSave = false) {
        if(IS_HOSTED){
            throw new Exception("Configs cannot be updated from within CP.");
        }

        if(!is_string($slotName)) {
            throw new Exception("Expected a string for config slot ID, but got '" . var_export($slotName, true) . "' instead.");
        }
        if(!defined($slotName) || !$slotValue = constant($slotName)) {
            throw new Exception("Expected to find a define for $slotName, but there's no such config slot.");
        }

        if ($doNotSave) {
            self::$updatedConfigs[$slotValue] = $newValue;
            return self::getConfig($slotValue);
        }

        $interfaceName = Api::intf_name();
        $setConfigScriptPath = get_cfg_var('rnt.cgi_root') . "/$interfaceName.cfg/bin/set_config";

        if($newValue === false)
            $newValue = "0";
        else
            $newValue = "\"$newValue\"";

        $oldValue = self::getConfig($slotValue);
        exec("CX_ENVIRONMENT=DEV $setConfigScriptPath $interfaceName $slotName $newValue 2>&1", $output);
        if(count($output)){
            throw new Exception("Tried to execute: $setConfigScriptPath $interfaceName $slotName $newValue and got this error: " . implode('\n', $output));
        }
        return $oldValue;
    }

    /**
     * Gets the override value of a configbase value in reference mode
     * @return mixed The value of the config in the correct form or null
     * if the value is not overridden in reference mode
     * @param $slotID int The config base slot ID
     */
    private static function getReferenceModeConfigValue($slotID) {
        if (in_array($slotID, array(CP_404_URL, CP_ACCOUNT_ASSIST_URL,
                    CP_ANSWERS_DETAIL_URL, CP_ANS_NOTIF_UNSUB_URL,
                    CP_CHAT_URL, CP_HOME_URL, CP_INCIDENT_RESPONSE_URL,
                    CP_LOGIN_URL, CP_WEBSEARCH_DETAIL_URL))) {
            switch($slotID) {
                case CP_404_URL:
                    return 'error404';
                case CP_ACCOUNT_ASSIST_URL:
                    return 'utils/account_assistance';
                case CP_ANSWERS_DETAIL_URL:
                    return IS_OKCS_REFERENCE ? 'answers/answer_view' : 'answers/detail';
                case CP_ANS_NOTIF_UNSUB_URL:
                    return 'account/notif/unsubscribe';
                case CP_CHAT_URL:
                    return 'chat/chat_launch';
                case CP_HOME_URL:
                    return 'home';
                case CP_INCIDENT_RESPONSE_URL:
                    return 'account/questions/detail';
                case CP_LOGIN_URL:
                    return 'utils/login_form';
                case CP_WEBSEARCH_DETAIL_URL:
                    return 'answers/detail';
            }
        }
        return null;
    }

    /**
     * Returns if the user-agent is determined to be a known spider
     * @return boolean Whether the user agent is a spider or not
     */
    function isSpider()
    {
        return $this->isSpider;
    }

    private function isSpiderInit()
    {
        return Api::check_spider(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '', NULL, $_SERVER['REMOTE_ADDR']);
    }


    /**
     * Returns an array of escape characters for SQL queries
     * @return array List of escape characters
     */
    function getSqlEscapeCharacters()
    {
        return array('\'' => '\\\'',
                     '\\' => '\\\\',
                     );
    }

    /**
     * Returns an array of escape characters for file attachment uploads
     * @return array List of escape characters
     */
    function getFileNameEscapeCharacters()
    {
        return array('<' => '-',
                     '>' => '-',
                     '&lt;' => '-',
                     '&gt;' => '-',
                     '%' => '-',
                     );
    }

    private static function loadConfigDefines(){
        self::loadDefinesFile('config');
    }
    private static function loadMessagebaseDefines(){
        self::loadDefinesFile('msgbase');
    }

    private static function loadDefinesFile($type){
        if (IS_HOSTED && !IS_OPTIMIZED)
            require_once(DOCROOT . "/include/src/$type/$type.phph");
        else if (!IS_HOSTED)
            require_once(DOCROOT . "/include/$type/$type.phph");
    }

    /**
     * Returns a list of core PHP files that are always loaded within CP. These files
     * are loaded individually on a non-hosted site within development mode, and this
     * list of files is combined to create the optimized_includes.php file for hosted sites
     * and those in production mode.
    */
    public static function getCorePhpIncludes()
    {
        $cpcore = CPCORE;
        return array(
            "{$cpcore}Controllers/Base.php",
            "{$cpcore}Controllers/Admin/Base.php",
            "{$cpcore}Decorators/Base.php",
            "{$cpcore}Models/Base.php",
            "{$cpcore}Models/Clickstream.php",
            "{$cpcore}Models/Pageset.php",
            "{$cpcore}Models/PrimaryObjectBase.php",
            "{$cpcore}Models/CommunityObjectBase.php",
            "{$cpcore}Models/SearchSourceBase.php",
            "{$cpcore}Internal/Exception.php",
            "{$cpcore}Internal/Libraries/Search.php",
            "{$cpcore}Internal/Libraries/Widget/Base.php",
            "{$cpcore}Internal/Libraries/Widget/Locator.php",
            "{$cpcore}Internal/Libraries/Widget/ExtensionLoader.php",
            "{$cpcore}Internal/Libraries/Widget/Helpers/Loader.php",
            "{$cpcore}Internal/Libraries/Widget/ViewPartials/Handler.php",
            "{$cpcore}Internal/Libraries/Widget/ViewPartials/Partial.php",
            "{$cpcore}Internal/Libraries/Widget/ViewPartials/WidgetPartial.php",
            "{$cpcore}Internal/Libraries/Widget/ViewPartials/SharedPartial.php",
            "{$cpcore}Libraries/Widget/Helper.php",
            "{$cpcore}Libraries/Widget/Base.php",
            "{$cpcore}Libraries/Widget/Input.php",
            "{$cpcore}Libraries/Widget/Output.php",
            "{$cpcore}Internal/Libraries/ClientLoader.php",
            "{$cpcore}Libraries/ClientLoader.php",
            "{$cpcore}Libraries/Decorator.php",
            "{$cpcore}Libraries/SearchResult.php",
            "{$cpcore}Libraries/SearchResults.php",
            "{$cpcore}Libraries/SearchMappers/BaseMapper.php",
            "{$cpcore}Libraries/Search.php",
            "{$cpcore}Libraries/Session.php",
            "{$cpcore}Libraries/Hooks.php",
            "{$cpcore}Libraries/SEO.php",
            "{$cpcore}Libraries/AbuseDetection.php",
            "{$cpcore}Libraries/PageSetMapping.php",
            "{$cpcore}Libraries/Formatter.php",
            "{$cpcore}Libraries/ResponseObject.php",
            "{$cpcore}Libraries/Cache/ReadThroughCache.php",
            "{$cpcore}Libraries/Cache/PersistentReadThroughCache.php",
            "{$cpcore}Libraries/ConnectTabular.php",
            "{$cpcore}Internal/Utils/Url.php",
            "{$cpcore}Internal/Utils/SearchSourceConfiguration.php",
            "{$cpcore}Internal/Utils/FileSystem.php",
            "{$cpcore}Internal/Utils/Config.php",
            "{$cpcore}Internal/Utils/Connect.php",
            "{$cpcore}Internal/Utils/Framework.php",
            "{$cpcore}Internal/Utils/Tags.php",
            "{$cpcore}Internal/Utils/Text.php",
            "{$cpcore}Internal/Utils/Widgets.php",
            "{$cpcore}Internal/Utils/WidgetViews.php",
            "{$cpcore}Internal/Utils/Version.php",
            "{$cpcore}Utils/Permissions/Social.php",
            "{$cpcore}Utils/Tags.php",
            "{$cpcore}Utils/Text.php",
            "{$cpcore}Utils/VersionBump.php",
            "{$cpcore}Utils/Widgets.php",
            "{$cpcore}Utils/Framework.php",
            "{$cpcore}Utils/Url.php",
            "{$cpcore}Utils/Connect.php",
            "{$cpcore}Utils/Config.php",
            "{$cpcore}Utils/FileSystem.php",
            "{$cpcore}Utils/Chat.php",
            "{$cpcore}Utils/Validation.php",
            "{$cpcore}Utils/Date.php",
            "{$cpcore}Utils/OpenLoginUserInfo.php",
            "{$cpcore}Internal/Libraries/Widget/PathInfo.php",
            "{$cpcore}Internal/Libraries/Widget/Registry.php",
            "{$cpcore}Internal/Libraries/MetaParser.php",
            "{$cpcore}Internal/Libraries/SandboxedConfigs.php",
            "{$cpcore}Hooks/CleanseData.php",
            "{$cpcore}Hooks/Clickstream.php",
            "{$cpcore}Hooks/SqlMailCommit.php",
            "{$cpcore}Hooks/Acs.php",
        );
    }

    /**
     * Returns a list of core compatibility PHP files that are always loaded within CP. These files
     * are loaded individually on a non-hosted site within development mode, and this
     * list of files is combined to create the compatibility optimized_includes.php file for hosted sites
     * and those in production mode.
    */
    public static function getCoreCompatibilityPhpIncludes()
    {
        $coreFiles = CORE_FILES;
        $fileList = array(
            "{$coreFiles}compatibility/Internal/Api.php",
            "{$coreFiles}compatibility/Api.php",
            "{$coreFiles}compatibility/ActionCapture.php",
            "{$coreFiles}compatibility/Internal/Sql/Clickstream.php",
            "{$coreFiles}compatibility/Internal/Sql/Pageset.php",
        );
        if(IS_HOSTED || IS_TARBALL_DEPLOY){
            $fileList[] = "{$coreFiles}compatibility/Mappings/Classes.php";
            $fileList[] = "{$coreFiles}compatibility/Mappings/Functions.php";
        }
        return $fileList;
    }
}

