<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Framework;
use RightNow\Utils\Text as Text;

class Test extends WidgetTestCase
{
    public $testingWidget = "standard/okcs/ContentType";

    /**
    * UnitTest case to test invalid IM Api Url scenario.
    */
    function testConfigInvalidImApiUrl(){
        $this->logIn();
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'p://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        
        $this->createWidgetInstance();
        $getData = $this->getWidgetMethod('getData');
        list($result, $content) = $this->returnResultAndContent($getData);
        $this->assertTrue(Text::stringContains($content, 'Widget Error: standard/okcs/ContentType - URL protocol for config IM_API_URL is not set'));
        Framework::setCache('OKCS_CONTENT_TYPES', false);

        $this->restoreUrlParameters();
        $this->logOut();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    /**
    * UnitTest case to test default attributes.
    */
    function testDefaultAttributes()
    {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->createWidgetInstance(); 
        $data = $this->getWidgetData();
        $this->assertIsA($data['contentTypes'],'Array');

        foreach ($data['contentTypes'] as $item) {
            $this->assertSame(1, count($item->referenceKey));
            $this->assertSame(1, count($item->name));
            $this->assertSame(1, count($item->recordId));
            $this->assertSame(1, count($item->dateAdded));
            $this->assertSame(1, count($item->dateModified));
            $this->assertSame(1, count($item->indexStatus));
            $this->assertNotNull($data['defaultContentType']);
        }
        Framework::setCache('OKCS_CONTENT_TYPES', false);
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    /**
    * UnitTest case to test parameterized case.
    */
    function testGetContentTypes()
    {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $widget = $this->createWidgetInstance();
        $widget->data['attrs']['only_display'] = 'FAQ';
        $this->addUrlParameters(array('ct' => 'FAQ'));
        $data = $this->getWidgetData();
        
        $this->assertSame(13, count($widget->data['contentTypes']));
        $this->assertSame('CATEGORY_TEST', $widget->data['contentTypes'][0]->referenceKey);
        $this->assertSame('CATEGORY_TEST', $widget->data['defaultContentType']);
        Framework::setCache('OKCS_CONTENT_TYPES', false);
        $this->restoreUrlParameters();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }
    
    /**
    * UnitTest case to test display_all_content attribute set to true.
    */
    function testAllContentType()
    {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $widget = $this->createWidgetInstance();
        $widget->data['attrs']['display_all_content'] = true;
        $data = $this->getWidgetData();
        $this->assertSame('All', $widget->data['defaultContentType']);
        Framework::setCache('OKCS_CONTENT_TYPES', false);
        $this->restoreUrlParameters();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }
}
