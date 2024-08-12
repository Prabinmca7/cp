<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

Use RightNow\Internal\Api\Structure,
    \RightNow\Utils\Text,
    \RightNow\Utils\Url as Url;

class DocumentStructureApiTest extends CPTestCase {
    
    public $testingClass = 'RightNow\Internal\Api\Structure\Document';

    function testGetData() {
        $document = new \RightNow\Internal\Api\Structure\Document();
        $document->setData('abc');
        $this->assertIdentical($document->getData(), 'abc');
    }

    function testGetError() {
        $document = new \RightNow\Internal\Api\Structure\Document();
        $document->setErrors('test error');
        $this->assertIdentical($document->getErrors(), 'test error');
    }

    function testOutput() {
        $documentWithError = new \RightNow\Internal\Api\Structure\Document();
        $documentWithError->setErrors('abc error');
        $documentWithError->setData('Foo Bar');
        $response1 = $documentWithError->output();
        $this->assertIdentical($response1->errors, 'abc error');
        
        $documentWithoutError = new \RightNow\Internal\Api\Structure\Document();
        $documentWithoutError->setErrors(null);
        $documentWithoutError->setData('Foo Bar');
        $documentWithoutError->setMeta('Sample Meta Data');
        $response2 = $documentWithoutError->output();
        $this->assertIdentical($response2->errors, null);
        $this->assertIdentical($response2->data, 'Foo Bar');
        $this->assertIdentical($response2->meta, 'Sample Meta Data');
    }
}
