<?php

namespace RightNow\Utils;

use \RightNow\Utils\Framework;

/**
 * Methods for retrieving data from the cururent URL, adding items to an existing URL, or generating new URLs.
 */
final class Url extends \RightNow\Internal\Utils\Url
{
    /**
     * This function will check the current URL and add a parameter
     * key if it does not exist. If it does exist, it will replace the
     * existing parameters value
     *
     * @param string $url The current URL to modify
     * @param string $key The parameter key to add
     * @param mixed $value The parameter value to use
     * @return string The url with the new parameter added
     */
    public static function addParameter($url, $key, $value)
    {
        $url = preg_replace("@((:?^|/)$key/)[^/]*@", "\${1}$value", $url, 1, $matched);
        if (!$matched) {
            return "$url/$key/$value";
        }
        return $url;
    }

    /**
     * This function will check the current URL and delete a parameter
     * key if it exists. If it doesn't exist, nothing will happen
     *
     * @param string $url The current URL to modify
     * @param string $key The parameter key to remove
     * @return string The url with the new parameter added
     */
    public static function deleteParameter($url, $key)
    {
        if(isset($url) && stripos($url, "/$key/") !== false)
            return preg_replace('@/'.preg_quote($key).'/[^#/]+@', '', $url);
        return $url;
    }

    /**
     * Returns the current URL including the Protocol, Server and Request URI
     *
     * @param bool $includeUri Whether or not to include request URI as well as hostname
     * @return string The current URL
     */
    public static function getOriginalUrl($includeUri = true) {
        $protocol = self::isRequestHttps() ? 'https' : 'http';
        return "$protocol://{$_SERVER['SERVER_NAME']}" . ($includeUri ? ORIGINAL_REQUEST_URI : '');
    }

    /**
     * Returns the index where parameters begin for the current URL
     * @return int The segment number
     */
    public static function getParameterIndex()
    {
        return get_instance()->config->item('parm_segment');
    }

    /**
     * Searches url parameters for key and returns its value
     * if it exists, null otherwise.
     *
     * This function will work only if the URL parameters are in /key/value format.
     *
     * @param string $key The url key to search for.
     * @return string|null The value associated to the key or null if not found
     */
    public static function getParameter($key)
    {
        $CI = func_num_args() > 1 ? func_get_arg(1) : get_instance(); // Allow unit
        $uriSegments = $CI->uri->uri_to_assoc($CI->config->item('parm_segment'));
        if(array_key_exists($key, $uriSegments) && !is_null($uriSegments[$key]))
            return urldecode($uriSegments[$key]);
        return null;
    }

    /**
     * Gets a string containing all of the URL parameters concatenated together.
     *
     * @return string A string containing all of the URL parameters concatenated together.
     */
    public static function getParameterString()
    {
        $CI = func_num_args() > 0 ? func_get_arg(0) : get_instance();
        $uriSegments = $CI->uri->uri_to_assoc($CI->config->item('parm_segment'));
        $toReturn = '';
        foreach ($uriSegments as $key => $value)
        {
            $toReturn .= "/$key/$value";
        }
        return $toReturn;
    }

    /**
     * Same function as getParameter except that it returns a string of the
     * key and value separated by a / to be used in a URL.
     *
     * @param string $key The url key to search for.
     * @param bool $checkPostData Whether the key should be checked in the POST data, too.
     * @return mixed The string of key and value separate by a /, null otherwise
     */
    public static function getParameterWithKey($key, $checkPostData = true)
    {
        $CI = func_num_args() > 2 ? func_get_arg(2) : get_instance(); // Allow unit
        $uriSegments = $CI->uri->uri_to_assoc($CI->config->item('parm_segment'));
        if(array_key_exists($key, $uriSegments))
            return $key . '/' . $uriSegments[$key];
        if($checkPostData && ($postData = $CI->input->post($key)) !== false)
            return $key . '/' . urlencode($postData);
        return null;
    }

