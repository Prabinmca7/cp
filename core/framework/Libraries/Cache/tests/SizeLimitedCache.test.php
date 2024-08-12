<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Libraries\Cache\SizeLimitedCache;

class TestSizeLimitedCache extends SizeLimitedCache {
    function set($key, $value) {
        return parent::set($key, $value);
    }

    function expireInternal($key, $valueLength) {
        return parent::expireInternal($key, $valueLength);
    }

    function getDataSize($value) {
        return parent::getDataSize($value);
    }

    function getSize() {
        return $this->size;
    }
}

// Counter class for tracking how often SizeLimitedCache calls the specified callback function.
class ValueCalculationCallbackCounter {
    private static $keys = array();
    static function timesCalled($key) {
        if (array_key_exists($key, self::$keys)) {
            self::$keys[$key] = self::$keys[$key] + 1;
        }
        else {
            self::$keys[$key] = 1;
        }
        return self::$keys[$key];
    }
}

class SizeLimitedCacheTest extends CPTestCase {

    // valueCalculationCallback used by SizeLimitedCache as callback function.
    function valueCalculationCallback($key) {
        return sprintf("$key [%s]", ValueCalculationCallbackCounter::timesCalled($key));
    }

    // returnArray used by SizeLimitedCache as callback function.
    function returnArray($size) {
        $a = array();
        for ($i=1; $i <=  $size; $i++) {
            $a["key${i}"] = array("subKey${i}" => "value${i}");
        }
        return $a;
    }

    function testSizeLimitedCacheIsCaching() {
        $maxSize = 5*1024*1024;
        $c = new SizeLimitedCache($maxSize, array($this, 'valueCalculationCallback'));
        $this->assertEqual('key1 [1]', $c->get('key1'));
        $this->assertEqual('key1 [1]', $c->get('key1'));
    }

    function testSizeLimitedCacheRefetchesWhenMaxSizeExceeded() {
        $maxSize = 1;
        $c = new SizeLimitedCache($maxSize, array($this, 'valueCalculationCallback'));
        $this->assertEqual('key2 [1]', $c->get('key2'));
        $this->assertEqual('key2 [2]', $c->get('key2'));
    }

    function testSizeLimitedCacheArrayReturnedIntact() {
        $maxSize = 5*1024*1024;
        $c = new SizeLimitedCache($maxSize, array($this, 'returnArray'));
        $expected = array(
          'key1'=> array('subKey1' => 'value1'),
          'key2'=> array('subKey2' => 'value2'),
        );
        $this->assertEqual($expected, $c->get(2));
    }

    function testSizeLimitedCacheIsLimitingStringSize() {
        $c = new SizeLimitedCache(10, array($this, 'identity'));
        $this->assertEqual(0, $c->count());
        $c->get("1234");
        $this->assertEqual(1, $c->count());
        $c->get("56789");
        $this->assertEqual(2, $c->count());
        $c->get("123456789");
        $this->assertEqual(1, $c->count());
    }

    function testSizeLimitedCacheIsLimitingArraySize() {
        $c = new SizeLimitedCache(17, array($this, 'evalIt'));
        $this->assertEqual(0, $c->count());
        $c->get('"1"');
        $this->assertEqual(1, $c->count());
        $c->get('"2"');
        $this->assertEqual(2, $c->count());
        $c->get('array(6)');
        $this->assertEqual(3, $c->count());
        $c->get('array(7)');
        $this->assertEqual(1, $c->count());
    }

    function testProtectedMethods() {
        $cache = new TestSizeLimitedCache(5*1024*1024, function() { return microtime(); });

        $cache->set("stringKey", "value");
        $cache->set("numericKey", 1234);
        $cache->set("numericKey2", 123456789);
        $cache->set("arrayKey", array("value"));
        $cache->set("boolKey", true);

        $this->assertIdentical($cache->count(), 5);
        $this->assertIsA($cache->get("stringKey"), "string");
        $this->assertIsA($cache->get("numericKey"), "integer");
        $this->assertIsA($cache->get("numericKey2"), "integer");
        $this->assertIsA($cache->get("arrayKey"), "array");
        $this->assertIsA($cache->get("boolKey"), "bool");
        $this->assertIdentical($cache->get("stringKey"), "value");
        $this->assertIdentical($cache->get("numericKey"), 1234);
        $this->assertIdentical($cache->get("numericKey2"), 123456789);
        $this->assertIdentical($cache->get("arrayKey"), array("value"));

        $this->assertIdentical($cache->getDataSize($cache->get("stringKey")), strlen($cache->get("stringKey")));
        $this->assertIdentical($cache->getDataSize($cache->get("arrayKey")), strlen(serialize($cache->get("arrayKey"))));
        // anything that isn't an object, array or string should return 16
        $this->assertIdentical($cache->getDataSize($cache->get("numericKey")), 16);
        $this->assertIdentical($cache->getDataSize($cache->get("numericKey2")), 16);
        $this->assertIdentical($cache->getDataSize($cache->get("boolKey")), 16);

        $currentSize = $cache->getDataSize($cache->get("stringKey")) + $cache->getDataSize($cache->get("arrayKey")) + 48;
        $this->assertIdentical($currentSize, $cache->getSize());

        $cache->expire("stringKey");
        $this->assertIdentical($cache->count(), 4);

        $currentSize = $cache->getDataSize($cache->get("arrayKey")) + 48;
        $this->assertIdentical($currentSize, $cache->getSize());

        $cache->expireInternal("numericKey2", $cache->getDataSize($cache->get("numericKey2")));
        $currentSize -= 16;
        $this->assertIdentical($currentSize, $cache->getSize());
        $this->assertIdentical($cache->count(), 3);

        $cache->clear();
        $this->assertIdentical($cache->count(), 0);
        $this->assertIdentical($cache->getSize(), 0);
    }

    function identity($x) {
        return $x;
    }

    function evalIt($x) {
        return eval("return $x;");
    }
}
