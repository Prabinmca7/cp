<?php

use RightNow\Utils\Text,
    RightNow\UnitTest\Helper as TestHelper;

class BaseControllerTest extends CPTestCase {
    public $testingClass = 'RightNow\Controllers\Base';
    protected $hookEndpointClass = __CLASS__;
    protected $hookEndpointFilePath = __FILE__;

    function testModel() {
        $base = new \RightNow\Controllers\Base();
        try{
            $base->model(null);
            $this->fail('Should have thrown an exception since no model path was provided.');
        }
        catch(Exception $e){
            $this->pass('Exception of ' . $e->getMessage() . ' was thrown correctly.');
        }

        try{
            $base->model('');
            $this->fail('Should have thrown an exception since no model path was provided.');
        }
        catch(Exception $e){
            $this->pass('Exception of ' . $e->getMessage() . ' was thrown correctly.');
        }

        try{
            $base->model(0);
            $this->fail('Should have thrown an exception since no model path was provided.');
        }
        catch(Exception $e){
            $this->pass('Exception of ' . $e->getMessage() . ' was thrown correctly.');
        }

        try{
            $base->model(false);
            $this->fail('Should have thrown an exception since no model path was provided.');
        }
        catch(Exception $e){
            $this->pass('Exception of ' . $e->getMessage() . ' was thrown correctly.');
        }

        // Problems occur when all phpFunctional tests are run together,
        // so class exists checks.
        if (!class_exists("\\RightNow\\Models\\Answer")) {
            $this->assertIsA($base->model('Answer'), 'RightNow\Models\Answer');
        }
        if (!class_exists("\\RightNow\\Models\\Account")) {
            $this->assertIsA($base->model('account'), 'RightNow\Models\Account');
        }
        if (!class_exists("\\RightNow\\Models\\Chat")) {
            $this->assertIsA($base->model('CHAT'), 'RightNow\Models\Chat');
        }
        if (!class_exists("\\RightNow\\Models\\Clickstream")) {
            $this->assertIsA($base->model('standard/Clickstream'), 'RightNow\Models\Clickstream');
        }
        if (!class_exists("\\RightNow\\Models\\Contact")) {
            $this->assertIsA($base->model('standard/contact'), 'RightNow\Models\Contact');
        }
        if (!class_exists("\\RightNow\\Models\\FileAttachment")) {
            $this->assertIsA($base->model('FileAttachment'), 'RightNow\Models\FileAttachment');
        }
        if (!class_exists("\\Custom\\Models\\Chat")) {
            $this->assertIsA($base->model('custom/Sample'), 'Custom\Models\Sample');
        }
    }

    function testGetRequestedInterfaceForStrings() {
        $base = new \RightNow\Controllers\Base();
        $isAgentResponse = $base->_isAgentConsoleRequest();
        $requestedInterfaceResponse = $base->_getRequestedInterfaceForStrings();
        $this->assertFalse($isAgentResponse);
        $this->assertFalse((bool)$requestedInterfaceResponse);
    }

    function testGetAgentAccount() {
        $base = new \RightNow\Controllers\Base();
        $response = $base->_getAgentAccount();
        $this->assertFalse($response);
    }

    function testGetRequestedAdminLangData() {
        $base = new \RightNow\Controllers\Base();
        $interface = 'someInterfaceName';
        $lang = 'es_ES';

        // no cookie
        $this->assertIdentical(array(), $base->_getRequestedAdminLangData());

        // cookie with interface name and language code (CP Admin)
        $_COOKIE['cp_admin_lang_intf'] = "{$interface}|{$lang}";
        $result = $base->_getRequestedAdminLangData();
        $this->assertIdentical(array($interface, $lang), $result);
        unset($_COOKIE['cp_admin_lang_intf']);

        // cookie with just the interface name (CX Console)
        $_SERVER['HTTP_X_RIGHTNOW_AGENT_CONSOLE_INTERFACE'] = $interface;
        $result = $base->_getRequestedAdminLangData();
        $this->assertIdentical(array($interface, null), $result);
        unset($_SERVER['HTTP_X_RIGHTNOW_AGENT_CONSOLE_INTERFACE']);
    }

