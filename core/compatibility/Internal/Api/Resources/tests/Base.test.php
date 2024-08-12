<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class FakeBaseModel extends RightNow\Internal\Api\Resources\Base {
    public function createError($error){
        return parent::createError($error);
    }

    public function validateAttributes($attributes, $map) {
        return parent::validateAttributes($attributes, $map);
    }
}

class BaseResourceTest extends CPTestCase {

    function testCreateError(){
        $mockModel = new FakeBaseModel();
        $response = $mockModel->createError(array((object)array('externalMessage' => 'test', 'errorCode' => 100)));
        $this->assertIdentical($response[0]->detail, 'test');
        $this->assertIdentical($response[0]->status, 100);
    }

    function testValidateAttributes(){
        $mockModel = new FakeBaseModel();
        $response = $mockModel->validateAttributes(array('foo', 'bar', 'test'), array('foo' => 1, 'bar' => 2));
        $this->assertIdentical($response, 'test');
    }
}
