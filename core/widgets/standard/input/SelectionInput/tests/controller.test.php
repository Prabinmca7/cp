<?php

use RightNow\UnitTest\Helper;

Helper::loadTestedFile(__FILE__);

class TestSelectionInput extends WidgetTestCase {
    public $testingWidget = 'standard/input/SelectionInput';

    function __construct() {
        parent::__construct();
        $this->reflectionClass = new ReflectionClass('RightNow\Widgets\SelectionInput');
        $this->reflectionInstance = $this->reflectionClass->newInstance(array());
    }

    function setInstanceProperty($propertyName, $propertyValue){
        Helper::setInstanceProperty($this->reflectionClass, $this->reflectionInstance, $propertyName, $propertyValue);
    }

    function testIsValidField() {
        // Valid 'Boolean' field
        $parameters = array('name' => 'Incident.CustomFields.c.priority');
        $instance = $this->createWidgetInstance($parameters);
        $this->getWidgetData($instance);
        $isValidField = $this->getWidgetMethod('isValidField');
        $this->assertEqual('NamedIDLabel', $instance->dataType);
        $this->assertTrue($isValidField($parameters));

        // Invalid 'Integer' field
        $parameters = array('name' => 'Incident.CustomFields.c.int1');
        ob_start(); // Suppress "Widget Error: - 'int1' is not a Menu or Yes/No field"
        $instance = $this->createWidgetInstance($parameters);
        $this->getWidgetData($instance);
        $isValidField = $this->getWidgetMethod('isValidField');
        ob_end_clean();
        $this->assertEqual('Integer', $instance->dataType);
        $this->assertFalse($isValidField($parameters));

        // Valid custom menu only attribute
        $this->setInstanceProperty('dataType', 'Menu');
        $this->assertTrue($this->reflectionInstance->isValidField());
    }

    function testOutputSelected() {
        $selected = 'selected="selected"';

        // // Menu
        $this->setInstanceProperty('dataType', 'Menu');
        $this->setInstanceProperty('data',  array('value' => (object) array('ID' => 1)));
        $this->assertEqual($selected, $this->reflectionInstance->outputSelected(1));
        $this->assertEqual($selected, $this->reflectionInstance->outputSelected('1'));
        $this->assertNull($this->reflectionInstance->outputSelected(2));
        $this->assertNull($this->reflectionInstance->outputSelected('2'));

        // Incident status - Ignore value and only set as selected when the key is 0
        $this->setInstanceProperty('table', 'Incident');
        $this->setInstanceProperty('fieldName', 'Status');
        $this->setInstanceProperty('data', array('value' => 1, 'displayType' => 'Select'));
        $this->assertEqual($selected, $this->reflectionInstance->outputSelected(0));
        $this->assertNull($this->reflectionInstance->outputSelected('0'));
        $this->assertNull($this->reflectionInstance->outputSelected(1));

        // General
        $this->setInstanceProperty('data',  array('value' => 1));
        $this->setInstanceProperty('dataType', 'Whatever');
        $this->assertEqual($selected, $this->reflectionInstance->outputSelected(1));
        $this->assertEqual($selected, $this->reflectionInstance->outputSelected('1'));
        $this->assertNull($this->reflectionInstance->outputSelected(null));
        $this->assertNull($this->reflectionInstance->outputSelected(''));
        $this->assertNull($this->reflectionInstance->outputSelected(0));

        $this->setInstanceProperty('data',  array('value' => null));
        $this->assertNull($this->reflectionInstance->outputSelected(1));
        $this->assertEqual($selected, $this->reflectionInstance->outputSelected(0));
        $this->assertEqual($selected, $this->reflectionInstance->outputSelected(null));
        $this->assertEqual($selected, $this->reflectionInstance->outputSelected(''));
    }

    function testIsValidMenuItems() {
        $parameters= array('dataType' => 'Menu', 'name' => 'Incident.CustomFields.c.priority');
        $instance = $this->createWidgetInstance($parameters);
        $instance->fieldMetaData = new \StdClass();
        $instance->fieldMetaData->named_values = array((object) array('ID' => 1, 'LookupName' => '0'), (object) array('ID' => 2, 'LookupName' => 'abc'));
        $getMenuItems = $this->getWidgetMethod('getMenuItems');
        $menuItems = $getMenuItems();
        $this->assertEqual('0', $menuItems[1]);
        $this->assertEqual('abc', $menuItems[2]);
    }
}