<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ModerationContentFlagFilter extends WidgetTestCase {

    public $testingWidget = "standard/moderation/ModerationContentFlagFilter";

    function testGetDataWithTwoFlagTypes () {
        $this->createWidgetInstance(array('report_filter_name' => 'question_content_flags.flag', 'object_type' => 'CommunityQuestion', 'report_id' => 15100, 'flag_types' => 'inappropriate,spam'));
        $data = $this->getWidgetData();
        $this->assertTrue(count($data["js"]["flags"]) === 2, 'Two Flags should be enabled');
        $this->assertTrue(isset($data["js"]["selected_flags"]), 'Selected flags should be present');
    }

    function testGetDataWithNoFlagTypes () {
        $this->createWidgetInstance(array('report_filter_name' => 'question_content_flags.flag', 'object_type' => 'CommunityQuestion', 'report_id' => 15100));
        $data = $this->getWidgetData();
        $this->assertNull($data["js"]["flags"], 'Widget should not be displayed');
    }

    function testGetDataWithFlagTypesInUrlParameters () {
        $this->createWidgetInstance(array('report_filter_name' => 'question_content_flags.flag', 'object_type' => 'CommunityQuestion', 'report_id' => 15100, 'flag_types' => 'inappropriate,spam,miscategorized'));
        $this->addUrlParameters(array('question_content_flags.flag' => '1,2'));
        $data = $this->getWidgetData();
        $this->assertTrue(count($data["js"]["flags"]) === 3, 'Three Flags should be enabled');
        $this->restoreUrlParameters();
    }

}
