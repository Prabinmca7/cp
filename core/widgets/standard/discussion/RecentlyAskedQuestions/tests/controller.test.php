<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestRecentlyAskedQuestions extends WidgetTestCase {
    public $testingWidget = "standard/discussion/RecentlyAskedQuestions";

    function getData($attributes = array()) {
        $instance = $this->createWidgetInstance($attributes);
        return $this->getWidgetData();
    }

    function setUp() {
        list($this->fixtureInstance,
             $this->questionActiveAuthorBestAnswer,
             $this->questionActiveModActive,
             $this->questionActiveSingleComment,
             $this->questionLockedUserActive,
             $this->questionSuspendedModActive,
             $this->questionSuspendedUserActive,
             $this->questionWithProduct,
             $this->questionWithCategory
         ) = $this->getFixtures(array(
                'QuestionActiveAuthorBestAnswer',
                'QuestionActiveModActive',
                'QuestionActiveSingleComment',
                'QuestionLockedUserActive',
                'QuestionSuspendedModActive',
                'QuestionSuspendedUserActive',
                'QuestionWithProduct',
                'QuestionWithCategory',
        ));
        $this->questions = array(
            $this->questionActiveAuthorBestAnswer,
            $this->questionActiveModActive,
            $this->questionActiveSingleComment,
            $this->questionLockedUserActive,
            $this->questionSuspendedModActive,
            $this->questionSuspendedUserActive,
            $this->questionWithProduct,
            $this->questionWithCategory
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
        unset($fixtureQuestionIDsWithoutProdAssoc[$this->questionWithProduct->ID]);

        foreach($fixtureQuestionIDsWithoutProdAssoc as $fixtureQuestionIDsWithoutProdAssoc) {
            $this->assertFalse(in_array($fixtureQuestionIDsWithoutProdcat, $resultQuestionIDs));
        }
        $this->assertTrue(in_array($this->questionWithProduct->ID, $resultQuestionIDs));
    }

    function testCategoryFilter() {
        $widgetData = $this->getData(array('maximum_questions' => 8, 'category_filter' => 70));
        $resultQuestionIDs = array_keys($widgetData['js']['questions']);

        $fixtureQuestionIDsWithoutCategory = $this->questionIDs;
        unset($fixtureQuestionIDsWithoutCategory[$this->questionWithCategory->ID]);

        foreach($fixtureQuestionIDsWithoutCategory as $fixtureQuestionIDsWithoutCategory) {
            $this->assertFalse(in_array($fixtureQuestionIDsWithoutCategory, $resultQuestionIDs));
        }
        $this->assertTrue(in_array($this->questionWithCategory->ID, $resultQuestionIDs));
    }

    function testIncludeChildren() {
        $widgetData = $this->getData(array('maximum_questions' => 8, 'product_filter' => 129, 'include_children' => false));
        $resultQuestionIDsWithoutChildren = array_keys($widgetData['js']['questions']);

        $widgetData = $this->getData(array('maximum_questions' => 8, 'product_filter' => 129, 'include_children' => true));
        $resultQuestionIDsWithChildren = array_keys($widgetData['js']['questions']);

        $this->assertNotEqual($resultQuestionIDsWithoutChildren, $resultQuestionIDsWithChildren);
    }
    
    function testWithBestAnswerFilter() {
        $widgetData = $this->getData(array('maximum_questions' => 8, 'questions_with_comments' => 'best_answers_only'));
        $resultQuestionIDs = array_keys($widgetData['js']['questions']);

        $fixtureQuestionIDsWithoutBestAnswers = $this->questionIDs;
        unset($fixtureQuestionIDsWithoutBestAnswers[$this->questionActiveAuthorBestAnswer->ID]);

        foreach($fixtureQuestionIDsWithoutBestAnswers as $fixtureQuestionIDsWithoutBestAnswers) {
            $this->assertFalse(in_array($fixtureQuestionIDsWithoutBestAnswers, $resultQuestionIDs));
        }
        $this->assertTrue(in_array($this->questionActiveAuthorBestAnswer->ID, $resultQuestionIDs));
    }
    
    function testWithoutBestAnswerFilter() {
        $widgetData = $this->getData(array('maximum_questions' => 8, 'questions_with_comments' => 'no_best_answers'));
        $resultQuestionIDs = array_keys($widgetData['js']['questions']);

        $fixtureQuestionIDsWithBestAnswers = $this->questionIDs;
        unset($fixtureQuestionIDsWithBestAnswers[$this->questionActiveModActive->ID]);
        unset($fixtureQuestionIDsWithBestAnswers[$this->questionLockedUserActive->ID]);
        unset($fixtureQuestionIDsWithBestAnswers[$this->questionWithProduct->ID]);
        unset($fixtureQuestionIDsWithBestAnswers[$this->questionWithCategory->ID]);

        foreach($fixtureQuestionIDsWithBestAnswers as $fixtureQuestionIDsWithBestAnswers) {
            $this->assertFalse(in_array($fixtureQuestionIDsWithBestAnswers, $resultQuestionIDs));
        }
        $this->assertTrue(in_array($this->questionActiveModActive->ID, $resultQuestionIDs));
        $this->assertTrue(in_array($this->questionLockedUserActive->ID, $resultQuestionIDs));
        $this->assertTrue(in_array($this->questionWithProduct->ID, $resultQuestionIDs));
        $this->assertTrue(in_array($this->questionWithCategory->ID, $resultQuestionIDs));
    }
    
    function testAllCommentsFilter() {
        $widgetData = $this->getData(array('maximum_questions' => 8, 'questions_with_comments' => 'all'));
        $resultQuestionIDs = array_keys($widgetData['js']['questions']);

        $fixtureQuestionIDs = $this->questionIDs;
        unset($fixtureQuestionIDs[$this->questionActiveAuthorBestAnswer->ID]);
        unset($fixtureQuestionIDs[$this->questionActiveSingleComment->ID]);
        unset($fixtureQuestionIDs[$this->questionActiveModActive->ID]);
        unset($fixtureQuestionIDs[$this->questionLockedUserActive->ID]);
        unset($fixtureQuestionIDs[$this->questionWithProduct->ID]);
        unset($fixtureQuestionIDs[$this->questionWithCategory->ID]);
        
        foreach($fixtureQuestionIDs as $fixtureQuestionIDs) {
            $this->assertFalse(in_array($fixtureQuestionIDs, $resultQuestionIDs));
        }
        $this->assertTrue(in_array($this->questionActiveAuthorBestAnswer->ID, $resultQuestionIDs));
        $this->assertTrue(in_array($this->questionActiveSingleComment->ID, $resultQuestionIDs));
        $this->assertTrue(in_array($this->questionActiveModActive->ID, $resultQuestionIDs));
        $this->assertTrue(in_array($this->questionLockedUserActive->ID, $resultQuestionIDs));
        $this->assertTrue(in_array($this->questionWithProduct->ID, $resultQuestionIDs));
        $this->assertTrue(in_array($this->questionWithCategory->ID, $resultQuestionIDs));
    }
}