<?php

use RightNow\Widgets;
use RightNow\Connect\v1_4 as Connect;
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestModerationSummaryTable extends WidgetTestCase
{
    public $testingWidget = "standard/moderation/ModerationSummaryTable";

    function setUp() {
        $this->logIn('modactive1');
    }

    function tearDown() {
        $this->logOut();
    }

    function testGetData() {
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertNotNull($data['attrs']['label_type_heading'], 'Label type heading not set');
        $this->assertNotNull($data['tableData']['headers'], 'Table headers not set');
        $this->assertTrue(count($data['tableData']['headers']) === 5, 'Table columns header count should be 5');
        $this->assertNotNull($data['tableData']['headers'][0]['heading'], 'Table heading not set for column 1');
        $this->assertNotNull($data['tableData']['data'], 'Table data not set');
        $this->assertTrue(count($data['tableData']['data']) > 0, 'Empty table data');
        $this->assertTrue(is_array($data['tableData']['data'][0][0]), 'First column data is not an array');
        $this->assertNotNull($data['tableData']['data'][0][0]['value'], 'Column data not set');

        $this->assertNull($data['tableData']['data'][0][0]['link'], 'Link should not exist for header');
        $this->assertNotNull(@$data['tableData']['data'][0][0]['value']['link'], 'Link should exist for value');
        $this->assertNull($data['tableData']['data'][0][3]['link'], 'Link should not exist');
        
        $this->createWidgetInstance();
        $this->setWidgetAttributes(array("date_filter_options"=>"last_30_days"));
        $data = $this->getWidgetData();
        $getDateFilterOption = $this->getWidgetMethod('getDateFilterOption');
        $selectedDateFilter = $getDateFilterOption($data['attrs']['date_filter_options']);
        $this->assertEqual(array('interval' => 'day', 'unit' => -30), $selectedDateFilter, 'Incorrect interval and unit');
        $this->assertEqual('Question and comment counts are limited to 30 days and are based on the times of their last updates.', $data['noteLabels']['last_30_days'], 'Label is not correct');
    }

    function testBuildModerationURL() {
        $this->createWidgetInstance();
        $buildModerationURL = $this->getWidgetMethod('buildModerationURL');

        $this->assertEqual('/app/social/moderate/question', $buildModerationURL('CommunityQuestion'), 'Invalid URL for question moderation');
        $this->assertEqual('/app/social/moderate/question/questions.status/30', $buildModerationURL('CommunityQuestion', STATUS_TYPE_SSS_QUESTION_SUSPENDED), 'Invalid URL for question moderation suspended filter');

        $this->assertEqual('/app/social/moderate/comment', $buildModerationURL('CommunityComment'), 'Invalid URL for comment moderation');
        $this->assertEqual('/app/social/moderate/comment/comments.status/33', $buildModerationURL('CommunityComment', STATUS_TYPE_SSS_COMMENT_ACTIVE), 'Invalid URL for comment moderation active filter');

        $this->assertEqual('/app/social/moderate/user', $buildModerationURL('CommunityUser'), 'Invalid URL for user moderation');
        $this->assertEqual('/app/social/moderate/user/users.status/41', $buildModerationURL('CommunityUser', STATUS_TYPE_SSS_USER_ARCHIVE), 'Invalid URL for user moderation archived filter');
    }

    function testGetSummaryTableData() {
        $this->createWidgetInstance();
        $getSummaryTableData = $this->getWidgetMethod('getSummaryTableData');
        $data = $getSummaryTableData();
        $this->assertEqual('Questions', $data['data'][0][0]['value'], "Question row doesn't exist");
        $this->assertEqual('Users', $data['data'][2][0]['value'], "User row doesn't exist");
        $this->assertEqual(0, $data['data'][1][3]['value'], "Archive count should be 0 for Comment");
        $this->assertEqual(0, $data['data'][0][3]['value'], "Archive count should be 0 for Question");
        $this->assertNull($data['data'][0][3]['link'], 'Link should not exist for Question archived status');
    }
}