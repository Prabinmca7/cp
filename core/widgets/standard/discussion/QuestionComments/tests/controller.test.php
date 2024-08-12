<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\Connect\v1_4 as Connect,
    RightNow\UnitTest\Fixture,
    RightNow\UnitTest\Helper;

class TestQuestionComments extends WidgetTestCase {
    public $testingWidget = "standard/discussion/QuestionComments";
    public $testingFile = __FILE__;
    public $testingClass = __CLASS__;

    function __construct() {
        parent::__construct();
        $this->fixtureInstance = new Fixture();
    }

    function testGetData() {
        $question = $this->fixtureInstance->make('QuestionActiveModActive');
        $this->addUrlParameters(array('qid' => $question->ID));
        $this->logIn($question->CreatedByCommunityUser->Contact->Login);

        $this->createWidgetInstance();
        $widgetData = $this->getWidgetData();

        $this->assertSame($question->ID, $widgetData['js']['questionID']);
        $this->assertTrue($widgetData['isLoggedIn']);
        $this->assertNotNull($widgetData['socialUser']);
        $this->assertIsA($widgetData['js']['bestAnswerTypes'], 'array');
        $this->assertEqual($widgetData['js']['bestAnswerTypes'], array(
            'author' => 2,
            'moderator' => 1,
            'community' => 3,
        ));

        $this->logOut();
        $this->restoreUrlParameters();
        $this->fixtureInstance->destroy();
    }

    function testDelete() {
        $question = $this->fixtureInstance->make('QuestionActiveModActive');
        $this->addUrlParameters(array('qid' => $question->ID));
        $comments = $this->CI->Model('CommunityQuestion')->getComments($question)->result;
        $comment = $comments[0];
        $this->logIn('useradmin');

        $this->createWidgetInstance();
        $this->getWidgetData();

        $deleteComment = $this->getWidgetMethod('delete');
        ob_start();
        $deleteComment(array(
            'commentID' => $comment->ID,
        ));
        $delete = json_decode(ob_get_contents());
        ob_end_clean();

        $this->assertIsA($delete, '\stdClass');
        $this->assertIsA($delete->result, '\stdClass');
        $this->assertSame($delete->result->ID, (int)$comment->ID);
        $this->assertSame($delete->result->CommunityQuestion->ID, $question->ID);

        $this->logOut();
        $this->restoreUrlParameters();
        $this->fixtureInstance->destroy();
    }

