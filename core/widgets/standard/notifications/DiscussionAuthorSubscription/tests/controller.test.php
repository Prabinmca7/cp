<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class DiscussionAuthorSubscriptionTest extends WidgetTestCase {

    public $testingWidget = 'standard/notifications/DiscussionAuthorSubscription';

    function setUp () {
        parent::setUp();
    }

    function tearDown () {
        $this->logOut();
    }

    function testGetData () {
        
        //If the user is not logged in then the widget should return false
        $this->createWidgetInstance();
        $this->assertFalse($this->widgetInstance->getData());
        
        $this->logIn('useractive1');
        $this->createWidgetInstance();
        $this->assertNull($this->widgetInstance->getData());
        
    }
    
    function testIsSubscribedToProdCat () {
        $this->logIn('useractive1');
        
        // Product ID 6
        $parameters = array('prodCatID' => 6);
        ob_start();
        $this->widgetInstance->isSubscribedToProdCat($parameters);
        $actualResponse = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertTrue($actualResponse);

        // Product ID 8
        $parameters = array('prodCatID' => 8);
        ob_start();
        $this->widgetInstance->isSubscribedToProdCat($parameters);
        $actualResponse = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertFalse($actualResponse);
        
        // Category ID 70 
        $this->createWidgetInstance(array("prodcat_type" => "Category"));
        $parameters = array('prodCatID' => 70);
        ob_start();
        $this->widgetInstance->isSubscribedToProdCat($parameters);
        $actualResponse = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertTrue($actualResponse);
        
        // Category ID 78
        $parameters = array('prodCatID' => 78);
        ob_start();
        $this->widgetInstance->isSubscribedToProdCat($parameters);
        $actualResponse = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertFalse($actualResponse);
    }

}