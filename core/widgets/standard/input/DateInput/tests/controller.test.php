<?php

use RightNow\Utils\Config;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class DateInputTest extends WidgetTestCase {
    public $testingWidget = 'standard/input/DateInput';

    function __construct() {
        $this->name = 'Contact.CustomFields.c.datetime1';
        $this->createWidgetInstance(array('name' => $this->name));
        $this->instance = $this->getWidgetInstance(array('name' => $this->name));
        $this->data = $this->getWidgetData($this->instance);
        $this->min = array();
        $this->max = array();
        $this->setDates();
        parent::__construct();
    }

    private function setDates() {
        $dates = array(
            'min' => array('year' => Config::getMinYear(), 'date' => MIN_DATE),
            'max' => array('year' => Config::getMaxYear(), 'date' => MAX_DATE),
        );
        foreach ($dates as $type => $data) {
            list($yearMonthDay, $time) = explode(' ', $data['date']);
            list(, $month, $day) = explode('-', $yearMonthDay);
            $this->{$type}['year'] = $year = $data['year'];
            $this->{$type}['month'] = $month = ltrim($month, 0);
            $this->{$type}['day'] = $day = ltrim($day, 0);
            $this->{$type}['time'] = $time;
            $this->{$type}['date'] = "{$data['year']}-{$month}-{$day} {$time}";
        }
    }

    function testGetData() {
        $data = $this->data;
        $this->assertEqual('datetime1', $data['attrs']['label_input']);
        $this->assertEqual($this->name, $data['inputName']);
        $this->assertEqual($this->name, $data['attrs']['name']);
        $this->assertEqual('DateTime', $data['js']['type']);
        $this->assertEqual('DateTime', $data['displayType']);
        $this->assertEqual($this->name, $data['js']['name']);
        $this->assertEqual($this->min['year'], $data['attrs']['min_year']);
        $this->assertEqual($this->max['year'], $data['attrs']['max_year']);
        $minYear = "{$this->min['month']}/{$this->min['day']}/{$this->min['year']} {$this->min['time']}";
        $this->assertEqual($minYear, $data['js']['min_val']);
        $this->assertEqual(strtotime($this->min['date']), $data['constraints']['minValue']);
        $this->assertEqual(strtotime($this->max['date']), $data['constraints']['maxValue']);
        // @@@ 140926-000082 - Current default min date is 1/3/1970
        $this->assertEqual($this->min['day'], $data['js']['min']['day']);
        $this->assertEqual($this->min['month'], $data['js']['min']['month']);
        $this->assertEqual($this->min['year'], $data['js']['min']['year']);
        $this->assertEqual(3, $data['js']['min']['day']);
        $this->assertEqual(1, $data['js']['min']['month']);
        $this->assertEqual(1970, $data['js']['min']['year']);

        // @@@ 210201-000075 check timestamp parsing for London DST
        $tz = \Rnow::getConfig(TZ_INTERFACE);
        try {
            date_default_timezone_set('Europe/London');

            // use a timestamp for a summer date - this defect is related to DST
            $expectedDay = 7;
            $defaultValueInstance = $this->getWidgetInstance(array('name' => $this->name, 'default_value' => strtotime("6/$expectedDay/08")));
            $defaultValueData = $this->getWidgetData($defaultValueInstance);
            $this->assertIdentical($expectedDay, intval($defaultValueData['value'][1]));
        } finally {
            \Rnow::updateConfig('TZ_INTERFACE', $tz, true);
            date_default_timezone_set($tz);
        }

        // @@@ 210317-000172 use a default value to check that the custom datetime field is parsed correctly
        // the original date format string in the controller was 'm j Y G i' which lacks leading zeros on elements 1 and 3
        $expected = array('01', '2', '2003', '4', '05');
        $defaultValueInstance = $this->getWidgetInstance(array('name' => $this->name, 'default_value' => strtotime("1/2/03 4:05")));
        $defaultValueData = $this->getWidgetData($defaultValueInstance);
        $this->assertIdentical($expected, $defaultValueData['value']);
    }

    function testGetDateArray() {
        $getDateArray = $this->getWidgetMethod('getDateArray', $this->instance);
        $min = $getDateArray($this->min['year'], $this->min['date'], 'min');
        $this->assertEqual($min['year'], $this->min['year']);
        $this->assertEqual($min['month'], $this->min['month']);
        $this->assertEqual($min['day'], $this->min['day']);
        $this->assertEqual($min['time'], $this->min['time']);

        // @@@ 140926-000082 Test min_year attribute being set - should return 1/1 day/month if year > 1970, 1/3 for 1970
        $min = $getDateArray(1970, $this->min['date'], 'min');
        $this->assertEqual($min['year'], $this->min['year']);
        $this->assertEqual($min['month'], $this->min['month']);
        $this->assertEqual($min['day'], 3);
        $this->assertEqual($min['time'], $this->min['time']);

        $min = $getDateArray(2010, $this->min['date'], 'min');
        $this->assertEqual($min['year'], 2010);
        $this->assertEqual($min['month'], 1);
        $this->assertEqual($min['day'], 1);
        $this->assertEqual($min['time'], $this->min['time']);

        $max = $getDateArray($this->max['year'], $this->max['date'], 'max');
        $this->assertEqual($max['year'], $this->max['year']);
        $this->assertEqual($max['month'], $this->max['month']);
        $this->assertEqual($max['day'], $this->max['day']);
        $this->assertEqual($max['time'], $this->max['time']);
    }

    function testGetConstraints() {
        $getConstraints = $this->getWidgetMethod('getConstraints', $this->instance);
        $metaConstraints = array('min' => 86400, 'max' => 2147385599);
        $expected = array('min' => MIN_DATE, 'max' => MAX_DATE);
        $actual = $getConstraints($metaConstraints);
        $this->assertIdentical($expected, $actual);

        $metaConstraints = array('min' => '1958-01-15 00:00:00', 'max' => '2038-01-17 23:59:59');
        $expected = array('min' => '1958-01-15 00:00:00', 'max' => '2038-01-17 23:59:59');
        $actual = $getConstraints($metaConstraints);
        $this->assertIdentical($expected, $actual);

        $metaConstraints = array('min' => -2145916800, 'max' => 2147385599);
        $expected = array('min' => '1902-01-01 00:00:00', 'max' => '2038-01-17 23:59:59');
        $actual = $getConstraints($metaConstraints);
        $this->assertIdentical($expected, $actual);

        $metaConstraints = array('min' => '1902-01-01 00:00:00', 'max' => '2038-01-17 23:59:59');
        $expected = array('min' => '1902-01-01 00:00:00', 'max' => '2038-01-17 23:59:59');
        $actual = $getConstraints($metaConstraints);
        $this->assertIdentical($expected, $actual);
    }

    function testGetMetaConstraints() {
        $getMetaConstraints = $this->getWidgetMethod('getMetaConstraints', $this->instance);
        $expected = array('min' => 86400, 'max' => 2147385599);
        $actual = $getMetaConstraints();
        $this->assertIdentical($expected, $actual);
    }

    function testGetOrderParameters() {
        $getOrderParameters = $this->getWidgetMethod('getOrderParameters', $this->instance);
        $data = $this->data;

        // DTF_INPUT_DATE_ORDER = 0 // mm/dd/yyyy
        $date = "{$this->min['month']}/{$this->min['day']}/{$this->min['year']}";
        $this->assertIdentical(array(0, 1, 2, $date), $getOrderParameters($this->min, 0, false));
        $this->assertIdentical(array(0, 1, 2, "$date {$this->min['time']}"), $getOrderParameters($this->min, 0));

        // DTF_INPUT_DATE_ORDER = 1 // yyyy/mm/dd
        $date = "{$this->min['year']}/{$this->min['month']}/{$this->min['day']}";
        $this->assertIdentical(array(1, 2, 0, $date), $getOrderParameters($this->min, 1, false));
        $this->assertIdentical(array(1, 2, 0, "$date {$this->min['time']}"), $getOrderParameters($this->min, 1));

        // DTF_INPUT_DATE_ORDER = 2 // dd/mm/yyyy
        $date = "{$this->min['day']}/{$this->min['month']}/{$this->min['year']}";
        $this->assertIdentical(array(1, 0, 2, $date), $getOrderParameters($this->min, 2, false));
        $this->assertIdentical(array(1, 0, 2, "$date {$this->min['time']}"), $getOrderParameters($this->min, 2));
    }

    // @@@ 140926-000082 Test min_year attribute being set - should return 1/1 day/month if year > 1970, 1/3 for 1970  
    function testMinYear() {
        $this->instance->data['attrs']['min_year'] = 2013;
        $data = $this->getWidgetData($this->instance);
        $this->assertEqual(1, $data['js']['min']['day']);
        $this->assertEqual(1, $data['js']['min']['month']);
        $this->assertEqual(2013, $data['js']['min']['year']);
        $this->instance->data['attrs']['min_year'] = 1970;
        $data = $this->getWidgetData($this->instance);
        $this->assertEqual(3, $data['js']['min']['day']);
        $this->assertEqual(1, $data['js']['min']['month']);
        $this->assertEqual(1970, $data['js']['min']['year']);
    }
}
