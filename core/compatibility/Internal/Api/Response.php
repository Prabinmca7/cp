<?php
namespace RightNow\Internal\Api;

require_once CORE_FILES . 'compatibility/Internal/Api/Structure/Error.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Structure/Document.php';

/**
 * Response utility class
 */
final class Response {

    const HTTP_BAD_REQUEST = '400';
    const HTTP_FORBIDDEN_STATUS_CODE = '403';
    const HTTP_INTERNAL_SERVER_ERROR = '500';
    const HTTP_NOT_FOUND_STATUS_CODE = '404';
    const HTTP_NOT_ACCEPTABLE = '406';
    const UNSUPPORTED_MEDIA_TYPE = '415';

    /**
     * Sends 403 header and creates {json-api} error document
     * @return object {json-api} error document
     */
    public static function create403Error() {
        $error = new \RightNow\Internal\Api\Structure\Error();
        $error->setStatus(self::HTTP_FORBIDDEN_STATUS_CODE);
        $error->setDetail('Access denied');

        $document = new \RightNow\Internal\Api\Structure\Document();
        $document->setErrors(array($error->output()));
        header('HTTP/1.1 403 Forbidden');
        return $document->output();
    }

    /**
     * Sends 404 header and creates {json-api} error document
     * @return object {json-api} error document
     */
    public static function create404Error() {
        $error = new \RightNow\Internal\Api\Structure\Error();
        $error->setStatus(self::HTTP_NOT_FOUND_STATUS_CODE);
        $error->setDetail('Invalid request');

        $document = new \RightNow\Internal\Api\Structure\Document();
        $document->setErrors(array($error->output()));
        header('HTTP/1.1 404 Not Found');
        return $document->output();
    }

    /**
     * Sends 406 header and creates {json-api} error document
     * @return object {json-api} error document
     */
    public static function create406Error() {
        $error = new \RightNow\Internal\Api\Structure\Error();
        $error->setStatus(self::HTTP_NOT_ACCEPTABLE);
        $error->setDetail('Not Acceptable');

        $document = new \RightNow\Internal\Api\Structure\Document();
        $document->setErrors(array($error->output()));
        header('HTTP/1.1 406 Not Acceptable');
        return $document->output();
    }

    /**
     * Sends 415 header and creates {json-api} error document
     * @return object {json-api} error document
     */
    public static function create415Error() {
        $error = new \RightNow\Internal\Api\Structure\Error();
        $error->setStatus(self::UNSUPPORTED_MEDIA_TYPE);
        $error->setDetail('Unsupported Media Type');

        $document = new \RightNow\Internal\Api\Structure\Document();
        $document->setErrors(array($error->output()));
        header('HTTP/1.1 415 Unsupported Media Type');
        return $document->output();
    }

    /**
     * Sends content-type header specific to {json-api} & generates json response
     * @param object $response Response to be converted to json
     * @return json JSON encoded response
     */
    public static function generateJSON($response) {
        header('Content-Type: application/vnd.api+json');
        return json_encode($response);
    }

    /**
     * Wrapper for ResponseObject class
     *
     * @param mixed $return The object|array|bool|string|whatever that is being returned from the method that was called.
     * @param \Closure|string $validationFunction A callable function that takes the return value as it's sole argument and returns
     * true upon success. If specified as null, no validation is performed.
     * @param array|string $errors An array of error messages, or a ResponseError objects or a string message
     * @param array|string $warnings An array of warning messages or a string message
     * @return \RightNow\Libraries\ResponseObject Instance of ResponseObject with populated properties
     * @see    \RightNow\Libraries\ResponseObject Used to construct one of these
     */
    public static function generateResponseObject($return, $validationFunction = 'is_object', $errors = array(), $warnings = array()) {
        $response = new \RightNow\Libraries\ResponseObject($validationFunction);
        $response->result = $return;

        if(!is_array($errors)){
            $errors = $errors ? array($errors) : array();
        }
        if(!is_array($warnings)){
            $warnings = $warnings ? array($warnings) : array();
        }
        foreach($errors as $error) {
            $response->error = $error;
        }
        foreach ($warnings as $warning) {
            $response->warning = $warning;
        }
        return $response;
    }

