<?php
namespace
{
if(!IS_HOSTED) {
    putenv('SKIP_SQL_METERS_RECORDING=1');
}

define('CONNECT_NAMESPACE_PREFIX', 'RightNow\Connect\v1_4');
define('KF_NAMESPACE_PREFIX', 'RightNow\Connect\Knowledge\v1');
if (IS_HOSTED)
    error_reporting(E_ALL & ~E_NOTICE); // All errors except E_STRICT and E_NOTICE
else
    error_reporting(~E_NOTICE); // All errors except E_NOTICE
// Store an immutable copy of the request URI so we know what it really was when we started.
// It's a define because that's about the only way I can think of to make it truly unchangeable.
define('ORIGINAL_REQUEST_URI', $_SERVER['REQUEST_URI']);

/*
|---------------------------------------------------------------
| RightNow CP front controller (index.php)
|---------------------------------------------------------------
| This file serves as the front controller, initializing the
| base resources needed to run CodeIgniter.
 */
$function = '';
$customControllerRequest = $isAppRequest = $isAdmin = $isDeployableAdmin = false;
\RightNow\Environment\unsanitizedPostVariable();
$runXssClean = true;
list($mode) = \RightNow\Environment\retrieveModeAndModeTokenFromCookie();
$isDevelopment = ($mode === 'development') || ($mode === 'developmentInspector') ? true : false;
$isReference = ($mode === 'reference' || $mode === 'okcs_reference') ? true : false;
define('STAGING_PREFIX', 'staging_');
$isStaging = (!empty($mode)) ? ((0 === strncmp(STAGING_PREFIX, $mode, 8)) ? true : false) : false;
define('STAGING_LOCATION', ($isStaging) ? $mode : null);
$isProduction = ($isReference || $isDevelopment || $isStaging) ? false : true;

//We strip off all query string parameters since we don't support them and they have the potential to create erroneous 404s
if(($queryStringIndex = strpos($_SERVER['REQUEST_URI'], '?')) !== false){
    $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 0, $queryStringIndex);
}
$requestUri = explode('/', $_SERVER['REQUEST_URI']);
switch (strtolower($requestUri[1])) {
    case 'cgi-bin':
        // reset $requestUri since the query string parameters following '?' may be important
        $requestUri = explode('/', ORIGINAL_REQUEST_URI);
        if (array_slice($requestUri, 3, 2) === array('php', 'enduser'))
            \RightNow\Environment\permanentRedirect('/ci/redirect/enduser/' . implode('/', array_slice($requestUri, 4)));
        if (array_slice($requestUri, 3, 2) === array('php', 'wap'))
            \RightNow\Environment\permanentRedirect('/ci/redirect/wap/' . implode('/', array_slice($requestUri, 4)));
        if (array_slice($requestUri, 3, 2) === array('php', 'ma'))
            \RightNow\Environment\permanentRedirect('/ci/redirect/ma/' . implode('/', array_slice($requestUri, 4)));
        // restore $requestUri in case we fall through
        $requestUri = explode('/', $_SERVER['REQUEST_URI']);
        break;
    case '':
    case 'app':
        \RightNow\Environment\redirectForDreamweaverPreviewInBrowserRequest($requestUri);
        \RightNow\Environment\redirectWindowsAttemptToDiscoverWebDav($requestUri);
        $isAppRequest = true;
        $function = 'page/render';
        break;
    case 'dav':
        $isAdmin = true;
        $function = 'admin/webdav/index';
        $runXssClean = false;
        break;
    case 'ci':
        //Potentially redirect all old and old-old admin url requests
        \RightNow\Environment\handleAdminRedirects($requestUri);
        switch (strtolower($requestUri[2] ? $requestUri[2] : '')) {
            case 'page':
                \RightNow\Environment\redirectToProperPageUrl($requestUri);
                break;
            case 'unittest':
                // Actually unitTest
                if (!$requestUri[3]) {
                    \RightNow\Environment\permanentRedirect("/ci/unitTest/overview");
                }
            case 'admin':
                $isAdmin = true;
                break;
            case 'answerpreview':
                $isDeployableAdmin = true;
                $runXssClean = false;
                break;
            case 'ajaxcustom':
                $customControllerRequest = true;
                break;
            case 'redirect':
                $runXssClean = false;
                break;
        }
        $isAppRequest = !$isAdmin && !$isDeployableAdmin && $isProduction;
        break;
    case 'cc':
        $customControllerRequest = true;
        $isAppRequest = true;
        break;
}

