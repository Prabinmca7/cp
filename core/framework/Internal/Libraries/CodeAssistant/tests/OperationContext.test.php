<?php
require_once(CPCORE . 'Internal/Utils/CodeAssistant.php');
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class OperationContextTest extends CPTestCase
{
    public $testingClass = '\RightNow\Internal\Libraries\CodeAssistant\OperationContext';
    function testCreatePathObject() {
        list($reflectionClass, $method, $property) = $this->reflect('method:createPathObject', 'absolutePath');

        $class = $reflectionClass->newInstance();
        $property->setValue($class, CUSTOMER_FILES);
        $results = $method->invoke($class, CUSTOMER_FILES . 'controllers/AjaxCustom.php');
        $this->assertIdentical($results, array(
            'key' => 'cp',
            'hiddenPath' => 'customer/development/',
            'visiblePath' => 'controllers/AjaxCustom.php'
        ));

        $property->setValue($class, HTMLROOT . '/euf/assets/');
        $results = $method->invoke($class, HTMLROOT . '/euf/assets/images/avatar_photo.jpg');

        $this->assertIdentical($results, array(
            'key' => 'assets',
            'hiddenPath' => 'assets/',
            'visiblePath' => 'images/avatar_photo.jpg'
        ));
    }

    function testNormalizePath() {
        list($reflectionClass, $method, $absolutePath) = $this->reflect('method:normalizePath', 'absolutePath');
        $instance = $reflectionClass->newInstance();
        $absolutePath->setValue($instance, CUSTOMER_FILES);

        //Strip slash from the end
        $this->assertIdentical($method->invoke($instance, CPCORE . 'cpHistory/', false), CPCORE . 'cpHistory');

        //Remove double slashes
        $this->assertIdentical($method->invoke($instance, CPCORE . '/cpHistory', false), CPCORE . 'cpHistory');

        //non-writable path can't be accessed if it impacts file system
        try {
            $method->invoke($instance, CPCORE . 'cpHistory', true);
            $this->fail('Expected exception');
        }
        catch(\Exception $e) {
            $this->assertTrue(\RightNow\Utils\Text::stringContains($e->getMessage(), 'not a writable path'));
        }

        //but it can be accessed, if it doesn't impact the FS
        $this->assertIdentical($method->invoke($instance, CPCORE . 'cpHistory', false), CPCORE . 'cpHistory');

        //non-readable path can't be accessed
        try {
            $method->invoke($instance, '/custom/scripts', false);
            $this->fail('Expected exception');
        }
        catch(\Exception $e) {
            $this->assertTrue(\RightNow\Utils\Text::stringContains($e->getMessage(), 'not accessible'));
        }
    }

    function testGetInstructions() {
        list($reflectionClass, $instructionSet) = $this->reflect('instructionSet');

        $instance = $reflectionClass->newInstance();

        //If we already have instructions, just return them
        $instructionSet->setValue($instance, array(array('operation' => 'test', 'path' => 'test')));
        $this->assertTrue(count($instance->getInstructions()) === 1);
    }

    function testGetFile() {
        list($reflectionClass, $writableCache, $fileCache) = $this->reflect('writableFileCache', 'fileCache');

        $instance = $reflectionClass->newInstance();
        $instance->setAbsolutePath(CUSTOMER_FILES);

        //A non-existent file should not show results
        $this->assertFalse($instance->getFile('nonExistentFile'));

        //A writable file is loaded and the content returned
        $result = $instance->getFile('widgetVersions');
        $cache = $writableCache->getValue($instance);
        $this->assertTrue(isset($cache[CUSTOMER_FILES . 'widgetVersions']));
        $this->assertTrue(\RightNow\Utils\Text::stringContains($result, 'standard/input/ProductCategoryInput'));

        //A non-writable file is still loaded, but in a different cache
        $instance->setAbsolutePath(CORE_FILES);
        $result = $instance->getFile('cpHistory');
        $cache = $fileCache->getValue($instance);

        $this->assertTrue(isset($cache[CORE_FILES . 'cpHistory']));
        $this->assertTrue(\RightNow\Utils\Text::stringContains($result, 'frameworkVersions'));

        //Retrieving a cached non-writable file returns the correct value
        $fileCache->setValue($instance, array(CORE_FILES . 'cpHistory' => 'test content'));
        $this->assertIdentical('test content', $instance->getFile('cpHistory'));

        //Retrieving a cached writable file returns the correct value
        $writableCache->setValue($instance, array(CUSTOMER_FILES . 'widgetVersions' => 'magic content'));
        $instance->setAbsolutePath(CUSTOMER_FILES);
        $this->assertIdentical('magic content', $instance->getFile('widgetVersions'));
    }

    function testSetAbsolutePath() {
        list($reflectionClass, $property) = $this->reflect('absolutePath');

        $instance = $reflectionClass->newInstance();

        //Only accept strings
        try {
            $instance->setAbsolutePath(true);
            $this->fail('Expected exception');
        }
        catch(\Exception $e) {
            $this->assertTrue(\RightNow\Utils\Text::stringContains($e->getMessage(), 'must be a string'));
        }

        //Require that the string is absolute
        try {
            $instance->setAbsolutePath('my/relative/path');
            $this->fail('Expected exception');
        }
        catch(\Exception $e) {
            $this->assertTrue(\RightNow\Utils\Text::stringContains($e->getMessage(), 'must be a string and absolute'));
        }

        //Valid path
        $instance->setAbsolutePath(CPCORE);
        $this->assertIdentical(CPCORE, $property->getValue($instance));
    }
}
