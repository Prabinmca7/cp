<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Framework,
    RightNow\Libraries\Cache\Memcache,
    RightNow\Utils\Text as Text;

class TestSupportedLanguages extends WidgetTestCase
{
    public $testingWidget = "standard/okcs/SupportedLanguages";

    /**
    * UnitTest case to test default attributes.
    */
    function testDefaultAttributes()
    {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $cache = new \RightNow\Libraries\Cache\Memcache(7200);
        $cache->set('OKCS_REPOSITORY_LOCALES', false);
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertIsA($data['availableLanguages'], 'Array');
        $this->assertNotNull($data['availableLanguages']);
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    /**
    * UnitTest case to test invalid IM API error scenario.
    */
    function testConfigInvalidImApiUrl(){
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'tp://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $cache = new \RightNow\Libraries\Cache\Memcache(7200);
        $cache->set('OKCS_REPOSITORY_LOCALES', false);
        
        $this->createWidgetInstance();
        $getData = $this->getWidgetMethod('getData');
        list($result, $content) = $this->returnResultAndContent($getData);
        $this->assertFalse($result);
        $this->assertTrue(Text::stringContains($content, 'Widget Error: standard/okcs/SupportedLanguages - URL protocol for config IM_API_URL is not set'));
        
        $this->restoreUrlParameters();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

}
