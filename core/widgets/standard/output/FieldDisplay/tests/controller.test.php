<?php

use RightNow\UnitTest\Helper,
    RightNow\Connect\v1_4 as ConnectPHP,
    RightNow\Utils\Connect;

Helper::loadTestedFile(__FILE__);

class TestFieldDisplay extends WidgetTestCase {
    public $testingWidget = 'standard/output/FieldDisplay';

    function __construct() {
        parent::__construct();
    }

    function testFieldShouldBeDisplayed() {
        $parameters = array('name' => 'Incident.CustomFields.c.priority');
        $instance = $this->createWidgetInstance($parameters);
        $data = $this->getWidgetData($instance);
        $method = $this->getWidgetMethod('fieldShouldBeDisplayed', $instance);

        // null values should not display
        $this->assertFalse($method($data['value'], 'String'));
        $this->assertFalse($method(null, 'String'));

        // empty string should not display
        $this->assertFalse($method('', 'String'));

        // Truthy types should display
        $this->assertTrue($method(true, 'Boolean'));
        $this->assertTrue($method(1, 'Integer'));
        $this->assertTrue($method('grog', 'String'));

        // Menu
        $this->assertTrue($method((object) array('ID' => 1), 'Menu'));
        $this->assertFalse($method((object) array('ID' => null), 'Menu'));

        // namedIDType
        $this->assertFalse($method((object) array('ID' => null, 'COM_type' => 'NamedIDOptList'), 'NamedID'));
        $this->assertTrue($method((object) array('ID' => 1, 'COM_type' => 'NamedIDOptList'), 'NamedID'));

        // slaInstance
        $this->assertFalse($method(new ConnectPHP\SLAInstance(), 'SlaInstance'));
        $this->assertTrue($method((object) array('ID' => 1), 'SlaInstance'));

        // Asset
        $this->assertFalse($method(new ConnectPHP\Asset(), 'Asset'));
        $this->assertTrue($method((object) array('ID' => 1), 'Asset'));
    }

    function testFieldShouldBeDisplayedForCountryType() {
        $parameters = array('name' => 'Contact.Address.Country');
        $instance = $this->createWidgetInstance($parameters);
        $data = $this->getWidgetData($instance);
        $method = $this->getWidgetMethod('fieldShouldBeDisplayed', $instance);

        // null ID should not display
        $this->assertFalse($method($data['value'], 'Country'));

        // Populated ID should display
        $this->assertTrue($method((object) array('ID' => 1), 'Country'));
    }

    function testFieldIsValid() {
        $parameters = array('name' => 'Incident.CustomFields.c.priority');
        $instance = $this->createWidgetInstance($parameters);
        $data = $this->getWidgetData($instance);
        $method = $this->getWidgetMethod('fieldIsValid', $instance);

        // Valid 'Boolean' field
        $this->assertTrue($method($data['values'], 'Boolean'));

        // Valid non-object values
        $this->assertTrue($method(1, 'Boolean'));
        $this->assertTrue($method(0, 'Boolean'));
        $this->assertTrue($method(true, 'Boolean'));
        $this->assertTrue($method(false, 'Boolean'));
        $this->assertTrue($method('1', 'String'));
        $this->assertTrue($method('0', 'String'));
        $this->assertTrue($method('aString', 'String'));
        $this->assertTrue($method(array(), 'Whatzit'));
        $this->assertTrue($method(array(1, 2, 3), 'Whatzit'));

        // Valid 'Country' field
        $parameters = array('name' => 'Contact.Address.Country');
        $instance = $this->createWidgetInstance($parameters);
        $data = $this->getWidgetData($instance);
        $method = $this->getWidgetMethod('fieldIsValid', $instance);
        $this->assertTrue($method($data['value'], 'Country'));

        // Valid namedIDType
        $this->assertTrue($method((object) array('COM_type' => 'NamedIDOptList'), 'NamedID'));

        // Valid slaInstance
        $this->assertTrue($method(new ConnectPHP\SLAInstance(), 'SlaInstance'));

        // Valid Asset
        $this->assertTrue($method(new ConnectPHP\Asset(), 'Asset'));

        // Valid custom menu only attribute
        $this->assertTrue($method(1, 'Menu'));

        // Invalid object
        $this->assertFalse($method((object) array('COM_type' => 'SomethingElse'), 'SomethingElse'));
    }

    function testGetValueType() {
        $parameters = array('name' => 'Contact.Address.Country');
        $instance = $this->createWidgetInstance($parameters);
        $data = $this->getWidgetData($instance);
        $method = $this->getWidgetMethod('getValueType', $instance);
        $this->assertEqual('Country', $method($data['value'], $instance->fieldMetaData));
        $this->assertEqual('Menu', $method(1, (object) array('is_menu' => true)));
        $this->assertEqual('NamedID', $method((object) array('COM_type' => 'NamedIDOptList'), (object) array()));
        $this->assertEqual('SlaInstance', $method(new ConnectPHP\SLAInstance(), (object) array()));
        $this->assertEqual('Asset', $method(new ConnectPHP\Asset(), (object) array()));
        $this->assertEqual('SomethingElse', $method('?', (object) array('COM_type' => 'SomethingElse')));
    }
}