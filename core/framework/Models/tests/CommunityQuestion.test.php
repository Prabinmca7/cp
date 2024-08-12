<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Utils\Framework,
    RightNow\Utils\Text,
    RightNow\UnitTest\Fixture as Fixture;

class CommunityQuestionTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\CommunityQuestion';
    private $contacts;

    /**
     * MUT (model under test): CommunityQuestion
     *
     * @var RightNow\Models\CommunityQuestion
     */
    private $model;

    function __construct() {
        parent::__construct();
        $this->model = new RightNow\Models\CommunityQuestion();
        get_instance()->model('CommunityQuestion');
        $this->fixtureInstance = new Fixture();
        $class = new \ReflectionClass('RightNow\Libraries\ConnectTabular');
        $this->connectTabularCache = $class->getProperty('cache');
        $this->connectTabularCache->setAccessible(true);

        $this->contacts = array(
            'regular' => Connect\Contact::first("Login = 'useractive1'"), // useractive1
            'moderator' => Connect\Contact::first("Login = 'modactive1'"), // modactive1
            'admin' => Connect\Contact::first("Login = 'slatest'"), // allmighty
        );
    }

    function setUp() {
        $this->connectTabularCache->setValue(array());
    }

    /*
     * Creates a Social Question with optional comments.
     * @param string $subject
     * @param array $comments An array containing the topLevel comment info as well as children
     *        E.g. array(
     *            'body' => 'A top-level comment..',
     *            'status' => 'active',
     *            'children' => array(
     *                array('body' => 'An active child comment', 'status' => 'active'),
     *                array('body' => 'An suspended child comment', 'status' => 'suspended'),
     *                ...
     *              ))
     */
    function createQuestion($subject, array $comments = array()) {
        static $statusLookup = array(
            'active'    => 'Active',    // 33,
            'suspended' => 'Suspended', // 34,
            'deleted'   => 'Deleted',   // 35,
            'pending'   => 'Pending',   // 36,
        );

        $question = $this->model->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => $subject),
            'CommunityQuestion.CustomFields.CO.cq_test_dt' => (object) array('value' => '2020-09-04 10:00:00'),
        ))->result;

        foreach($comments as $comment) {
            $parent = $this->CI->model('CommunityComment')->create(array(
                'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
                'CommunityComment.Body' => (object) array('value' => $comment['body']),
                'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => $statusLookup[$comment['status']]),
            ))->result;
            foreach($comment['children'] ?: array() as $child) {
                $this->CI->model('CommunityComment')->create(array(
                    'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
                    'CommunityComment.Body' => (object) array('value' => $child['body']),
                    'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => $statusLookup[$child['status']]),
                    'CommunityComment.Parent' => (object) array('value' => $parent->ID),
                ))->result;
            }
        }

        return $question;
    }

    function destroyQuestion($question) {
        if ($question) {
            $this->destroyObject($question);
            Connect\ConnectAPI::commit();
        }
    }

    function testGetBlank() {
        $response = $this->assertResponseObject($this->model->getBlank());

        // check the blank object
        $question = $response->result;
        $this->assertNotNull($question, "Null Question returned");
        $this->assertTrue($question instanceof Connect\CommunityQuestion, "Should have a Connect\CommunityQuestion object");

        $this->assertTrue(empty($question->ID), "Blank Question should have an empty ID (null or zero)");
        $this->assertNull($question->Title, "Blank Question should have a null Title");
        $this->assertNull($question->Body, "Blank Question should have a null Body");
    }

    function testGet(){
        $response = $this->model->get(0);
        $this->assertResponseObject($response, 'is_null', 1);

        $response = $this->model->get(null);
        $this->assertResponseObject($response, 'is_null', 1);

        $response = $this->model->get(38908);
        $this->assertResponseObject($response, 'is_null', 1);

        $response = $this->model->get(1);
        $this->assertResponseObject($response);
        $this->assertConnectObject($response->result, 'CommunityQuestion');
        $this->assertIdentical(1, $response->result->ID);
        $this->assertIsA($response->result->SocialPermissions, 'RightNow\Decorators\SocialQuestionPermissions');
        $this->assertTrue($response->result->SocialPermissions->canRead());
    }

    function testCreateWithoutProductOrCategory(){
        $this->logIn();

        $response = $this->assertResponseObject($this->model->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__),
        )));

        $this->destroyObject($response->result);
    }

    function testCreate(){
        $response = $this->model->create(array());
        $this->assertResponseObject($response, 'is_null', 1);

        $response = $this->model->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__),
        ));
        $this->assertResponseObject($response, 'is_null', 1);

        $this->logIn();

        $response = $this->assertResponseObject($this->model->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__),
            'CommunityQuestion.Product' => (object) array('value' => 1),
        )));
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\CommunityQuestion');
        $this->assertIsA($response->result->CreatedByCommunityUser, CONNECT_NAMESPACE_PREFIX . '\CommunityUser');

        // ensure the Author is the same by comparing it's Contact to the logged-in user
        $this->assertNotNull($response->result->CreatedByCommunityUser);
        if ($response->result->CreatedByCommunityUser) {
            $this->assertEqual($this->CI->model('Contact')->get()->result->ID,
                $response->result->CreatedByCommunityUser->Contact->ID);
        }
        $this->assertIdentical('Active', $response->result->StatusWithType->Status->LookupName);
        $this->assertIdentical('bananas ' . __FUNCTION__, $response->result->Subject);
        $this->assertEqual('text/html', $response->result->BodyContentType->LookupName, "Question should have a default bodycontenttype of text/html");

        $subject = str_pad('bananas', 260, 'bananas');
        $this->destroyObject($response->result);

        $response = $this->model->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => "{$subject}IsNowTooLong")
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(Text::stringContains($response->error, 'exceeds its size limit'));

        $response = $this->model->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'MOAR bananas'),
            'CommunityQuestion.Body' => (object) array('value' => 'main content'),
            'CommunityQuestion.Product' => (object) array('value' => 6),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors), var_export($response->errors, true));
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\CommunityQuestion');
        // ensure the Author is the same by comparing it's Contact to the logged-in user
        $this->assertNotNull($response->result->CreatedByCommunityUser);
        if ($response->result->CreatedByCommunityUser) {
            $this->assertEqual($this->CI->model('Contact')->get()->result->ID,
                $response->result->CreatedByCommunityUser->Contact->ID);
        }
        $this->assertIdentical('MOAR bananas', $response->result->Subject);
        $this->assertIdentical('main content', $response->result->Body);
        $this->assertIdentical('text/html', $response->result->BodyContentType->LookupName);

        $this->destroyObject($response->result);
    }

    function testNewQuestionSubscription() {
        $this->logIn();
        $response = $this->model->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'Subscription Test'),
            'CommunityQuestion.Body' => (object) array('value' => 'Subscribing to new question'),
            'CommunityUser.Subscribe' => (object) array('value' => '1'),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\CommunityQuestion');
        $this->assertNotNull($this->CI->model('SocialSubscription')->getSubscriptionID($response->result->ID, $this->CI->session->getProfileData('socialUserID'), 'Question'));

        $this->destroyObject($response->result);
    }

    function testUpdate() {
        // create a new question
        $this->logIn('useractive1');
        $question1 = $this->model->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        $question2 = $this->model->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;

        // can't update a question without providing an ID
        $response = $this->model->update('asdf', array());
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        // can't update a question with bad data (string is too long)
        $subject = str_pad('bananas', 260, 'bananas');
        $response = $this->model->update($question1->ID, array(
            'CommunityQuestion.Subject' => (object) array('value' => $subject),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        // we should be able to update if we do everything right (2 = markdown content type)
        list($newSubject, $newBody, $newContentType, $co_cq_dt) = array("axe", "extra fox", 2, '2020-09-07 10:00:00');
        $response = $this->assertResponseObject($this->model->update($question1->ID, array(
            'CommunityQuestion.Subject' => (object) array('value' => $newSubject),
            'CommunityQuestion.Body' => (object) array('value' => $newBody),
            'CommunityQuestion.BodyContentType' => (object) array('value' => $newContentType),
        )));
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\CommunityQuestion');
        $this->assertIdentical($newSubject, $response->result->Subject);
        $this->assertIdentical($newBody, $response->result->Body);
        $this->assertIdentical('text/x-markdown', $response->result->BodyContentType->LookupName);

        $this->logOut();

        // now show that other users (e.g. not the author and not a moderator) cannot update the question
        // use a second question since the response to the first one is cached
        $this->logIn('useractive2');

        $response = $this->model->update($question2->ID, array(
            'CommunityQuestion.Subject' => (object) array('value' => 'edit by another user'),
            'CommunityQuestion.Body' => (object) array('value' => 'separate user'),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings), print_r($response->warnings, true));
        $this->assertNull($response->result, "Expected a null response since the editor wasn't the author");

        // clean up - since we logged in as a different user we must commit the deletes
        $this->destroyObject($question1);
        $this->destroyObject($question2);
        Connect\ConnectAPI::commit();
    }

    function xtestUpdateRegularUserCannotUpdateStatus() {
        $this->logIn($this->contacts['regular']->Login);
        // create a new question as a regular user
        $question1 = $this->assertResponseObject($this->model->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        )))->result;
        $question2 = $this->assertResponseObject($this->model->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        )))->result;

        $namedID = new Connect\NamedID();
        $namedID->ID = 26; // CommunityQuestion.UPDATE.Status
        $this->assertFalse($question1->hasPermission($namedID)); // this line should return false but returns not enough information

        // can't update the status - even of your own question - as a regular user
        $response = $this->model->update($question1->ID, array(
            'CommunityQuestion.StatusWithType.Status.ID' => (object) array('value' => 28), // 28 = pending
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        // but you can update it as a moderator
        // use a different question since the permissions are already cached for the first one
        $this->logOut();
        $this->logIn($this->contacts['moderator']->Login);
        $response = $this->assertResponseObject($this->model->update($question2->ID, array(
            'CommunityQuestion.StatusWithType.Status.ID' => (object) array('value' => 28), // 28 = pending
        )));

        // clean up - since we logged in as a different user we must commit the deletes
        $this->destroyObject($question1);
        $this->destroyObject($question2);
        Connect\ConnectAPI::commit();
    }

    function testRegularUserCanDeleteOwnQuestion() {
        $this->logIn($this->contacts['regular']->Login);
        $question = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;

        // we should be able to update/delete the question
        $question = $this->assertResponseObject($this->model->update($question->ID, array(
            'CommunityQuestion.StatusWithType.Status.ID' => (object) array('value' => 31), // 31 is Deleted
        )))->result;
        $this->assertEqual(STATUS_TYPE_SSS_QUESTION_DELETED, $question->StatusWithType->StatusType->ID);

        // clean up
        $this->destroyObject($question);
    }

    function testGetSocialObjectStatuses() {
        // get a list of all the statuses and status types
        $statuses = array();
        $statusTypes = array();
        $results = Connect\ROQL::query("SELECT qs.ID, qs.LookupName, qs.StatusType FROM CommunityQuestionSts qs")->next();
        while($results && $result = $results->next()) {
            $statuses[$result['ID']] = array(
                'StatusLookupName' => $result['LookupName'],
                'StatusTypeID' => $result['StatusType']
            );
            // avoid duplicates by using the hash index
            $statusTypes[$result['StatusType']] = $result['StatusType'];
        }

        // try the get-all-statuses case
        $results = $this->assertResponseObject($this->model->getSocialObjectStatuses())->result;
        $this->assertTrue(count($results) > 0, "Expected at least one status");
        foreach ($results as $status) {
            $this->assertEqual($statuses[$status->ID]['StatusLookupName'], $status->LookupName);
            $this->assertEqual($statuses[$status->ID]['StatusTypeID'], $status->StatusType->ID);
        }

        // now try looking up each status by status type
        foreach ($statusTypes as $statusType) {
            $results = $this->assertResponseObject($this->model->getSocialObjectStatuses($statusType))->result;
            foreach ($results as $status) {
                $this->assertEqual($statuses[$status->ID]['StatusLookupName'], $status->LookupName);
                $this->assertEqual($statusType, $statuses[$status->ID]['StatusTypeID']);
            }
        }
    }

    function testGetStatusesFromStatusType(){
        $statuses = $this->model->getStatusesFromStatusType(STATUS_TYPE_SSS_QUESTION_DELETED)->result;
        $this->assertTrue(in_array(31, $statuses),"Should return deleted status id ");
        $statuses = $this->model->getStatusesFromStatusType(STATUS_TYPE_SSS_QUESTION_SUSPENDED)->result;
        $this->assertTrue(in_array(30, $statuses),"Should return suspended status id ");
        $statuses = $this->model->getStatusesFromStatusType(STATUS_TYPE_SSS_QUESTION_ACTIVE)->result;
        $this->assertTrue(in_array(29, $statuses),"Should return active status id");
    }

    function testSetStatusWithTypeForAllTypes() {
        // retrieve all the possible statuses and types
        $statuses = ConnectUtil::getNamedValues('CommunityQuestion', 'StatusWithType.Status');
        $this->assertTrue(count($statuses) > 0, "Need at least one status");
        $rawStatusTypes = ConnectUtil::getNamedValues('CommunityQuestion', 'StatusWithType.StatusType');

        // organize the types by lookupname
        $statusTypesByName = array();
        foreach ($rawStatusTypes as $rawStatusType) {
            $statusTypesByName[$rawStatusType->LookupName] = $rawStatusType->ID;
        }

        $this->logIn();

        // try setting the status by ID
        foreach ($statuses as $status) {
            $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
                'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__),
                'CommunityQuestion.Product' => (object) array('value' => 1),
                'CommunityQuestion.StatusWithType.Status.ID' => (object) array('value' => $status->ID)
            )))->result;
            $this->assertIdentical($status->ID, $question->StatusWithType->Status->ID);

            // the corresponding status type should be set automatically
            $this->assertIdentical($statusTypesByName[$status->LookupName], $question->StatusWithType->StatusType->ID);

            $this->destroyObject($question);
        }

        // try setting the status by LookupName
        foreach ($statuses as $status) {
            $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
                'CommunityQuestion.Subject' => (object) array('value' => 'bananas'),
                'CommunityQuestion.Product' => (object) array('value' => 1),
                'CommunityQuestion.StatusWithType.Status.LookupName' => (object) array('value' => $status->LookupName)
            )))->result;
            $this->assertIdentical($status->LookupName, $question->StatusWithType->Status->LookupName);

            // the corresponding status type should be set automatically
            $this->assertIdentical($statusTypesByName[$status->LookupName], $question->StatusWithType->StatusType->ID);

            $this->destroyObject($question);
        }
    }

    function testGetRecentlyAskedQuestions() {
        // create a question with a comment
        $this->logIn();
        $question1 = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'Distance to the sun ' . __FUNCTION__),
            'CommunityQuestion.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityQuestion.Body' => (object) array('value' => 'How far is it from the Earth to the sun?'),
            'CommunityQuestion.Product' => (object) array('value' => 1),
        )))->result;

        // now our question should be first in the list
        $response = $this->assertResponseObject($this->model->getRecentlyAskedQuestions(array('answerType' => array())));
        $this->assertTrue(count($response->result) > 0, "Could not find any recently answered questions");
        $this->assertEqual($question1->ID, $response->result[0]->ID, "wrong question at the front of the list");

        // create a second question
        $question2 = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'Distance to the moon'),
            'CommunityQuestion.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityQuestion.Body' => (object) array('value' => 'How far is it from the Earth to the moon?'),
            'CommunityQuestion.Product' => (object) array('value' => 1),
        )))->result;

        // the newly answered question should be first and the previous question second in the list
        $response = $this->assertResponseObject($this->model->getRecentlyAskedQuestions(array('answerType' => array())));
        $this->assertTrue(count($response->result) > 1, "Could not find enough recently answered questions");
        $this->assertEqual($question2->ID, $response->result[0]->ID, "wrong question first in the list");
        $this->assertEqual($question1->ID, $response->result[1]->ID, "wrong question second in the list");

        // clean up
        foreach (array($question1, $question2) as $deleteMe) {
            $this->destroyObject($deleteMe);
        }
    }

    function testGetRecentlyAskedQuestionsDoesNotReturnPendingQuestions() {
        $this->logIn();

        // create a pending question with a best answer, need to create the comment before changing the question to pending
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        )))->result;

        $comment = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        )))->result;
        $this->model->markCommentAsBestAnswer($comment->ID);
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->update($question->ID, array(
            'CommunityQuestion.StatusWithType.Status.ID' => (object) array('value' => 32), // pending
        )))->result;

        // we shouldn't see our question since it is pending
        $response = $this->assertResponseObject($this->model->getRecentlyAskedQuestions(array('answerType' => array())));
        if (count($response->result) > 1) {
            $this->assertNotEqual($question->ID, $response->result[0]->ID, "shouldn't see our pending question");
        }

        // clean up
        $this->destroyObject($question);

        $this->logOut();
    }

    function testGetRecentlyAskedQuestionsDoesNotReturnSuspendedQuestions() {
        $this->logIn();

        // create a suspended question with a best answer, need to create the comment before suspending the question
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        )))->result;

        $comment = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        )))->result;
        $this->model->markCommentAsBestAnswer($comment->ID);
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->update($question->ID, array(
            'CommunityQuestion.StatusWithType.Status.ID' => (object) array('value' => 30), // suspended
        )))->result;

        // we shouldn't see our question since it is suspended
        $response = $this->assertResponseObject($this->model->getRecentlyAskedQuestions(array('answerType' => array())));
        if (count($response->result) > 1) {
            $this->assertNotEqual($question->ID, $response->result[0]->ID, "shouldn't see our suspended question");
        }

        // clean up
        $this->destroyObject($question);

        $this->logOut();
    }

    function testGetRecentlyAskedQuestionsWithRatings() {
        // create a question with a comment and mark it as best answer
        $this->logIn('useractive1');
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'Distance to the sun ' . __FUNCTION__),
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityQuestion.Body' => (object) array('value' => 'How far would you say is it from the Earth to the sun?'),
        )))->result;
        $comment = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.Body' => (object) array('value' => "2 miles")
        )))->result;
        $this->assertResponseObject($this->model->markCommentAsBestAnswer($comment->ID));

        // now our question should be first in the list, and should have no rating data
        $response = $this->assertResponseObject($this->model->getRecentlyAskedQuestions(array()))->result;
        $this->assertEqual($question->ID, $response[0]->ID, "wrong question at the front of the list");
        $this->assertNull($response[0]->RatingValue, "Should not have rating value for unrated question");

        // rate the question and ensure it has rating data
        $this->logOut();
        $this->logIn('useractive2');
        $this->assertResponseObject($this->model->rateQuestion($question, 50));
        $response = $this->assertResponseObject($this->model->getRecentlyAskedQuestions(array()))->result;
        $this->assertEqual($question->ID, $response[0]->ID, "wrong question at the front of the list");
        $this->assertEqual(50, $response[0]->RatingValue, "Wrong rating value");

        // log in as a different user and verify they don't get the rating data returned
        $this->logOut();
        $this->logIn('modactive1');
        $response = $this->assertResponseObject($this->model->getRecentlyAskedQuestions(array()))->result;
        $this->assertEqual($question->ID, $response[0]->ID, "wrong question at the front of the list");
        $this->assertNull($response[0]->RatingValue, "Should not get someone else's rating value");

        // still as a different user, rate the question and ensure we get our rating back
        $this->assertResponseObject($this->model->rateQuestion($question, 60));
        $response = $this->assertResponseObject($this->model->getRecentlyAskedQuestions(array()))->result;
        $this->assertEqual($question->ID, $response[0]->ID, "wrong question at the front of the list");
        $this->assertEqual(60, $response[0]->RatingValue, "Wrong rating value");

        // clean up - since we logged in as a different user we must commit the deletes
        $this->destroyObject($question);
        Connect\ConnectAPI::commit();
    }

    function testGetRecentlyAskedQuestionsWithFlags() {
        // create a question with a comment and mark it as best answer
        $this->logIn('useractive1');
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'Distance to the sun ' . __FUNCTION__),
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityQuestion.Body' => (object) array('value' => 'How far would you say is it from the Earth to the sun?'),
        )))->result;
        $comment = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.Body' => (object) array('value' => "2 miles")
        )))->result;
        $this->assertResponseObject($this->model->markCommentAsBestAnswer($comment->ID));

        // now our question should be first in the list, and should have no flag data
        $response = $this->assertResponseObject($this->model->getRecentlyAskedQuestions(array()))->result;
        $this->assertEqual($question->ID, $response[0]->ID, "wrong question at the front of the list");
        $this->assertNull($response[0]->FlagType, "Should not have flag value for unrated question");

        // flag the question and ensure it has flag data
        $this->logOut();
        $this->logIn('useractive2');
        $this->assertResponseObject($this->model->flagQuestion($question->ID, 1));
        $response = $this->assertResponseObject($this->model->getRecentlyAskedQuestions(array()))->result;
        $this->assertEqual($question->ID, $response[0]->ID, "wrong question at the front of the list");
        $this->assertEqual(1, $response[0]->FlagType, "Wrong flag value");

        // log in as a different user and verify they don't get the flag data returned
        $this->logOut();
        $this->logIn($this->contacts['moderator']->Login);
        $response = $this->assertResponseObject($this->model->getRecentlyAskedQuestions(array()))->result;
        $this->assertEqual($question->ID, $response[0]->ID, "wrong question at the front of the list");
        $this->assertNull($response[0]->FlagType, "Should not get someone else's flag value");

        // still as the different user, flag the question and ensure it has only our flag data
        $this->assertResponseObject($this->model->flagQuestion($question->ID, 2));
        $response = $this->assertResponseObject($this->model->getRecentlyAskedQuestions(array()))->result;
        $this->assertEqual($question->ID, $response[0]->ID, "wrong question at the front of the list");
        $this->assertEqual(2, $response[0]->FlagType, "Wrong flag value");

        // clean up - since we logged in as a different user we must commit the deletes
        $this->destroyObject($question);
        Connect\ConnectAPI::commit();
    }

    function testGetRecentlyAskedQuestionsWithBestAnswers() {
        // create a question with a best answer
        $this->logIn();
        $question = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        $comment = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        ))->result;
        $this->assertResponseObject($this->model->markCommentAsBestAnswer($comment->ID));

        $questionList = $this->assertResponseObject($this->model->getRecentlyAskedQuestions(array()))->result;
        $this->assertEqual($question->ID, $questionList[0]->ID, "Wrong question at front of list");
        $this->assertEqual($comment->ID, $questionList[0]->BestCommunityQuestionAnswers->CommunityComment, "Couldn't find best answer");

        // author selected best answer
        $filters = array(
            'maxQuestions' => 3,
            'answerType' => array('author'),
        );
        $questionList = $this->assertResponseObject($this->model->getRecentlyAskedQuestions($filters))->result;
        $this->assertTrue(count($questionList) <= 3);
        $this->assertEqual($question->ID, $questionList[0]->ID, "Wrong question at front of list");
        $this->assertEqual($comment->ID, $questionList[0]->BestCommunityQuestionAnswers->CommunityComment, "Couldn't find best answer");
        foreach ($questionList as $bestAnswer) {
            if (intval($bestAnswer->BestCommunityQuestionAnswers->BestAnswerType) === SSS_BEST_ANSWER_MODERATOR) {
                $this->fail("Request for author best answers contains a moderator best answer");
            }
        }
        $this->logOut();

        // moderator selected best answer
        // log in as a different user which has moderator permissions
        $this->logIn($this->contacts['moderator']->Login);

        $comment2 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        ))->result;
        $this->assertResponseObject($this->model->markCommentAsBestAnswer($comment2->ID));
        $filters = array(
            'maxQuestions' => 4,
            'answerType' => array('moderator'),
        );
        $questionList = $this->assertResponseObject($this->model->getRecentlyAskedQuestions($filters))->result;
        $this->assertTrue(count($questionList) <= 4);
        $this->assertEqual($question->ID, $questionList[0]->ID, "Wrong question at front of list");
        $this->assertEqual($comment2->ID, $questionList[0]->BestCommunityQuestionAnswers->CommunityComment, "Couldn't find best answer");
        foreach ($questionList as $bestAnswer) {
            if (intval($bestAnswer->BestCommunityQuestionAnswers->BestAnswerType) === SSS_BEST_ANSWER_AUTHOR) {
                $this->fail("Request for author best answers contains a moderator best answer");
            }
        }

        // test for author and moderator best answers
        $filters = array (
            'maxQuestions' => 1,
            'answerType' => array('author', 'moderator'),
        );
        $questionList = $this->assertResponseObject($this->model->getRecentlyAskedQuestions($filters))->result;

        $this->assertEqual(2, count($questionList));
        $this->assertEqual($question->ID, $questionList[0]->ID, "Wrong question at front of list");
        $this->assertEqual($comment2->ID, $questionList[0]->BestCommunityQuestionAnswers->CommunityComment, "Couldn't find best answer");
        $this->assertEqual(SSS_BEST_ANSWER_MODERATOR, $questionList[0]->BestCommunityQuestionAnswers->BestAnswerType, "Comment had wrong best answer type");
        $this->assertEqual($question->ID, $questionList[1]->ID, "Wrong question second in the list");
        $this->assertEqual($comment->ID, $questionList[1]->BestCommunityQuestionAnswers->CommunityComment, "Couldn't find best answer");
        $this->assertEqual(SSS_BEST_ANSWER_AUTHOR, $questionList[1]->BestCommunityQuestionAnswers->BestAnswerType, "Comment had wrong best answer type");
        $this->logOut();

        // community selected best answer
        $this->logIn($this->contacts['admin']->Login);
        $comment3 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        ))->result;
        $bestAnswer = new Connect\BestCommunityQuestionAnswer();
        $bestAnswer->BestAnswerType->ID = SSS_BEST_ANSWER_COMMUNITY;
        $bestAnswer->CommunityComment = $comment3;
        $bestAnswer->CommunityUser = $this->CI->model('CommunityUser')->get()->result;
        $question->BestCommunityQuestionAnswers[] = $bestAnswer;
        $question->save();

        $filters = array(
            'maxQuestions' => 4,
            'answerType' => array('community'),
        );
        $questionList = $this->assertResponseObject($this->model->getRecentlyAskedQuestions($filters))->result;
        $this->assertTrue(count($questionList) <= 4);
        $this->assertEqual($question->ID, $questionList[0]->ID, "Wrong question at front of list");
        $this->assertEqual($comment3->ID, $questionList[0]->BestCommunityQuestionAnswers->CommunityComment, "Couldn't find best answer");
        foreach ($questionList as $bestAnswer) {
            if (intval($bestAnswer->BestCommunityQuestionAnswers->BestAnswerType) !== SSS_BEST_ANSWER_COMMUNITY) {
                $this->fail("Request for commnity best answers does not contain community best answer");
            }
        }
        $this->logOut();

        // Returning no best answers
        $filters = array (
            'maxQuestions' => 10,
            'answerType' => array(),
        );
        $questionList = $this->assertResponseObject($this->model->getRecentlyAskedQuestions($filters))->result;
        foreach ($questionList as $questionWithoutBestAnswer) {
            $this->assertNull($questionWithoutBestAnswer->BestCommunityQuestionAnswers);
            $this->assertNull($questionWithoutBestAnswer->BestCommunityQuestionAnswers->BestAnswerType);
        }

        // clean up - since we logged in as a different user we must commit the deletes
        $this->destroyObject($question);
        Connect\ConnectAPI::commit();
    }

    function testMultipleAnsweredQuestions() {
        // create a question with a best answer
        $this->logIn();

        $question1 = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        $comment1 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question1->ID)
        ))->result;
        $this->assertResponseObject($this->model->markCommentAsBestAnswer($comment1->ID));

        $question2 = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas')
        ))->result;
        $comment2 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question2->ID)
        ))->result;
        $this->assertResponseObject($this->model->markCommentAsBestAnswer($comment2->ID));

        $question3 = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas')
        ))->result;
        $comment3 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question3->ID)
        ))->result;
        $response = $this->assertResponseObject($this->model->markCommentAsBestAnswer($comment3->ID));

        $data = $this->assertResponseObject($this->model->getRecentlyAskedQuestions(array('maxQuestions' => 2)))->result;

        $this->assertIsA($data, 'Array');

        $questionIDs = array();
        foreach ($data as $row) {
            if (!in_array($row->ID, $questionIDs)) {
                $questionIDs[] = $row->ID;
            }
        }

        $this->assertSame(2, count($questionIDs));
        $this->assertFalse(in_array($question1->ID, $questionIDs));

        // clean up
        foreach (array($comment1, $comment2, $comment3, $question1, $question2, $question3) as $deleteMe)
            $this->destroyObject($deleteMe);
    }

    function testRecentQuestionsByProduct() {
        // create a question with a product and a comment which is marked as a best answer
        $this->logIn();
        $productID = 1;
        $question1 = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'Distance to the sun ' . __FUNCTION__),
            'CommunityQuestion.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityQuestion.Body' => (object) array('value' => 'How far is it from the Earth to the sun?'),
            'CommunityQuestion.Product' => (object) array('value' => $productID),
        )))->result;
        $comment1 = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question1->ID),
            'CommunityComment.Body' => (object) array('value' => "2 miles")
        )))->result;
        $this->model->markCommentAsBestAnswer($comment1->ID);

        // create a second answered question with a different product
        $question2 = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'Distance to the moon'),
            'CommunityQuestion.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityQuestion.Body' => (object) array('value' => 'How far is it from the Earth to the moon?'),
            'CommunityQuestion.Product' => (object) array('value' => $productID + 1),
        )))->result;
        $comment2 = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question2->ID),
            'CommunityComment.Body' => (object) array('value' => "5 miles")
        )))->result;
        $this->model->markCommentAsBestAnswer($comment2->ID);

        // get the list of recently answered questions
        $filters = array(
            'maxQuestions' => 10,
            'product' => $productID,
        );
        $response = $this->assertResponseObject($this->model->getRecentlyAskedQuestions($filters));
        $questionsList = $response->result;

        // the first question should be in the list and the second should not
        $this->assertEqual($question1->ID, $questionsList[0]->ID, "Wrong question returned first in list");
        foreach ($questionsList as $question) {
            $this->assertNotEqual($question2->ID, $question->ID, "\$question2 should not be in the list because it has a different product");
        }

        // clean up
        foreach (array($comment1, $question1, $comment2, $question2) as $deleteMe) {
            $this->destroyObject($deleteMe);
        }
    }

    function testRecentQuestionsByCategory() {
        // create a question with a category and a comment which is marked as a best answer
        $this->logIn();
        $categoryID = 70;
        $question1 = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'Distance to the sun ' . __FUNCTION__),
            'CommunityQuestion.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityQuestion.Body' => (object) array('value' => 'How far is it from the Earth to the sun?'),
            'CommunityQuestion.Category' => (object) array('value' => $categoryID),
        )))->result;
        $comment1 = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question1->ID),
            'CommunityComment.Body' => (object) array('value' => "2 miles")
        )))->result;
        $this->model->markCommentAsBestAnswer($comment1->ID);

        // create a second answered question with a different category
        $question2 = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'Distance to the moon'),
            'CommunityQuestion.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityQuestion.Body' => (object) array('value' => 'How far is it from the Earth to the moon?'),
            'CommunityQuestion.Category' => (object) array('value' => $categoryID + 1),
        )))->result;
        $comment2 = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question2->ID),
            'CommunityComment.Body' => (object) array('value' => "5 miles")
        )))->result;
        $this->model->markCommentAsBestAnswer($comment2->ID);

        // get the list of recently answered questions
        $filters = array(
            'maxQuestions' => 10,
            'category' => $categoryID,
        );
        $response = $this->assertResponseObject($this->model->getRecentlyAskedQuestions($filters));
        $questionsList = $response->result;

        // the first question should be in the list and the second should not
        $this->assertEqual($question1->ID, $questionsList[0]->ID, "Wrong question returned first in list");
        foreach ($questionsList as $question) {
            $this->assertNotEqual($question2->ID, $question->ID, "\$question2 should not be in the list because it has a different category");
        }

        // clean up
        foreach (array($comment1, $question1, $comment2, $question2) as $deleteMe) {
            $this->destroyObject($deleteMe);
        }
    }

    function testD() {
        $this->logIn();
        $question = new Connect\CommunityQuestion();
        $question->Subject = 'shibby';
        $question->save();

        $comment = new Connect\CommunityComment();
        $comment->CommunityQuestion = $question;
        $comment->save();

        $bestAnswer = new Connect\BestCommunityQuestionAnswer();
        $bestAnswer->BestAnswerType->ID = SSS_BEST_ANSWER_AUTHOR;
        $bestAnswer->CommunityComment = $comment;
        $bestAnswer->CommunityUser = $question->CreatedByCommunityUser;
        $question->BestCommunityQuestionAnswers[] = $bestAnswer;
        $question->save();
    }

    function testBestAnswer() {
        // create a question with a comment
        $this->logIn();
        $question = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        $comment = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        ))->result;

        // fails with invalid comment ID
        $this->assertResponseObject($this->model->markCommentAsBestAnswer(-1), 'is_null', 1);

        // ensure that there are no best answers by default
        $this->assertSame(0, count($question->BestCommunityQuestionAnswers), "Question should not have a best answer yet");

        $response = $this->assertResponseObject($this->model->markCommentAsBestAnswer($comment->ID));

        // ensure we have a best answer now
        $this->assertSame(1, count($question->BestCommunityQuestionAnswers), "Question should have a best answer now");
        $this->assertSame(1, count($response->result));
        $this->assertTrue($response->result[0]->CommunityComment->ID > 0);
        $this->assertSame(SSS_BEST_ANSWER_AUTHOR, $response->result[0]->BestAnswerType->ID);

        // clean up
        foreach (array($comment, $question) as $deleteMe) {
            $this->destroyObject($deleteMe);
        }
    }

    function testUnmarkCommentAsBestAnswerAsModerator() {
        $question = $this->fixtureInstance->make('QuestionActiveUserActiveSameBestAnswer');
        $moderator = $this->fixtureInstance->make('UserModActive');
        $this->logIn($moderator->Contact->Login);

        // fails with invalid comment ID
        $this->assertResponseObject($this->model->unmarkCommentAsBestAnswer(-1), 'is_null', 1);

        $this->assertSame(2, count($question->BestCommunityQuestionAnswers));
        //Get Best answer chosen by moderator
        foreach($question->BestCommunityQuestionAnswers as $bestAnswer) {
           if($bestAnswer->BestAnswerType->LookupName === "Moderator Selected") {
                $bestAnswerToUnmark = $bestAnswer;
                break;
           }
        }
        $response = $this->assertResponseObject($this->model->unmarkCommentAsBestAnswer($bestAnswerToUnmark->CommunityComment->ID));
        $this->assertSame(1, count($question->BestCommunityQuestionAnswers));

        $this->logOut();
        $this->fixtureInstance->destroy();
    }

    function testUnmarkCommentAsBestAnswerAsAuthor() {
        $question = $this->fixtureInstance->make('QuestionActiveUserActiveSameBestAnswer');
        $author = $this->fixtureInstance->make('UserActive1');
        $this->logIn($author->Contact->Login);

        $this->assertSame(2, count($question->BestCommunityQuestionAnswers));
        //Get Best answer chosen by author
        foreach($question->BestCommunityQuestionAnswers as $bestAnswer) {
           if($bestAnswer->BestAnswerType->LookupName === "Author Selected") {
                $bestAnswerToUnmark = $bestAnswer;
                break;
           }
        }
        $response = $this->assertResponseObject($this->model->unmarkCommentAsBestAnswer($bestAnswerToUnmark->CommunityComment->ID));
        $this->assertSame(1, count($question->BestCommunityQuestionAnswers));

        $this->logOut();
        $this->login('modactive1');

        //Unmark last best answer
        $response = $this->assertResponseObject($this->model->unmarkCommentAsBestAnswer($question->BestCommunityQuestionAnswers[0]->CommunityComment->ID), 'is_null');
        $this->assertSame(0, count($question->BestCommunityQuestionAnswers));

        $this->logOut();
        $this->fixtureInstance->destroy();
    }

    /** QA 131112-000139 **/
    function testBestAnswerCanBeChanged() {
        // create a question with two comments and mark the first as best answer
        $this->logIn();
        $question = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        $comment1 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        ))->result;
        $comment2 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        ))->result;
        $response = $this->assertResponseObject($this->model->markCommentAsBestAnswer($comment1->ID));

        // now change the best answer to the second one
        $response = $this->assertResponseObject($this->model->markCommentAsBestAnswer($comment2->ID));

        // ensure it took
        $this->assertSame(1, count($question->BestCommunityQuestionAnswers), "Question should have a best answer now");
        $this->assertEqual($comment2->ID, $question->BestCommunityQuestionAnswers[0]->CommunityComment->ID);

        // clean up
        foreach (array($comment1, $comment2, $question) as $deleteMe) {
            $this->destroyObject($deleteMe);
        }
    }

    function testGetBestAnswers() {
        //Create a question without best answers, expect empty array
        $this->logIn();
        $question = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;

        //Expect zero results
        $result = $this->model->getBestAnswers($question);
        $this->assertIsA($result, '\RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($result->result));
        $this->assertTrue(count($result->result) === 0);
        $this->assertFalse(count($response->errors) > 0, print_r($response->errors, true));
        $this->assertSame(0, count($result->warnings));

        // Add two comments and mark one a best answer
        $comment1 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        ))->result;
        $comment2 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        ))->result;
        $this->model->markCommentAsBestAnswer($comment1->ID);

        // Expect one result
        $result = $this->model->getBestAnswers($question);
        $this->assertIsA($result, '\RightNow\Libraries\ResponseObject');
        $this->assertTrue(count($result->result) === 1);
        $this->assertIsA($result->result[0]->CommunityComment->SocialPermissions, 'RightNow\Decorators\SocialCommentPermissions');
        $this->assertFalse(count($response->errors) > 0, print_r($response->errors, true));

        // Comment has status of deleted
        $deletedBAQuestion = $this->fixtureInstance->make('QuestionActiveBestAnswerDeleted');
        $result = $this->model->getBestAnswers($deletedBAQuestion);
        $this->assertIsA($result, '\RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($result->result));
        $this->assertTrue(count($result->result) === 0);
        $this->assertFalse(count($response->errors) > 0, print_r($response->errors, true));
        $this->assertSame(0, count($result->warnings));

        // Parent Comment has status of deleted
        $deletedParentBAQuestion = $this->fixtureInstance->make('QuestionActiveParentCommentDeleted');
        $result = $this->model->getBestAnswers($deletedParentBAQuestion);
        $this->assertIsA($result, '\RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($result->result));
        $this->assertTrue(count($result->result) === 0);
        $this->assertFalse(count($response->errors) > 0, print_r($response->errors, true));
        $this->assertSame(0, count($result->warnings));

        //Clean up
        foreach (array($comment1, $comment2, $question) as $deleteMe) {
            $this->destroyObject($deleteMe);
        }
        $this->fixtureInstance->destroy();
    }

    function testGetBestAnswerForComment() {
        // Create a question with no best answers
        $this->logIn();
        $question = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;

        // Add two comments and mark one a best answer
        $comment1 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        ))->result;
        $comment2 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        ))->result;
        $this->model->markCommentAsBestAnswer($comment1->ID);

        // BestSocialAnswers, but don't match given comment
        $result = $this->model->getBestAnswerForComment($question, $comment2);
        $this->assertIsA($result, '\RightNow\Libraries\ResponseObject');
        $this->assertNull($result->result);
        $this->assertFalse(count($response->errors) > 0, print_r($response->errors, true));
        $this->assertSame(1, count($result->warnings));

        // comment as Comment
        $result = $this->assertResponseObject($this->model->getBestAnswerForComment($question, $comment1));
        $this->assertIsA($result->result, CONNECT_NAMESPACE_PREFIX . '\BestCommunityQuestionAnswer');
        $this->assertIdentical($result->result->CommunityComment->ID, $comment1->ID);

        // comment, but doesn't match the specified type
        $result = $this->model->getBestAnswerForComment($question, $comment1, SSS_BEST_ANSWER_MODERATOR);
        $this->assertIsA($result, '\RightNow\Libraries\ResponseObject');
        $this->assertNull($result->result);
        $this->assertFalse(count($response->errors) > 0, print_r($response->errors, true));
        $this->assertSame(1, count($result->warnings));

        // comment has status of deleted
        $deletedBAQuestion = $this->fixtureInstance->make('QuestionActiveBestAnswerDeleted');
        $deletedComment = $this->fixtureInstance->make('CommentDeletedModActive');
        $result = $this->model->getBestAnswerForComment($deletedBAQuestion, $deletedComment, SSS_BEST_ANSWER_MODERATOR);
        $this->assertIsA($result, '\RightNow\Libraries\ResponseObject');
        $this->assertNull($result->result);
        $this->assertFalse(count($response->errors) > 0, print_r($response->errors, true));
        $this->assertSame(1, count($result->warnings));

        // comment matches specified type
        $result = $this->assertResponseObject($this->model->getBestAnswerForComment($question, $comment1, SSS_BEST_ANSWER_AUTHOR));
        $this->assertIsA($result->result, CONNECT_NAMESPACE_PREFIX . '\BestCommunityQuestionAnswer');
        $this->assertIdentical($result->result->BestAnswerType->ID, SSS_BEST_ANSWER_AUTHOR);

        // clean up
        foreach (array($comment1, $comment2, $question) as $deleteMe) {
            $this->destroyObject($deleteMe);
        }
        $this->fixtureInstance->destroy();
    }

    function testFlagQuestion() {
        $this->logIn('useractive1');

        // send in a null question
        $response = $this->model->flagQuestion(null);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertIdentical($response->errors[0]->externalMessage, "Invalid Question");

        // send in an invalid question
        $response = $this->model->flagQuestion((object)array(1,2,3));
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertEqual($response->errors[0]->externalMessage, "Invalid Question");

        // now create a question and ensure it has no flags
        $question = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        $response = $this->assertResponseObject($this->model->getUserFlag($question->ID));
        $this->assertNull($response->result);

        // flag the question
        $this->logOut();
        $this->logIn('useractive2');
        $response = $this->assertResponseObject($this->model->flagQuestion($question->ID));
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\CommunityQuestionFlg');
        $flag = $response->result;
        $this->assertEqual($this->CI->model('CommunityUser')->get()->result->ID, $flag->CreatedByCommunityUser->ID);
        $this->assertEqual($question->ID, $flag->CommunityQuestion->ID);

        // ensure we can see the flag and that it matches the one we have
        $response = $this->assertResponseObject($this->model->getUserFlag($question->ID));
        $this->assertNotNull($response->result, "Wrong number of flags - should have one");

        $this->assertEqual($flag->ID, $response->result->ID, "Wrong flag returned");

        // clean up; flags are deleted automatically when their question is deleted
        $this->destroyObject($question);
        Connect\ConnectAPI::commit();
    }

    function testFlagQuestionWithAllTypes() {
        // retrieve all the possible types
        $flagTypes = ConnectUtil::getNamedValues('CommunityQuestionFlg', 'Type');
        $this->assertTrue(count($flagTypes) > 0, "Need at least one flag type");

        // create a question for each type and flag with that type
        $this->logIn('useractive1');
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => "Flag me as type {$flagType->LookupName}")
        )))->result;

        $this->logOut();
        $this->logIn('useractive2');
        foreach ($flagTypes as $flagType) {
            $this->assertResponseObject($this->model->flagQuestion($question->ID, $flagType->ID));

            // ensure we can see the flag and that the author and type are correct
            $response = $this->assertResponseObject($this->model->getUserFlag($question->ID));
            $this->assertEqual($this->CI->model('CommunityUser')->get()->result->ID, $response->result->CreatedByCommunityUser->ID);
            $this->assertEqual($flagType->ID, $response->result->Type->ID);
        }

        // clean up
        $this->destroyObject($question);
        Connect\ConnectAPI::commit();
    }

    function testRateQuestion () {
        $ratingValue = 50;
        $this->logIn('useractive1');

        // now create a question and ensure it has no ratings
        $question = $this->CI->model('CommunityQuestion')->create(array(
                'CommunityQuestion.Product' => (object) array('value' => 1),
                'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
            ))->result;
        $response = $this->model->getUserRating($question);
        $this->assertEqual(0, count($response->result));

        // rate the question
        $this->logOut();
        $this->logIn('useractive2');
        $rating = $this->assertResponseObject($this->model->rateQuestion($question, $ratingValue))->result;
        $this->assertIsA($rating, CONNECT_NAMESPACE_PREFIX . '\CommunityQuestionRtg');
        $this->assertEqual($this->CI->model('CommunityUser')->get()->result->ID, $rating->CreatedByCommunityUser->ID);
        $this->assertEqual($ratingValue, $rating->RatingValue);

        // ensure we can see the rating and that the author and question are correct
        $response = $this->assertResponseObject($this->model->getUserRating($question));
        $this->assertNotNull($response->result, "Wrong number of ratings - should have one");
        $this->assertEqual($rating->ID, $response->result->ID, "Wrong rating returned");

        // clean up; ratings are deleted automatically when their question is deleted
        $this->destroyObject($question);
        Connect\ConnectAPI::commit();
    }

    function testResetQuestionRating () {
        $ratingValue = 50;

        // log in as a user besides the UserActive1 to create comment
        $moderator = $this->fixtureInstance->make('UserModActive');
        $this->logIn($moderator->Contact->Login);

        // now create a comment and ensure it has no ratings
        list($fixtureInstance, $question) = $this->getFixtures(array(
            'QuestionActiveModActive'
        ));

        $this->logOut();

        // log in as UserActive1, in which to rate shibby's comment
        $userActive = $this->fixtureInstance->make('UserActive1');
        $this->logIn($userActive->Contact->Login);

        $rating = $this->assertResponseObject($this->model->rateQuestion($question, $ratingValue))->result;
        $this->assertIsA($rating, CONNECT_NAMESPACE_PREFIX . '\CommunityQuestionRtg');
        $this->assertEqual($this->CI->model('CommunityUser')->get()->result->ID, $rating->CreatedByCommunityUser->ID);
        $this->assertEqual($ratingValue, $rating->RatingValue);

        // reset the rating on question
        $oldRating = $this->assertNotNull($this->model->resetQuestionRating($question))->result;

        // ensure that the rating is reset
        $response = $this->assertNull($this->model->getUserRating($question)->result);

        // clean up; ratings are deleted automatically when their question is deleted
        $this->destroyObject($question);
        Connect\ConnectAPI::commit();
    }

    function testRateQuestionValidation() {
        $this->logIn('useractive1');

        // send in a null question
        $response = $this->model->rateQuestion(null, 50);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertEqual($response->errors[0]->externalMessage, "Invalid Question");

        // send in an invalid question
        $response = $this->model->rateQuestion((object)array(1,2,3), 50);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertEqual($response->errors[0]->externalMessage, "Invalid Question");

        // create questions to rate
        $question1 = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        $question2 = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;

        // author cannot rate
        $response = $this->model->rateQuestion($question1, 50);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertEqual($response->errors[0]->externalMessage, "User does not have permission to rate a question");

        // log in as a different user and use the other question from here on out
        // since the first one has a negative result cached
        $this->logOut();
        $this->logIn('useractive2');

        // rating values must be > 0 and <= 100
        $response = $this->model->rateQuestion($question2, 0);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertFalse(strpos($response->errors[0]->externalMessage, "Minimum value exceeded") === false, print_r($response->errors, true));

        // similar to flags, new ratings should overwrite the old
        $this->assertResponseObject($this->model->rateQuestion($question2, 50, 100));
        //reseting the rating
        $this->model->resetQuestionRating($question2);

        $response = $this->model->rateQuestion($question2, 101);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertFalse(strpos($response->errors[0]->externalMessage, "Maximum value exceeded") === false, print_r($response->errors, true));

        // rating weights must be > 0 and <= 500
        $response = $this->model->rateQuestion($question2, 50, 0);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertFalse(strpos($response->errors[0]->externalMessage, "Minimum value exceeded") === false, print_r($response->errors, true));

        $response = $this->model->rateQuestion($question2, 50, 501);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertFalse(strpos($response->errors[0]->externalMessage, "Maximum value exceeded") === false, print_r($response->errors, true));

        // rating should succeed with 1 more than the min values
        $rating = $this->assertResponseObject($this->model->rateQuestion($question2, 1, 1))->result;
        //reseting the rating
        $this->model->resetQuestionRating($question2);
        // rating should succeed with 1 less than the max value
        $this->assertResponseObject($this->model->rateQuestion($question2, 100, 500))->result;

        // clean up; ratings are deleted automatically when their question is deleted
        $this->destroyObject($question1);
        $this->destroyObject($question2);
        Connect\ConnectAPI::commit();
    }

    function testGetUserRating() {
        // create a question and rate it
        $this->logIn('useractive1');
        $question = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        $this->logOut();
        $this->logIn('useractive2');
        $rating = $this->assertResponseObject($this->model->rateQuestion($question, 1, 1))->result;

        // if no user is passed, defaults to the current user
        $currentUser = $this->CI->model('CommunityUser')->get()->result;
        $rating = $this->assertResponseObject($this->model->getUserRating($question))->result;
        $this->assertEqual($currentUser->ID, $rating->CreatedByCommunityUser->ID);

        // get rating for the current user
        $rating = $this->assertResponseObject($this->model->getUserRating($question, $currentUser))->result;
        $this->assertEqual($currentUser->ID, $rating->CreatedByCommunityUser->ID);

        // a different user will not have a rating
        $differentUser = $this->CI->model("CommunityUser")->getForContact($this->contacts['moderator']->ID)->result;
        $rating = $this->model->getUserRating($question, $differentUser)->result;
        $this->assertNull($rating);

        // clean up; ratings are deleted automatically when their question is deleted
        $this->destroyObject($question);
    }

    /**
     * Rating summaries are wired in that they can only be accessed via tabular ROQL, e.g. via the
     * getRecentlyAskedQuestions() method.  They also don't provide the summary data directly,
     * there is some post-processing required (this allows the insert to be faster)
     *
     * SMC: disabled due to SPM bug,see 150519-000097
     **/
    function xtestRatingSummaries() {
        // create a question with a comment and mark it as best answer so it will show up in
        // the recently answered question list
        $this->logIn('useractive1');
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'Distance to the sun ' . __FUNCTION__),
            'CommunityQuestion.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityQuestion.Body' => (object) array('value' => 'How far is it from the Earth to the sun?'),
        )))->result;
        $comment = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityComment.Body' => (object) array('value' => "2 miles")
        )))->result;
        $this->model->markCommentAsBestAnswer($comment->ID);

        // get the tabular question data which should include the rating summary
        $data = $this->assertResponseObject($this->model->getRecentlyAskedQuestions(array()))->result[0];
        $this->assertNull($data->ContentRatingSummaries->NegativeVoteCount, "Unrated question should not have any summary data");
        $this->assertNull($data->ContentRatingSummaries->PositiveVoteCount, "Unrated question should not have any summary data");
        $this->assertNull($data->ContentRatingSummaries->RatingTotal, "Unrated question should not have any summary data");
        $this->assertNull($data->ContentRatingSummaries->RatingWeightedCount, "Unrated question should not have any summary data");
        $this->assertNull($data->ContentRatingSummaries->RatingWeightedTotal, "Unrated question should not have any summary data");

        // rate the question with a low value
        $this->logOut();
        $this->logIn('useractive2');
        $lowRatingValue = 25;
        $rating = $this->assertResponseObject($this->model->rateQuestion($question, $lowRatingValue))->result;

        // we should see a change in the rating summary
        $data = $this->assertResponseObject($this->model->getRecentlyAskedQuestions(array()))->result[0];
        $this->assertEqual(1, $data->ContentRatingSummaries->NegativeVoteCount, "Low rating value should count as a negative vote");
        $this->assertEqual(0, $data->ContentRatingSummaries->PositiveVoteCount, "There should not be any positive votes yet");
        $this->assertEqual($lowRatingValue, $data->ContentRatingSummaries->RatingTotal, "Wrong rating total");
        $this->assertEqual(100, $data->ContentRatingSummaries->RatingWeightedCount);
        $this->assertEqual(($lowRatingValue * 100), $data->ContentRatingSummaries->RatingWeightedTotal);

        // log in as a different user and rate the question with a high, weighted value
        $this->logOut();
        $this->logIn($this->contacts['moderator']->Login);
        $highRatingValue = 75;
        $rating = $this->assertResponseObject($this->model->rateQuestion($question, $highRatingValue, 200))->result;

        // we should see a change in the rating summary
        $data = $this->assertResponseObject($this->model->getRecentlyAskedQuestions(array()))->result[0];
        $this->assertEqual(1, $data->ContentRatingSummaries->NegativeVoteCount, "Low rating value should count as a negative vote");
        $this->assertEqual(1, $data->ContentRatingSummaries->PositiveVoteCount, "High rating value should count as a positive vote");
        $this->assertEqual($lowRatingValue + $highRatingValue, $data->ContentRatingSummaries->RatingTotal, "Wrong rating total");
        $this->assertEqual(300, $data->ContentRatingSummaries->RatingWeightedCount);
        $this->assertEqual(($lowRatingValue * 100) + ($highRatingValue * 200), $data->ContentRatingSummaries->RatingWeightedTotal);

        // clean up; ratings are deleted automatically when their question is deleted
        $this->destroyObject($question);
        Connect\ConnectAPI::commit();
    }

    function testGetTopLevelCommentCount() {
        $this->assertSame(0, $this->model->getTopLevelCommentCount(null));
        $this->assertSame(0, $this->model->getTopLevelCommentCount($this->model->getBlank()->result));

        $this->logIn('useractive1');

        // create a question with no children
        $question1 = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__),
        )))->result;
        $this->assertSame(0, $this->model->getTopLevelCommentCount($question1));

        // create a question with a comment and a child comment
        $question2 = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__),
        )))->result;
        $parentComment = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question2->ID)
        ))->result;
        $childComment = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityComment.Parent' => (object) array('value' => $parentComment->ID),
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question2->ID)
        ))->result;

        // we should only get a result for the top-level comment
        $this->assertSame(1, $this->model->getTopLevelCommentCount($question2));

        // clean up
        foreach (array($childComment, $parentComment, $question1, $question2) as $deleteMe)
            $this->destroyObject($deleteMe);
    }

    function testGetComments() {
        $this->logIn();

        // send in a null question
        $response = $this->model->getComments(null);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertEqual($response->errors[0]->externalMessage, "Invalid Question");

        // send in an invalid question
        $badQuestion = $this->model->getComments((object)array('name' => 'fred'));
        $this->assertNull($badQuestion->result);
        $this->assertTrue(count($badQuestion->errors) > 0);
        $this->assertEqual($badQuestion->errors[0]->externalMessage, "Invalid Question");

        // create a question
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        )))->result;

        // we should start out with no comments
        $response = $this->assertResponseObject($this->model->getComments($question));
        $this->assertSame(0, count($response->result), "Question should not have any comments yet");

        // add some comments and ensure they take.  As we add each comment, check for errors,
        // comment counts, and positioning
        $comments = array();
        for ($i=0; $i<3; $i++) {
            $comments[] = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
                'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
                'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            )))->result;

            $response = $this->assertResponseObject($this->model->getComments($question));
            $this->assertEqual($i+1, count($response->result), "Question should have " . ($i+1) . " comment(s)");
            // MAPI should return a reference to the orignal object
            $this->assertEqual($comments[$i]->ID, $response->result[$i]->ID, "Wrong comment at index $i");
            $this->assertIsA($response->result[$i]->SocialPermissions, 'RightNow\Decorators\SocialCommentPermissions');
        }

        // some child (e.g. not top level) comments are only returned under certain circumstances
        // an author should be able to see their own pending comment
        // a moderator should be able to see all pending and all suspended comments
        $this->logOut();
        $this->logIn('useractive2');
        $comments[] = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.Parent' => (object) array('value' => $comments[0]->ID),
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Pending"),
        )))->result;
        $comments[] = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.Parent' => (object) array('value' => $comments[0]->ID),
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Suspended"),
        )))->result;
        $this->logOut();

        // 'useractive1' - regular user
        // 'useractive2' - author of pending comment
        // 'modactive1'  - moderator
        foreach (array('useractive1' => 3, 'useractive2' => 4, 'modactive1' => 5) as $user => $numberOfComments) {
            $this->logIn($user);
            $response = $this->assertResponseObject($this->model->getComments($question));
            $this->assertSame($numberOfComments, count($response->result), "Question should have {$numberOfComments} comments but has " . count($response->result));
            $this->logOut();
        }

        // clean up - reverse the array so the child comments are deleted first
        foreach (array_merge(array_reverse($comments), array($question)) as $deleteMe)
            $this->destroyObject($deleteMe);
    }

    /**
     * getComments() should return rating values for the current user.  Rating values will not
     * be returned if the comment is unrated or is only rated by other users.
     **/
    function testGetCommentsWithRatings() {
        // create a question with three comments
        $this->logIn($this->contacts['regular']->Login);
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        )))->result;
        $comment1 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        ))->result;
        $comment2 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        ))->result;
        $comment3 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        ))->result;

        // rate the first comment
        $this->logOut();
        $this->logIn($this->contacts['moderator']->Login);
        $this->assertResponseObject($this->CI->model('CommunityComment')->rateComment($comment1, 60));

        // log in as a different user and rate the second comment
        $this->logIn();
        $this->assertResponseObject($this->CI->model('CommunityComment')->rateComment($comment2, 70));

        // the tabular comment data should include the rating information for the current user
        // who has only rated comment2; the other comments should have null rating values
        $comments = $this->assertResponseObject($this->model->getComments($question))->result;
        $this->assertEqual(3, count($comments), "Question should have 3 comment(s)");
        $this->assertNull($comments[0]->RatingValue, "First comment should not have a rating for the current user");
        $this->assertEqual(70, $comments[1]->RatingValue, "Incorrect rating value for question {$question->ID}");
        $this->assertEqual(70, $comments[1]->RatingValue);
        $this->assertNull($comments[2]->RatingValue, "Third comment should not have a rating at all");

        // clean up - since we logged in as a different user we must commit the deletes
        $this->destroyObject($question);
        Connect\ConnectAPI::commit();
    }

    function testGetCommentsWithMultipleRatings() {
        // create a question with a comment and rate it
        $this->logIn($this->contacts['regular']->Login);
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        )))->result;
        $comment = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        ))->result;

        // rate the comment as one user
        $this->logIn($this->contacts['moderator']->Login);
        $this->assertResponseObject($this->CI->model('CommunityComment')->rateComment($comment, 60));

        // log in as a different user and rate the comment
        $this->logIn();
        $this->assertResponseObject($this->CI->model('CommunityComment')->rateComment($comment, 70));

        // the tabular comment data should include the rating information for the current user
        $comments = $this->assertResponseObject($this->model->getComments($question))->result;
        $this->assertEqual(1, count($comments), "Question should have 1 comment(s)");
        $this->assertEqual(70, $comments[0]->RatingValue, "Incorrect rating value");

        // clean up - since we logged in as a different user we must commit the deletes
        $this->destroyObject($question);
        Connect\ConnectAPI::commit();
    }

    /**
     * getComments() should return flag values for the current user.  Flag values will not
     * be returned if the comment is not flagged or is only flagged by other users.
     **/
    function testGetCommentsWithFlags() {
        $this->logIn();

        // create a question with three comments
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        )))->result;
        $comment1 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        ))->result;
        $comment2 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        ))->result;
        $comment3 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        ))->result;

        // flag the first comment
        $this->logOut();
        $this->logIn('useractive1');
        $this->assertResponseObject($this->CI->model('CommunityComment')->flagComment($comment1, 1));

        // log in as a different user and flag the second comment
        $this->logOut();
        $this->logIn($this->contacts['moderator']->Login);
        $this->assertResponseObject($this->CI->model('CommunityComment')->flagComment($comment2, 2));

        // the tabular comment data should include the flag information for the current user
        // who has only flagged comment2; the other comments should have null flag values
        $comments = $this->assertResponseObject($this->model->getComments($question))->result;
        $this->assertEqual(3, count($comments), "Question should have 3 comment(s)");
        $this->assertNull($comments[0]->FlagType, "First comment should not have a flag for the current user");
        $this->assertEqual(2, $comments[1]->FlagType, "Incorrect flag value");
        $this->assertNull($comments[2]->FlagType, "Third comment should not have a flag at all");

        // clean up - since we logged in as a different user we must commit the deletes
        $this->destroyObject($question);
        Connect\ConnectAPI::commit();
    }

    function testGetCommentsWithMultipleFlags() {
        $this->logIn();

        // create a question with a comment and flag it
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        )))->result;
        $comment = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        ))->result;

        // flag the first comment
        $this->logOut();
        $this->logIn('useractive1');
        $this->assertResponseObject($this->CI->model('CommunityComment')->flagComment($comment, 1));

        // log in as a different user and flag the comment
        $this->logOut();
        $this->logIn($this->contacts['moderator']->Login);
        $this->assertResponseObject($this->CI->model('CommunityComment')->flagComment($comment, 2));

        // the tabular comment data should include the flag information for the current user
        $comments = $this->assertResponseObject($this->model->getComments($question))->result;
        $this->assertEqual(1, count($comments), "Question should have 1 comment(s)");
        $this->assertEqual(2, $comments[0]->FlagType, "Incorrect flag type");

        // clean up - since we logged in as a different user we must commit the deletes
        $this->destroyObject($question);
        Connect\ConnectAPI::commit();
    }

    /**
     * getComments() should return comments in display order regardless of the order
     * they are entered.  So if we insert two top-level comments and then two replies
     * and then a reply to the first reply, we should get each comment with all
     * descendants in order
     *
     * TL;DR if we add a comment tree breadth-first, it should be returned depth-first
     **/
    function testGetCommentsWithNesting() {
        $this->logIn();

        // create a question
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        )))->result;

        // add comments to create this structure, but enter them breadth-first:
        // 1
        //   1.1
        //     1.1.1
        //   1.2
        // 2
        $comment1 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityComment.Body' => (object) array('value' => "Comment 1")
        ))->result;
        $comment2 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityComment.Body' => (object) array('value' => "Comment 2")
        ))->result;
        $comment1dot1 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityComment.Body' => (object) array('value' => "Comment 1.1"),
            'CommunityComment.Parent' => (object) array('value' => $comment1->ID)
        ))->result;
        $comment1dot2 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityComment.Body' => (object) array('value' => "Comment 1.2"),
            'CommunityComment.Parent' => (object) array('value' => $comment1->ID)
        ))->result;
        $comment1dot1dot1 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityComment.Body' => (object) array('value' => "Comment 1.1.1"),
            'CommunityComment.Parent' => (object) array('value' => $comment1dot1->ID)
        ))->result;

        $comments = $this->assertResponseObject($this->model->getComments($question))->result;

        $this->assertEqual(5, count($comments), "Question should have 5 comments but has " . count($comments));

        // we should get the comments back depth-first (e.g. display order)
        $this->assertEqual($comment1->ID, $comments[0]->ID, "Wrong comment in position 0 - found {$comments[0]->Body}");
        $this->assertEqual($comment1dot1->ID, $comments[1]->ID, "Wrong comment in position 1 - found {$comments[1]->Body}");
        $this->assertEqual($comment1dot1dot1->ID, $comments[2]->ID, "Wrong comment in position 2 - found {$comments[2]->Body}");
        $this->assertEqual($comment1dot2->ID, $comments[3]->ID, "Wrong comment in position 3 - found {$comments[3]->Body}");
        $this->assertEqual($comment2->ID, $comments[4]->ID, "Wrong comment in position 4 - found {$comments[4]->Body}");

        // clean up
        foreach (array($comment1dot1dot1, $comment1dot1, $comment1dot2, $comment1, $comment2, $question) as $deleteMe)
            $this->destroyObject($deleteMe);
    }

    function testGetCommentsWithPagination() {
        $this->logIn();

        // create a question with three comments
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__),
            'CommunityQuestion.Product' => (object) array('value' => 1),
        )))->result;
        $comments = array();
        for ($i=0; $i<3; $i++) {
            $comments[] = $this->CI->model('CommunityComment')->create(array(
                'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
                'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
            ))->result;
        }

        // get all comments one "page" at a time using a page size of 1
        for ($i=0; $i<3; $i++) {
            $pageOfComments = $this->assertResponseObject($this->model->getComments($question, 1, $i+1))->result;
            $this->assertEqual(1, count($pageOfComments), "Page should have 1 comment(s)");
            $this->assertEqual($comments[$i]->ID, $pageOfComments[0]->ID, "Wrong comment on page " . $i+1);
        }

        // get all of the comments using a page size of 10 (greater than the number of comments)
        $pageOfComments = $this->assertResponseObject($this->model->getComments($question, 10, 1))->result;
        $this->assertEqual(3, count($pageOfComments), "Question should have 3 comment(s)");
        $this->assertEqual($comments[0]->ID, $pageOfComments[0]->ID, "Wrong comment on page 1 in position 0");
        $this->assertEqual($comments[1]->ID, $pageOfComments[1]->ID, "Wrong comment on page 1 in position 1");
        $this->assertEqual($comments[2]->ID, $pageOfComments[2]->ID, "Wrong comment on page 1 in position 2");

        // no results if we request a page that doesn't exist (page 2 with page size 10)
        $pageOfComments = $this->assertResponseObject($this->model->getComments($question, 10, 2))->result;
        $this->assertEqual(0, count($pageOfComments), "Question should have 3 comment(s)");

        // clean up
        foreach (array_merge($comments, array($question)) as $deleteMe)
            $this->destroyObject($deleteMe);
    }

    function testGetCommentsDoesNotReturnPendingChildComments() {
        $this->logIn('useractive1');

        // create a question with an active parent comment and pending child comment
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__),
            'CommunityQuestion.Product' => (object) array('value' => 1),
        )))->result;
        $parentComment = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        ))->result;
        $childComment = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Pending"),
            'CommunityComment.Parent' => (object) array('value' => $parentComment->ID),
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID)
        ))->result;

        // create a question with a pending parent comment and pending child comment
        $question2 = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__),
            'CommunityQuestion.Product' => (object) array('value' => 1),
        )))->result;
        $parentComment2 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Pending"),
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question2->ID)
        ))->result;
        $childComment2 = $this->CI->model('CommunityComment')->create(array(
            'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => "Pending"),
            'CommunityComment.Parent' => (object) array('value' => $parentComment2->ID),
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question2->ID)
        ))->result;

        // author should see all pending comments
        $response = $this->assertResponseObject($this->model->getComments($question));
        $this->assertSame(2, count($response->result), "getComments should not return pending parentComments");

        $response = $this->assertResponseObject($this->model->getComments($question2));
        $this->assertSame(2, count($response->result), "getComments should not return pending parentComments");

        // log in as a different user since authors can see their pending comments
        $this->logOut();
        $this->logIn('useractive2');

        // user should only see active comment
        $response = $this->assertResponseObject($this->model->getComments($question));
        $this->assertSame(1, count($response->result), "getComments should not return pending parentComments");

        // user should not see anything, since they are both pending
        $response = $this->assertResponseObject($this->model->getComments($question2));
        $this->assertSame(0, count($response->result), "getComments should not return pending parentComments");

        // clean up
        foreach (array($childComment, $parentComment, $question, $childComment2, $parentComment2, $question2) as $deleteMe)
            $this->destroyObject($deleteMe);
    }

    function testAddCommentToCache() {
        $mockTabularComment = (object) array(
            'ID' => '1',
            'LookupName' => '1',
            'CreatedTime' => '2013-11-14T15:49:36Z',
            'UpdatedTime' => '2013-11-14T15:49:36Z',
            'CreatedByCommunityUser' => (object) array(
                'ID' => '1',
                'DisplayName' => 'Jim',
                'AvatarURL' => NULL,
            ),
            'Body' => 'Jim&amp;#x27;s comment',
            'BodyContentType' => '2',
            'Parent' => (object) array(),
            'CommunityQuestion' => (object) array('ID' => '1'),
            'Status' => '1',
            'Type' => '1',
        );

        $addCommentToCache = $this->getMethod('addCommentToCache');
        $this->assertTrue($addCommentToCache($mockTabularComment));

        $cacheResults = Framework::checkCache("CommunityComment_{$mockTabularComment->ID}");

        $this->assertNotNull($cacheResults);
        $this->assertIsA($cacheResults, 'stdClass');
        $this->assertSame($cacheResults->ID, $mockTabularComment->ID);
        $this->assertSame($cacheResults->Body, $mockTabularComment->Body);

        unset($mockTabularComment->ID);
        $this->assertFalse($addCommentToCache($mockTabularComment));
    }

    function testGetTabular() {
        // calling with an invalid ID should return null
        $this->assertNull($this->CI->model('CommunityQuestion')->getTabular(-1)->result);

        // create a question
        $this->logIn();
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'Shibby'),
        )))->result;

        // Retrieve the tabular question
        $tabularQuestion = $this->assertResponseObject($this->CI->model('CommunityQuestion')->getTabular($question->ID))->result;

        $this->assertEqual($question->ID, $tabularQuestion->ID);
        $this->assertEqual($question->Body, $tabularQuestion->Body);
        $this->assertEqual($question->CreatedByCommunityUser->ID, $tabularQuestion->CreatedByCommunityUser->ID);
        $this->assertEqual($question->Product->ID, $tabularQuestion->Product->ID);

        // repeated calls should return identical results, even when we force a cache miss
        $cachedQuestion = $this->CI->model('CommunityQuestion')->getTabular($question->ID)->result;
        $this->assertIdentical($tabularQuestion, $cachedQuestion);

        Framework::removeCache("CommunityQuestion_{$question->ID}");
        $missedCacheQuestion = $this->CI->model('CommunityQuestion')->getTabular($question->ID)->result;
        $this->assertIdentical($tabularQuestion, $missedCacheQuestion);

        // clean up
        foreach (array($question) as $deleteMe)
            $this->destroyObject($deleteMe);
    }

    function testGetMappedSocialObjectStatuses() {
        //test - if valid social question object statuses are returned
        $statuses = $this->model->getMappedSocialObjectStatuses()->result;
        $this->assertTrue(count($statuses) > 0, 'Empty status type');
        $this->assertTrue(is_array($statuses), 'Result is not an array');
        $this->assertTrue(is_array($statuses[key($statuses)]), 'Status is not an array');

        //test - fetch statuses for given status type
        $statuses = $this->model->getMappedSocialObjectStatuses(STATUS_TYPE_SSS_QUESTION_ACTIVE)->result;
        $this->assertTrue(isset($statuses[STATUS_TYPE_SSS_QUESTION_ACTIVE]), 'Failed to fecth statuses for given status type');

        //test - if method return status when object name passed as an argument
        $statuses = $this->model->getMappedSocialObjectStatuses(STATUS_TYPE_SSS_QUESTION_PENDING, "CommunityQuestionSts")->result;
        $this->assertTrue(isset($statuses[STATUS_TYPE_SSS_QUESTION_PENDING]), 'Failed to fecth statuses when object name is passed');

        $statuses = $this->model->getMappedSocialObjectStatuses(STATUS_TYPE_SSS_COMMENT_DELETED, "CommunityCommentSts")->result;
        $this->assertTrue(isset($statuses[STATUS_TYPE_SSS_COMMENT_DELETED]), 'Failed to fecth statuses when comment object name is passed');
    }

    function testIsModerateActionAllowed() {

        //test - allowed to moderate without login
        $this->logOut();
        $response = $this->model->isModerateActionAllowed();
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        //test -  allowed to moderate if user is  not logged-in as moderator
        $this->logIn('useractive1');
        $response = $this->model->isModerateActionAllowed();
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        //test -  allowed to moderate if user is logged-in as moderator
        $this->logIn('modactive1');
        $newSocialUser = $this->CI->model('CommunityUser')->get()->result;
        $response = $this->model->isModerateActionAllowed();
        $this->assertSame(true, $response);
        $this->logOut();

        //test - allowed to moderate if moderator is is not active (i.e deleted or suspended)
        $this->logIn('modsuspended');
        $newSocialUser = $this->CI->model('CommunityUser')->get()->result;
        $response = $this->model->isModerateActionAllowed();
        $this->assertSame(1, count($response->errors));
        $this->logOut();

        //test - contentmoderator do not have permission to moderate users
        $this->logIn('contentmoderator');
        $response = $this->model->isModerateActionAllowed(true);
        $this->assertNotNull($response->errors, 'There should be an error');
        $this->assertSame(1, count($response->errors));
        $this->logOut();

        //test - usermoderator has permission to moderate users
        $this->logIn('usermoderator');
        $this->assertEqual(true, $this->model->isModerateActionAllowed(true));
    }

    function testIsValidSocialObjectToModerate() {

        $this->logIn();
        //test - invalid object id
        $response = $this->model->isValidSocialObjectToModerate(0, 'CommunityQuestion');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        //test - update valid active object
        $question = $this->model->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas')
        ))->result;
        $response = $this->model->isValidSocialObjectToModerate($question->ID, 'CommunityQuestion');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNotNull($response->result);

        //test - update valid but deleted object
        $question = $this->model->update($question->ID, array('CommunityQuestion.StatusWithType.Status.ID' => (object) array('value' => key($this->model->getMappedSocialObjectStatuses(STATUS_TYPE_SSS_QUESTION_DELETED)->result[STATUS_TYPE_SSS_QUESTION_DELETED]))));
        $response = $this->model->isValidSocialObjectToModerate($question->ID, 'CommunityQuestion');
        $this->assertSame(1, count($response->errors));

        $this->logOut();
        //test permission - valid object id
        $this->logIn('useractive1');
        $question = $this->model->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas 2')
        ))->result;
        $this->logOut();

        $this->logIn('usermoderator');
        $response = $this->model->isValidSocialObjectToModerate($question->ID, 'CommunityQuestion');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertEqual($response->errors[0]->externalMessage, 'User does not have permission to edit this question.', 'Error message must be same');

    }

    function testGetFlagTypes () {
        $flagTypes = $this->model->getFlagTypes();
        $this->assertTrue(is_array($flagTypes), 'Flag types should be an array');
        $this->assertTrue(count($flagTypes) > 0, 'Flag types should not be empty');
        $this->assertEqual(key($flagTypes[key($flagTypes)]), 'ID', 'Flag types should be an array');
        $this->assertNotNull($flagTypes[key($flagTypes)]['ID'], 'Flag ID should not be null');
    }

    function xtestResetSocialContentFlags() {
        // JVSWSPMHACK???
        // don't run this function to see if SPMs with flags is causing an error
        // (e.g. Internal Error - Pair Error Pair Chain: Description: The sss_question_content_flags
        // record does not exist for foreign key in pair spm_queue_create=>context=>context_item = 811)
        $this->logIn('useractive1');
        $question1 = $this->model->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'question title 1'),
        ))->result;
        $question2 = $this->model->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'question title 1'),
        ))->result;

        // flag the questions using MAPI so as not to preload the question permission cache
        $this->logOut();
        $this->logIn('useractive2');
        foreach (array($question1, $question2) as $question) {
            $flag = new Connect\CommunityQuestionFlg();
            $flag->CommunityQuestion = $question->ID;
            $flag->CreatedByCommunityUser = $this->CI->model('CommunityUser')->get()->result->ID;
            $flag->Type = 1;
            $flag->save();
        }

        //Normal user should not be able to reset flags
        $this->logOut();
        $this->logIn('useractive1');
        $response = $this->model->resetSocialContentFlags(array($question1->ID), 'CommunityQuestion');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));
        $this->logOut();

        //Moderator should be allowed to reset flags
        $this->logIn('slatest');
        $response = $this->model->resetSocialContentFlags(array($question2->ID), 'CommunityQuestion');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors));

        //test - invalid call
        $response = $this->model->resetSocialContentFlags(array($question2->ID), 'SomeInvalidObjectName');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        //Question without flags
        list($fixtureInstance, $questionActiveModActive) = $this->getFixtures(array(
            'QuestionActiveModActive'
        ));
        $response = $this->model->resetSocialContentFlags(array($questionActiveModActive->ID), 'CommunityQuestion');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertEqual("Does not have flags", $response->errors[0]->externalMessage, "Message should be same");
        $fixtureInstance->destroy();

        //Questions with flags and with permission
        list($fixtureInstance3, $questionActiveSingleComment2) = $this->getFixtures(array(
            'QuestionActiveSingleComment'
        ));
        $response = $this->model->resetSocialContentFlags(array($questionActiveSingleComment2->ID), 'CommunityQuestion');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertEqual(1, $response->result, "Should be 1");
        $fixtureInstance3->destroy();

        //Questions with flags and no permission
        $this->logOut();
        list($fixtureInstance2, $questionActiveSingleComment) = $this->getFixtures(array(
            'QuestionActiveSingleComment'
        ));
        $response = $this->model->resetSocialContentFlags(array($questionActiveSingleComment->ID), 'CommunityQuestion');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertEqual("User does not have permission to reset flags", $response->errors[0]->externalMessage, "Message should be same");
        $fixtureInstance2->destroy();

    }

    function testUpdateModeratorAction() {
        $this->logIn('useractive2');
        $question = $this->model->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'question title 1'),
        ))->result;
        $this->logOut();

        //suspend this question
        $this->logIn('slatest');
        $flag = $this->model->flagQuestion($question->ID);
        $response = $this->model->updateModeratorAction($question->ID, array('CommunityQuestion.StatusWithType.Status.ID' => (object) array('value' => key($this->model->getMappedSocialObjectStatuses(STATUS_TYPE_SSS_QUESTION_SUSPENDED)->result[STATUS_TYPE_SSS_QUESTION_SUSPENDED]))));
        $this->assertResponseObject($response);
        Connect\ConnectAPI::commit();

        //flag should not be removed
        $this->assertResponseObject($this->model->getUserFlag($question->ID));

        //restore this question
        $response = $this->model->updateModeratorAction($question->ID, array('CommunityQuestion.StatusWithType.Status.ID' => (object) array('value' => key($this->model->getMappedSocialObjectStatuses(STATUS_TYPE_SSS_QUESTION_ACTIVE)->result[STATUS_TYPE_SSS_QUESTION_ACTIVE]))));
        $this->assertResponseObject($response);

        //flag should be removed
        $flag = $this->model->getUserFlag($question->ID);
        $this->assertNull($flag->result, "Flag should be removed on restore action");

        //move this question to different product with user as slatest
        $response = $this->model->updateModeratorAction($question->ID, array('CommunityQuestion.Product' => (object) array('value' => 1 )));
        $this->assertResponseObject($response);
        $this->logOut();

        //move this question to different product with user as useractive1
        $this->logIn('useractive1');
        $response = $this->model->updateModeratorAction($question->ID, array('CommunityQuestion.Product' => (object) array('value' => 1 )));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));
        $this->logOut();
    }

    function testAddProdCatFilterROQL() {
        $addProdCatFilterROQL = $this->getMethod('addProdCatFilterROQL');

        $result = $addProdCatFilterROQL('Product', 1, false, 'q');
        $this->assertEqual(' AND q.Product.ID in (1)', $result);

        $result = $addProdCatFilterROQL('Product', 1, true, 'qu');
        $this->assertEqual(' AND qu.Product.ID in (1,2,3,4,8,9,10,159,160)', $result);

        $result = $addProdCatFilterROQL('Product', 4, true, 'que');
        $this->assertEqual(' AND que.Product.ID in (4,159,160)', $result);

        $result = $addProdCatFilterROQL('Category', 153, false, 'quest');
        $this->assertEqual(' AND quest.Category.ID in (153)', $result);

        $result = $addProdCatFilterROQL('Category', 122, true, 'questi');
        $this->assertNull($result, 'Filter string returned when it should be blank');
    }

    function getCommentStatus($comment) {
        if ($comment) {
            return $this->model->getSocialObjectStatuses($comment->StatusWithType->StatusType->ID, 'CommunityCommentSts')->result[0]->LookupName;
        }
    }

    function testSuspendedParentCommentWithActiveChildren() {
        $questionData = array(
            array(
                'body' => 'comment 1',
                'status' => 'active'
            ),
            array(
                'body' => 'comment 2',
                'status' => 'suspended',
                'children' => array(
                    array('body' => 'comment 2a', 'status' => 'active'),
                    array('body' => 'comment 2b', 'status' => 'suspended'),
                ),
            ),
        );

        $this->logIn('useractive1');
        $newQuestion = $this->createQuestion('question1 ' . __FUNCTION__, $questionData);

        // Non-Privileged user sees suspended top-level comment but not suspended child
        $this->logIn('useractive2');
        $question = $this->model->get($newQuestion->ID)->result;
        $comments = $this->model->getComments($question)->result;
        $this->assertIsA($comments, 'array');
        $this->assertEqual(3, count($comments));

        $this->assertEqual('comment 1', $comments[0]->Body);
        $this->assertEqual('comment 2', $comments[1]->Body);
        $this->assertEqual('Suspended', $this->getCommentStatus($comments[1]));
        $this->assertEqual('comment 2a', $comments[2]->Body);

        $this->destroyQuestion($newQuestion);

        // Privileged user sees all comments
        $newQuestion = $this->createQuestion('question2 ' . __FUNCTION__, $questionData);
        $this->logIn('modactive1');
        $question = $this->model->get($newQuestion->ID)->result;
        $comments = $this->model->getComments($question)->result;
        $this->assertIsA($comments, 'array');
        $this->assertEqual(4, count($comments));
        $this->assertEqual('comment 1', $comments[0]->Body);
        $this->assertEqual('comment 2', $comments[1]->Body);
        $this->assertEqual('comment 2a', $comments[2]->Body);
        $this->assertEqual('comment 2b', $comments[3]->Body);

        $this->destroyQuestion($newQuestion);
        $this->logOut();
    }

    function testSuspendedParentCommentWithNoActiveChildren() {
        $questionData = array(
            array(
                'body' => 'comment 1',
                'status' => 'active',
            ),
            array(
                'body' => 'comment 2',
                'status' => 'suspended',
                'children' => array(
                    array('body' => 'comment 2a', 'status' => 'suspended'),
                    array('body' => 'comment 2b', 'status' => 'suspended'),
                ),
            ),
        );

        $this->logIn('useractive1');
        $newQuestion = $this->createQuestion('question1 ' . __FUNCTION__, $questionData);

        // Non-Privileged user sees only active top-level comment
        $this->logIn('useractive2');
        $question = $this->model->get($newQuestion->ID)->result;
        $comments = $this->model->getComments($question)->result;
        $this->assertIsA($comments, 'array');
        $this->assertEqual(2, count($comments));
        $this->assertEqual('comment 1', $comments[0]->Body);
        $this->assertEqual('Active', $this->getCommentStatus($comments[0]));

        $this->destroyQuestion($newQuestion);

        // Privileged user sees all comments
        $newQuestion = $this->createQuestion('question2 ' . __FUNCTION__, $questionData);
        $this->logIn('modactive1');
        $question = $this->model->get($newQuestion->ID)->result;
        $comments = $this->model->getComments($question)->result;
        $this->assertIsA($comments, 'array');
        $this->assertEqual(4, count($comments));
        $this->assertEqual('comment 1', $comments[0]->Body);
        $this->assertEqual('comment 2', $comments[1]->Body);
        $this->assertEqual('comment 2a', $comments[2]->Body);
        $this->assertEqual('comment 2b', $comments[3]->Body);

        $this->destroyQuestion($newQuestion);
        $this->logOut();
    }

    function testSuspendedParentCommentWithNoChildren() {
        $questionData = array(
            array(
                'body' => 'comment 1',
                'status' => 'active',
            ),
            array(
                'body' => 'comment 2',
                'status' => 'suspended',
            ),
        );

        $this->logIn('useractive1');
        $newQuestion = $this->createQuestion('question1 ' . __FUNCTION__, $questionData);

        // Non-Privileged user sees only active top-level comment
        $this->logIn('useractive2');
        $question = $this->model->get($newQuestion->ID)->result;
        $comments = $this->model->getComments($question)->result;
        $this->assertIsA($comments, 'array');
        $this->assertEqual(2, count($comments));
        $this->assertEqual('comment 1', $comments[0]->Body);
        $this->assertEqual('Active', $this->getCommentStatus($comments[0]));

        $this->destroyQuestion($newQuestion);

        // Privileged user sees all comments
        $newQuestion = $this->createQuestion('question2 ' . __FUNCTION__, $questionData);
        $this->logIn('modactive1');
        $question = $this->model->get($newQuestion->ID)->result;
        $comments = $this->model->getComments($question)->result;
        $this->assertIsA($comments, 'array');
        $this->assertEqual(2, count($comments));
        $this->assertEqual('comment 1', $comments[0]->Body);
        $this->assertEqual('comment 2', $comments[1]->Body);

        $this->destroyQuestion($newQuestion);
        $this->logOut();
    }

    function testSuspendedChildCommentsDoNotDisplay() {
        $questionData = array(
            array(
                'body' => 'comment 1',
                'status' => 'active',
                'children' => array(
                    array('body' => 'comment 1a', 'status' => 'suspended'),
                ),
            ),
        );

        $this->logIn('useractive1');
        $newQuestion = $this->createQuestion('question1 ' . __FUNCTION__, $questionData);

        // Non-Privileged user sees only active top-level comment
        $this->logIn('useractive2');
        $question = $this->model->get($newQuestion->ID)->result;
        $comments = $this->model->getComments($question)->result;
        $this->assertIsA($comments, 'array');
        $this->assertEqual(1, count($comments));
        $this->assertEqual('comment 1', $comments[0]->Body);
        $this->assertEqual('Active', $this->getCommentStatus($comments[0]));

        $this->destroyQuestion($newQuestion);

        // Privileged user sees all comments
        $newQuestion = $this->createQuestion('question2 ' . __FUNCTION__, $questionData);
        $this->logIn('modactive1');
        $question = $this->model->get($newQuestion->ID)->result;
        $comments = $this->model->getComments($question)->result;
        $this->assertIsA($comments, 'array');
        $this->assertEqual(2, count($comments));
        $this->assertEqual('comment 1', $comments[0]->Body);
        $this->assertEqual('comment 1a', $comments[1]->Body);

        $this->destroyQuestion($newQuestion);
        $this->logOut();
    }

    function testShouldIncludeComment() {
        $method = $this->getMethod('shouldIncludeComment');

        // Log in as moderator so Comment model retrieves the proper comment.
        $this->logIn('modactive1');
        // Comment is suspended.
        $comment = $this->CI->model('CommunityComment')->get(426)->result;
        $this->logIn('useractive1');
        $this->assertFalse($method($comment));

        $this->logIn('modactive1');
        // Comment is suspended, but moderator can access it.
        $comment = $this->CI->model('CommunityComment')->get(1007)->result;
        $this->assertTrue($method($comment));

        // Comment is pending, but moderator can access it.
        $comment = $this->CI->model('CommunityComment')->get(181)->result;
        $this->assertTrue($method($comment));

        // Comment is pending, but author can access it.
        $this->logIn('useractive1');
        $comment = $this->CI->model('CommunityComment')->get(157)->result;
        $this->assertTrue($method($comment));

        $this->logOut();
    }

    function testEmailToFriend() {
        $this->logIn('useractive1');
        // create a new question to act as newest question for product.
        $suspendedQuestion = $this->model->create(array(
                'CommunityQuestion.Product' => (object) array('value' => 6),
                'CommunityQuestion.Subject' => (object) array('value' => 'test question ' . __FUNCTION__),
                'CommunityQuestion.Body' => (object) array('value' => 'I am the question'),
                'CommunityQuestion.StatusWithType.Status.ID' => (object) array('value' => 30),
            ))->result;

        $deletedQuestion = $this->model->create(array(
                'CommunityQuestion.Product' => (object) array('value' => 6),
                'CommunityQuestion.Subject' => (object) array('value' => 'test question ' . __FUNCTION__),
                'CommunityQuestion.Body' => (object) array('value' => 'I am the question'),
                'CommunityQuestion.StatusWithType.Status.ID' => (object) array('value' => 31),
            ))->result;

        // invalid to address
        $return = $this->model->emailToFriend('b.b.invalid', 5);
        $this->assertIsA($return, '\RightNow\Libraries\ResponseObject');
        $this->assertFalse($return->result);

        // invalid question id
        $return = $this->model->emailToFriend('b.b.invalid@invalid.invalid', null);
        $this->assertIsA($return, '\RightNow\Libraries\ResponseObject');
        $this->assertFalse($return->result);
        $this->assertIdentical($return->errors[0]->externalMessage, "Invalid question ID");

        // inactive question
        $return = $this->model->emailToFriend('b.b.invalid@invalid.invalid', $suspendedQuestion->ID);
        $this->assertIsA($return, '\RightNow\Libraries\ResponseObject');
        $this->assertFalse($return->result);
        $this->assertSame(1, count($return->errors));
        $this->assertIdentical($return->errors[0]->externalMessage, "User does not have read permission on this question");

        // deleted question
        $return = $this->model->emailToFriend('b.b.invalid@invalid.invalid', $deletedQuestion->ID);
        $this->assertIsA($return, '\RightNow\Libraries\ResponseObject');
        $this->assertFalse($return->result);
        $this->assertSame(1, count($return->errors));
        $this->assertIdentical($return->errors[0]->externalMessage, "Cannot find question, which may have been deleted by another user.");

        // valid address with spaces and question id, hence should return a successful response
        $return = $this->model->emailToFriend('     b.b.invalid@invalid.invalid    ', 5);
        $this->assertIsA($return, '\RightNow\Libraries\ResponseObject');
        $this->assertTrue($return->result);

        $this->destroyQuestion($suspendedQuestion);
        $this->destroyQuestion($deletedQuestion);
        $this->logOut();
    }

    function testTouch() {
        $this->logIn('useractive1');
        list($fixtureInstance, $question) = $this->getFixtures(array(
            'QuestionActiveModActive',
        ));

        $creationTime = $question->LastActivityTime;

        $touchObject = $this->model->touch($question, time() + 100);
        $touchObjectTime = $touchObject->result->LastActivityTime;
        $this->assertTrue(count($touchObject->errors) < 1);
        $this->assertTrue($touchObjectTime > $creationTime);

        $touchID = $this->model->touch($question->ID, time() + 200);
        $touchIDTime = $touchID->result->LastActivityTime;
        $this->assertTrue(count($touchID->errors) < 1);
        $this->assertTrue($touchIDTime > $touchObjectTime);

        $fixtureInstance->destroy();
        $this->logOut();
    }

    function testGetQuestionSubject() {
        $response = $this->model->getQuestionSubject(2055);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');

        $this->assertTrue(is_array($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertSame(1, count($response->result));

        $question = $response->result[2055];
        $this->assertSame('2055', $question['ID']);
        $this->assertIsA($question['Subject'], 'string');

        $response = $this->model->getQuestionSubject('2055');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');

        $this->assertTrue(is_array($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertSame(1, count($response->result));

        $question = $response->result[2055];
        $this->assertSame('2055', $question['ID']);
        $this->assertIsA($question['Subject'], 'string');

        $response = $this->model->getQuestionSubject(array(2058, 2056, 2059));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');

        $this->assertTrue(is_array($response->result));
        $this->assertIdentical(3, count($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $firstQuestion = $response->result['2058'];
        $this->assertSame('2058', $firstQuestion['ID']);
        $this->assertIsA($firstQuestion['Subject'], 'string');
        $secondQuestion = $response->result['2056'];
        $this->assertSame('2056', $secondQuestion['ID']);
        $this->assertIsA($secondQuestion['Subject'], 'string');
        $thirdQuestion = $response->result['2059'];
        $this->assertSame('2059', $thirdQuestion['ID']);
        $this->assertIsA($thirdQuestion['Subject'], 'string');

        $response = $this->model->getQuestionSubject(array(2007, 2058));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertIdentical(1, count($response->result));
        $firstQuestion = $response->result['2058'];
        $this->assertSame('2058', $firstQuestion['ID']);
        $this->assertIsA($firstQuestion['Subject'], 'string');

        //Error conditions
        $response = $this->model->getQuestionSubject(array());
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->getQuestionSubject('abc');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->getQuestionSubject(-1);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->getQuestionSubject(999);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertIdentical(0, count($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
    }

    // code under test not ready yet
    function xtestContentLastUpdatedTime() {
        $socialUser = $this->CI->model('CommunityUser')->get()->result;

        $x = new Connect\CommunityQuestion();
        $x->Subject = 'shibby';
        $x->ContentLastUpdatedByCommunityUser = $socialUser;
        $x->ContentLastUpdatedTime = 1000000000;
        $x->save();
        $this->do_dump($x);

        $y = Connect\CommunityQuestion::fetch($x->ID);
        $this->assertEqual(1000000000, $y->ContentLastUpdatedTime);
        $this->do_dump($y);

        $y->ContentLastUpdatedByCommunityUser = $socialUser;
        $y->ContentLastUpdatedTime = 1300000000;
        $y->save();

        $z = Connect\CommunityQuestion::fetch($x->ID);
        $this->assertEqual(1300000000, $y->ContentLastUpdatedTime);
        $this->do_dump($z);
return;
        // create a new question
        $this->logIn();
        $currentUser = $this->CI->model('CommunityUser')->get()->result;
        $question = $this->model->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        $this->assertNotNull($question->ContentLastUpdatedTime, "ContentLastUpdatedTime should have been set on create");

        // create a CommunityUser so we can set ContentLastUpdatedTime to a different value
        $yesterday = strtotime(date('d.m.Y',strtotime("-1 days")));
        $this->do_dump($yesterday);
        $socialUser = $this->createSocialUser();
        $question = $this->assertResponseObject($this->model->update($question->ID, array(
            'CommunityQuestion.ContentLastUpdatedTime' => (object) array('value' => $yesterday),
        )))->result;
        // $this->assertEqual($yesterday, $question->ContentLastUpdatedTime, "Wrong ContentLastUpdatedTime");
        $this->assertEqual($yesterday, $question->ContentLastUpdatedTime);

        // only changes to Subject and Body should cause ContentLastUpdatedTime to update - these fields shouldn't
        $question = $this->assertResponseObject($this->model->update($question->ID, array(
            'CommunityQuestion.BodyContentType' => (object) array('value' => 2), // 2 = Markdown
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.StatusWithType.Status' => (object) array('value' => 29),
        )))->result;
        $this->assertEqual($yesterday, $question->ContentLastUpdatedTime);

        // change Subject and ensure ContentLastUpdatedTime changes
        $question = $this->assertResponseObject($this->model->update($question->ID, array(
            'CommunityQuestion.Subject' => (object) array('value' => 'shibby'),
        )))->result;
        $this->assertNotEqual($yesterday, $question->ContentLastUpdatedTime);

        // change the user back so we can test another field
        $question = $this->assertResponseObject($this->model->update($question->ID, array(
            'CommunityQuestion.ContentLastUpdatedTime' => (object) array('value' => $yesterday),
        )))->result;
        $this->assertEqual($yesterday, $question->ContentLastUpdatedTime, "Wrong ContentLastUpdatedTime");

        // change Body and ensure ContentLastUpdatedTime changes
        $question = $this->assertResponseObject($this->model->update($question->ID, array(
            'CommunityQuestion.Body' => (object) array('value' => 'shibby'),
        )))->result;
        $this->assertNotEqual($yesterday, $question->ContentLastUpdatedTime);

        $this->destroyObject($question);
    }

    // code under test not ready yet
    function xtestContentLastUpdatedBySocialUser() {
        // create a new question
        $this->logIn();
        $currentUser = $this->CI->model('CommunityUser')->get()->result;
        $question = $this->model->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        $this->assertNotNull($question->ContentLastUpdatedByCommunityUser, "ContentLastUpdatedByCommunityUser should have been set on create");

        // create a CommunityUser so we can set ContentLastUpdatedByCommunityUser to a different value
        $socialUser = $this->createSocialUser();
echo "currentUser: {$currentUser->ID}<br/>";
echo "socialUser: {$socialUser->ID}<br/>";
        $question = $this->assertResponseObject($this->model->update($question->ID, array(
            'CommunityQuestion.ContentLastUpdatedByCommunityUser' => (object) array('value' => $socialUser->ID),
        )))->result;
        $this->assertEqual($socialUser->ID, $question->ContentLastUpdatedByCommunityUser->ID, "Wrong ContentLastUpdatedByCommunityUser");

        // only changes to Subject and Body should cause ContentLastUpdatedByCommunityUser to update - these fields shouldn't
        $question = $this->assertResponseObject($this->model->update($question->ID, array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.BodyContentType' => (object) array('value' => 2), // 2 = Markdown
            'CommunityQuestion.StatusWithType.Status' => (object) array('value' => 29),
        )))->result;
        $this->assertEqual($socialUser->ID, $question->ContentLastUpdatedByCommunityUser->ID);

        // change Subject and ensure ContentLastUpdatedByCommunityUser changes
        $question = $this->assertResponseObject($this->model->update($question->ID, array(
            'CommunityQuestion.Subject' => (object) array('value' => 'shibby'),
        )))->result;
        $this->assertEqual($currentUser->ID, $question->ContentLastUpdatedByCommunityUser->ID);

        // change the user back so we can test another field
        $question = $this->assertResponseObject($this->model->update($question->ID, array(
            'CommunityQuestion.ContentLastUpdatedByCommunityUser' => (object) array('value' => $socialUser->ID),
        )))->result;
        $this->assertEqual($socialUser->ID, $question->ContentLastUpdatedByCommunityUser->ID, "Wrong ContentLastUpdatedByCommunityUser");

        // change Body and ensure ContentLastUpdatedByCommunityUser changes
        $question = $this->assertResponseObject($this->model->update($question->ID, array(
            'CommunityQuestion.Body' => (object) array('value' => 'shibby'),
        )))->result;
        // $this->assertEqual($currentUser->ID, $question->ContentLastUpdatedByCommunityUser->ID, "Wrong ContentLastUpdatedByCommunityUser");
        $this->assertEqual($currentUser->ID, $question->ContentLastUpdatedByCommunityUser->ID);

        $this->destroyObject($question);
    }

    function testUserPermissions() {
        $this->_testUserPermissionsOnModel(array(array()), $this->model, 'create');
        $this->_testUserPermissionsOnModel(array($question1->ID, array()), $this->model, 'update');
        $this->_testUserPermissionsOnModel(array(0), $this->model, 'markCommentAsBestAnswer');
        $this->_testUserPermissionsOnModel(array(0), $this->model, 'unmarkCommentAsBestAnswer');
        $this->_testUserPermissionsOnModel(array(0), $this->model, 'flagQuestion');
        $this->_testUserPermissionsOnModel(array(0, 1), $this->model, 'rateQuestion');
        $this->_testUserPermissionsOnModel(array('invaliduser@invalid.oracle.com', 0), $this->model, 'emailToFriend');
    }

    function testCreateWithSA() {
        $this->logIn();
        $question = $this->model->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'iPhone ' . __FUNCTION__),
            'CommunityQuestion.Body' => (object) array('value' => 'iphone'),
            ), true);
        $this->assertIsA($question->result, 'array');
        // sometimes SA results include social questions, sometimes they don't
        // 150521-000035
        if (count($question->result['suggestions']) === 2) {
            $this->assertIdentical(2, count($question->result['suggestions']));
            $this->assertIdentical('AnswerSummary', $question->result['suggestions'][0]['type']);
            $this->assertIdentical('QuestionSummary', $question->result['suggestions'][1]['type']);
        }
        else {
            $this->assertIdentical(1, count($question->result['suggestions']));
            $this->assertIdentical('AnswerSummary', $question->result['suggestions'][0]['type']);
        }
    }

    function testGetPrevNextQuestionID() {
        // log in as an active user
        $this->logIn('useractive1');

        // create a new question to act as newest question for product.
        $newestProductQuestion = $this->model->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 6),
            'CommunityQuestion.Subject' => (object) array('value' => 'test question ' . __FUNCTION__),
            'CommunityQuestion.Body' => (object) array('value' => 'I am the latest question'),
        ))->result;

        // get prev, next, oldest and newest for question
        $results = $this->model->getPrevNextQuestionID(51, 6, 'product', array(
                'prevQuestion' => true,
            'nextQuestion' => true,
            'oldestNewestQuestion' => true
            ))->result;

        $this->assertEqual(3, $results['oldestQuestion'], "Oldest question is " . $results['oldestQuestion']);
        $this->assertEqual($newestProductQuestion->ID, $results['newestQuestion'], "Newest question is " . $results['newestQuestion']);
        $this->assertEqual(48, $results['prevQuestion'], "Previous question is " . $results['prevQuestion']);
        $this->assertEqual(54, $results['nextQuestion'], "Next question is " . $results['nextQuestion']);

        // question is first one in its product
        $results = $this->model->getPrevNextQuestionID(3, 6, 'product', array('prevQuestion' => true, 'oldestNewestQuestion' => true))->result;

        $this->assertEqual(3, $results['oldestQuestion'], "I am not the oldest question anymore." . $results['oldestQuestion']);
        $this->assertNull($results['prevQuestion'], "I am not the first question anymore" . $results['prevQuestion']);

        // question is last one in its product
        $results = $this->model->getPrevNextQuestionID($newestProductQuestion->ID, 6, 'product', array('nextQuestion' => true, 'oldestNewestQuestion' => true))->result;

        $this->assertEqual($newestProductQuestion->ID, $results['newestQuestion'], "I am not the newest question anymore. I got " . $results['newestQuestion']);
        $this->assertNull($results['nextQuestion'], "I am not the last question anymore. I got ". $results['nextQuestion']);

        // get null for no params
        $results = $this->model->getPrevNextQuestionID(3, 6, 'product', null)->result;
        $this->assertNull($results, "Results for no parameters is not null");

        // get null for not a valid question object
        $question = null;
        $results = $this->model->getPrevNextQuestionID($question, 6, 'product', array('prevQuestion' => true))->result;
        $this->assertNull($results, "Results for no question is not null");

        // question does not belong to any product or category
        $results = $this->model->getPrevNextQuestionID(91, null, 'product', array('prevQuestion' => true))->result;
        $this->assertNull($results, "Results for question belonging to no product is not null");

        $this->destroyQuestion($newestProductQuestion);
        $this->logOut();
    }

    function testGetCommentsWithRatingAndFlags(){
	$mzoeller = $this->CI->model('Contact')->get(1285)->result;
        $this->logIn($mzoeller->Login);
        $response = $this->model->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ));
        $question = $response->result;
        $comment = $this->createActiveComment($question);
        $this->logOut();
        $this->logIn();
        $this->assertResponseObject($this->CI->Model('CommunityComment')->rateComment($comment, 50, 100))->result;
        $this->assertResponseObject($this->CI->Model('CommunityComment')->flagComment($comment))->result;
        $comments = $this->model->getComments($question)->result;
        $this->assertEqual(50, $comments[0]->RatingValue);
        $this->assertEqual(100, $comments[0]->RatingWeight);
        $this->assertEqual(1, $comments[0]->FlagType);
        $this->destroyQuestion($question);
        $this->logOut();
    }

    private function createActiveComment($question) {
        $comment = new Connect\CommunityComment();
        $comment->CommunityQuestion = $question->ID;
        $comment->StatusWithType->Status->ID = 33; // active
        $comment->CreatedByCommunityUser = $this->CI->model('CommunityUser')->get()->result;
        $comment->save();
        return $comment;
    }

    function testGetQuestionCountByProductCategory() {
        $this->logIn();

        $response = $this->assertResponseObject($this->model->getQuestionCountByProductCategory('Product', array(1), array(question_count, last_activity), 5, "question_count"));
        $this->assertTrue($response->result['questionCount'][1] > 0, "Count of questions under the product: Mobile Phones");
        $count = $response->result['questionCount'][1];

        // create a question under the product: Mobile Phone
        $question1 = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'Distance to the sun ' . __FUNCTION__),
            'CommunityQuestion.StatusWithType.Status.LookupName' => (object) array('value' => "Active"),
            'CommunityQuestion.Body' => (object) array('value' => 'How far is it from the Earth to the sun?'),
            'CommunityQuestion.Product' => (object) array('value' => 1),
        )))->result;

        //Verify that the count has increased
        $secondResponse = $this->assertResponseObject($this->model->getQuestionCountByProductCategory('Product', array(1), array(question_count, last_activity), 5, "question_count"));

        $this->assertTrue($secondResponse->result['questionCount'][1] > 0, "Count of questions under the product: Mobile Phones");
        $this->assertEqual($count + 1, $secondResponse->result['questionCount'][1]);

        $this->destroyObject($question1);
        $this->logOut();
    }

    function testGetCommentCountByQuestion() {
        $this->logIn();

        $response = $this->assertResponseObject($this->model->getCommentCountByQuestion('Product', 1, array(), array(comment_count, last_activity), 5, "comment_count"));
        $firstQuestion = reset($response->result);
        $this->assertTrue($firstQuestion['comment_count'] > 0, "Count of comments for the first question under the product: Mobile Phones");
        $count = $firstQuestion['comment_count'];

        // create a question under the product: Mobile Phone
        $comment = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $firstQuestion['id']),
            'CommunityComment.Body' => (object) array('value' => "2 miles")
        )))->result;

        //Verify that the count has increased
        $secondResponse = $this->assertResponseObject($this->model->getCommentCountByQuestion('Product', 1, array(), array(comment_count, last_activity), 5, "comment_count"));
        $firstQuestion = reset($secondResponse->result);
        $this->assertTrue($firstQuestion['comment_count'] > 0, "Count of comments for the first question under the product: Mobile Phones");
        $this->assertEqual($count + 1, $firstQuestion['comment_count']);

        $this->destroyObject($comment);
        $this->logOut();
    }

    function testCreateAttachmentEntry(){
        $createAttachmentEntry = $this->getMethod('createAttachmentEntry');

        $mockQuestion = $this->model->getBlank()->result;

        $createAttachmentEntry($mockQuestion, null);
        $this->assertNull($mockQuestion->FileAttachments);

        $createAttachmentEntry($mockQuestion, array());
        $this->assertNull($mockQuestion->FileAttachments);

        $createAttachmentEntry($mockQuestion, array('newFiles' => array((object)array('localName' => 'tempNameDoesntMatter', 'contentType' => 'image/sheen', 'userName' => 'reinactedScenesFromPlatoon.jpg'))));
        $this->assertIsA($mockQuestion->FileAttachments, CONNECT_NAMESPACE_PREFIX . '\FileAttachmentCommunityArray');
        $this->assertIdentical(0, count($mockQuestion->FileAttachments));

        file_put_contents(\RightNow\Api::fattach_full_path('winning'), 'test data');

        $createAttachmentEntry($mockQuestion, array((object)array('localName' => 'winning', 'contentType' => 'image/sheen', 'userName' => 'tigersBlood.jpg')));
        $this->assertIsA($mockQuestion->FileAttachments, CONNECT_NAMESPACE_PREFIX . '\FileAttachmentCommunityArray');
        $this->assertIsA($mockQuestion->FileAttachments[0], CONNECT_NAMESPACE_PREFIX . '\FileAttachmentCommunity');
        $this->assertIdentical('image/sheen', $mockQuestion->FileAttachments[0]->ContentType);
        $this->assertIdentical('tigersBlood.jpg', $mockQuestion->FileAttachments[0]->FileName);

        unlink(\RightNow\Api::fattach_full_path('winning'));
    }
    
    function testGetTotalQuestionCount() {
        $getTotalCount = $this->getMethod('getTotalQuestionCount');

        $mockQuestion = $this->model->getBlank()->result;

        $count = $getTotalCount($mockQuestion, null);

        $this->assertTrue($count >= 0, "Count of Questions is less than 0");
    }
}
