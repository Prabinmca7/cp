<?php

use RightNow\Utils\FileSystem,
    RightNow\Internal\Libraries\Widget\ExtensionLoader;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class WidgetExtensionLoaderTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Widget\ExtensionLoader';

    private $fileContentsToRestore = array();

    function __construct() {
        parent::__construct();
        umask(0);
    }

    function writeFile ($path, $contents) {
        $this->fileContentsToRestore[$path] = @file_get_contents($path);
        FileSystem::filePutContentsOrThrowExceptionOnFailure($path, $contents);
    }

    function restoreFile ($path) {
        if ($this->fileContentsToRestore[$path] === false) {
            unlink($path);
        }
        else {
            FileSystem::filePutContentsOrThrowExceptionOnFailure($path, $this->fileContentsToRestore[$path]);
        }
    }

    function testLoadExtensionNoOpsWithInvalidDirName () {
        $loader = new ExtensionLoader('viewHelperExtensions', 'ankles');
        $this->assertIdentical(array(), $loader->loadExtension('Social', 'Social.php'));
    }

    function testLoadExtensionNoOpsWithInvalidFileName () {
        $loader = new ExtensionLoader('viewHelperExtensions', 'helpers');
        $this->assertIdentical(array(), $loader->loadExtension('pricey', 'pricey'));
    }

    function testLoadExtensionLoadsCoreHelper () {
        $urlRequest = '/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . '/loadCoreHelper';
        $output = $this->makeRequest($urlRequest);
        $this->assertIdentical('', $output);
    }

    function loadCoreHelper() {
        $loader = new ExtensionLoader('viewHelperExtensions', 'helpers');
        $this->assertFalse(class_exists('\RightNow\Helpers\SocialHelper'));
        $this->assertIdentical(array('core' => true), $loader->loadExtension('Social', 'Social.php'));
        $this->assertTrue(class_exists('\RightNow\Helpers\SocialHelper'));
    }

    function testLoadExtensionLoadsCustomHelper () {
        $file = APPPATH . 'helpers/Green.php';
        $phpClass = "<? class SmotheringGreen {}\n";
        $loader = new ExtensionLoader('viewHelperExtensions', 'helpers');

        $this->writeFile($file, $phpClass);

        $this->assertFalse(class_exists('\SmotheringGreen'));
        $this->assertIdentical(array('custom' => true), $loader->loadExtension('Green', 'Green.php'));
        $this->assertTrue(class_exists('\SmotheringGreen'));

        $this->restoreFile($file);
    }

    function testLoadExtensionLoadsCustomExtendingHelper () {
        $customFile = APPPATH . 'helpers/Social.php';
        $customPhpClass = "<?\nnamespace Custom\Helpers;\nclass SocialHelper extends \RightNow\Helpers\SocialHelper {}\n";
        $this->writeFile($customFile, $customPhpClass);

        $loader = new ExtensionLoader('viewHelperExtensions', 'helpers');
        $loader::$extensionRegistry = array('viewHelperExtensions' => array('Social'));
        $this->assertFalse(class_exists('Taken'));
        $this->assertIdentical(array('core' => true, 'custom' => true),
        $loader->loadExtension('Social', 'Social.php'));
        $this->assertTrue(class_exists('\Custom\Helpers\SocialHelper'));
        $this->assertTrue(class_exists('\RightNow\Helpers\SocialHelper'));
        $this->restoreFile($customFile);
    }

    function testGetExtensionContentNoOpsWithInvalidDirName () {
        $loader = new ExtensionLoader('viewPartialExtensions', 'ankles');
        $this->assertFalse($loader->getExtensionContent('Partials.Social.Avatar', 'Partials/Social/Avatar.html.php'));
    }

    function testGetExtensionContentNoOpsWithInvalidFileName () {
        $loader = new ExtensionLoader('viewPartialExtensions', 'views');
        $this->assertFalse($loader->getExtensionContent('pricey', 'pricey.php'));
    }

    function testGetExtensionContentLoadsCoreHelper () {
        $loader = new ExtensionLoader('viewHelperExtensions', 'views');
        $result = $loader->getExtensionContent('Partials.Social.Avatar', 'Partials/Social/Avatar.html.php');
        $this->assertIsA($result, 'string');
    }

    function testGetExtensionContentLoadsCustomHelper () {
        $file = APPPATH . 'views/Partials/Green.php';
        $content = "Will Call";
        $loader = new ExtensionLoader('viewPartialExtensions', 'views');

        $this->writeFile($file, $content);

        $this->assertSame($content, $loader->getExtensionContent('Partials.Green', 'Partials/Green.php'));

        $this->restoreFile($file);
    }

    function testGetExtensionContentLoadsCustomOverriddingHelper () {
        $customFile = APPPATH . 'views/Partials/Will.php';
        $content = "Call";
        $this->writeFile($customFile, $content);

        $loader = new ExtensionLoader('viewPartialExtensions', 'views');
        $loader::$extensionRegistry = array('viewPartialExtensions' => array('Partials.Will'));

        $this->assertSame($content, $loader->getExtensionContent('Partials.Will', 'Partials/Will.php'));

        $this->restoreFile($customFile);
    }

    function testExtensionIsRegistered () {
        $loader = new ExtensionLoader('viewPartialExtensions', 'ivy');
        $this->assertFalse($loader->extensionIsRegistered('house'));
        $loader::$extensionRegistry = array('viewPartialExtensions' => array('Smothering'));
        $this->assertTrue($loader->extensionIsRegistered('Smothering'));
    }
}
