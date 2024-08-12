<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\UnitTest\Fixture as Fixture;

class TestQuestionCommentsHelper extends CPTestCase {
    public $testingClass = "RightNow/Helpers/QuestionCommentsHelper";

    function __construct() {
        parent::__construct();
        $this->helper = new \RightNow\Helpers\QuestionCommentsHelper;

        $this->commentStatus = array(
            'pending' => $this->CI->model('CommunityComment')->getSocialObjectStatuses(STATUS_TYPE_SSS_COMMENT_PENDING)->result[0],
            'active' => $this->CI->model('CommunityComment')->getSocialObjectStatuses(STATUS_TYPE_SSS_COMMENT_ACTIVE)->result[0],
            'suspended' => $this->CI->model('CommunityComment')->getSocialObjectStatuses(STATUS_TYPE_SSS_COMMENT_SUSPENDED)->result[0],
            'deleted' => $this->CI->model('CommunityComment')->getSocialObjectStatuses(STATUS_TYPE_SSS_COMMENT_DELETED)->result[0],
        );

        $this->fixtureInstance = new Fixture();
    }

    function setUp() {
        $this->logIn();
        $this->user = $this->CI->model('CommunityUser')->get()->result;
        $this->helper->bestAnswerTypes = array(
            'author'    => SSS_BEST_ANSWER_AUTHOR,
            'moderator' => SSS_BEST_ANSWER_MODERATOR,
            'community' => SSS_BEST_ANSWER_COMMUNITY,
        );
    }

    function tearDown() {
        parent::tearDown();
        $this->fixtureInstance->destroy();
        $this->user = null;
        $this->helper->question = null;
        $this->helper->bestAnswerTypes = null;
    }

    function testFormattedTimestamp(){
        $result = $this->helper->formattedTimestamp(1398441034);
        $this->assertSame($result, '04/25/2014 09:50 AM');

        $result = $this->helper->formattedTimestamp('yesterday');
        $this->assertPattern('@\d+/\d+/\d{4}\s{1}\d+:\d+\s{1}[AMP]{2}$@', $result, "Result of 'yesterday' to strtotime doesn't look like a valid date.");

        $result = $this->helper->formattedTimestamp(1398441034, false);
        $this->assertSame($result, '04/25/2014 09:50 AM');

        $result = $this->helper->formattedTimestamp(1398441034, true);
        $this->assertSame($result, '2014-04-25');
    }

    function testShouldDisplayNewCommentArea(){
        $this->fixtureQuestionLocked = $this->fixtureInstance->make('QuestionLockedUserActive');
        $this->fixtureQuestionActive = $this->fixtureInstance->make('QuestionActiveModActive');
        $this->fixtureContactNoSocial = $this->fixtureInstance->make('ContactActive1');
        $this->fixtureUserActive = $this->fixtureInstance->make('UserActive1');

        $this->logOut();
        //Don't display reply on locked questions
        $this->helper->question = $this->fixtureQuestionLocked;
        $this->assertFalse($this->helper->shouldDisplayNewCommentArea());

        //Display for active questions
        $this->helper->question = $this->fixtureQuestionActive;
        $this->assertTrue($this->helper->shouldDisplayNewCommentArea());

        //Display for users without a social profile
        $this->logIn($this->fixtureContactNoSocial->Login);
        $this->helper->question = $this->fixtureQuestionActive;
        $this->assertTrue($this->helper->shouldDisplayNewCommentArea());
        $this->logOut();

        //Display for users with social profile
        $this->logIn($this->fixtureUserActive->Contact->Login);
        $this->helper->question = $this->fixtureQuestionActive;
        $this->assertTrue($this->helper->shouldDisplayNewCommentArea());

        $this->logOut();
        $this->fixtureInstance->destroy();
    }

