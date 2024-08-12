<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestRecentlyAskedQuestionsHelper extends CPTestCase {
    public $testingWidget = "RightNow/Helpers/RecentlyAskedQuestionsHelper";

    function __construct($label = null) {
        parent::__construct($label);
        $this->helper = new \RightNow\Helpers\RecentlyAskedQuestionsHelper;
    }

    function testQuestionLink () {
        $this->assertSame('endless/qid/fantasy', $this->helper->questionLink((object) array('ID' => 'fantasy'), 'endless'));
        $this->assertSame('endless/qid/', $this->helper->questionLink((object) array('ID' => null), 'endless'));
    }
}
