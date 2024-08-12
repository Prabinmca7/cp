<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestQuestionList extends WidgetTestCase {
    public $testingWidget = "standard/discussion/QuestionList";
    
    function testGetData() {
        $this->logIn();
        $defaultParamsToPass = array('show_sub_items_for' => 6);
        $this->createWidgetInstance($defaultParamsToPass);
        $data = $this->getWidgetData();
        $this->assertIdentical($data['attrs']['show_columns'], array(comment_count, last_activity));
        $this->assertIdentical($data['attrs']['sort_order'], "last_activity");
        $this->assertIdentical("product", $data['attrs']['type']);
        $this->assertIdentical(count($data['result']), 10);
    }
}