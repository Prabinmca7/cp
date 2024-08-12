<?php

use RightNow\Utils\Text,
    RightNow\Utils\Connect as ConnectUtils,
    RightNow\Connect\v1_4 as Connect,
    RightNow\UnitTest\Helper as TestHelper;

TestHelper::loadTestedFile(__FILE__);

class OkcsFileTest extends CPTestCase {
    public $testingClass = 'RightNow\Controllers\OkcsFile';
    protected $hookEndpointClass = __CLASS__;
    protected $hookEndpointFilePath = __FILE__;
    protected $attachmentAnswer = null;
    
    function setUp(){
        $this->initialConfigValues['OKCS_ENABLED'] = \Rnow::getConfig(OKCS_ENABLED);
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        $this->initialConfigValues['OKCS_API_TIMEOUT'] = \Rnow::getConfig(OKCS_API_TIMEOUT);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        $this->initialConfigValues['OKCS_SRCH_API_URL'] = \Rnow::getConfig(OKCS_SRCH_API_URL);
        \Rnow::updateConfig('OKCS_SRCH_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsSrt/api/', true);
        $this->initialConfigValues['OKCS_IM_API_URL'] = \Rnow::getConfig(OKCS_IM_API_URL);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        parent::setUp();
    }
    
    function tearDown(){
        foreach ($this->initialConfigValues as $config => $value) {
            \Rnow::updateConfig($config, $value, true);
        }
        parent::tearDown();
    }

    function verifyHeaders($response) {
        $directives = array(
            'Date: ',
            'Content-Type: '
        );
        foreach ($directives as $directive) {
            if (!Text::stringContains($response, $directive)) {
                $this->fail("Directive not found: $directive");
            }
        }
    }

    function verifyRedirect($response, $errorCode) {
        $this->assertTrue(Text::stringContains($response, "HTTP/1.1 302 Moved Temporarily"));
        $this->assertTrue(Text::stringContains($response, "Location: /app/error/error_id/$errorCode [following]"));
    }

    function testGet() {
        $response = $this->makeRequest("/ci/okcsFile/get/16777217/413810c229dd-1b1e-4196-a948-d8746081a7f8/1132446678/HTML");
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertFalse(Text::stringContains($response, "302 Moved Temporarily"));
    }
    
    function testGetHtmlDataForText() {
        $getHtmlData = $this->getMethod('_getHtmlData');
        $response = $getHtmlData($answerID = 1000052, $attachmentData = null, $priorTxnId = 1242, $fileType = 'TEXT', $transactionId = 1243, $externalURL = null);
        $this->assertNotNull($response); 
        $this->assertNotNull($response['file']);
        $this->assertNull($response['html']);
        $this->assertSame('http://slc04oqn.us.oracle.com/test/Car%20Loan%20Closure.txt', $response['url']);
    }
     
    function testGetHtmlDataForHTML() {
        $getHtmlData = $this->getMethod('_getHtmlData');
        $response = $getHtmlData($answerID = 1000053, $attachmentData = null, $priorTxnId = 1252, $fileType = 'HTML', $transactionId = 1253, $externalURL = null);
        $this->assertNotNull($response);
        $this->assertNull($response['file']);
        $this->assertNotNull($response['html']);
        $this->assertSame('http://slc04oqn.us.oracle.com/test/Home%20Loan.html', $response['url']);
    }
    
    function testGetHtmlDataForPDF() {
        $getHtmlData = $this->getMethod('_getHtmlData');
        $response = $getHtmlData($answerID = 1000054, $attachmentData = null, $priorTxnId = 1262, $fileType = 'PDF', $transactionId = 1263, $externalURL = null);
        $this->assertNotNull($response); 
        $this->assertNotNull($response['file']);
        $this->assertNull($response['html']);
        $this->assertTrue(Text::stringContains($response['url'], "xml"));
    }
    
    function testGetHtmlDataForDownloadDoc() {
        $getHtmlData = $this->getMethod('_getHtmlData');
        $response = $getHtmlData($answerID = 1000055, $attachmentData = null, $priorTxnId = 1272, $fileType = 'MS-WORD', $transactionId = 1273, $externalURL = null);
        $this->assertNotNull($response); 
        $this->assertNull($response['file']);
        $this->assertNull($response['html']);
        $this->assertSame('http://slc04oqn.us.oracle.com/test/Guidelines%20for%20Proof%20Submission%20FY%202014-15.doc', $response['url']);
    }
    
    function testGetHtmlDataForDownloadRTF() {
        $getHtmlData = $this->getMethod('_getHtmlData');
        $response = $getHtmlData($answerID = 1000056, $attachmentData = null, $priorTxnId = 1423, $fileType = 'RTF', $transactionId = 1424, $externalURL = null);
        $this->assertNotNull($response); 
        $this->assertNull($response['file']);
        $this->assertNull($response['html']);
        $this->assertSame('http://slc04oqn.us.oracle.com/test/Loan%20recovery%20amount%20for%20Car%20loan%20and%20clearing%20of%20car%20loan.rtf', $response['url']);
    }
    
