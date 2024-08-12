<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestProductCategoryListWidget extends WidgetTestCase
{
    public $testingWidget = "standard/search/ProductCategoryList";

    function testGetData() {
        $this->createWidgetInstance();
        $this->setWidgetAttributes(array('default_value' => 11));
        $data = $this->getWidgetData();
        $products = $data['results'];
        $this->assertEqual(3, count($products));
        $this->assertEqual('Mobile Phones', $products[0][0]['label']);
        $this->assertEqual('Voice Plans', $products[0][1]['label']);
        $this->assertEqual('Text Messaging', $products[1][0]['label']);
        $this->assertEqual('Mobile Broadband', $products[1][1]['label']);
        $this->assertEqual('Replacement/Repair Coverage', $products[2][0]['label']);
        $this->assertEqual('p1', $products[2][1]['label']);
    }

    function testOnlyDisplayAttribute() {
        $this->createWidgetInstance();
        $this->setWidgetAttributes(array('only_display' => "1,6,163"));
        $data = $this->getWidgetData();
        $products = $data['results'];
        $this->assertEqual(3, count($products));
        $this->assertEqual('Mobile Phones', $products[0][0]['label']);
        $this->assertEqual('Voice Plans', $products[1][0]['label']);
        $this->assertEqual('Mobile Broadband', $products[2][0]['label']);
    }
}
