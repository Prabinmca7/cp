<?php
namespace RightNow\Internal\Api;

use RightNow\Internal\Api\Request,
    RightNow\Internal\Api\Resources\Answer as Answer,
    RightNow\Internal\Api\Resources\Incident as Incident,
    RightNow\Internal\Api\Utils;

require_once CORE_FILES . 'compatibility/Internal/Api/Request.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Resources/Answer.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Resources/CustomField.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Resources/Incident.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Resources/Product.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Resources/Category.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Utils.php';

/**
 * Routes the API requests
 */
final class Router {

    const RESOURCE_NAMESPACE = '\\RightNow\\Internal\\Api\\Resources\\';

    /*
     * Well it is hard to determine the perfect caching time for the three levels of cache. However it has been decided
     * to not keep anything in memcache for endpoints where response depends on individual object, and the object can be bigger in size.
     * For example, /answers/{id} can be too many in number with each answer being too big in size.
     */
    const ROUTES = array(
        '^\/answers\/$'             => array('method' => 'getAnswerList', 'memcacheTTL' => 900, 'privateCacheTTL' => 900, 'publicCacheTTL' => 900, 'requestMethod' => 'GET', 'resource' => 'Answer'),
        '^\/answers\/([0-9]+)$'     => array('method' => 'getAnswer', 'memcacheTTL' => 0, 'privateCacheTTL' => 600, 'publicCacheTTL' => 600, 'requestMethod' => 'GET', 'resource' => 'Answer'),
        '^\/categories\/$'          => array('method' => 'getCategoryList', 'memcacheTTL' => 900, 'privateCacheTTL' => 900, 'publicCacheTTL' => 900, 'requestMethod' => 'GET', 'resource' => 'Category'),
        '^\/categories\/([0-9]+)$'  => array('method' => 'getCategory', 'memcacheTTL' => 0, 'privateCacheTTL' => 900, 'publicCacheTTL' => 900, 'requestMethod' => 'GET', 'resource' => 'Category'),
        '^\/customFields\/$'        => array('method' => 'getCustomFieldList', 'memcacheTTL' => 900, 'privateCacheTTL' => 900, 'publicCacheTTL' => 900, 'requestMethod' => 'GET', 'resource' => 'CustomField'),
        '^\/incidents\/$'           => array('method' => 'createIncident', 'memcacheTTL' => 0, 'privateCacheTTL' => 0, 'publicCacheTTL' => 0, 'requestMethod' => 'POST', 'resource' => 'Incident'),
        '^\/products\/$'            => array('method' => 'getProductList', 'memcacheTTL' => 900, 'privateCacheTTL' => 900, 'publicCacheTTL' => 900, 'requestMethod' => 'GET', 'resource' => 'Product'),
        '^\/products\/([0-9]+)$'    => array('method' => 'getProduct', 'memcacheTTL' => 0, 'privateCacheTTL' => 900, 'publicCacheTTL' => 900, 'requestMethod' => 'GET', 'resource' => 'Product'),
    );

    /**
     * Routes the api call to the corresponding resource and method. Also, optimizes performance by using memcache for cacheable requests.
     * @return object {json-api} Document
     */
    public static function route() {
        $requestUri = Request::getUriParamString();
        $requestMethod = Request::getRequestMethod();
        $result = new \stdClass();
        $result->response = new \stdClass();
        $result->responseMetadata = new \stdClass();

        foreach(self::ROUTES as $request => $metadata) {
            if((preg_match('/' . $request . '/', $requestUri)) && $metadata['requestMethod'] === $requestMethod) {
                $params['uriParams'] = Request::getUriParams();
                $params['queryParams'] = Request::getQueryParams();
                $params['postData'] = Request::getPostData();

                self::cacheException($metadata, $params);
                $cache = new \RightNow\Libraries\Cache\Memcache($metadata['memcacheTTL']);
                if($cachedResult = self::fetchFromCache($cache, $metadata, $params)){
                    return $cachedResult;
                }

                $resourceClass = self::RESOURCE_NAMESPACE . $metadata['resource'];
                $resource = new $resourceClass();
                $result->response = $resource->$metadata['method']($params);

                $result->responseMetadata->privateCacheTTL = $metadata['privateCacheTTL'];
                $result->responseMetadata->publicCacheTTL = $metadata['publicCacheTTL'];
                self::storeInCache($cache, $metadata, $result, $params);
                return $result;
            }
        }
    }

    /**
     * Fetches api result from the memcache
     * @param object $cache Memcache object
     * @param array $metadata Metadata for the incoming request
     * @param array $params Uri and query parameters
     * @return object {json-api} Document
     */
    private static function fetchFromCache($cache, $metadata, $params) {
        if($metadata['memcacheTTL'] && !self::cacheException($metadata, $params)) {
            $cacheKey = Utils::getSHA2Hash($metadata['requestMethod'] . '_' . Request::getOriginalUrl());
            return $cache->get($cacheKey);
        }
    }

    /**
     * Stores the api result in memecache
     * @param object $cache Memcahe onject
     * @param array $metadata Metadata for the incoming request
     * @param object $result Document
     * @param array $params Uri and query parameters
     */
    private static function storeInCache($cache, $metadata, $result, $params) {
        if($metadata['memcacheTTL'] && !$result->response->errors && !self::cacheException($metadata, $params)) {
            $cacheKey = Utils::getSHA2Hash($metadata['requestMethod'] . '_' . Request::getOriginalUrl());
            $cache->set($cacheKey, $result);
        }
    }

    /**
     * Evaluates caching exceptions based on certain request paramters
     * @param array &$metadata Metadata for the incoming request
     * @param array $params Uri and query parameters
     * @return boolean Cacheable or not
     */
    private static function cacheException(&$metadata, $params) {
        //cache system search queries only
        if($metadata['method'] === 'getAnswerList' && isset($params['queryParams']['filter']['$content']['contains'])) {
            if($params['queryParams']['searchType'] === 'system') {
                $metadata['memcacheTTL'] = 0;
                $metadata['privateCacheTTL'] = 900;
                $metadata['publicCacheTTL'] = 900;
            }
            else {
                $metadata['memcacheTTL'] = $metadata['publicCacheTTL'] = 0;
                $metadata['privateCacheTTL'] = 300;
            }
            return true;
        }
    }
}
