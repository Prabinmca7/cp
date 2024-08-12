<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class SimpleSearchTest extends WidgetTestCase {
    public $testingWidget = "standard/search/SimpleSearch";

    function testAddParamsToUrl() {
        $this->addUrlParameters(array('user' => 123));
        $this->createWidgetInstance(array('add_params_to_url' => 'author:userFromUrl'));
        $data = $this->getWidgetData();
        $this->assertIdentical('123', $data['js']['url_parameters']['author']);
        $this->restoreUrlParameters();
    }

    function testGetUrlParameters() {
        $instance = $this->createWidgetInstance();
        $method = $this->getWidgetMethod('getUrlParameters', $instance);
        $this->assertIdentical(array(), $method('', array()));

        $this->addUrlParameters(array('user' => 123));
        $filterData = array('parameter' => 'p', 'object' => (object) array('ID' => 1, 'LookupName' => 'Mobile Phones'));
        $this->assertIdentical(array('author' => '123', 'p' => 1), $method('author:userFromUrl', $filterData));

        $this->restoreUrlParameters();
    }

    function testGetPlaceholderText() {
        $instance = $this->createWidgetInstance();
        $method = $this->getWidgetMethod('getPlaceholderText', $instance);
        $this->assertEqual('Search...', $method(array()));
        $filterData = array('parameter' => 'p', 'object' => (object) array('ID' => 1, 'LookupName' => 'Mobile Phones'));
        $this->assertEqual("Search in 'Mobile Phones'", $method($filterData));

        // No %s in label text
        $instance = $this->createWidgetInstance(array('label_filter_type_placeholder' => 'Search Me'));
        $method = $this->getWidgetMethod('getPlaceholderText', $instance);
        $this->assertEqual('Search Me', $method($filterData));
    }

    function testGetDataFromFilter() {
        $this->addUrlParameters(array('p' => 1));

        $instance = $this->createWidgetInstance();
        $method = $this->getWidgetMethod('getDataFromFilter', $instance);
        $results = $method();
        $this->assertEqual('p', $results['parameter']);
        $this->assertIsA($results['object'], 'RightNow\Connect\v1_4\ServiceProduct');

        // `filter_type` of none should return an empty array
        $instance = $this->createWidgetInstance(array('filter_type' => 'none'));
        $this->getWidgetData($instance);
        $method = $this->getWidgetMethod('getDataFromFilter', $instance);
        $this->assertIdentical(array(), $method());
        $this->restoreUrlParameters();

        // Test with categories
        $categoryID = 68;
        $this->addUrlParameters(array('c' => $categoryID));
        $instance = $this->createWidgetInstance(array('filter_type' => 'category'));
        $this->getWidgetData($instance);
        $method = $this->getWidgetMethod('getDataFromFilter', $instance);
        $results = $method();
        $this->assertIdentical('c', $results['parameter']);
        $this->assertIdentical($categoryID, $results['object']->ID);
        $this->restoreUrlParameters();

        // Non enduser visible category should return an empty array
        $nonVisibleCategoryID = 122;
        $this->addUrlParameters(array('c' => $nonVisibleCategoryID));
        $instance = $this->createWidgetInstance(array('filter_type' => 'category'));
        $this->getWidgetData($instance);
        $method = $this->getWidgetMethod('getDataFromFilter', $instance);
        $this->assertIdentical(array(), $method());

        $this->restoreUrlParameters();
    }
}