    /**
     * Returns allowed origin for CORS
     * @return string Origin allowed for CORS
     */
    public static function getAllowedOrigin() {
        $parsedReferer = $extractedOrigin = '';
        // If origin is empty, then check if the referrer is present. This is required when you disable security in chrome.
        // Function isValidRequestDomain must have be called before reaching this stage
        if(!$_SERVER['HTTP_ORIGIN']) {
            $parsedReferer = parse_url($_SERVER['HTTP_REFERER']);
            $extractedOrigin = str_replace($parsedReferer['path'], '', $_SERVER['HTTP_REFERER']);
        }
        return ($_SERVER['HTTP_ORIGIN'] ? $_SERVER['HTTP_ORIGIN'] : $extractedOrigin);
    }

    /**
     * Wrapper function used to set errors
     * @param string $message Error message
     * @param int $code Error code
     * @return \RightNow\Libraries\ResponseObject Instance of ResponseObject with error properties set
     */
    public static function getErrorResponseObject($message, $code) {
        $error = new \RightNow\Libraries\ResponseError($message, $code);
        return self::generateResponseObject(null, null, $error);
    }

    /**
     * Wrapper function used to set success results
     * @param object $result Result object
     * @param \Closure|null $validationFunction A callable function that takes the return value as it's sole argument and returns
     * true upon success. If specified as null, no validation is performed.
     * @return \RightNow\Libraries\ResponseObject Instance of ResponseObject with result properties set
     */
    public static function getResponseObject($result, $validationFunction = 'is_object') {
        return self::generateResponseObject($result, $validationFunction);
    }

    /**
    * Sends the appropriate response headers for a CORS requests.
    * @param array $allowedMethods HTTP methods to be allowed for CORS requests
    * @param boolean $allowedOrigin Origin opened for CORS request
    * should be cached for
    */
    public static function sendCORSHeaders(array $allowedMethods, $allowedOrigin = '') {
        if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            //cache OPTIONS requests for 24 hours
            header("Access-Control-Max-Age: " . 86400);
            header("Access-Control-Allow-Methods: ". implode(', ', $allowedMethods));
        }
        $allowedOrigins = !empty($allowedOrigin) ? $allowedOrigin : \RightNow\Utils\Url::getShortEufBaseUrl('sameAsRequest');
        header("Access-Control-Allow-Origin: ". $allowedOrigins);
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Vary: Origin");
    }

    /**
     * Sends cache-control header
     * @param int $privateCacheTTL Private cache TTL in seconds
     * @param int $publicCacheTTL Public cache TTL in seconds
     * @return void
     */
    public static function sendCacheHeaders($privateCacheTTL, $publicCacheTTL) {
        if(!$privateCacheTTL && !$publicCacheTTL) return;
        if($privateCacheTTL && $publicCacheTTL) {
            header("Cache-Control: public" . ($publicCacheTTL ? ", s-maxage=" . $publicCacheTTL : '') . ($privateCacheTTL ? ", max-age=" . $privateCacheTTL : ''), true);
            return;
        }
        if($privateCacheTTL) {
            header("Cache-Control: private, max-age=" . $privateCacheTTL, true);
        }
    }

    /**
     * Sends content-type header
     */
    public static function sendContentTypeHeader() {
        header('Content-Type: application/vnd.api+json');
    }

    /**
     * Sends error header
     * @param string $errorCode HTTP error code
     */
    public static function sendErrorHeader($errorCode) {
        switch($errorCode) {
            case self::HTTP_BAD_REQUEST:
                header('HTTP/1.1 400 Bad Request');
                break;
            case self::HTTP_FORBIDDEN_STATUS_CODE:
                header('HTTP/1.1 403 Forbidden');
                break;
            case self::HTTP_NOT_FOUND_STATUS_CODE:
                header('HTTP/1.1 404 Not Found');
                break;
            case self::HTTP_NOT_ACCEPTABLE:
                header('HTTP/1.1 406 Not Acceptable');
                break;
            case self::UNSUPPORTED_MEDIA_TYPE:
                header('HTTP/1.1 415 Unsupported Media Type');
                break;
            case self::HTTP_INTERNAL_SERVER_ERROR:
                header('HTTP/1.1 500 Internal Server Error');
                break;
            default:
                header('HTTP/1.1 400 Bad Request');
                break;
        }
    }

    /**
     * Sends HTTP status header
     */
    public static function sendHttpStatusHeader() {
        if ($_SERVER["REQUEST_METHOD"] == "POST")
            header("Status: 201 Created");
    }
}
