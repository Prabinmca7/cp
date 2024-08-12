<?php

use RightNow\Widgets;
use RightNow\Connect\v1_4 as Connect;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestModerationInlineAction extends WidgetTestCase {

    public $testingWidget = "standard/moderation/ModerationInlineAction";

    function setUp () {
        $this->createWidgetInstance();
        parent::setUp();
    }

    function testSaveModeratorAction () {
        //Log in as normal user
        $this->logIn('useractive1');
        //create new question
        $question = $this->CI->model('CommunityQuestion')->create(array(
                'CommunityQuestion.Subject' => (object) array('value' => 'Test Question Subject'),
                'CommunityQuestion.Body' => (object) array('value' => 'Test Question Body'),
            ))->result;
        //create new comment
        $comment = $this->CI->model('CommunityComment')->create(array(
                'CommunityComment.Body' => (object) array('value' => 'Test Comment Body'),
                'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
            ))->result;
        $this->logOut();

        list($fixtureInstance, $deletedQuestion) = $this->getFixtures(array('QuestionDeleted'));

        //Log in as Moderator
        $this->logIn('doug@rightnow.com.invalid');
        $this->widgetInstance->data['attrs']['object_type'] = 'CommunityQuestion';
        $saveModeratorAction = $this->getWidgetMethod('saveModeratorAction');

        //perform suspend action on invalid question
        $this->widgetInstance->data['attrs']['object_id'] = 0;
        $parameters = array('actionID' => 30, 'objectType' => 'CommunityQuestion');
        list(, $response) = $this->returnResultAndContent($saveModeratorAction, $parameters);
        $response = json_decode($response);
        $this->assertTrue(isset($response->error), 'Validation failed for invalid question');

        //set valid question ID
        $this->widgetInstance->data['attrs']['object_id'] = $question->ID;

        //perform suspend action on question when user is not logged in
        $this->logOut();
        $parameters = array('actionID' => 30, 'objectType' => 'CommunityQuestion');
        list(, $response) = $this->returnResultAndContent($saveModeratorAction, $parameters);
        $response = json_decode($response);
        $this->assertTrue(isset($response->error), 'Validation failed for logged out user');

        //log-in the moderator
        $this->logIn('doug@rightnow.com.invalid');

        //perform suspend action on question
        $parameters = array('actionID' => 30, 'objectType' => 'CommunityQuestion');
        list(, $response) = $this->returnResultAndContent($saveModeratorAction, $parameters);
        $response = json_decode($response);
        $this->assertEqual($response->updatedObject, (object) array("objectType" => "CommunityQuestion", "ID" => $question->ID, "statusID" => 30, "statusWithTypeID" => 23, "successMessage"=> "The question has been suspended.", "isContentLocked" => false), "Social question suspend action failed");

        //perform restore action on question
        $parameters = array('actionID' => 29, 'objectType' => 'CommunityQuestion');
        list(, $response) = $this->returnResultAndContent($saveModeratorAction, $parameters);
        $response = json_decode($response);
        $this->assertEqual($response->updatedObject, (object) array("objectType" => "CommunityQuestion", "ID" => $question->ID, "statusID" => 29, "statusWithTypeID" => 22, "successMessage"=> "The question has been approved/restored.", "isContentLocked" => false), "Social question restore action failed");

        //lock question
        $parameters = array('actionID' => 'lock', 'objectType' => 'CommunityQuestion');
        list(, $response) = $this->returnResultAndContent($saveModeratorAction, $parameters);
        $response = json_decode($response);
        $this->assertEqual($response->updatedObject, (object) array("objectType" => "CommunityQuestion", "ID" => $question->ID, "statusID" => 29, "statusWithTypeID" => 22, "successMessage"=> "The question has been locked.", "isContentLocked" => true), "Social question lock action failed");

        //perform suspend on question author
        $parameters = array('actionID' => 39, 'objectType' => 'CommunityUser');
        list(, $response) = $this->returnResultAndContent($saveModeratorAction, $parameters);
        $response = json_decode($response);
        $this->assertEqual($response->updatedObject, (object) array("objectType" => "CommunityUser", "ID" => $question->CreatedByCommunityUser->ID, "statusID" => 39, "statusWithTypeID" => 32, "successMessage"=> "The user has been suspended.", "isContentLocked" => null), "Social question author suspend action failed");

        //perform restore action on question author
        $parameters = array('actionID' => 38, 'objectType' => 'CommunityUser');
        list(, $response) = $this->returnResultAndContent($saveModeratorAction, $parameters);
        $response = json_decode($response);
        $this->assertEqual($response->updatedObject, (object) array("objectType" => "CommunityUser", "ID" => $question->CreatedByCommunityUser->ID, "statusID" => 38, "statusWithTypeID" => 31, "successMessage"=> "The user has been approved/restored.", "isContentLocked" => null), "Social question author restore action failed");

        $this->widgetInstance->data['attrs']['object_id'] = $comment->ID;
        $this->widgetInstance->data['attrs']['object_type'] = 'CommunityComment';

        //perform suspend action on comment
        $parameters = array('actionID' => 34, 'objectType' => 'CommunityComment');
        list(, $response) = $this->returnResultAndContent($saveModeratorAction, $parameters);
        $response = json_decode($response);
        $this->assertEqual($response->updatedObject, (object) array("objectType" => "CommunityComment", "ID" => $comment->ID, "statusID" => 34, "statusWithTypeID" => 27, "successMessage"=> "The comment has been suspended."), "Social comment suspend action failed");

        //perform restore action on comment
        $parameters = array('actionID' => 33, 'objectType' => 'CommunityComment');
        list(, $response) = $this->returnResultAndContent($saveModeratorAction, $parameters);
        $response = json_decode($response);
        $this->assertEqual($response->updatedObject, (object) array("objectType" => "CommunityComment", "ID" => $comment->ID, "statusID" => 33, "statusWithTypeID" => 26, "successMessage"=> "The comment has been approved/restored."), "Social comment restore action failed");

        //clean up
        $this->destroyObject($comment);
        $this->destroyObject($question);

        //perform suspend author on a deleted question
        $this->widgetInstance->data['attrs']['object_id'] = $deletedQuestion->ID;
        $this->widgetInstance->data['attrs']['object_type'] = 'CommunityQuestion';
        $saveModeratorAction = $this->getWidgetMethod('saveModeratorAction');

        $parameters = array('actionID' => 39, 'objectType' => 'CommunityUser');
        list(, $response) = $this->returnResultAndContent($saveModeratorAction, $parameters);
        $response = json_decode($response);
        $this->assertTrue(isset($response->error), 'Validation failed for invalid question');

        //clean up
        $this->destroyObject($deletedQuestion);

        //testing status change for CommunityUser
        $this->widgetInstance->data['attrs']['object_type'] = 'CommunityUser';
        $saveModeratorAction = $this->getWidgetMethod('saveModeratorAction');

        //perform suspend action on invalid user
        $this->widgetInstance->data['attrs']['object_id'] = 0;
        $parameters = array('actionID' => 39, 'objectType' => 'CommunityUser');
        list(, $response) = $this->returnResultAndContent($saveModeratorAction, $parameters);
        $response = json_decode($response);
        $this->assertTrue(isset($response->error), 'Validation failed for invalid user');

        list($fixtureInstance, $user) = $this->getFixtures(array('UserActive1'));

        //set valid user ID
        $this->widgetInstance->data['attrs']['object_id'] = $user->ID;

        //perform suspend action on question when user is not logged in
        $this->logOut();
        $parameters = array('actionID' => 39, 'objectType' => 'CommunityUser');
        list(, $response) = $this->returnResultAndContent($saveModeratorAction, $parameters);
        $response = json_decode($response);
        $this->assertTrue(isset($response->error), 'Validation failed for logged out user');

        //log-in the moderator
        $this->logIn('doug@rightnow.com.invalid');

        //perform suspend action on user
        $parameters = array('actionID' => 39, 'objectType' => 'CommunityUser');
        list(, $response) = $this->returnResultAndContent($saveModeratorAction, $parameters);
        $response = json_decode($response);
        $this->assertEqual($response->updatedObject, (object) array("objectType" => "CommunityUser", "ID" => $user->ID, "statusID" => 39, "statusWithTypeID" => 32, "successMessage"=> "The user has been suspended."), "Social user suspend action failed");

        //perform archive action on user
        $parameters = array('actionID' => 41, 'objectType' => 'CommunityUser');
        list(, $response) = $this->returnResultAndContent($saveModeratorAction, $parameters);
        $response = json_decode($response);
        $this->assertEqual($response->updatedObject, (object) array("objectType" => "CommunityUser", "ID" => $user->ID, "statusID" => 41, "statusWithTypeID" => 34, "successMessage"=> "The user has been archived."), "Social user archive action failed");

        //perform restore action on user
        $parameters = array('actionID' => 38, 'objectType' => 'CommunityUser');
        list(, $response) = $this->returnResultAndContent($saveModeratorAction, $parameters);
        $response = json_decode($response);
        $this->assertEqual($response->updatedObject, (object) array("objectType" => "CommunityUser", "ID" => $user->ID, "statusID" => 38, "statusWithTypeID" => 31, "successMessage"=> "The user has been approved/restored."), "Social user restore action failed");

        //create new user for deletion
        list($fixtureInstance, $newSocialUser) = $this->getFixtures(array('UserActive1'));
        $this->logOut();

        //login as super moderator for delete action
        $this->logIn('slatest');
        $this->widgetInstance->data['attrs']['object_id'] = $newSocialUser->ID;
        $parameters = array('actionID' => 40, 'objectType' => 'CommunityUser');
        list(, $response) = $this->returnResultAndContent($saveModeratorAction, $parameters);
        $response = json_decode($response);
        $this->assertEqual($response->updatedObject, (object) array("objectType" => "CommunityUser", "ID" => $newSocialUser->ID, "statusID" => 40, "statusWithTypeID" => 33, "successMessage"=> "The user has been deleted."), "Social user delete action failed");
        $fixtureInstance->destroy();
        $this->logOut();
    }
}
