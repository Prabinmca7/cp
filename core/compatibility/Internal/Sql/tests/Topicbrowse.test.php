<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TopicbrowseSqlTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Sql\Topicbrowse';

    function testGetClusterTreeLevels() {
        $getClusterTreeLevels = $this->getMethod('getClusterTreeLevels');
        $expected = 'ct.lvl1_id, ct.lvl2_id, ct.lvl3_id, ct.lvl4_id, ct.lvl5_id, ct.lvl6_id, ct.lvl7_id, ct.lvl8_id, ct.lvl9_id, ct.lvl10_id, ct.lvl11_id';
        $actual = $getClusterTreeLevels();
        $this->assertIdentical($expected, $actual);
    }
}
