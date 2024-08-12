<?php

use RightNow\UnitTest\Helper,
    RightNow\Connect\v1_4 as ConnectPHP,
    RightNow\Utils\Connect,
    RightNow\Api;

Helper::loadTestedFile(__FILE__);

class TestAnswerField extends WidgetTestCase {
    public $testingWidget = 'standard/okcs/AnswerField';

    function __construct() {
        parent::__construct();
    }

    function testFieldClassName() {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $widget = $this->createWidgetInstance();
        $widget->data['attrs']['label'] = 'Test Node';
        $widget->data['attrs']['type'] = 'Node';
        $widget->data['attrs']['xpath'] = 'TEST_CHANNEL/NODE';
        $data = $this->getWidgetData();
        $this->assertIdentical($widget->classList->classes[1], 'rn_AnswerField_Test_Channel_Node');
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    function testCheckbox() {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $widget = $this->createWidgetInstance();
        $widget->data['attrs']['label'] = 'Checkbox';
        $widget->data['attrs']['type'] = 'CHECKBOX';
        $widget->data['attrs']['xpath'] = 'TEST_CHANNEL/CHECKBOX';
        $data = $this->getWidgetData();
        $this->assertIdentical($widget->classList->classes[1], 'rn_AnswerField_Test_Channel_Checkbox');
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    function testAnswerFieldWithFileType() {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $answerData = 'title/test/status/draft';
        $encryptedData = Api::ver_ske_encrypt_fast_urlsafe($answerData);
        $encodedData = Api::encode_base64_urlsafe($encryptedData);
        $this->addUrlParameters(array('a_id' => '1000003', 'loc' => 'en_US', 'answer_data' => $encodedData));
        $widget = $this->createWidgetInstance();
        $widget->data['attrs']['label'] = 'Attachment';
        $widget->data['attrs']['type'] = 'FILE';
        $widget->data['attrs']['value'] = 'http://test.com/upload#test.pdf';
        $widget->data['attrs']['xpath'] = 'TEST_CHANNEL/ATTACHMENT/FILE';
        $data = $this->getWidgetData();
        $this->assertIdentical($widget->classList->classes[1], 'rn_AnswerField_Test_Channel_Attachment_File');
        $this->assertNotNull($widget->data['fieldData']['value']);
        $this->assertIdentical($widget->data['fileName'], 'test.pdf');
        $this->restoreUrlParameters();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
     }
}