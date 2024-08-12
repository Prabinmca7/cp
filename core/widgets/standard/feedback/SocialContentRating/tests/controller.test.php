<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class SocialContentRatingTest extends WidgetTestCase {
    public $testingWidget = 'standard/feedback/SocialContentRating';
    public $testingFile = __FILE__;
    public $testingClass = __CLASS__;

    function setUp() {
        $this->fixtureInstance = new \RightNow\UnitTest\Fixture();
    }

    function tearDown () {
        $this->logOut();
        $this->fixtureInstance->destroy();
    }

    function testDoesNotRenderWithInvalidID(){
        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => 35233));
        $this->assertFalse($this->widgetInstance->getData());

        $this->createWidgetInstance(array('content_type' => 'comment', 'question_id' => 35233, 'comment_id' => 40));
        $this->assertFalse($this->widgetInstance->getData());

        $this->createWidgetInstance(array('content_type' => 'comment', 'question_id' => 12, 'comment_id' => 35233));
        $this->assertFalse($this->widgetInstance->getData());
    }

    function testShowControlsBasedOnUserProfile () {
        //Should render when no user is logged in
        list($fixtureInstance, $question, $comment) = $this->getFixtures(array('QuestionActiveAllBestAnswer', 'CommentActiveUserActive'));
        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => $question->ID));
        $this->widgetInstance->getData();
        $this->assertTrue($this->widgetInstance->data['js']['canRate']);

        $this->createWidgetInstance(array('content_type' => 'comment', 'question_id' => $question->ID, 'comment_id' => $comment->ID));
        $this->widgetInstance->getData();
        $this->assertTrue($this->widgetInstance->data['js']['canRate']);
        $this->assertEqual(0, $this->widgetInstance->data['js']['userRating']);
        $fixtureInstance->destroy();

        //Render for user without social user
        $this->logIn('weide@rightnow.com.invalid');
        list($fixtureInstance, $question, $comment) = $this->getFixtures(array('QuestionActiveAuthorBestAnswer', 'CommentActiveUserActive'));
        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => $question->ID));
        $this->widgetInstance->getData();
        $this->assertTrue($this->widgetInstance->data['js']['canRate']);

        $this->createWidgetInstance(array('content_type' => 'comment', 'question_id' => $question->ID, 'comment_id' => $comment->ID));
        $data = $this->widgetInstance->getData();
        $fixtureInstance->destroy();

        //Render for user with social user
        list($fixtureInstance, $question, $comment) = $this->getFixtures(array('QuestionActiveAuthorBestAnswerReply', 'CommentActiveUserActive'));
        $this->logIn('mzoeller@rightnow.com.invalid');
        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => $question->ID));
        $this->widgetInstance->getData();
        $this->assertTrue($this->widgetInstance->data['js']['canRate']);

        $this->createWidgetInstance(array('content_type' => 'comment', 'question_id' => $question->ID, 'comment_id' => $comment->ID));
        $data = $this->widgetInstance->getData();
        $this->assertTrue($this->widgetInstance->data['js']['canRate']);

        $fixtureInstance->destroy();
        $this->logOut();
    }

    function testRenderBasedOnStatus(){
        // Render, but unable to vote on locked questions.
        list($fixtureInstance, $question, $comment) = $this->getFixtures(array('QuestionLockedUserActive', 'CommentActiveModActive'));
        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => $question->ID));
        $this->widgetInstance->getData();
        $this->assertFalse($this->widgetInstance->data['js']['canRate']);

        $this->createWidgetInstance(array('content_type' => 'comment', 'question_id' => $question->ID, 'comment_id' => $comment->ID));
        $this->widgetInstance->getData();
        $this->assertFalse($this->widgetInstance->data['js']['canRate']);
        $fixtureInstance->destroy();

        // No render at all on non-active questions.
        list($fixtureInstance, $question, $comment) = $this->getFixtures(array('QuestionSuspendedModActive', 'CommentActiveUserActive'));
        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => $question->ID));
        $this->assertFalse($this->widgetInstance->getData());
        $fixtureInstance->destroy();

        // No render at all on non-active comments (on an active question).
        list($fixtureInstance, $question, $comment) = $this->getFixtures(array('QuestionActiveModActive', 'CommentSuspendedModActive'));
        $this->createWidgetInstance(array('content_type' => 'comment', 'question_id' => $question->ID, 'comment_id' => $comment->ID));
        $this->assertFalse($this->widgetInstance->getData());
        $fixtureInstance->destroy();
    }

    function testAlreadyRated(){
        list($fixtureInstance, $question, $comment1, $comment2, $user) = $this->getFixtures(array('QuestionActiveLongAuthorBestAnswer', 'CommentActiveLongUserActive', 'CommentActiveUserArchive', 'UserModActive'));
        $this->logIn($user->Contact->Login);

        $this->createWidgetInstance(array('content_type' => 'comment', 'question_id' => $question->ID, 'comment_id' => $comment1->ID));
        $this->widgetInstance->getData();
        $this->assertTrue($this->widgetInstance->data['js']['alreadyRated']);
        $this->assertNotNull($this->widgetInstance->data['js']['userRating']);

        $this->createWidgetInstance(array('content_type' => 'comment', 'question_id' => $question->ID, 'comment_id' => $comment2->ID));
        $this->widgetInstance->getData();
        $this->assertFalse($this->widgetInstance->data['js']['alreadyRated']);
        $this->assertEqual(0, $this->widgetInstance->data['js']['userRating']);

        $this->logOut();
        $fixtureInstance->destroy();
    }

    function testVoteCount(){
        list($fixtureInstance, $question1, $question2) = $this->getFixtures(array('QuestionActiveSingleComment', 'QuestionActiveAllBestAnswer'));
        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => $question1->ID));
        $this->widgetInstance->getData();
        //disabled due to SPM bug,see 150519-000097
        //$this->assertIdentical(1, $this->widgetInstance->data['js']['ratingValue']);

        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => $question2->ID));
        $this->widgetInstance->getData();
        $this->assertIdentical(0, $this->widgetInstance->data['js']['ratingValue']);
        $fixtureInstance->destroy();
    }

    function testVoteSubmitted () {
        list($fixtureInstance, $question, $comment, $user) = $this->getFixtures(array('QuestionActiveAllBestAnswer', 'CommentActiveUserActive', 'UserActive2'));

        //Not logged in, should cause an error for both questions and comments
        $defaultParamsToPass = array('content_type' => 'question', 'question_id' => $question->ID);
        $this->createWidgetInstance($defaultParamsToPass);
        $response = $this->callAjaxMethod('submitVoteHandler', array(), true, $this->widgetInstance, $defaultParamsToPass);
        $this->assertSame($response->errors[0]->errorCode, 'ERROR_USER_NOT_LOGGED_IN');

        $defaultParamsToPass = array('content_type' => 'comment', 'question_id' => $question->ID, 'comment_id' => $comment->ID);
        $this->createWidgetInstance($defaultParamsToPass);
        $response = $this->callAjaxMethod('submitVoteHandler', array(), true, $this->widgetInstance, $defaultParamsToPass);
        $this->assertSame($response->errors[0]->errorCode, 'ERROR_USER_NOT_LOGGED_IN');

        //Logged in user, should not cause errors
        $defaultParamsToPass = array('content_type' => 'question', 'question_id' => $question->ID);
        $this->createWidgetInstance($defaultParamsToPass);
        $response = $this->callWidgetMethodViaWgetRecipient('submitVoteHandler', array("rating" => 1), true, $this->widgetInstance, $user->Contact->Login, $defaultParamsToPass);
        $this->assertTrue(is_int($response->ratingID));

        $defaultParamsToPass = array('content_type' => 'comment', 'question_id' => $question->ID, 'comment_id' => $comment->ID);
        $this->createWidgetInstance($defaultParamsToPass);
        $response = $this->callWidgetMethodViaWgetRecipient('submitVoteHandler', array("rating" => 1), true, $this->widgetInstance, $user->Contact->Login, $defaultParamsToPass);
        $this->assertTrue(is_int($response->ratingID));

        $fixtureInstance->destroy();
    }

    function testVoteResetted () {
        list($fixtureInstance, $question, $user) = $this->getFixtures(array('QuestionWithFormatting', 'UserModActive'));

        //Not logged in, should cause an error for both questions and comments
        $defaultParamsToPass = array('content_type' => 'question', 'question_id' => $question->ID);
        $this->createWidgetInstance($defaultParamsToPass);
        $response = $this->callAjaxMethod('submitVoteHandler', array("rating" => 1), true, $this->widgetInstance, $defaultParamsToPass);
        $this->assertSame($response->errors[0]->errorCode, 'ERROR_USER_NOT_LOGGED_IN');

        $defaultParamsToPass = array('content_type' => 'comment', 'question_id' => $question->ID, 'comment_id' => 1004);
        $this->createWidgetInstance($defaultParamsToPass);
        $response = $this->callAjaxMethod('submitVoteHandler', array("rating" => 1), true, $this->widgetInstance, $defaultParamsToPass);
        $this->assertSame($response->errors[0]->errorCode, 'ERROR_USER_NOT_LOGGED_IN');

        //Logged in user, should not cause errors
        $defaultParamsToPass = array('content_type' => 'question', 'question_id' => $question->ID);
        $this->createWidgetInstance($defaultParamsToPass);
        $response = $this->callWidgetMethodViaWgetRecipient('submitVoteHandler', array("rating" => 0), true, $this->widgetInstance, $user->Contact->Login, $defaultParamsToPass);
        $this->assertTrue($response->ratingReset);
        $fixtureInstance->destroy();
    }

    function testGetTotalRatingLabel(){
        // question upvote rating
        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => 2003));
        $this->widgetInstance->getData();
        $this->assertSame("Rating: 1 (1 user)", $this->widgetInstance->data['js']['totalRatingLabel']);

        // comment upvote rating
        $this->createWidgetInstance(array('content_type' => 'comment', 'question_id' => 2002, 'comment_id' => 1004));
        $this->widgetInstance->getData();
        $this->assertSame("Rating: 1 (1 user)", $this->widgetInstance->data['js']['totalRatingLabel']);

        // question star rating
        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => 2003, 'rating_type' => 'star'));
        $this->widgetInstance->getData();
        $this->assertSame("Rating: 5/5 (1 user)", $this->widgetInstance->data['js']['totalRatingLabel']);

        // comment star rating
        $this->createWidgetInstance(array('content_type' => 'comment', 'question_id' => 2002, 'comment_id' => 1004, 'rating_type' => 'star'));
        $this->widgetInstance->getData();
        $this->assertSame("Rating: 5/5 (1 user)", $this->widgetInstance->data['js']['totalRatingLabel']);
        
        // question updown rating
        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => 2003, 'rating_type' => 'updown'));
        $this->widgetInstance->getData();
        $this->assertSame("Rating: +1/-0 (1 user)", $this->widgetInstance->data['js']['totalRatingLabel']);
        
        // comment updown rating
        $this->createWidgetInstance(array('content_type' => 'comment', 'question_id' => 2002, 'comment_id' => 1004, 'rating_type' => 'updown'));
        $this->widgetInstance->getData();
        $this->assertSame("Rating: +1/-0 (1 user)", $this->widgetInstance->data['js']['totalRatingLabel']);
    }
}
