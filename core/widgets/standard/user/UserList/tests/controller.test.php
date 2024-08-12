<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestUserList extends WidgetTestCase {
    public $testingWidget = "standard/user/UserList";
    
    function testGetData() {
        $this->logIn();
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertIdentical("table_view", $data['attrs']['content_display_type']);
        $this->assertIdentical($data['attrs']['max_user_count'], 5);
    }

    function testTopQuestionContributor() {
        $this->logIn('modactive1');
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertIdentical("useractive1", $data['user_data'][0]['user']->DisplayName);
        $this->logOut();
    }
}