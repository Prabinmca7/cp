<?php

use RightNow\Utils\Widgets,
    RightNow\Utils\FileSystem,
    RightNow\Internal\Libraries\Widget\DependencyInfo;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
class DependencyInfoTest extends CPTestCase {
    function __construct() {
        require_once(CPCORE . "Internal/Libraries/Widget/DependencyInfo.php");
    }

    function setUp() {
        DependencyInfo::$setCookie = false;
        parent::setUp();
    }

    function tearDown() {
        DependencyInfo::removeTests();
        parent::tearDown();
    }

    function testSetTest() {
        $this->assertFalse(DependencyInfo::setTest('TestDNE'));
        $this->assertTrue(is_string(DependencyInfo::setTest('Test1.json')));
    }

    function testRemoveTests() {
        $testFilePath = CPCORE . 'Internal/Libraries/Widget/tests/dependencyInfoTest_';
        DependencyInfo::removeTests();
        $testFiles = FileSystem::listDirectory(CPCORE . 'Internal/Libraries/Widget/tests/', true, false, array('match', '/dependencyInfoTest_.*\.json/'));
        $this->assertSame(0, count($testFiles));

        file_put_contents($testFilePath . 'test1.json', 'a');
        file_put_contents($testFilePath . 'test2.json', 'a');
        file_put_contents($testFilePath . 'test1.js', 'a');
        file_put_contents($testFilePath . 'test1json', 'a');

        DependencyInfo::removeTests();
        $testFiles = FileSystem::listDirectory(CPCORE . 'Internal/Libraries/Widget/tests/', true, false, array('match', '/dependencyInfoTest_.*\.json/'));
        $this->assertSame(0, count($testFiles));

        $this->assertTrue(FileSystem::isReadableFile($testFilePath . 'test1.js'));
        $this->assertTrue(FileSystem::isReadableFile($testFilePath . 'test1json'));

        unlink($testFilePath . 'test1.js');
        unlink($testFilePath . 'test1json');
    }

    function testGetTestFile() {
        unset($_COOKIE['version_testing']);
        $this->assertSame('', DependencyInfo::getTestFile());

        $testFile = CPCORE . "Internal/Libraries/Widget/tests/dependencyInfoTest_00000000.json";
        file_put_contents($testFile, "testfile");
        $_COOKIE['version_testing'] = $testFile;
        $this->assertSame($testFile, DependencyInfo::getTestFile());
    }

    function testGetAllFixtures() {
        $path = CPCORE . 'Internal/Libraries/Widget/tests/fixtures/';

        $results = DependencyInfo::getAllFixtures();
        $this->assertIsA($results, 'array');
        $numberOfTests = count($results);
        $this->assertTrue($numberOfTests > 1);

        $invalidFileName = 'banana.json';
        file_put_contents($path . $invalidFileName, 'yeeeeeeaaaaaahhh');
        $results = DependencyInfo::getAllFixtures();
        $this->assertSame($numberOfTests, count($results));

        $validFileName1 = 'Test994.json';
        file_put_contents($path . $validFileName1, null);
        $results = DependencyInfo::getAllFixtures();
        $this->assertSame(++$numberOfTests, count($results));
        $lastTest = end($results);
        $this->assertSame("There's a problem with this file!", $lastTest);


        $validFileName2 = 'Test995.json';
        file_put_contents($path . $validFileName2, json_encode(array('description' => 'banana')));
        $results = DependencyInfo::getAllFixtures();
        $this->assertSame(++$numberOfTests, count($results));
        $lastTest = end($results);
        $this->assertSame("banana", $lastTest);

        unlink($path . $invalidFileName);
        unlink($path . $validFileName1);
        unlink($path . $validFileName2);
    }

    function testIsTesting() {
        unset($_COOKIE['version_testing']);
        $this->assertFalse(DependencyInfo::isTesting());

        $testFile = CPCORE . "Internal/Libraries/Widget/tests/dependencyInfoTest_00000000.json";
        file_put_contents($testFile, '{"testfile": "testing"}');
        $_COOKIE['version_testing'] = $testFile;
        $this->assertTrue(DependencyInfo::isTesting());
    }

