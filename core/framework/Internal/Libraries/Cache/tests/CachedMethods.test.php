<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class Counter {
    static $counter = 0;
    static function increment() {
        self::$counter += 1;
    }
}

class SubClass extends \RightNow\Internal\Libraries\Cache\CachedMethods {
    function __construct() {
        $this->propertyOne = 1;
    }

    function _getCachedMethod() {
        Counter::increment();
    }
}

class CachedMethodsTest extends CPTestCase
{
    function testCachedMethods() {
        // assert _get<property>() methods only invoked once.
        $s = new SubClass();
        $this->assertEqual(0, Counter::$counter);
        $s->cachedMethod;
        $this->assertEqual(1, Counter::$counter);
        $s->cachedMethod;
        $this->assertEqual(1, Counter::$counter);
    }

    function testInvalidAttributeException() {
        // assert invoking an undefined property throws expected exception.
        $s = new SubClass();
        try {
            $s->foo;
            $this->assertFalse('Accessing invalid property $s->foo did not raise exception.');
        }
        catch (\RightNow\Internal\Libraries\Cache\InvalidAttributeException $e) {
            // Property or method does not exist: SubClass->foo
        }
        catch (\Exception $e) {
            $this->assertFalse('Accessing invalid property $s->foo did not raise expected exception.');
        }
    }

    function testReadOnlyProperties() {
        // assert private 'readOnlyProperties' array cannot be accessed and raises expected exception.
        $s = new SubClass();
        try {
            $s->readOnlyProperties['propertyOne'] = 4;
            $this->assertFalse('Accessing private property $s->readOnlyProperties did not raise exception.');
        }
        catch (\RightNow\Internal\Libraries\Cache\InvalidAttributeException $e) {
            // Property or method does not exist: SubClass->foo
        }
        catch (\Exception $e) {
            $this->assertFalse('Accessing private property $s->readOnlyProperties did not raise expected exception.');
        }
    }

    function testPropertyCannotBeSet() {
        // assert that properties are read-only
        $s = new SubClass();
        $value = $s->propertyOne;
        try {
            $s->propertyOne = 4;
            $this->assertFalse('Attempt to set class property SubClass->propertyOne did not raise exception');
        }
        catch (\RightNow\Internal\Libraries\Cache\UnsettablePropertyException $e) {
            // Property cannot be set: SubClass->propertyOne
        }
        catch (\Exception $e) {
            $this->assertFalse('UnsettablePropertyException not raised');
        }
        $this->assertEqual($value, $s->propertyOne);
    }
}
