<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Connect\v1_4 as Connect,
    RightNow\UnitTest\Helper as TestHelper,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Utils\Text,
    RightNow\UnitTest\Fixture as Fixture;

TestHelper::loadTestedFile(__FILE__);

class CommunityCommentTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\CommunityComment';

    // preconfigured moderator contact for testing
    private $mzoeller;
    // preconfigured regular contact for testing
    private $nzhang;

    /**
     * MUT (model under test): CommunityComment
     *
     * @var RightNow\Models\CommunityComment
     */
    private $model;

    function __construct() {
        parent::__construct();
        $this->model = new RightNow\Models\CommunityComment();
        get_instance()->model('CommunityComment');
        $this->fixtureInstance = new Fixture();
        $this->mzoeller = $this->CI->model('Contact')->get(1285)->result;
        $this->nzhang = $this->CI->model('Contact')->get(1284)->result;
    }

    function testGetBlank() {
        $response = $this->model->getBlank();

        // check the validation
        $this->assertEqual(0, count($response->errors), 'Response object had errors: [' . implode(',', $response->errors) .']');
        $this->assertEqual(0, count($response->warnings), 'Response object had warnings: [' . implode(',', $response->warnings) .']');

        // check the blank object
        $comment = $response->result;
        $this->assertNotNull($comment, "Null Comment returned");
        $this->assertTrue($comment instanceof Connect\CommunityComment, "Should have a Connect\CommunityComment object");

        $this->assertTrue(empty($comment->ID), "Blank Comment should have an empty ID (null or zero)");
    }

    function testGet(){
        $this->logIn('useractive1');
        $question = $this->assertResponseObject($this->CI->Model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        )))->result;
        $activeComment = $this->assertResponseObject($this->model->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
        )))->result;
        $pendingComment1 = $this->assertResponseObject($this->model->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.StatusWithType.Status.ID' => (object) array('value' => 36), // Pending
        )))->result;
        $pendingComment2 = $this->assertResponseObject($this->model->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.StatusWithType.Status.ID' => (object) array('value' => 36), // Pending
        )))->result;

        // check for the decorator
        $this->assertIsA($activeComment->SocialPermissions, 'RightNow\Decorators\SocialCommentPermissions');

        // author can read non-active comments
        $this->assertResponseObject($this->model->get($pendingComment1->ID));

        // non-author can't read non-active comments
        $this->logOut();
        $this->logIn('useractive2');
        // TODO on hold pending Jeremy's input
        // $this->assertResponseObject($this->model->get($pendingComment1->ID), 'is_null', 1, 0);

        // Can read nonactive with permissions
        $this->logIn('useradmin');
        $this->assertResponseObject($this->model->get($pendingComment2->ID));

        // clean up; commit the deletions since we logged out
        foreach (array($pendingComment1, $pendingComment2, $activeComment, $question) as $object)
            $this->destroyObject($object);
        Connect\ConnectAPI::commit();

        // check for error if comment is deleted
        list($fixtureInstance, $questionActive, $commentDeleted) = $this->getFixtures(array('QuestionActiveAllBestAnswer', 'CommentDeletedModActive'));
        $response = $this->model->get($commentDeleted->ID);
        $this->assertSame(1, count($response->errors), print_r($response->errors, true));
        $fixtureInstance->destroy();
    }

    function testCreate() {
        // can't create a comment with no input
        $response = $this->model->create(array());
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        // comments need questions
        $this->logIn();
        $question = $this->CI->Model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;

        // now that we have a question, we can create a comment
        // with the minimum required fields
        $response = $this->model->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors), print_r($response->errors, true));
        $this->assertSame(0, count($response->warnings), print_r($response->warnings, true));
        $this->assertIdentical('Active', $response->result->StatusWithType->Status->LookupName);
        $this->assertEqual($this->CI->model('CommunityUser')->get()->result->ID, $response->result->CreatedByCommunityUser->ID, "Author not set correctly");
        $this->assertIsA($response->result->SocialPermissions, 'RightNow\Decorators\SocialCommentPermissions');

        $this->destroyObject($response->result);

        // create a comment with every field
        $response = $this->model->create(array(
            'CommunityComment.Body' => (object) array('value' => 'shibby'),
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors), print_r($response->errors, true));
        $this->assertSame(0, count($response->warnings), print_r($response->warnings, true));
        $this->assertTrue($response->result instanceof Connect\CommunityComment, "Should have gotten a CommunityComment back");
        $this->assertIdentical('shibby', $response->result->Body);
        $this->assertEqual($this->CI->model('CommunityUser')->get()->result->ID, $response->result->CreatedByCommunityUser->ID, "Author not set correctly");
        $this->assertIsA($response->result->SocialPermissions, 'RightNow\Decorators\SocialCommentPermissions');

        $this->destroyObject($response->result);
        $this->destroyObject($question);

        $this->logout();
    }

    function testUpdate() {
        // comments need questions
        $this->logIn();
        $question = $this->CI->Model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        $comment = $this->model->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.Body' => (object) array('value' => 'shibby'),
        ))->result;
        $this->logOut();

        // can't update a comment unless you are logged in
        $response = $this->model->update($comment->ID, array());
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        $this->logIn();
        // can't update a comment without providing an ID
        $response = $this->model->update('asdf', array());
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        // we should be able to update if we do everything right
        $newBody = "extra fox";
        $response = $this->assertResponseObject($this->model->update($comment->ID, array(
            'CommunityComment.Body' => (object) array('value' => $newBody),
        )));
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\CommunityComment');
        $this->assertIdentical($newBody, $response->result->Body);

        // now that we have checked the object returned by the update method, re-fetch to be sure the changes took
        $response = Connect\CommunityComment::fetch($comment->ID);
        $this->assertEqual($newBody, $response->Body);

        // now show that other users (e.g. not the author) cannot update the comment
        // SMC TODO This section won't work properly until permissions are fully implemented
        // $this->logOut();
        // $prof = $this->logIn('jerry@indigenous.example.com.invalid.070503.invalid');

        // $response = $this->assertResponseObject($this->model->update($comment->ID, array(
        //     'CommunityComment.Body' => (object) array('value' => 'alert!  unauthorized edit!'),
        // )));
        // $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\CommunityComment');
        // $this->assertIdentical($newBody, $response->result->Body);

        // $this->destroyObject($response->result);
        // clean up - since we logged in as a different user we must commit the deletes
        $this->destroyObject($question);
        Connect\ConnectAPI::commit();
    }

    function testRegularUserCanDeleteOwnComment() {
        $this->logIn($this->nzhang->Login);
        $question = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        $comment = $this->model->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.Body' => (object) array('value' => 'shibby'),
        ))->result;

        // we should be able to update/delete the comment
        $comment = $this->assertResponseObject($this->model->update($comment->ID, array(
            'CommunityComment.StatusWithType.Status.ID' => (object) array('value' => 35), // 35 is Deleted
        )))->result;
        $this->assertEqual(STATUS_TYPE_SSS_COMMENT_DELETED, $comment->StatusWithType->StatusType->ID);

        // clean up
        $this->destroyObject($question);
    }

    function testSetBodyContentTypeWithAllTypes() {
        // retrieve all the possible types
        $contentTypes = ConnectUtil::getNamedValues('CommunityComment', 'BodyContentType');
        $this->assertTrue(count($contentTypes) > 0, "Need at least one content type");

        // create a comment for each type and flag with that type
        $this->logIn();
        $question = $this->CI->Model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;

        // try setting the content type by ID
        foreach ($contentTypes as $contentType) {
            $comment = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
                'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
                'CommunityComment.BodyContentType' => (object) array('value' => $contentType->ID)
            )))->result;
            $this->assertIdentical($contentType->ID, $comment->BodyContentType->ID);

            $this->destroyObject($comment);
        }

        // try setting the content type by LookupName
        foreach ($contentTypes as $contentType) {
            $comment = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
                'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
                'CommunityComment.BodyContentType.LookupName' => (object) array('value' => $contentType->LookupName)
            )))->result;
            $this->assertIdentical($contentType->LookupName, $comment->BodyContentType->LookupName);

            $this->destroyObject($comment);
        }

        $this->destroyObject($question);
    }

    function testGetSocialObjectStatuses() {
        // get a list of all the statuses and status types
        $statuses = array();
        $statusTypes = array();
        $results = Connect\ROQL::query("SELECT qs.ID, qs.LookupName, qs.StatusType FROM CommunityCommentSts qs")->next();
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
        $statuses = $this->model->getStatusesFromStatusType(STATUS_TYPE_SSS_COMMENT_DELETED)->result;
        $this->assertTrue(in_array(35, $statuses),"Should return deleted status id ");
        $statuses = $this->model->getStatusesFromStatusType(STATUS_TYPE_SSS_COMMENT_SUSPENDED)->result;
        $this->assertTrue(in_array(34, $statuses),"Should return suspended status id ");
        $statuses = $this->model->getStatusesFromStatusType(STATUS_TYPE_SSS_COMMENT_PENDING)->result;
        $this->assertTrue(in_array(36, $statuses),"Should return pending status id");
    }

    function testSetStatusWithTypeForAllTypes() {
        // retrieve all the possible types
        $statuses = ConnectUtil::getNamedValues('CommunityComment', 'StatusWithType.Status');
        $this->assertTrue(count($statuses) > 0, "Need at least one status");

        // create a question to hold our comments
        $this->logIn();
        $question = $this->CI->Model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;

        // try setting the status by ID
        foreach ($statuses as $status) {
            $comment = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
                'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
                'CommunityComment.StatusWithType.Status.ID' => (object) array('value' => $status->ID)
            )))->result;
            $this->assertIdentical($status->ID, $comment->StatusWithType->Status->ID);

            $this->destroyObject($comment);
        }

        // try setting the status by LookupName
        foreach ($statuses as $status) {
            $comment = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
                'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
                'CommunityComment.StatusWithType.Status.LookupName' => (object) array('value' => $status->LookupName)
            )))->result;
            $this->assertIdentical($status->LookupName, $comment->StatusWithType->Status->LookupName);

            $this->destroyObject($comment);
        }

        $this->destroyObject($question);
    }

    function testNestedComments() {
        // comments need questions
        $this->logIn();
        $question = $this->CI->Model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;

        // create some comments to work with, wired up like $comment1->$comment2->$comment3
        $comment1 = $this->model->create(array(
            'CommunityComment.Body' => (object) array('value' => "comment1"),
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
        ))->result;
        $comment2 = $this->model->create(array(
            'CommunityComment.Body' => (object) array('value' => "comment2"),
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.Parent' => (object) array('value' => $comment1->ID),
        ))->result;
        $comment3 = $this->model->create(array(
            'CommunityComment.Body' => (object) array('value' => "comment3"),
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.Parent' => (object) array('value' => $comment2->ID),
        ))->result;

        // each comment should have a list of its ancestors
        $this->assertSame(0, count($comment1->CommunityCommentHierarchy), 'Wrong number of parent comments for $comment1');
        $this->assertSame(1, count($comment2->CommunityCommentHierarchy), 'Wrong number of parent comments for $comment2');
        $this->assertEqual($comment1->ID, $comment2->CommunityCommentHierarchy[0]->ID, 'Wrong parent comment for $comment2');
        $this->assertSame(2, count($comment3->CommunityCommentHierarchy), 'Wrong number of parent comments for $comment3');
        $this->assertEqual($comment1->ID, $comment3->CommunityCommentHierarchy[0]->ID, 'Wrong grandparent comment for $comment3');
        $this->assertEqual($comment2->ID, $comment3->CommunityCommentHierarchy[1]->ID, 'Wrong parent comment for $comment3');

        // clean up
        foreach (array($comment3, $comment2, $comment1, $question) as $object)
            $this->destroyObject($object);
    }

    /*function testFlagComment() {
        // need to be logged in to flag a comment
        $response = $this->model->flagComment(null);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertIdentical($response->errors[0]->externalMessage, "User is not logged in");

        $this->logIn('useractive1');

        // send in a null comment
        $response = $this->model->flagComment(null);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertIdentical($response->errors[0]->externalMessage, "Invalid Comment");

        // send in an invalid comment
        $response = $this->model->flagComment((object)array(1,2,3));
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertIdentical($response->errors[0]->externalMessage, "Invalid Comment");

        // now create a comment and ensure it has no flags
        $question = $this->CI->Model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        $comment1 = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.Body' => (object) array('value' => 'bananas')
        )))->result;
        $response = $this->model->getUserFlag($comment1->ID);
        $this->assertNull($response->result);

        // author cannot flag comment
        $response = $this->model->flagComment($comment1);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertIdentical($response->errors[0]->externalMessage, "User does not have permission to flag a comment");

        // flag the comment as a different user
        // use MAPI to create the comment so it won't be decorated - we need to be logged in
        // as a different user when the decorator is instantiated
        $comment2 = $this->createActiveComment($question);
        $this->logOut();
        $this->logIn('useractive2');
        $response = $this->assertResponseObject($this->model->flagComment($comment2->ID));
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\CommunityCommentFlg');
        $flag = $response->result;

        $this->assertEqual($this->CI->model('CommunityUser')->get()->result->ID, $flag->CreatedByCommunityUser->ID);
        $this->assertEqual($comment2->ID, $flag->CommunityComment->ID);

        // ensure we can see the flag and that it matches the one we have
        $response = $this->assertResponseObject($this->model->getUserFlag($comment2->ID));
        $this->assertEqual($flag->ID, $response->result->ID, "Wrong flag returned");
        $this->assertEqual($this->CI->model('CommunityUser')->get()->result->ID, $flag->CreatedByCommunityUser->ID);

        // clean up; flags are deleted automatically when their comment is deleted
        $this->destroyObject($comment1);
        $this->destroyObject($comment2);
        $this->destroyObject($question);
    }

    function testFlagCommentWithAllTypes() {
        // retrieve all the possible types
        $flagTypes = ConnectUtil::getNamedValues('CommunityCommentFlg', 'Type');
        $this->assertTrue(count($flagTypes) > 0, "Need at least one flag type");

        // create a comment for each type and flag with that type
        $this->logIn();
        $question = $this->CI->Model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        foreach ($flagTypes as $flagType) {

            // the author cannot flag the comment
            $this->logIn('useractive1');
            $comment = $this->createActiveComment($question);
            $this->logOut();
            $this->logIn('useractive2');

            $this->assertResponseObject($this->model->flagComment($comment, $flagType->ID));
            // $this->model->flagComment($comment, $flagType->ID);

            // ensure we can see the flag and that the author and type are correct
            $response = $this->assertResponseObject($this->model->getUserFlag($comment->ID));
            $this->assertIdentical($this->CI->model('CommunityUser')->get()->result->ID, $response->result->CreatedByCommunityUser->ID);
            $this->assertIdentical($flagType->ID, $response->result->Type->ID);

            // clean up
            $this->destroyObject($comment);
        }

        $this->destroyObject($question);
    }

    function testFlaggingWithDeletedParent() {
        list($fixtureInstance, $question, $childComment) = $this->getFixtures(array(
            'QuestionActiveParentCommentDeleted',
            'CommentToUseWithDeletedParent',
        ));
        $this->logIn();

        $commentFlagging = $this->model->flagComment($childComment, FLAG_INAPPROPRIATE);
        $this->assertNull($commentFlagging->result);
        $this->assertIdentical('Invalid Comment', $commentFlagging->errors[0]->externalMessage);

        $this->logOut();
        $fixtureInstance->destroy();
    }

    function testRateComment() {
        $ratingValue = 50;

        // log in as a user besides the default to create comment
        $this->logIn($this->mzoeller->Login);

        // now create a comment and ensure it has no ratings
        $question = $this->CI->Model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        $comment = $this->createActiveComment($question);

        // log in the default user, in which to rate shibby's comment
        $this->logIn();
        $response = $this->model->getUserRating($comment);
        $this->assertNull($response->result);

        // rate the comment
        $rating = $this->assertResponseObject($this->model->rateComment($comment, $ratingValue))->result;
        $this->assertIsA($rating, CONNECT_NAMESPACE_PREFIX . '\CommunityCommentRtg');
        $this->assertEqual($this->CI->model('CommunityUser')->get()->result->Contact->ID, $rating->CreatedByCommunityUser->Contact->ID);
        $this->assertEqual($ratingValue, $rating->RatingValue);

        // ensure we can see the rating and that the author and comment are correct
        $response = $this->assertResponseObject($this->model->getUserRating($comment));
        $this->assertEqual(1, count($response->result), "Wrong number of ratings - should have one");
        $this->assertEqual($rating->ID, $response->result->ID, "Wrong rating returned");

        // clean up - since we logged in as a different user we must commit the deletes
        $this->destroyObject($question);
        Connect\ConnectAPI::commit();
    }

    /* 190712-000090 Commenting out since the test is silently failing and cause 
       approximately 800 FL Unit Tests to not execute
    function testResetCommentRating () {
        $ratingValue = 50;

        // log in as a user besides the UserActive1 to create comment
        $moderator = $this->fixtureInstance->make('UserModActive');
        $this->logIn($moderator->Contact->Login);

        // now create a comment and ensure it has no ratings
        list($fixtureInstance, $question, $comment) = $this->getFixtures(array(
            'QuestionActiveModActive',
            'CommentActiveModActive',
        ));

        $this->logOut();

        // log in as UserActive1, in which to rate shibby's comment
        $userActive = $this->fixtureInstance->make('UserActive1');
        $this->logIn($userActive->Contact->Login);

        // rate the comment
        $rating = $this->assertResponseObject($this->model->rateComment($comment, $ratingValue))->result;
        $this->assertIsA($rating, CONNECT_NAMESPACE_PREFIX . '\CommunityCommentRtg');
        $this->assertEqual($this->CI->model('CommunityUser')->get()->result, $rating->CreatedByCommunityUser);
        $this->assertEqual($ratingValue, $rating->RatingValue);

        // reset the rating on comment
        $this->assertNotNull($this->model->resetCommentRating($comment))->result;

        // ensure that the rating is reset
        $response = $this->assertNull($this->model->getUserRating($comment)->result);

        // clean up - since we logged in as a different user we must commit the deletes
        $this->destroyObject($question);
        Connect\ConnectAPI::commit();
    }
    */

    function testRateCommentValidation() {
        // need to be logged in to rate a comment
        $response = $this->model->rateComment(null, 50);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertIdentical($response->errors[0]->externalMessage, "User is not logged in");

        $this->logIn('useractive1');

        // send in a null comment
        $response = $this->model->rateComment(null, 50);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertIdentical($response->errors[0]->externalMessage, "Invalid Comment");

        // send in an invalid comment
        $response = $this->model->rateComment((object)array(1,2,3), 50);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertIdentical($response->errors[0]->externalMessage, "Invalid Comment");

        // create comments to rate
        $question = $this->CI->Model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        $comment1 = $this->CI->Model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
        ))->result;
        $comment2 = $this->createActiveComment($question);

        // Rate comment as author should throw an error
        $comment1 = $this->model->get($comment1->ID)->result;
        $response = $this->model->rateComment($comment1->ID, 1);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertFalse(strpos($response->errors[0]->externalMessage, "Cannot vote on own comment") === false, print_r($response->errors, true));

        //Login as another user
        $this->logOut();
        $this->logIn($this->mzoeller->Login);
        // rating values must be > 0 and <= 100
        $response = $this->model->rateComment($comment2, 0);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertFalse(strpos($response->errors[0]->externalMessage, "Minimum value exceeded") === false, print_r($response->errors, true));

        // similar to flags, new ratings should overwrite the old without errors
        $this->assertResponseObject($this->model->rateComment($comment2, 50, 100));

        //User has already rated the comment
        $response = $this->model->rateComment($comment2, 101);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertFalse(strpos($response->errors[0]->externalMessage, "User has rated on the content") === false, print_r($response->errors, true));

        //User has already rated the comment
        $response = $this->model->rateComment($comment2, 50, 0);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertFalse(strpos($response->errors[0]->externalMessage, "User has rated on the content") === false, print_r($response->errors, true));

        //User has already rated the comment
        $response = $this->model->rateComment($comment2, 50, 501);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertFalse(strpos($response->errors[0]->externalMessage, "User has rated on the content") === false, print_r($response->errors, true));

        //Reset the rating on comment2
        $this->model->resetCommentRating($comment2);

        // rating values must be > 0 and <= 100
        $response = $this->model->rateComment($comment2, 0);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertFalse(strpos($response->errors[0]->externalMessage, "Minimum value exceeded") === false, print_r($response->errors, true));

        $response = $this->model->rateComment($comment2, 101);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertFalse(strpos($response->errors[0]->externalMessage, "Maximum value exceeded") === false, print_r($response->errors, true));

        // rating weights must be > 0 and <= 500
        $response = $this->model->rateComment($comment2, 50, 0);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertFalse(strpos($response->errors[0]->externalMessage, "Minimum value exceeded") === false, print_r($response->errors, true));

        $response = $this->model->rateComment($comment2, 50, 501);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertFalse(strpos($response->errors[0]->externalMessage, "Maximum value exceeded") === false, print_r($response->errors, true));

        // rating should succeed with 1 more than the min values
        $rating = $this->assertResponseObject($this->model->rateComment($comment2, 1, 1))->result;

        //Reset the rating on comment2
        $this->model->resetCommentRating($comment2);

        // rating should succeed with 1 less than the max value
        $this->assertResponseObject($this->model->rateComment($comment2, 100, 500))->result;

        // clean up; ratings are deleted automatically when their comment is deleted
        $this->destroyObject($comment1);
        $this->destroyObject($comment2);
        $this->destroyObject($question);

        $this->logOut();
    }

    function testRatingWithDeletedParent() {
        list($fixtureInstance, $question, $childComment) = $this->getFixtures(array(
            'QuestionActiveParentCommentDeleted',
            'CommentToUseWithDeletedParent',
        ));
        $this->logIn();

        $commentRating = $this->model->rateComment($childComment, 100, 100);
        $this->assertNull($commentRating->result);
        $this->assertIdentical('Invalid Comment', $commentRating->errors[0]->externalMessage);

        $this->logOut();
        $fixtureInstance->destroy();
    }

    function testGetUserRating() {
        // must be logged in in order to get a rating
        $response = $this->model->getUserRating(5);
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) > 0);
        $this->assertIdentical($response->errors[0]->externalMessage, "User is not logged in");

        // create a question w/ comment as one user
        $this->logIn($this->mzoeller->Login);
        $question = $this->CI->Model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        $comment = $this->createActiveComment($question);

        // rate the comment as a different user
        $this->logOut();
        $this->logIn();
        $rating = $this->assertResponseObject($this->model->rateComment($comment, 1, 1))->result;

        // if no user is passed, defaults to the current user
        $currentUser = $this->CI->model('CommunityUser')->get()->result;
        $rating = $this->assertResponseObject($this->model->getUserRating($comment))->result;
        $this->assertEqual($currentUser->ID, $rating->CreatedByCommunityUser->ID);

        // get rating for the current user
        $rating = $this->assertResponseObject($this->model->getUserRating($comment, $currentUser))->result;
        $this->assertEqual($currentUser->ID, $rating->CreatedByCommunityUser->ID);

        // a different user will not have a rating
        $differentUser = $this->CI->Model("CommunityUser")->getForContact($this->mzoeller->ID)->result;
        $rating = $this->model->getUserRating($comment, $differentUser)->result;
        $this->assertNull($rating);

        // clean up; ratings are deleted automatically when their question is deleted
        $this->destroyObject($comment);
        $this->destroyObject($question);
        $this->logOut();
    }

    function testGetTabular() {
        // calling with an invalid ID should return null
        $this->assertNull($this->CI->model('CommunityComment')->getTabular(-1)->result);

        $tabularComment = $this->CI->model('CommunityComment')->getTabular(41);
        $this->assertResponseObject($tabularComment);
        $this->assertIsA($tabularComment->result->SocialPermissions, 'RightNow\Decorators\SocialCommentPermissions');

        list($fixtureInstance, $question, $suspendedComment) = $this->getFixtures(array('QuestionActiveAllBestAnswer', 'CommentSuspendedModActive'));

        // Users cannot read deleted comments
        $this->logIn('useractive1');
        $question = $this->CI->Model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        $deletedComment = $this->assertResponseObject($this->model->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.StatusWithType.Status.ID' => (object) array('value' => 35), // Deleted
        )))->result;
        $tabularComment = $this->CI->model('CommunityComment')->getTabular($deletedComment->ID);
        $this->assertResponseObject($tabularComment, 'is_null');

        // Author can read pending comment
        $pendingComment1 = $this->assertResponseObject($this->model->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.StatusWithType.Status.ID' => (object) array('value' => 36), // Pending
        )))->result;
        $tabularComment = $this->CI->model('CommunityComment')->getTabular($pendingComment1->ID);
        $this->assertResponseObject($tabularComment);

        // non-author cannot read pending comment (need a new one since the old one is cached)
        $pendingComment2 = $this->assertResponseObject($this->model->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.StatusWithType.Status.ID' => (object) array('value' => 36), // Pending
        )))->result;

        $tabularComment = $this->CI->model('CommunityComment')->getTabular($suspendedComment->ID, false);
        $this->assertResponseObject($tabularComment);
        $keys = get_object_vars($tabularComment);
        $this->assertFalse(array_key_exists("flagType", $keys));
        $this->assertFalse(array_key_exists("RatingValue", $keys));

        $tabularComment = $this->CI->model('CommunityComment')->getTabular($suspendedComment->ID);
        $this->assertResponseObject($tabularComment, 'is_null');

        $this->logOut();
        $this->logIn('useractive2');
        $tabularComment = $this->CI->model('CommunityComment')->getTabular($pendingComment2->ID);
        $this->assertResponseObject($tabularComment, 'is_null');

        // clean up - since we logged in as a different user we must commit the deletes
        foreach (array($deletedComment, $pendingComment1, $pendingComment2, $question) as $deleteMe)
            $this->destroyObject($deleteMe);
        $fixtureInstance->destroy();
        Connect\ConnectAPI::commit();
    }

    function testCheckCachedTabularQuestions() {
        // create a question w/ comment as one user
        $this->logIn($this->mzoeller->Login);
        $question = $this->CI->Model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        $comment = $this->createActiveComment($question);

        // rate the comment as a different user
        $this->logOut();
        $this->logIn();
        $rating = $this->assertResponseObject($this->model->rateComment($comment, 50, 100))->result;

        // Comments are cached in getComments
        $comments = $this->assertResponseObject($this->CI->model('CommunityQuestion')->getComments($question))->result;

        // Retrieve cached comment
        $cachedComment = $this->CI->model('CommunityComment')->getTabular($comments[0]->ID)->result;

        $this->assertEqual($comment->ID, $cachedComment->ID);
        $this->assertEqual($comment->Body, $cachedComment->Body);
        $this->assertEqual($comment->CommunityQuestion->ID, $cachedComment->CommunityQuestion->ID);

        \RightNow\Utils\Framework::removeCache("CommunityComment_{$comment->ID}");
        $missedCachedComment = $this->CI->model('CommunityComment')->getTabular($comments[0]->ID)->result;

        $this->assertEqual($comment->ID, $cachedComment->ID);
        $this->assertEqual($comment->Body, $cachedComment->Body);
        $this->assertEqual($comment->CommunityQuestion->ID, $cachedComment->CommunityQuestion->ID);

        // clean up - since we logged in as a different user we must commit the deletes
        foreach (array($comment, $question) as $deleteMe)
            $this->destroyObject($deleteMe);
        Connect\ConnectAPI::commit();
    }

    function testGetFromList() {
        $this->logIn();
        $user = $this->CI->Model('CommunityUser')->get()->result;

        $question = $this->CI->Model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        $comment1 = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.Body' => (object) array('value' => 'bananas one')
        )))->result;
        $comment2 = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.Body' => (object) array('value' => 'bananas two')
        )))->result;
        Connect\ConnectAPI::commit();

        $result = $this->CI->model('CommunityComment')->getFromList(array(
            $comment1->ID,
            $comment2->ID,
        ))->result;

        $this->assertNotNull($result[$comment1->ID]);
        $this->assertNotNull($result[$comment2->ID]);
        $this->assertEqual($question->ID, $result[$comment1->ID]->CommunityQuestion->ID);
        $this->assertEqual($question->ID, $result[$comment2->ID]->CommunityQuestion->ID);
        $this->assertEqual($user->ID, $result[$comment1->ID]->CreatedByCommunityUser->ID);
        $this->assertEqual($user->ID, $result[$comment2->ID]->CreatedByCommunityUser->ID);
        $this->assertEqual(33, $result[$comment1->ID]->StatusWithType->Status->ID);
        $this->assertEqual(33, $result[$comment2->ID]->StatusWithType->Status->ID);
        $this->assertEqual(26, $result[$comment1->ID]->StatusWithType->StatusType->ID);
        $this->assertEqual(26, $result[$comment2->ID]->StatusWithType->StatusType->ID);

        $result = $this->CI->model('CommunityComment')->getFromList(array('hello', array()));
        $this->assertNull($result->result);
        $this->assertEqual(1, count($result->errors));
        $this->assertEqual('Comment IDs must be integers.', $result->errors[0]->externalMessage);

        // clean up - since we logged in as a different user we must commit the deletes
        $this->destroyObject($question);
        Connect\ConnectAPI::commit();
    }

    function testIsValidSocialObjectToModerate() {
        $this->logIn('slatest');

        // invalid object id
        $response = $this->model->isValidSocialObjectToModerate(0, 'CommunityComment');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        list($fixtureInstance, $question, $parentComment, $childComment, $deletedComment) = $this->getFixtures(array(
            'QuestionActiveModActive',
            'CommentWithRepliesUserArchive',
            'CommentActiveModArchive',
            'CommentDeletedModActive',
        ));
        $this->assertTrue($this->model->isValidSocialObjectToModerate($childComment->ID, 'CommunityComment')->result);
        $response = $this->model->isValidSocialObjectToModerate($deletedComment->ID, 'CommunityComment');
        $this->assertSame(1, count($response->errors));

        //test permission
        $this->logOut();
        $this->logIn('usermodertor');
        $this->assertEqual("User does not have permission to edit this comment.", $this->model->isValidSocialObjectToModerate($parentComment->ID, 'CommunityComment')->errors[0]->externalMessage, 'Error message must be same');
        $fixtureInstance->destroy();
    }

    function testGetFlagTypes () {
        $flagTypes = $this->model->getFlagTypes();
        $this->assertTrue(is_array($flagTypes), 'Flag types should be an array');
        $this->assertTrue(count($flagTypes) > 0, 'Flag types should not be empty');
        $this->assertEqual(key($flagTypes[key($flagTypes)]), 'ID', 'Flag types should be an array');
        $this->assertNotNull($flagTypes[key($flagTypes)]['ID'], 'Flag ID should not be null');
    }

    function testResetSocialContentFlags() {
        $this->logIn("useractive1");
        //reset flags for few comment
        //test - update valid active object
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'orange')
        )))->result;
        $comment = $this->createActiveComment($question);
        $this->logOut();
        $this->logIn('useractive2');
        $this->model->flagComment($comment);
        $this->logOut();

        //Normal user should not be able to reset flags
        $this->logIn('useractive1');
        $response = $this->model->resetSocialContentFlags(array($comment->ID), 'CommunityComment');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));
        $this->logOut();

        $this->logIn("useractive1");
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'orange')
        )))->result;
        $this->logOut();

        //User moderator should not be allowed to reset flags
        $this->logIn('usermoderator');
        $response = $this->model->resetSocialContentFlags(array($comment->ID), 'CommunityComment');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame('User does not have permission to reset flags', $response->errors[0]->externalMessage);
        $this->logOut();

        $this->logIn("useractive1");
        $comment = $this->createActiveComment($question);
        $this->logOut();
        $this->logIn('useractive2');
        $this->model->flagComment($comment);
        $this->logOut();

        //Moderator should be allowed to reset flags
        $this->logIn('modactive1');
        $response = $this->model->resetSocialContentFlags(array($comment->ID), 'CommunityComment');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors));
    }

    function testUpdateModeratorAction() {
        $this->logIn("useractive1");
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'orange')
        )))->result;
        $comment = $this->assertResponseObject($this->model->create(array(
            'CommunityComment.Body' => (object) array('value' => 'comment for orange'),
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
        )))->result;
        $this->logOut();

        //suspend this comment
        $this->logIn('slatest');
        $flag = $this->model->flagComment($comment->ID);
        $response = $this->model->updateModeratorAction($comment->ID, array('CommunityComment.StatusWithType.Status.ID' => (object) array('value' => key($this->model->getMappedSocialObjectStatuses(STATUS_TYPE_SSS_COMMENT_SUSPENDED)->result[STATUS_TYPE_SSS_COMMENT_SUSPENDED]))));
        $this->assertResponseObject($response);

        //flag should not be removed
        $this->assertResponseObject($this->model->getUserFlag($comment->ID));

        //restore this comment
        $response = $this->model->updateModeratorAction($comment->ID, array('CommunityComment.StatusWithType.Status.ID' => (object) array('value' => key($this->model->getMappedSocialObjectStatuses(STATUS_TYPE_SSS_COMMENT_ACTIVE)->result[STATUS_TYPE_SSS_COMMENT_ACTIVE]))));
        $this->assertResponseObject($response);

        //flag should be removed
        $flag = $this->model->getUserFlag($comment->ID);
        $this->assertNull($flag->result, "Flag should be removed on restore action");
        $this->logOut();
    }

    function testGetIndexOfTopLevelComment() {
        $this->assertSame(-1, $this->model->getIndexOfTopLevelComment('low'));
        $this->assertSame(-1, $this->model->getIndexOfTopLevelComment(null));
        $this->assertSame(-1, $this->model->getIndexOfTopLevelComment(PHP_INT_MAX));

        // Three active, top-level comments on the same question
        $this->assertSame(0, $this->model->getIndexOfTopLevelComment(1289));
        $this->assertSame(1, $this->model->getIndexOfTopLevelComment(1292));
        $this->assertSame(2, $this->model->getIndexOfTopLevelComment(1296));
        // 1294, a child of 1292 is a 2nd-level comment.
        $this->assertSame(1, $this->model->getIndexOfTopLevelComment(1294));

        // Comment 1002 on question 2002 is pending.
        $this->assertSame(-1, $this->model->getIndexOfTopLevelComment(1002));
        // Comment 1007 on question 2002 is suspended.
        // Suspended comment is shown in Question detail page
        $this->assertSame(1, $this->model->getIndexOfTopLevelComment(1007));

        $output = TestHelper::makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/'
                . urlencode(__FILE__) . '/' . __CLASS__ . '/getIndexOfTopLevelComment/useractive1');
        $this->assertEqual('', $output);

        $output = TestHelper::makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/'
                . urlencode(__FILE__) . '/' . __CLASS__ . '/getIndexOfTopLevelComment/useractive2');
        $this->assertEqual('', $output);

        $output = TestHelper::makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/'
                . urlencode(__FILE__) . '/' . __CLASS__ . '/getIndexOfTopLevelComment/modactive1');
        $this->assertEqual('', $output);

        $output = TestHelper::makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/'
                . urlencode(__FILE__) . '/' . __CLASS__ . '/getIndexOfTopLevelComment/contentmoderator');
        $this->assertEqual('', $output);
    }

    function getIndexOfTopLevelComment() {
        $user = Text::getSubstringAfter(get_instance()->uri->uri_string(), 'getIndexOfTopLevelComment/');
        $this->logIn($user);

        // Comment 1007 on question 2002 is suspended.
        // Suspended comment is shown in question detail page
        $this->assertTrue($this->model->getIndexOfTopLevelComment(1007) !== -1);
        // Comment 1311 on question 2040 is suspended.
        // Suspended comment is shown in question detail page
        $this->assertTrue($this->model->getIndexOfTopLevelComment(1311) !== -1);

        if ($user === 'contentmoderator')
            $expectedResult = 1;
        else if ($user === 'modactive1')
            // if there were a question to provide a permission check, modactive1 would have a different result (1)
            $expectedResult = -1;
        else
            $expectedResult = -1;

        // Comment 1002 on question 2002 is pending (and authored by contentmoderator).
        $this->assertSame($expectedResult, $this->model->getIndexOfTopLevelComment(1002));

        // if there were a question to provide a permission check, contentmoderator and modactive1 would have different results (1)
        if ($user === 'contentmoderator')
            $expectedResult = -1;
        else if ($user === 'modactive1')
            $expectedResult = -1;
        else
            $expectedResult = -1;

        // Comment 1010 on question 2003 is pending.
        $this->assertSame($expectedResult, $this->model->getIndexOfTopLevelComment(1010));
        // Comment 1306 on question 2040 is pending.
        $this->assertSame($expectedResult, $this->model->getIndexOfTopLevelComment(1306));

        $this->logOut();
    }

    function testUserPermissions() {
        list($fixtureInstance, $question, $comment) = $this->getFixtures(array(
            'QuestionActiveModActive',
            'CommentActiveUserActive',
        ));

        $this->_testUserPermissionsOnModel(array(array()), $this->model, 'create');
        $this->_testUserPermissionsOnModel(array($comment->ID, array()), $this->model, 'update');
        $this->_testUserPermissionsOnModel(array($comment->ID), $this->model, 'getUserFlag');
        $this->_testUserPermissionsOnModel(array($comment->ID), $this->model, 'flagComment');
        $this->_testUserPermissionsOnModel(array($comment->ID), $this->model, 'getUserRating');
        $this->_testUserPermissionsOnModel(array($comment->ID, 100), $this->model, 'rateComment');

        $fixtureInstance->destroy();
    }

    /**
     * Creates a comment using MAPI so that it is not decorated.  This is important because the author
     * is set when the decorator is applied and it cannot be changed without reflection.  Creating the
     * comment this way allows one author to create it and a different user to log in before it is
     * decorated
     */
    private function createActiveComment($question) {
        $comment = new Connect\CommunityComment();
        $comment->CommunityQuestion = $question->ID;
        $comment->StatusWithType->Status->ID = 33; // active
        $comment->CreatedByCommunityUser = $this->CI->model('CommunityUser')->get()->result;
        $comment->save();
        return $comment;
    }

        function testGetCommentCountByProductCategory() {
        $this->logIn();

        $response = $this->assertResponseObject($this->model->getCommentCountByProductCategory('Product', array(1), 5, 'comment_count'));
        $this->assertTrue($response->result[1] > 0, "Count of comments under the product: Mobile Phones");
        $count = $response->result[1];

        // create question and comments under the product: Mobile Phone
        $question = $this->CI->Model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        ))->result;
        $comment1 = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.Body' => (object) array('value' => 'bananas one'),
            'CommunityComment.StatusWithType.Status.ID' => (object) array('value' => 35)
        )))->result;
        $comment2 = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            'CommunityComment.Body' => (object) array('value' => 'bananas two')
        )))->result;

        //Verify that the count has increased
        $secondResponse = $this->assertResponseObject($this->model->getCommentCountByProductCategory('Product', array(1), 5, 'comment_count'));
        $this->assertTrue($secondResponse->result[1] > 0, "Count of questions under the product: Mobile Phones");
        $this->assertEqual($count + 1, $secondResponse->result[1]);

        $this->destroyObject($comment1);
        $this->destroyObject($comment2);
        $this->destroyObject($question);

        $this->logOut();
    }

    function testCreateAttachmentEntry(){
        $createAttachmentEntry = $this->getMethod('createAttachmentEntry');

        $mockComment = $this->model->getBlank()->result;

        $createAttachmentEntry($mockComment, null);
        $this->assertNull($mockComment->FileAttachments);

        $createAttachmentEntry($mockComment, array());
        $this->assertNull($mockComment->FileAttachments);

        $createAttachmentEntry($mockComment, array('newFiles' => array((object)array('localName' => 'tempNameDoesntMatter', 'contentType' => 'image/sheen', 'userName' => 'reinactedScenesFromPlatoon.jpg'))));
        $this->assertIsA($mockComment->FileAttachments, CONNECT_NAMESPACE_PREFIX . '\FileAttachmentCommunityArray');
        $this->assertIdentical(0, count($mockComment->FileAttachments));

        file_put_contents(\RightNow\Api::fattach_full_path('winning'), 'test data');

        $createAttachmentEntry($mockComment, array((object)array('localName' => 'winning', 'contentType' => 'image/sheen', 'userName' => 'tigersBlood.jpg')));
        $this->assertIsA($mockComment->FileAttachments, CONNECT_NAMESPACE_PREFIX . '\FileAttachmentCommunityArray');
        $this->assertIsA($mockComment->FileAttachments[0], CONNECT_NAMESPACE_PREFIX . '\FileAttachmentCommunity');
        $this->assertIdentical('image/sheen', $mockComment->FileAttachments[0]->ContentType);
        $this->assertIdentical('tigersBlood.jpg', $mockComment->FileAttachments[0]->FileName);

        unlink(\RightNow\Api::fattach_full_path('winning'));
    }
    
    function testGetTotalCommentCount() {
        $getTotalCount = $this->getMethod('getTotalCommentCount');

        $mockComment = $this->model->getBlank()->result;

        $count = $getTotalCount($mockComment, null);

        $this->assertTrue($count >= 0, "Count of Comments is less than 0");
    }
}