if($runXssClean)
    \RightNow\Environment\xssSanitize();

\RightNow\Environment\sanitizeHeaders();

if ($isAdmin) {
    $isDevelopment = $isReference = $isProduction = $isStaging = false;
}

$documentRoot = get_cfg_var('doc_root');
$_SERVER['QUERY_STRING'] = $function . strstr(substr($_SERVER['REQUEST_URI'], 1), '/');

// The full file system path to the location where static content is served from.
// This is usually called the doc root at RightNow, but from some reason we've
// called it HTMLROOT here.
define('HTMLROOT', get_cfg_var('rnt.html_root'));

//Points to optimized folder. Staging and production folders live here
define('OPTIMIZED_FILES', "$documentRoot/cp/generated/");

//Points to customers file within development
define('CUSTOMER_FILES', "$documentRoot/cp/customer/development/");

//Points to customers config file within development
define('CUSTOMER_CONFIG_FILES', "$documentRoot/cp/customer/development/config/");

//Points to core directory which contains all core rightnow code, in addition to versioned framework code
define('CORE_FILES', "$documentRoot/cp/core/");

//Points to core widgets directory
define('CORE_WIDGET_FILES', CORE_FILES . "widgets/");

// The code in fixScriptEnvironmentVariables
// that sets the SCRIPT_NAME env var was located in at least three places in partial
// or whole form.  This isn't a great place for it, but at least it's common.
\RightNow\Environment\fixScriptEnvironmentVariables();

\RightNow\Environment\setProductionValues($applicationFolder, $optimizedAssetsPath, $deployTimestampFile);
if ($isStaging) {
    $applicationFolder = sprintf("%sstaging/%s/optimized/", OPTIMIZED_FILES, STAGING_LOCATION);
    $optimizedAssetsPath = sprintf('%s/euf/generated/staging/%s/optimized', HTMLROOT, STAGING_LOCATION);
    $deployTimestampFile = sprintf("%sstaging/%s/deployTimestamp", OPTIMIZED_FILES, STAGING_LOCATION);
    // Verify that the staging files exist or the file structure can be
    // exposed to malicious users.
    if (!is_dir($applicationFolder) ||
        !is_dir($optimizedAssetsPath) ||
        !is_readable($deployTimestampFile)) {
        $isProduction = true;
        $isStaging = false;
        \RightNow\Environment\setProductionValues($applicationFolder, $optimizedAssetsPath, $deployTimestampFile);
    }
}
else if(!$isProduction){
    $applicationFolder = CUSTOMER_FILES;
    \RightNow\Environment\fixHttpAuthenticationHeader();
}

define('OPTIMIZED_ASSETS_PATH', $optimizedAssetsPath);

// Path to file containing timestamp of last deploy. Used in paths to optimized assets.
define('DEPLOY_TIMESTAMP_FILE', $deployTimestampFile);

// The file extension.  Typically ".php"
define('EXT', '.'.pathinfo(__FILE__, PATHINFO_EXTENSION));

// The full file system path to THIS file
define('FCPATH', __FILE__);

// The name of THIS file (typically "index.php")
define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));

// The full file system path to the CodeIgniter "system" folder
define('BASEPATH', realpath(dirname(__FILE__)) . '/CodeIgniter3/system/');

// The full file system path to the .cfg/scripts folder
define('DOCROOT', $documentRoot);

// A boolean to indicate if the request is for an enduser page in development
// mode or for an admin page which needs to act like it's in development.
// __Please don't use this define.  Its meaning is too muddled.__
define('DEVELOPMENT_MODE', !$isProduction && !$isStaging);

// A boolean to indicate if the request is (essentially) not an admin request.
// Basically, did the request come from an enduser CP page.
// __Please don't use this define.  Its meaning is too muddled.__
define('APP_REQUEST', $isAppRequest);

// A boolean indicating if the request is going to a custom controller
define('CUSTOM_CONTROLLER_REQUEST', $customControllerRequest);

// The full file system path to the "application" folder for the request's mode
// (i.e., development or source).
define('APPPATH', "$applicationFolder");

