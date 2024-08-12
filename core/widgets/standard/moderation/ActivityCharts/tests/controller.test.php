<?php

use RightNow\Utils\Text as Text;
use RightNow\Connect\v1_4 as Connect;

require_once(__DIR__ . '/../../../reports/Grid/controller.php');


RightNow\UnitTest\Helper::loadTestedFile(__FILE__);


class TestActivityCharts extends WidgetTestCase {
    public $testingWidget = "standard/moderation/ActivityCharts";

    function setUp() {
        $this->logIn('modactive1');
    }

    function tearDown() {
        $this->logOut();
    }

    function testGetData () {
        //Tests for Questions and Comments

        $this->createWidgetInstance(array('object_types' => 'CommunityComment, CommunityQuestion', 'interval_unit' => 'month', 'number_of_intervals' => -5));
        $data = $this->getWidgetData();

        //attribute tests
        $this->assertEqual(count($data['attrs']['object_types']), 2, 'Invalid social object type counts');
        $this->assertSame($data['attrs']['object_types'], array('CommunityComment', 'CommunityQuestion'), 'Invalid object types');
        $this->assertEqual($data['attrs']['interval_unit'], 'month', 'Invalid interval_unit');
        $this->assertEqual($data['attrs']['number_of_intervals'], -5, 'Invalid number_of_intervals');

        //axes data test
        $this->assertTrue($data['js']['chart']['axes']['maximum'] >= 5, 'Maximum should be greater than or equal to 5');

        //chart legend key tests
        $this->assertSame($data['attrs']['object_types'], array_keys($data['js']['chart']['keys']), 'Object keys are not in the right order');
        $this->assertIdentical($data['attrs']['object_types'], array_keys($data['js']['chart']['keys']), 'Object keys are not set correctly');

        //chart data test
        $this->assertEqual(count($data['js']['chart']['data']), 5, 'Invalid row counts for chart');

        //chart data test
        $socialObjectKey1 = $data['js']['chart']['keys'][$data['attrs']['object_types'][0]];
        $this->assertNotNull($data['js']['chart']['data'][0][$socialObjectKey1], 'Data not set for first social object');

        $socialObjectKey2 = $data['js']['chart']['keys'][$data['attrs']['object_types'][0]];
        $this->assertNotNull($data['js']['chart']['data'][0][$socialObjectKey2], 'Data not set for second social object');
    }

    function testSocialUserGetData () {
        //Tests for Questions and Comments

        $this->createWidgetInstance(array('object_types' => 'CommunityUser', 'interval_unit' => 'day', 'number_of_intervals' => -7));
        $data = $this->getWidgetData();

        //attribute tests
        $this->assertEqual(count($data['attrs']['object_types']), 1, 'Invalid social object type counts');
        $this->assertSame($data['attrs']['object_types'], array('CommunityUser'), 'Invalid object types');
        $this->assertEqual($data['attrs']['interval_unit'], 'day', 'Invalid interval_unit');
        $this->assertEqual($data['attrs']['number_of_intervals'], -7, 'Invalid number_of_intervals');

        //chart data test
        $this->assertEqual(count($data['js']['chart']['data']), 7, 'Invalid row counts for chart');

        //chart data test
        $socialObjectKey1 = $data['js']['chart']['keys'][$data['attrs']['object_types'][0]];
        $this->assertNotNull($data['js']['chart']['data'][0][$socialObjectKey1], 'Data not set for first social object');
        $this->assertEqual(count($data['js']['chart']['data'][0]), 2, 'Number of columns should not be 2');
    }
}