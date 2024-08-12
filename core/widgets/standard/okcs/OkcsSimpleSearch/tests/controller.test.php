<?php
require_once CORE_WIDGET_FILES . 'standard/search/SimpleSearch/controller.php';

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class OkcsSimpleSearchTest extends WidgetTestCase {
    public $testingWidget = "standard/okcs/OkcsSimpleSearch";

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
        $filterData = array('parameter' => 'categoryRecordID', 'object' => (object) array('recordId' => 'MOBILE_PHONES', 'name' => 'Mobile Phones'));
        $this->assertIdentical(array('author' => '123', 'categoryRecordID' => 'Mobile Phones'), $method('author:userFromUrl', $filterData));

        $this->restoreUrlParameters();
    }

    function testGetPlaceholderText() {
        $instance = $this->createWidgetInstance();
        $method = $this->getWidgetMethod('getPlaceholderText', $instance);
        $this->assertEqual('Search...', $method(array()));
        $filterData = array('parameter' => 'categoryRecordID', 'object' => (object) array('recordId' => '08202020184a61f5014b5539099f007fdc', 'name' => 'Linux'));
        $this->assertEqual("Search in 'Linux'", $method($filterData));

        // No %s in label text
        $instance = $this->createWidgetInstance(array('label_filter_type_placeholder' => 'Search Me'));
        $method = $this->getWidgetMethod('getPlaceholderText', $instance);
        $this->assertEqual('Search Me', $method($filterData));
    }

    function testGetDataFromFilter() {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->addUrlParameters(array('categoryRecordID' => 'RN_PRODUCT_1'));

        $instance = $this->createWidgetInstance();
        $method = $this->getWidgetMethod('getDataFromFilter', $instance);
        $results = $method();
        $this->assertEqual('categoryRecordID', $results['parameter']);

        //'filter_type' of none should return an empty array
        $instance = $this->createWidgetInstance(array('filter_type' => 'none'));
        $this->getWidgetData($instance);
        $method = $this->getWidgetMethod('getDataFromFilter', $instance);
        $this->assertIdentical(array(), $method());
        $this->restoreUrlParameters();

        // Test with products
        $categoryID = 'RN_PRODUCT_1';
        $this->addUrlParameters(array('categoryRecordID' => $categoryID));
        $instance = $this->createWidgetInstance(array('filter_type' => 'product'));
        $this->getWidgetData($instance);
        $method = $this->getWidgetMethod('getDataFromFilter', $instance);
        $results = $method();
        $this->assertIdentical('categoryRecordID', $results['parameter']);
        $this->assertIdentical($categoryID, $results['object']->referenceKey);
        $this->assertIdentical('PRODUCT', $results['object']->externalType);
        $this->assertIdentical('Iphone', $results['object']->name);
        $this->restoreUrlParameters();

        // Test with categories
        $categoryID = 'RN_CATEGORY_1';
        $this->addUrlParameters(array('categoryRecordID' => $categoryID));
        $instance = $this->createWidgetInstance(array('filter_type' => 'category'));
        $this->getWidgetData($instance);
        $method = $this->getWidgetMethod('getDataFromFilter', $instance);
        $results = $method();
        $this->assertIdentical('categoryRecordID', $results['parameter']);
        $this->assertIdentical($categoryID, $results['object']->referenceKey);
        $this->assertIdentical('CATEGORY', $results['object']->externalType);
        $this->assertIdentical('OPERATING_SYSTEM', $results['object']->name);
        $this->restoreUrlParameters();

        $this->restoreUrlParameters();
    }

    function testGetHierarchyObjectForCategory() {
        $instance = $this->createWidgetInstance();
        $method = $this->getWidgetMethod('getHierarchyObjectForCategory', $instance);
        $categoryRecordID = 'RN_PRODUCT_1';
        $results = $method($categoryRecordID);
        $this->assertIdentical('Iphone', $results[0]['label']);
    }
}