    /**
     * Takes a comma separated list of URL parameter keys and builds up
     * a string of their values based on the current URL.
     *
     * @param string $parameterList Comma separated list of parameter keys
     * @param array $excludedParameters List of parameter keys that should be ignored
     * @param bool $checkPostData Whether the key should be checked in the POST data, too.
     * @return string The parameters in key1/value1/key2/value2 format
     */
    public static function getParametersFromList($parameterList, array $excludedParameters=array(), $checkPostData = true)
    {
        $CI = func_num_args() > 3 ? func_get_arg(3) : get_instance(); // Allow unit
        $parameterString = '';
        $parameterList = explode(',', str_replace(' ', '', "$parameterList"));
        for($i = 0; $i < count($parameterList); $i++)
        {
            if(!in_array($parameterList[$i], $excludedParameters))
            {
                $parameterValue = self::getParameterWithKey($parameterList[$i], $checkPostData, $CI);
                if($parameterValue !== null && $parameterValue !== $parameterList[$i] . "/")
                    $parameterString .= '/' . $parameterValue;
            }
        }
        return $parameterString;
    }

    /**
     * Returns the URL sessionParm if it is required to be in the URL
     *
     * @return string The url parameter that goes into the URL
     */
    public static function sessionParameter()
    {
        $CI = get_instance();
        if((!isset($CI->session) || !$CI->session) || ($CI->session->canSetSessionCookies() && $CI->session->getSessionData('cookiesEnabled')) || $CI->rnow->isSpider())
        {
            return '';
        }
        return $CI->session->getSessionData('sessionString');
    }

    /**
     * Returns the SSO token to log the user into the community. This
     * function will only return the token if RightNow Social is enabled
     * and the user is logged in.
     *
     * @param string $openingCharacter The character to prepend before the URL parameter
     * @param boolean $includeKey Denotes if token key should be included. If false, the $openingCharacter will be ignored
     * @param string $redirectUrl The url to redirect to
     * @param boolean $checkCommunityEnabled Flag to indicate whether COMMUNITY_ENABLED config should be checked or not
     * @return string The SSO token or an empty string if it is not necessary to generate
     */
    public static function communitySsoToken($openingCharacter = '?', $includeKey = true, $redirectUrl = '', $checkCommunityEnabled = true)
    {
        $CI = get_instance();
        if (!$checkCommunityEnabled || (Config::getConfig(COMMUNITY_ENABLED) && Config::getConfig(COMMUNITY_BASE_URL) && $CI->session && Framework::isLoggedIn())) {
            $communityVersion = Config::getConfig(COMMUNITY_VERSION);
            if ($communityVersion) {
                require_once CPCORE . 'Internal/Libraries/Version.php';
                $version = new \RightNow\Internal\Libraries\Version($communityVersion);
            }
            $ssoToken = $CI->model('Social')->generateSsoToken(false, $redirectUrl, $version && ($version->equals('15.11') || $version->greaterThan('15.11')) ? 3 : 2)->result;
            if ($includeKey)
                return "{$openingCharacter}opentoken=$ssoToken";
            return $ssoToken;
        }
        return '';
    }

    /**
     * Return the source path for YUI source files
     *
     * @param string $module The YUI Module path to load
     * @return string The full path to the YUI source files
     */
    public static function getYUICodePath($module = '')
    {
        return YUI_SOURCE_DIR . $module;
    }

    /**
     * Returns the browser path to the core, versioned asset directory and optionally appends the relative path sent in.
     *
     * @param string $path An optional relative path to append onto the base path
     * @return string Path to core assets with optional $path appended
     */
    public static function getCoreAssetPath($path=null){
        if(IS_HOSTED) {
            return '/euf/core/' . CP_FRAMEWORK_VERSION . '/' . ($path ?: '');
        }
        return '/euf/core/' . ($path ?: '');
    }