    function testMarkBestAnswer() {
        $question = $this->fixtureInstance->make('QuestionActiveModActive');
        $this->addUrlParameters(array('qid' => $question->ID));
        $comments = $this->CI->Model('CommunityQuestion')->getComments($question)->result;
        $comment = $comments[0];
        $suspendedComment = $this->fixtureInstance->make('CommentSuspendedModActive');
        $this->logIn('useradmin');

        $this->createWidgetInstance();
        $widgetData = $this->getWidgetData();

        $bestAnswers = $this->callWidgetMethodViaWgetRecipient('markBestAnswer', array('questionID' => $question->ID, 'commentID' => $comment->ID, 'chosenByType' => 'Moderator'), true, $this->widgetInstance, 'useradmin');

        $this->assertIsA($bestAnswers, '\stdClass');
        $this->assertIsA($bestAnswers->{$comment->ID}, '\stdClass');
        $this->assertSame($bestAnswers->{$comment->ID}->commentID, (int)$comment->ID);
        $this->assertIsA($bestAnswers->{$comment->ID}->types, '\stdClass');
        $this->assertIsA($bestAnswers->{$comment->ID}->label, 'string');
        $this->assertEqual($bestAnswers->{$comment->ID}->types->{2}, false);
        $this->assertEqual($bestAnswers->{$comment->ID}->types->{1}, true);
        $this->assertEqual($bestAnswers->{$comment->ID}->types->{3}, false);

        // test to check if author is able to mark best answer when authorization is granted to moderator
        $comment = $comments[1];
        $bestAnswers = $this->callWidgetMethodViaWgetRecipient('markBestAnswer', array('questionID' => $question->ID, 'commentID' => $comment->ID, 'chosenByType' => 'Author'), true, $this->widgetInstance, 'useradmin', array('best_answer_types' => 'moderator'));
        $this->assertEqual($bestAnswers->errors{0}->externalMessage, $widgetData['attrs']['label_best_answer_error']);

        // test to check if moderator is able to mark best answer when authorization is granted to author
        $comment = $comments[1];
        $bestAnswers = $this->callWidgetMethodViaWgetRecipient('markBestAnswer', array('questionID' => $question->ID, 'commentID' => $comment->ID, 'chosenByType' => 'Moderator'), true, $this->widgetInstance, 'useradmin', array('best_answer_types' => 'author'));
        $this->assertEqual($bestAnswers->errors{0}->externalMessage, $widgetData['attrs']['label_best_answer_error']);

        // test to check if moderator is able to mark best answer when authorization is granted to none
        $comment = $comments[1];
        $bestAnswers = $this->callWidgetMethodViaWgetRecipient('markBestAnswer', array('questionID' => $question->ID, 'commentID' => $comment->ID, 'chosenByType' => 'Moderator'), true, $this->widgetInstance, 'useradmin', array('best_answer_types' => 'none'));
        $this->assertEqual($bestAnswers->errors{0}->externalMessage, $widgetData['attrs']['label_best_answer_error']);

        // test to confirm that trying to mark a suspended comment as best answer results in error
        $bestAnswers = $this->callWidgetMethodViaWgetRecipient('markBestAnswer', array('questionID' => $question->ID, 'commentID' => $suspendedComment->ID, 'chosenByType' => 'Moderator'), true, $this->widgetInstance, 'useradmin');
        $this->assertEqual($bestAnswers->errors{0}->externalMessage, $widgetData['attrs']['label_mark_suspended_comment_best_answer_error']);

        $this->logOut();
        $this->restoreUrlParameters();
        $this->fixtureInstance->destroy();
    }

    function testSimplifiedBestAnswersList() {
        $question = $this->fixtureInstance->make('QuestionActiveModActive');
        $this->addUrlParameters(array('qid' => $question->ID));
        $comments = $this->CI->Model('CommunityQuestion')->getComments($question)->result;
        $comment = $comments[0];
        $this->logIn('useradmin');

        $this->createWidgetInstance();
        $this->getWidgetData();
        $getSimplifiedBestAnswersList = $this->getWidgetMethod('getSimplifiedBestAnswersList');

        $bestAnswers = $this->CI->model('CommunityQuestion')->markCommentAsBestAnswer($comment->ID)->result;
        $simpleBestAnswers = $getSimplifiedBestAnswersList($bestAnswers);

        $this->assertIsA($simpleBestAnswers, 'array');
        $this->assertIsA($simpleBestAnswers[$comment->ID], 'array');
        $this->assertSame($simpleBestAnswers[$comment->ID]['commentID'], (int)$comment->ID);
        $this->assertIsA($simpleBestAnswers[$comment->ID]['types'], 'array');
        $this->assertFalse($simpleBestAnswers[$comment->ID]['types'][2]);
        $this->assertTrue($simpleBestAnswers[$comment->ID]['types'][1]);
        $this->assertFalse($simpleBestAnswers[$comment->ID]['types'][3]);

        $this->logOut();
        $this->restoreUrlParameters();
        $this->fixtureInstance->destroy();
    }