    function testGetCXVersionNumber() {
        unset($_COOKIE['version_testing']);
        $this->assertSame(\RightNow\Internal\Utils\Version::getCXVersionNumber(), DependencyInfo::getCXVersionNumber());

        $_COOKIE['version_testing'] = DependencyInfo::setTest('Test2.json');
        $this->assertSame('13.2', DependencyInfo::getCXVersionNumber());
    }

    function testSetCurrentWidgetVersions() {
        $declaredWidgetVersions = array('standard/input/FormInput' => '4.0');
        unset($_COOKIE['version_testing']);
        $this->assertFalse(DependencyInfo::setCurrentWidgetVersions($declaredWidgetVersions));

        $_COOKIE['version_testing'] = DependencyInfo::setTest('Test1.json');
        $this->assertTrue(DependencyInfo::setCurrentWidgetVersions($declaredWidgetVersions));
        $newDeclaredWidgetVersions = DependencyInfo::overrideAllDeclaredWidgetVersions(array());
        $this->assertSame('4.0', $newDeclaredWidgetVersions['Development']['standard/input/FormInput']);
    }

    function testSetCurrentFrameworkVersion()
    {
        $testVersion = "4.99";
        $originalVersions = \RightNow\Internal\Utils\Version::getVersionsInEnvironments('framework');

        unset($_COOKIE['version_testing']);
        $this->assertFalse(DependencyInfo::setCurrentFrameworkVersion($testVersion));

        $_COOKIE['version_testing'] = DependencyInfo::setTest('Test1.json');
        $this->assertTrue(DependencyInfo::setCurrentFrameworkVersion($testVersion));
        $frameworkVersions = DependencyInfo::overrideAllDeclaredFrameworkVersions(array());

        $this->assertEqual($frameworkVersions, array('Development' => '4.99', 'Staging' => '3.0', 'Production' => '3.0'));

        $this->assertTrue(DependencyInfo::setCurrentFrameworkVersion($originalVersions['Development']));
        $resetVersions = DependencyInfo::overrideAllDeclaredFrameworkVersions(array());

        $this->assertEqual($frameworkVersions, array('Development' => '4.99', 'Staging' => '3.0', 'Production' => '3.0'));
    }

    function testOverrideAllDeclaredFrameworkVersions() {
        $expected = array(
            'banana', 'yeeeeeeaaaaaahhh'
        );
        unset($_COOKIE['version_testing']);
        $actual = DependencyInfo::overrideAllDeclaredFrameworkVersions($expected);
        $this->assertIdentical($expected, $actual);

        $_COOKIE['version_testing'] = DependencyInfo::setTest('Test1.json');

        $expected = array(
            'Production' => '3.0',
            'Staging' => '3.0',
            'Development' => '3.0',
        );
        $actual = DependencyInfo::overrideAllDeclaredFrameworkVersions(array(
            'Production' => 'banana',
            'Staging' => 'yeeeeeeaaaaaahhh',
            'Development' => 'yaz_addinfo',
        ));
        $this->assertIdentical($expected, $actual);
    }

    function testOverrideAllDeclaredWidgetVersions() {
        $declaredWidgetVersions = array(
            'Production' => array('standard/input/FormInput' => 'current', 'monkeys' => 'abc'),
            'Staging' => array('standard/input/FormInput' => 'current'),
            'Development' => array('standard/input/FormInput' => 'current'),
        );
        unset($_COOKIE['version_testing']);
        $result = DependencyInfo::overrideAllDeclaredWidgetVersions($declaredWidgetVersions);
        $this->assertSame('abc', $result['Production']['monkeys']);
        $this->assertSame('current', $result['Production']['standard/input/FormInput']);
        $this->assertSame('current', $result['Staging']['standard/input/FormInput']);
        $this->assertSame('current', $result['Development']['standard/input/FormInput']);

        $_COOKIE['version_testing'] = DependencyInfo::setTest('Test1.json');
        $result = DependencyInfo::overrideAllDeclaredWidgetVersions($declaredWidgetVersions);
        $this->assertSame('1.0', $result['Production']['monkeys']);
        $this->assertSame('1.0', $result['Production']['standard/input/FormInput']);
        $this->assertSame('1.1', $result['Staging']['standard/input/FormInput']);
        $this->assertSame('1.1', $result['Development']['standard/input/FormInput']);
    }

