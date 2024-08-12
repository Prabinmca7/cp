<?php

if (!class_exists('RightNow\Widgets\SourceResultListing')) {
    \RightNow\Internal\Utils\Widgets::requireWidgetControllerWithPathInfo(\RightNow\Internal\Libraries\Widget\Registry::getWidgetPathInfo('standard/searchsource/SourceResultListing'));
}

\RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class SummaryResultListingTest extends WidgetTestCase {
    public $testingWidget = 'standard/searchsource/SummaryResultListing';

    function testGetData() {
        \RightNow\Libraries\Search::clearCache();
        $query = 'phone';
        $this->addUrlParameters(array('kw' => $query));

        $widget = $this->createWidgetInstance(array());
        $widgetdata = $this->getWidgetData($widget);
        $results = $widgetdata['results'];
        $this->assertEqual($widgetdata['attrs']['per_page'], $results->filters['limit']['value']);
        $this->assertEqual($query, $results->filters['query']['value']);
        $this->assertTrue(count($results->results) > 0);

        $this->restoreUrlParameters();
    }

    function testGetAjaxResults() {
        $results = $this->callAjaxMethod('getAjaxResults', array(
            'sourceID' => 'KFSearch',
            'filters' => '{"query":{"value":"phone","key":"kw","type":"query"},"sort":{"value":null,"key":"sort","type":"sort"},"direction":{"value":null,"key":"dir","type":"direction"},"page":{"value":1,"key":"page","type":"page"},"category":{"value":null,"key":"c","type":"category"},"offset":{"value":0,"key":null,"type":"offset"}}',
            'w_id' => '31',
            'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
        ));
        $this->assertIsA($results->results, 'array');
        $this->assertIsA($results->html, 'string');
        $this->assertStringContains($results->html, '<li data-index');
    }
}
