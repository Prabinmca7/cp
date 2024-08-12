<?php

use RightNow\Utils\Text as Text;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestBasicSearchFilters extends WidgetTestCase
{
    public $testingWidget = "standard/search/BasicDisplaySearchFilters";

    function testGetData()
    {
        \RightNow\Utils\Framework::removeCache("filtersFromUrl176");

        $this->addUrlParameters(array('c' => '77', 'p' => '3'));

        $this->createWidgetInstance(array('report_id' => 123456));
        $data = $this->getWidgetData();

        $this->assertTrue(Text::stringContains($data['filters'][0]['data'][0]['linkUrl'], '/p/'));
        $this->assertFalse(Text::stringContains($data['filters'][0]['data'][0]['linkUrl'], '/c/'));

        $this->assertTrue(Text::stringContains($data['filters'][1]['data'][0]['linkUrl'], '/c/'));
        $this->assertFalse(Text::stringContains($data['filters'][1]['data'][0]['linkUrl'], '/p/'));

        $this->restoreUrlParameters();
    }
}
