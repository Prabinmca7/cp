<?php

use RightNow\Utils\Text as Text;
use RightNow\Connect\v1_4 as Connect;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);


class SourceSortTest extends WidgetTestCase {
    public $testingWidget = "standard/searchsource/SourceSort";

    function testGetData () {
        \RightNow\Libraries\Search::clearCache();

        $this->createWidgetInstance(array('source_id' => 'thing1'));
        $getData = $this->getWidgetMethod('getData');
        list($result, $content) = $this->returnResultAndContent($getData);
        $this->assertFalse($result);
        $this->assertTrue(Text::stringContains($content, 'Widget Error: standard/searchsource/SourceSort - No search sources were provided'));

        $this->createWidgetInstance(array('source_id' => 'thing1,thing2'));
        $getData = $this->getWidgetMethod('getData');
        list($result, $content) = $this->returnResultAndContent($getData);
        $this->assertFalse($result);
        $this->assertTrue(Text::stringContains($content, 'Widget Error: standard/searchsource/SourceSort - This widget only supports a single value for its source_id attribute'));

        $this->createWidgetInstance(array('source_id' => 'KFSearch', 'column_order' => '1,0', 'direction_order' => '0,1'));
        $getData = $this->getWidgetMethod('getData');
        list($result, $content) = $this->returnResultAndContent($getData);
        $this->assertNull($result);
        $this->assertIdentical('', $content);
        $data = $this->getWidgetData();
        $this->assertIdentical(1, $data['options_column'][0]->ID);
        $this->assertIdentical('UpdatedTime', $data['options_column'][0]->LookupName);
        $this->assertIdentical(2, $data['options_column'][1]->ID);
        $this->assertIdentical('CreatedTime', $data['options_column'][1]->LookupName);
        $this->assertIdentical(1, $data['options_direction'][0]->ID);
        $this->assertIdentical('Descending', $data['options_direction'][0]->LookupName);
        $this->assertIdentical(2, $data['options_direction'][1]->ID);
        $this->assertIdentical('Ascending', $data['options_direction'][1]->LookupName);
        $this->assertIdentical(
            array(
                'filter_column' => array('value' => NULL, 'key' => 'sort', 'type' => 'sort'),
                'filter_direction' => array('value' => NULL, 'key' => 'dir', 'type' => 'direction'),
            ),
            $data['js']
        );

        $this->addUrlParameters(array('sort' => 1, 'dir' => 2));

        $this->createWidgetInstance(array('source_id' => 'SocialSearch', 'column_order' => '1,0', 'direction_order' => '0,1'));
        $getData = $this->getWidgetMethod('getData');
        list($result, $content) = $this->returnResultAndContent($getData);
        $this->assertNull($result);
        $this->assertIdentical('', $content);
        $data = $this->getWidgetData();
        $this->assertIdentical(1, $data['options_column'][0]->ID);
        $this->assertIdentical('UpdatedTime', $data['options_column'][0]->LookupName);
        $this->assertIdentical(2, $data['options_column'][1]->ID);
        $this->assertIdentical('CreatedTime', $data['options_column'][1]->LookupName);
        $this->assertIdentical(1, $data['options_direction'][0]->ID);
        $this->assertIdentical('Descending', $data['options_direction'][0]->LookupName);
        $this->assertIdentical(2, $data['options_direction'][1]->ID);
        $this->assertIdentical('Ascending', $data['options_direction'][1]->LookupName);
        $this->assertIdentical(
            array(
                'filter_column' => array('value' => '1', 'key' => 'sort', 'type' => 'sort'),
                'filter_direction' => array('value' => '2', 'key' => 'dir', 'type' => 'direction'),
            ),
            $data['js']
        );

        $this->restoreUrlParameters();
    }
}
