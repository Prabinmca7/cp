<?php

use RightNow\Utils\Text,
    RightNow\Utils\Connect as ConnectUtils,
    RightNow\Connect\v1_4 as Connect,
    RightNow\UnitTest\Helper as TestHelper;

TestHelper::loadTestedFile(__FILE__);

class OkcsFattachTest extends CPTestCase {
    public $testingClass = 'RightNow\Controllers\OkcsFattach';
    protected $hookEndpointClass = __CLASS__;
    protected $hookEndpointFilePath = __FILE__;
    protected $attachmentAnswer = null;
    private $initialConfigValues = array();

    function setUp(){
        $this->initialConfigValues['OKCS_ENABLED'] = \Rnow::getConfig(OKCS_ENABLED);
        \Rnow::updateConfig('OKCS_ENABLED', 1, false);
        $this->initialConfigValues['OKCS_API_TIMEOUT'] = \Rnow::getConfig(OKCS_API_TIMEOUT);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, false);
        $this->initialConfigValues['OKCS_SRCH_API_URL'] = \Rnow::getConfig(OKCS_SRCH_API_URL);
        \Rnow::updateConfig('OKCS_SRCH_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsSrt/api/', false);
        $this->initialConfigValues['OKCS_IM_API_URL'] = \Rnow::getConfig(OKCS_IM_API_URL);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', false);
        parent::setUp();
    }

    function tearDown(){
        foreach ($this->initialConfigValues as $config => $value) {
            \Rnow::updateConfig($config, $value, false);
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
        $this->assertStatusCode($response, "302 Moved Temporarily");
        $this->assertTrue(Text::stringContains($response, "Location: /app/error/error_id/$errorCode [following]"));
    }

    function testGet() {
        $response = $this->makeRequest("/ci/okcsFattach/get/1001:1", array('justHeaders' => true));
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));
        $this->verifyHeaders($response);

        $response = $this->makeRequest("/ci/okcsFattach/get/16777217/C807DEFE77744046B1614174DEEB4CCE");
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertFalse(Text::stringContains($response, "302 Moved Temporarily"));
    }

    function testGetMimeType() {
        $getMimeType = $this->getMethod('getMimeType');
        $response = $getMimeType('html');
        $this->assertNotNull($response);
        $this->assertSame('application/octet-stream', $response);
    }

    function testGetContentDisposition() {
        $getContentDisposition = $this->getMethod('_getContentDisposition');
        $response = $getContentDisposition('test.html', 100, true);
        $this->assertNotNull($response);
        $this->assertSame('attachment', $response);
    }

    function testGetFileWithModifiedSchema() {
        $response = $this->makeRequest("/ci/okcsFattach/get/1000040_3", array('justHeaders' => true));
        $this->assertFalse(Text::stringContains($response, "302 Moved Temporarily"));
        $response = $this->makeRequest("/ci/okcsFattach/get/1000040_4", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));
        $this->verifyRedirect($response, '3');
    }
}