    /**
     * Gets all the different filters used for searching from the url and attributes
     * and writes them to the filters array
     *
     * @param array $attributes Array of attributes
     * @param array|null &$filters Array of filters which is modified during the call
     * @return void
     */
    public static function setFiltersFromAttributesAndUrl(array $attributes, &$filters) {
        $CI = func_num_args() > 2 ? func_get_arg(2) : get_instance(); /* Enable unit tests */
        $reportID = isset($attributes['report_id']) ? $attributes['report_id'] : null;

        $cacheKey = 'filtersFromUrl' . $reportID;
        $cachedFilters = Framework::checkCache($cacheKey);
        // ignore caching if $filters is set or there are specific keys set in $attributes
        if($cachedFilters !== null && $filters === null && (!isset($attributes['per_page']) || !$attributes['per_page'])) {
            $filters = $cachedFilters;
            return;
        }

        //The r_id parameter optionally specifies for which report to apply the URL parameters
        $reportParam = self::getParameter('r_id');
        $addURLFilter = ($reportID == $reportParam || $reportParam == null);

        // Get runtime values (either from url parameters or from fixed values specified in the 'static_filter' attribute).
        $staticFilters = null;
        if(isset($attributes['static_filter']))
        {
            $staticFilters = parent::extractFilters($attributes['static_filter']);
        }
        $runtimeValues = array();
        foreach (array('st', 'kw', 'org', 'sort', 'p', 'c', 'page') as $key) {
            $value = $addURLFilter ? self::getParameter($key) : null;
            if (isset($staticFilters) && isset($staticFilters[$key]) && !is_null($staticFilters[$key])) {
                // Fixed value overrides url parameter value, even if fixed value is empty-string.
                $value = $staticFilters[$key];
            }
            $runtimeValues[$key] = $value;
        }
        $urlFilters = array();

        if ($searchTypeFilter = parent::getSearchTypeFilter($reportID, $runtimeValues['st'], $CI)) {
            $urlFilters['searchType'] = $searchTypeFilter;
        }
        if ($keywordFilter = parent::getKeywordFilter($reportID, $runtimeValues['kw'], $CI)) {
            $urlFilters['keyword'] = $keywordFilter;
        }
        if ($org = $runtimeValues['org']) {
            $urlFilters['org'] = parent::getOrganizationFilter($reportID, $org, $CI);
        }
        if ($sort = $runtimeValues['sort']) {
            $urlFilters['sort_args'] = parent::getSortFilter($sort);
        }

        //Add in the product and category filters
        parent::getProductOrCategoryFilter('p', $runtimeValues['p'], $urlFilters, $reportID, $CI);
        parent::getProductOrCategoryFilter('c', $runtimeValues['c'], $urlFilters, $reportID, $CI);

        if((isset($attributes['filter_type']) && $attributes['filter_type'] === 'pc') || self::getParameter('pc')) {
            parent::getProductCatalogFilter($addURLFilter, $urlFilters, $reportID, $CI);
        }

        //Add in the reporting flags
        if ($addURLFilter && ($page = $runtimeValues['page']) && intval($page) > 0) {
            $urlFilters['page'] = $page;
        }

        // The work-around to check for the POST'ed keyword parameter is for our
        // basic (non-JavaScript) page set.
        // If the keyword has been POST'ed assume that the user actively made a search
        // and add the search filter.
        // Note that we are unable to determine that the search filter should be
        // added when the user is selecting a product or category. Only an interaction
        // with the BasicKeywordSearch widget will cause this filter to be set in the basic page set.
        if((($search = self::getParameter('search')) || $CI->input->post("kw") !== false) && $addURLFilter) {
            $urlFilters['search'] = $search ?: '1';
        }
        if (isset($attributes['per_page']) && $attributes['per_page'] > 0) {
            $urlFilters['per_page'] = $attributes['per_page'];
        }
        if((isset($attributes['filter_type'] ) && $attributes['filter_type'] === 'pc') || self::getParameter('pc')) {
            parent::getProductCatalogFilter($addURLFilter, $urlFilters, $reportID, $CI);
        }

        parent::applyWebIndexFilters($reportID, $urlFilters, $runtimeValues);

        //Finally, add in any custom menu filters
        $customFilters = array_merge($addURLFilter ? $CI->uri->uri_to_assoc($CI->config->item('parm_segment')) : array(), $staticFilters != null ? $staticFilters : array());
        $urlFilters = array_merge($urlFilters, parent::getCustomFilters($reportID, $customFilters, $CI));

        Framework::setCache($cacheKey, $urlFilters, true);

        if(is_array($filters) && is_array($urlFilters))
            $filters = array_merge($filters, $urlFilters);
        else
            $filters = $urlFilters;
    }

