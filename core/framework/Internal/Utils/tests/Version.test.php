<?php

use RightNow\Internal\Utils\Version,
    RightNow\Utils\Text;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class VersionTest extends CPTestCase {
    function testGetVersionHistory() {
        Version::clearCacheVariables();

        //Default; mergeVersions is false and includeWidgetInfo is true
        $versions = Version::getVersionHistory();
        $cpHistory = file_get_contents(Version::getVersionHistoryPath());
        $cpHistory = yaml_parse($cpHistory);
        //modify cpHistory to insert categories under each widget
        foreach($cpHistory['widgetInfo'] as $widgetPath => $widgetDetails) {
            if(isset($widgetDetails['category']))
                $cpHistory['widgetVersions'][$widgetPath] = array('category' => $widgetDetails['category']) + $cpHistory['widgetVersions'][$widgetPath];
        }
        $cpHistory = yaml_emit($cpHistory);

        $this->assertSame(yaml_emit($versions), $cpHistory);

        //Grab widgets with a category for tests below
        $widgetsWithCategory = array();
        foreach($versions['widgetVersions'] as $widgetPath => $widgetDetails) {
            if(isset($widgetDetails['category']))
                $widgetsWithCategory []= $widgetPath;
        }

        //Test when includeWidgetInfo is false, mergeVersions false
        $versions = Version::getVersionHistory(false, false);
        foreach($versions['widgetVersions'] as $widgetPath => $widgetDetails) {
            $this->assertFalse(isset($widgetDetails['category']));
        }

        //Test when includeWidgetInfo is false, mergeVersions true
        $versions = Version::getVersionHistory(true, false);
        foreach($versions['widgetVersions'] as $widgetPath => $widgetDetails) {
            $this->assertFalse(isset($widgetDetails['category']));
        }

        //Test when includeWidgetInfo is true, mergeVersions false
        $versions = Version::getVersionHistory(false, true);
        foreach($versions['widgetVersions'] as $widgetPath => $widgetDetails) {
            if(in_array($widgetPath, $widgetsWithCategory))
                $this->assertTrue(isset($widgetDetails['category']));
        }

        //Test when includeWidgetInfo is true with mergeVersions
        $versions = Version::getVersionHistory(true, true);
        foreach($versions['widgetVersions'] as $widgetPath => $widgetDetails) {
            if(in_array($widgetPath, $widgetsWithCategory))
                $this->assertTrue(isset($widgetDetails['category']));
        }

        //Test that the widget versions found on the filesystem match with what's specified in the cpHistory file.
        $versionsOnDisk = Version::getVersionHistory(true);
        $yamlForVersionsOnDisk = yaml_emit($versionsOnDisk);
        $this->assertFalse(Text::stringContains($yamlForVersionsOnDisk, 'description'), "Version history contains 'description'");

        if (!$identical = $this->assertIdentical($yamlForVersionsOnDisk, $cpHistory)) {
            echo \RightNow\UnitTest\Helper::diff($yamlForVersionsOnDisk, $cpHistory, array(
                'actualLabel'   => 'CPHistory file',
                'expectedLabel' => 'Widget versions from widget info.yml on disk',
            ));
            echo "<br><pre>Either CPHistory or a widget's info.yml file is incorrect.</pre>";
        }
    }

    function testCompareVersionNumbers(){
        $this->assertEqual(Version::compareVersionNumbers("3.0.0", "3.0.1"), -1);
        $this->assertEqual(Version::compareVersionNumbers("3.0.10", "3.0.1"), 1);
        $this->assertEqual(Version::compareVersionNumbers("3.01.0", "3.1.0"), 0);
        $this->assertEqual(Version::compareVersionNumbers("3.4.15", "4.8.9"), -1);
        $this->assertEqual(Version::compareVersionNumbers("4.8.9", "3.4.5"), 1);
        $this->assertEqual(Version::compareVersionNumbers("03.5.5", "3.5.5"), 0);
        $this->assertEqual(Version::compareVersionNumbers("3.010.5", "3.010.5"), 0);
        $this->assertEqual(Version::compareVersionNumbers("3", "3.1.1"), -1);
        $this->assertEqual(Version::compareVersionNumbers("3", "3.1"), -1);
        $this->assertEqual(Version::compareVersionNumbers("3", "3"), 0);
        $this->assertEqual(Version::compareVersionNumbers("3", "3.0.0"), 0);
        $this->assertEqual(Version::compareVersionNumbers("3.2", "3.1.1"), 1);
        $this->assertEqual(Version::compareVersionNumbers("3.2", "3.3"), -1);
    }

    function testGetVersionFile() {
        umask(0);
        $testDir = sprintf("%s/unitTest/%s", get_cfg_var('upload_tmp_dir'), get_class($this));

        $this->assertNull(Version::getVersionFile(''));
        $this->assertNull(Version::getVersionFile('banana/dent/crook'));

        $contents = array('foo' => 'bar', 'banana' => 'baz');

        // cpHistory
        $file = $testDir . '/cpHistory';
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure($file, yaml_emit($contents));
        $result = Version::getVersionFile($file);
        $this->assertIdentical($contents, $result);

        // widgetVersions - php
        $file = $testDir . '/widgetVersions';
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure($file, serialize($contents));
        $result = Version::getVersionFile($file);
        $this->assertIdentical($contents, $result);

        // widgetVersions - yaml
        $file = $testDir . '/' . CUSTOMER_FILES . '/widgetVersions';
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure($file, yaml_emit($contents));
        $result = Version::getVersionFile($file);
        $this->assertIdentical($contents, $result);

        // frameworkVersion
        $file = $testDir . '/frameworkVersion';
        $contents = 'bananas     ';
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure($file, $contents);
        $result = Version::getVersionFile($file);
        $this->assertIdentical(trim($contents), $result);

        \RightNow\Internal\Utils\FileSystem::removeDirectory($testDir, true);
    }

    function testWriteVersionFile() {
        umask(0);
        $testDir = sprintf("%s/unitTest/%s", get_cfg_var('upload_tmp_dir'), get_class($this));

        $this->assertFalse(Version::writeVersionFile('', ''));

        // generic
        $file = $testDir . '/bananas.md';
        $content = 'ohyeah';
        $this->assertTrue(Version::writeVersionFile($file, $content));
        $this->assertSame($content, file_get_contents($file));

        $content = array('banana' => 'no');

        // cpHistory
        $file = $testDir . '/cpHistory';
        $this->assertTrue(Version::writeVersionFile($file, $content));
        $this->assertSame($content, yaml_parse(file_get_contents($file)));

        // widgetVersions - php
        $file = $testDir . '/widgetVersions';
        $this->assertTrue(Version::writeVersionFile($file, $content));
        $this->assertSame($content, unserialize(file_get_contents($file)));

        // widgetVersions - yaml
        $file = $testDir . '/' . CUSTOMER_FILES . '/widgetVersions';
        $this->assertTrue(Version::writeVersionFile($file, $content));
        $this->assertSame($content, yaml_parse(file_get_contents($file)));

        // frameworkVersion
        $file = $testDir . '/frameworkVersion';
        $content = 'bananas  ';
        $this->assertTrue(Version::writeVersionFile($file, $content));
        $this->assertSame($content, file_get_contents($file));

        // unsuported serialize type - just writes out contents
        $file = $testDir . '/frameworkVersion';
        $content = 'bananas  ';
        $this->assertTrue(Version::writeVersionFile($file, $content, 'json'));
        $this->assertSame($content, file_get_contents($file));

        // php serialize
        $file = $testDir . '/cpHistory';
        $this->assertTrue(Version::writeVersionFile($file, $content, 'php'));
        $this->assertSame($content, unserialize(file_get_contents($file)));

        // yaml serialize
        $file = $testDir . '/widgetVersions';
        $this->assertTrue(Version::writeVersionFile($file, $content, 'yaml'));
        $this->assertSame($content, yaml_parse(file_get_contents($file)));

        \RightNow\Internal\Utils\FileSystem::removeDirectory($testDir, true);
    }

    function testRemoveVersionPath(){
        $this->assertEqual(Version::removeVersionPath(''), '');
        $this->assertEqual(Version::removeVersionPath('asdf'), 'asdf');
        $this->assertEqual(Version::removeVersionPath('asdf/1'), 'asdf/1');
        $this->assertEqual(Version::removeVersionPath('asdf/1.'), 'asdf/1.');
        $this->assertEqual(Version::removeVersionPath('asdf/1a'), 'asdf/1a');
        $this->assertEqual(Version::removeVersionPath('asdf/1.0'), 'asdf');
        $this->assertEqual(Version::removeVersionPath('asdf/1.0.'), 'asdf.');
        $this->assertEqual(Version::removeVersionPath('asdf/1.0.3'), 'asdf');
        $this->assertEqual(Version::removeVersionPath('asdf/1.0.33'), 'asdf');
        $this->assertEqual(Version::removeVersionPath('asdf/1.0.33.'), 'asdf.');
        $this->assertEqual(Version::removeVersionPath('asdf/1.0.33.44'), 'asdf.44');
        $this->assertEqual(Version::removeVersionPath('/'), '/');
        $this->assertEqual(Version::removeVersionPath('/1'), '/1');
        $this->assertEqual(Version::removeVersionPath('/1.'), '/1.');
        $this->assertEqual(Version::removeVersionPath('/1a'), '/1a');
        $this->assertEqual(Version::removeVersionPath('/1.0'), '');
        $this->assertEqual(Version::removeVersionPath('/1.0.'), '.');
        $this->assertEqual(Version::removeVersionPath('/1.0.3'), '');
        $this->assertEqual(Version::removeVersionPath('/1.0.33'), '');
        $this->assertEqual(Version::removeVersionPath('/1.0.33.'), '.');
        $this->assertEqual(Version::removeVersionPath('/1.0.33.44'), '.44');
        $this->assertEqual(Version::removeVersionPath('asdf/1.0/qwer'), 'asdf/qwer');
        $this->assertEqual(Version::removeVersionPath('asdf/1.0.3/qwer'), 'asdf/qwer');
        $this->assertEqual(Version::removeVersionPath('asdf/1.0.33/qwer'), 'asdf/qwer');
    }

    function testGetVersionsInEnvironments() {
        $results = Version::getVersionsInEnvironments();
        $this->assertIdentical(2, count($results));
        $this->assertIdentical(3, count($results['widgets']));
        $this->assertIdentical(3, count($results['framework']));
        $this->assertIdentical($results['framework']['Production'], $results['framework']['Development']);
        $this->assertIdentical($results['framework']['Production'], $results['framework']['Staging']);
        $this->assertIdentical($results['widgets']['Production'], $results['widgets']['Development']);
        $this->assertIdentical($results['widgets']['Production'], $results['widgets']['Staging']);

        $limitedResults = Version::getVersionsInEnvironments('FRAMEWORK', 'DEVELOPMENT');
        $this->assertIsA($limitedResults, 'string');
        $this->assertIdentical($results['framework']['Development'], $limitedResults);

        $limitedResults = Version::getVersionsInEnvironments(null, 'production');
        $this->assertIdentical(array('widgets', 'framework'), array_keys($limitedResults));
        $this->assertIdentical($results['widgets']['Production'], $limitedResults['widgets']);
        $this->assertIdentical($results['framework']['Production'], $limitedResults['framework']);

        $limitedResults = Version::getVersionsInEnvironments('framework');
        $keys = array_keys($limitedResults);
        $this->assertIdentical(3, count($keys));
        $this->assertTrue(in_array('Staging', $keys));
        $this->assertTrue(in_array('Production', $keys));
        $this->assertTrue(in_array('Development', $keys));
        $this->assertIdentical($results['framework']['Staging'], $limitedResults['Staging']);
        $this->assertIdentical($results['framework']['Development'], $limitedResults['Development']);
        $this->assertIdentical($results['framework']['Production'], $limitedResults['Production']);

        $limitedResults = Version::getVersionsInEnvironments('WIDGETS');
        $keys = array_keys($limitedResults);
        $this->assertIdentical(3, count($keys));
        $this->assertTrue(in_array('Staging', $keys));
        $this->assertTrue(in_array('Production', $keys));
        $this->assertTrue(in_array('Development', $keys));
        $this->assertIdentical($results['widgets']['Staging'], $limitedResults['Staging']);
        $this->assertIdentical($results['widgets']['Development'], $limitedResults['Development']);
        $this->assertIdentical($results['widgets']['Production'], $limitedResults['Production']);
    }

    function testVersionNameToNumber() {
        $pairs = array(
          array('November `09',  '9.11'),
          array("November '09",  '9.11'),
          array("November ^09",  '9.11'),
          array('february \`09', '9.2'),
          array('February \`09', '9.2'),
          array('3434 \`09',     null),
          array('Feb \`09',      null),
          array('May 10',        '10.5'),
          array('May `10',       '10.5'),
          array('Mayab',         null),
          array('May ab',        null),
          array('August 2010',   '10.8'),
          array('10.2',          null),
          array('~!@#$%^&*()_+-={}|[]\:";\'<>?,./', null)
        );
        $this->_testPairsForEquality($pairs, array('\RightNow\Internal\Utils\Version', 'versionNameToNumber'));
    }

    function testVersionNumberToName() {
        $pairs = array(
          array('9.11',   'November 2009'),
          array('10.8.0.1-b122h-mysql', 'August 2010'),
          array('10.8.0.1-mysql', null),
          array('10.8-b23h-mysql', null),
          array('10.2',   'February 2010'),
          array('11.5',   'May 2011'),
          array('12.8',   'August 2012'),
          array('November 09', null),
          array('2010.5',  null),
          array('10.13',   null),
          array('a.b',     null),
          array('ab',      null),
          array('-10.2',   null),
          array('10.-2',   null),
          array('0.11',   'November 2000'),
          array('~!@#$%^&*()_+-={}|[]\:";\'<>?,./', null)
        );

        $this->_testPairsForEquality($pairs, array('\RightNow\Internal\Utils\Version', 'versionNumberToName'));
    }

    function testIsValidVersionNumber() {
        $this->assertTrue(Version::isValidVersionNumber('11.2'));
        $this->assertTrue(Version::isValidVersionNumber('11.2.0.1'));
        $this->assertTrue(Version::isValidVersionNumber('11.2.0.1-b32h-mysql'));
        $this->assertFalse(Version::isValidVersionNumber('11.2.0.1-mysql'));
        $this->assertFalse(Version::isValidVersionNumber('11.2-b32h-mysql'));
        $this->assertFalse(Version::isValidVersionNumber('11.2.0.1.5'));
    }

    function testGetVersionNumberAndName() {
        $this->assertEqual(array('10.2', 'February 2010'), Version::getVersionNumberAndName('10.2'));
        $this->assertEqual(array('10.2', 'February 2010'), Version::getVersionNumberAndName('10.2.0.1-b34h-mysql'));
        $this->assertEqual(array(null, null), Version::getVersionNumberAndName('10.2.0.1-mysql'));
        $this->assertEqual(array('10.2', 'February 2010'), Version::getVersionNumberAndName('February `10'));
        $this->assertEqual(array('10.2', 'February 2010'), Version::getVersionNumberAndName('February 10'));
        $this->assertEqual(array('10.8', 'August 2010'), Version::getVersionNumberAndName('August 2010'));
        $this->assertEqual(array('10.8', 'August 2010'), Version::getVersionNumberAndName('August 10'));
        $this->assertEqual(array(null, null), Version::getVersionNumberAndName('100.13'));
    }

    function testGetVersionNumber() {
        $this->assertEqual('10.2', Version::getVersionNumber('10.2'));
        $this->assertEqual('10.2', Version::getVersionNumber('10.2.0.0'));
        $this->assertEqual('10.2', Version::getVersionNumber('10.2.0.0-b34h-mysql'));
        $this->assertNotNull(Version::getVersionNumber(MOD_BUILD_VER));
    }

    function testGetVersionName() {
        $this->assertEqual('February 2010', Version::getVersionName('10.2'));
        $this->assertEqual('February 2010', Version::getVersionName('10.2.0.0'));
        $this->assertEqual('February 2010', Version::getVersionName('10.2.0.0-b34h-mysql'));
        $this->assertEqual('February 2010', Version::getVersionName('February `10'));
        $this->assertEqual('February 2010', Version::getVersionName('February 10'));
        $this->assertEqual('May 2010', Version::getVersionName('May 2010'));
        $this->assertEqual('August 2010', Version::getVersionName('August 2010'));
        $this->assertEqual('August 2010', Version::getVersionName('August 10'));
        $this->assertNotNull(Version::getVersionName(MOD_BUILD_VER));
    }

    function testVersionToDigits() {
        $this->assertEqual(array(10, 2, 0, 1, 123), Version::versionToDigits('10.2.0.1-b123h-mysql'));
        $this->assertEqual(array(9, 11, 0, 0, 0), Version::versionToDigits('9.11'));
        $this->assertEqual(array(9, 11, 1, 2, 3), Version::versionToDigits('9.11.1.2.3.4'));
        $this->assertEqual(array(10, 0, 0, 0, 0), Version::versionToDigits(10));
    }

    function testGetVersionRange() {
        $expected = array('8.2', '8.5', '8.8', '8.11', '9.2', '9.5', '9.8', '9.11', '10.2');
        $actual = Version::getVersionRange('8.2', '10.2');
        $this->assertEqual($expected, $actual);

        $actual = Version::getVersionRange(new \RightNow\Internal\Libraries\Version('8.2'), new \RightNow\Internal\Libraries\Version('10.2'));
        $this->assertEqual($expected, $actual);
    }

    function testIsValidFrameworkVersion() {
        $this->assertTrue(Version::isValidFrameworkVersion('3.6'));
        $this->assertTrue(Version::isValidFrameworkVersion('3.8'));
        $this->assertFalse(Version::isValidFrameworkVersion(3.8));
        $this->assertFalse(Version::isValidFrameworkVersion('3.99'));
        $this->assertFalse(Version::isValidFrameworkVersion('1111'));
        $this->assertFalse(Version::isValidFrameworkVersion(null));
    }

    function testGetAdjacentVersionNumber() {
        $this->assertEqual('10.2', Version::getAdjacentVersionNumber('9.11'));
        $this->assertEqual('10.2', Version::getAdjacentVersionNumber('10.2', 0));
        $this->assertEqual('9.11', Version::getAdjacentVersionNumber('10.2', -1));
        $this->assertEqual('10.11', Version::getAdjacentVersionNumber('9.11', 4));
        $this->assertEqual('9.11', Version::getAdjacentVersionNumber('10.11', -4));
    }

    private function _testPairsForEquality($pairs, $func) {
        foreach ($pairs as $pair) {
            $input = $pair[0];
            $expected = $pair[1];
            $output = call_user_func($func, $input);
            $this->assertEqual($expected, $output, "INPUT: '$input'  EXPECTED: '$expected'  GOT: '$output'");
        }
    }
}
