<?php
use RightNow\Api,
    RightNow\Connect\v1_4 as Connect,
    RightNow\UnitTest\Helper;

Helper::loadTestedFile(__FILE__);

class DiscussionSubscriptionIconTest extends WidgetTestCase {
    public $testingWidget = 'standard/notifications/DiscussionSubscriptionIcon';

    function __construct() {
        parent::__construct();
        $this->interfaceID = \RightNow\Api::intf_id();
    }

    function setUp() {
        $this->logIn();
        // clear out the token processCache
        $reflectionClass = new ReflectionClass('RightNow\Utils\Framework');
        $reflectionProperty = $reflectionClass->getProperty('processCache');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(array());
        parent::setUp();
    }

    function tearDown() {
        $this->logOut();
        parent::tearDown();
    }

    function testGetData() {
        $this->addUrlParameters(array('qid' => '4'));

        // subscription id should be populated if subscribed to a question
        $this->CI->model('SocialSubscription')->addSubscription(4, 'Question');
        $this->createWidgetInstance();
        $widgetData = $this->getWidgetData();
        $this->assertNotNull($widgetData['subscriptionID']);

        // subscribe to product '7' to which question '4' belongs
        $this->CI->model('SocialSubscription')->addSubscription(7, 'Product');
        $this->createWidgetInstance();
        $widgetData = $this->getWidgetData();
        $this->assertNotNull($widgetData['js']['prodSubscriptionID']);
        $this->assertEqual(7, $widgetData['productID']);
        $this->assertEqual('Replacement/Repair Coverage', $widgetData['productName']);

        // subscription id should be null if not subscribed to a question
        $this->CI->model('SocialSubscription')->deleteSubscription(4, 'Question');
        $this->createWidgetInstance();
        $widgetData = $this->getWidgetData();
        $this->assertNull($widgetData['subscriptionID']);

        $this->CI->model('SocialSubscription')->deleteSubscription(7, 'Product');
        $this->restoreUrlParameters();
    }

    function testProductSubscription() {
        $this->addUrlParameters(array('p' => '1'));

        // subscription id should be populated if subscribed to a product
        $this->CI->model('SocialSubscription')->addSubscription(1, 'Product');
        $this->createWidgetInstance(array("subscription_type" => "Product"));
        $widgetData = $this->getWidgetData();
        $this->assertNotNull($widgetData['subscriptionID']);

        // subscription id should be null if not subscribed to a product
        $this->CI->model('SocialSubscription')->deleteSubscription(1, 'Product');
        $this->createWidgetInstance(array("subscription_type" => "Product"));
        $widgetData = $this->getWidgetData();
        $this->assertNull($widgetData['subscriptionID']);

        $this->restoreUrlParameters();
    }

    function testCategorySubscription() {
        $this->addUrlParameters(array('c' => '68'));

        // subscription id should be populated if subscribed to a category
        $this->CI->model('SocialSubscription')->addSubscription(68, 'Category');
        $this->createWidgetInstance(array("subscription_type" => "Category"));
        $widgetData = $this->getWidgetData();
        $this->assertNotNull($widgetData['subscriptionID']);

        // subscription id should be null if not subscribed to a category
        $this->CI->model('SocialSubscription')->deleteSubscription(68, 'Category');
        $this->createWidgetInstance(array("subscription_type" => "Category"));
        $widgetData = $this->getWidgetData();
        $this->assertNull($widgetData['subscriptionID']);

        $this->restoreUrlParameters();
    }

    function testEndUserVisibility() {
        $this->addUrlParameters(array('p' => '1'));

        Api::test_sql_exec_direct("UPDATE prod_vis SET enduser = 0 WHERE id = 1");
        Connect\ConnectAPI::commit();
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}1Visible", false);
        $this->CI->model('SocialSubscription')->addSubscription(1, 'Product');

        $instance = $this->createWidgetInstance(array("subscription_type" => "Product"));
        $this->assertFalse($instance->getData());
        $this->CI->model('SocialSubscription')->deleteSubscription(1, 'Product');
        Api::test_sql_exec_direct("UPDATE prod_vis SET enduser = 1  WHERE id = 1");
        Connect\ConnectAPI::commit();
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}1Visible", true);

        $this->CI->model('SocialSubscription')->addSubscription(1, 'Product');
        $this->createWidgetInstance(array("subscription_type" => "Product"));
        $widgetData = $this->getWidgetData();
        $this->assertNotNull($widgetData['subscriptionID']);
        $this->assertSame($widgetData['js']['objectID'], '1');
        $this->CI->model('SocialSubscription')->deleteSubscription(1, 'Product');

        $this->restoreUrlParameters();
    }

    function testIsValidObjectAnonymous() {
        $this->logOut();
        $this->addUrlParameters(array('p' => '1'));

        $instance = $this->createWidgetInstance(array("subscription_type" => "Product"));
        $this->assertNull($instance->getData());

        $this->restoreUrlParameters();
    }

    function testIsValidObjectUserActive1() {
        $this->logOut();
        $this->logIn('useractive1');
        $this->addUrlParameters(array('p' => '1'));

        $instance = $this->createWidgetInstance(array("subscription_type" => "Product"));
        $this->assertNull($instance->getData());

        $this->restoreUrlParameters();
        $this->addUrlParameters(array('p' => '129'));

        $instance = $this->createWidgetInstance(array("subscription_type" => "Product"));
        $this->assertNull($instance->getData());

        $this->restoreUrlParameters();
        $this->logOut();
    }

    function testIsValidObjectUserProdOnly() {
        $this->logOut();
        $this->logIn('userprodonly');
        $this->addUrlParameters(array('p' => '1'));

        $instance = $this->createWidgetInstance(array("subscription_type" => "Product"));
        $this->assertFalse($instance->getData());

        $this->restoreUrlParameters();
        $this->addUrlParameters(array('p' => '129'));

        $instance = $this->createWidgetInstance(array("subscription_type" => "Product"));
        $this->assertNull($instance->getData());

        $this->restoreUrlParameters();
        $this->logOut();
    }
}
