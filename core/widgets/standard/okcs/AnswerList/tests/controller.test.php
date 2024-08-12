<?php
require_once CORE_WIDGET_FILES . 'standard/searchsource/SourceResultListing/controller.php';
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestAnswerList extends WidgetTestCase
{
    public $testingWidget = "standard/okcs/AnswerList";

    /**
    * UnitTest case to test default attributes.
    */
    function testDefaultAttributes() {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->addUrlParameters(array('ct' => 'DEFECT'));
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertIsA($data['articles'], 'Array');
        $this->assertTrue(count($data['articles']) <= $data['attrs']['per_page']);
        $this->assertNotNull($data['articles']);
        $this->assertNotNull($data['articles'][0]['documentId']);
        $this->restoreUrlParameters(); 
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    /**
    * UnitTest case to test draft default attributes.
    */
    function testDraftDefaultAttributes() {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->addUrlParameters(array('ct' => 'DEFECT'));
        $widget = $this->createWidgetInstance();
        $widget->data['attrs']['show_draft'] = true;
        $data = $this->getWidgetData();
        $this->assertNotNull($data['articles'][0]['encryptedUrl']);
        $this->assertIsA($data['articles'], 'Array');
        $this->assertTrue(count($data['articles']) <= $data['attrs']['per_page']);
        $this->assertNotNull($data['articles']);
        $this->assertNotNull($data['articles'][0]['documentId']);
        $this->restoreUrlParameters();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    /**
    * UnitTest case to test empty result set.
    */
    function testEmptyResultSet(){
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->addUrlParameters(array('ct' => 'CATEGORY_TEST'));
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertIdentical(0, count($data['articles']));
        $this->restoreUrlParameters();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    /**
    * UnitTest case to test Products and categories.
    */
    function testProductCategory(){
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->addUrlParameters(array('productRecordID' => 'WINDOWS', 'isProductSelected' => 'true', 'categoryRecordID' => 'LINUX', 'isCategorySelected' => 'true', 'ct' => 'CATEGORY_TEST'));
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertNotNull($data['js']['filters']['category']);
        $this->restoreUrlParameters(); 
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    /**
    * UnitTest case to test view type.
    */
    function testViewType(){
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertNotNull($data['js']['articles']);
        $this->assertNotNull($data['js']['filters']);
        $this->assertNotNull($data['js']['answerUrl']);
        $this->assertNotNull($data['js']['viewType']);
        $this->assertNotNull($data['js']['filters']['truncate']);
        if ($data['attrs']['view_type'] === 'table') {
            $this->assertTrue(count($data['js']['headers']) === count(explode("|", $data['attrs']['display_fields'])));
            $this->assertNotNull($data['fields']['0']['columnID']);
            $this->assertNotNull($data['js']['sortDirection']);
            $this->assertNotNull($data['js']['headers']);
        }
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }
}
