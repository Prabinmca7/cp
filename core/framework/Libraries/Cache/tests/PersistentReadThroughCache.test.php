<?php

use \RightNow\Libraries\Cache\PersistentReadThroughCache,
    \RightNow\Libraries\Cache\Memcache;

class TestPersistentReadThroughCache extends PersistentReadThroughCache
{
    public function set($key, $value)
    {
        return parent::set($key, $value);
    }

    public function check($key)
    {
        return parent::check($key);
    }
}

class PersistentReadThroughCacheTest extends CPTestCase
{
    public $testingClass = 'RightNow\Libraries\Cache\PersistentReadThroughCache';
    private $cache;

    function setUp()
    {
        $this->cache = new TestPersistentReadThroughCache(10, function() { return microtime(); });
        parent::setUp();
    }

    function testPersistentReadThroughCache()
    {
        try
        {
            $value = $this->cache->get("key1");
            $this->assertIdentical($value, $this->cache->get("key1"));

            $value = $this->cache->get("key2");
            $this->assertIdentical($value, $this->cache->get("key2"));
            $this->assertNotIdentical($value, $this->cache->get("key1"));

            $this->assertIsA($this->cache->check("key2"), 'string');
            $this->cache->expire("key2");
            $this->assertIdentical($this->cache->check("key2"), '');

            $longKey = "key3" . str_repeat("a", 250);
            $value = $this->cache->get($longKey);
            $this->assertIdentical($value, $this->cache->get($longKey));
            $this->assertNotIdentical($value, $this->cache->get("key1"));
        }
        catch (\Exception $e)
        {
            $this->fail();
        }
    }

    function testSet()
    {
        try
        {
            $value = $this->cache->set("key3", microtime());
            $this->assertIdentical($value, $this->cache->get("key3"));
        }
        catch (\Exception $e)
        {
            $this->fail();
        }
    }

    function testGetMemcacheKey() {
        $getMemcacheKey = $this->getMethod('getMemcacheKey', array(1));
        $this->assertIdentical('DEV-key', $getMemcacheKey('key'));
    }

    function getModePrefixKeyDev() {
        list($reflectionClass, $modePrefixKey, $setModePrefixKey) = $this->reflect('modePrefixKey');
        $instance = $reflectionClass->newInstance(1);
        echo $modePrefixKey->getValue($instance);
    }

    function getModePrefixKeyDeploy() {
        list($reflectionClass, $modePrefixKey, $setModePrefixKey) = $this->reflect('modePrefixKey', 'method:setModePrefixKey');
        $instance = $reflectionClass->newInstance(1);
        $setModePrefixKey->invokeArgs($instance, array('deploy'));
        echo $modePrefixKey->getValue($instance);
    }

    function getModePrefixKeyOptimized() {
        list($reflectionClass, $modePrefixKey, $setModePrefixKey) = $this->reflect('modePrefixKey', 'method:setModePrefixKey');
        $instance = $reflectionClass->newInstance(1);
        $setModePrefixKey->invokeArgs($instance, array('optimized'));
        echo $modePrefixKey->getValue($instance);
    }

    function testSetModePrefixKey() {
        $result = $this->makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/getModePrefixKeyDev");
        $this->assertIdentical('DEV', $result);

        // deploy uses the current time
        $currentTime = time();
        $result = $this->makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/getModePrefixKeyDeploy");
        $this->assertTrue(intval($result) - $currentTime < 10, "The timestamps are not within 10 seconds");

        // optimized uses the timestamp to isolate requests
        $timestamp = \RightNow\Utils\FileSystem::getLastDeployTimestampFromFile() ?: \RightNow\Utils\FileSystem::getLastDeployTimestampFromDir();
        $result = $this->makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/getModePrefixKeyOptimized");
        $this->assertIdentical($timestamp, $result);
    }
}

class MemcacheTest extends CPTestCase
{
    function testMemcache()
    {
        try
        {
            $cache = new Memcache(10);

            $this->assertIdentical($cache->get("nonexistent"), false);

            $cache->set("key1", "value1");
            $cache->set("key2", array("value2"));

            $this->assertIsA($cache->get("key1"), 'string');
            $this->assertIdentical($cache->get("key1"), "value1");
            $this->assertIsA($cache->get("key2"), 'array');
            $this->assertIdentical($cache->get("key2"), array("value2"));
        }
        catch (\Exception $e)
        {
            $this->fail();
        }
    }
}
