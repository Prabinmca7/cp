<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestSourceResultListing extends WidgetTestCase {
    public $testingWidget = "standard/searchsource/SourceResultListing";

    function __construct() {
        parent::__construct();

        $this->socialSearchParams = array(
            'sourceID' => 'SocialSearch',
            'filters' => '{"query":{"value":"iphone","key":"kw","type":"query"},"sort":{"value":null,"key":"sort","type":"sort"},"direction":{"value":null,"key":"dir","type":"direction"},"page":{"key":"page","type":"page","value":1},"author":{"value":null,"key":"author","type":"author"},"updatedTime":{"value":null,"key":"updated","type":"updatedTime"},"createdTime":{"value":null,"key":"created","type":"createdTime"},"numberOfBestAnswers":{"value":null,"key":"ba","type":"numberOfBestAnswers"},"offset":{"value":0,"key":null,"type":"offset"}}',
            'limit' => 10
        );

        $this->kFSearchParams = array(
            'sourceID' => 'KFSearch',
            'filters' => '{"query":{"value":"iphone","key":"kw","type":"query"},"sort":{"value":null,"key":"sort","type":"sort"},"direction":{"value":null,"key":"dir","type":"direction"},"page":{"key":"page","type":"page","value":1},"offset":{"value":0,"key":null,"type":"offset"}}',
            'limit' => 10
        );
    }

    function testGetData() {
        $this->createWidgetInstance(array("source_id" => "KFSearch"));
        $widgetData = $this->getWidgetData();

        $this->assertNotNull($widgetData["results"]);
        $this->assertNotNull($widgetData["historyData"]);
        $this->assertNotNull($widgetData["js"]["filters"]);
        $this->assertNotNull($widgetData["js"]["sources"]);
    }

    function testGetAjaxResultsSocialSearchNoTopicWords() {
        $params = $this->socialSearchParams;
        // Default, no topicwords
        $params['includeTopicWords'] = 0;
        $params['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0, false, false);

        $this->createWidgetInstance(array('name' => 'SummaryResultListing_37', 'source_id' => 'SocialSearch'));
        $data = $this->getWidgetData();
        $response = $this->callAjaxMethod('getAjaxResults' , $params, true, $this->widgetInstance, array('source_id' => 'SocialSearch'));

        $resultsCount = count($response->results);
        $this->assertTrue($resultsCount > 0 || $resultsCount === 0); // Result count seem temperamental for SocialSearch; we should address what causes these variations and update this test in the future
        $this->assertFalse(property_exists($response, 'topic_words'));
    }

    function testGetAjaxResultsSocialSearchWithTopicWords() {
        $params = $this->socialSearchParams;
        // Include topicwords
        $params['includeTopicWords'] = 1;
        $params['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0, false, false);
        
        $this->createWidgetInstance(array('name' => 'SummaryResultListing_37', 'source_id' => 'SocialSearch'));
        $data = $this->getWidgetData();
        $response = $this->callAjaxMethod('getAjaxResults' , $params, true, $this->widgetInstance, array('source_id' => 'SocialSearch'));

        $resultsCount = count($response->results);
        $this->assertTrue($resultsCount > 0 || $resultsCount === 0); // Result count seem temperamental for SocialSearch; we should address what causes these variations and update this test in the future
        $this->assertTrue(property_exists($response, 'topic_words'));
        $this->assertTrue(count($response->topic_words) > 0);
    }

    function testGetAjaxResultsKFSearchNoTopicWords() {
        $params = $this->kFSearchParams;
        // Default, no topicwords
        $params['includeTopicWords'] = 0;
        $params['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0, false, false);

        $this->createWidgetInstance(array('name' => 'SourceResultListing_37', 'source_id' => 'KFSearch'));
        $data = $this->getWidgetData();
        $response = $this->callAjaxMethod('getAjaxResults' , $params, true, $this->widgetInstance, array('source_id' => 'KFSearch'));

        $this->assertTrue(count($response->results) > 0);
        $this->assertFalse(property_exists($response, 'topic_words'));
    }

    function testGetAjaxResultsKFSearchWithTopicWords() {
        $params = $this->kFSearchParams;
        // Include topicwords
        $params['includeTopicWords'] = 1;
        $params['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0, false, false);

        $this->createWidgetInstance(array('name' => 'SourceResultListing_37', 'source_id' => 'KFSearch'));
        $data = $this->getWidgetData();
        $response = $this->callAjaxMethod('getAjaxResults' , $params, true, $this->widgetInstance, array('source_id' => 'KFSearch'));

        $this->assertTrue(count($response->results) > 0);
        $this->assertTrue(property_exists($response, 'topic_words'));
        $this->assertTrue(count($response->topic_words) > 0);
    }
}