    function testShouldDisplayCommentReply() {
        $this->logOut();
        $this->fixtureQuestionActive = $this->fixtureInstance->make('QuestionActiveModActive');
        $this->fixtureQuestionLocked = $this->fixtureInstance->make('QuestionLockedUserActive');
        $this->fixtureUserActive = $this->fixtureInstance->make('UserActive1');

        $this->helper->question = $this->fixtureQuestionActive;
        $comments = $this->CI->model('CommunityQuestion')->getComments($this->fixtureQuestionActive)->result;

        //Display comment reply on active questions when not logged in
        $this->assertTrue($this->helper->shouldDisplayCommentReply($comments[0]));

        //Don't display reply link for replies to a comment when not logged in
        $this->assertFalse($this->helper->shouldDisplayCommentReply($comments[3]));

        //Display comment reply on active questions when logged in as active user
        $this->logIn($this->fixtureUserActive->Contact->Login);
        $this->assertTrue($this->helper->shouldDisplayCommentReply($comments[0]));

        //Don't display comment reply on locked questions
        $this->helper->question = $this->fixtureQuestionLocked;
        $comments = $this->CI->model('CommunityQuestion')->getComments($this->fixtureQuestionLocked)->result;
        $this->assertFalse($this->helper->shouldDisplayCommentReply($comments[0]));
        $this->logOut();
        $this->fixtureInstance->destroy();
    }

    function testShouldDisplayBestAnswerActionsForAuthor() {
        $this->logOut();
        $this->fixtureQuestionBestAnswer = $this->fixtureInstance->make('QuestionActiveAuthorBestAnswer');

        $this->helper->question = $this->fixtureQuestionBestAnswer;
        $comments = $this->CI->model('CommunityQuestion')->getComments($this->fixtureQuestionBestAnswer)->result;

        //No user logged in
        $this->assertFalse($this->helper->shouldDisplayBestAnswerActions($comments[1], null, true, array("author")));

        //Non-active moderator logged in
        $this->fixtureArchivedMod = $this->fixtureInstance->make('UserModArchive');
        $this->logIn($this->fixtureArchivedMod->Contact->Login);
        $this->user = $this->CI->model('CommunityUser')->get()->result;
        $this->assertFalse($this->helper->shouldDisplayBestAnswerActions($comments[1], $this->user, true, array("author")));
        $this->logout();

        //User logged in who is the author
        $this->logIn($this->fixtureQuestionBestAnswer->CreatedByCommunityUser->Contact->Login);
        $this->user = $this->CI->model('CommunityUser')->get()->result;
        $this->assertSame("Author", $this->helper->shouldDisplayBestAnswerActions($comments[1], $this->user, true, array("author")));
        $this->logOut();
        //Since permission checks are being cached, we are destroying and remaking the question
        $this->fixtureInstance->destroy();

        //User logged in who is not the author
        $this->fixtureQuestionBestAnswer = $this->fixtureInstance->make('QuestionActiveAuthorBestAnswer');
        $this->helper->question = $this->fixtureQuestionBestAnswer;
        $comments = $this->CI->model('CommunityQuestion')->getComments($this->fixtureQuestionBestAnswer)->result;

        $this->fixtureUserActive2 = $this->fixtureInstance->make('UserActive2');
        $this->logIn($this->fixtureUserActive2->Contact->Login);
        $this->user = $this->CI->model('CommunityUser')->get()->result;
        $this->assertFalse($this->helper->shouldDisplayBestAnswerActions($comments[1], $this->user, true, array("author")));
        $this->logOut();
        $this->fixtureInstance->destroy();

        //Suspended question, active best answer
        $this->helper->question = $this->fixtureInstance->make('QuestionSuspendedBestAnswer');
        $this->logIn($this->helper->question->CreatedByCommunityUser->Contact->Login);
        $this->user = $this->CI->model('CommunityUser')->get()->result;
        $comments = $this->CI->model('CommunityQuestion')->getComments($this->helper->question)->result;
        $this->assertFalse($this->helper->shouldDisplayBestAnswerActions($comments[1], $this->user, true, array("author")));
        $this->logOut();
        $this->fixtureInstance->destroy();
    }


