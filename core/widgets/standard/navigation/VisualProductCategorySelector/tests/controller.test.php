<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\Utils\Url;

class TestVisualProductCategorySelector extends WidgetTestCase {
    public $testingWidget = "standard/navigation/VisualProductCategorySelector";

    function __construct() {
        parent::__construct();
    }

    function testGetData() {
        //Only show sub items for 'p1' 
        $this->createWidgetInstance(array('show_sub_items_for' => 128, 'prefetch_sub_items' => false, 'prefetch_sub_items_non_ajax' => true));
        $widgetData = $this->getWidgetData();

        //Make sure items only contains sub items for 'p1'
        $this->assertEqual($widgetData['js']['appendedParameters'], Url::getParametersFromList($this->data['attrs']['add_params_to_url']) . Url::sessionParameter());
        $this->assertNotNull($widgetData['js']['items']);
        $this->assertEqual($widgetData['js']['items'], array('0' => array('id' => 132, 'label' => "p1a", 'hasChildren' => false),
            array('id' => 133, 'label' => "p1b", 'hasChildren' => false),
            array('id' => 129, 'label' => "p2", 'hasChildren' => true)
        ));

        //Sub items are fetched without AJAX request
        $this->assertNotNull($widgetData['js']['subItems']);
        $this->assertEqual($widgetData['js']['subItems'], array('129' => array('0' => array('id' => 134, 'label' => "p2a", 'hasChildren' => false), 
            '1' => array('id' => 135, 'label' => "p2b", 'hasChildren' => false),
            '2' => array('id' => 130, 'label' => "p3", 'hasChildren' => true))
        ));
    }

    function testLimitItems() {
        $widget = $this->createWidgetInstance();
        $widgetData = $this->getWidgetData();
        //Items are limited to Mobile Phones and p1
        $limitedData = $widget->limitItems($widgetData['js']['items'], '1,128', 6);
        $this->assertSame(2, count($limitedData));
        $this->assertSame(1, $limitedData[0]['id']);
        $this->assertSame(128, $limitedData[1]['id']);

        //Last two items are chopped off
        $choppedData = $widget->limitItems($widgetData['js']['items'], '', 4);
        $this->assertSame(4, count($choppedData));
    }

    function testGetSubItems() {
        $widget = $this->createWidgetInstance();
        //Get Mobile Phone sub items
        $subItems = $widget->getSubItems(1, 'product', false);
        $this->assertNotNull($subItems->result);
        $this->assertEqual($subItems->result[0], array('0' => array('id' => 2, 'label' => "Android", 'hasChildren' => true),
            '1' => array('id' => 3, 'label' => "Blackberry", 'hasChildren' => false),
            '2' => array('id' => 4, 'label' => "iPhone", 'hasChildren' => true)
        ));
    }
}