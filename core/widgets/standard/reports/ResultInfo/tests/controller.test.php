<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestResultInfo extends WidgetTestCase
{
    public $testingWidget = "standard/reports/ResultInfo";

    function testGetDataWithDateRangeInUrl() {
        $this->createWidgetInstance(array('max_date_range_interval' => 'Invalid', 'report_id' => 15101));
        ob_start();
        $this->getWidgetData();
        $error = ob_get_contents();
        ob_end_clean();
        $this->assertTrue(strpos($error, "Widget Error:") !== false);
    }
}