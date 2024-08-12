<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class PagesetSqlTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Sql\Pageset';

    function testGet() {
        $method = $this->getMethod('get');
        $results = $method();
        $this->assertNotEqual(0, count($results));
        foreach ($results as $result) {
            $this->assertNotNull($result['page_set_id']);
            $this->assertNotNull($result['description']);
            $this->assertNotNull($result['ua_regex']);
            $this->assertNotNull($result['page_set']);
            $this->assertNotNull($result['attr']);
        }
    }

    function testGetFacebookPageSetID() {
        $method = $this->getMethod('getFacebookPageSetID');
        $result = $method();
        $this->assertIsA($result, 'int');
    }
}
