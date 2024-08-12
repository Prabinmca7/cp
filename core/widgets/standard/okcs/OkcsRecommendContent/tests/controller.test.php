<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Framework;
use RightNow\Utils\Text as Text;

class TestOkcsRecommendContent extends WidgetTestCase
{
    public $testingWidget = "standard/okcs/OkcsRecommendContent";

    /**
    * UnitTest case to test without default channel
    */
    function testWhenNoDefaultChannel(){
        $this->logIn();
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        Framework::setCache('DEFAULT_CHANNEL', false);

        $widget = $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertFalse($data['js']['selectedContentType']);
        $this->assertSame('', $data['recommendationClass']);
        $this->assertFalse($data['js']['isRecommendChange']);

        $this->logOut();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
        $this->restoreUrlParameters();
    }

    /**
    * UnitTest case to test with a channel where recommendations 
    * are allowed
    */
    function testWithRecommendationsAllowedChannel(){
        $this->logIn();
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        Framework::setCache('DEFAULT_CHANNEL', false);
        Framework::setCache('ANSWER_KEY', null);

        $this->addUrlParameters(array('a_id' => '1000004'));//SOLUTIONS channel

        $widget = $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertFalse($data['js']['selectedContentType']);
        $this->assertSame('', $data['recommendationClass']);
        $this->assertSame('RE: WAS Installation on Windows', $data['js']['title']);

        $this->logOut();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
        $this->restoreUrlParameters();
    }

    /**
    * UnitTest case to test with a channel where recommendations 
    * are not allowed
    */
    function testWithRecommendationsNotAllowedChannel(){
        $this->logIn();
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        Framework::setCache('DEFAULT_CHANNEL', false);

        $this->addUrlParameters(array('a_id' => '1000022'));//FACET_TESTING channel
        $widget = $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertFalse($data['js']['selectedContentType']);
        $this->assertTrue($data['js']['isRecommendChange']);

        $this->logOut();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
        $this->restoreUrlParameters();
    }
}