    function testShouldDisplayBestAnswerActionsForModerator() {
        $this->fixtureUserMod = $this->fixtureInstance->make('UserModActive');
        $this->fixtureQuestion = $this->fixtureInstance->make('QuestionActiveAuthorBestAnswer');
        $this->logIn($this->fixtureUserMod->Contact->Login);
        $this->user = $this->CI->model('CommunityUser')->get()->result;

        $this->helper->question = $this->fixtureQuestion;
        $comments = $this->CI->model('CommunityQuestion')->getComments($this->fixtureQuestion)->result;

        // CommentActiveUserArchive
        $this->assertSame("Moderator", $this->helper->shouldDisplayBestAnswerActions($comments[6], $this->user, true, array("moderator")));

        // CommentActiveModActive selected as best answer by author
        $this->assertSame("Moderator", $this->helper->shouldDisplayBestAnswerActions($comments[4], $this->user, true, array("moderator")));

        // CommentSuspendedModActive
        $this->assertFalse($this->helper->shouldDisplayBestAnswerActions($comments[5], $this->user, true, array("moderator")));
        $this->logOut();
        $this->fixtureInstance->destroy();

        //Suspended question, active best answer
        $this->helper->question = $this->fixtureInstance->make('QuestionSuspendedBestAnswer');
        $this->logIn('modactive1');
        $this->user = $this->CI->model('CommunityUser')->get()->result;
        $comments = $this->CI->model('CommunityQuestion')->getComments($this->helper->question)->result;
        $this->assertFalse($this->helper->shouldDisplayBestAnswerActions($comments[0], $this->user, true, array("moderator")));
        $this->logOut();
        $this->fixtureInstance->destroy();
    }

    function testShouldDisplayBestAnswerActionsForBoth() {
        $this->fixtureUserMod = $this->fixtureInstance->make('UserModActive');
        $this->fixtureQuestion = $this->fixtureInstance->make('QuestionActiveModActiveSameBestAnswer');
        $this->logIn($this->fixtureUserMod->Contact->Login);
        $this->user = $this->CI->model('CommunityUser')->get()->result;

        $this->helper->question = $this->fixtureQuestion;
        $comments = $this->CI->model('CommunityQuestion')->getComments($this->fixtureQuestion)->result;

        // CommentActiveUserArchive
        $this->assertSame("Both", $this->helper->shouldDisplayBestAnswerActions($comments[6], $this->user, true, array("author", "moderator")));

        // CommentActiveUserActive selected as best answer by both mod and author
        $this->assertFalse($this->helper->shouldDisplayBestAnswerActions($comments[0], $this->user, true, array("author", "moderator")));

        $this->CI->Model('CommunityQuestion')->markCommentAsBestAnswer($comments[4]->ID, SSS_BEST_ANSWER_AUTHOR);

        // CommentActiveModActive selected as best answer by author
        $this->assertSame("Moderator", $this->helper->shouldDisplayBestAnswerActions($comments[4], $this->user, true, array("author", "moderator")));

        // CommentActiveUserActive selected as best answer by moderator
        $this->assertSame("Author", $this->helper->shouldDisplayBestAnswerActions($comments[0], $this->user, true, array("author", "moderator")));

        // CommentSuspendedModActive
        $this->assertFalse($this->helper->shouldDisplayBestAnswerActions($comments[5], $this->user, true, array("author", "moderator")));

        $this->logOut();
        $this->fixtureInstance->destroy();
    }

