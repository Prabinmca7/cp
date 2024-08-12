<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class DataStructureApiTest extends CPTestCase {
    
    public $testingClass = 'RightNow\Internal\Api\Structure\Data';

    function testGetId() {
        $data = new \RightNow\Internal\Api\Structure\Data();
        $data->setId(10);
        $this->assertIdentical($data->getId(), 10);
    }

    function testGetType() {
        $data = new \RightNow\Internal\Api\Structure\Data();
        $data->setType('foo');
        $this->assertIdentical($data->getType(), 'foo');
    }

    function testGetAttributes() {
        $data = new \RightNow\Internal\Api\Structure\Data();
        $data->setAttributes('attachment', 'test.txt');
        $this->assertIdentical($data->getAttributes(), array('attachment' => 'test.txt'));
    }

    function testOutput() {
        $data = new \RightNow\Internal\Api\Structure\Data();
        $data->setAttributes('url', 'www.google.com');
        $data->setType('Foo Bar');
        $data->setId(12);
        $response = $data->output();
        $this->assertIdentical($data->getAttributes(), array('url' => 'www.google.com'));
        $this->assertIdentical($response->type, 'Foo Bar');
        $this->assertIdentical($response->id, 12);
    }
}
