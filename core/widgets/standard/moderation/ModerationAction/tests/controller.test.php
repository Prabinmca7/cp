<?php

use RightNow\Utils\Text as Text;
use RightNow\Widgets;
use RightNow\Connect\v1_4 as Connect;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestModerationAction extends WidgetTestCase
{
    public $testingWidget = "standard/moderation/ModerationAction";

    function setUp() {
        $this->logIn();
    }

    function tearDown() {
        $this->logOut();
    }

    function testGetData()
    {
        //For CommunityQuestion
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertIdentical($data['attrs']['object_type'], 'CommunityQuestion', 'Invalid object type');
        $this->assertTrue(isset($data['actions']['restore']), 'CommunityQuestion restore Action is not set');
        $this->assertTrue(isset($data['actions']['suspend_user']), 'suspend_user action is not set');
        $this->assertTrue(isset($data['actions']['restore_user']), 'restore_user action is not set');
        $this->assertTrue(!isset($data['actions']['archive']), 'archive action is not allowed from CommunityQuestion page');

        //For CommunityUser
        $this->setWidgetAttributes(array("object_type"=>"CommunityUser"));
        $data = $this->getWidgetData();
        //make sure suspend_user is set only for Question and Comment Page
        $this->assertTrue(!isset($data['actions']['suspend_user']), '"suspend_user" action is not allowed from CommunityUser page');
        $this->assertTrue(isset($data['actions']['suspend']), '"suspend" action is not set for CommunityUser page');
        $this->assertTrue(isset($data['actions']['archive']), '"archive" action is not set from CommunityUser  page');

        //For CommunityComment
        $this->setWidgetAttributes(array("object_type"=>"CommunityComment"));
        $data = $this->getWidgetData();
        $this->assertIdentical($data['attrs']['object_type'], 'CommunityComment', 'Invalid object type');
        $this->assertTrue(isset($data['actions']['restore']), 'CommunityComment restore Action is not set');
        $this->assertTrue(isset($data['actions']['suspend_user']), 'suspend_user action is not set');
        $this->assertTrue(isset($data['actions']['restore_user']), 'restore_user action is not set');
        $this->assertTrue(!isset($data['actions']['archive']), 'archive action is not allowed from CommunityComment page');
    }
}