<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class MetaStructureApiTest extends CPTestCase {
    
    public $testingClass = 'RightNow\Internal\Api\Structure\Meta';

    function testGetLimit() {
        $meta = new \RightNow\Internal\Api\Structure\Meta();
        $meta->setLimit(10);
        $this->assertIdentical($meta->getLimit(), 10);
    }

    function testGetOffset() {
        $meta = new \RightNow\Internal\Api\Structure\Meta();
        $meta->setOffset(5);
        $this->assertIdentical($meta->getOffset(), 5);
    }

    function testGetTotalResults() {
        $meta = new \RightNow\Internal\Api\Structure\Meta();
        $meta->setTotalResults(100);
        $this->assertIdentical($meta->getTotalResults(), 100);
    }

    function testOutput() {
        $meta = new \RightNow\Internal\Api\Structure\Meta();
        $meta->setLimit(100);
        $meta->setOffset(50);
        $meta->setTotalResults(100);
        $response = $meta->output();
        $this->assertIdentical($response->limit, 100);
        $this->assertIdentical($response->offset, 50);
        $this->assertIdentical($response->totalResults, 100);
    }
}
