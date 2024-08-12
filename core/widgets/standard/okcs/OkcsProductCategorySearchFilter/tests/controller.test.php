<?php
require_once CORE_WIDGET_FILES . 'standard/search/ProductCategorySearchFilter/controller.php';

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Text as Text;

class TestOkcsProductCategorySearchFilter extends WidgetTestCase
{
    public $testingWidget = "standard/okcs/OkcsProductCategorySearchFilter";

    /**
    * UnitTest case to test default attributes.
    */
    function testDefaultAttributes() {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertTrue(is_array($data['js']['hierData']));
        $this->assertNotNull($data['js']['hierData']);
        $this->assertTrue(count($data['js']['hierData']) > 0);
        $this->restoreUrlParameters();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    /**
    * UnitTest case to test valid response.
    */
    function testGetData() {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertTrue(is_array($data['js']['hierData']));
        $this->assertNotNull($data['js']['hierData']);
        $this->restoreUrlParameters();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

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
        $this->assertTrue(Text::stringContains($content, 'Widget Error: standard/okcs/OkcsProductCategorySearchFilter - URL protocol for config IM_API_URL is not set'));
        
        $this->restoreUrlParameters();
        $this->logOut();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

}
