<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\Connect\v1_4 as Connect,
    RightNow\UnitTest\Fixture,
    RightNow\UnitTest\Helper,
    RightNow\Utils\Url;

class TestBestAnswerDisplayWidget extends WidgetTestCase {
    public $testingWidget = "standard/discussion/BestAnswerDisplay";

    function __construct() {
        parent::__construct();
        $this->fixtureInstance = new Fixture();
    }

    function tearDown() {
        $this->fixtureInstance->destroy();
        if ($this->question) {
            $this->destroyObject($this->question);
            $this->question = null;
        }
    }

    function testGetBestAnswers() {
        $this->logIn();

        $instance = $this->createWidgetInstance();
        $question = $this->fixtureInstance->make('QuestionActiveModeratorBestAnswer');
        $getBestAnswers = $this->getWidgetMethod('getBestAnswers');

        $this->assertFalse($getBestAnswers(null));

        $bestAnswers = $this->CI->model('CommunityQuestion')->getBestAnswers($question)->result;
        $results = $getBestAnswers($bestAnswers);
        $expected = array('type' => SSS_BEST_ANSWER_MODERATOR,
                          'id'   => $bestAnswers[0]->CommunityUser->ID);

        $this->assertIdentical($expected, $results[$bestAnswers[0]->CommunityComment->ID]['selectedBy'][0]);

        $this->logOut();
        $this->fixtureInstance->destroy();
    }

    function testShouldDisplayComment() {
        $instance = $this->createWidgetInstance();
        $shouldDisplayComment = $this->getWidgetMethod('shouldDisplayComment', $instance);

        $this->assertTrue($shouldDisplayComment(SSS_BEST_ANSWER_MODERATOR));
        $this->assertTrue($shouldDisplayComment(SSS_BEST_ANSWER_AUTHOR));
        $this->assertFalse($shouldDisplayComment(SSS_BEST_ANSWER_COMMUNITY)); // For now..
        $this->assertFalse($shouldDisplayComment(0));

        $instance = $this->createWidgetInstance(array('best_answer_types' => array('moderator')));
        $shouldDisplayComment = $this->getWidgetMethod('shouldDisplayComment', $instance);
        $this->assertTrue($shouldDisplayComment(SSS_BEST_ANSWER_MODERATOR));
        $this->assertFalse($shouldDisplayComment(SSS_BEST_ANSWER_AUTHOR));
        $this->assertFalse($shouldDisplayComment(SSS_BEST_ANSWER_COMMUNITY));

        $instance = $this->createWidgetInstance(array('best_answer_types' => array('author')));
        $shouldDisplayComment = $this->getWidgetMethod('shouldDisplayComment', $instance);
        $this->assertFalse($shouldDisplayComment(SSS_BEST_ANSWER_MODERATOR));
        $this->assertTrue($shouldDisplayComment(SSS_BEST_ANSWER_AUTHOR));
        $this->assertFalse($shouldDisplayComment(SSS_BEST_ANSWER_COMMUNITY));
    }

    function testGetBestAnswersInactive() {
        $this->logIn('useradmin');
        $instance = $this->createWidgetInstance();
        $getBestAnswers = $this->getWidgetMethod('getBestAnswers');
        $question = $this->fixtureInstance->make('QuestionActiveBestAnswerSuspended');
        $bestAnswers = $this->CI->model('CommunityQuestion')->getBestAnswers($question)->result;

        $results = $getBestAnswers($bestAnswers);
        $expected = array('type' => SSS_BEST_ANSWER_MODERATOR,
                          'id'   => $bestAnswers[0]->CommunityUser->ID);

        $this->assertTrue(count($results) === 1);
        $this->assertIdentical($expected, $results[$bestAnswers[0]->CommunityComment->ID]['selectedBy'][0]);

        $this->logOut();
        $this->fixtureInstance->destroy();
    }

    function testGetBestAnswersSuspendedModActive() {
        $this->logIn('modactive1');
        $instance = $this->createWidgetInstance();
        $getBestAnswers = $this->getWidgetMethod('getBestAnswers');
        $question = $this->fixtureInstance->make('QuestionSuspendedBestAnswer');

        $bestAnswers = $this->CI->model('CommunityQuestion')->getBestAnswers($question)->result;
        $this->assertFalse($getBestAnswers($bestAnswers));
        $this->logOut();
        $this->fixtureInstance->destroy();
    }

    function testRefresh() {
        $results = $this->makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . '/refreshPage', array('includeHeaders' => true));

        // verify rendered content (i.e. check for known CSS classes) is returned
        $this->assertStringContains($results, 'rn_BestAnswerList', $results);
        $this->assertStringContains($results, 'rn_BestAnswerInfo');
        $this->assertStringContains($results, 'rn_BestAnswerContent');
        $this->assertStringContains($results, 'rn_BestAnswerBody');

        $this->logout();
    }

    function refreshPage() {
        $this->logIn();
        $question = $this->fixtureInstance->make('QuestionActiveModeratorBestAnswer');

        $this->createWidgetInstance();
        $refresh = $this->getWidgetMethod('refresh');

        ob_start();
        $refresh(array('questionID' => $question->ID));
        $response = ob_get_contents();
        ob_end_clean();

        echo $response;
        $this->logOut();
        $this->fixtureInstance->destroy();
    }

    function testGetCommentWithSuspendedQuestion() {
        $instance = $this->createWidgetInstance();
        $getComment = $this->getWidgetMethod('getComment', $instance);
        $this->fixtureInstance->make('QuestionSuspendedModActive');

        //Suspended question should never return a best answer
        $this->login('modactive1');
        $comment = $this->fixtureInstance->make('CommentSuspendedModActive');
        $response = $getComment($comment->ID);
        $this->assertNull($getComment($comment->ID));

        $comment = $this->fixtureInstance->make('CommentActiveUserActive');
        $this->assertNull($getComment($comment->ID));
        $this->logout();

        $this->login('useractive1');
        $comment = $this->fixtureInstance->make('CommentSuspendedModActive');
        $this->assertNull($getComment($comment->ID));

        $comment = $this->fixtureInstance->make('CommentActiveUserActive');
        $this->assertNull($getComment($comment->ID));
        $this->logout();

        $this->logOut();
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
