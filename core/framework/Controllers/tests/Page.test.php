<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\FileSystem,
    RightNow\Utils\Text,
    RightNow\UnitTest\Helper as TestHelper;

class PageTest extends CPTestCase {
    public $testingClass = 'RightNow\Controllers\Page';

    function testRender() {
        $response = $this->makeRequest("/ci/Page/render", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "Location: /app/"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));
        $this->assertTrue(Text::stringContains($response, 'Cache-Control: no-cache, no-store'));
    }

    function testGetMetaInformation() {
        // Should
        // 1) Redirect to /app/method
        // 2) 404 because method is prefixed with underscore
        $response = $this->makeRequest("/ci/Page/_sendUserToErrorPage", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, '301 Moved Permanently'));
        $this->assertTrue(Text::stringContains($response, '/app/_sendUserToErrorPage'));
        $this->assertTrue(Text::stringContains($response, '404 Not Found'));
    }

    function testInvalidPageUrls() {
        //Product without non existing category id
        $response = $this->makeRequest("/app/categories/detail/c", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, '302 Moved Temporarily'));
        $this->assertTrue(Text::stringContains($response, '/app/categories/detail/c'));
        $this->assertTrue(Text::stringContains($response, "Location: /app/error/error_id/404"));

        //Product without non existing product id
        $response = $this->makeRequest("/app/products/detail/p/", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, '302 Moved Temporarily'));
        $this->assertTrue(Text::stringContains($response, '/app/products/detail/p/'));
        $this->assertTrue(Text::stringContains($response, "Location: /app/error/error_id/404"));
        
        $response = $this->makeRequest("/app/categories/detail/c/99999999", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, '302 Moved Temporarily'));
        $this->assertTrue(Text::stringContains($response, '/app/categories/detail/c/99999999'));
        $this->assertTrue(Text::stringContains($response, "Location: /app/error/error_id/404"));

        //Product without non existing product id
        $response = $this->makeRequest("/app/products/detail/p/99999999", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, '302 Moved Temporarily'));
        $this->assertTrue(Text::stringContains($response, '/app/products/detail/p/99999999'));
        $this->assertTrue(Text::stringContains($response, "Location: /app/error/error_id/404"));

        //Question detail without question id
        $response = $this->makeRequest("/app/social/questions/detail/qid/", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, '301 Moved Permanently'));
        $this->assertTrue(Text::stringContains($response, '/app/social/questions/detail/qid/'));
        $this->assertTrue(Text::stringContains($response, "Location: /app/error/error_id/6"));
    }

    function testGetLoginReturnUrl()
    {
        // Set segments prior to instantiating page,
        // because checks in constructor emit a 404 without doing so
        $realSegments = $CI->router->segments;
        $this->CI->router->segments = array('page', 'render', 'home',
            'blah', '1', 'a_id', '2', 'i_id', '3', 'kw', '4', 'p', '5', 'c', '6',
            'email', '7', 'cid', '8', 'unsub', '9', 'session', '10', 'session_id', '11');
        $this->CI->router->setUriData();

        $page = new RightNow\Controllers\Page();
        $method = new ReflectionMethod('RightNow\Controllers\Page', '_getLoginReturnUrl');
        $method->setAccessible(true);
        $page->page = '/app/home';

        $this->assertIdentical('/app/home/a_id/2/i_id/3/kw/4/p/5/c/6/email/7/cid/8/unsub/9', $method->invoke($page));

        $this->CI->router->segments = $realSegments;
        $this->CI->router->setUriData();
    }

    function testPageSetVaryWithNoHook()
    {
        $this->pageSetVary();
    }

    function testPageSetVaryWithHook()
    {
        $hookPath = DOCROOT . '/cp/generated/production/optimized/config/pageSetMapping.php';
        if (FileSystem::isReadableFile($hookPath))
            $originalHooks = @file_get_contents($hookPath);
        $hooks = "<?php
\$rnHooks['pre_page_set_selection'][] = array( 'class' => 'Sample', 'function' => 'sampleFunction', 'filepath' => '' );
";
        FileSystem::filePutContentsOrThrowExceptionOnFailure($hookPath, $hooks);

        $sampleModelPath = DOCROOT . '/cp/generated/production/optimized/config/pageSetMapping.php';
        if (FileSystem::isReadableFile($sampleModelPath))
            $originalSampleModel = @file_get_contents($sampleModelPath);
        $sampleModel = "<?php
namespace Custom\Models;
class Sample extends \RightNow\Models\Base {
function __construct() {
parent::__construct();
}
function sampleFunction(&$pageSetArray) {
$pageSetArray = array('selected' => 1, 1 => new \RightNow\Libraries\PageSetMapping(array('id' => 1, 'item' => '/iphone/i', 'description' => 'iPhone', 'value' => 'mobile', 'enabled' => true, 'locked' => true)));
}
}
";
        FileSystem::filePutContentsOrThrowExceptionOnFailure($sampleModelPath, $sampleModel);

        $this->pageSetVary();

        @unlink($hookPath);

        if ($originalHooks)
            FileSystem::filePutContentsOrThrowExceptionOnFailure($hookPath, $originalHooks);

        @unlink($sampleModelPath);

        if ($originalSampleModel)
            FileSystem::filePutContentsOrThrowExceptionOnFailure($sampleModelPath, $originalSampleModel);
    }

    function pageSetVary()
    {
        $pageSetPath = DOCROOT . '/cp/generated/production/optimized/config/pageSetMapping.php';
        if (FileSystem::isReadableFile($pageSetPath))
            $originalProductionPageSet = @file_get_contents($pageSetPath);

        $noPageSet = "<?
function getPageSetMapping() { return array(); }
function getRNPageSetMapping() { return array(); }
";
        FileSystem::filePutContentsOrThrowExceptionOnFailure($pageSetPath, $noPageSet);
        $response = $this->makeRequest("/app/home", array('justHeaders' => true, 'noDevCookie' => true));
        $this->assertIdentical(0, preg_match('/Vary: [a-zA-Z0-9,-]*User-Agent/', $response));
        $response = $this->makeRequest("/ci/ajaxRequestMin/getCountryValues", array('justHeaders' => true, 'noDevCookie' => true));
        $this->assertIdentical(0, preg_match('/Vary: [a-zA-Z0-9,-]*User-Agent/', $response));

        $pageSetNoneEnabled = "<?
function getPageSetMapping() {
return array( 10000 => new \RightNow\Libraries\PageSetMapping(array('id' => 10000, 'item' => '/blah/i', 'description' => 'blah', 'value' => 'mobile', 'enabled' => false, 'locked' => false)), 10001 => new \RightNow\Libraries\PageSetMapping(array('id' => 10001, 'item' => '/foo/i', 'description' => 'foo', 'value' => 'mobile', 'enabled' => false, 'locked' => false)));
}
function getRNPageSetMapping() {
return array( 1 => new \RightNow\Libraries\PageSetMapping(array('id' => 1, 'item' => '/iphone/i', 'description' => 'iPhone', 'value' => 'mobile', 'enabled' => false, 'locked' => true)), 2 => new \RightNow\Libraries\PageSetMapping(array('id' => 2, 'item' => '/Android/i', 'description' => 'Android', 'value' => 'mobile', 'enabled' => false, 'locked' => true)));
}
";
        FileSystem::filePutContentsOrThrowExceptionOnFailure($pageSetPath, $pageSetNoneEnabled);
        $response = $this->makeRequest("/app/home", array('justHeaders' => true, 'noDevCookie' => true));
        $this->assertIdentical(0, preg_match('/Vary: [a-zA-Z0-9,-]*User-Agent/', $response));
        $response = $this->makeRequest("/ci/ajaxRequestMin/getCountryValues", array('justHeaders' => true, 'noDevCookie' => true));
        $this->assertIdentical(0, preg_match('/Vary: [a-zA-Z0-9,-]*User-Agent/', $response));

        $pageSetEnabled = "<?
function getPageSetMapping() {
return array( 10000 => new \RightNow\Libraries\PageSetMapping(array('id' => 10000, 'item' => '/blah/i', 'description' => 'blah', 'value' => 'mobile', 'enabled' => true, 'locked' => false)), 10001 => new \RightNow\Libraries\PageSetMapping(array('id' => 10001, 'item' => '/foo/i', 'description' => 'foo', 'value' => 'mobile', 'enabled' => true, 'locked' => false)));
}
function getRNPageSetMapping() {
return array( 1 => new \RightNow\Libraries\PageSetMapping(array('id' => 1, 'item' => '/iphone/i', 'description' => 'iPhone', 'value' => 'mobile', 'enabled' => true, 'locked' => true)), 2 => new \RightNow\Libraries\PageSetMapping(array('id' => 2, 'item' => '/Android/i', 'description' => 'Android', 'value' => 'mobile', 'enabled' => true, 'locked' => true)));
}
";
        FileSystem::filePutContentsOrThrowExceptionOnFailure($pageSetPath, $pageSetEnabled);
        $response = $this->makeRequest("/app/home", array('justHeaders' => true, 'noDevCookie' => true));
        $this->assertIdentical(1, preg_match('/Vary: [a-zA-Z0-9,-]*User-Agent/', $response));
        $response = $this->makeRequest("/ci/ajaxRequestMin/getCountryValues", array('justHeaders' => true, 'noDevCookie' => true));
        $this->assertIdentical(0, preg_match('/Vary: [a-zA-Z0-9,-]*User-Agent/', $response));

        @unlink($pageSetPath);

        if ($originalProductionPageSet)
            FileSystem::filePutContentsOrThrowExceptionOnFailure($pageSetPath, $originalProductionPageSet);
    }

    function testHookPrePageRender()
    {
        // Set segments prior to instantiating page,
        // because checks in constructor emit a 404 without doing so
        $realSegments = $this->CI->router->segments;
        $this->CI->router->segments = array('page', 'render', 'home');
        $this->CI->router->setUriData();

        $page = new RightNow\Controllers\Page();
        $page->page = '/app/home';
        $page->themes = new PageTestThemes();

        $method = new ReflectionMethod('RightNow\Controllers\Page', '_checkMeta');
        $method->setAccessible(true);

        ob_start();
        $method->invoke($page, array());
        $echoValue = ob_get_clean();
        $this->assertIdentical('', $echoValue);

        $hooks = TestHelper::getHooks();
        $hooks->setValue(array('pre_page_render' => array(
            'class' => 'PageTest',
            'function' => 'verifyThatHookIsCalled',
            'filepath' => 'Controllers/tests/Page.test.php'
        )));

        ob_start();
        $method->invoke($page, array());
        $echoValue = ob_get_clean();
        $this->assertIdentical("data: array (\n)", $echoValue);

        $this->CI->router->segments = $realSegments;
        $this->CI->router->setUriData();
    }

    function testProcessPostRequest() {
        $realSegments = $this->CI->router->segments;
        $this->CI->router->segments = array('page', 'render', 'home');
        $this->CI->router->setUriData();

        $method = $this->getMethod('_processPostRequest');
        $getClass = function($time) {
            return "<?
                namespace Custom\Libraries;
                class TestLibrary{$time} extends \RightNow\Libraries\PostRequest {
                    private \$currentTime;
                    function __construct() { \$this->currentTime = '{$time}'; }
                    public function getTime() { return \$this->currentTime; }
            }";
        };

        //Make sure the post request handling method is called
        $time = time();
        $filePath = CUSTOMER_FILES . "libraries/TestLibrary{$time}.php";
        FileSystem::filePutContentsOrThrowExceptionOnFailure($filePath, $getClass($time));

        $handler = "TestLibrary{$time}/getTime";
        $constraints = json_encode(array());
        $token = \RightNow\Utils\Framework::createPostToken($constraints, ORIGINAL_REQUEST_URI, $handler);

        $_POST = array('handler' => "TestLibrary{$time}/getTime", 'validationToken' => $token, 'constraints' => $constraints);
        $result = $method($isTesting = true);
        $this->assertEqual($time, $result);

        //@@@ QA 130404-000126 verify that we DO allow POSTs with SA-related keys
        $_POST = array('handler' => "TestLibrary{$time}/getTime", 'validationToken' => $token, 'constraints' => $constraints, 'saToken' => '');
        $result = $method($isTesting = true);
        $this->assertEqual($time, $result);

        $_POST = array('handler' => "TestLibrary{$time}/getTime", 'validationToken' => $token, 'constraints' => $constraints, 'smart_assistant' => '');
        $result = $method($isTesting = true);
        $this->assertEqual($time, $result);

        $_POST = array('handler' => "TestLibrary{$time}/getTime", 'validationToken' => $token, 'constraints' => $constraints, 'saToken' => '', 'smart_assistant' => '');
        $result = $method($isTesting = true);
        $this->assertEqual($time, $result);

        unlink($filePath);

        $this->CI->router->segments = $realSegments;
        $this->CI->router->setUriData();
    }

    static function verifyThatHookIsCalled($data)
    {
        echo "data: " . var_export($data, true);
    }
}

class PageTestThemes {
    function disableSettingTheme() {}
}
