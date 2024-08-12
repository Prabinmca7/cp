<?php
require_once CORE_WIDGET_FILES . 'standard/searchsource/SourceResultListing/controller.php';
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestOkcsManageRecommendations extends WidgetTestCase
{
    public $testingWidget = "standard/okcs/OkcsManageRecommendations";

    /**
    * UnitTest case to test default attributes.
    */
    function testDefaultAttributes() {
        $this->logIn();
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertIsA($data['recommendations'], 'Array');
        $this->assertTrue(count($data['recommendations']) <= $data['attrs']['per_page']);
        $this->assertNotNull($data['recommendations']);
        $this->assertNotNull($data['recommendations'][0]['recordID']);
        $this->restoreUrlParameters(); 
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }
}
