<?php

use RightNow\Utils\Text as Text;
use RightNow\Connect\v1_4 as Connect;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);


class SourceFilterTest extends WidgetTestCase {
    public $testingWidget = "standard/searchsource/SourceFilter";

    function testGetData () {
        $this->createWidgetInstance(array('source_id' => 'thing1'));
        $getData = $this->getWidgetMethod('getData');
        list($result, $content) = $this->returnResultAndContent($getData);
        $this->assertFalse($result);
        $this->assertTrue(Text::stringContains($content, 'Widget Error: standard/searchsource/SourceFilter - No search sources were provided'));

        $this->createWidgetInstance(array('source_id' => 'SocialSearch'));
        $getData = $this->getWidgetMethod('getData');
        list($result, $content) = $this->returnResultAndContent($getData);
        $this->assertNull($result);
        $this->assertIdentical('', $content);
    }
}