    function testNewComment() {
        $question = $this->fixtureInstance->make('QuestionActiveModActive');
        $this->addUrlParameters(array('qid' => $question->ID));
        $comments = $this->CI->Model('CommunityQuestion')->getComments($question)->result;
        $comment = $comments[0];
        $this->logIn('useradmin');

        // use the ajax handler to add a comment
        $this->createWidgetInstance();
        $response = $this->callWidgetMethodViaWgetRecipient('newComment', array('commentBody' => 'shibby', 'questionID' => $question->ID, 'fileAttachment' => '{"newFiles":[],"removedFiles":null}'), false, $this->widgetInstance, 'useractive1');

        // check that the comment was created (assume that '> 0' commentID in the rendered content means it got saved)
        preg_match('/data-commentid="(\d+)"/', $response, $matches);
        $this->assertTrue((int)($matches[1]) > 0);
        $this->assertEqual(1, substr_count($response, 'rn_Comments'));
        $this->assertEqual(7, substr_count($response, 'rn_CommentContainer'));
        $this->assertStringContains($response, 'shibby', "The response doesn't have comment body");

        // Invalid question id results in error message.
        $response = $this->callWidgetMethodViaWgetRecipient('newComment', array('commentBody' => 'shibby', 'questionID' => -1, 'fileAttachment' => '{"newFiles":[],"removedFiles":null}'), true, $this->widgetInstance, 'useractive1');
        $this->assertIdentical($response->errors[0]->externalMessage, 'Invalid Communityquestion ID: -1');

        $response = $this->callWidgetMethodViaWgetRecipient('newComment', array('commentBody' => 'shibby', 'questionID' => PHP_INT_MAX, 'fileAttachment' => '{"newFiles":[],"removedFiles":null}'), true, $this->widgetInstance, 'useractive1');
        $this->assertIdentical($response->errors[0]->externalMessage, 'Invalid ID: No such CommunityQuestion with ID = 2147483647');

        $this->logOut();
        $this->restoreUrlParameters();
        $this->fixtureInstance->destroy();
    }

    function testEditLockedComment() {
        $question = $this->fixtureInstance->make('QuestionLockedUserActive');
        $this->addUrlParameters(array('qid' => $question->ID));
        $comments = $this->CI->Model('CommunityQuestion')->getComments($question)->result;
        $comment = $comments[0];
        $this->logIn('useradmin');

        $this->createWidgetInstance();
        $response = $this->callWidgetMethodViaWgetRecipient('edit', array('commentBody' => 'shibby', 'commentID' => $comment->ID), true, $this->widgetInstance, 'useractive1');
        $this->assertIdentical($response->errors[0]->externalMessage, 'User does not have permission to edit this comment');

        $this->logOut();
        $this->restoreUrlParameters();
        $this->fixtureInstance->destroy();
    }

    function testReply() {
        $question = $this->fixtureInstance->make('QuestionActiveUserActive');
        $activeComment = $this->fixtureInstance->make('CommentActiveUserActive');
        $deletedComment = $this->fixtureInstance->make('CommentDeletedModActive');
        $suspendedComment = $this->fixtureInstance->make('CommentSuspendedModActive');
        $this->addUrlParameters(array('qid' => $question->ID));
        $this->logIn('useractive2');

        // use the ajax handler to add a comment
        $this->createWidgetInstance();
        $widgetData = $this->getWidgetData();
        $response = $this->callWidgetMethodViaWgetRecipient('reply', array('commentBody' => 'shibby123', 'questionID' => $question->ID, 'commentID' => $activeComment->ID), false, $this->widgetInstance, 'useractive2');
        $response = json_decode($response);
        $this->assertIdentical($response->result->Body, 'shibby123');
        $this->assertIdentical($response->result->CommunityQuestion->ID, $question->ID);
        $this->assertTrue(isset($response->result->Parent));
        $this->assertTrue(isset($response->result->Parent->CreatedByCommunityUser));
        $this->assertTrue(isset($response->result->Parent->CommunityQuestion));

        // Invalid comment id results in error message.
        $response = $this->callWidgetMethodViaWgetRecipient('reply', array('commentBody' => 'shibby123', 'questionID' => $question->ID, 'commentID' => $deletedComment->ID), false, $this->widgetInstance, 'useractive2');
        $response = json_decode($response);
        $this->assertIdentical($response->errors[0]->externalMessage, 'Cannot find comment, which may have been deleted by another user.');

        // trying to reply to suspended comment as a normal user
        $response = $this->callWidgetMethodViaWgetRecipient('reply', array('commentBody' => 'shibby123', 'questionID' => $question->ID, 'commentID' => $suspendedComment->ID), false, $this->widgetInstance, 'useractive2');
        $response = json_decode($response);
        $this->assertIdentical($response->errors[0]->externalMessage, 'User does not have read permission on this comment');

        // trying to reply to suspended comment as a moderator
        $response = $this->callWidgetMethodViaWgetRecipient('reply', array('commentBody' => 'shibby123', 'questionID' => $question->ID, 'commentID' => $suspendedComment->ID), false, $this->widgetInstance, 'modactive1');
        $response = json_decode($response);
        $this->assertIdentical($response->errors[0]->externalMessage, $widgetData['attrs']['label_reply_suspended_comment_error']);

        $this->logOut();
        $this->restoreUrlParameters();
        $this->fixtureInstance->destroy();
    }

