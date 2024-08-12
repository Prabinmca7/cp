<?php

use RightNow\Utils\Text as Text;
use RightNow\Widgets;

use RightNow\Connect\v1_4 as Connect;

require_once(__DIR__.'/../../../reports/Grid/controller.php');

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);


class TestModerationGrid extends WidgetTestCase
{
    public $testingWidget = "standard/moderation/ModerationGrid";

    function testGetData()
    {
        $this->logIn('slatest');
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertTrue($data['js']['statuses']['deleted'] != '', 'Delete flag is not set');
    }

    function testModerateSocialObject()
    {
        $this->logOut();
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        ob_start();
        $this->widgetInstance->moderateSocialObject($parameters);
        $response = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertEqual('You must be logged in to perform this action.', $response->error[0], "Invalid error message when moderator is not logged-in");

        //create new question (as slatest because authors cannot delete or create flags on their own content)
        $this->logIn('slatest');
        $question = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'hello'),
            'CommunityQuestion.Body' => (object) array('value' => 'bacon pancakes'),
        ))->result;
        $this->logOut();

        //Create question with flags and test reset flags
        list($fixtureInstance, $questionActiveSingleComment) = $this->getFixtures(array(
            'QuestionActiveSingleComment'
        ));

        $this->logIn('contentmoderator');
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $statuses = $this->widgetInstance->CI->model($data['attrs']['object_type'])->getMappedSocialObjectStatuses()->result;
        $socialObjectMetaData = $this->widgetInstance->CI->model($data['attrs']['object_type'])->getSocialObjectMetadataMapping($data['attrs']['object_type'])->result;
        $allowedActions = array(
            key($statuses[$socialObjectMetaData['allowed_actions']['restore']]),
            key($statuses[$socialObjectMetaData['allowed_actions']['suspend']]),
            key($statuses[$socialObjectMetaData['allowed_actions']['delete']]),
            'reset_flags'
        );

        //update invalid question
        $parameters['action'] = key($statuses[STATUS_TYPE_SSS_QUESTION_SUSPENDED]);
        $parameters['object_ids'] = '00000';//some invalid ID
        ob_start();
        $this->widgetInstance->moderateSocialObject($parameters);
        $response = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertTrue(isset($response->error), 'Validation failed for invalid question');

        //change status to suspended
        $parameters['action'] = key($statuses[STATUS_TYPE_SSS_QUESTION_SUSPENDED]);
        $parameters['object_ids'] = $question->ID;

        ob_start();
        $this->widgetInstance->moderateSocialObject($parameters);
        $response = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertTrue(isset($response->success), 'Failed to update question');

        //Reset all flags
        $parameters['action'] = 'reset_flags';
        $parameters['object_ids'] = $question->ID;
        ob_start();
        $this->widgetInstance->moderateSocialObject($parameters);
        $response = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertEqual("The flags have been reset.", $response->success[0], "Message should be same");

        $parameters['action'] = 'reset_flags';
        $parameters['object_ids'] = $questionActiveSingleComment->ID;
        ob_start();
        $this->widgetInstance->moderateSocialObject($parameters);
        $response = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertEqual("The flags have been reset.", $response->success[0], "Message should be same");
        $fixtureInstance->destroy();

        //move question across product
        $parameters['action'] = 'move';
        $parameters['object_ids'] = $question->ID;
        $parameters['prodcat_id'] = 1;
        $parameters['prodcat_type'] = 'Product';
        ob_start();
        $this->widgetInstance->moderateSocialObject($parameters);
        $response = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertTrue(isset($response->success), 'Failed to move question');

        //move question across category
        $parameters['action'] = 'move';
        $parameters['object_ids'] = $question->ID;
        $parameters['prodcat_id'] = 153;
        $parameters['prodcat_type'] = 'Category';
        ob_start();
        $this->widgetInstance->moderateSocialObject($parameters);
        $response = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertTrue(isset($response->success), 'Failed to move question');

        //pass invalid action for question but valid for user e.g archive
        $parameters['action'] = 'archive';
        $parameters['object_ids'] = 2; //some invalid ID
        ob_start();
        $this->widgetInstance->moderateSocialObject($parameters);
        $response = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertTrue(isset($response->error[0]), 'Your requested action is not supported');

        //Delete question
        $parameters['action'] = key($statuses[STATUS_TYPE_SSS_QUESTION_DELETED]);
        $parameters['object_ids'] = $question->ID;
        ob_start();
        $this->widgetInstance->moderateSocialObject($parameters);
        $response = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertTrue(isset($response->success), 'Failed to update question');

        //Try to update the  staus for the deleted question and check if we are getting error
        $parameters['action'] = key($statuses[STATUS_TYPE_SSS_QUESTION_DELETED]);
        $parameters['object_ids'] = $question->ID;
        ob_start();
        $this->widgetInstance->moderateSocialObject($parameters);
        $response = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertTrue(isset($response->error), 'Failed to update question');

        //Suspend multiple question and look for correct error
        $question2 = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'hello2'),
            'CommunityQuestion.Body' => (object) array('value' => 'bacon pancakes2'),
        ))->result;
        $question3 = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'hello3'),
            'CommunityQuestion.Body' => (object) array('value' => 'bacon pancakes3'),
        ))->result;
        $parameters['action'] = key($statuses[STATUS_TYPE_SSS_QUESTION_SUSPENDED]);
        $parameters['object_ids'] = $question2->ID. ','. $question3->ID;
        ob_start();
        $this->widgetInstance->moderateSocialObject($parameters);
        $response = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertEqual("The questions have been suspended.", $response->success[0], "Message should be same");

        //Restore single question and look for correct error
        $parameters['action'] = key($statuses[STATUS_TYPE_SSS_QUESTION_ACTIVE]);
        $parameters['object_ids'] = $question2->ID;
        ob_start();
        $this->widgetInstance->moderateSocialObject($parameters);
        $response = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertEqual("The question has been approved/restored.", $response->success[0], "Message should be same");

        $this->destroyObject($question);
        $this->destroyObject($question2);
        $this->destroyObject($question3);
        $this->logOut();
    }

    function testModerateSocialUserObject()
    {
        $this->logIn('slatest');
        $this->createWidgetInstance();
        $this->setWidgetAttributes(array("object_type"=>"CommunityUser"));
        $data = $this->getWidgetData();
        $statuses = $this->widgetInstance->CI->model($data['attrs']['object_type'])->getMappedSocialObjectStatuses()->result;
        $socialObjectMetaData = $this->widgetInstance->CI->model($data['attrs']['object_type'])->getSocialObjectMetadataMapping($data['attrs']['object_type'])->result;

        // create a question to perform action on
        $question = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'hello'),
            'CommunityQuestion.Body' => (object) array('value' => 'bacon pancakes'),
        ))->result;

        //update invalid question
        $parameters['action'] = key($statuses[STATUS_TYPE_SSS_USER_SUSPENDED]);
        $parameters['object_ids'] = 'AAAA';//some invalid ID
        ob_start();
        $this->widgetInstance->moderateSocialObject($parameters);
        $response = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertTrue(isset($response->error), 'Validation failed for invalid user');

        //suspend author action on self
        $parameters['action'] = key($statuses[STATUS_TYPE_SSS_USER_SUSPENDED]);
        $parameters['object_ids'] = $question->ID;
        ob_start();
        $this->widgetInstance->moderateSocialObject($parameters);
        $response = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertTrue(isset($response->error), 'Validation failed for self action');
        $this->assertEqual('Your requested action has been processed, but with the following errors:',$response->error[0], "Message should be same");

        //pass some invalid action for user but valid for question and comment
        $parameters['action'] = 'reset_flags';
        $parameters['object_ids'] = 2; //some invalid ID
        ob_start();
        $this->widgetInstance->moderateSocialObject($parameters);
        $response = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertTrue(isset($response->error[0]), 'Your requested action is not supported');

        //pass invalid action for user but valid for question e.g move
        $parameters['action'] = 'move';
        $parameters['object_ids'] = 2; //some invalid ID
        ob_start();
        $this->widgetInstance->moderateSocialObject($parameters);
        $response = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertTrue(isset($response->error[0]), 'Your requested action is not supported');

        //pass random invalid action
        $parameters['action'] = 'invalid_action';
        $parameters['object_ids'] = 2;//some invalid ID
        ob_start();
        $this->widgetInstance->moderateSocialObject($parameters);
        $response = json_decode(ob_get_contents());
        ob_end_clean();
        $this->assertTrue(isset($response->error), 'Validation failed for invalid action');

        $this->destroyObject($question);
    }

    function testGetDefaultAvatar () {
        $this->createWidgetInstance();
        $this->setWidgetAttributes(array("object_type" => "CommunityUser"));
        $getDefaultAvatar = $this->getWidgetMethod('getDefaultAvatar', $this->instance);
        $actual = $getDefaultAvatar('<a href="/app/public_profile/user/12">useractive1</a>');
        $expected = array("text" => 'U', "color" => 1);
        $this->assertIdentical($expected, $actual);
    }

    function testGetFormattedErrorMessage() {
        $this->logIn('slatest');
        $this->createWidgetInstance();
        $getFormattedErrorMessage = $this->getWidgetMethod('getFormattedErrorMessage');
        $this->assertEqual("not exist <span>(Question ID: -11)</span>", $getFormattedErrorMessage('CommunityQuestion', -11 , "not exist"), "Message should be same");

        list($fixtureInstance, $questionActiveModActive) = $this->getFixtures(array(
            'QuestionActiveModActive'
        ));
        $this->assertEqual("<i>Question with active admin</i> - No Permission <span>(Question ID: $questionActiveModActive->ID)</span>",  $getFormattedErrorMessage('CommunityQuestion', $questionActiveModActive->ID , "No Permission"), "Message should be same");
        $fixtureInstance->destroy();

        //check proper formatting of the error message
        list($fixtureInstance, $questionWithFormatting, $commentWithFormatting) = $this->getFixtures(array(
            'QuestionWithFormatting', 'CommentWithFormatting'
        ));
        $this->assertEqual("<i>Comment by an active user with special formatting . </i> - User does not have permission to edit this comment. <span>(Comment ID: $commentWithFormatting->ID)</span>", $getFormattedErrorMessage('CommunityComment', $commentWithFormatting->ID , "User does not have permission to edit this comment."), "Message should be same");
        $fixtureInstance->destroy();
    }

    function testGetDataOfQuestionsWithCategory() {
        $this->logIn('slatest');
        $this->createWidgetInstance();
        $this->setWidgetAttributes(array("per_page" => 1, "report_id" => 15100, "object_type" => "CommunityQuestion", "prodcat_type" => "Category", "product_column_index" => 3, "category_column_index" => 9));
        $data = $this->getWidgetData();
        $this->assertEqual("Category", $data['tableData']['headers'][$data['attrs']['product_column_index'] - 1]['heading']);
        $this->assertEqual("Category", $data['js']['headers'][$data['attrs']['product_column_index'] - 1]['heading']);
    }

    function testGetDataCommentsWithCategory() {
        $this->logIn('slatest');
        $this->setWidgetAttributes(array("per_page" => 1, "report_id" => 15101, "object_type" => "CommunityComment", "prodcat_type" => "Category", "product_column_index" => 4, "category_column_index" => 10));
        $data = $this->getWidgetData();
        $this->assertEqual("Category", $data['tableData']['headers'][$data['attrs']['product_column_index'] - 1]['heading']);
        $this->assertEqual("Category", $data['js']['headers'][$data['attrs']['product_column_index'] - 1]['heading']);
    }

}
