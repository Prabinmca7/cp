<?php

 use RightNow\Utils\Widgets,
     RightNow\Internal\Utils\VersionTracking,
     RightNow\Utils\FileSystem,
     RightNow\Api,
     RightNow\Internal\Utils\Version;

\RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class VersionTrackingTest extends CPTestCase {
    public $testingClass = '\RightNow\Internal\Utils\VersionTracking';

    function __construct() {
        $this->accountID = get_instance()->_getAgentAccount()->acct_id;
        $this->dir = sprintf("%s/unitTest/%s", get_cfg_var('upload_tmp_dir'), get_class($this));
        $this->auditLogPath = CUSTOMER_FILES . 'versionAuditLog';
        $this->reportID = VersionTracking::$reports['versions'];
        VersionTracking::initializeCache();
    }

    function setUp() {
        $this->resetAuditLog();
    }

    function resetAuditLog() {
        FileSystem::filePutContentsOrThrowExceptionOnFailure($this->auditLogPath, '');
    }

    function checkAuditLog($expected) {
        $contents = file_get_contents($this->auditLogPath);
        return \RightNow\Utils\Text::beginsWith($contents, $expected);
    }

    //@@@ QA 130124-000096 Framework and widget version tracking
    function testLog() {
        $data = array('name' => 'standard/input/TextInput', 'from' => '1.0.1', 'to' => '1.1.1');
        $results = VersionTracking::log($data);
        $this->assertTrue($this->checkAuditLog("{$this->accountID},standard/input/TextInput,1.1.1,1.0.1"));

        $this->resetAuditLog();
        $results = VersionTracking::log($data, 'all');
        $this->assertTrue($this->checkAuditLog("{$this->accountID},standard/input/TextInput,1.1.1,1.0.1"));

        try {
            VersionTracking::log($data, 'foo');
            $this->fail('Expected invalid targets exception');
        }
        catch (\Exception $e) {
            $this->assertEqual($e->getMessage(), "Targets must be either the string 'all' or an array");
        }

        try {
            VersionTracking::log($data, array('foo'));
            $this->fail('Expected invalid target exception');
        }
        catch (\Exception $e) {
            $this->assertEqual($e->getMessage(), "Not a recognized target: 'foo'");
        }
    }

    //@@@ QA 130124-000096 Framework and widget version tracking
    function testLogToAudit() {
        $contents = file_get_contents($this->auditLogPath);

        $expected = "{$this->accountID},framework,3.1.1,3.0.1";
        $actual = VersionTracking::logToAudit(array('name' => 'framework', 'from' => '3.0.1', 'to' => '3.1.1'));
        $this->assertTrue($this->checkAuditLog($expected));
        $this->assertTrue($this->checkAuditLog($actual));

        $expected = "{$this->accountID},standard/input/TextInput,1.1.1,1.0.1";
        $actual = VersionTracking::logToAudit(array('name' => 'standard/input/TextInput', 'from' => '1.0.1', 'to' => '1.1.1'));
        $this->assertTrue($this->checkAuditLog($expected));
        $this->assertTrue($this->checkAuditLog($actual));
    }

    //@@@ QA 130124-000096 Framework and widget version tracking
    function testLogToDatabase() {
        $name = 'framework';
        $type = CP_OBJECT_TYPE_FRAMEWORK;
        $row = $this->fetch($type, $name);
        $oldVersion = $row['development'] ?: '0.0.0';
        list($major, $minor, $nano) = explode('.', $oldVersion);
        $nano = $nano ?: 0;
        $newVersion = (intval($major) + 1) . ".$minor.$nano";

        $result = VersionTracking::logToDatabase(array('name' => $name, 'to' => $newVersion, 'mode' => 'development'));
        $this->assertIsA($result, 'array');
        $this->assertIsA($result['id'], 'integer');
        $this->assertEqual($newVersion, $result['development']);
        $row = $this->fetch($type, $name);
        $this->assertEqual($result['id'], $row['id']);
        $this->assertEqual($newVersion, $row['development']);

        // Restore state
        VersionTracking::logToDatabase(array('name' => $name, 'to' => $oldVersion));
        $row = $this->fetch($type, $name);
        $this->assertEqual($oldVersion, $row['development']);
    }

    /** Fetch rows from the cp_object tables via the canned report */
    function fetch($type, $name) {
        $getReportEntries = $this->getMethod('getReportEntries', true);

        $rows = $getReportEntries($this->reportID, array(), true);

        $getModeMap = $this->getMethod('modeDefines', true);
        $modeMap = $getModeMap(null, true);
        $results = array('id' => null, 'development' => null, 'staging' => null, 'production' => null);
        foreach($rows as $row) {
            list($objectID, $objectType, $objectMode, $objectName, $version) = $row;
            if ($type === $objectType && $name === $objectName) {
                $results['id'] = $objectID;
                $results[$modeMap[$objectMode]] = $row[4];
            }
        }
        return $results;

    }

    function testLookup() {
        $lookup = $this->getMethod('lookup');
        $items = array('key1' => 1, 'key2' => 2, 'key3' => 3);
        $this->assertIdentical($items, $lookup(null, false, $items));
        $this->assertIdentical(array_flip($items), $lookup(null, true, $items));
        $this->assertEqual(1, $lookup('key1', false, $items));
        $this->assertEqual('key1', $lookup(1, true, $items));
    }

    function testGetVersionsFromDatabase() {
        $method = $this->getMethod('getVersionsFromDatabase');
        $result = $method('framework', 'development', 'framework');
        $this->assertIsA($result, 'array');
        $this->assertTrue(array_key_exists('id', $result));
        $this->assertTrue(array_key_exists('development', $result));
        $this->assertTrue(array_key_exists('staging', $result));
        $this->assertTrue(array_key_exists('production', $result));
    }

    //@@@  QA 130311-000092
    function testLogToAcs() {
        // widget versionUp
        $dataIn  = array('from' => '1.0.1', 'to' => '1.1.1', 'name' => 'standard/input/TextInput');
        $dataOut = array('from' => '1.0.1', 'to' => '1.1.1', 'mode' => 'development', 'type' => 'standard', 'name' => 'input/TextInput');
        $expected = array('widget', 'versionUp', json_encode($dataOut));
        $actual = VersionTracking::logToACS($dataIn);
        $this->assertIdentical($expected, $actual);

        // widget versionUp, 'development' mode
        $dataIn  = array('from' => '1.0.1', 'to' => '1.1.1', 'mode' => 'development', 'name' => 'standard/input/TextInput');
        $dataOut = array('from' => '1.0.1', 'to' => '1.1.1', 'mode' => 'development', 'type' => 'standard', 'name' => 'input/TextInput');
        $expected = array('widget', 'versionUp', json_encode($dataOut));
        $actual = VersionTracking::logToACS($dataIn);
        $this->assertIdentical($expected, $actual);

        // widget versionUp, 'production' mode
        $dataIn  = array('from' => '1.0.1', 'to' => '1.1.1', 'name' => 'standard/input/TextInput', 'mode' => 'production');
        $dataOut = array('from' => '1.0.1', 'to' => '1.1.1', 'mode' => 'production', 'type' => 'standard', 'name' => 'input/TextInput');
        $expected = array('widget', 'versionUp', json_encode($dataOut));
        $actual = VersionTracking::logToACS($dataIn);
        $this->assertIdentical($expected, $actual);

        // widget versionUp, no 'from' version specified
        $dataIn  = array('to' => '1.1.1', 'name' => 'standard/input/TextInput');
        $dataOut = array('from' => null, 'to' => '1.1.1', 'mode' => 'development', 'type' => 'standard', 'name' => 'input/TextInput');
        $expected = array('widget', 'versionUp', json_encode($dataOut));
        $actual = VersionTracking::logToACS($dataIn);
        $this->assertIdentical($expected, $actual);

        // widget versionDown
        $dataIn  = array('from' => '1.1.1', 'to' => '1.0.1', 'name' => 'standard/input/TextInput');
        $dataOut = array('from' => '1.1.1', 'to' => '1.0.1', 'mode' => 'development', 'type' => 'standard', 'name' => 'input/TextInput');
        $expected = array('widget', 'versionDown', json_encode($dataOut));
        $actual = VersionTracking::logToACS($dataIn);
        $this->assertIdentical($expected, $actual);

        // widget versionDown, 'to' version null
        $dataIn  = array('from' => '1.1.1', 'to' => null, 'name' => 'standard/input/TextInput');
        $dataOut = array('from' => '1.1.1', 'to' => null, 'mode' => 'development', 'type' => 'standard', 'name' => 'input/TextInput');
        $expected = array('widget', 'versionDown', json_encode($dataOut));
        $actual = VersionTracking::logToACS($dataIn);
        $this->assertIdentical($expected, $actual);

        // widget no change
        $this->assertNull(VersionTracking::logToACS(array('from' => '1.1.1', 'to' => '1.1.1', 'name' => 'standard/input/TextInput')));
        $this->assertNull(VersionTracking::logToACS(array('name' => 'standard/input/TextInput')));

        $msg = "Widget names must begin with 'standard/' or 'custom/'";
        try {
            VersionTracking::logToACS(array('from' => '1.0.1', 'to' => '1.1.1', 'name' => 'input/TextInput'));
            $this->fail("Expected exception: $msg");
        }
        catch (\Exception $e) {
            $this->assertEqual($msg, $e->getMessage());
        }

        // framework versionUp
        $dataIn  = array('from' => '3.0.1', 'to' => '3.1.1', 'name' => 'framework');
        $dataOut = array('from' => '3.0.1', 'to' => '3.1.1', 'mode' => 'development');
        $expected = array('framework', 'versionUp', json_encode($dataOut));
        $actual = VersionTracking::logToACS($dataIn);
        $this->assertIdentical($expected, $actual);

        // framework versionUp, 'development' mode
        $dataIn  = array('from' => '3.0.1', 'to' => '3.1.1', 'mode' => 'development', 'name' => 'framework');
        $dataOut = array('from' => '3.0.1', 'to' => '3.1.1', 'mode' => 'development');
        $expected = array('framework', 'versionUp', json_encode($dataOut));
        $actual = VersionTracking::logToACS($dataIn);
        $this->assertIdentical($expected, $actual);

        // framework versionUp, 'production' mode
        $dataIn  = array('from' => '3.0.1', 'to' => '3.1.1', 'mode' => 'production', 'name' => 'framework');
        $dataOut = array('from' => '3.0.1', 'to' => '3.1.1', 'mode' => 'production');
        $expected = array('framework', 'versionUp', json_encode($dataOut));
        $actual = VersionTracking::logToACS($dataIn);
        $this->assertIdentical($expected, $actual);

        // framework versionUp, no 'from' version specified
        $dataIn  = array('to' => '3.1.1', 'name' => 'framework');
        $dataOut = array('from' => null, 'to' => '3.1.1', 'mode' => 'development');
        $expected = array('framework', 'versionUp', json_encode($dataOut));
        $actual = VersionTracking::logToACS($dataIn);
        $this->assertIdentical($expected, $actual);

        // framework versionDown
        $dataIn  = array('from' => '3.1.1', 'to' => '3.0.1', 'name' => 'framework');
        $dataOut = array('from' => '3.1.1', 'to' => '3.0.1', 'mode' => 'development');
        $expected = array('framework', 'versionDown', json_encode($dataOut));
        $actual = VersionTracking::logToACS($dataIn);
        $this->assertIdentical($expected, $actual);

        // framework versionDown, 'to' version null
        $dataIn  = array('from' => '3.1.1', 'to' => null, 'name' => 'framework');
        $dataOut = array('from' => '3.1.1', 'to' => null, 'mode' => 'development');
        $expected = array('framework', 'versionDown', json_encode($dataOut));
        $actual = VersionTracking::logToACS($dataIn);
        $this->assertIdentical($expected, $actual);

        // framework no change
        $this->assertNull(VersionTracking::logToACS(array('from' => '3.1.1', 'to' => '3.1.1', 'name' => 'framework')));
        $this->assertNull(VersionTracking::logToACS(array('name' => 'framework')));
    }

    //@@@ QA 130124-000096 Framework and widget version tracking
    function testGetAndRecordVersionChanges() {
        $fromPath = $this->dir . '/from/';
        $fromWidgetPath = "{$fromPath}widgetVersions";
        $fromFrameworkPath = "{$fromPath}frameworkVersion";

        $toPath = $this->dir . '/to/';
        $toWidgetPath = "{$toPath}widgetVersions";
        $toFrameworkPath = "{$toPath}frameworkVersion";

        umask(0);
        FileSystem::mkdirOrThrowExceptionOnFailure($fromPath, true);
        FileSystem::mkdirOrThrowExceptionOnFailure($toPath);

        $fromVersions = array(
            'standard/input/TextInput' => '1.0', // Stays the same
            'standard/input/DateInput' => '1.0', // 1.0 -> 1.1
            'custom/chat/ChatAgentStatus' => '1.0', // Goes away
        );

        $toVersions = array(
            'standard/input/TextInput' => '1.0', // Stays the same
            'standard/input/DateInput' => '1.1', // 1.0 -> 1.1
            'custom/chat/ChatDisconnectButton' => '1.0', // New
        );

        Version::writeVersionFile($fromWidgetPath, $fromVersions, 'php');
        Version::writeVersionFile($fromFrameworkPath, '3.0');

        Version::writeVersionFile($toWidgetPath, $toVersions, 'php');
        Version::writeVersionFile($toFrameworkPath, '3.1');

        $expected = array(
          'framework' => array('3.0', '3.1'),
          'standard/input/DateInput' => array('1.0', '1.1'),
          'custom/chat/ChatAgentStatus' => array('1.0', NULL),
          'custom/chat/ChatDisconnectButton' => array(NULL, '1.0'),
        );

        $this->assertIdentical($expected, VersionTracking::getVersionChanges($fromPath, $toPath));
        $this->assertIdentical($expected, VersionTracking::recordVersionChanges($fromPath, $toPath));

        // From versions empty (v2 -> v3 migration)
        Widgets::killCacheVariables();
        Version::writeVersionFile($fromWidgetPath, array(), 'php');
        Version::writeVersionFile($fromFrameworkPath, null);

        $toVersions = array(
            'standard/input/TextInput' => '1.0',
            'standard/input/DateInput' => '1.0',
            'custom/chat/ChatAgentStatus' => '1.0',
            'custom/chat/ChatDisconnectButton' => '1.0',
        );
        Version::writeVersionFile($toWidgetPath, $toVersions, 'php');
        Version::writeVersionFile($toFrameworkPath, '3.0');

        $expected = array(
          'framework' => array(null, '3.0'),
          'standard/input/TextInput' => array(null, '1.0'),
          'standard/input/DateInput' => array(null, '1.0'),
          'custom/chat/ChatAgentStatus' => array(null, '1.0'),
          'custom/chat/ChatDisconnectButton' => array(NULL, '1.0'),
        );
        $this->assertIdentical($expected, VersionTracking::getVersionChanges($fromPath, $toPath));
        $this->assertIdentical($expected, VersionTracking::getVersionChanges(null, $toPath));
        $this->assertIdentical($expected, VersionTracking::recordVersionChanges($fromPath, $toPath));
        $this->assertIdentical($expected, VersionTracking::recordVersionChanges(null, $toPath));

        // v2 -> v3 migration where frameworkVersion is 2.0 -> 3.1, but widgetVersions is identical (all widgets 1.0).
        // In this case the 'from' widgetVersions should be treated as all nulls.
        Widgets::killCacheVariables();

        $fromVersions = $toVersions = array(
            'standard/input/TextInput' => '1.0',
            'standard/input/DateInput' => '1.0',
            'custom/chat/ChatAgentStatus' => '1.0',
            'custom/chat/ChatDisconnectButton' => '1.0',
        );

        Version::writeVersionFile($fromWidgetPath, $fromVersions, 'php');
        Version::writeVersionFile($fromFrameworkPath, '2.0');

        Version::writeVersionFile($toWidgetPath, $toVersions, 'php');
        Version::writeVersionFile($toFrameworkPath, '3.1');

        $expected = array(
            'framework' => array('2.0', '3.1'),
            'standard/input/TextInput' => array(null, '1.0'),
            'standard/input/DateInput' => array(null, '1.0'),
            'custom/chat/ChatAgentStatus' => array(null, '1.0'),
            'custom/chat/ChatDisconnectButton' => array(NULL, '1.0'),
        );
        $this->assertIdentical($expected, VersionTracking::getVersionChanges($fromPath, $toPath));

        // v2 -> v3 migration where frameworkVersion is null -> 3.1, but widgetVersions is identical (all widgets 1.0).
        // In this case the 'from' widgetVersions should be treated as all nulls.
        Widgets::killCacheVariables();

        $fromVersions = $toVersions = array(
            'standard/input/TextInput' => '1.0',
            'standard/input/DateInput' => '1.0',
            'custom/chat/ChatAgentStatus' => '1.0',
            'custom/chat/ChatDisconnectButton' => '1.0',
        );

        Version::writeVersionFile($fromWidgetPath, $fromVersions, 'php');
        Version::writeVersionFile($fromFrameworkPath, null);

        Version::writeVersionFile($toWidgetPath, $toVersions, 'php');
        Version::writeVersionFile($toFrameworkPath, '3.1');

        $expected = array(
            'framework' => array(null, '3.1'),
            'standard/input/TextInput' => array(null, '1.0'),
            'standard/input/DateInput' => array(null, '1.0'),
            'custom/chat/ChatAgentStatus' => array(null, '1.0'),
            'custom/chat/ChatDisconnectButton' => array(NULL, '1.0'),
        );
        $this->assertIdentical($expected, VersionTracking::getVersionChanges($fromPath, $toPath));

        // From versions empty (v3 -> v2 migration)
        Widgets::killCacheVariables();

        $fromVersions = array(
            'standard/input/TextInput' => '1.0',
            'standard/input/DateInput' => '1.0',
            'custom/chat/ChatAgentStatus' => '1.0',
            'custom/chat/ChatDisconnectButton' => '1.0',
        );
        Version::writeVersionFile($fromWidgetPath, $fromVersions, 'php');
        Version::writeVersionFile($fromFrameworkPath, '3.0');

        Version::writeVersionFile($toWidgetPath, array(), 'php');
        Version::writeVersionFile($toFrameworkPath, null);

        $expected = array(
          'framework' => array('3.0', null),
          'standard/input/TextInput' => array('1.0', null),
          'standard/input/DateInput' => array('1.0', null),
          'custom/chat/ChatAgentStatus' => array('1.0', null),
          'custom/chat/ChatDisconnectButton' => array('1.0', null),
        );
        $this->assertIdentical($expected, VersionTracking::getVersionChanges($fromPath, $toPath));
        $this->assertIdentical($expected, VersionTracking::getVersionChanges($fromPath, null));
        $this->assertIdentical($expected, VersionTracking::recordVersionChanges($fromPath, $toPath));
        $this->assertIdentical($expected, VersionTracking::recordVersionChanges($fromPath, null));
    }

    function removeAllEntries() {
        $getReportEntries = $this->getMethod('getReportEntries');
        $destroyed = array();
        foreach ($getReportEntries($this->reportID) as $entry) {
            $id = $entry[0];
            if (!$destroyed[$id]) {
                Api::cp_object_destroy(array('cp_object_id' => $id));
                $destroyed[$id] = true;
            }
       }
       VersionTracking::initializeCache();
    }

    //@@@  QA 130829-000059
    function testVersionsPopulatedForMode() {
        $this->removeAllEntries();
        $this->assertFalse(VersionTracking::versionsPopulatedForMode('development'));
        $this->assertFalse(VersionTracking::versionsPopulatedForMode('staging'));
        $this->assertFalse(VersionTracking::versionsPopulatedForMode('production'));

        VersionTracking::logToDatabase(array('name' => 'framework', 'to' => '3.1', 'mode' => 'development'));
        $this->assertTrue(VersionTracking::versionsPopulatedForMode('development'));

        VersionTracking::logToDatabase(array('name' => 'framework', 'to' => '3.1', 'mode' => 'staging'));
        $this->assertTrue(VersionTracking::versionsPopulatedForMode('staging'));

        VersionTracking::logToDatabase(array('name' => 'framework', 'to' => '3.1', 'mode' => 'production'));
        $this->assertTrue(VersionTracking::versionsPopulatedForMode('production'));

        $expected =  sprintf(\RightNow\Utils\Config::getMessage(INVALID_MODE_PCT_S_COLON_LBL), 'someInvalidMode');
        try {
            VersionTracking::versionsPopulatedForMode('someInvalidMode');
            $this->fail("Expected exception: $expected");
        }
        catch (\Exception $e) {
            $this->assertEqual($expected, $e->getMessage());
        }
    }

    //@@@  QA 130311-000092
    function testGetTypeAndName() {
        $method = $this->getMethod('getTypeAndName', true);
        $this->assertIdentical(array('framework', 'framework', null), $method('framework'));
        $this->assertIdentical(array('widget', 'input/TextInput', 'standard'), $method('standard/input/TextInput'));
        $this->assertIdentical(array('widget', 'input/TextInputCustom', 'custom'), $method('custom/input/TextInputCustom'));

        $msg = "Widget names must begin with 'standard/' or 'custom/'";
        try {
            $method('input/TextInput');
            $this->fail("Expected exception: $msg");
        }
        catch (\Exception $e) {
            $this->assertEqual($msg, $e->getMessage());
        }
    }

    function testGetReportEntries() {
        $getReportEntries = $this->getMethod('getReportEntries');
        $results = $getReportEntries($this->reportID);
        $this->assertIsA($results,'array');
    }

    function getWidgetName($type = 'custom', $name = 'search/MegaSearch') {
        return "$type/$name" . microtime(true);
    }

    function testVersionCache() {
        $method = $this->getMethod('versionCache');
        $result = $method('framework', 'framework');
        $this->assertIsA($result, 'array');
        $this->assertEqual(4, count(array_keys($result)));
        $this->assertTrue(array_key_exists('id', $result));
        $this->assertTrue(array_key_exists('development', $result));
        $this->assertTrue(array_key_exists('staging', $result));
        $this->assertTrue(array_key_exists('production', $result));

        // Initial call returns array having all keys as null.
        $widgetName = $this->getWidgetName();
        $result = $method('custom', $widgetName);
        $this->assertIsA($result, 'array');
        $this->assertNull($result['id']);
        $this->assertNull($result['development']);
        $this->assertNull($result['staging']);
        $this->assertNull($result['production']);

        // Set ID
        $result = $method('custom', $widgetName, 123);
        $this->assertIsA($result, 'array');
        $this->assertEqual(123, $result['id']);
        $this->assertNull($result['development']);
        $this->assertNull($result['staging']);
        $this->assertNull($result['production']);

        // Set ID and development version
        $result = $method('custom', $widgetName, 123, 'development', '3.1');
        $this->assertIsA($result, 'array');
        $this->assertEqual(123, $result['id']);
        $this->assertEqual('3.1', $result['development']);
        $this->assertNull($result['staging']);
        $this->assertNull($result['production']);

        // Retrieve values set above
        $result = $method('custom', $widgetName);
        $this->assertIsA($result, 'array');
        $this->assertEqual(123, $result['id']);
        $this->assertEqual('3.1', $result['development']);
        $this->assertNull($result['staging']);
        $this->assertNull($result['production']);

        // Invalid args
        try {
            $method('SomeInvalidType', $widgetName);
            $this->fail('Expected an exception');
        }
        catch (\Exception $e) {
            $this->assertEqual("Invalid type: 'SomeInvalidType'", $e->getMessage());
        }

        try {
            $method('custom', $widgetName, 'SomeInvalidID');
            $this->fail('Expected an exception');
        }
        catch (\Exception $e) {
            $this->assertEqual("Invalid id: '0'", $e->getMessage());
        }

        try {
            $method('custom', $widgetName, 123, 'SomeInvalidMode');
            $this->fail('Expected an exception');
        }
        catch (\Exception $e) {
            $this->assertEqual("Invalid mode: 'SomeInvalidMode'", $e->getMessage());
        }

        try {
            $method('custom', $widgetName, 123, 'development');
            $this->fail('Expected an exception');
        }
        catch (\Exception $e) {
            $this->assertEqual('Both mode and version must be provided when either are specified.', $e->getMessage());
        }

        try {
            $method('custom', $widgetName, 123, null, '3.1');
            $this->fail('Expected an exception');
        }
        catch (\Exception $e) {
            $this->assertEqual('Both mode and version must be provided when either are specified.', $e->getMessage());
        }
    }
}