    function testDoesAccountHavePermission() {
        $base = new \RightNow\Controllers\Base();
        // This uses getAgentAccount, we shouldn't get back any info
        $response = $base->_doesAccountHavePermission(false, 'global');
        $this->assertFalse($response);
    }

    function testCheckConstructor() {
        $before = \RightNow\Controllers\Base::checkConstructor(null);
        $base = new \RightNow\Controllers\Base();
        $after = \RightNow\Controllers\Base::checkConstructor($base);
        $this->assertFalse($before);
        $this->assertTrue($after);
    }

    function testGetAgentSessionId() {
        $base = new \RightNow\Controllers\Base();
        $response = $base->_getAgentSessionId();
        $this->assertFalse($response);
    }

    function testGetPageSetPath() {
        $base = new \RightNow\Controllers\Base();
        $response = $base->getPageSetPath();
        // Null is the default pageset
        $this->assertNull($response);
    }

    function testGetPageSetOffset() {
        $base = new \RightNow\Controllers\Base();
        $response = $base->getPageSetOffset();
        // Default page set is 0
        $this->assertEqual($response, 0);
    }

    function testGetPageSetID() {
        $base = new \RightNow\Controllers\Base();
        $response = $base->getPageSetID();
        // Default page set is null
        $this->assertNull($response);
    }

    function testGetClickstreamMapping() {
        $base = new \RightNow\Controllers\Base();
        $response = $base->_getClickstreamMapping();
        $this->assertIsA($response, 'array');
        $this->assertEqual(count($response), 0);
    }

    function testGetClickstreamActionTag() {
        $base = new \RightNow\Controllers\Base();
        $response = $base->_getClickstreamActionTag("getPageSetPath");
        $this->assertEqual($response, "getPageSetPath");
    }

    function testGetAgentSessionIdFromCookie() {
        $base = new \ReflectionClass('RightNow\Controllers\Base');
        $method = $base->getMethod('_getAgentSessionIdFromCookie');
        $method->setAccessible(true);
        $instance = $base->newInstance();

        $response = $method->invoke($instance);
        $this->assertFalse($response);
    }

    function testVerifyAgentSessionId() {
        $base = new \ReflectionClass('RightNow\Controllers\Base');
        $method = $base->getMethod('_verifyAgentSessionId');
        $method->setAccessible(true);
        $instance = $base->newInstance();

        $response = $method->invoke($instance);
        $this->assertFalse($response);
    }