    /**
     * Returns the external login URL with the %error_code%,
     * %next_page%, and %session% variables replaced. If there is no %next_page%
     * variable in the config setting, it will be tacked onto the end of the
     * config value with query string parameters.
     *
     * @param int $errorCode The error code to use in the URL
     * @param string $nextPage The next page parameter to use in the URL
     * @return string The external login with variables replaced
     */
    public static function replaceExternalLoginVariables($errorCode, $nextPage)
    {
        if($errorCode){
            $redirectUrl = Config::getConfig(PTA_ERROR_URL) ?: Config::getConfig(PTA_EXTERNAL_LOGIN_URL);
        }
        else{
            $redirectUrl = Config::getConfig(PTA_EXTERNAL_LOGIN_URL);
        }

        if(!$redirectUrl)
            return null;

        $redirectUrl = str_ireplace('%session%', urlencode(Text::getSubstringAfter(self::sessionParameter(), "session/")), str_ireplace('%error_code%', $errorCode, $redirectUrl));

        $nextPage = urlencode($nextPage);
        if(Text::stringContainsCaseInsensitive($redirectUrl, '%next_page%')){
            $redirectUrl = str_ireplace('%next_page%', $nextPage, $redirectUrl);
        }
        else if($nextPage){
            $redirectUrl .= ((strrchr($redirectUrl, '?')) ? '&' : '?') . "p_next_page=$nextPage";
        }
        return $redirectUrl;
    }

    /**
     * Generates the version name in the lowercase cloud year release format (e.g. cloud18a) expected by resource and documentation links.
     *
     * @param string $version The version to display (e.g. 12.2). If not specified, return current version.
     * @return string Product version
     */
    public static function getProductVersionForLinks($version = null)
    {
        $version = ($version) ? $version : MOD_BUILD_VER;
        if (strpos($version, '.') !== false) {
            $versionNumbers = explode('.', $version);
            switch ($versionNumbers[1]) {
                case '2':
                    $release = "cloud" . $versionNumbers[0] . "a";
                    break;
                case '5':
                    $release = "cloud" . $versionNumbers[0] . "b";
                    break;
                case '8':
                    $release = "cloud" . $versionNumbers[0] . "c";
                    break;
                case '11':
                    $release = "cloud" . $versionNumbers[0] . "d";
                    break;
                default:
                    $release = "";
                    break;
            }
        }
        return ($release) ? $release : "";
    }

    /**
     * Returns the url for the interface with /app appended
     *  e.g. http://{site}.com/app
     *
     * @param mixed $matchProtocol Indicates if the URL should match the current page, the request, or one of the protocol configs.
     * Defaults to the same value as the current page.
     * Valid values are true, false, 'sameAsRequest' and 'sameAsCurrentPage'.
     * 'sameAsRequest' should be used if including resources (e.g. CSS, images) onto the current page. This looks at the actual connection.
     * 'sameAsCurrentPage' should be used if creating a link to another page in the same security context.  This looks at the current page's protocol.
     * true will use the admin protocol config
     * false will use the enduser protocol config
     * @param string $path Appended to the base URL
     * @return string The URL for the site
     */
    public static function getShortEufAppUrl($matchProtocol = 'sameAsCurrentPage', $path = '')
    {
        if (strlen($path) > 0 && !Text::beginsWith($path, '/')) {
            $path = '/' . $path;
        }
        return self::getShortEufBaseUrl($matchProtocol, '/app' . $path);
    }

