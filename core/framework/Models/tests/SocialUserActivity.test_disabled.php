<?

use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Framework;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class SocialUserActivityTest extends CPTestCase {
    public $testingClass = '\RightNow\Models\SocialUserActivity';

    function __construct ($label = null) {
        parent::__construct($label);

        $this->model = new RightNow\Models\SocialUserActivity;
        $this->excludedStatusTypes = array(
            'question' => array(0, STATUS_TYPE_SSS_QUESTION_PENDING, STATUS_TYPE_SSS_QUESTION_DELETED, STATUS_TYPE_SSS_QUESTION_SUSPENDED),
            'comment'  => array(0, STATUS_TYPE_SSS_COMMENT_PENDING, STATUS_TYPE_SSS_COMMENT_DELETED, STATUS_TYPE_SSS_COMMENT_SUSPENDED),
        );

        foreach (array('useractive1','useractive2','modactive1', 'modactive2', 'useradmin') as $user) {
            $this->logIn($user);
            $this->$user = $this->CI->model('CommunityUser')->get()->result;
            $this->logOut();
        }

        $class = new \ReflectionClass('RightNow\Libraries\ConnectTabular');
        $this->connectTabularCache = $class->getProperty('cache');
        $this->connectTabularCache->setAccessible(true);
    }

    function setUp() {
        $this->connectTabularCache->setValue(array());
    }

    function testGetQuestionsReturnsEmptyArrayInErrorCase () {
        $response = $this->assertResponseObject($this->model->getQuestions((object) array()), 'is_array');
        $this->assertIsA($response->result, 'array');
    }

    function testGetQuestions () {
        $response = $this->assertResponseObject($this->model->getQuestions($this->useractive1), 'is_array');
        $this->assertIsA($response->result, 'array');
        $this->assertTrue(count($response->result) > 0);
        foreach ($response->result as $result) {
            $this->assertTrue(Framework::isValidID($result->ID));
        }
    }

    function testGetCommentsReturnsEmptyArrayInErrorCase () {
        $response = $this->assertResponseObject($this->model->getComments((object) array()), 'is_array');
        $this->assertSame(0, count($response->result));
        $this->assertIsA($response->result, 'array');
        $this->assertSame(0, count($response->result));
    }

    function testGetComments () {
        $response = $this->assertResponseObject($this->model->getComments($this->useractive1), 'is_array');
        $this->assertIsA($response->result, 'array');
        $this->assertTrue(count($response->result) > 0);
        $ids = array();
        foreach ($response->result as $result) {
            $this->assertTrue(Framework::isValidID($result->ID));
            $this->assertTrue(Framework::isValidID($result->CommunityQuestion->ID));
            $ids []= $result->ID;
        }
        $this->assertIdentical($ids, array_unique($ids));
    }

    function testQueryByType () {
        $method = $this->getMethod('queryByType');
        $user = 'useractive1';

        $type = 'someInvalidType';
        $this->assertIdentical(array(), $method($type, "CreatedByCommunityUser = {$this->$user->ID}"));

        $type = 'question';
        $response = $method($type, "CreatedByCommunityUser = {$this->$user->ID}");
        $this->assertTrue(count($response) > 0);
        foreach($response as $question) {
            $this->assertEqual($user, $question->CreatedByCommunityUser->DisplayName);
            $statusType = (int) $question->StatusWithType->StatusType->ID;
            $this->assertFalse(in_array($statusType, $this->excludedStatusTypes[$type]));
        }

        $type = 'comment';
        $response = $method($type, "CreatedByCommunityUser = {$this->$user->ID}");
        $this->assertTrue(count($response) > 0);
        foreach($response as $comment) {
            $this->assertEqual($user, $comment->CreatedByCommunityUser->DisplayName);
            $statusType = (int) $comment->StatusWithType->StatusType->ID;
            $this->assertFalse(in_array($statusType, $this->excludedStatusTypes[$type]));
        }

        // Only active comments associated with an active question are returned.
        list($fixtureInstance, $activeQuestion, $suspendedQuestion) = $this->getFixtures(array(
            'QuestionActiveModActive', 'QuestionSuspendedModActive'
        ));

        $response = $method('comment', "CreatedByCommunityUser = {$suspendedQuestion->CreatedByCommunityUser->ID}");
        $activeQuestionID = $activeQuestion->ID;
        $suspendedQuestionID = $suspendedQuestion->ID;
        $commentsFromActiveQuestionInResults = $commentsFromSuspendedQuestionInResults = false;
        $this->assertTrue(count($response) > 0);
        foreach($response as $comment) {
            $questionID = intval($comment->CommunityQuestion->ID);
            if ($questionID === $activeQuestionID) {
                $commentsFromActiveQuestionInResults = true;
            }
            if ($questionID === $suspendedQuestionID) {
                $commentsFromSuspendedQuestionInResults = true;
            }
        }

        $this->assertTrue($commentsFromActiveQuestionInResults, 'Comments from active question not in results');
        $this->assertFalse($commentsFromSuspendedQuestionInResults, 'Comments from suspended question in results');

        $fixtureInstance->destroy();
    }

    function testGetBestAnswersGivenByAuthor () {
        $this->logIn('useractive1');
        $user = $this->CI->model('CommunityUser')->get()->result;
        $this->logOut();

        $response = $this->assertResponseObject($this->model->getBestAnswersVotedByUser($user), 'is_array');

        $this->assertIsA($response->result, 'array');
        $this->assertTrue(count($response->result) > 0);
        foreach ($response->result as $result) {
            $this->assertTrue(Framework::isValidID($result->ID));
            $this->assertTrue(Framework::isValidID($result->CommunityQuestion->ID));
            $this->assertEqual($user->ID, $result->CommunityQuestion->BestCommunityQuestionAnswers->CommunityUser, "Best Answer was created by user '{$result->CommunityQuestion->BestCommunityQuestionAnswers->CommunityUser}', not '{$user->ID}' as expected");
            // since useractive1 should only be able to mark things as author best answers, go ahead and verify useractive1 is the question author
            $this->assertEqual($user->ID, $result->CommunityQuestion->CreatedByCommunityUser->ID, "Question was created by user '{$result->CommunityQuestion->CreatedByCommunityUser}', not '{$user->ID}' as expected");
        }
    }

    function testGetBestAnswersGivenByModerator () {
        $this->logIn('modactive1');
        $user = $this->CI->model('CommunityUser')->get()->result;
        $this->logOut();

        $response = $this->assertResponseObject($this->model->getBestAnswersVotedByUser($user), 'is_array');

        $this->assertIsA($response->result, 'array');
        $this->assertTrue(count($response->result) > 0);
        foreach ($response->result as $result) {
            $this->assertTrue(Framework::isValidID($result->ID));
            $this->assertTrue(Framework::isValidID($result->CommunityQuestion->ID));
            $this->assertEqual($user->ID, $result->CommunityQuestion->BestCommunityQuestionAnswers->CommunityUser, "Best Answer was created by user '{$result->CommunityQuestion->BestCommunityQuestionAnswers->CommunityUser}', not '{$user->ID}' as expected");
        }
    }

    /**
     * Returns an array of best answers for $user from object query.
     */
    function getExpectedBestAnswersAuthoredByUser($user) {
        $roql = sprintf(
            "SELECT CommunityComment FROM CommunityComment WHERE CreatedByCommunityUser = %d AND StatusWithType.StatusType NOT IN (%s) ORDER BY ID",
            $user->ID, implode(',', $this->excludedStatusTypes['comment'])
        );
        $comments = Connect\ROQL::queryObject($roql)->next();
        $bestAnswers = array();
        while ($comment = $comments->next()) {
            if (!in_array($comment->CommunityQuestion->StatusWithType->StatusType->ID, $this->excludedStatusTypes['question'])) {
                foreach($comment->CommunityQuestion->BestCommunityQuestionAnswers ?: array() as $bestAnswer) {
                    if ($comment->ID === $bestAnswer->CommunityComment->ID
                        && $user->ID === $bestAnswer->CommunityComment->CreatedByCommunityUser->ID
                        && !in_array($bestAnswer->CommunityComment->StatusWithType->StatusType->ID, $this->excludedStatusTypes['comment'])) {
                            $bestAnswers[] = $bestAnswer;
                    }
                }
            }
        }

        return $bestAnswers;
    }

    /**
     * Returns an array of best answers for $user from `getBestAnswersAuthoredByUser` method.
     */
    function getActualBestAnswersAuthoredByUser($user) {
        list($class, $maxActivityResults, $getBestAnswersAuthoredByUser) = $this->reflect('maxActivityResults', 'method:getBestAnswersAuthoredByUser');
        $instance = $class->newInstance();
        $maxActivityResults->setValue($instance, 2000);
        return $getBestAnswersAuthoredByUser->invoke($instance, $user)->result;
    }

    function getExpectedAndActualBestAnswersViaMakeRequest() {
        $userID = (int) \RightNow\Utils\Text::getSubstringAfter(get_instance()->uri->uri_string(), __FUNCTION__ . '/');
        $user = get_instance()->Model('CommunityUser')->get($userID)->result;

        $expected = $this->getExpectedBestAnswersAuthoredByUser($user);
        $actual = $this->getActualBestAnswersAuthoredByUser($user);

        $expectedCommentIDs = $actualCommentIDs = array();

        foreach($expected as $bestAnswer) {
            $commentID = $bestAnswer->CommunityComment->ID;
            // consolidate 'Moderator Selected' and 'Author Selected' BestAnswerTypes
            if (!in_array($commentID, $expectedCommentIDs)) {
                $expectedCommentIDs[] = $commentID;
            }
        }

        foreach($actual as $bestAnswer) {
            $actualCommentIDs[] = (int) $bestAnswer->ID;
        }

        echo json_encode(array($expectedCommentIDs, $actualCommentIDs));
    }

    function testBestAnswersAuthoredByUserForSelectUsers () {
        // Loop through all users and do a comparison for users found to have a yet un-tested best answer count.
        $testedBestAnswerCounts = array();
        $results = Connect\ROQL::queryObject('SELECT CommunityUser FROM CommunityUser WHERE ID in (101,102)')->next();
        while ($user = $results->next()) {
            $bestAnswerCount = count($this->getExpectedBestAnswersAuthoredByUser($user));
            if (in_array($bestAnswerCount, $testedBestAnswerCounts)) {
                continue;
            }
            $testedBestAnswerCounts[] = $bestAnswerCount;

            // Use makeRequest to better the chances of hitting the database in the same state
            list($expected, $actual) = json_decode($this->makeRequest(
                '/ci/unitTest/wgetRecipient/invokeTestMethod/' .
                urlencode(__FILE__) . '/' . __CLASS__ .
                "/getExpectedAndActualBestAnswersViaMakeRequest/{$user->ID}"
            ));

           foreach(array(array('expected', 'actual'), array('actual', 'expected')) as $targets) {
                list($source, $target) = $targets;
                foreach($$source as $commentID) {
                    if (!in_array($commentID, $$target)) {
                        $this->fail("CommunityUser '{$user->DisplayName}' [{$user->ID}] Best answer having comment ID '$commentID' in $source results but not $target.");
                    }
                }
            }
            sleep(.5); // Try not to hammer makeRequest too hard
        }

        $this->assertTrue(count($testedBestAnswerCounts) > 1, 'Users having best answers not found.');
    }

    function testAggregateBestAnswers() {
        $method = $this->getMethod('aggregateBestAnswers');

        $questions = array(
            (object) array(
                'ID' => 1,
                'BestCommunityQuestionAnswers' => (object) array(
                    'CommunityComment' => 10,
                    'BestAnswerType' => 2,
                    'CreatedTime' => '2014-01-04T12:56:12Z',
                    'CommunityUser' => 123,
                ),
            ),
            (object) array(
                'ID' => 1,
                'BestCommunityQuestionAnswers' => array(
                    (object) array(
                        'CommunityComment' => 10,
                        'BestAnswerType' => 2,
                        'CreatedTime' => '2014-01-04T12:56:12Z',
                        'CommunityUser' => 123,
                    ),
                    (object) array(
                        'CommunityComment' => 11,
                        'BestAnswerType' => 2,
                        'CreatedTime' => '2014-01-04T12:57:12Z',
                        'CommunityUser' => 123,
                    ),
                    (object) array(
                        'CommunityComment' => 12,
                        'BestAnswerType' => 2,
                        'CreatedTime' => '2014-01-04T12:58:12Z',
                        'CommunityUser' => 123,
                    ),
                ),
            ),
        );

        $expected = array(
            1 => array(
                (object) array(
                    'CommunityComment' => 10,
                    'BestAnswerType' => 2,
                    'CreatedTime' => '2014-01-04T12:56:12Z',
                    'CommunityUser' => 123,
                ),
                (object) array(
                    'CommunityComment' => 11,
                    'BestAnswerType' => 2,
                    'CreatedTime' => '2014-01-04T12:57:12Z',
                    'CommunityUser' => 123,
                ),
                (object) array(
                    'CommunityComment' => 12,
                    'BestAnswerType' => 2,
                    'CreatedTime' => '2014-01-04T12:58:12Z',
                    'CommunityUser' => 123,
                ),
            ),
        );

        $actual = $method($questions);
        $this->assertIdentical($expected, $actual);
    }

    function testGetBestAnswersAuthoredByUser () {
        $user = $this->modactive2;
        // Create a question.
        $this->logIn();
        $question = $this->createQuestion();
        $this->logOut();
        // Someone posts a comment.
        $this->logIn($user->Contact->Login);
        $comment = $this->createCommentForQuestion($question);
        $this->logOut();
        // Author marks it as best answer.
        $this->logIn();
        $this->markBestAnswer($comment);
        $this->logOut();

        $response = $this->assertResponseObject($this->model->getBestAnswersAuthoredByUser($user), 'is_array');

        $this->assertIsA($response->result, 'array');
        $this->assertTrue(count($response->result) > 0);
        $resultsToProcess = array();
        foreach ($response->result as $result) {
            if ($question->ID === (int)$result->CommunityQuestion->ID || $comment->ID === (int)$result->CommunityQuestion->BestCommunityQuestionAnswers[0]->CommunityComment) {
                $resultsToProcess[] = $result;
            }
        }
        $this->assertIdentical(1, count($resultsToProcess));
        $bestAnswers = $resultsToProcess[0]->CommunityQuestion->BestCommunityQuestionAnswers;
        $this->assertIsA($bestAnswers, 'array');
        $this->assertNotEqual($this->modactive1->ID, $bestAnswers[0]->CommunityUser);

        $this->destroyObject($comment);
        $this->removeQuestion($question);
    }

    function testGetQuestionRatingGivenToUser() {
        $user = $this->useradmin;
        $this->logIn($user->Contact->Login);
        $question = $this->createQuestion();
        $this->logOut();
        $rating = $this->rateAQuestionAsUser($question->ID, 'useractive2');

        $response = $this->assertResponseObject($this->model->getQuestionRatingsGivenToUser($user), 'is_array');

        $this->assertIsA($response->result, 'array');
        $this->assertTrue(count($response->result) > 0);
        $resultsToProcess = array();
        foreach ($response->result as $result) {
            if ($question->ID === (int)$result->UserRating->CommunityQuestion) {
                $resultsToProcess[] = $result;
            }
        }
        $this->assertIdentical(1, count($resultsToProcess));
        $this->assertIsA($result->ID, 'string');
        $this->assertSame('100', $resultsToProcess[0]->UserRating->RatingValue);
        $this->assertSame($resultsToProcess[0]->ID, $resultsToProcess[0]->UserRating->CommunityQuestion);
        $this->assertTrue(Framework::isValidID($resultsToProcess[0]->UserRating->ID));
        $this->assertTrue(Framework::isValidID($resultsToProcess[0]->ID));

        $this->removeQuestionRating($rating);
        $this->destroyObject($question);
    }

    function testGetQuestionRatingsGivenByUser () {
        $this->login();
        $question = $this->createQuestion();
        $this->logOut();

        $rating = $this->rateAQuestionAsUser($question->ID, 'useractive2');

        $response = $this->assertResponseObject($this->model->getQuestionRatingsGivenByUser($this->useractive2), 'is_array');

        $this->assertIsA($response->result, 'array');
        $this->assertTrue(count($response->result) > 0);
        $resultsToProcess = array();
        foreach ($response->result as $result) {
            if ($question->ID === (int)$result->UserRating->CommunityQuestion) {
                $resultsToProcess[] = $result;
            }
        }
        $this->assertIdentical(1, count($resultsToProcess));
        $this->assertIsA($result->ID, 'string');
        $this->assertSame('100', $resultsToProcess[0]->UserRating->RatingValue);
        $this->assertSame($resultsToProcess[0]->ID, $resultsToProcess[0]->UserRating->CommunityQuestion);
        $this->assertTrue(Framework::isValidID($resultsToProcess[0]->UserRating->ID));
        $this->assertTrue(Framework::isValidID($resultsToProcess[0]->ID));

        $this->removeQuestionRating($rating);
        $this->destroyObject($question);
    }

    function testGetCommentRatingsGivenByUser () {
        $this->login();
        $question = $this->createQuestion();
        $comment = $this->createCommentForQuestion($question);
        $this->logOut();

        $rating = $this->rateACommentAsUser($comment->ID, 'useractive1');

        $response = $this->assertResponseObject($this->model->getCommentRatingsGivenByUser($this->useractive1), 'is_array');

        $this->assertIsA($response->result, 'array');
        $this->assertTrue(count($response->result) > 0);
        $resultsToProcess = array();
        foreach ($response->result as $result) {
            if ($comment->ID === (int)$result->UserRating->CommunityComment) {
                $resultsToProcess[] = $result;
            }
        }
        $this->assertIdentical(1, count($resultsToProcess));
        $this->assertIsA($result->ID, 'string');
        $this->assertSame('100', $resultsToProcess[0]->UserRating->RatingValue);
        $this->assertSame($resultsToProcess[0]->ID, $resultsToProcess[0]->UserRating->CommunityComment);
        $this->assertTrue(Framework::isValidID($resultsToProcess[0]->UserRating->ID));
        $this->assertTrue(Framework::isValidID($resultsToProcess[0]->ID));

        $this->removeCommentRating($rating);
        $this->destroyObject($comment);
        $this->destroyObject($question);
    }

    function testGetCommentRatingsGivenToUser () {
        $this->login('useractive2');
        $question = $this->createQuestion();
        $comment = $this->createCommentForQuestion($question);
        $this->logOut();

        $rating = $this->rateACommentAsUser($comment->ID, 'useractive1');

        $response = $this->assertResponseObject($this->model->getCommentRatingsGivenToUser($this->useractive2), 'is_array');

        $this->assertIsA($response->result, 'array');
        $this->assertTrue(count($response->result) > 0);
        $resultsToProcess = array();
        foreach ($response->result as $result) {
            if ($comment->ID === (int)$result->UserRating->CommunityComment) {
                $resultsToProcess[] = $result;
            }
        }
        $this->assertIdentical(1, count($resultsToProcess));
        $this->assertIsA($result->ID, 'string');
        $this->assertSame('100', $resultsToProcess[0]->UserRating->RatingValue);
        $this->assertSame($resultsToProcess[0]->ID, $resultsToProcess[0]->UserRating->CommunityComment);
        $this->assertTrue(Framework::isValidID($resultsToProcess[0]->UserRating->ID));
        $this->assertTrue(Framework::isValidID($resultsToProcess[0]->ID));

        $this->removeCommentRating($rating);
        $this->destroyObject($comment);
        $this->destroyObject($question);
    }

    function testGetCommentRatingsGivenToUserReturnsEmptyArrayWithInvalidUserID () {
        $response = $this->assertResponseObject($this->model->getCommentRatingsGivenToUser($this->CI->model('CommunityUser')->get(9999999)->result), 'is_array');
        $this->assertIsA($response->result, 'array');
        $this->assertSame(0, count($response->result));
    }

    function testGetCommentRatingsGivenByUserReturnsEmptyArrayWithInvalidUserID () {
        $response = $this->assertResponseObject($this->model->getCommentRatingsGivenByUser($this->CI->model('CommunityUser')->get(9999999)->result), 'is_array');
        $this->assertIsA($response->result, 'array');
        $this->assertSame(0, count($response->result));
    }

    function testGetQuestionRatingsGivenToUserReturnsEmptyArrayWithInvalidUserID () {
        $response = $this->assertResponseObject($this->model->getQuestionRatingsGivenToUser($this->CI->model('CommunityUser')->get(9999999)->result), 'is_array');
        $this->assertIsA($response->result, 'array');
        $this->assertSame(0, count($response->result));
    }

    function testGetQuestionRatingsGivenBtUserReturnsEmptyArrayWithInvalidUserID () {
        $response = $this->assertResponseObject($this->model->getQuestionRatingsGivenByUser($this->CI->model('CommunityUser')->get(9999999)->result), 'is_array');
        $this->assertIsA($response->result, 'array');
        $this->assertSame(0, count($response->result));
    }

    function rateACommentAsUser ($commentID, $username) {
        $this->logIn($username);
        $result = $this->CI->model('CommunityComment')->rateComment($commentID, 100)->result;
        $this->logOut();
        return $result;
    }

    function rateAQuestionAsUser ($question, $username) {
        $this->logIn($username);
        $result = $this->CI->model('CommunityQuestion')->rateQuestion($question, 100)->result;
        $this->logOut();
        return $result;
    }

    function removeCommentRating ($rating) {
        $this->destroyObject($rating);
        $this->logOut();
    }

    function removeQuestionRating ($rating) {
        $this->destroyObject($rating);
        $this->logOut();
    }

    function markBestAnswer ($comment) {
        return $this->CI->model('CommunityQuestion')->markCommentAsBestAnswer($comment->ID)->result;
    }

    function createQuestion () {
        return $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__),
        ))->result;
    }

    function createCommentForQuestion ($question) {
        return $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.Body'           => (object) array('value' => 'vagabonds'),
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
        ))->result;
    }

    function removeQuestion ($question) {
        $this->destroyObject($question);
        Connect\ConnectAPI::commit();
        $this->logOut();
    }
}