    function testRenderCommentPage() {
        $question = $this->fixtureInstance->make('QuestionActiveModActive');
        $this->addUrlParameters(array('qid' => $question->ID));
        $comments = $this->CI->Model('CommunityQuestion')->getComments($question)->result;
        $comment = $comments[0];
        $cookies = Helper::logInUser('useradmin');

        $defaultParamsToPass = array('comments_per_page' => 1);
        $this->createWidgetInstance($defaultParamsToPass);
        $widgetData = $this->getWidgetData();
        $response = $this->callAjaxMethod('renderCommentPage', array('sortOrder' => 'ASC', 'pageID' => 4, 'questionID' => $question->ID), false, $this->widgetInstance, $defaultParamsToPass, true, $cookies);
        // 4 pages, with 1 comment on the fourth page (that has no child comments)
        $this->assertIsA($response, 'string');
        // four total pages
        $this->assertStringContains($response, 'Page 1 of 4', "Not on page 1 to 4");
        // current page is 4
        $this->assertStringContains($response, 'Page 4 of 4 selected', "Current page is not 4");
        // found one comment
        preg_match_all('/data-commentid="(\d+)"/', $response, $matches);
        $this->assertEqual(1, count($matches[1]));
        $this->assertEqual(1, substr_count($response, 'rn_Comments'));
        $this->assertEqual(1, substr_count($response, 'rn_CommentContainer'));
        $this->assertEqual(0, substr_count($response, 'rn_Replies'));

        Helper::logOutUser('useradmin', $cookies['rawSession']);
        $this->restoreUrlParameters();
        $this->fixtureInstance->destroy();
    }

    function testParentCommentsUsedToDeterminePagination() {
        $question = $this->fixtureInstance->make('QuestionActiveModActive');
        $this->addUrlParameters(array('qid' => $question->ID));
        $this->createWidgetInstance();

        $this->setWidgetAttributes(array('comments_per_page' => 1));
        $widgetData = $this->getWidgetData();
        $this->assertTrue($widgetData['displayPagination']);

        $this->setWidgetAttributes(array('comments_per_page' => 20));
        $widgetData = $this->getWidgetData();
        $this->assertFalse($widgetData['displayPagination']);

        $this->restoreUrlParameters();

        $this->fixtureInstance->destroy();
    }

    function testGetPageNumberContainingComment() {
        $question = $this->fixtureInstance->make('QuestionActiveModActive');
        $widget = $this->createWidgetInstance();
        $method = $this->getWidgetMethod('getPageNumberContainingComment', $widget);

        $this->logIn('useradmin');
        $comments = $this->CI->Model('CommunityQuestion')->getComments($question)->result;

        $this->assertSame(1, $method(PHP_INT_MAX));

        $this->addUrlParameters(array('qid' => $question->ID));
        $widget = $this->createWidgetInstance();
        $method = $this->getWidgetMethod('getPageNumberContainingComment', $widget);

        $this->assertSame(1, $method($comments[0]->ID));

        $widget = $this->createWidgetInstance(array('comments_per_page' => 2));
        $method = $this->getWidgetMethod('getPageNumberContainingComment', $widget);
        $this->assertSame(2, $method($comments[3]->ID));

        $this->logOut();
        $this->restoreUrlParameters();
        $this->fixtureInstance->destroy();
    }

