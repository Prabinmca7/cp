<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Text,
    RightNow\Internal\Api;

class CacheHeadersTest extends CPTestCase {
public $testingClass = 'RightNow\Internal\Libraries\Cache\CacheHeaders';

    function testParseJson() {
        $defaultProps =   array(
            "EnableCache"   => "0",
            "PageRegex"     => array("#^(?:/|/app|/app/home)$#", "#^/app/answers#"),
            "PrivateMaxAge" => "180",
            "MemCacheTTL"   => "60"
        );

        $method = $this->getMethod('_parseJson');
        $jsonPath = CPCORE . "Internal/Libraries/Cache/tests/cacheDisabled.json";
        $json = $method($jsonPath);
        $this->assertNotNull($json);
        $this->assertIdentical($json, $defaultProps);
    }

    function testMemCacheRetrieval() {
        //Make sure json was stored in memcache
        $memCacheKey = "CacheControl";
        $memCacheHandle = Api::memcache_value_deferred_get(1, array($memCacheKey));
        $json = Api::memcache_value_fetch(1, $memCacheHandle);

        //Make sure json is returned correctly from memcache
        $this->assertNotNull($json[$memCacheKey]);

        //Clear json from memcache
        Api::memcache_value_delete(1, $memCacheKey);
    }

    function testSendHeaders() {
        //Default cache disabled
        $memCacheKey = "CacheControl";
        $method = $this->getMethod('sendHeaders');
        $noCacheArray = array('Cache-Control: no-cache, no-store', "Expires: -1", "Pragma: no-cache");
        $jsonDisabledPath = CPCORE . "Internal/Libraries/Cache/tests/cacheDisabled.json";

        $result = $method("/app/home", $jsonDisabledPath);
        $this->assertIdentical($noCacheArray, $result);

        $result = $method("/app", $jsonDisabledPath);
        $this->assertIdentical($noCacheArray, $result);

        $result = $method("/", $jsonDisabledPath);
        $this->assertIdentical($noCacheArray, $result);

        //Clear json from memcache
        Api::memcache_value_delete(1, $memCacheKey);

        //Cache enabled
        $cacheControl = 'Cache-Control: private, max-age=180, must-revalidate';
        $jsonEnabledPath = CPCORE . "Internal/Libraries/Cache/tests/cacheEnabled.json";

        $result = $method("/app/home", $jsonEnabledPath);
        // is $result a 3 element array with both Cache-Control, ETag and Expires ?
        $this->assertIdentical(3, count($result));
        $this->assertIdentical($cacheControl, $result[0]);
        // check for Etag and Expires. Also do the same for the other two
        // urls.
        $this->assertIdentical(1, preg_match('/^etag/i', $result[1]));
        $this->assertIdentical(1, preg_match('/^expires/i', $result[2]));

        $result = $method("/app", $jsonEnabledPath);
        $this->assertIdentical(3, count($result));
        $this->assertIdentical($cacheControl, $result[0]);
        $this->assertIdentical(1, preg_match('/^etag/i', $result[1]));
        $this->assertIdentical(1, preg_match('/^expires/i', $result[2]));

        $result = $method("/", $jsonEnabledPath);
        $this->assertIdentical(3, count($result));
        $this->assertIdentical($cacheControl, $result[0]);
        $this->assertIdentical(1, preg_match('/^etag/i', $result[1]));
        $this->assertIdentical(1, preg_match('/^expires/i', $result[2]));

        //Clear json from memcache
        Api::memcache_value_delete(1, $memCacheKey);

        //Json path provided is not readable
        $result = $method("/app/home", $jsonNullPath);
        $this->assertIdentical($noCacheArray, $result);

        $result = $method("/app", $jsonNullPath);
        $this->assertIdentical($noCacheArray, $result);

        $result = $method("/", $jsonNullPath);
        $this->assertIdentical($noCacheArray, $result);

        //Clear json from memcache
        Api::memcache_value_delete(1, $memCacheKey);

    }
}
