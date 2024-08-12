<?php
use RightNow\Utils\Text,
    RightNow\Utils\FileSystem;

require_once CORE_FILES . 'util/tarball/Versioning.php';
require_once CORE_FILES . 'framework/Internal/Utils/Version.php';

class TarballVersioningTest extends CPTestCase {
    function setUp() {
        $this->dir = sprintf("%s/unitTest/%s", get_cfg_var('upload_tmp_dir'), get_class($this));
        umask(0);
        parent::setUp();
    }

    function tearDown() {
        FileSystem::removeDirectory($this->dir, true);
        parent::tearDown();
    }

    function testGetCpHistory() {
        $cpHistory = Versioning::getCpHistory();
        $this->assertIsA($cpHistory, 'array');
        $this->assertIsA($cpHistory['frameworkVersions'], 'array');
    }

    function testGetHighestNanoVersions() {
        $versions = Versioning::getHighestNanoVersions();
        $this->assertTrue(in_array('3.1.3', $versions['framework']));
        $this->assertTrue(in_array('1.0.1', $versions['standard/knowledgebase/RelatedAnswers']));
    }

    function testfilterByHighestNanoVersion() {
        $versions = array('3.0.1', '3.0.2', '3.1.1', '3.1.2', '3.1.3', '3.2.1', '3.2.2', '3.2.3', '4.0.1');
        $expected = array('3.0.2', '3.1.3', '3.2.3', '4.0.1');
        $actual = Versioning::filterByHighestNanoVersion($versions);
        $this->assertIdentical($expected, $actual);
    }

    function testGetEufCorePath() {
        $this->assertEqual('/euf/core/1.2.3', Versioning::getEufCorePath('1.2.3'));
        $this->assertEqual('/euf/core/3.2.6', Versioning::getEufCorePath('3.2.6'));
        $this->assertEqual('/euf/core/3.2', Versioning::getEufCorePath('3.2.7'));
        $this->assertEqual('/euf/core/3.10', Versioning::getEufCorePath('3.10.0'));
        $this->assertEqual('/euf/core/3.10', Versioning::getEufCorePath('3.10.0'));
        $this->assertEqual('/euf/core/9.9', Versioning::getEufCorePath('9.9.0'));
    }

    function testIsHighestNanoVersion() {
        $this->assertTrue(Versioning::isHighestNanoVersion('framework', '3.1.3'));
        $this->assertFalse(Versioning::isHighestNanoVersion('framework', '3.1.1'));
        $this->assertTrue(Versioning::isHighestNanoVersion('standard/knowledgebase/RelatedAnswers', '1.0.1'));
        $this->assertFalse(Versioning::isHighestNanoVersion('standard/knowledgebase/RelatedAnswers', '1.0.2'));
    }

    function testSerializeCpHistoryFile() {
        $cpHistory = file_get_contents(\RightNow\Internal\Utils\Version::getVersionHistoryPath());
        $realThing = yaml_parse($cpHistory);
        $this->assertIsA($realThing, 'array');
        $path = "{$this->dir}/cpHistory";
        FileSystem::filePutContentsOrThrowExceptionOnFailure($path, $cpHistory);

        Versioning::serializeCpHistoryFile($path);
        $result = file_get_contents($path);
        $this->assertSame(unserialize($result), $realThing);
    }

    function testCompareBranchVersions() {
        $versionsToPull = array (
            'rnw-15-2-fixes',
            'rnw-14-11-fixes',
            'rnw-13-8-fixes',
            'rnw-13-5-fixes',
            'rnw-13-11-fixes',
            'rnw-12-11-fixes',
            'rnw-14-2-fixes',
            'rnw-14-5-fixes',
            'rnw-14-8-fixes',
            'rnw-15-8-fixes',
            'rnw-13-2-fixes',
            'rnw-15-5-fixes',
        );
        $expected = array (
            'rnw-12-11-fixes',
            'rnw-13-2-fixes',
            'rnw-13-5-fixes',
            'rnw-13-8-fixes',
            'rnw-13-11-fixes',
            'rnw-14-2-fixes',
            'rnw-14-5-fixes',
            'rnw-14-8-fixes',
            'rnw-14-11-fixes',
            'rnw-15-2-fixes',
            'rnw-15-5-fixes',
            'rnw-15-8-fixes',
        );
        usort($versionsToPull, "Versioning::compareBranchVersions");
        $this->assertSame($expected, $versionsToPull);
    }
}