    function testGetPagesPath() {
        $base = new \ReflectionClass('RightNow\Controllers\Base');
        $method = $base->getMethod('_getPagesPath');
        $method->setAccessible(true);
        $instance = $base->newInstance();

        $response = $method->invoke($instance);
        $this->assertIsA($response, 'string');
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "cp/customer/development/views/pages/"));
    }

    function testGetViewsPath() {
        $base = new \ReflectionClass('RightNow\Controllers\Base');
        $method = $base->getMethod('_getViewsPath');
        $method->setAccessible(true);
        $instance = $base->newInstance();

        $response = $method->invoke($instance);
        $this->assertIsA($response, 'string');
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "cp/customer/development/views/"));
    }

    function testIsContactAllowed() {
        $base = new \ReflectionClass('RightNow\Controllers\Base');
        $method = $base->getMethod('_isContactAllowed');
        $method->setAccessible(true);
        $instance = $base->newInstance();

        $response = $method->invoke($instance);
        $this->assertTrue($response);
    }

    function testGetLoginReturnUrl() {
        $base = new \ReflectionClass('RightNow\Controllers\Base');
        $method = $base->getMethod('_getLoginReturnUrl');
        $method->setAccessible(true);
        $instance = $base->newInstance();

        $response = $method->invoke($instance);
        $this->assertNotNull($response);
        $this->assertIsA($response, 'string');
        $this->assertTrue(Text::stringContains($response, "/ci/unitTest/phpFunctional"));
    }

    function testGetPageFromSegments() {
        $pagesPath = APPPATH . 'views/pages/';
        $base = new \ReflectionClass('\RightNow\Controllers\Base');
        $method = $base->getMethod('getPageFromSegments');
        $method->setAccessible(true);
        $pageSetPath = $base->getProperty('pageSetPath');
        $pageSetPath->setAccessible(true);
        $pageSetOffset = $base->getProperty('pageSetOffset');
        $pageSetOffset->setAccessible(true);
        $instance = $base->newInstance();

        $result = $method->invoke($instance);
        $this->assertSame($pagesPath, $result['path']);
        $this->assertNull($result['page']);
        $this->assertFalse($result['found']);
        $this->assertNull($result['segment']);
        $this->assertSame('', $result['currentPath']);
        $this->assertNull($result['segmentIndex']);

        $result = $method->invokeArgs($instance, array(array('home')));
        $this->assertSame("{$pagesPath}home.php", $result['path']);
        $this->assertSame('home', $result['page']);
        $this->assertTrue($result['found']);
        $this->assertSame('home', $result['segment']);
        $this->assertSame('', $result['currentPath']);
        $this->assertSame(4, $result['segmentIndex']);

        $result = $method->invokeArgs($instance, array(array('answers', 'list', 'p', '123')));
        $this->assertSame("{$pagesPath}answers/list.php", $result['path']);
        $this->assertSame('answers/list', $result['page']);
        $this->assertTrue($result['found']);
        $this->assertSame('list', $result['segment']);
        $this->assertSame('answers/', $result['currentPath']);
        $this->assertSame(5, $result['segmentIndex']);

        $result = $method->invokeArgs($instance, array(array('bananas')));
        $this->assertSame($pagesPath, $result['path']);
        $this->assertNull($result['page']);
        $this->assertFalse($result['found']);
        $this->assertSame('bananas', $result['segment']);
        $this->assertSame('bananas', $result['currentPath']);
        $this->assertNull($result['segmentIndex']);

        $result = $method->invokeArgs($instance, array(array('bananas', 'multi', 'segment')));
        $this->assertSame($pagesPath, $result['path']);
        $this->assertNull($result['page']);
        $this->assertFalse($result['found']);
        $this->assertSame('bananas', $result['segment']);
        $this->assertSame('bananas', $result['currentPath']);
        $this->assertNull($result['segmentIndex']);

        $result = $method->invokeArgs($instance, array(array('answers', 'bananas', 'segment')));
        $this->assertSame($pagesPath, $result['path']);
        $this->assertNull($result['page']);
        $this->assertFalse($result['found']);
        $this->assertSame('bananas', $result['segment']);
        $this->assertSame('answers/bananas', $result['currentPath']);
        $this->assertNull($result['segmentIndex']);

        $pageSetPath->setValue($instance, 'mobile');

        $result = $method->invokeArgs($instance, array(array('answers', 'list', 'p', '123')));
        $this->assertSame("{$pagesPath}mobile/answers/list.php", $result['path']);
        $this->assertSame('answers/list', $result['page']);
        $this->assertTrue($result['found']);
        $this->assertSame('list', $result['segment']);
        $this->assertSame('mobile/answers/', $result['currentPath']);
        $this->assertSame(5, $result['segmentIndex']);

        $result = $method->invokeArgs($instance, array(array('answers', 'bananas', 'segment')));
        $this->assertSame($pagesPath, $result['path']);
        $this->assertNull($result['page']);
        $this->assertFalse($result['found']);
        $this->assertSame('bananas', $result['segment']);
        $this->assertSame('mobile/answers/bananas', $result['currentPath']);
        $this->assertNull($result['segmentIndex']);

        //@@@ QA 130305-000119
        $pageSetPath->setValue($instance, 'mobile');
        $pageSetOffset->setValue($instance, 1);
        //The submit_feedback page exists in the basic page set, but because we've set this to be mobile, it won't be found
        $result = $method->invokeArgs($instance, array(array('basic', 'answers', 'submit_feedback')));
        $this->assertFalse($result['found']);
        $this->assertIdentical('mobile/answers/submit_feedback', $result['currentPath']);
        //Tell this method to ignore the page set and treat this path literally
        $result = $method->invokeArgs($instance, array(array('basic', 'answers', 'submit_feedback'), true));
        $this->assertTrue($result['found']);
        $this->assertIdentical('basic/answers/', $result['currentPath']);

        //@@@ QA 130429-000054
        $traversalUrl = 'mobile/..%2f/..%2f/..%2f/..%2f/..%2f/..%2fcore/framework/Views/Admin/deploy/stage';
        $segments = explode('/', $traversalUrl);
        $result = $method->invokeArgs($instance, array($segments));
        $this->assertNull($result['segment']);                 // Was ../core
        $this->assertEqual('mobile/', $result['currentPath']); // Was mobile/..//..//..//..//..//../core

        $traversals = array(
            '../',
            '..%2f',
            '%2e%2e%2f',
            '%00../',
            '%00%2e%2e%2f',
            '..%255c',
            '%2e%2e%255c',
        );

        foreach ($traversals as $traversal) {
            $result = $method->invokeArgs($instance, array(array('mobile', $traversal)));
            if ($result['segment'] !== null || $result['currentPath'] !== 'mobile/') {
                $decoded = urldecode($traversal);
                $this->fail("Traversal not detected: '$traversal' (decoded: '$decoded'");
            }
        }

    }

    function testLoadingCompatibilityController(){

        $this->assertNotNull(TestHelper::makeRequest("/ci/nonExistingController"));

        $controllerCode = <<<'MODEL'
<?php
namespace RightNow\Controllers;
class NonExistingController extends Base{

    function index(){
        echo "from compat layer";
    }
}
MODEL;
        $subDirectoryControllerCode = <<<'MODEL'
<?php
namespace RightNow\Controllers\Admin;
class NonExistingController extends Base{

    function __construct() {
        parent::__construct(true, '_phonyLogin');
    }
    function _phonyLogin() {}

    function index(){
        echo "from compat layer";
    }
}
MODEL;

        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CORE_FILES . "compatibility/Controllers/NonExistingController.php", $controllerCode);
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CORE_FILES . "compatibility/Controllers/Admin/NonExistingController.php", $subDirectoryControllerCode);

        $this->assertIdentical('from compat layer', TestHelper::makeRequest("/ci/nonExistingController"));
        $this->assertIdentical('from compat layer', TestHelper::makeRequest("/ci/admin/nonExistingController"));

        \RightNow\Utils\FileSystem::removeDirectory(CORE_FILES . "compatibility/Controllers/Admin", true);
        @unlink(CORE_FILES . "compatibility/Controllers/NonExistingController.php");
    }

    function testPrePageSetSelectionHook() {
        // Set up the page default set
        $defaultPageSet = $this->CI->model('Pageset')->getPageSetDefaultArray();
        $defaultPageSet['selected'] = 2; // Android - mobile

        $hookName = 'pre_page_set_selection';
        // Set the hook to use our endpoint
        $this->setHook($hookName, $defaultPageSet);
        // hookData is set as the default page set at this point
        $this->assertIdentical($defaultPageSet, self::$hookData);

        // Hook is fired in this function
        $setPageSetMethod = $this->getMethod('_setPageSet');
        $setPageSetMethod();

        // hookData has been set to null because our user agent doesn't match Android or iphone (defaults)
        $this->assertNull(self::$hookData);
    }

    function testPreAllowContactHook() {
        $hookName = 'pre_allow_contact';
        $this->setHook($hookName, array($hookName));
        $this->assertIdentical($hookName, self::$hookData[0]);
        
        $CI = get_instance();
        $currentControllerClass = $CI->router->fetch_class();
        $CI->router->set_class('page');
        $baseInstance = new \RightNow\Controllers\Base();
        $baseInstance->_ensureContactIsAllowed();
        $CI->router->set_class($currentControllerClass);

        $this->assertIsA(self::$hookData, 'array');
        $this->assertTrue(self::$hookData['isContactAllowed']);
        $this->assertEqual(self::$hookData['ifNotAllowed'], 'redirectToLogin');

        //POST requests should cause an exit()..
        $_SERVER['REQUEST_METHOD'] = "POST";
        $baseInstance->_ensureContactIsAllowed();
        $this->assertIsA(self::$hookData, 'array');
        $this->assertTrue(self::$hookData['isContactAllowed']);
        $this->assertEqual(self::$hookData['ifNotAllowed'], 'exit');

        //..unless we're POSTing to the Page controller, then we redirect
        $currentControllerClass = $CI->router->fetch_class();
        $CI->router->set_class('page');
        $baseInstance->_ensureContactIsAllowed();
        $this->assertIsA(self::$hookData, 'array');
        $this->assertTrue(self::$hookData['isContactAllowed']);
        $this->assertEqual(self::$hookData['ifNotAllowed'], 'redirectToLogin');
        $CI->router->set_class($currentControllerClass);
        $_SERVER['REQUEST_METHOD'] = "GET";
    }

    function testPreLoginRedirectHook() {
        $makeRequest = function($hookName) {
            return json_decode(TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
                . urlencode(__FILE__) . "/" . __CLASS__ . "/callLoginRedirect/$hookName"));
        };

        $expectedMessage = "Exiting _loginRedirect before the redirect happens.";

        $hookName = 'pre_login_redirect';
        $output = $makeRequest($hookName);
        $this->assertIsA($output, 'stdClass');
        $this->assertEqual($output->message, $expectedMessage);
        $this->assertNotNull($output->hookData);
        $this->assertIsA($output->hookData->data, 'string');
        $this->assertTrue(Text::stringContains($output->hookData->data, '/app/utils/login_form/redirect'));
    }

    function callLoginRedirect() {
        $hookName = Text::getSubstringAfter($this->CI->uri->uri_string(), 'callLoginRedirect/');
        $this->setHook($hookName, array(), 'loginRedirectHookEndpoint', false);

        $loginRedirectMethod = $this->getMethod('_loginRedirect');
        $loginRedirectMethod();
    }

    function loginRedirectHookEndpoint($data) {
        exit(json_encode(array('message' => 'Exiting _loginRedirect before the redirect happens.',
            'hookData' => $data
        )));
    }

    //@@@ QA 130212-000118
    function testProcessForShitJisTranscodingForBasic() {
        $makeRequest = function($urlData = '', $options = array(), $jsonDecode = true) {
            $response = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
                . urlencode(__FILE__) . "/" . __CLASS__ . "/callProcessForShiftJisTranscodingForBasic$urlData", $options);
            return $jsonDecode ? json_decode($response) : $response;
        };

        $response = $makeRequest();
        $this->assertIdentical($response, array('', '', '', '', (object)array('BaseControllerTest' => 'callProcessForShiftJisTranscodingForBasic')));

        $response = $makeRequest('', array('justHeaders' => true), false);
        $this->assertTrue(Text::stringContains($response, 'Content-Type: text/html; charset=Shift_JIS'));
        $this->assertFalse(Text::stringContains($response, 'Content-Type: text/html; charset=UTF-8'));

        $response = $makeRequest('/test/abc');
        $this->assertIdentical($response, array('/test/abc', 'abc', '', '', (object)array('BaseControllerTest' => 'callProcessForShiftJisTranscodingForBasic', 'test' => 'abc')));

        $response = $makeRequest('/test/abc', array('justHeaders' => true), false);
        $this->assertTrue(Text::stringContains($response, 'Content-Type: text/html; charset=Shift_JIS'));
        $this->assertFalse(Text::stringContains($response, 'Content-Type: text/html; charset=UTF-8'));

        // as expected, URL parameters are not transcoded - we assume them to be escaped when they come in
        $response = $makeRequest('/test/' . urldecode('%89%91'));
        $this->assertIdentical($response, array('/test/%89%91', '%89%91', '', '', (object)array('BaseControllerTest' => 'callProcessForShiftJisTranscodingForBasic', 'test' => '%89%91')));

        $response = $makeRequest('/test/' . urldecode('%89%91'), array('justHeaders' => true), false);
        $this->assertTrue(Text::stringContains($response, 'Content-Type: text/html; charset=Shift_JIS'));
        $this->assertFalse(Text::stringContains($response, 'Content-Type: text/html; charset=UTF-8'));

        // GET and POST data are transcoded - the end user's browser is providing us the SHIFT_JIS code
        // also note that the data from `Url::getParametersFromList` encodes the data for us
        $response = $makeRequest('?test=' . urldecode('%89%91'), array('post' => 'test=' . urldecode('%89%91')));
        $this->assertIdentical($response, array('/test/%E8%8B%91', '', '%E8%8B%91', '%E8%8B%91', (object)array('BaseControllerTest' => 'callProcessForShiftJisTranscodingForBasic')));

        $response = $makeRequest('?test=' . urldecode('%89%91'), array('post' => 'test=' . urldecode('%89%91'), 'justHeaders' => true), false);
        $this->assertTrue(Text::stringContains($response, 'Content-Type: text/html; charset=Shift_JIS'));
        $this->assertFalse(Text::stringContains($response, 'Content-Type: text/html; charset=UTF-8'));
    }

    function callProcessForShiftJisTranscodingForBasic() {
        TestHelper::setConfigValues(array('CP_SHIFT_JIS_PAGE_SETS' => 'basic'));
        $base = new \ReflectionClass('\RightNow\Controllers\Base');
        $instance = $base->newInstance();
        $pageSetPath = $base->getProperty('pageSetPath');
        $pageSetPath->setAccessible(true);
        $pageSetPath->setValue($instance, 'basic');
        $processForShiftJisTranscoding = $base->getMethod('_processForShiftJisTranscoding');
        $processForShiftJisTranscoding->setAccessible(true);
        $this->CI->config->set_item('parm_segment', $this->CI->config->item('parm_segment') + 1);
        $processForShiftJisTranscoding->invoke($instance);
        echo json_encode(array(\RightNow\Utils\Url::getParametersFromList('test'), urlencode(\RightNow\Utils\Url::getParameter('test')), urlencode($_GET['test']), urlencode($_POST['test']), $this->CI->uri->uri_to_assoc($this->CI->config->item('parm_segment'))));
    }

    //@@@ QA 130212-000118
    function testProcessForShitJisTranscodingForStandard() {
        $makeRequest = function($urlData = '', $options = array(), $jsonDecode = true) {
            $response = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
                . urlencode(__FILE__) . "/" . __CLASS__ . "/callProcessForShiftJisTranscodingForStandard$urlData", $options);
            return $jsonDecode ? json_decode($response) : $response;
        };

        $response = $makeRequest();
        $this->assertIdentical($response, array('', '', '', '', (object)array('BaseControllerTest' => 'callProcessForShiftJisTranscodingForStandard')));

        $response = $makeRequest('', array('justHeaders' => true), false);
        $this->assertFalse(Text::stringContains($response, 'Content-Type: text/html; charset=Shift_JIS'));
        $this->assertTrue(Text::stringContains($response, 'Content-Type: text/html; charset=UTF-8'));

        $response = $makeRequest('/test/abc');
        $this->assertIdentical($response, array('/test/abc', 'abc', '', '', (object)array('BaseControllerTest' => 'callProcessForShiftJisTranscodingForStandard', 'test' => 'abc')));

        $response = $makeRequest('/test/abc', array('justHeaders' => true), false);
        $this->assertFalse(Text::stringContains($response, 'Content-Type: text/html; charset=Shift_JIS'));
        $this->assertTrue(Text::stringContains($response, 'Content-Type: text/html; charset=UTF-8'));

        // as expected, URL parameters are not transcoded - we assume them to be escaped when they come in
        $response = $makeRequest('/test/' . urldecode('%89%91'));
        $this->assertIdentical($response, array('/test/%89%91', '%89%91', '', '', (object)array('BaseControllerTest' => 'callProcessForShiftJisTranscodingForStandard', 'test' => '%89%91')));

        $response = $makeRequest('/test/' . urldecode('%89%91'), array('justHeaders' => true), false);
        $this->assertFalse(Text::stringContains($response, 'Content-Type: text/html; charset=Shift_JIS'));
        $this->assertTrue(Text::stringContains($response, 'Content-Type: text/html; charset=UTF-8'));

        // GET and POST data are transcoded - the end user's browser is providing us the SHIFT_JIS code
        // also note that the data from `Url::getParametersFromList` encodes the data for us
        $response = $makeRequest('?test=' . urldecode('%89%91'), array('post' => 'test=' . urldecode('%89%91')));
        $this->assertIdentical($response, array('/test/%89%91', '', '%89%91', '%89%91', (object)array('BaseControllerTest' => 'callProcessForShiftJisTranscodingForStandard')));

        $response = $makeRequest('?test=' . urldecode('%89%91'), array('post' => 'test=' . urldecode('%89%91'), 'justHeaders' => true), false);
        $this->assertFalse(Text::stringContains($response, 'Content-Type: text/html; charset=Shift_JIS'));
        $this->assertTrue(Text::stringContains($response, 'Content-Type: text/html; charset=UTF-8'));
    }

    function callProcessForShiftJisTranscodingForStandard() {
        TestHelper::setConfigValues(array('CP_SHIFT_JIS_PAGE_SETS' => 'basic'));
        $base = new \ReflectionClass('\RightNow\Controllers\Base');
        $instance = $base->newInstance();
        $pageSetPath = $base->getProperty('pageSetPath');
        $pageSetPath->setAccessible(true);
        $pageSetPath->setValue($instance, null);
        $processForShiftJisTranscoding = $base->getMethod('_processForShiftJisTranscoding');
        $processForShiftJisTranscoding->setAccessible(true);
        $this->CI->config->set_item('parm_segment', $this->CI->config->item('parm_segment') + 1);
        $processForShiftJisTranscoding->invoke($instance);
        echo json_encode(array(\RightNow\Utils\Url::getParametersFromList('test'), urlencode(\RightNow\Utils\Url::getParameter('test')), urlencode($_GET['test']), urlencode($_POST['test']), $this->CI->uri->uri_to_assoc($this->CI->config->item('parm_segment'))));
    }

    //@@@ QA 130212-000118
    function testUseShiftJis() {
        $base = new \ReflectionClass('\RightNow\Controllers\Base');
        $instance = $base->newInstance();
        $pageSetPath = $base->getProperty('pageSetPath');
        $pageSetPath->setAccessible(true);
        $method = $base->getMethod('_useShiftJis');
        $method->setAccessible(true);

        TestHelper::setConfigValues(array('CP_SHIFT_JIS_PAGE_SETS' => 'basic'));
        $pageSetPath->setValue($instance, null);
        $this->assertFalse($method->invoke($instance));
        $pageSetPath->setValue($instance, 'basic');
        $this->assertTrue($method->invoke($instance));
        $pageSetPath->setValue($instance, 'garbage');
        $this->assertFalse($method->invoke($instance));

        TestHelper::setConfigValues(array('CP_SHIFT_JIS_PAGE_SETS' => ''));
        $pageSetPath->setValue($instance, null);
        $this->assertFalse($method->invoke($instance));
        $pageSetPath->setValue($instance, 'basic');
        $this->assertFalse($method->invoke($instance));
        $pageSetPath->setValue($instance, 'garbage');
        $this->assertFalse($method->invoke($instance));

        TestHelper::setConfigValues(array('CP_SHIFT_JIS_PAGE_SETS' => 'DEFAULT'));
        $pageSetPath->setValue($instance, null);
        $this->assertTrue($method->invoke($instance));
        $pageSetPath->setValue($instance, 'basic');
        $this->assertFalse($method->invoke($instance));
        $pageSetPath->setValue($instance, 'garbage');
        $this->assertFalse($method->invoke($instance));

        TestHelper::setConfigValues(array('CP_SHIFT_JIS_PAGE_SETS' => '  DEFAULT  ,  basic  '));
        $pageSetPath->setValue($instance, null);
        $this->assertTrue($method->invoke($instance));
        $pageSetPath->setValue($instance, 'basic');
        $this->assertTrue($method->invoke($instance));
        $pageSetPath->setValue($instance, 'garbage');
        $this->assertFalse($method->invoke($instance));
    }

    function testTranscodeArrayOfShiftJisData() {
        $method = $this->getMethod('_transcodeArrayOfShiftJisData', true);

        $input = $expected = array('blah', array('blah1' => 'blah2', 'hey' => 'yah'), array('here'));
        $result = $method($input);
        $this->assertIdentical($result, $expected);

        $input = array(urldecode('%89%91'), array(urldecode('%89%92') => urldecode('%89%93'), urldecode('%89%94') => urldecode('%89%95')), array(urldecode('%89%96')));
        $expected = array('苑', array('薗' => '遠', '鉛' => '鴛'), array('塩'));
        $result = $method($input);
        $this->assertNotIdentical($input, $expected);
        $this->assertIdentical($result, $expected);
    }

    function testIsAjaxRequest() {
        $method = $this->getMethod('isAjaxRequest');

        $this->assertFalse($method());

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'banana';
        $this->assertFalse($method());

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHTTPREQUEST';
        $this->assertTrue($method());

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLhttprequeST';
        $this->assertTrue($method());

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        $this->assertTrue($method());

        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
    }

    function testGetPageFromReferrer() {
        $makeRequestWith = function($value = null) {
            return TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
                . urlencode(__FILE__) . "/" . __CLASS__ . "/callGetPageFromReferrerWithRnt/" . urlencode($value));
        };
        $makeRequestWithout = function($value = null) {
            return TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
                . urlencode(__FILE__) . "/" . __CLASS__ . "/callGetPageFromReferrerWithoutRnt/" . urlencode($value));
        };
        $exitMessage = "The HTTP Referrer is invalid.";
        $server = \RightNow\Utils\Config::getConfig(OE_WEB_SERVER);
        $this->assertIdentical($exitMessage,    $makeRequestWith());
        $this->assertIdentical($exitMessage, $makeRequestWithout());
        $this->assertIdentical($exitMessage,    $makeRequestWith("https://$server/"));
        $this->assertIdentical($exitMessage, $makeRequestWithout("https://$server/"));
        $this->assertIdentical($exitMessage,    $makeRequestWith("https://$server/"));
        $this->assertIdentical($exitMessage, $makeRequestWithout("https://$server/"));
        $this->assertIdentical('home',    $makeRequestWith("http://$server/"));
        $this->assertIdentical('home', $makeRequestWithout("http://$server/"));
        $this->assertIdentical('home',    $makeRequestWith("http://$server/app"));
        $this->assertIdentical('home', $makeRequestWithout("http://$server/app"));
        $this->assertIdentical('home',    $makeRequestWith("http://$server/app/"));
        $this->assertIdentical('home', $makeRequestWithout("http://$server/app/"));
        $this->assertIdentical($exitMessage,    $makeRequestWith("http://$server/app/hom"));
        $this->assertIdentical($exitMessage, $makeRequestWithout("http://$server/app/hom"));
        $this->assertIdentical('home',    $makeRequestWith("http://$server/ci/hom"));
        $this->assertIdentical('home', $makeRequestWithout("http://$server/ci/hom"));
        $this->assertIdentical('home',    $makeRequestWith("http://$server/ci/ajaxRequest/sendForm"));
        $this->assertIdentical('home', $makeRequestWithout("http://$server/ci/ajaxRequest/sendForm"));
        $this->assertIdentical($exitMessage,    $makeRequestWith("http://$server/app/whatever/is/here"));
        $this->assertIdentical($exitMessage, $makeRequestWithout("http://$server/app/whatever/is/here"));
        $this->assertIdentical('answers/list',    $makeRequestWith("http://$server/app/answers/list"));
        $this->assertIdentical('answers/list', $makeRequestWithout("http://$server/app/answers/list"));
    }

    function callGetPageFromReferrerWithRnt() {
        $method = $this->getMethod('getPageFromReferrer');

        $value = urldecode(Text::getSubstringAfter($this->CI->uri->uri_string(), 'callGetPageFromReferrerWithRnt/'));

        if ($value !== '')
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';

        $_SERVER['HTTP_RNT_REFERRER'] = $value;

        echo $method();
    }

    function callGetPageFromReferrerWithoutRnt() {
        $method = $this->getMethod('getPageFromReferrer');

        $value = urldecode(Text::getSubstringAfter($this->CI->uri->uri_string(), 'callGetPageFromReferrerWithoutRnt/'));

        if ($value !== '')
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';

        $_SERVER['HTTP_REFERER'] = $value;

        echo $method();
    }
}
