<?php

require_once CORE_WIDGET_FILES . 'standard/utils/EmailAnswerLink/controller.php';
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Api,
    RightNow\Utils\Text as Text;

class TestOkcsEmailAnswerLink extends WidgetTestCase
{
    public $testingWidget = "standard/okcs/OkcsEmailAnswerLink";

    function __construct($label = false) {
        parent::__construct($label);
    }

    function testWithoutSetAttributes()
    {
        $this->createWidgetInstance();

        // with no a_id URL parameter, nothing should be set and widget error should be thrown
        $getData = $this->getWidgetMethod('getData');
        $data = $this->getWidgetData();
        list($result, $content) = $this->returnResultAndContent($getData);
        $this->assertTrue(Text::stringContains($content, 'Widget Error: standard/okcs/OkcsEmailAnswerLink - AnswerID is not available'));
        $this->assertFalse(array_key_exists('emailAnswerToken', $data['js']));
        $this->assertFalse(array_key_exists('isProfile', $data['js']));
        $this->assertFalse(array_key_exists('senderName', $data['js']));
        $this->assertFalse(array_key_exists('senderEmail', $data['js']));
        $this->assertFalse(array_key_exists('docId', $data['js']));
        $this->assertFalse(array_key_exists('title', $data['js']));

        $this->restoreUrlParameters();
    }

    function testInvalidAnswerId()
    {
        $this->addUrlParameters(array('a_id' => 'XXXX'));
        $this->createWidgetInstance();
        Framework::setCache('ANSWER_KEY', false);
        $data = $this->getWidgetData();
        $this->assertIdentical($data['js']['docId'],'XXXX');
        $this->assertNull($data['js']['title']);    
    }

    function testValidAnswerId()
    {
        $this->addUrlParameters(array('a_id' => '1000000'));
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertIdentical($data['js']['docId'], "1000000");
        $this->assertTrue(array_key_exists('emailAnswerToken', $data['js']));
        $this->assertFalse($data['js']['isProfile']);
        $this->assertFalse(array_key_exists('senderName', $data['js']));
        $this->logIn();
        $data = $this->getWidgetData();
        $this->assertIdentical($data['js']['senderEmail'], 'perpetualslacontactnoorg@invalid.com');
        $this->assertTrue($data['js']['isProfile']);
        $this->logOut();

        $this->restoreUrlParameters();
    }
}
