<?php

use RightNow\Connect\v1_4 as Connect;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestRecentlyAnsweredQuestions extends WidgetTestCase {
    public $testingWidget = "standard/discussion/RecentlyAnsweredQuestions";

    function getData($attributes = array()) {
        $instance = $this->createWidgetInstance($attributes);
        return $this->getWidgetData();
    }

    function setUp() {
        list(
             $this->fixtureInstance,
             $this->questionActiveAuthorBestAnswer,
             $this->questionActiveModeratorBestAnswer,
             $this->questionSuspendedModActive,
             $this->questionSuspendedUserActive,
             $this->questionActiveAuthorBestAnswerWithProduct,
             $this->questionActiveAuthorBestAnswerWithCategory,
             $this->questionActiveBestAnswerDeleted,
             $this->questionActiveParentCommentDeleted
       ) = $this->getFixtures(array(
                'QuestionActiveAuthorBestAnswer',
                'QuestionActiveModeratorBestAnswer',
                'QuestionSuspendedModActive',
                'QuestionSuspendedUserActive',
                'QuestionActiveAuthorBestAnswerWithProduct',
                'QuestionActiveAuthorBestAnswerWithCategory',
                'QuestionActiveBestAnswerDeleted',
                'QuestionActiveParentCommentDeleted'
      ));
      $this->questions = array(
            $this->questionActiveAuthorBestAnswer,
            $this->questionActiveModeratorBestAnswer,
            $this->questionSuspendedModActive,
            $this->questionSuspendedUserActive,
            $this->questionActiveAuthorBestAnswerWithProduct,
            $this->questionActiveAuthorBestAnswerWithCategory,
            $this->questionActiveBestAnswerDeleted,
            $this->questionActiveParentCommentDeleted
        );
        $this->questionIDs = array_keys($this->questions);
    }

    function tearDown() {
        $this->fixtureInstance->destroy();
    }

    function testMaxQuestionsFilter() {
        $widgetData = $this->getData(array('maximum_questions' => 3));
        $questions = $widgetData['js']['questions'];
        $this->assertEqual(count($questions), 3);
    }

    function testProductFilter() {
        $widgetData = $this->getData(array('maximum_questions' => 8, 'product_filter' => 120));
        $resultQuestionIDs = array_keys($widgetData['js']['questions']);

        $fixtureQuestionIDsWithoutProdAssoc = $this->questionIDs;
        unset($fixtureQuestionIDsWithoutProdAssoc[$this->questionActiveAuthorBestAnswerWithProduct->ID]);

        foreach($fixtureQuestionIDsWithoutProdAssoc as $fixtureQuestionIDsWithoutProdAssoc) {
            $this->assertFalse(in_array($fixtureQuestionIDsWithoutProdcat, $resultQuestionIDs));
        }
        $this->assertTrue(in_array($this->questionActiveAuthorBestAnswerWithProduct->ID, $resultQuestionIDs));
    }

    function testCategoryFilter() {
        $widgetData = $this->getData(array('maximum_questions' => 8, 'category_filter' => 70));
        $resultQuestionIDs = array_keys($widgetData['js']['questions']);

        $fixtureQuestionIDsWithoutCategory = $this->questionIDs;
        unset($fixtureQuestionIDsWithoutCategory[$this->questionActiveAuthorBestAnswerWithCategory->ID]);

        foreach($fixtureQuestionIDsWithoutCategory as $fixtureQuestionIDsWithoutCategory) {
            $this->assertFalse(in_array($fixtureQuestionIDsWithoutCategory, $resultQuestionIDs));
        }
        $this->assertTrue(in_array($this->questionActiveAuthorBestAnswerWithCategory->ID, $resultQuestionIDs));
    }

    function testIncludeChildren() {
        $widgetData = $this->getData(array('maximum_questions' => 8, 'product_filter' => 129, 'include_children' => false));
        $resultQuestionIDsWithoutChildren = array_keys($widgetData['js']['questions']);

        $widgetData = $this->getData(array('maximum_questions' => 8, 'product_filter' => 129, 'include_children' => true));
        $resultQuestionIDsWithChildren = array_keys($widgetData['js']['questions']);

        $this->assertNotEqual($resultQuestionIDsWithoutChildren, $resultQuestionIDsWithChildren);
    }

    function testAuthorAnswerType() {
        $widgetData = $this->getData(array('maximum_questions' => 8, 'author_type' => 'author'));
        $resultQuestionIDs = array_keys($widgetData['js']['questions']);

        $fixtureQuestionIDsNotAuthorType = $this->questionIDs;
        unset($fixtureQuestionIDsNotAuthorType[$this->questionActiveAuthorBestAnswer->ID]);

        foreach($fixtureQuestionIDsNotAuthorType as $fixtureQuestionIDsNotAuthorType) {
            $this->assertFalse(in_array($fixtureQuestionIDsNotAuthorType, $resultQuestionIDs));
        }
        $this->assertTrue(in_array($this->questionActiveAuthorBestAnswer->ID, $resultQuestionIDs));
    }

    function testModeratorAnswerType() {
        $widgetData = $this->getData(array('maximum_questions' => 8, 'author_type' => 'moderator'));
        $resultQuestionIDs = array_keys($widgetData['js']['questions']);

        $fixtureQuestionIDsNotModeratorType = $this->questionIDs;
        unset($fixtureQuestionIDsNotModeratorType[$this->questionActiveModeratorBestAnswer->ID]);

        foreach($fixtureQuestionIDsNotModeratorType as $fixtureQuestionIDsNotModeratorType) {
            $this->assertFalse(in_array($fixtureQuestionIDsNotModeratorType, $resultQuestionIDs));
        }
        $this->assertTrue(in_array($this->questionActiveModeratorBestAnswer->ID, $resultQuestionIDs));
    }

    function testBothAnswerType() {
        $widgetData = $this->getData(array('maximum_questions' => 8, 'author_type' => 'both'));
        $resultQuestionIDs = array_keys($widgetData['js']['questions']);

        $fixtureQuestionIDsNotModeratorOrAuthorType = $this->questionIDs;
        unset($fixtureQuestionIDsNotModeratorOrAuthorType[$this->questionActiveAuthorBestAnswer->ID]);
        unset($fixtureQuestionIDsNotModeratorOrAuthorType[$this->questionActiveModeratorBestAnswer->ID]);

        foreach($fixtureQuestionIDsNotModeratorOrAuthorType as $fixtureQuestionIDsNotModeratorOrAuthorType) {
            $this->assertFalse(in_array($fixtureQuestionIDsNotModeratorOrAuthorType, $resultQuestionIDs));
        }
        $this->assertTrue(in_array($this->questionActiveAuthorBestAnswer->ID, $resultQuestionIDs));
        $this->assertTrue(in_array($this->questionActiveModeratorBestAnswer->ID, $resultQuestionIDs));
    }

    function testGetVisibleBestAnswer() {
        $instance = $this->createWidgetInstance();
        $widgetData = $this->getData(array('maximum_questions' => 10, 'author_type' => 'both'));
        $resultQuestions = array_keys($widgetData['js']['questions']);

        $this->assertSame(count($widgetData['js']['questions'][$this->questionActiveAuthorBestAnswer->ID]->bestAnswers), 1);
        $this->assertSame(count($widgetData['js']['questions'][$this->questionActiveModeratorBestAnswer->ID]->bestAnswers), 1);
    }
}
