<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestGridReport extends WidgetTestCase
{
    public $testingWidget = "standard/reports/Grid";

    function testParseSanitizeDataToArray() {
        $instance = $this->createWidgetInstance();

        $attributeSanitizeData = '3|text/x-markdown, 4|text/html';
        $expectedData = array(3 => 'text/x-markdown', 4 => 'text/html');

        $parseSanitizeDataToArray = $this->getWidgetMethod('parseSanitizeDataToArray');
        $result = $parseSanitizeDataToArray($attributeSanitizeData);
        $this->assertEqual($result, $expectedData);
    }
    
    function testGetData() {
        $this->logIn('slatest');
        $this->createWidgetInstance();
        $this->setWidgetAttributes(array("report_id"=>15100));
        $data = $this->getWidgetData();
        $this->assertNotNull($data['js']['columnID']);
        $this->assertNotNull($data['js']['sortDirection']);
    }    
    
    function testGetDataWithDateRangeInUrl() {
        $this->createWidgetInstance(array('max_date_range_interval' => '90 days', 'report_id' => 15100));
        $dateRange = '12/03/2013|01/01/2014';
        $this->addUrlParameters(array("questions.updated" => $dateRange));
        $data = $this->getWidgetData();
        $this->assertNotNull($data["js"]["filters"]["questions.updated"]->filters->data);
        $this->restoreUrlParameters();

        $this->createWidgetInstance(array('max_date_range_interval' => '90 days', 'report_id' => 15100));
        $dateRange = '12/03/2013|12/01/2014';
        $this->addUrlParameters(array("questions.updated" => $dateRange));
        $data = $this->getWidgetData();
        $this->assertNull($data["js"]["filters"]["questions.updated"]->filters->data);
        $this->restoreUrlParameters();
        
        $this->createWidgetInstance(array('max_date_range_interval' => '90 days', 'report_id' => 15100));
        $dateRange = 'last_24_hours';
        $this->addUrlParameters(array("questions.updated" => $dateRange));
        $data = $this->getWidgetData();
        $this->assertNotNull($data["js"]["filters"]["questions.updated"]->filters->data);
        $this->restoreUrlParameters();
        
        $this->createWidgetInstance(array('max_date_range_interval' => '90 days', 'report_id' => 15100));
        $dateRange = 'invalid';
        $this->addUrlParameters(array("questions.updated" => $dateRange));
        $data = $this->getWidgetData();
        $this->assertNull($data["js"]["filters"]["questions.updated"]->filters->data);
        $this->restoreUrlParameters();

        $this->createWidgetInstance(array('max_date_range_interval' => '90 days', 'report_id' => 15101));
        $dateRange = '12/03/2013|01/01/2014';
        $this->addUrlParameters(array("comments.updated" => $dateRange));
        $data = $this->getWidgetData();
        $this->assertNotNull($data["js"]["filters"]["comments.updated"]->filters->data);
        $this->restoreUrlParameters();

        $this->createWidgetInstance(array('max_date_range_interval' => '90 days', 'report_id' => 15101));
        $dateRange = '12/03/2013|12/01/2014';
        $this->addUrlParameters(array("comments.updated" => $dateRange));
        $data = $this->getWidgetData();
        $this->assertNull($data["js"]["filters"]["comments.updated"]->filters->data);
        $this->restoreUrlParameters();
        
        $this->createWidgetInstance(array('max_date_range_interval' => '90 days', 'report_id' => 15101));
        $dateRange = 'last_24_hours';
        $this->addUrlParameters(array("comments.updated" => $dateRange));
        $data = $this->getWidgetData();
        $this->assertNotNull($data["js"]["filters"]["comments.updated"]->filters->data);
        $this->restoreUrlParameters();
        
        $this->createWidgetInstance(array('max_date_range_interval' => '90 days', 'report_id' => 15101));
        $dateRange = 'invalid';
        $this->addUrlParameters(array("comments.updated" => $dateRange));
        $data = $this->getWidgetData();
        $this->assertNull($data["js"]["filters"]["comments.updated"]->filters->data);
        $this->restoreUrlParameters();        
        
        $this->createWidgetInstance(array('max_date_range_interval' => 'Invalid', 'report_id' => 15101));
        ob_start();
        $data = $this->getWidgetData();
        $error =  ob_get_contents();
        ob_end_clean();
        $this->assertTrue(strpos($error, "Widget Error:") !== false);
    }
}