    /**
     * Function to determine if a particular page should always force SSL access
     *
     * @param string $request Relative page name (e.g. '/app/utils/login_form')
     * @return bool Whether page should force SSL access
     * @internal
     */
    public static function shouldPageForceSsl($request)
    {
        if(!Config::getConfig(CP_FORCE_PASSWORDS_OVER_HTTPS))
            return false;

        $pregFunction = function($page) use ($request) {
            return preg_match("@^(/app/)?$page(/|#|$)@", $request);
        };
        return $pregFunction(Config::getConfig(CP_LOGIN_URL))
            || $pregFunction("account/setup_password")
            || $pregFunction("account/reset_password");
    }

    /**
     * Returns the base url for the site with the calculated protocol
     *  e.g. http://{site}.com
     *
     * @param mixed $matchProtocol Indicates if the URL should match the current page, the request, or one of the protocol configs.
     * Defaults to the same value as the current page.
     * Valid values are true, false, 'sameAsRequest' and 'sameAsCurrentPage'.
     * 'sameAsRequest' should be used if including resources (e.g. CSS, images) onto the current page. This looks at the actual connection.
     * 'sameAsCurrentPage' should be used if creating a link to another page in the same security context.  This looks at the current page's protocol.
     * true will use the admin protocol config
     * false will use the enduser protocol config
     * @param string $path Appended to the base URL
     * @return string The URL for the site
     */
    public static function getShortEufBaseUrl($matchProtocol = 'sameAsCurrentPage', $path = '')
    {
        static $eufBaseAdminUrl;
        static $eufBaseEndUserUrl;

        if (strlen($path) > 0 && !Text::beginsWith($path, '/'))
        {
            $path = '/' . $path;
        }
        if(self::shouldPageForceSsl($path))
        {
            return parent::calculateEufBaseUrl('shouldBeSecure') . $path;
        }
        if ($matchProtocol === 'sameAsRequest')
        {
            return parent::calculateEufBaseUrl('sameAsRequest') . $path;
        }
        if ($matchProtocol === 'sameAsCurrentPage')
        {
            $matchProtocol = USES_ADMIN_HTTPS_SEC_RULES;
        }
        if ($matchProtocol)
        {
            if (!isset($eufBaseAdminUrl))
            {
                $eufBaseAdminUrl = parent::calculateEufBaseUrl(true);
            }
            return $eufBaseAdminUrl . $path;
        }
        if (!isset($eufBaseEndUserUrl))
        {
            $eufBaseEndUserUrl = parent::calculateEufBaseUrl(false);
        }
        return $eufBaseEndUserUrl . $path;
    }

    /**
     * Returns base URL for Syndicated Widget services
     *  e.g. http://widget.{site}.com/
     *
     * @param bool $requireCachedServer Denotes if we can fall back to using the OE_WEB server if the cached server doesn't exist
     * @param string $path URL path to append to server hostname
     * @param bool $getIP Denotes if the returned URL should be the IP of the server instead of the URL
     * @return string widget service URL
     */
    public static function getCachedContentServer($requireCachedServer = false, $path = '', $getIP = false)
    {
        $useHttps = Config::getConfig(SEC_END_USER_HTTPS);
        if($useHttps || self::isRequestHttps())
            $protocol = 'https';
        else
            $protocol = 'http';

        if($getIP)
            $widgetServer = Config::getConfig(CACHED_CONTENT_SERVER_IP);
        else
            $widgetServer = Config::getConfig(CACHED_CONTENT_SERVER);
        if(!$widgetServer || $widgetServer === '')
        {
            if($requireCachedServer)
                return false;
            $widgetServer = Config::getConfig(OE_WEB_SERVER);
        }
        return "$protocol://$widgetServer$path";
    }

