<?php

use \RightNow\Libraries\Cache\ReadThroughCache;

class TestReadThroughCache extends ReadThroughCache
{
    function set($key, $value)
    {
        return parent::set($key, $value);
    }
}

class ReadThroughCacheTest extends CPTestCase 
{
    function testReadThroughCacheTest() 
    {
        $cache = new TestReadThroughCache(function() { return microtime(); });

        try
        {
            $this->assertIdentical($cache->count(), 0);

            $cache->set("key1", "value1");
            $value2 = $cache->get("key2");
            $value3 = $cache->get("key3");

            $this->assertIdentical($cache->count(), 3);
            $this->assertIdentical($cache->get("key1"), "value1");
            $this->assertIdentical($cache->get("key2"), $value2);
            $this->assertNotIdentical($cache->get("key2"), $cache->get("key3"));

            $cache->expire("key1");
            $this->assertIdentical($cache->count(), 2);

            $cache->set("arrayKey", array("value"));
            $this->assertIdentical($cache->get("arrayKey"), array("value"));

            $cache->clear();
            $this->assertIdentical($cache->count(), 0);

        }
        catch (\Exception $e)
        {
            $this->fail();
        }
    }
}
