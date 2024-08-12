<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Framework;
use RightNow\Utils\Text as Text;

class Test extends WidgetTestCase
{
    public $testingWidget = "standard/okcs/OkcsTranslatedAnswerSelector";

    function testOkcsDisabled(){
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);

        $this->createWidgetInstance();
        $getData = $this->getWidgetMethod('getData');
        list($result, $content) = $this->returnResultAndContent($getData);
        $this->assertTrue(Text::stringContains($content, 'Widget Error: standard/okcs/OkcsTranslatedAnswerSelector - The OKCS_ENABLED config setting must be enabled to use this widget.'));

        $this->restoreUrlParameters();
    }

    function testinvalidImApiUrl(){
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'p://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);

        $this->addUrlParameters(array('a_id' => '1000000'));
        $this->createWidgetInstance();$data = $this->getWidgetData();
        $this->assertTrue(Text::stringContains($data['error'], 'URL protocol for config IM_API_URL is not set'));
        $this->restoreUrlParameters();
    }
    
    /**
    * UnitTest case to test valid scenario.
    */
    function testDefaultAttributes(){
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);

        $this->addUrlParameters(array('a_id' => '1000001'));
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertSame('en_US', $data['locale']);

        $this->restoreUrlParameters();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }
}
