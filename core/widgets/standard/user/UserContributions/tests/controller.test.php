<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class UserContributionsTest extends WidgetTestCase {
    public $testingWidget = 'standard/user/UserContributions';

    function __construct() {
        parent::__construct();
        list($fixtureInstance, $socialUser, $question, $questionParentCommentDeleted) = $this->getFixtures(array('UserActive1', 'QuestionActiveAuthorBestAnswer','QuestionActiveParentCommentDeleted'));
        $this->fixtureInstance = $fixtureInstance;
        $this->socialUser = $socialUser;
    }

    function testGetData() {
        // Absense of 'user' url parameter
        $widget = $this->createWidgetInstance();
        $widgetData = $this->getWidgetData($widget);
        $this->assertFalse(array_key_exists('contributions', $widgetData));

        // 'user' url parameter present
        $this->addUrlParameters(array('user' => $this->socialUser->ID));
        $widget = $this->createWidgetInstance();
        $widgetData = $this->getWidgetData($widget);
        $this->assertEqual($widgetData['attrs']['label_questions'], $widgetData['contributions']['questions']['label']);
        $this->assertIsA($widgetData['contributions']['questions']['count'], 'integer');

        $this->restoreUrlParameters();
    }

    function testGetContributions() {
        $this->addUrlParameters(array('user' => $this->socialUser->ID));

        $widget = $this->createWidgetInstance();
        $widgetData = $this->getWidgetData($widget);
        $results = $widget->getContributions('questions');
        $this->assertEqual($widgetData['attrs']['label_questions'], $results['label']);
        $this->assertEqual(2, $results['count']);

        $results = $widget->getContributions('answers');
        $this->assertEqual(1, $results['count']);

        $results = $widget->getContributions('comments');
        $this->assertEqual(1, $results['count']);

        $this->restoreUrlParameters();
    }

    function testCount() {
        $this->addUrlParameters(array('user' => $this->socialUser->ID));

        $widget = $this->createWidgetInstance();
        $widgetData = $this->getWidgetData($widget);

        $results = $widget->getCount('someInvalidType');
        $this->assertEqual(0, $results);

        $results = $widget->getCount('questions');
        $this->assertIsA($results, 'integer');

        $this->restoreUrlParameters();
    }
}
