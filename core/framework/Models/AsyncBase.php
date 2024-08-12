<?php
namespace RightNow\Models;

use RightNow\Api;

require_once CPCORE . 'Libraries/Asynchronous.php';

/**
 * Provides a mechanism for models to make asynchronous HTTP requests to external servers.
 * Useful for making several external requests simultaneously without blocking execution.
 *
 * In order to use this asynchronous capability...
 *
 * * The model must extend this class.
 * * The model must pass an array to its parent constructor call that contains the names of
 *   all methods that will be primed to handle an asynchronous response.
 * * Each one of these listed methods must return data in a specific format when its `async` flag
 *   is set:
 *
 *              Array(
 *              'url'      => [String] URL of external API,
 *              'cacheKey' => [String] (optional) Cache key to use for the request; if not specified, url is used
 *              'method'   => [String] (optional) Request method: 'get' or 'post'; defaults to 'get',
 *              'data'     => [Array]  (optional) Associative array of form data to post if method is 'post',
 *              'host'     => [String] (optional) Host name to use for the request, if different than the one in url,
 *              'timeout'  => [Int]    (optional) Number of seconds before a connection is considered timed-out; defaults to 3,
 *              'callback' => [Closure|String] (optional) Either a function [Closure] or a [String] model method name to execute when a response is received,
 *              'params'   => [Mixed]  (optional) parameters to pass to the callback in addition to the response, which is the first argument,
 *              )
 *
 *   The method can also return a falsey value in order to cancel the request.
 * * The caller of the model must then call the model's request method; the method that returns the data structure from above is the first parameter,
 *   followed by any parameters that that method expects:
 *
 *      `$CI->model('MyModel')->request('getResultsFromAPI', $keyword, $options);`
 *
 * * The object that is returned to the caller has a getResponse method that the caller may invoke at any time (preferably later) in order to obtain the results that have
 *   been retrieved and (optionally) post-processed by the model-specified callback function.
 *
 *              $requestHandle = $CI->model('MyModel')->request('getResultsFromAPI', $keyword, $options);
 *              ... // Do some other stuff
 *              $results = $requestHandle->getResponse();
 *              OR
 *              $results = $CI->model('MyModel')->request('getResultsFromAPI', $keyword, $options)->getResponse();
 *
 * * Every response is stored in memcache for 30 minutes, keyed by cacheKey or url.
 */
class AsyncBase extends Base {
    private $asyncFunctions = array();
    private static $cache;

    protected $async = false;

    /**
     * Constructor.
     * @param array $asyncFunctions Contains the names of all model functions that intend to use asynchronous fetching
     * @param object|null $cache Cache class to use
     */
    public function __construct(array $asyncFunctions, $cache = null) {
        parent::__construct();
        $this->asyncFunctions = $asyncFunctions;
        self::$cache = $cache ?: new AsyncBaseCache();
    }

    /**
     * Sets the async flag and invokes the specified method, expecting to return a data structure that is used to
     * establish a new connection.
     * @param string $method Name of the model's method that will return an array containing a url and any callback
     * @return AsyncBaseRequest An instance of AsyncBaseRequest that may be used to fetch the results later
     * @throws \Exception If the extending model doesn't return what's expected
     */
    public function request($method /*, varies */) {
        if (!in_array($method, $this->asyncFunctions)) {
            throw new \Exception("The $method method hasn't been declared for asynchronous use by the model you're calling");
        }

        $this->async = true;
        $modelResponse = call_user_func_array(array($this, $method), array_slice(func_get_args(), 1));
        $this->async = false;

        if (!$modelResponse) {
            return new AsyncBaseRequest(array('cancelled' => true));
        }
        if (!$url = $modelResponse['url']) {
            // Don't check url validity here; Asynchronous::request will throw an exception if it's truly invalid
            throw new \Exception("The URL must be specified.");
        }

        if ($callback = $modelResponse['callback']) {
            if (is_string($callback)) {
                if (method_exists($this, $callback)) {
                    $callback = array($this, $callback);
                }
                else {
                    throw new \Exception("The callback method specified, {$callback}, doesn't exist for the model you're using");
                }
            }
            else if (!is_callable($callback)) {
                throw new \Exception("The callback specified isn't a callable function");
            }
        }

        $cacheKey = $modelResponse['cacheKey'] ?: $url;
        $requestIsPost = $modelResponse['method'] && strtolower($modelResponse['method']) === 'post';

        if (!$requestIsPost && ($cachedResponse = self::checkCache($cacheKey))) {
            $request = new AsyncBaseRequest(array(
                'url' => $url,
                'response' => $cachedResponse,
                'callback' => $callback,
                'arguments' => $modelResponse['params'],
            ));
        }
        else {
            $requestArgs = array('url' => $url, 'key' => $method . time(), 'host' => $modelResponse['host']);
            $cacheable = true;

            if ($requestIsPost) {
                $requestArgs += array('method' => 'post', 'data' => $modelResponse['data']);
                $cacheable = false;
            }

            try {
                $requestOptions = array('connectionKey' => \RightNow\Libraries\Asynchronous::request($requestArgs));
            }
            catch (\Exception $e) {
                Api::phpoutlog("Error making request to <{$requestArgs['url']}>: " . $e->getMessage());
                $requestOptions = array('response' => '');
                if (IS_DEVELOPMENT) {
                    echo $e->getMessage();
                }
            }

            $request = new AsyncBaseRequest(array(
                'url'       => $url,
                'cacheKey'  => $cacheKey,
                'callback'  => $callback,
                'arguments' => $modelResponse['params'],
                'cacheable' => $cacheable,
            ) + $requestOptions);
        }

        return $request;
    }

