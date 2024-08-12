<?php
namespace RightNow\Controllers;
use RightNow\Internal\Api\Request,
    RightNow\Internal\Api\Response,
    RightNow\Internal\Api\Router,
    RightNow\Internal\Api\Utils,
    RightNow\Internal\Utils\Version,
    RightNow\Libraries\AbuseDetection;

if (!IS_HOSTED){
    require_once CORE_FILES . 'compatibility/Mappings/Functions.php';
}

require_once CORE_FILES . 'compatibility/Internal/Api/Request.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Response.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Router.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Utils.php';
require_once CPCORE . 'Internal/Libraries/Version.php';

/**
 * This class defines entry points for CP REST APIs
 */
final class Api extends Base {

    const MINIMUM_SUPPORTED_CX_VERSION = '18.5';

    function __construct() {
        parent::__construct();
        parent::_setClickstreamMapping(array(
            "v1" => "v1_rest_api_call"
        ));
        parent::_setMethodsExemptFromContactLoginRequired(array(
            "v1"
        ));

        //REST APIs are not supported below CX version 18.5 as OIT_CORS_ALLOWLIST is not available which
        //controls the CORS behavior
        $currentCXVersion = new \RightNow\Internal\Libraries\Version(Version::getCXVersionNumber());
        if ($currentCXVersion->lessThan(self::MINIMUM_SUPPORTED_CX_VERSION)) {
            $result = Response::create403Error();
            echo Response::generateJSON($result);
            exit;
        }
    }

    /**
     * Handler for rest api version 1 calls
     * @return json {json-api} response
     */
    public function v1() {
        AbuseDetection::check();
        if(!Request::isValidRequestDomain()) {
            $result = Response::create404Error();
            echo Response::generateJSON($result);
            return;
        }

        if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            Response::sendCORSHeaders(array('GET', 'POST'), Response::getAllowedOrigin());
            exit;
        }

        if (strpos($_SERVER['HTTP_ACCEPT'], 'application/vnd.api+json;') !== false) {
            $result = Response::create415Error();
            echo Response::generateJSON($result);
            return;
        }

        if (!in_array('application/vnd.api+json', explode(",", $_SERVER['HTTP_ACCEPT']))) {
            $result = Response::create406Error();
            echo Response::generateJSON($result);
            return;
        }

        $result = Router::route();

        if(!$result->response) {
            $result = Response::create404Error();
            echo Response::generateJSON($result);
            return;
        }
        Response::sendCORSHeaders(array('GET', 'POST'), Response::getAllowedOrigin());
        if($result->response->errors) {
            Response::sendErrorHeader($result->response->errors[0]->status);
        }
        else {
            Response::sendCacheHeaders($result->responseMetadata->privateCacheTTL, $result->responseMetadata->publicCacheTTL);
            Response::sendHttpStatusHeader();
        }
        Utils::commit(true);
        Response::sendContentTypeHeader();
        echo Response::generateJSON($result->response);
    }
}