// Like CPCORE except relative to DOCROOT.  This gets used to build URLs to the CP files.
define('SOURCEPATH', 'cp/core/framework/' . (IS_HOSTED ? CP_FRAMEWORK_VERSION . '.' . CP_FRAMEWORK_NANO_VERSION . '/' : ''));

// The full file system path to the controllers, models, etc. RightNow ships with CodeIgniter
// that are read only to users.  If you don't like the name of this, blame Ernie.
define('CPCORE', $documentRoot . '/' . SOURCEPATH);

// In hosting, the files in CPCORE are script compiled.  We also ship the
// original version of those files.
define('CPCORESRC', "$documentRoot/cp/src/core/framework/" . (IS_HOSTED ? CP_FRAMEWORK_VERSION . '.' . CP_FRAMEWORK_NANO_VERSION . '/' : ''));

// The path fragment to add to HTMLROOT to get the base directory of assets, such as images and CSS.
// If you need the actual path on disk, prefix this with HTMLROOT.
define('ASSETS_ROOT', $isReference && IS_HOSTED ? '/euf/assets/default/' : '/euf/assets/');

// Indicate if running from within a unit test
define('IS_UNITTEST', !IS_HOSTED && $requestUri[1] === 'ci' && $requestUri[2] === 'unitTest');

//
// ======================
// The various page modes
// ======================
//
// An optimized, deployable page
define('IS_PRODUCTION', $isProduction);

// A page in reference implementation mode
define('IS_REFERENCE', $isReference);

// A page in okcs reference implementation mode
define('IS_OKCS_REFERENCE', ($mode === 'okcs_reference') ? true : false);

// A page in staging mode
define('IS_STAGING', $isStaging);

// A page in either production or staging mode (saves a lot of 'if (IS_PRODUCTION || IS_STAGING)' statements).
define('IS_OPTIMIZED', $isProduction || $isStaging);

// A page in development mode
define('IS_DEVELOPMENT', $isDevelopment);

// An admin page, such as the tag gallary, logs, and webdav.
define('IS_ADMIN', $isAdmin);

// A CX console admin viewing page, such as the answer preview.
define('IS_DEPLOYABLE_ADMIN', $isDeployableAdmin);

// Not really necessary, but avoids having to always check both defined and constant all over the place
define('IS_TARBALL_DEPLOY', false);

// PHP 8.3 will be default PHP version for CP from 24C
define('CP_DEFAULT_PHP_VERSION', 80300);
define('CP_DEFAULT_PHP_VERSION_LABEL', "8.3");
define('CP_LEGACY_PHP_VERSION', 50600);

if (!$isProduction && !$isStaging) {
    ini_set('display_errors', 'on');
}
\RightNow\Environment\verifyOnlyOneModeDefineIsTrue(array('IS_PRODUCTION', 'IS_REFERENCE', 'IS_DEVELOPMENT', 'IS_ADMIN', 'IS_STAGING'));

define('USES_ADMIN_IP_ACCESS_RULES', ($isAdmin || $isDeployableAdmin || $isDevelopment || $isReference));
define('USES_ADMIN_HTTPS_SEC_RULES', ($isAdmin || $isDeployableAdmin));

function friendlyErrorType($type) {
    $levels = [];
    foreach (get_defined_constants() as $key => $value) {
        if (strpos($key, 'E_') !== 0) { 
            continue;
        }
        $levels[$value] = substr($key, 2); 
    }
    return (isset($levels[$type]) ? $levels[$type] : "Error #{$type}");
}

register_shutdown_function(function() {
    $error = error_get_last();
    
    if($error && ($error['type'] === E_ERROR || $error['type'] === E_USER_ERROR || $error['type'] === E_COMPILE_ERROR)) {
        // fatal
        while (ob_get_level())
            ob_end_clean();
        if(!IS_HOSTED || IS_DEVELOPMENT || IS_STAGING) {
            $errorType = ("E_" . friendlyErrorType($error['type']));
            exit("<br />\n<b>Fatal error</b>:  {$error['message']} of type {$errorType} in <b>{$error['file']}</b> on line <b>{$error['line']}</b><br />\n");
        }
        else {
            $errorString = var_export($error, true);
            if (function_exists('phpoutlog')) {
                \RightNow\Api::phpoutlog("Fatal error: $errorString");
            }
            if (\RightNow\ActionCapture::isInitialized()) {
                \RightNow\ActionCapture::record('init', 'error', substr($errorString, 0, \RightNow\ActionCapture::OBJECT_MAX_LENGTH));
            }
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
            echo file_get_contents(OPTIMIZED_FILES . "/production/optimized/errors/error500.html");
            exit();
        }
    }
});

