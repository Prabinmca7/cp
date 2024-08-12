<?php

use RightNow\Widgets;
use RightNow\Connect\v1_4 as Connect;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ModerationStatusFilter extends WidgetTestCase {

    public $testingWidget = "standard/moderation/ModerationStatusFilter";

    function testGetQuestionStatuses(){
        $this->createWidgetInstance(array('object_type' => 'CommunityQuestion', 'report_id' => '15100'));
        $data = $this->getWidgetData();
        $this->assertTrue(isset($data["js"]["filter_id"]), 'Question filter id should be present');
        $this->assertTrue(isset($data["js"]["oper_id"]), 'Question operator id should be present');
        $this->assertTrue(isset($data["js"]["default_value"]), 'Default value should be present');
    }

    function testGetCommentStatuses(){
        $this->createWidgetInstance(array('object_type' => 'CommunityComment', 'report_id' => '15101', 'report_filter_name' => 'comments.status'));
        $data = $this->getWidgetData();
        $this->assertTrue(isset($data["js"]["filter_id"]), 'Comment filter id should be present');
        $this->assertTrue(isset($data["js"]["oper_id"]), 'Comment operator id should be present');
        $this->assertTrue(isset($data["js"]["default_value"]), 'Default value should be present');
    }

}