    /**
     * Given a URL, strips the leading "http:" to convert the URL into a
     * network-path reference. ({@link http://tools.ietf.org/html/rfc3986#section-4.2})
     * When the browser loads this URL, it will expand the URL to use the same
     * protocol (HTTP or HTTPS) as the page loading it.  That's useful for
     * syndicated widgets because we don't know whether the hosting page will
     * be secure.  Loading insecure content into a secure page can cause a
     * browser error.
     * URLs beginning with anything other than 'http:' are returned unmodified.
     *
     * @param string $url URL to convert.
     * @return string A network-path reference if the input was an http: URL; otherwise the original input.
     */
    public static function convertInsecureUrlToNetworkPathReference($url) {
        if (!Text::beginsWithCaseInsensitive($url, 'http://'))
            return $url;
        return Text::getSubstringStartingWith($url, '//');
    }

    /**
     * Indicates if the current request was made over HTTPS.
     * @return bool Whether or not the current request was made over HTTPS
     */
    public static function isRequestHttps()
    {
        return (isset($_SERVER['HTTP_RNT_SSL']) && $_SERVER['HTTP_RNT_SSL'] === 'yes') || (!IS_HOSTED && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    }

    /**
     * Returns the URL of the home page without the hostname and protocol.
     *
     * @param bool $makeAbsolute Indicates if the returned value should include the leading '/app/' part of the URL or only the page name.
     * @return string The URL of the home page without the hostname and protocol.
     */
    public static function getHomePage($makeAbsolute = true)
    {
        $defaultPage = Config::getConfig(CP_HOME_URL);
        if (!$defaultPage)
            $defaultPage = 'home';
        if (!$makeAbsolute)
            return $defaultPage;
        return "/app/$defaultPage";
    }

    /**
     * Determines if the given url is external to CP based on OE_WEB_SERVER
     *
     * @param string $url The url to check
     * @return bool True if external url, false if url is in CP
     */
    public static function isExternalUrl($url)
    {
        $host = Config::getConfig(OE_WEB_SERVER);
        return ($host !== parse_url($url ? $url : '', PHP_URL_HOST));
    }

    /**
     * Determines if redirection to the external URL is permitted
     *
     * @param string $url The URL to check
     * @return bool True if redirection to external URL is permitted, otherwise false
     */
    public static function isRedirectAllowedForHost($url)
    {
        if (!Text::beginsWith($url, 'http') && !Text::beginsWith($url, '//')) {
            return true;
        }

        $redirectHosts = Config::getConfig(CP_REDIRECT_HOSTS);
        if ($redirectHosts === '*') {
            return true;
        }

        if (!$redirectHosts) {
            $validHosts = array();
        }
        else {
            $validHosts = array_map('trim', explode(',', $redirectHosts));
            if (in_array('*', $validHosts)) {
                return true;
            }
        }

        $validHosts[] = $_SERVER['HTTP_HOST']; // should be the same as OE_WEB_SERVER
        if ($communityBaseUrl = trim(Config::getConfig(COMMUNITY_BASE_URL))) {
            $validHosts[] = $communityBaseUrl;
        }

        // parse_url() doesn't handle protocol relative values (ie, //domain.com = NULL)
        if (Text::beginsWith($url, '//')) {
            $url = "http:" . $url;
        }
        $host = parse_url($url, PHP_URL_HOST);
        return self::hostIsAllowed($host, $validHosts);
    }

    /**
     * Redirects the current request if the user is on http and might be logged in on https.
     * Only redirect if request is of type GET because data will get lost in the redirect otherwise
     * @return void
     */
    public static function redirectToHttpsIfNecessary() {
        $CI = get_instance();
        if($CI->session && $CI->session->isProfileFlagCookieSet() && !self::isRequestHttps() && $_SERVER['REQUEST_METHOD'] === 'GET')
            self::redirectIfPageNeedsToBeSecure(true);
    }

    /**
     * Redirects user to same page if user is not on https and CP_FORCE_PASSWORDS_OVER_HTTPS is enabled.
     *
     * @param bool $forceRedirect Skips check for CP_FORCE_PASSWORDS_OVER_HTTPS
     * @return void
     */
    public static function redirectIfPageNeedsToBeSecure($forceRedirect = false)
    {
        if(($forceRedirect || Config::getConfig(CP_FORCE_PASSWORDS_OVER_HTTPS)) && !self::isRequestHttps())
        {
            Framework::setLocationHeader(self::deleteParameter('https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'], 'session') . self::sessionParameter());
            exit;
        }
    }