// ================================================================================
// This is where the magic happens.  We load CI and it serves the request.
// ================================================================================
require_once BASEPATH . 'CoreCodeIgniter' . EXT;
// ================================================================================
// This is the end.  When we reach here, the page is done.
// ================================================================================
}
namespace RightNow\Environment
{
// Cut Dreamweaver a little slack; redirect its attempts to preview pages to the right place.
function redirectForDreamweaverPreviewInBrowserRequest($requestUri) {
    if (count($requestUri) > 6 &&
        strcasecmp($requestUri[2], 'euf') === 0 &&
        strcasecmp($requestUri[3], 'development') === 0 &&
        strcasecmp($requestUri[4], 'views') === 0 &&
        strcasecmp($requestUri[5], 'pages') === 0 &&
        strcasecmp(substr($requestUri[count($requestUri) - 1], -4), '.php') === 0) {
            permanentRedirect('/ci/admin/overview/developmentRedirect/' . substr(implode('/', array_slice($requestUri, 6)), 0, -4));
    }
}

function setProductionValues(&$applicationFolder, &$optimizedAssetsPath, &$deployTimestampFile) {
    $applicationFolder = OPTIMIZED_FILES . 'production/optimized/';
    $optimizedAssetsPath = HTMLROOT . '/euf/generated/optimized';
    $deployTimestampFile = OPTIMIZED_FILES . 'production/deployTimestamp';
}

function retrieveModeAndModeTokenFromCookie() {
    if(isset($_COOKIE['location']))
        return explode("~", $_COOKIE['location']);
    return "";
}

function verifyOnlyOneModeDefineIsTrue($modeDefines) {
    $numModeDefinesEnabled = 0;
    foreach ($modeDefines as $modeDefine) {
        if (!defined($modeDefine)) {
            throw new \Exception("There is no $modeDefine define.");
        }
        if (!is_bool($modeDefineValue = constant($modeDefine))) {
            throw new \Exception("Mode defines must be true or false.  The value of $modeDefine is not.");
        }
        if ($modeDefineValue) {
            ++$numModeDefinesEnabled;
        }
    }
    if ($numModeDefinesEnabled != 1) {
        throw new \Exception('Exactly one of the mode constants must be true.');
    }
}

function fixHttpAuthenticationHeader() {
    // In development we use mod_rewrite to mangle the URLs into short form for CP.
    // For some security-related reason PHP CGI + mod_rewrite results in the HTTP_AUTHORIZATION header being ripped out (http://blogs.23.nu/c0re/stories/1048/).
    // I made mod_rewrite write the header back to REDIRECT_HTTP_AUTHORIZATION,
    // so that I could capture it here and fix the problem.
    //
    // We only use HTTP authentication for administrative pages, so I perform this switcheroo here to avoid
    // adding any processing for production pages.
    if (!IS_HOSTED && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
}

function fixScriptEnvironmentVariables() {
    // The following code that the SCRIPT_NAME env var was located in at least three places in partial or whole form.
    // This isn't the best place for it, but it is a common place to make sure that those values are set consistantly.
    // I overwrite the $_SERVER['SCRIPT_NAME'] variable below because in hosting it's set to the original REQUEST_URI because hosting
    // uses ScriptAliasMatch to expand short CP URLs into the /cgi-bin/*.cfg/.../*.php equivalent.  I set the old-school $SCRIPT_NAME
    // just for good measure.
    if (isset($_SERVER['SCRIPT_FILENAME']) && $_SERVER['SCRIPT_FILENAME']) {
        //called from the web server, i.e., not the command line
        $_SERVER['SCRIPT_NAME'] = substr($_SERVER['SCRIPT_FILENAME'], strpos($_SERVER['SCRIPT_FILENAME'], '/cgi-bin/'));
    }
    putenv("SCRIPT_NAME={$_SERVER['SCRIPT_NAME']}");
}

function redirectToProperPageUrl($requestUri) {
    assert(strcasecmp($requestUri[1], 'ci') === 0);
    assert(strcasecmp($requestUri[2], 'page') === 0);
    $segmentsToSkip = 3;
    if (strcasecmp($requestUri[3], 'render') === 0) {
        $segmentsToSkip = 4;
    }
    permanentRedirect('/app/' . implode('/', array_slice($requestUri, $segmentsToSkip)));
}

/**
 * In CP3 we moved all our admin controllers to a sub directory so we need to handle any redirects to keep the
 * old URLs working. This function redirects the list of old admin controllers to their new sub-folder location. In
 * addition, this handles some of the older redirects from when the deployment changes were made.
 * @param string &$requestUri Current request URI
 */
function handleAdminRedirects(&$requestUri){
    $redirectedAdminEndpoints = array('configurations', 'deploy', 'designer', 'internalTools', 'logs', 'tags', 'widgetConverter');
    if(in_array($requestUri[2], $redirectedAdminEndpoints)){
        temporaryRedirect("/ci/admin/" . implode('/', array_slice($requestUri, 2)));
    }

    if($requestUri[2] === 'admin'){
        if(!isset($requestUri[3]) || (isset($requestUri[3]) && $requestUri[3] == "")) {
            temporaryRedirect("/ci/admin/overview");
        }
        //Force WebDAV requests to go through the /dav URL.
        if($requestUri[3] === 'webdav'){
            permanentRedirect('/dav/');
        }
        if(($requestUri[3] === 'deploy' && isset($requestUri[4]) && strtolower($requestUri[4] ? $requestUri[4] : '') === 'productiondeploy') ||
            ($requestUri[3] === 'deploy' && (!isset($requestUri[4]) || !$requestUri[4]) )){
            permanentRedirect('/ci/admin/deploy/stageAndPromote');
        }
        //The console sends a session ID without specifying the method, take them to the right location
        if($requestUri[3] === 'session_id'){
            permanentRedirect("/ci/admin/overview/index/" . implode('/', array_slice($requestUri, 3)));
        }
        //Note: $mapping keys below should be lower-case to allow for case-insensitive url's
        $mapping = array(
            'cmdline_deploy' => 'upgradeDeploy',
            'prepare_deploy' => 'prepareDeploy',
            'commit_deploy' => 'commitDeploy',
            'servicepackdeploy' => 'servicePackDeploy',
            'unittestdeploy' => 'unitTestDeploy',
            'modifyproductionpagesetmappingfilestomatchdb' => 'modifyProductionPageSetMappingFilesToMatchDB'
        );
        if(!isset($mapping[strtolower($requestUri[3])]) || !$mappedTo = $mapping[strtolower($requestUri[3])]) {
            // Nothing to do; it's not a /ci/admin/*deploy* request
            return;
        }
        $requestUri[2] = 'admin';
        $requestUri[3] = 'deploy';
        if (isset($mappedTo)) {
            $requestUri[4] = $mappedTo;
        }
        $_SERVER['REQUEST_URI'] = implode('/', $requestUri);
    }
}

/**
 * Various versions of Windows send an OPTIONS or PROFIND request for /, which should get redirected to /app by HMS,
 * when asked to view /dav/.
 * http://support.microsoft.com/?kbid=831805
 * http://www.greenbytes.de/tech/webdav/webdav-redirector-list.html#issue-server-discovery
 * 081203-000016
 * 090323-000062
 * @param string $requestUri Current request URI
 */
function redirectWindowsAttemptToDiscoverWebDav($requestUri) {
    if (($_SERVER['REQUEST_METHOD'] === 'OPTIONS' || $_SERVER['REQUEST_METHOD'] === 'PROPFIND') &&
        (($requestUri[0] === '') ||
        (strcasecmp($requestUri[1], 'app') === 0 && $requestUri[2] === ''))) {
        permanentRedirect('/dav/');
    }
}

/**
 * If updating, please see equivalent method in \RightNow\Utils\Framework::setLocationHeader.
 * @param string $headerValue The value for which to set the Location header.
 * @see \RightNow\Utils\Framework::setLocationHeader
 */
function setLocationHeader($headerValue) {
    $location = str_replace(array("\r\n", "\n", "\r", '%0D%0A', '%0D', '%0A', '%5Cr%5Cn', '%5Cr', '%5Cn'), '', $headerValue);
    header("Location: {$location}");
}

function temporaryRedirect($url) {
    setLocationHeader($url);
    header($_SERVER['SERVER_PROTOCOL'] . ' 302 Found');
    exit(' '); // Sending a space seems to help the browser update the address bar
}

function permanentRedirect($url) {
    setLocationHeader($url);
    header($_SERVER['SERVER_PROTOCOL'] . ' 301 Moved Permanently');
    exit(' '); // Sending a space seems to help the browser update the address bar
}

/**
 * Escape parameters for potential XSS attacks
 */
function xssSanitize(){
    $serverGlobalsToEscape = array('QUERY_STRING', 'REQUEST_URI', 'HTTP_REFERER', 'REDIRECT_URL');
    foreach($serverGlobalsToEscape as $value){
        if(isset($_SERVER[$value])){
            $_SERVER[$value] = xssSanitizeReplacer($_SERVER[$value]);
        }
    }
    $_GET = escapeArrayOfData($_GET, true);
    $_POST = escapeArrayOfData($_POST, false);
    //Update values in REQUEST with cleaned data
    $_REQUEST = $_GET + $_POST + $_COOKIE;
}

/**
 * Recursively escapes $data to remove any possible XSS exploits in both keys and values
 * @param array $data The array of data to escape
 * @param bool $shouldEscapeQuotes Whether quotes in the data should be escaped
 * @return array Cleaned data
 */
function escapeArrayOfData(array $data, $shouldEscapeQuotes){
    $safeData = array();
    foreach($data as $key => $value){
        $safeData[xssSanitizeReplacer($key)] = is_array($value) ? escapeArrayOfData($value, $shouldEscapeQuotes) : xssSanitizeReplacer($value, $shouldEscapeQuotes);;
    }
    return $safeData;
}

function xssSanitizeReplacer($value, $cleanQuotes = true){
    if(is_int($value) || is_float($value)){
        return $value;
    }
    if(!is_string($value))
        return '';
    $value = strtr($value, array("\0" => '',
                                 "\t" => '    ',
                                 '%09' => '    '
                                ));
    $value = preg_replace('@javascript(:|&#58;?|&#x3A;?|%3A|%253A|%25253A)@i', 'javascript ', $value);
    $value = str_ireplace(array('%3C', '%253C', '<'), '&lt;', $value);
    $value = str_ireplace(array('%3E', '%253E', '>'), '&gt;', $value);
    if($cleanQuotes)
    {
        $value = str_replace(array('%22', '%2522', '"'), '&quot;', $value);
        return str_replace(array('%27', '%2527', "'"), '&#039;', $value);
    }
    return $value;
}

function sanitizeHeaders(){
    foreach($_SERVER as $key => $value){
        // Parts of our system (IAPI) don't appreciate 'bad' characters (outside of the [ -~] range)
        // being used as input (e.g. when we log the user agent string of a spider).
        // Go ahead and escape the 'bad' characters, which are also not HTTP-protocol-compliant, in
        // all HTTP input fields so that they are preserved for future inspection, if necessary.
        if (substr($key, 0, 5) === 'HTTP_' && isset($_SERVER[$key])){
            // apparently Windows 8.1 would put nbsp chars ("\xc2\xa0") in the user agent string,
            // so just accept those as valid and convert them to spaces
            if ($key === 'HTTP_USER_AGENT')
                $_SERVER[$key] = str_replace("\xc2\xa0", ' ', $_SERVER[$key]);

            $_SERVER[$key] = preg_replace_callback(
                '/[^ -~]/',
                function($match) {
                    // preserve the bad characters as URL-escaped entities
                    return '%' . bin2hex($match[0]);
                },
                $_SERVER[$key]);
        }
    }
}
/**
 * Storing orginial $_POST data which is unsanitized/unfiltered
 * @param String $key Item to be fetched from $_POST
 * @return array|string|null unsanitized $_POST array or value of the requested key or null if data doesn't exist
 */
function unsanitizedPostVariable($key = null){
   static $postData = null;
   
   if($postData === null)
       $postData = $_POST;

   return $key ? $postData[$key] : $postData;
}
}
