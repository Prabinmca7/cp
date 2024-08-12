<?php
namespace RightNow\Internal\Api;
use RightNow\Utils\Config;

/**
 * Request utility class
 */
final class Request {

    /**
     * Returns the current URL including the Protocol, Server and Request URI
     * @param bool $includeUri Whether or not to include request URI as well as hostname
     * @return string The current URL
     */
    public static function getOriginalUrl($includeUri = true) {
        $protocol = self::isRequestHttps() ? 'https' : 'http';
        return "$protocol://{$_SERVER['SERVER_NAME']}" . ($includeUri ? ORIGINAL_REQUEST_URI : '');
    }

    /**
     * Returns the looked up query string parameter
     * @param string $key Query string parameter name
     * @return array|string Value of the looked up parameter. If key is null then returns _GET array
     */
    public static function getQueryParams($key = null) {
        switch ($key) {
            case 'fields':
                return $_GET['fields'];
            case 'filter':
                return $_GET['filter'];
            case 'page':
                return $_GET['page'];
            default:
                return $_GET;
        }
    }

    /**
     * Returns the looked up post parameter
     * @param string $key Post parameter name
     * @return array|string Value of the looked up parameter. If key is null then returns _POST array
     */
    public static function getPostData($key = null) {
        return $key ? $_POST[$key] : $_POST;
    }

    /**
     * Gets the HTTP request method
     * @return string HTTP method name
     */
    public static function getRequestMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Returns the looked up uri parameter
     * @param string $key Uri paramter name
     * @return array|string Value of the looked up parameter. If key is null then return array of all uri parameters
     */
    public static function getUriParams($key = null) {
        $CI = func_num_args() > 1 ? func_get_arg(1) : get_instance(); // Allow unit
        $uriSegments = $CI->uri->uri_to_assoc($CI->config->item('parm_segment'));
        return $key ? $uriSegments[$key] : $uriSegments;
    }

    /**
     * Returns uri param string
     * @return string Uri param string
     */
    public static function getUriParamString() {
        $CI = func_num_args() > 0 ? func_get_arg(0) : get_instance(); // Allow unit
        $uriSegments = $CI->uri->uri_to_assoc($CI->config->item('parm_segment'));
        $toReturn = '';
        foreach ($uriSegments as $key => $value)
        {
            $toReturn .= "/$key/$value";
        }
        return $toReturn;
    }

    /**
     * Indicates if the current request was made over HTTPS.
     * @return bool Whether or not the current request was made over HTTPS
     */
    public static function isRequestHttps() {
        return (isset($_SERVER['HTTP_RNT_SSL']) && $_SERVER['HTTP_RNT_SSL'] === 'yes') || (!IS_HOSTED && $_SERVER['HTTPS'] === 'on');
    }

    /**
     * Determines whether the requesting domain is allowed to call inlay endpoint.
     * @return boolean $domainAllowed
     */
    public static function isValidRequestDomain() {
        $allowedDomains = Config::getConfig(OIT_CORS_ALLOWLIST);
        $domainAllowed = false;

        if($allowedDomains && (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN']) && preg_match('/' . $allowedDomains . '/', $_SERVER['HTTP_ORIGIN'])) {
            $domainAllowed = true; //domain allowed as origin matches
        } else if($allowedDomains && (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN']) && !preg_match('/' . $allowedDomains . '/', $_SERVER['HTTP_ORIGIN'])) {
            $domainAllowed = false; //domain not allowed as origin doesn't match
        } else if($allowedDomains && (!isset($_SERVER['HTTP_ORIGIN']) || !$_SERVER['HTTP_ORIGIN']) && (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) && preg_match('/' . $allowedDomains . '/', $_SERVER['HTTP_REFERER'])) {
            $domainAllowed = true; //domain allowed as referrer matches. This is required when you disable security in chrome.
        } else if($allowedDomains && (!isset($_SERVER['HTTP_ORIGIN']) || !$_SERVER['HTTP_ORIGIN']) && (!isset($_SERVER['HTTP_REFERER']) || !$_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_HOST']
            && preg_match('/' . $_SERVER['HTTP_HOST'] . '/', \RightNow\Utils\Url::getShortEufBaseUrl('sameAsRequest'))
            && preg_match('/' . $allowedDomains . '/', $_SERVER['HTTP_HOST'])) {
            $domainAllowed = true; //domain same as CP host
        }
        return $domainAllowed;
    }
}