    function testFetchPaginatedComments() {
        $question = $this->fixtureInstance->make('QuestionActiveModActive');

        $widget = $this->createWidgetInstance(array('comments_per_page' => 2));
        $widget->question = $question;
        $method = $this->getWidgetMethod('fetchPaginatedComments', $widget);

        $this->logIn('useradmin');
        $comments = $this->CI->Model('CommunityQuestion')->getComments($question)->result;

        // Page is honored.
        $this->addUrlParameters(array('page' => 2));
        $method();
        $this->assertSame(2, $widget->data['currentPage']);

        // Invalid page defaults to page 1.
        $this->restoreUrlParameters();
        $this->addUrlParameters(array('page' => 212));
        $method();
        $this->assertSame(1, $widget->data['currentPage']);

        // Comment id is honored.
        $this->restoreUrlParameters();
        $this->addUrlParameters(array('comment' => $comments[3]->ID));
        $method();
        $this->assertSame(2, $widget->data['currentPage']);

        // Invalid comment id defaults to page 1.
        $this->restoreUrlParameters();
        $this->addUrlParameters(array('comment' => 'wolves'));
        $method();
        $this->assertSame(1, $widget->data['currentPage']);

        // Page takes precedence over comment id.
        $this->restoreUrlParameters();
        $this->addUrlParameters(array('comment' => $comments[0]->ID, 'page' => 2));
        $method();
        $this->assertSame(2, $widget->data['currentPage']);

        $this->logOut();
        $this->restoreUrlParameters();
        $this->fixtureInstance->destroy();
    }

    function testTransformChosenByType() {
        $widget = $this->createWidgetInstance();
        $transformChosenByType = $this->getWidgetMethod('transformChosenByType');

        $this->assertSame(SSS_BEST_ANSWER_MODERATOR, $transformChosenByType("Moderator"));
        $this->assertSame(SSS_BEST_ANSWER_AUTHOR, $transformChosenByType("Author"));
        $this->assertNull($transformChosenByType());
    }

    function testCheckCommentExists() {

        //Error message is displayed when a deleted comment is attempted to be shared
        list($fixtureInstance, $questionActive, $commentDeleted) = $this->getFixtures(array( 'QuestionActiveAllBestAnswer', 'CommentDeletedModActive'));
        $instance = $this->createWidgetInstance();
        $cookies = Helper::logInUser('useradmin');

        $response = $this->callAjaxMethod('checkCommentExists', array('commentID' => $commentDeleted->ID), true, $this->widgetInstance, array(), true, $cookies);

        $this->assertNull($response->result);
        $this->assertIdentical($response->errors[0]->externalMessage, 'Cannot find comment, which may have been deleted by another user.');

        Helper::logOutUser('useradmin', $cookies['rawSession']);
        $this->fixtureInstance->destroy();

        //Sharing an active comment doesn't display any errors
        list($fixtureInstance, $questionActive, $commentActive) = $this->getFixtures(array( 'QuestionActiveAllBestAnswer', 'CommentActiveUserActive'));
        $cookies = Helper::logInUser('useradmin');

        $response = $this->callAjaxMethod('checkCommentExists', array('commentID' => $commentActive->ID), true, $this->widgetInstance, array(), true, $cookies);

        $this->assertNotNull($response);
        $this->assertNull($response->errors);

        Helper::logOutUser('useradmin', $cookies['rawSession']);
        $this->fixtureInstance->destroy();
    }

    function testHighlightContent() {
        $question = $this->fixtureInstance->make('QuestionActiveModActive');
        $this->addUrlParameters(array('qid' => $question->ID));
        $this->logIn($question->CreatedByCommunityUser->Contact->Login);

        $widget = $this->createWidgetInstance(array('author_roleset_callout' => "5|Posted by a moderator; 1,7|Posted by an admin"));
        $method = $this->getWidgetMethod('highlightContent');
        $method();
        $this->assertEqual($widget->data['author_roleset_callout'], array(
            '5' => 'Posted by a moderator',
            '1' => 'Posted by an admin',
            '7' => 'Posted by an admin',
        ));
        $this->assertEqual($widget->data['author_roleset_styling'], array(
            'Posted by a moderator' => 'rn_AuthorStyle_1',
            'Posted by an admin' => 'rn_AuthorStyle_2',
        ));

        $this->logOut();
        $this->restoreUrlParameters();
        $this->fixtureInstance->destroy();
    }
}
