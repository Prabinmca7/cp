<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Api;

class TestEmailAnswerLink extends WidgetTestCase
{
    public $testingWidget = "standard/utils/EmailAnswerLink";

    function __construct($label = false) {
        parent::__construct($label);
    }

    function testGetData()
    {
        $this->createWidgetInstance();

        // with no a_id URL parameter, nothing should be set
        $data = $this->getWidgetData();
        $this->assertFalse(array_key_exists('objectID', $data['js']));
        $this->assertFalse(array_key_exists('f_tok', $data['js']));
        $this->assertFalse(array_key_exists('isProfile', $data['js']));
        $this->assertFalse(array_key_exists('senderName', $data['js']));
        $this->assertFalse(array_key_exists('senderEmail', $data['js']));

        // with a_id URL parameter
        $this->addUrlParameters(array('a_id' => 52));
        $data = $this->getWidgetData();
        $this->assertIdentical($data['js']['objectID'], "52");
        $this->assertTrue(array_key_exists('f_tok', $data['js']));
        $this->assertFalse($data['js']['isProfile']);
        $this->assertFalse(array_key_exists('senderName', $data['js']));

        $this->logIn();
        $data = $this->getWidgetData();
        $this->assertIdentical($data['js']['senderEmail'], 'perpetualslacontactnoorg@invalid.com');
        $this->assertTrue($data['js']['isProfile']);

        $this->logOut();

        $this->restoreUrlParameters();
    }

    function testEmailAnswer()
    {
        $this->createWidgetInstance();
       
        $result = $this->callAjaxMethod('emailAnswer',
            array(
                'to' => 'asdf@email.null',
                'name' => 'Non-Serious Guy',
                'from' => 'blah@email.null',
                'a_id' => 1,
                'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
            ),
            true,
            $this->widgetInstance,
            array(),
            true
        );
        $this->assertTrue($result->result);

        $result = $this->callAjaxMethod('emailAnswer', array('f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0)), true, $this->widgetInstance, array(), true);
        $this->assertFalse($result->result);


        // with a valid token, but invalid 'to'
        $f_tok = \RightNow\Utils\Framework::createTokenWithExpiration(0);

        $result = $this->callAjaxMethod('emailAnswer',
            array(
                'to' => 'invalid email address',
                'name' => 'Non-Serious Guy',
                'from' => 'blah@email.null',
                'a_id' => 1,
                'f_tok' => $f_tok,
            ),
            true,
            $this->widgetInstance,
            array(),
            true
        );
        $this->assertFalse($result->result);
        $this->assertTrue(is_array($result->errors));
        $this->assertIdentical(count($result->errors), 1);

        // valid 'to', but invalid 'from'
        $f_tok = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        $result = $this->callAjaxMethod('emailAnswer',
            array(
                'to' => 'asdf@email.null',
                'name' => 'Non-Serious Guy',
                'from' => 'invalid email address',
                'a_id' => 1,
                'f_tok' => $f_tok,
            ),
            true,
            $this->widgetInstance,
            array(),
            true
        );
        $this->assertFalse($result->result);
        $this->assertTrue(is_array($result->errors));
        $this->assertIdentical(count($result->errors), 1);

        // invalid 'name'
        $f_tok = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        $result = $this->callAjaxMethod('emailAnswer',
            array(
                'to' => 'asdf@email.null',
                'name' => '',
                'from' => 'blah@email.null',
                'a_id' => 1,
                'f_tok' => $f_tok,
            ),
            true,
            $this->widgetInstance,
            array(),
            true
        );
        $this->assertFalse($result->result);
        $this->assertTrue(is_array($result->errors));
        $this->assertIdentical(count($result->errors), 1);
    }

    function testDiscussionGetData()
    {

        $this->logIn();
        $this->createWidgetInstance(array('object_type' => 'question'));

        // with no qid URL parameter, nothing should be set
        $this->addUrlParameters(array('qid' => null));
        $data = $this->getWidgetData();
        $this->assertFalse(array_key_exists('objectID', $data['js']));

        // with qid URL parameter
        $this->addUrlParameters(array('qid' => 5));
        $data = $this->getWidgetData();
        $this->assertIdentical($data['js']['objectID'], "5");

        $this->logOut();

        $this->restoreUrlParameters();
    }
}
