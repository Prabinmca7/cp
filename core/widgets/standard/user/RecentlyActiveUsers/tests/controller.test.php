<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestRecentlyActiveUsers extends WidgetTestCase {
    public $testingWidget = "standard/user/RecentlyActiveUsers";
    
    function testGetData() {
        $this->logIn();
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertIdentical($data['attrs']['specify_list_view_metadata'], array(user_avatar, display_name, last_active));
        $this->assertIdentical("grid_view", $data['attrs']['content_display_type']);
        $this->assertIdentical($data['attrs']['max_user_count'], 25);
    }

    function testMostRecentUser() {
        $this->logIn('modactive1');
        $question = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'hello'),
            'CommunityQuestion.Body' => (object) array('value' => 'bacon pancakes'),
        ))->result;
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertIdentical("modactive1", $data['users'][0]['user']->DisplayName);
        
        $this->destroyObject($question);
        $this->logOut();
    }
}