<?php

use RightNow\Utils\Text as Text;
use RightNow\Connect\v1_4 as Connect;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);


class SourcePaginationTest extends WidgetTestCase {
    public $testingWidget = "standard/searchsource/SourcePagination";

    function testGetData () {
        $this->createWidgetInstance(array('source_id' => 'thing1,thing2'));
        $getData = $this->getWidgetMethod('getData');
        list($result, $content) = $this->returnResultAndContent($getData);
        $this->assertFalse($result);
        $this->assertTrue(Text::stringContains($content, 'Widget Error: standard/searchsource/SourcePagination - This widget only supports a single value for its source_id attribute'));

        $this->setWidgetAttributes(array('source_id' => 'thing1'));
        list($result, $content) = $this->returnResultAndContent($getData);
        $this->assertFalse($result);
        $this->assertTrue(Text::stringContains($content, 'Widget Error: standard/searchsource/SourcePagination - No search sources were provided'));

        $this->setWidgetAttributes(array('source_id' => 'KFSearch'));
        list($result, $content) = $this->returnResultAndContent($getData);
        $this->assertNull($result);
        $this->assertIdentical('', $content);
    }

    function testPopulateJsData () {
        $this->createWidgetInstance(array('source_id' => 'thing1'));
        $populateJsData = $this->getWidgetMethod('populateJsData');

        $results = (object)array(
            'total'  => 41,
            'offset' => 10,
        );
        $filters = array(
            'limit'       => array('value' => 10),
            'page'        => array('value' => 2),
        );
        $expected = array(
            'size'          => null,
            'total'         => 41,
            'offset'        => 10,
            'currentPage'   => 2,
            'numberOfPages' => 0,
            'filter'        => array('value' => 2),
            'limit'         => 10,
        );
        $this->assertIdentical($populateJsData($results, $filters), $expected);

        $results->size = 12;
        $expected = array(
            'size'          => 12,
            'total'         => 41,
            'offset'        => 10,
            'currentPage'   => 2,
            'numberOfPages' => 5,
            'filter'        => array('value' => 2),
            'limit'         => 10,
        );
        $this->assertIdentical($populateJsData($results, $filters), $expected);
    }

    function testSourceError () {
        $this->createWidgetInstance(array('source_id' => 'thing1,thing2'));
        $sourceError = $this->getWidgetMethod('sourceError');
        list($result, $content) = $this->returnResultAndContent($sourceError);
        $this->assertTrue($result);
        $this->assertTrue(Text::stringContains($content, 'Widget Error: standard/searchsource/SourcePagination - This widget only supports a single value for its source_id attribute'));

        $this->createWidgetInstance(array('source_id' => 'thing1'));
        $sourceError = $this->getWidgetMethod('sourceError');
        list($result, $content) = $this->returnResultAndContent($sourceError);
        $this->assertFalse($result);
        $this->assertIdentical($content, '');
    }
}