    /**
     * Generic function used to redirect the user to our error page but
     * also have support for changing the URL if we're in Facebook mode
     *
     * @param int $errorCode The error code to show
     * @param bool $permanent Denotes if the page move is considered permanent which will use a 301 header code instead of a 302
     * @return void
     * @internal
     */
    public static function redirectToErrorPage($errorCode, $permanent = false)
    {
        if($permanent){
            header($_SERVER['SERVER_PROTOCOL'] . ' 301 Moved Permanently');
        }
        Framework::setLocationHeader("/app/error/error_id/$errorCode" . self::sessionParameter());
        exit;
    }

    /**
     * Generic function used to redirect the user to the question detail page using a question ID
     * @param int $qid Question ID
     * @param int $commentID Comment ID
     * @return void
     * @internal
     */
    public static function redirectToQuestionDetailPage($qid, $commentID = null) {
        Framework::setLocationHeader(self::defaultQuestionUrl($qid, $commentID));
        exit;
    }

    /**
     * Default function to create KB answer url
     * @param int $id Unique answer id
     * @return string Answer Url for specified KB answer
     */
    public static function defaultAnswerUrl ($id) {
        static $answerUrlPrefix;
        if (!$answerUrlPrefix) {
            $answerUrlPrefix = '/app/' . Config::getConfig(CP_ANSWERS_DETAIL_URL) . '/a_id';
        }
        return "$answerUrlPrefix/$id" . (($keywordWithKey = self::getParameterWithKey('kw')) ? "/$keywordWithKey" : '') . self::sessionParameter();
    }

    /**
     * Default function to create a social question url
     * @param int $id Unique question id
     * @param int $commentID Unique comment id
     * @return string Question Url for specified Social Question
     */
    public static function defaultQuestionUrl ($id, $commentID = null) {
        static $questionUrlPrefix;
        if (!$questionUrlPrefix) {
            $questionUrlPrefix = '/app/' . Config::getConfig(CP_SOCIAL_QUESTIONS_DETAIL_URL) . '/qid';
        }
        return "$questionUrlPrefix/$id" . (($commentID) ? "/comment/$commentID" : '') . (($keywordWithKey = self::getParameterWithKey('kw')) ? "/$keywordWithKey" : '') . self::sessionParameter();
    }
    
    /**
     * Warning: Returns unsanitized POST data thus they are susceptible to xss attack
     * @param string $fieldName Name of the field of $_POST variable
     * @param boolean $form It will fetch parsed form data from $_POST variable 
     * @return array|string|null Associative array whose keys are each field's name and values are the fields 
     *         or only fields's value of the field name argument
     */
    public static function getRawPostFields($fieldName = null, $form = false) {
        $CI = func_num_args() > 2 ? func_get_arg(2) : get_instance(); /* Enable unit tests */
        if($form === true) {
            return $CI->model('Field')->getRawFormFields($fieldName);
        }
        return \RightNow\Environment\unsanitizedPostVariable($fieldName);
    }
}
