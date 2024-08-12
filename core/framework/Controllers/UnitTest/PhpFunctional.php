<?php

namespace RightNow\Controllers\UnitTest;

use RightNow\Api,
    RightNow\Utils\Text;

require_once __DIR__ . '/classes/Helper.php';
require_once __DIR__ . '/classes/Fixture.php';

if (IS_HOSTED) {
    exit('Did we ship the unit tests?  That would be sub-optimal.');
}

class PhpFunctional extends \RightNow\Controllers\Admin\Base {
    public static $metaInformation = array();
    public $sessionCookie;
    public $sessionString;
    public $sessionID;

    protected $testHelper;
    protected $simpletestDir = "simpletest1.1beta/";
    protected $basePath;

    function __construct() {
        parent::__construct(true, '_verifyLoginWithCPEditPermission');

        $this->baseUri = "{$_SERVER['SERVER_NAME']}/ci/unitTest/phpFunctional";
        $this->basePath = null;

        if (!array_key_exists('cp_session', $_COOKIE)) {
            // Set temporary login cookie if not being run from a browser, such as when run in Cruise Control environment.
            $_COOKIE = array('cp_login_start' => 1);
        }

        $this->testHelper = new \RightNow\UnitTest\Helper;

        $session = \RightNow\Libraries\Session::getInstance(true);
        $this->sessionID = Api::generate_session_id();
        $time = time();
        $session->setSessionData(array('sessionID' => $this->sessionID));
        $session->setFlashData(array('filler_garbage' => 'garbage'));
        $urlSafeSessionID = urlencode(Api::ver_ske_encrypt_fast_urlsafe(json_encode(array('session_id' => $this->sessionID))));
        $this->sessionString = '/session/' . base64_encode("/time/$time/sid/" . $urlSafeSessionID);
        $session->setSessionData(array('sessionString' => $this->sessionString));
        $this->sessionCookie = Api::ver_ske_encrypt_fast_urlsafe(json_encode(array('s' => array('s' => $this->sessionID, 'e' => $this->sessionString, 'l' => $time, 'i' => Api::intf_id()))));
    }

    public function index() {
        $this->load->view('tests/phpFunctional');
    }

    /**
     * Runs all .test.php tests
     * under the specified directory or runs a single
     * test if the path to a .test.php file is given
     */
    public function test() {
        $module = $_GET["module"];
        $this->_test(implode('/', func_get_args()), 'test', $module);
    }

    /**
     * Runs all .slowtest.php tests
     * under the specified directory or runs a single
     * test if the path to a .slowtest.php file is given
     */
    public function testSlow() {
        $module = $_GET["module"];
        $this->_test(implode('/', func_get_args()), 'slowtest', $module);
    }

    /**
     * Runs all .test.php and .slowtest.php tests
     * under the specified directory or runs a single
     * test if the path to a .test.php or .slowtest.php file is given
     */
    public function testAll() {
        $this->_test(implode('/', func_get_args()), 'test|slowtest');
    }

    /**
     * Show all PHP tests.
     */
    public function viewTests() {
        echo "<html>\n<head></head>\n<body>\n<br><h3>Available tests</h3>\n<a href='//{$this->baseUri}/all' target='_blank'>run all</a>\n<ul>\n";
        foreach ($this->testHelper->getTestFiles('test|slowtest', $this->basePath) as $path) {
            echo "<li><a href='//{$this->baseUri}/test/$path' target='_blank'>$path</a></li>\n";
        }
        echo "</ul>\n</body>\n</html>";
    }

    /**
     * Run all .test.php tests, each as its own suite.
     */
    public function all() {
        $this->_requireSimpleTestFiles();

        $testFiles = $this->testHelper->getTestFiles('test|slowtest', $this->basePath);

        $this->load->view('tests/testBoilerplates/allPhpFunctional', array(
            'type'  => 'php',
            'tests' => $testFiles,
            'total' => count($testFiles),
        ));
    }

    /**
     * So calls to the page controller's _getMetaInformation don't fail
     */
    function _getMetaInformation() { return self::$metaInformation; }

    protected function _test($baseDir, $fileExtensionPattern, $module = null) {
        //Strip off any specified sub tests from the tested path
        $baseDir = $this->basePath . Text::getSubstringBefore($baseDir, '/subtests/', $baseDir);
        if (preg_match("@reporter/(\w+)@", $baseDir, $matches)) {
            $reporter = $matches[1];
            $baseDir = \RightNow\Utils\Url::deleteParameter(Text::beginsWith($baseDir, '/') ? $baseDir : "/$baseDir", 'reporter');
            $baseDir = Text::beginsWith($baseDir, '/') ? substr($baseDir, 1) : $baseDir;
        }

        $this->_requireSimpleTestFiles();
        list($suite, $files) = $this->testHelper->createSuiteForTestsIn($baseDir, $fileExtensionPattern, $module);
        $files = ($files)
            ? array_map(function ($a) use ($baseDir) { return "{$baseDir}/{$a}"; }, $files)
            : array($baseDir);
        $result = $suite->run(\TestReporter::reporter($reporter ?: 'CPHTML', $files, $this->baseUri));
        exit($result ? 0 : 1);
    }

    protected function _requireSimpleTestFiles() {
        require_once("{$this->simpletestDir}unit_tester.php");
        require_once("{$this->simpletestDir}mock_objects.php");
        require_once("{$this->simpletestDir}collector.php");
        require_once(__DIR__ . '/classes/CPTestCase.php');
        require_once(__DIR__ . '/classes/TestReporter.php');
        require_once(__DIR__ . '/classes/ViewPartialTestCase.php');
    }
}
