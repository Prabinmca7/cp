<?php

use RightNow\Utils\Config;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestRssIcon extends WidgetTestCase {
    public $testingWidget = 'standard/knowledgebase/RssIcon';

    function testLoginRequiredSetting() {
        $instance = $this->getWidgetInstance();
        $originalConfigValue = Config::getConfig(CP_CONTACT_LOGIN_REQUIRED);
        \Rnow::updateConfig("CP_CONTACT_LOGIN_REQUIRED", true, true);

        $this->assertFalse($instance->getData());

        \Rnow::updateConfig("CP_CONTACT_LOGIN_REQUIRED", $originalConfigValue, true);

        $this->assertNull($instance->getData());
    }

    function testWithoutParameters(){
        $instance = $this->getWidgetInstance();
        $widgetData = $this->getWidgetData($instance);

        $this->assertIdentical($widgetData['feedParams'], '');
    }

    function testWithParameters(){
        $instance = $this->getWidgetInstance();

        $this->addUrlParameters(array('p' => 10));
        $widgetData = $this->getWidgetData($instance);
        $this->assertIdentical($widgetData['feedParams'], '/p/1,2,10');

        $this->restoreUrlParameters();

        $this->addUrlParameters(array('c' => 20));
        $widgetData = $this->getWidgetData($instance);
        $this->assertIdentical($widgetData['feedParams'], '/c/');

        $this->restoreUrlParameters();

        $this->addUrlParameters(array('p' => 10, 'c' => 20));
        $widgetData = $this->getWidgetData($instance);
        $this->assertIdentical($widgetData['feedParams'], '/p/1,2,10/c/');

        $this->restoreUrlParameters();
    }

    function testHrefWithSocialQuestionAttribute () {
        $instance = $this->getWidgetInstance(array('object_type' => 'CommunityQuestion'));
        $widgetData = $this->getWidgetData($instance);
        $this->assertIdentical($widgetData['href'], '/ci/cache/socialrss');
    }

    function testFeedParamsWithSocialQuestionAndProductAttribute () {
        $instance = $this->getWidgetInstance(array('object_type' => 'CommunityQuestion', "prodcat_type" => "Product"));
        $widgetData = $this->getWidgetData($instance);
        $this->assertIdentical($widgetData['feedParams'], '/p/');
    }

    function testFeedParamsWithSocialQuestionAndCategoryAttribute () {
        $instance = $this->getWidgetInstance(array('object_type' => 'CommunityQuestion', "prodcat_type" => "Category"));
        $widgetData = $this->getWidgetData($instance);
        $this->assertIdentical($widgetData['feedParams'], '/c/');
    }

    function testHrefWithoutSocialQuestionAttribute () {
        $instance = $this->getWidgetInstance();
        $widgetData = $this->getWidgetData($instance);
        $this->assertIdentical($widgetData['href'], '/ci/cache/rss');
    }
}
