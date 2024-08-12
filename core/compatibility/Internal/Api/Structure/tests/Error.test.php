<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ErrorStructureApiTest extends CPTestCase {
    
    public $testingClass = 'RightNow\Internal\Api\Structure\Error';

    function testGetDetail() {
        $error = new \RightNow\Internal\Api\Structure\Error();
        $error->setDetail('Access denied');
        $this->assertIdentical($error->getDetail(), 'Access denied');
    }

    function testGetStatus() {
        $error = new \RightNow\Internal\Api\Structure\Error();
        $error->setStatus(403);
        $this->assertIdentical($error->getStatus(), 403);
    }

    function testOutput() {
        $error = new \RightNow\Internal\Api\Structure\Error();
        $error->setDetail('Page Not Found');
        $error->setStatus(404);
        $response = $error->output();
        $this->assertIdentical($response->detail, 'Page Not Found');
        $this->assertIdentical($response->status, 404);
    }
}
