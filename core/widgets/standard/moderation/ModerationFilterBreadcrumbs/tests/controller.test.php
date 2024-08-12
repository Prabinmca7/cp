<?php

use RightNow\Utils\Text as Text;
use RightNow\Connect\v1_4 as Connect;

require_once(__DIR__ . '/../../../reports/Grid/controller.php');


RightNow\UnitTest\Helper::loadTestedFile(__FILE__);


class TestModerationFilterBreadcrumbs extends WidgetTestCase {
    public $testingWidget = "standard/moderation/ModerationFilterBreadcrumbs";

    function setUp() {
        $this->logIn();
    }

    function tearDown() {
        $this->logOut();
    }

    function testGetDataForSocialQuestion () {
        //Tests for Question objects
        $reportId = 15100;
        \RightNow\Utils\Framework::removeCache("filtersFromUrl$reportId");
        $this->addUrlParameters(array("questions.status" => '29,30', "p" => '10', "c" => '70'));
        $this->createWidgetInstance(array('object_type' => 'CommunityQuestion', 'report_id' => $reportId, 'questions.status' => '29'));
        $data = $this->getWidgetData();
        $this->assertEqual($data['attrs']['object_type'], 'CommunityQuestion', 'Invalid Social Object Name');
        $this->assertIsA($data['js']['defaultFilters'], 'array', 'Default filter must be an array');
        $this->assertTrue(isset($data['js']['defaultFilters']['questions.updated']), 'Invalid Default Filter');
        $this->assertTrue(count($data['js']['filters']) > 0, 'Filter is not set');
        $this->assertEqual($data['js']['filters'][0]['data'][0]['id'], 29, 'Active Social Question filter is not set');
        $this->assertEqual($data['js']['filters'][0]['data'][1]['id'], 30, 'Suspended Social Question filter is not set');
        $this->assertEqual($data['js']['filters'][1]['data'][count($data['js']['filters'][1]['data'])-1]['id'], 10, 'Product HTC-10 is not set');
        $this->assertEqual($data['js']['filters'][2]['data'][count($data['js']['filters'][2]['data'])-1]['id'], 70, 'Category Mobile Broadband is not set');
        $this->assertEqual($data['js']['date_format'],'m/d/Y');
        $this->restoreUrlParameters();
    }
    
    function testGetDataForNoValueProductCategory () {
        //Tests for Question objects
        $reportId = 15100;
        \RightNow\Utils\Framework::removeCache("filtersFromUrl$reportId");
        $this->addUrlParameters(array("p" => '-1'));
        $this->createWidgetInstance(array('object_type' => 'CommunityQuestion', 'report_id' => $reportId));
        $data = $this->getWidgetData();
        $this->assertEqual($data['attrs']['object_type'], 'CommunityQuestion', 'Invalid Social Object Name');
        $this->assertIsA($data['js']['defaultFilters'], 'array', 'Default filter must be an array');
        $this->assertTrue(count($data['js']['filters']) > 0, 'Filter is not set');
        $this->assertEqual($data['js']['filters'][0]['data'][0]['id'], -1, 'No Value ID is not set');
        $this->assertEqual($data['js']['filters'][0]['data'][0]['label'], 'No Value', 'No Value label is not set');
        $this->restoreUrlParameters();
    }

    function testGetFilterMetaData () {
        $reportId = 15100;
        \RightNow\Utils\Framework::removeCache("filtersFromUrl$reportId");
        $this->addUrlParameters(array("questions.status" => '29,30'));
        $this->createWidgetInstance(array('object_type' => 'CommunityQuestion', 'report_id' => $reportId, 'questions.status' => '29'));
        $data = $this->getWidgetData();
        $this->assertEqual(count($data['js']['allAvailableFilters']), 5, 'All available filter is not set');
        $this->assertNotNull($data['js']['allAvailableFilters']['question_content_flags.flag'], 'question_content_flags.flag filter meta data is null');
        $this->assertNotNull($data['js']['allAvailableFilters']['questions.status'], 'questions.status filter meta data is null');
        $this->assertNotNull($data['js']['allAvailableFilters']['questions.updated']['caption'], 'questions.updated caption is is null');
        $this->assertNotNull($data['js']['allAvailableFilters']['p'], 'Product filter meta data is null');
        $this->assertNotNull($data['js']['allAvailableFilters']['c'], 'Product filter meta data is null');
        $this->restoreUrlParameters();
    }

    function testIsDefaultFilter () {
        $reportId = 15101;
        \RightNow\Utils\Framework::removeCache("filtersFromUrl$reportId");
        $this->addUrlParameters(array("comments.status" => '34', 'comments.updated' => 'last_90_days'));
        $this->createWidgetInstance(array('object_type' => 'CommunityComment', 'report_id' => $reportId));
        $data = $this->getWidgetData();
        $this->assertEqual(count($data['js']['filters']), 1, 'Default filter should not be set ');
        $this->restoreUrlParameters();
    }

    function testGetDataForSocialComment () {
        //Tests for Comment objects
        $reportId = 15101;
        \RightNow\Utils\Framework::removeCache("filtersFromUrl$reportId");
        $this->addUrlParameters(array("comments.status" => '34', "p" => '10', "c" => '70'));
        $this->createWidgetInstance(array('object_type' => 'CommunityComment', 'report_id' => $reportId));
        $data = $this->getWidgetData();
        $this->assertEqual($data['attrs']['object_type'], 'CommunityComment', 'Invalid Social Object Name');
        $this->assertIsA($data['js']['defaultFilters'], 'array', 'Default filter must be an array');
        $this->assertTrue(isset($data['js']['defaultFilters']['comments.updated']), 'Invalid Default Filter');
        $this->assertEqual(count($data['js']['filters']), 3, 'Number of filters should be 3');
        $this->assertEqual($data['js']['filters'][0]['data'][0]['id'], 34, 'Suspended Social Comment filter is not set');
        $this->assertTrue(count($data['js']['allAvailableFilters']) > 0, 'Available filter is not set');
        $this->assertNotNull($data['js']['allAvailableFilters']['comment_cnt_flgs.flag'], 'comment_cnt_flgs.flag filter meta data is null');
        $this->assertEqual($data['js']['filters'][1]['data'][count($data['js']['filters'][1]['data'])-1]['id'], 10, 'Product HTC-10 is not set');
        $this->assertEqual($data['js']['filters'][2]['data'][count($data['js']['filters'][2]['data'])-1]['id'], 70, 'Category Mobile Broadband is not set');
        $this->restoreUrlParameters();
    }

    function testGetDataForSocialUser () {
        //Tests for User objects
        $reportId = 15102;
        \RightNow\Utils\Framework::removeCache("filtersFromUrl15100");
        $this->addUrlParameters(array("users.status" => '37,38'));
        $this->createWidgetInstance(array('object_type' => 'CommunityUser', 'report_id' => $reportId));
        $data = $this->getWidgetData();
        $this->assertEqual($data['attrs']['object_type'], 'CommunityUser', 'Invalid Social Object Name');
        $this->assertIsA($data['js']['defaultFilters'], 'array', 'Default filter must be an array');
        $this->assertEqual(count($data['js']['filters']), 1, 'Number of filters should be 1');
        $this->assertEqual($data['js']['filters'][0]['data'][0]['id'], 38, 'Active Social User filter is not set');
        $this->assertEqual(count($data['js']['allAvailableFilters']), 1, 'Available filter is not set');
        $this->restoreUrlParameters();
    }

}