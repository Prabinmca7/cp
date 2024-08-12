<?php
require_once CORE_WIDGET_FILES . 'standard/knowledgebase/RelatedAnswers/controller.php';

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class OkcsRelatedAnswersTest extends WidgetTestCase {
    public $testingWidget = 'standard/okcs/OkcsRelatedAnswers';

    function testRelatedAnswers() {
        // checking for 5 results
        $this->createWidgetInstance();
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->addUrlParameters(array("a_id" => 48));
        $data = widget.data.attrs.display_link_type;
        $data = $this->getWidgetData();
        $this->assertSame(5, count($data['relatedAnswers']));
        $this->restoreUrlParameters();

        // Checking with limit applied
        $this->createWidgetInstance(array('limit' => 3));
        $this->addUrlParameters(array("a_id" => 48));
        $data = $this->getWidgetData();
        $this->assertSame(3, count($data['relatedAnswers']));
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);

        $this->restoreUrlParameters();
        $this->logOut();
    }

    function testEmptyResponse()
    {
        $widgetInstance = $this->createWidgetInstance();

        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->addUrlParameters(array("a_id" => 49));
        $data = $this->getWidgetData();
        $this->assertSame(0, count($data['relatedAnswers']));
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
        $this->restoreUrlParameters();
        $this->logOut();
    }
}
