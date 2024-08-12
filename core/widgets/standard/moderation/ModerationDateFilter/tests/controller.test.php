<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ModerationDateFilter extends WidgetTestCase {

    public $testingWidget = "standard/moderation/ModerationDateFilter";

    function testGetData () {
        $this->createWidgetInstance(array('report_filter_name' => 'questions.updated', 'report_id' => 15100));
        $data = $this->getWidgetData();
        $this->assertTrue($data["js"]["default_value"] === "last_90_days", 'The default date option should be last_90_days');
        $this->assertTrue(count($data["js"]["options"]) === 5, '5 date options should be enabled');
    }

    function testDateFilterOptionsNone () {
        $widget = $this->createWidgetInstance(array('date_filter_options' => 'none'));
        $this->assertFalse($widget->getData());
    }

    function testGetDataWithCustomizedDateOptions () {
        $this->createWidgetInstance(array('report_filter_name' => 'questions.updated', 'report_id' => 15100, 'date_filter_options' => 'all,last_24_hours,'));
        $data = $this->getWidgetData();
        $this->assertTrue(count($data["js"]["options"]) === 3, '3 date options should be enabled');
    }

    function testGetDataWithValidDateRangeInUrl () {
        $this->createWidgetInstance(array('report_filter_name' => 'questions.updated', 'report_id' => 15100, 'date_filter_options' => 'all,last_24_hours,'));
        $dateRange = '12/03/2013|03/03/2014';
        $this->addUrlParameters(array("questions.updated" => $dateRange));
        $data = $this->getWidgetData();
        $this->assertNotNull($data["js"]["options"]["custom"], 'Custom should be enabled');
        $this->assertEqual($data["js"]["urlDateValue"] , $dateRange, "Value should be $dateRange");
        $this->restoreUrlParameters();
    }

    function testGetDataWithInValidDateRangeInUrl () {
        $this->createWidgetInstance(array('report_filter_name' => 'questions.updated', 'report_id' => 15100, 'date_filter_options' => 'all,last_24_hours,'));
        $dateRange = 'invalid|12/24/2014';
        $this->addUrlParameters(array("questions.updated" => $dateRange));
        $data = $this->getWidgetData();
        $this->assertNull($data["js"]["options"]["custom"], 'Custom should not be enabled');
        $this->assertEqual($data["js"]["urlDateValue"] , $data['js']['urlDateValue'], "Value should be the default date option");
        $this->restoreUrlParameters();
    }

    function testGetDefaultFilterValue(){
        $instance = $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $optionLabels = $instance->helper('Social')->formatListAttribute($data['attrs']['date_filter_option_labels']);
        $method = $this->getWidgetMethod('getDefaultFilterValue', $instance);
        $filterMetaDataResponse = new RightNow\Libraries\ResponseObject();
        $filterMetaDataResponse->result = array("default_value" => "DATE_ADD(SYSDATE(), -365, DAYS, 1)|", "data_type"=>4);
        $this->assertEqual("last_365_days", $method($filterMetaDataResponse,$optionLabels));
        $filterMetaDataResponse->result = array("default_value" => "DATE_ADD(SYSDATE(), -7, DAYS, 1)|", "data_type"=>4);
        $this->assertEqual("last_7_days", $method($filterMetaDataResponse,$optionLabels));
        $dateRange = RightNow\Utils\Text::validateDateRange("01/01/2010|12/30/2011", 'm/d/Y', "|", true);
        $filterMetaDataResponse->result = array("default_value" => $dateRange, "data_type"=>4);
        $this->assertEqual("01/01/2010|12/31/2011", $method($filterMetaDataResponse,$optionLabels));
    }
}
