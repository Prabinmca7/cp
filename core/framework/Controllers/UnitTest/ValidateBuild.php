<?php

namespace RightNow\Controllers\UnitTest;

if (IS_HOSTED) {
    exit('Did we ship the unit tests?  That would be sub-optimal.');
}

require_once("simpletest1.1beta/unit_tester.php");
require_once("simpletest1.1beta/collector.php");
require_once __DIR__ . '/classes/Helper.php';
require_once(__DIR__ . '/classes/CPTestCase.php');
require_once(__DIR__ . '/classes/TestReporter.php');

use RightNow\Utils\Text;


/**
* Controller endpoint to validate the CP build created via the cp/Rakefile.
*/
class ValidateBuild extends \RightNow\Controllers\Admin\Base {

    function __construct() {
        parent::__construct(true, '_verifyLoginWithCPEditPermission');
        $this->testHelper = new \RightNow\UnitTest\Helper;
    }

    public function test() {
        $parameters = get_instance()->uri->uri_to_assoc(4);
        $baseDir = DOCROOT . '/cp/core/util/tarball/tests/';
        list($suite, $files) = $this->testHelper->createSuiteForTestsIn($baseDir, 'buildtest');
        $files = ($files)
            ? array_map(function ($a) use ($baseDir) { return "{$baseDir}/{$a}"; }, $files)
            : array($baseDir);

        // Set the target path so tests have access to it.
        define('VALIDATE_BUILD_CP_PATH', urldecode($parameters['target']));

        $result = $suite->run(\TestReporter::reporter($parameters['reporter'] ?: 'CPHTML', $files, $this->baseUri));
        exit($result ? 0 : 1);
    }
}