<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\UnitTest\Fixture as Fixture;

class TestBestAnswerDisplayHelper extends CPTestCase {
    public $testingClass = "RightNow/Helpers/BestAnswerDisplayHelper";

    function __construct() {
        parent::__construct();
        $this->helper = new \RightNow\Helpers\BestAnswerDisplayHelper;
        $this->fixtureInstance = new Fixture();
    }

    function setUp() {
        $this->logIn();
        $this->user = $this->CI->model('CommunityUser')->get()->result;
        $this->helper->bestAnswerTypes = array(
            'author'    => SSS_BEST_ANSWER_AUTHOR,
            'moderator' => SSS_BEST_ANSWER_MODERATOR,
        );
    }

    function tearDown() {
        parent::tearDown();
        $this->fixtureInstance->destroy();
        $this->helper->question = null;
        $this->helper->bestAnswerTypes = null;
    }

   function testShouldDisplayBestAnswerRemoval() {
        $this->helper->question = $this->fixtureInstance->make('QuestionActiveUserSuspended');
        $user = $this->fixtureInstance->make('UserSuspended');
        $nonAuthorUser = $this->fixtureInstance->make('UserActive1');
        $comment = $this->CI->model('CommunityQuestion')->getComments($this->helper->question)->result[0];

        //Author is Inactive
        $this->logIn($user->Login);
        $this->assertFalse($this->helper->shouldDisplayBestAnswerRemoval($comment, $user));
        $this->logOut();

        //User isn't Author or Moderator
        $this->logIn($nonAuthorUser->Login);
        $this->assertFalse($this->helper->shouldDisplayBestAnswerRemoval($comment, $nonAuthorUser));
        $this->logOut();
        $this->fixtureInstance->destroy();

        $this->helper->question = $this->fixtureInstance->make('QuestionLockedUserActive');
        $user = $this->fixtureInstance->make('UserActive1');
        $comment = $this->CI->model('CommunityQuestion')->getComments($this->helper->question)->result[0];

        //Question is Locked
        $this->logIn($user->Login);
        $this->assertFalse($this->helper->shouldDisplayBestAnswerRemoval($comment, $user));
        $this->logOut();
        $this->fixtureInstance->destroy();

        $this->helper->question = $this->fixtureInstance->make('QuestionActiveAuthorBestAnswer');
        $user = $this->helper->question->CreatedByCommunityUser;

        //Active Author, Active Best Answer
        $this->logIn($user->Login);
        $this->assertSame('Author', $this->helper->shouldDisplayBestAnswerRemoval($this->helper->question->BestCommunityQuestionAnswers[0]->CommunityComment, $user));
        $this->logOut();
        $this->fixtureInstance->destroy();

        $this->helper->question = $this->fixtureInstance->make('QuestionActiveModeratorBestAnswer');
        $user = $this->fixtureInstance->make('UserModActive');
        $this->logIn($user->Login);
        $this->assertSame('Moderator', $this->helper->shouldDisplayBestAnswerRemoval($this->helper->question->BestCommunityQuestionAnswers[0]->CommunityComment, $user));
        $this->logOut();
        $this->fixtureInstance->destroy();

        //User is Author and Moderator
        $this->helper->question = $this->fixtureInstance->make('QuestionActiveModActiveSameBestAnswer');
        $user = $this->fixtureInstance->make('UserModActive');
        $this->logIn($user->Login);
        // TODO: Due to migration to Connect 1.4 the following line cause test failure. Commenting the line
        // until further investigation
        // $comment = $this->CI->model('CommunityQuestion')->getComments($this->helper->question)->result[0];
        $this->CI->Model('CommunityQuestion')->markCommentAsBestAnswer($comment->ID, SSS_BEST_ANSWER_AUTHOR);

        $this->assertSame($this->helper->question->BestCommunityQuestionAnswers[0]->CommunityComment,
                          $this->helper->question->BestCommunityQuestionAnswers[1]->CommunityComment);

        //Author/Mod - Same Best Answer chosen
        $this->assertSame('Both', $this->helper->shouldDisplayBestAnswerRemoval($this->helper->question->BestCommunityQuestionAnswers[0]->CommunityComment, $user));
        $this->logOut();
        $this->fixtureInstance->destroy();
    }

    function testCommentIsBestAnswer() {
        $this->fixtureQuestionBestAnswer = $this->fixtureInstance->make('QuestionActiveAuthorBestAnswer');
        $this->helper->question = $this->fixtureQuestionBestAnswer;
        $comments = $this->CI->model('CommunityQuestion')->getComments($this->fixtureQuestionBestAnswer)->result;

        // CommentPendingModActive not best answer
        $this->assertFalse($this->helper->commentIsBestAnswer($comments[1]));

        // CommentActiveUserActive selected as best answer
        $this->assertTrue($this->helper->commentIsBestAnswer($comments[0]));

        $this->fixtureInstance->destroy();
    }
}
