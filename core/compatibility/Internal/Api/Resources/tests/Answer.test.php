<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\Api\Models\Answer as AnswerModel;

class AnswerResourceTest extends CPTestCase {

    public $testingClass = 'RightNow\Internal\Api\Resources\Answer';

    function testGetAnswer() {
        $method = $this->getMethod('getAnswer');
        $params = array('uriParams' => array('answers' => 52), 'queryParams' => array());
        $this->assertNotNull($method($params)->data->attributes);
    }

    function testGetAnswerList() {
        $method = $this->getMethod('getAnswerList');
        $params = array('uriParams' => array('answers' => null), 'queryParams' => array());
        $this->assertTrue(count($method($params)->data) > 0);
    }

    function testCreateData() {
        $method = $this->getMethod('createData');
        $answer = new AnswerModel();
        $answer = $answer->getById(1)->result;
        $attributes = array('body', 'created', 'description');
        $this->assertEqual($method($answer, $attributes)->id, 1);
        $this->assertEqual($method($answer, $attributes)->type, 'answers');
    }

    function testCreateDataCollection() {
        $method2 = $this->getMethod('createDataCollection');
        $answer = new AnswerModel();
        $answers = $answer->getPopular()->result;
        $attributes = array('title', 'excerpt');
        $this->assertEqual(count($method2($answers, $attributes)), 10);
    }

    function testSearch() {
        $method = $this->getMethod('search');
        $params = array('uriParams' => array('answers' => null), 'queryParams' => array('filter' => array('$content' => array('contains' => 'iphone')), 'searchType' => 'user'));
        $this->assertTrue(count($method($params)->data) > 0);
    }

    function testCreateMeta() {
        $method = $this->getMethod('createMeta');
        $result = (object)array('size' => '200', 'offset' => 10, 'total' => 100);
        $response = $method($result);
        $this->assertIdentical((int)$response->limit, 200);
        $this->assertIdentical((int)$response->offset, 10);
        $this->assertIdentical((int)$response->totalResults, 100);
    }
}
