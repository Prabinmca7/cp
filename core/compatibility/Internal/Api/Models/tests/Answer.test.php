<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\Connect\v1_4 as Connect;
class AnswerModelTest extends CPTestCase {
    public $testingClass = 'RightNow\Api\Models\Answer';

    function __construct() {
        parent::__construct();
        $this->model = new RightNow\Api\Models\Answer();
    }

    function testInvalidGet() {
        $response = $this->model->getById('sdf');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->getById(null);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->getById("abc123");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->getById(456334);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
    }

    function testValidGet() {
        $response = $this->model->getById(1);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $answer = $response->result;
        $this->assertIsA($answer, KF_NAMESPACE_PREFIX . '\AnswerContent');
        $this->assertSame(1, $answer->ID);
        $this->assertTrue(is_string($answer->Summary));

        $response = $this->model->getById("1");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $answer = $response->result;
        $this->assertIsA($answer, KF_NAMESPACE_PREFIX . '\AnswerContent');
        $this->assertSame(1, $answer->ID);
        $this->assertTrue(is_string($answer->Summary));

        $response = $this->model->getById(99);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertFalse(is_object($response->result));
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertNull($response->result);
    }

    function testGetPopular(){
        $response = $this->model->getPopular();
        $this->assertResponseObject($response);
        $this->assertIdentical(10, count($response->result));

        //Limit tests
        $response = $this->model->getPopular(5);
        $this->assertResponseObject($response);
        $this->assertIdentical(5, count($response->result));
        $response = $this->model->getPopular(1);
        $this->assertResponseObject($response);
        $this->assertIdentical(1, count($response->result));
        $response = $this->model->getPopular(10);
        $this->assertResponseObject($response);
        $this->assertIdentical(10, count($response->result));
        $response = $this->model->getPopular(25);
        $this->assertResponseObject($response);
        $this->assertIdentical(10, count($response->result));
        $response = $this->model->getPopular(-10);
        $this->assertResponseObject($response);
        $this->assertIdentical(1, count($response->result));

        //Product/Category tests
        $response = $this->model->getPopular(10, 1);
        $this->assertResponseObject($response);
        $this->assertIdentical(10, count($response->result));
        $response = $this->model->getPopular(10, "1");
        $this->assertResponseObject($response);
        $this->assertIdentical(10, count($response->result));
        $response = $this->model->getPopular(10, null, 161);
        $this->assertResponseObject($response);
        $this->assertIdentical(9, count($response->result));
        $response = $this->model->getPopular(10, null, "161");
        $this->assertResponseObject($response);
        $this->assertIdentical(9, count($response->result));
        $response = $this->model->getPopular(10, 2, 161);
        $this->assertResponseObject($response);
        $this->assertIdentical(6, count($response->result));

        //Invalid Product/Category
        $response = $this->model->getPopular(10, "product ID", "category ID");
        $this->assertResponseObject($response, 'is_null', 1);
        $response = $this->model->getPopular(10, 300);
        $this->assertResponseObject($response, 'is_null', 1);
        $response = $this->model->getPopular(10, null, 300);
        $this->assertResponseObject($response, 'is_null', 1);
    }
}
