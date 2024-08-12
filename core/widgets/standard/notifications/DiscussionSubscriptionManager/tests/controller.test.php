<?php

require_once(__DIR__ . '/../../../reports/Multiline/controller.php');

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class DiscussionSubscriptionManagerTest extends WidgetTestCase {

    public $testingWidget = 'standard/notifications/DiscussionSubscriptionManager';

    function setUp () {
        parent::setUp();
    }

    function tearDown () {
        $this->logOut();
    }

    function testGetData () {
        //If logged in then widget data should be available
        $this->logIn('useractive1');
        $this->createWidgetInstance(array( 'report_id' => '15104'));
        $widgetData = $this->getWidgetData();
        $this->assertNotEqual(count($widgetData['reportData']['data']), 0);
        
        //When logged in as useractive 2 no data should be rendered
        $this->logIn('useractive2');
        $this->createWidgetInstance(array( 'report_id' => '15104'));
        $widgetData = $this->getWidgetData();
        $this->assertEqual(count($widgetData['reportData']['data']), 0);
    }

}