    function testShouldDisplayBestAnswerActionsOnChildCommentForModerator() {
        $this->fixtureUserMod = $this->fixtureInstance->make('UserModActive');
        $this->fixtureQuestionBestAnswer = $this->fixtureInstance->make('QuestionActiveAuthorBestAnswerReply');
        $this->logIn($this->fixtureUserMod->Contact->Login);
        $this->user = $this->CI->model('CommunityUser')->get()->result;

        $this->helper->question = $this->fixtureQuestionBestAnswer;
        $comments = $this->CI->model('CommunityQuestion')->getComments($this->fixtureQuestionBestAnswer)->result;

        // CommentActiveModActive - A child comment not selected as best answer.
        // Should show best answer actions
        $this->assertSame("Moderator", $this->helper->shouldDisplayBestAnswerActions($comments[4], $this->user, true, array("moderator")));

        // CommentActiveModArchive - A child comment already selected as best answer by the author
        $this->assertSame("Moderator", $this->helper->shouldDisplayBestAnswerActions($comments[3], $this->user, true, array("moderator")));

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

    function testShouldDisplayCommentContent() {
        $this->fixtureUserMod = $this->fixtureInstance->make('UserModActive');
        $this->fixtureQuestionActive = $this->fixtureInstance->make('QuestionActiveModActive');
        $comments = $this->CI->model('CommunityQuestion')->getComments($this->fixtureQuestionActive)->result;

        $this->logIn($this->fixtureUserMod->Contact->Login);

        // Pending comment, user is moderator
        $this->assertTrue($this->helper->shouldDisplayCommentContent($comments[1]));

        // Active comment, user is moderator
        $this->assertTrue($this->helper->shouldDisplayCommentContent($comments[0]));

        // Suspended comment, user is moderator
        $this->assertTrue($this->helper->shouldDisplayCommentContent($comments[5]));

        $this->logOut();
        $this->fixtureUserActive = $this->fixtureInstance->make('UserActive1');
        $this->logIn($this->fixtureUserMod->Contact->Login);
        $comments = $this->CI->model('CommunityQuestion')->getComments($this->fixtureQuestionActive)->result;

        // Pending comment, user is author
        $this->assertTrue($this->helper->shouldDisplayCommentContent($comments[7]));

        $this->fixtureInstance->destroy();
    }

    function testShouldDisplayComment() {
        list($fixtureInstance, $question1, $question2) = $this->getFixtures(array(
            'QuestionWithPendingCommentsByDifferentAuthors',
            'QuestionWithPendingCommentsByDifferentAuthors',
        ));

        // Moderator should see all
        $this->logIn();
        $comments = $this->CI->model('CommunityQuestion')->getComments($question1)->result;
        $this->assertTrue($this->helper->shouldDisplayComment($comments[0]));
        $this->assertTrue($this->helper->shouldDisplayComment($comments[1]));
        $this->assertTrue($this->helper->shouldDisplayComment($comments[2]));
        $this->logOut();

        // non-moderator should only see pending comments for which they authored
        $this->logIn();
        // get comments logged in a moderator so we're ensured to get them all
        $comments = $this->CI->model('CommunityQuestion')->getComments($question2)->result;
        $this->logOut();
        // grab user who authored comment #2 and log in as them
        $commentAuthoredByUser = $comments[1];
        $user = $commentAuthoredByUser->CreatedByCommunityUser;
        $contact = $this->CI->model('Contact')->getForSocialUser($user->ID)->result;
        $this->logIn($contact->Login);
        // active comment
        $this->assertTrue($this->helper->shouldDisplayComment($comments[0]));
        // pending comment authored by user
        $this->assertEqual($commentAuthoredByUser->CreatedByCommunityUser->ID, $user->ID);
        $this->assertEqual($commentAuthoredByUser->StatusWithType->StatusType->ID, STATUS_TYPE_SSS_COMMENT_PENDING);
        $this->assertTrue($this->helper->shouldDisplayComment($commentAuthoredByUser));
        // pending comment authored by another user
        $this->assertNotEqual($comments[2]->CreatedByCommunityUser->ID, $user->ID);
        $this->assertEqual($comments[2]->StatusWithType->StatusType->ID, STATUS_TYPE_SSS_COMMENT_PENDING);
        $this->assertFalse($this->helper->shouldDisplayComment($comments[2]));
        $this->logOut();

        $fixtureInstance->destroy();
    }

    function testGetBestAnswerLabelNoBestAnswer() {
        //No best answer
        $this->helper->question = $this->fixtureInstance->make('QuestionActiveModActive');
        $comment = $this->CI->model('CommunityQuestion')->getComments($this->helper->question)->result[0];
        $this->assertStringContains($this->helper->getBestAnswerLabel($comment), "not a best answer");

        $this->fixtureInstance->destroy();
    }

    function testGetBestAnswerLabelAuthorSelectedBestAnswer() {
        //Author selected best answer
        $this->helper->question = $this->fixtureInstance->make('QuestionActiveSingleComment');
        $comment = $this->CI->model('CommunityQuestion')->getComments($this->helper->question)->result[0];
        $this->assertStringContains($this->helper->getBestAnswerLabel($comment), "author");

        $this->fixtureInstance->destroy();
    }

    function testGetBestAnswerLabelModeratorSelectedBestAnswer() {
        //Moderator selected best answer
        $this->helper->question = $this->fixtureInstance->make('QuestionActiveModeratorBestAnswer');
        $comment = $this->CI->model('CommunityQuestion')->getComments($this->helper->question)->result[0];
        $this->assertStringContains($this->helper->getBestAnswerLabel($comment), "moderator");

        $this->fixtureInstance->destroy();
    }

    function testGetBestAnswerLabelCommunitySelectedBestAnswer() {
        //Community selected best answer
        $this->helper->question = $this->fixtureInstance->make('QuestionActiveCommunityBestAnswer');
        $comment = $this->CI->model('CommunityQuestion')->getComments($this->helper->question)->result[0];
        $this->assertStringContains($this->helper->getBestAnswerLabel($comment), "community");

        $this->fixtureInstance->destroy();
    }

    function testGetLabelForBestAnswerTypes() {
        $result = $this->helper->getLabelForBestAnswerTypes(array());
        $this->assertNull($result);

        $result = $this->helper->getLabelForBestAnswerTypes(array( SSS_BEST_ANSWER_COMMUNITY => true ));
        $this->assertStringContains($result, "community");
        $this->assertStringDoesNotContain($result, "author");
        $this->assertStringDoesNotContain($result, "moderator");

        $result = $this->helper->getLabelForBestAnswerTypes(array( SSS_BEST_ANSWER_COMMUNITY => true, SSS_BEST_ANSWER_MODERATOR => true ));
        $this->assertStringContains($result, "community");
        $this->assertStringContains($result, "moderator");
        $this->assertStringDoesNotContain($result, "author");

        $result = $this->helper->getLabelForBestAnswerTypes(array( SSS_BEST_ANSWER_COMMUNITY => true, SSS_BEST_ANSWER_MODERATOR => true, SSS_BEST_ANSWER_AUTHOR => true ));
        $this->assertStringContains($result, "community");
        $this->assertStringContains($result, "moderator");
        $this->assertStringContains($result, "author");
    }

    function testIsCurrentPage() {
        $currentPage = 2;

        $response = $this->helper->isCurrentPage(2, $currentPage);
        $this->assertIsA($response, bool);
        $this->assertTrue($response);
        $this->assertFalse($this->helper->isCurrentPage(0, $currentPage));
        $this->assertFalse($this->helper->isCurrentPage(1, $currentPage));
        $this->assertFalse($this->helper->isCurrentPage(4, $currentPage));
        $this->assertFalse($this->helper->isCurrentPage(8, $currentPage));
    }

    function testPaginationLinkTitle() {
        $labelPage = 'Page %s of %s';
        $endPage = 8;

        $response = $this->helper->paginationLinkTitle($labelPage, 2, $endPage);
        $this->assertNotNull($response);
        $this->assertIsA($response, string);
        $this->assertEqual($response, 'Page 2 of 8');
        $this->assertNotEqual($response, 'Page 3 of 8');
        $this->assertNotEqual($this->helper->paginationLinkTitle($labelPage, 5, $endPage), 'Page 2 of 8');
    }

    function testShouldShowHellip() {
        $currentPage = 4;
        $endPage = 8;

        $response = $this->helper->shouldShowHellip(2, $currentPage, $endPage);
        $this->assertIsA($response, bool);
        $this->assertTrue($response);
        $this->assertFalse($this->helper->shouldShowHellip(3, $currentPage, $endPage));
        $this->assertFalse($this->helper->shouldShowHellip(5, $currentPage, $endPage));
        $this->assertTrue($this->helper->shouldShowHellip(6, $currentPage, $endPage));

        $currentPage = 1;
        $this->assertFalse($this->helper->shouldShowHellip(3, $currentPage, $endPage));
        $this->assertTrue($this->helper->shouldShowHellip(4, $currentPage, $endPage));

        $currentPage = 2;
        $this->assertFalse($this->helper->shouldShowHellip(3, $currentPage, $endPage));
        $this->assertTrue($this->helper->shouldShowHellip(4, $currentPage, $endPage));

        $currentPage = 8;
        $this->assertFalse($this->helper->shouldShowHellip(3, $currentPage, $endPage));
        $this->assertTrue($this->helper->shouldShowHellip(5, $currentPage, $endPage));
    }

    function testShouldShowPageNumber() {
        $currentPage = 2;
        $endPage = 8;

        $response = $this->helper->shouldShowPageNumber(1, $currentPage, $endPage);
        $this->assertIsA($response, bool);
        $this->assertTrue($response);
        $this->assertTrue($this->helper->shouldShowPageNumber(3, $currentPage, $endPage));
        $this->assertFalse($this->helper->shouldShowPageNumber(4, $currentPage, $endPage));
        $this->assertTrue($this->helper->shouldShowHellip(4, $currentPage, $endPage));
        $this->assertTrue($this->helper->shouldShowPageNumber(8, $currentPage, $endPage));

        $currentPage = 1;
        $this->assertTrue($this->helper->shouldShowPageNumber(3, $currentPage, $endPage));
        $this->assertFalse($this->helper->shouldShowPageNumber(4, $currentPage, $endPage));
        $this->assertTrue($this->helper->shouldShowHellip(4, $currentPage, $endPage));
        $this->assertTrue($this->helper->shouldShowPageNumber(8, $currentPage, $endPage));

        $currentPage = 4;
        $this->assertTrue($this->helper->shouldShowPageNumber(1, $currentPage, $endPage));
        $this->assertFalse($this->helper->shouldShowPageNumber(2, $currentPage, $endPage));
        $this->assertTrue($this->helper->shouldShowHellip(2, $currentPage, $endPage));
        $this->assertTrue($this->helper->shouldShowPageNumber(3, $currentPage, $endPage));
        $this->assertTrue($this->helper->shouldShowPageNumber(5, $currentPage, $endPage));
        $this->assertTrue($this->helper->shouldShowHellip(6, $currentPage, $endPage));
        $this->assertTrue($this->helper->shouldShowPageNumber(8, $currentPage, $endPage));

        $currentPage = 8;
        $this->assertTrue($this->helper->shouldShowPageNumber(1, $currentPage, $endPage));
        $this->assertFalse($this->helper->shouldShowPageNumber(2, $currentPage, $endPage));
        $this->assertTrue($this->helper->shouldShowHellip(5, $currentPage, $endPage));
        $this->assertFalse($this->helper->shouldShowPageNumber(5, $currentPage, $endPage));
        $this->assertTrue($this->helper->shouldShowPageNumber(6, $currentPage, $endPage));
        $this->assertTrue($this->helper->shouldShowPageNumber(7, $currentPage, $endPage));
    }

    function testShouldDisplayBestAnswerRemoval() {
        $this->helper->question = $this->fixtureInstance->make('QuestionActiveUserSuspended');
        $user = $this->fixtureInstance->make('UserSuspended');
        $nonAuthorUser = $this->fixtureInstance->make('UserActive1');
        $comment = $this->CI->model('CommunityQuestion')->getComments($this->helper->question)->result[0];

        //Author is Inactive
        $this->logIn($user->Login);
        $this->assertFalse($this->helper->shouldDisplayBestAnswerActions($comment, $user, false, array("author")));
        $this->logOut();

        //User isn't Author or Moderator
        $this->logIn($nonAuthorUser->Login);
        $this->assertFalse($this->helper->shouldDisplayBestAnswerActions($comment, $nonAuthorUser, false, array("author", "moderator")));
        $this->logOut();
        $this->fixtureInstance->destroy();

        $this->helper->question = $this->fixtureInstance->make('QuestionLockedUserActive');
        $user = $this->fixtureInstance->make('UserActive1');
        $comment = $this->CI->model('CommunityQuestion')->getComments($this->helper->question)->result[0];

        //Question is Locked
        $this->logIn($user->Login);
        $this->assertFalse($this->helper->shouldDisplayBestAnswerActions($comment, $user, false, array("author", "moderator")));
        $this->logOut();
        $this->fixtureInstance->destroy();

        $this->helper->question = $this->fixtureInstance->make('QuestionActiveAuthorBestAnswer');
        $user = $this->fixtureInstance->make('UserActive1');

        //Active Author, Active Best Answer
        $this->logIn($user->Login);
        $this->assertSame('Author', $this->helper->shouldDisplayBestAnswerActions($this->CI->model('CommunityQuestion')->getBestAnswers($this->helper->question)->result[0]->CommunityComment, $user, false, array("author")));
        $this->logOut();
        $this->fixtureInstance->destroy();

        $this->helper->question = $this->fixtureInstance->make('QuestionActiveModeratorBestAnswer');
        $user = $this->fixtureInstance->make('UserModActive');

        $this->logIn($user->Login);
        $this->assertSame('Moderator', $this->helper->shouldDisplayBestAnswerActions($this->CI->model('CommunityQuestion')->getBestAnswers($this->helper->question)->result[0]->CommunityComment, $user, false, array("moderator")));
        $this->logOut();
        $this->fixtureInstance->destroy();

        //User is Author and Moderator
        $this->helper->question = $this->fixtureInstance->make('QuestionActiveModActiveSameBestAnswer');
        $user = $this->fixtureInstance->make('UserModActive');
        $this->logIn($user->Login);
        $comment = $this->CI->model('CommunityQuestion')->getComments($this->helper->question)->result[0];
        $this->CI->Model('CommunityQuestion')->markCommentAsBestAnswer($comment->ID, SSS_BEST_ANSWER_AUTHOR);

        $this->assertSame($this->helper->question->BestCommunityQuestionAnswers[0]->CommunityComment,
                          $this->helper->question->BestCommunityQuestionAnswers[1]->CommunityComment);

        //Author/Mod - Same Best Answer chosen
        $this->assertSame('Both', $this->helper->shouldDisplayBestAnswerActions($this->CI->model('CommunityQuestion')->getBestAnswers($this->helper->question)->result[0]->CommunityComment, $user, false, array("author", "moderator")));
        $this->logOut();
        $this->fixtureInstance->destroy();
    }

    function testPaginationLinkUrl() {
        $result = $this->helper->paginationLinkUrl(34);
        $this->assertBeginsWith($result, '/app/' . $this->CI->page);
        $this->assertEndsWith($result, "page/34");
    }
}
