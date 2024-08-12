<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestRecentlyAnsweredQuestionsHelper extends CPTestCase {
    public $testingWidget = "RightNow/Helpers/RecentlyAnsweredQuestionsHelper";

    function __construct($label = null) {
        parent::__construct($label);
        $this->helper = new \RightNow\Helpers\RecentlyAnsweredQuestionsHelper;
    }

    function testQuestionLink () {
        $this->assertSame('endless/qid/fantasy', $this->helper->questionLink((object) array('ID' => 'fantasy'), 'endless'));
        $this->assertSame('endless/qid/', $this->helper->questionLink((object) array('ID' => null), 'endless'));
    }

    function testBestAnswerTypes () {
        $this->assertFalse($this->helper->questionHasModeratorChosenBestAnswer((object) array('bestAnswers' => array(SSS_BEST_ANSWER_AUTHOR => 'bob'))));
        $this->assertTrue($this->helper->questionHasAuthorChosenBestAnswer((object) array('bestAnswers' => array(SSS_BEST_ANSWER_AUTHOR => 'bob'))));

        $this->assertTrue($this->helper->questionHasModeratorChosenBestAnswer((object) array('bestAnswers' => array(SSS_BEST_ANSWER_MODERATOR => 'bob'))));
        $this->assertFalse($this->helper->questionHasAuthorChosenBestAnswer((object) array('bestAnswers' => array(SSS_BEST_ANSWER_MODERATOR => 'bob'))));

        $this->assertTrue($this->helper->questionHasModeratorChosenBestAnswer((object) array('bestAnswers' => array(SSS_BEST_ANSWER_MODERATOR => 'bob', SSS_BEST_ANSWER_AUTHOR => 'cathy'))));
        $this->assertTrue($this->helper->questionHasAuthorChosenBestAnswer((object) array('bestAnswers' => array(SSS_BEST_ANSWER_MODERATOR => 'bob', SSS_BEST_ANSWER_AUTHOR => 'cathy'))));

        $this->assertTrue($this->helper->questionHasModeratorChosenBestAnswer((object) array('bestAnswers' => array(SSS_BEST_ANSWER_MODERATOR => 'bob', SSS_BEST_ANSWER_AUTHOR => 'bob'))));
        $this->assertFalse($this->helper->questionHasAuthorChosenBestAnswer((object) array('bestAnswers' => array(SSS_BEST_ANSWER_MODERATOR => 'bob', SSS_BEST_ANSWER_AUTHOR => 'bob'))));
    }

    function testCommentLink () {
        $this->assertStringContains($this->helper->commentLink((object) array('ID' => 1), (object) array('ID' => 100)), 'qid/1/comment/100');
    }
}