    function testGetHtmlDataForDownloadPPT() {
        $getHtmlData = $this->getMethod('_getHtmlData');
        $response = $getHtmlData($answerID = 1000057, $attachmentData = null, $priorTxnId = 1433, $fileType = 'MS-POWERPOINT', $transactionId = 1434, $externalURL = null);
        $this->assertNotNull($response); 
        $this->assertNull($response['file']);
        $this->assertNull($response['html']);
        $this->assertSame('http://slc04oqn.us.oracle.com/test/Power%20point%20presentation%20on%20the%20loan%20calculation.ppt', $response['url']);
    }
    
    function testGetHtmlDataForDownloadEXCEL() {
        $getHtmlData = $this->getMethod('_getHtmlData');
        $response = $getHtmlData($answerID = 1000059, $attachmentData = null, $priorTxnId = 1443, $fileType = 'MS-EXCEL', $transactionId = 1444, $externalURL = null);
        $this->assertNotNull($response); 
        $this->assertNull($response['file']);
        $this->assertNull($response['html']);
        $this->assertSame('http://slc04oqn.us.oracle.com/test/Home%20Loan%20Calculation.xlsx', $response['url']);
    }
    
    function testGetHtmlDataForXML() {
        $getHtmlData = $this->getMethod('_getHtmlData');
        $response = $getHtmlData($answerID = 1000058, $attachmentData = null, $priorTxnId = 1453, $fileType = 'XML', $transactionId = 1454, $externalURL = null);
        $this->assertNotNull($response); 
        $this->assertNull($response['file']);
        $this->assertNull($response['html']);
        $this->assertSame('http://slc04oqn.us.oracle.com/test/XML%20document%20for%20loan%20recovery.xml', $response['url']);
    }
    
    function testGetHtmlDataForODT() {
        $getHtmlData = $this->getMethod('_getHtmlData');
        $response = $getHtmlData($answerID = 1000060, $attachmentData = null, $priorTxnId = 1463, $fileType = 'OPEN-OFFICE-DOCUMENT', $transactionId = 1464, $externalURL = null);
        $this->assertNotNull($response); 
        $this->assertNull($response['file']);
        $this->assertNull($response['html']);
        $this->assertSame('http://slc04oqn.us.oracle.com/test/Home%20Loan%20Oracle%20document.odt', $response['url']);
    }
    
    function testGetHtmlDataForODS() {
        $getHtmlData = $this->getMethod('_getHtmlData');
        $response = $getHtmlData($answerID = 1000061, $attachmentData = null, $priorTxnId = 1473, $fileType = 'OPEN-OFFICE-SPREADSHEET', $transactionId = 1474, $externalURL = null);
        $this->assertNotNull($response); 
        $this->assertNull($response['file']);
        $this->assertNull($response['html']);
        $this->assertSame('http://slc04oqn.us.oracle.com/test/Loan%20Calculation%20sheet.ods', $response['url']);
    }
    
    function testGetHtmlDataForODP() {
        $getHtmlData = $this->getMethod('_getHtmlData');
        $response = $getHtmlData($answerID = 1000062, $attachmentData = null, $priorTxnId = 1483, $fileType = 'OPEN-OFFICE-PRESENTATION', $transactionId = 1484, $externalURL = null);
        $this->assertNotNull($response); 
        $this->assertNull($response['file']);
        $this->assertNull($response['html']);
        $this->assertSame('http://slc04oqn.us.oracle.com/test/Loan%20ODP.odp', $response['url']);
    }
    
    function testGetHtmlDataForCMSXML() {
        $getHtmlData = $this->getMethod('_getHtmlData');
        $response = $getHtmlData($answerID = 1000036, $attachmentData = null, $priorTxnId = 1235, $fileType = 'CMS-XML', $transactionId = 1235, $externalURL = null);
        $this->assertNotNull($response); 
        $this->assertNull($response['file']);
        $this->assertNull($response['html']);
        $this->assertTrue(Text::stringContains($response['url'], "IM:"));
    }

    function testHTMLDataWithNullHttpPassThrough() {
        $getHtmlData = $this->getMethod('_getHtmlData');
        $response = $getHtmlData($answerID = 1000053, $attachmentData = null, $priorTxnId = 1252, $fileType = 'HTML', $transactionId = 1253, $externalURL = null);
        $this->assertSame('http://slc04oqn.us.oracle.com/test/Home%20Loan.html', $response['url']);
    }
	
    function testGetExternalDocuments() {
        $getHtmlData = $this->getMethod('_getHtmlData');
        $response = $getHtmlData($answerID = 0, $attachmentData = null, $priorTxnId = 0, $fileType = 'HTML', $transactionId = 0, $externalURL = 'https%3A%2F%2Fslc09cug.us.oracle.com%2Ftest%2FSyndicateWidget%2F');
        $this->assertNotNull($response);
        $this->assertNotNull($response['file']);
        $this->assertSame('https://slc09cug.us.oracle.com/test/SyndicateWidget/', $response['file']);
    }
}
