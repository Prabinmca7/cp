<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class FakeBaseModelClass extends RightNow\Models\Base{
    public function getKnowledgeToken(){
        return $this->getKnowledgeApiSessionToken();
    }

    public function addSecurityFilters($contentObject, $contact=null){
        $this->addKnowledgeApiSecurityFilter($contentObject, $contact);
    }

    public function cache($key, $val) {
        return parent::cache($key, $val);
    }

    public function getCached($key) {
        return parent::getCached($key);
    }

    public function abuseCheck(){
        return $this->isAbuse();
    }
}

class ResponseApiTest extends CPTestCase {
    
    public $testingClass = 'RightNow\Internal\Api\Response';

    function testGetAllowedOrigin() {
        $method = $this->getMethod('getAllowedOrigin');
        // Origin present
        $_SERVER['HTTP_ORIGIN'] = 'test.oracle.com';
        $this->assertEqual($_SERVER['HTTP_ORIGIN'], $method());

        // Origin empty - Referer present
        $_SERVER['HTTP_ORIGIN'] = '';
        $_SERVER['HTTP_REFERER'] = "http://vipdq01.dq.lan/s/oit-qa/latest/inlays/oracle/chat-embedded/example1.html";
        $this->assertEqual('http://vipdq01.dq.lan', $method());
    }

    function testGetResponseObject(){
        $mockModel = new FakeBaseModelClass();
        $response = $mockModel->getResponseObject(null);
        $this->assertNull($response->result);
        $this->assertIdentical('is_object', $response->validationFunction);
        $this->assertTrue(is_array($response->errors));
        $this->assertTrue(is_array($response->warnings));

        $response = $mockModel->getResponseObject(null, 'is_null');
        $this->assertNull($response->result);
        $this->assertIdentical('is_null', $response->validationFunction);
        $this->assertTrue(is_array($response->errors));
        $this->assertTrue(is_array($response->warnings));

        $response = $mockModel->getResponseObject(null, 'is_null', 'Fake error from testing');
        $this->assertNull($response->result);
        $this->assertIdentical('is_null', $response->validationFunction);
        $this->assertTrue(is_array($response->errors));
        $this->assertSame("Fake error from testing", $response->error . '');
        $this->assertTrue(is_array($response->warnings));

        $response = $mockModel->getResponseObject(null, 'is_null', array('Fake error from testing'));
        $this->assertNull($response->result);
        $this->assertIdentical('is_null', $response->validationFunction);
        $this->assertTrue(is_array($response->errors));
        $this->assertSame("Fake error from testing", $response->error . '');
        $this->assertTrue(is_array($response->warnings));

        $response = $mockModel->getResponseObject(null, 'is_null', null, 'Fake warning from testing');
        $this->assertNull($response->result);
        $this->assertIdentical('is_null', $response->validationFunction);
        $this->assertTrue(is_array($response->errors));
        $this->assertTrue(is_array($response->warnings));
        $this->assertSame("Fake warning from testing", $response->warning . '');

        $response = $mockModel->getResponseObject(null, 'is_null', null, array('Fake warning from testing'));
        $this->assertNull($response->result);
        $this->assertIdentical('is_null', $response->validationFunction);
        $this->assertTrue(is_array($response->errors));
        $this->assertTrue(is_array($response->warnings));
        $this->assertSame("Fake warning from testing", $response->warning . '');
    }


}
