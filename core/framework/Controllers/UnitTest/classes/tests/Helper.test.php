<?php

use RightNow\Utils\Text;

class UnitTestHelperTest extends CPTestCase {
    public $testingClass = 'RightNow\UnitTest\Helper';

    function __construct() {
        parent::__construct();
    }

    function testTranslatePath() {
        $translatePath = $this->getMethod('translatePath');
        $scriptsDir = DOCROOT;
        $paths = array(
            array('Utils', "$scriptsDir/cp/core/framework/Utils", true),
            array("$scriptsDir/cp/core/framework/Utils", "$scriptsDir/cp/core/framework/Utils", true),
            array('Utils/tests', "$scriptsDir/cp/core/framework/Utils/tests", true),
            array('core_util/', "$scriptsDir/cp/core/util//", true),
            array('bootstrap/', "$scriptsDir/bootstrap/", true),
            array('Controllers', "$scriptsDir/cp/core/framework/Controllers", true),
            array('Controllers/Admin/tests/Tools.test.php', "$scriptsDir/cp/core/framework/Controllers/Admin/tests/Tools.test.php", false),
            array("$scriptsDir/cp/core/compatibility/Internal", "$scriptsDir/cp/core/compatibility/Internal", true),
            array("$scriptsDir/cp/core/compatibility/Mappings", "$scriptsDir/cp/core/compatibility/Mappings", true),
            array("$scriptsDir/cp/core/compatibility/Internal/Sql", "$scriptsDir/cp/core/compatibility/Internal/Sql", true),
        );
        foreach ($paths as $triple) {
            list($path, $fullPath, $isDir) = $triple;
            $result = $translatePath($path);
            $this->assertIdentical($fullPath, $result[1], "'$path' returned '{$result[1]}', Expected: '$fullPath'.");
            $this->assertIdentical($isDir, $result[0]);
        }
    }

    // function testGetTestFiles() {
    //     $scriptsDir = DOCROOT;
    //     $getTestFiles = $this->getMethod('getTestFiles');
    //     $results = $getTestFiles();
    //     $this->assertIsA($results, 'array');
    //     $this->assertNotEqual(0, count($results));
    // }

    function testCreateSuiteForTestsIn() {
        $scriptsDir = DOCROOT;
        $createSuite = $this->getMethod('createSuiteForTestsIn');
        $results = $createSuite($scripts, 'test|slowtest');
        $this->assertIsA($results, 'array');
        list($tests, $files) = $results;
        $this->assertIsA($tests, TestSuite);
        $this->assertIsA($files, 'array');

        $paths = array(
            CPCORE . '/Utils',
            'Controllers',
            'core_util/',
            'core_util/tests/dynamicJavaScript.test.php',
            'bootstrap/',
            "$scriptsDir/cp/core/compatibility/Internal",
        );
        foreach($paths as $path) {
            $results = $createSuite($path, 'test|slowtest');
            list($tests, $files) = $results;
            $this->assertIsA($tests, TestSuite);
            $this->assertTrue(count($tests) > 0);
        }

        try {
            $results = $createSuite(CPCORE . 'some/Directory', 'test');
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }
    }

    function testTrimLines() {
        $createSuite = $this->getMethod('trimLines');

        $stringToTest = <<<STRT
	Oh man, this line starts with a tab
 Oh boy, this line starts with a space
Oh noes, this line ends with a tab and a line!
This line is ok.    But it has a tab in the middle.
This line ends with two tabs.
STRT;

        $expectedResult = <<<EXPR
Oh man, this line starts with a tab
Oh boy, this line starts with a space
Oh noes, this line ends with a tab and a line!
This line is ok.    But it has a tab in the middle.
This line ends with two tabs.
EXPR;

        $this->assertEqual($expectedResult, $createSuite($stringToTest));
    }

    function testPostArrayToParams() {
        $postArrayToParams = $this->getMethod('postArrayToParams');
        $post = array(
            'w_id' => 123,
            'rate' => 0,
            'f_tok' => 'abc123!!',
            'message' => 'The operation was completed successfully',
            'email' => 'foo@you.nill',
        );
        $actual = $postArrayToParams($post);
        $expected = 'w_id=123&rate=0&f_tok=abc123%21%21&message=The+operation+was+completed+successfully&email=foo%40you.nill';
        $this->assertIdentical($expected, $actual);
    }
}