    function testOverrideWidgetInfo() {
        $widgetInfo = array('requires' => array('framework' => array('3.0')));
        unset($_COOKIE['version_testing']);
        $this->assertIdentical($widgetInfo, DependencyInfo::overrideWidgetInfo('standard/input/TextInput', $widgetInfo));

        $_COOKIE['version_testing'] = DependencyInfo::setTest('Test1.json');
        $newWidgetInfo = DependencyInfo::overrideWidgetInfo('standard/input/TextInput', $widgetInfo);
        $this->assertIdentical(array('3.1'), $newWidgetInfo['requires']['framework']);
        $this->assertIdentical(array('placeholder-1.1.2'), $newWidgetInfo['requires']['yui']);
    }

    function testOverrideDeclaredWidgetVersions() {
        $declaredWidgetVersions = array(
            'standard/input/TextInput' => 'current',
            'standard/input/FormInput' => 'current'
        );
        unset($_COOKIE['version_testing']);
        $this->assertIdentical($declaredWidgetVersions, DependencyInfo::overrideDeclaredWidgetVersions($declaredWidgetVersions));

        $_COOKIE['version_testing'] = DependencyInfo::setTest('Test1.json');
        $newDeclaredWidgetVersions = DependencyInfo::overrideDeclaredWidgetVersions($declaredWidgetVersions);
        $this->assertIdentical(array('standard/input/TextInput' => '1.1', 'standard/input/FormInput' => '1.1'), $newDeclaredWidgetVersions);
    }

    function testOverrideVersionHistory() {
        $versionHistory = array(
            'widgetVersions' => array('standard/input/TextInput' => array('1.0.1' => array())),
            'frameworkVersions' => array('12.5' => '3.0.1')
        );
        unset($_COOKIE['version_testing']);
        $this->assertIdentical($versionHistory, DependencyInfo::overrideVersionHistory($versionHistory));

        $_COOKIE['version_testing'] = DependencyInfo::setTest('Test1.json');
        $newVersionHistory = DependencyInfo::overrideVersionHistory($versionHistory);
        $this->assertIdentical(array('1.0.1', '1.0.2', '1.1.1'), array_keys($newVersionHistory['widgetVersions']['standard/input/TextInput']));
        $this->assertIdentical(array('12.5' => '3.0.1', '12.8' => '3.0.1', '12.11' => '3.0.1', '13.2' => '3.1.1', '13.5' => '3.1.1'), $newVersionHistory['frameworkVersions']);
    }

    function testFillInBaseMockData() {
        $method = \RightNow\UnitTest\Helper::getMethodInvoker('\RightNow\Internal\Libraries\Widget\DependencyInfo', 'fillInBaseMockData');
        $data = array('all' => 'foo');

        $this->assertIdentical(array(), $method($data, null));

        $input = array();
        $result = $method($data, $input);
        $this->assertIdentical($input, $result);

        $input = array('foobar' => 'foobar');
        $result = $method($data, $input);
        $this->assertIdentical(array('foobar' => 'foo'), $result);

        $data = array('all' => array('com' => array('ca' => 'ted')));
        $input = array('foobar' => 'foobar', 'banana' => array('nanana'));
        $result = $method($data, $input);
        $this->assertIdentical(array('foobar' => array('com' => array('ca' => 'ted')), 'banana' => array('com' => array('ca' => 'ted'))), $result);
    }
}
