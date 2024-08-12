<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestForumList extends WidgetTestCase {
    public $testingWidget = "standard/discussion/ForumList";
    
    function testGetData() {
        $this->logIn();
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertIdentical($data['attrs']['show_columns'], array(question_count, comment_count, last_activity));
        $this->assertIdentical("product", $data['attrs']['type']);
        $this->assertIdentical($data['attrs']['show_forum_description'], true);
        $this->assertIdentical(128, $data['attrs']['maximum_description_length']);
        $this->assertIdentical(count($data['last_activity']), 6);
    }

    function testGetPermissionedForums() {
        $this->logIn();
        $instance = $this->createWidgetInstance();
        $getFunction = $this->getWidgetMethod('getPermissionedForums', $this->widgetInstance);
        $result = $getFunction('Product', array(array('id' => 1, 'label' => 'Mobile Phones', 'hasChildren' => 1, 'selected' => 1)));
        $this->assertIdentical(count($result), 1);
        $this->assertIdentical($result[1]['name'], 'Mobile Phones');
    }

    function testAllForums() {
        $this->logIn();
        $defaultParamsToPass = array('show_sub_items_for' => 0);
        $this->createWidgetInstance($defaultParamsToPass);
        $data = $this->getWidgetData();
        $this->assertIdentical($data['attrs']['show_columns'], array(question_count, comment_count, last_activity));
        $this->assertIdentical($data['attrs']['sort_order'], "last_activity");
        $this->assertIdentical("product", $data['attrs']['type']);
        $this->assertIdentical(count($data['last_activity']), 10);
    }
}