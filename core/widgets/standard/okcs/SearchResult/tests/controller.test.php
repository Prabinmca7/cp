<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Text as Text;

class TestSearchResult extends WidgetTestCase
{
    public $testingWidget = "standard/okcs/SearchResult";

    /*
    * UnitTest case to test default attributes.
    */
    function testDefaultAttributes()
    {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_SRCH_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsSrt/api/', true);
        \Rnow::updateConfig('CP_ANSWERS_DETAIL_URL', 'answers/answer_view', true);
        \RightNow\Libraries\Search::clearCache();
        $this->addUrlParameters(array('kw' => 'Windows'));
        $widget = $this->createWidgetInstance();
        $widget->data['attrs']['truncate_size'] = 15;
        $widget->data['attrs']['source_id'] = 'OKCSSearch';
        $data = $this->getWidgetData();
        $this->assertIsA($data['results'],'Array');
        foreach ($data['results'] as $value) {
            $this->assertNotNull($value->title);
            $this->assertNotNull($value->docId);
            $this->assertNotNull($value->fileType);
            $this->assertNotNull($value->href);
            $this->assertNotNull($value->clickThroughUrl);
        }
        $this->restoreUrlParameters();
        $this->assertSame('/app/answers/answer_view', $data['js']['answerPageUrl']);
        $this->assertSame('MS Powerpoint file', $data['fileDescription']['ms_powerpoint']);
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
        \Rnow::updateConfig('CP_ANSWERS_DETAIL_URL', 'answers/detail', true);
    }

    /**
    * UnitTest case to test Multiple source Ids
    */
    function testMultipleSourceIds()
    {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_SRCH_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsSrt/api/', true);
        
        $this->createWidgetInstance(array('source_id' => 'OKCSSearch,OKCSBrowse'));
        $getData = $this->getWidgetMethod('getData');
        list($result, $content) = $this->returnResultAndContent($getData);
        $this->assertFalse($result);
        $this->assertTrue(Text::stringContains($content, 'Widget Error: standard/okcs/SearchResult - This widget only supports a single value for its source_id attribute'));
        
        $this->restoreUrlParameters();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }
}