    /**
     * Caches the given data
     * @param string $key Cache key
     * @param string $result The response to cache
     * @return Result of cache set operation
     * @internal
     */
    public static function cacheResult($key, $result) {
        return self::$cache->set($key, $result);
    }

    /**
     * Looks to see if a result has been stored for the given key value.
     * @param string $key Cache key
     * @return mixed False or the cached results
     * @internal
     */
    public static function checkCache($key) {
        return self::$cache->get($key);
    }
}

/**
* Returned from requests to asynchronous model methods.
* Provides connectionMade and responseReceived flags.
*/
class AsyncBaseRequest {
    public $connectionMade = false;
    public $responseReceived = false;
    public $url = '';

    private $connectionKey;
    private $callback;
    private $callbackArguments;
    private $response;
    private $cacheable;
    private $cacheKey;

    /**
     * Constructor.
     * @param array $options Must contain url, callback, arguments, cacheable.
     * Then must have either connectionMade or response.
     * @internal
     */
    public function __construct(array $options) {
        if ($options['cancelled'] === true) {
            $this->connectionMade = false;
            return;
        }
        $this->url = $options['url'];
        $this->callback = $options['callback'];
        $this->cacheKey = $options['cacheKey'];
        $this->cacheable = $options['cacheable'];
        $this->callbackArguments = $options['arguments'];

        if ($options['connectionKey']) {
            $this->connectionMade = true;
            $this->connectionKey = $options['connectionKey'];
        }
        else if (array_key_exists('response', $options)) {
            $this->connectionMade = $this->responseReceived = true;
            $this->response = $options['response'];
        }
    }

    /**
     * Fetches the response.
     * @return string Results from the HTTP request
     */
    public function getResponse() {
        if (!$this->connectionMade) {
            return false;
        }
        if (!$this->responseReceived && $this->connectionKey) {
            $this->response = \RightNow\Libraries\Asynchronous::get($this->connectionKey);
            if ($this->cacheable) {
                AsyncBase::cacheResult($this->cacheKey, $this->response);
            }
            $this->responseReceived = true;
        }
        if ($this->callback) {
            $arguments = ($this->callbackArguments) ? array($this->response, $this->callbackArguments) : array($this->response);
            return call_user_func_array($this->callback, $arguments);
        }
        return $this->response;
    }
}

/**
 * Wraps PersistentReadThroughCache in order to call protected methods.
 *
 * @internal
 */
class AsyncBaseCache extends \RightNow\Libraries\Cache\PersistentReadThroughCache {
    const DEFAULT_CACHE_TIME = 1800; // 30 min

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(self::DEFAULT_CACHE_TIME);
    }

    /**
     * Calls into PersistentReadThroughCache's protected set method.
     * @param string $key Cache key
     * @param string $value Cache value
     * @return void
     */
    public function set($key, $value) {
        try {
            parent::set($key, $value);
        }
        catch (\Exception $e) {
            //Failure to insert an entry shouldn't throw an exception since it'll just cause a cache miss
        }
    }

    /**
     * Calls into PersistentReadThroughCache's protected check method.
     * @param string $key Cache key
     * @return mixed Cache result or false on failure
     */
    public function get($key) {
        try {
            $result = parent::check($key);
        }
        catch (\Exception $e) {
            //Cache failure, will cause false to be returned
        }
        if ($result !== null && $result !== '') {
            return @unserialize($result);
        }
        return false;
    }
}